<?php

function tounColonnes(PDO $con, string $table): array
{
    try {
        return $con->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function tounNumeroCertificat(): string
{
    return 'TOUN-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function tounQuizFormationValides(PDO $con, int $idUser, int $idForm): bool
{
    $colonnesQuiz = tounColonnes($con, 't_quiz');

    if (!$colonnesQuiz || !tounColonnes($con, 't_resultats_quiz')) {
        return true;
    }

    $filtre = '';

    if (in_array('type_quiz', $colonnesQuiz, true)) {
        $stmt = $con->prepare("
            SELECT COUNT(*)
            FROM t_quiz q
            INNER JOIN t_modules m ON m.id_module = q.id_module
            WHERE m.id_form = ? AND q.type_quiz <> 'module'
        ");
        $stmt->execute([$idForm]);

        if ((int) $stmt->fetchColumn() > 0) {
            $filtre = "AND q.type_quiz <> 'module'";
        }
    }

    $stmt = $con->prepare("
        SELECT COUNT(*)
        FROM t_quiz q
        INNER JOIN t_modules m ON m.id_module = q.id_module
        WHERE m.id_form = ?
          $filtre
          AND NOT EXISTS (
              SELECT 1
              FROM t_resultats_quiz r
              WHERE r.id_quiz = q.id_quiz
                AND r.id_user = ?
                AND r.statut_resultat = 'reussi'
          )
    ");
    $stmt->execute([$idForm, $idUser]);

    return (int) $stmt->fetchColumn() === 0;
}

function tounFormationAQuiz(PDO $con, int $idForm): bool
{
    if (!tounColonnes($con, 't_quiz')) {
        return false;
    }

    $stmt = $con->prepare("
        SELECT COUNT(*)
        FROM t_quiz q
        INNER JOIN t_modules m ON m.id_module = q.id_module
        WHERE m.id_form = ?
    ");
    $stmt->execute([$idForm]);

    return (int) $stmt->fetchColumn() > 0;
}

function tounPourcentCours(PDO $con, int $idUser, int $idForm): int
{
    $stmt = $con->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(EXISTS (
                SELECT 1
                FROM t_suivi_contenus s
                WHERE s.id_user = ? AND s.type_element = 'lecon' AND s.id_element = l.id_lecon
            )) AS faits
        FROM t_lecons l
        INNER JOIN t_modules m ON m.id_module = l.id_module
        WHERE m.id_form = ?
    ");
    $stmt->execute([$idUser, $idForm]);
    $lecons = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $con->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(EXISTS (
                SELECT 1
                FROM t_suivi_contenus s
                WHERE s.id_user = ? AND s.type_element = 'module' AND s.id_element = m.id_module
            )) AS faits
        FROM t_modules m
        WHERE m.id_form = ?
          AND NOT EXISTS (SELECT 1 FROM t_lecons l WHERE l.id_module = m.id_module)
    ");
    $stmt->execute([$idUser, $idForm]);
    $modules = $stmt->fetch(PDO::FETCH_ASSOC);

    $total = (int) $lecons['total'] + (int) $modules['total'];
    $faits = (int) $lecons['faits'] + (int) $modules['faits'];

    return $total > 0 ? (int) round($faits * 100 / $total) : 0;
}

function tounEnregistrerProgression(PDO $con, int $idUser, int $idForm, int $pourcentCours): array
{
    $pourcentCours = max(0, min(100, $pourcentCours));
    $avecQuiz = tounFormationAQuiz($con, $idForm);
    $evaluationReussie = $avecQuiz && tounQuizFormationValides($con, $idUser, $idForm);

    if ($avecQuiz) {
        $pourcentage = $pourcentCours >= 100 && $evaluationReussie ? 100 : (int) floor($pourcentCours * 0.9);
    } else {
        $pourcentage = $pourcentCours;
    }

    $statut = $pourcentage >= 100 ? 'termine' : ($pourcentage > 0 ? 'en_cours' : 'non_commence');

    $stmt = $con->prepare('SELECT id_user FROM t_progression WHERE id_user = ? AND id_form = ? LIMIT 1');
    $stmt->execute([$idUser, $idForm]);

    if ($stmt->fetch()) {
        $stmt = $con->prepare(
            'UPDATE t_progression SET pourcent_avancement = ?, statut_progression = ?, date_derniere_connexion = NOW() WHERE id_user = ? AND id_form = ?'
        );
        $stmt->execute([$pourcentage, $statut, $idUser, $idForm]);
    } else {
        $stmt = $con->prepare(
            'INSERT INTO t_progression (id_user, id_form, pourcent_avancement, date_debut, date_derniere_connexion, statut_progression) VALUES (?, ?, ?, NOW(), NOW(), ?)'
        );
        $stmt->execute([$idUser, $idForm, $pourcentage, $statut]);
    }

    if ($pourcentage >= 100) {
        $stmt = $con->prepare("UPDATE t_inscriptions SET statut_inscrip = 'terminee' WHERE id_user = ? AND id_form = ? AND statut_inscrip = 'active'");
        $stmt->execute([$idUser, $idForm]);
        tounDelivrerCertificats($con, $idUser);
    } else {
        $stmt = $con->prepare("UPDATE t_inscriptions SET statut_inscrip = 'active' WHERE id_user = ? AND id_form = ? AND statut_inscrip = 'terminee'");
        $stmt->execute([$idUser, $idForm]);
    }

    return [
        'pourcentage' => $pourcentage,
        'statut' => $statut,
        'cours_termines' => $pourcentCours >= 100,
        'evaluation_requise' => $avecQuiz,
        'evaluation_reussie' => $evaluationReussie,
    ];
}

function tounCorrigerProgressions(PDO $con, ?int $idUser = null): int
{
    if (!tounColonnes($con, 't_progression')) {
        return 0;
    }

    $sql = "
        SELECT i.id_user, i.id_form
        FROM t_inscriptions i
        LEFT JOIN t_progression p ON p.id_user = i.id_user AND p.id_form = i.id_form
        WHERE (i.statut_inscrip = 'terminee' OR p.pourcent_avancement >= 100)
    ";
    $valeurs = [];

    if ($idUser !== null) {
        $sql .= ' AND i.id_user = ?';
        $valeurs[] = $idUser;
    }

    $stmt = $con->prepare($sql);
    $stmt->execute($valeurs);
    $corrigees = 0;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $user = (int) $ligne['id_user'];
        $form = (int) $ligne['id_form'];

        if (!tounFormationAQuiz($con, $form) || tounQuizFormationValides($con, $user, $form)) {
            continue;
        }

        $maj = $con->prepare("UPDATE t_progression SET pourcent_avancement = 90, statut_progression = 'en_cours' WHERE id_user = ? AND id_form = ?");
        $maj->execute([$user, $form]);

        $maj = $con->prepare("UPDATE t_inscriptions SET statut_inscrip = 'active' WHERE id_user = ? AND id_form = ? AND statut_inscrip = 'terminee'");
        $maj->execute([$user, $form]);

        $corrigees++;
    }

    return $corrigees;
}

function tounDelivrerCertificats(PDO $con, int $idUser): int
{
    tounCorrigerProgressions($con, $idUser);

    $colonnes = tounColonnes($con, 't_certificats');

    if (!in_array('id_user', $colonnes, true)) {
        return 0;
    }

    $avecProgression = (bool) tounColonnes($con, 't_progression');

    $sql = "
        SELECT i.id_inscrip, i.id_form
        FROM t_inscriptions i
        " . ($avecProgression ? 'LEFT JOIN t_progression p ON p.id_user = i.id_user AND p.id_form = i.id_form' : '') . "
        WHERE i.id_user = ?
          AND (LOWER(i.statut_inscrip) = 'terminee'" . ($avecProgression ? ' OR p.pourcent_avancement >= 100' : '') . ")
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute([$idUser]);
    $terminees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $delivres = 0;

    foreach ($terminees as $ligne) {
        if (!tounQuizFormationValides($con, $idUser, (int) $ligne['id_form'])) {
            continue;
        }

        $conditions = ['id_user = ?'];
        $valeurs = [$idUser];

        if (in_array('id_form', $colonnes, true)) {
            $conditions[] = 'id_form = ?';
            $valeurs[] = (int) $ligne['id_form'];
        } elseif (in_array('id_inscrip', $colonnes, true)) {
            $conditions[] = 'id_inscrip = ?';
            $valeurs[] = (int) $ligne['id_inscrip'];
        }

        $verif = $con->prepare('SELECT COUNT(*) FROM t_certificats WHERE ' . implode(' AND ', $conditions));
        $verif->execute($valeurs);

        if ((int) $verif->fetchColumn() > 0) {
            continue;
        }

        $donnees = ['id_user' => $idUser];

        if (in_array('id_form', $colonnes, true)) {
            $donnees['id_form'] = (int) $ligne['id_form'];
        }

        if (in_array('id_inscrip', $colonnes, true)) {
            $donnees['id_inscrip'] = (int) $ligne['id_inscrip'];
        }

        if (in_array('numero_certificat', $colonnes, true)) {
            $donnees['numero_certificat'] = tounNumeroCertificat();
        }

        if (in_array('date_obtention', $colonnes, true)) {
            $donnees['date_obtention'] = date('Y-m-d H:i:s');
        }

        if (in_array('lien_certificat', $colonnes, true)) {
            $donnees['lien_certificat'] = '';
        }

        $insertion = $con->prepare(
            'INSERT INTO t_certificats (' . implode(', ', array_keys($donnees)) . ')
             VALUES (' . implode(', ', array_fill(0, count($donnees), '?')) . ')'
        );
        $insertion->execute(array_values($donnees));
        $delivres++;
    }

    return $delivres;
}


function tounCertificat(PDO $con, int $idCertificat, int $idUser): ?array
{
    $stmt = $con->prepare("
        SELECT
            c.*,
            f.titre_form,
            f.duree_form,
            f.niveau_form,
            u.nom_user,
            u.prenom_user,
            (
                SELECT MIN(i.date_inscrip_form)
                FROM t_inscriptions i
                WHERE i.id_user = c.id_user AND i.id_form = c.id_form
            ) AS date_debut,
            TRIM(CONCAT(COALESCE(uf.prenom_user, ''), ' ', COALESCE(uf.nom_user, ''))) AS nom_formateur
        FROM t_certificats c
        INNER JOIN t_formations f ON f.id_form = c.id_form
        INNER JOIN t_utilisateurs u ON u.id_user = c.id_user
        LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur
        LEFT JOIN t_utilisateurs uf ON uf.id_user = fo.id_user
        WHERE c.id_certificat = ? AND c.id_user = ?
        LIMIT 1
    ");
    $stmt->execute([$idCertificat, $idUser]);
    $certificat = $stmt->fetch(PDO::FETCH_ASSOC);

    return $certificat ?: null;
}

function tounAfficherCertificat(array $certificat, bool $imprimer = false, string $retour = ''): void
{
    $e = fn($valeur) => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
    $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $fin = !empty($certificat['date_obtention']) ? strtotime($certificat['date_obtention']) : time();
    $debut = !empty($certificat['date_debut']) ? strtotime($certificat['date_debut']) : $fin;
    $nom = trim(($certificat['prenom_user'] ?? '') . ' ' . mb_strtoupper((string) ($certificat['nom_user'] ?? ''), 'UTF-8'));

    $donnees = [
        'nom' => $nom !== '' ? $nom : 'Apprenant TOUN',
        'formation' => mb_strtoupper((string) $certificat['titre_form'], 'UTF-8'),
        'debut' => date('d/m/Y', min($debut, $fin)),
        'fin' => date('d/m/Y', $fin),
        'fait_le' => date('j', $fin) . ' ' . $mois[(int) date('n', $fin) - 1] . ' ' . date('Y', $fin),
        'formateur' => trim((string) ($certificat['nom_formateur'] ?? '')),
        'numero' => (string) ($certificat['numero_certificat'] ?? ''),
    ];

    $nomFichier = 'Certificat-TOUN-' . trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $donnees['nom']) ?: 'apprenant'), '-');
    ?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Certificat - <?= $e($certificat['titre_form']) ?> | TOUN</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
        <style>
            @page {
                size: A4 landscape;
                margin: 0;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                padding: 24px 16px 40px;
                background: #eaf3f9;
                font-family: 'Poppins', Arial, sans-serif;
                color: #18213f;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                margin-bottom: 20px;
            }

            .actions button,
            .actions a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 11px 20px;
                border: 1px solid transparent;
                border-radius: 10px;
                background: linear-gradient(135deg, #0b5a91, #20a1b8);
                color: #fff;
                font: 600 14px 'Poppins', sans-serif;
                text-decoration: none;
                cursor: pointer;
            }

            .actions .secondaire {
                background: #fff;
                border-color: #cfe0ec;
                color: #0b5a91;
            }

            .certificat {
                display: block;
                width: 100%;
                max-width: 1050px;
                height: auto;
                margin: 0 auto;
                background: #fff;
                box-shadow: 0 20px 50px rgba(24, 33, 63, 0.15);
            }

            .numero {
                margin: 14px 0 0;
                text-align: center;
                font-size: 12.5px;
                color: #5b6b82;
            }

            @media print {
                body {
                    padding: 0;
                    background: #fff;
                }

                .actions,
                .numero {
                    display: none;
                }

                .certificat {
                    width: auto;
                    max-width: none;
                    height: 200mm;
                    margin: 5mm auto 0;
                    box-shadow: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="actions">
            <button type="button" id="telecharger">Télécharger le certificat</button>
            <button type="button" class="secondaire" onclick="window.print()">Imprimer</button>
            <?php if ($retour !== ''): ?>
                <a class="secondaire" href="<?= $e($retour) ?>">Retour</a>
            <?php endif; ?>
        </div>

        <canvas class="certificat" id="certificat" width="2896" height="2172" aria-label="Certificat de <?= $e($donnees['nom']) ?>"></canvas>

        <p class="numero">Certificat n° <?= $e($donnees['numero']) ?></p>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script>
            const donnees = <?= json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
            const nomFichier = <?= json_encode($nomFichier) ?>;
            const canvas = document.getElementById('certificat');
            const ctx = canvas.getContext('2d');
            const police = 'Poppins, Arial, sans-serif';

            function ajuster(texte, poids, taille, largeurMax, minimum) {
                let t = taille;
                ctx.font = `${poids} ${t}px ${police}`;

                while (ctx.measureText(texte).width > largeurMax && t > minimum) {
                    t -= 1;
                    ctx.font = `${poids} ${t}px ${police}`;
                }
            }

            function segments(parties, x, y, taille) {
                parties.forEach(([texte, poids]) => {
                    ctx.font = `${poids} ${taille}px ${police}`;
                    ctx.fillText(texte, x, y);
                    x += ctx.measureText(texte).width;
                });
            }

            function dessiner(fond) {
                ctx.setTransform(2, 0, 0, 2, 0, 0);
                ctx.drawImage(fond, 0, 0, 1448, 1086);
                ctx.textBaseline = 'alphabetic';
                ctx.fillStyle = '#18213f';

                ctx.textAlign = 'left';
                ajuster(donnees.nom, 600, 30, 640, 18);
                ctx.fillText(donnees.nom, 440, 391);

                ctx.textAlign = 'center';
                ctx.fillStyle = '#ffffff';
                ajuster('« ' + donnees.formation + ' »', 700, 32, 1100, 16);
                ctx.fillText('« ' + donnees.formation + ' »', 726, 557);

                ctx.textAlign = 'left';
                ctx.fillStyle = '#18213f';
                segments([
                    ['Cette formation s’est tenue du ', 400],
                    [donnees.debut, 600],
                    [' au ', 400],
                    [donnees.fin, 600],
                    [', à ', 400],
                    ['Cocody', 700],
                    ['.', 400]
                ], 162, 636, 24);

                ctx.textAlign = 'right';
                ctx.font = `italic 400 24px ${police}`;
                ctx.fillText('Fait à Cocody, le ' + donnees.fait_le, 1376, 757);

                if (donnees.formateur) {
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#0b5a91';
                    ajuster(donnees.formateur, 500, 20, 260, 13);
                    ctx.fillText(donnees.formateur, 287, 893);
                }

                ctx.textAlign = 'center';
                ctx.fillStyle = '#5b6b82';
                ctx.font = `500 15px ${police}`;
                ctx.fillText('N° ' + donnees.numero, 726, 1068);
            }

            function telecharger() {
                const image = canvas.toDataURL('image/jpeg', 0.92);

                if (window.jspdf) {
                    const pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
                    const hauteur = 210;
                    const largeur = hauteur * 1448 / 1086;
                    pdf.addImage(image, 'JPEG', (297 - largeur) / 2, 0, largeur, hauteur);
                    pdf.save(nomFichier + '.pdf');
                    return;
                }

                const lien = document.createElement('a');
                lien.href = image;
                lien.download = nomFichier + '.jpg';
                lien.click();
            }

            const fond = new Image();
            fond.onload = async () => {
                await Promise.all([
                    document.fonts.load('400 24px Poppins'),
                    document.fonts.load('italic 400 24px Poppins'),
                    document.fonts.load('500 20px Poppins'),
                    document.fonts.load('600 30px Poppins'),
                    document.fonts.load('700 32px Poppins')
                ]).catch(() => null);

                dessiner(fond);

                <?php if ($imprimer): ?>
                setTimeout(() => window.print(), 400);
                <?php endif; ?>
            };
            fond.src = '../images/certificat_fond.jpg';

            document.getElementById('telecharger').addEventListener('click', telecharger);
        </script>
    </body>
</html>
<?php
}
