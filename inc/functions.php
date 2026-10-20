<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_price(int $fcfa): string
{
    if ($fcfa <= 0) {
        return 'Gratuit';
    }
    return number_format($fcfa, 0, ',', ' ') . ' FCFA';
}

function format_duration(int $minutes): string
{
    if ($minutes <= 0) {
        return '—';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) {
        return $m . 'm';
    }
    return $m === 0 ? $h . 'h' : $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
}

function get_categories_with_counts(PDO $pdo): array
{
    $sql = "SELECT c.id_cat, c.nom_cat, COUNT(f.id_form) AS total
            FROM t_categorie c
            LEFT JOIN t_formation f ON f.id_cat = c.id_cat
            GROUP BY c.id_cat, c.nom_cat
            ORDER BY c.nom_cat ASC";
    return $pdo->query($sql)->fetchAll();
}

function get_facet_counts(PDO $pdo): array
{
    $niveaux = $pdo
        ->query('SELECT niveau_form, COUNT(*) AS total FROM t_formation GROUP BY niveau_form')
        ->fetchAll(PDO::FETCH_KEY_PAIR);

    $prix = $pdo->query('SELECT SUM(prix_form = 0) AS gratuit, SUM(prix_form > 0) AS payant FROM t_formation')->fetch();

    $durees = $pdo
        ->query(
            "SELECT
            SUM(total_minutes < 120) AS moins_2h,
            SUM(total_minutes BETWEEN 120 AND 599) AS de_2h_a_10h,
            SUM(total_minutes BETWEEN 600 AND 1799) AS de_10h_a_30h,
            SUM(total_minutes >= 1800) AS plus_30h
         FROM (
            SELECT f.id_form, COALESCE(SUM(m.duree_module), 0) AS total_minutes
            FROM t_formation f
            LEFT JOIN t_module m ON m.id_form = f.id_form
            GROUP BY f.id_form
         ) AS duree_par_formation"
        )
        ->fetch();

    return [
        'niveau' => [
            'debutant' => (int) ($niveaux['debutant'] ?? 0),
            'intermediaire' => (int) ($niveaux['intermediaire'] ?? 0),
            'avance' => (int) ($niveaux['avance'] ?? 0)
        ],
        'prix' => [
            'gratuit' => (int) ($prix['gratuit'] ?? 0),
            'payant' => (int) ($prix['payant'] ?? 0)
        ],
        'duree' => [
            'moins_2h' => (int) ($durees['moins_2h'] ?? 0),
            'de_2h_a_10h' => (int) ($durees['de_2h_a_10h'] ?? 0),
            'de_10h_a_30h' => (int) ($durees['de_10h_a_30h'] ?? 0),
            'plus_30h' => (int) ($durees['plus_30h'] ?? 0)
        ]
    ];
}

function get_hero_stats(PDO $pdo): array
{
    $formations = (int) $pdo->query('SELECT COUNT(*) FROM t_formation')->fetchColumn();
    $apprenants = (int) $pdo->query('SELECT COUNT(DISTINCT id_user) FROM t_inscription')->fetchColumn();

    $reussite = $pdo
        ->query(
            "SELECT
            SUM(statut_resultat = 'reussi') AS reussis,
            COUNT(*) AS total
         FROM t_resultat"
        )
        ->fetch();

    $taux_reussite = 0;
    if (!empty($reussite['total'])) {
        $taux_reussite = (int) round(($reussite['reussis'] / $reussite['total']) * 100);
    }

    return [
        'formations' => $formations,
        'apprenants' => $apprenants,
        'taux_reussite' => $taux_reussite
    ];
}

function get_user_widgets(PDO $pdo, ?int $id_user): ?array
{
    if (!$id_user) {
        return null;
    }

    $progression = $pdo->prepare(
        "SELECT AVG(p.pourcent_avancement) AS moyenne
         FROM t_progression p
         JOIN t_inscription i ON i.id_inscrip = p.id_inscrip
         WHERE i.id_user = :id_user"
    );
    $progression->execute(['id_user' => $id_user]);
    $moyenne = $progression->fetchColumn();

    $certificats = $pdo->prepare(
        "SELECT COUNT(*)
         FROM t_certificat c
         JOIN t_inscription i ON i.id_inscrip = c.id_inscrip
         WHERE i.id_user = :id_user"
    );
    $certificats->execute(['id_user' => $id_user]);

    return [
        'progression_globale' => $moyenne !== null ? (int) round((float) $moyenne) : 0,
        'certificats_obtenus' => (int) $certificats->fetchColumn()
    ];
}

function build_where_clause(array $filters): array
{
    $conditions = [];
    $params = [];

    if (!empty($filters['q'])) {
        $conditions[] = '(f.titre_form LIKE :q1 OR f.descrip_form LIKE :q2)';
        $params['q1'] = '%' . $filters['q'] . '%';
        $params['q2'] = '%' . $filters['q'] . '%';
    }

    if (!empty($filters['categories'])) {
        $placeholders = [];
        foreach ($filters['categories'] as $i => $idCat) {
            $key = "cat{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = $idCat;
        }
        $conditions[] = 'f.id_cat IN (' . implode(',', $placeholders) . ')';
    }

    if (!empty($filters['niveaux'])) {
        $placeholders = [];
        foreach ($filters['niveaux'] as $i => $niv) {
            $key = "niv{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = $niv;
        }
        $conditions[] = 'f.niveau_form IN (' . implode(',', $placeholders) . ')';
    }

    if (!empty($filters['prix'])) {
        $prixConditions = [];
        if (in_array('gratuit', $filters['prix'], true)) {
            $prixConditions[] = 'f.prix_form = 0';
        }
        if (in_array('payant', $filters['prix'], true)) {
            $prixConditions[] = 'f.prix_form > 0';
        }
        if ($prixConditions) {
            $conditions[] = '(' . implode(' OR ', $prixConditions) . ')';
        }
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    return ['where' => $where, 'params' => $params];
}

const ROLES_LABELS = [
    'admin' => 'Administrateur',
    'formateur' => 'Formateur',
    'gest_entreprise' => 'Gestionnaire entreprise',
    'apprenant' => 'Apprenant'
];

const STATUTS_LABELS = [
    'actif' => 'Actif',
    'inactif' => 'Inactif',
    'suspendu' => 'Suspendu'
];

function get_entreprises_list(PDO $pdo): array
{
    return $pdo
        ->query('SELECT id_entreprise, nom_entreprise FROM t_entreprise ORDER BY nom_entreprise ASC')
        ->fetchAll();
}

function get_all_users(PDO $pdo): array
{
    return $pdo
        ->query(
            "SELECT u.id_user, u.nom_user, u.prenom_user, u.email_user, u.tel_user,
                u.role_user, u.statut_user, u.date_inscrip_user,
                e.nom_entreprise
         FROM t_utilisateur u
         LEFT JOIN t_entreprise e ON e.id_entreprise = u.id_entreprise
         ORDER BY u.date_inscrip_user DESC, u.id_user DESC"
        )
        ->fetchAll();
}

function create_user(PDO $pdo, array $data): int
{
    $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM t_utilisateur WHERE email_user = :email');
    $stmtCheck->execute(['email' => $data['email']]);
    if ((int) $stmtCheck->fetchColumn() > 0) {
        throw new RuntimeException('Un compte existe déjà avec cet email.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO t_utilisateur
            (nom_user, prenom_user, email_user, tel_user, motpass_user, role_user, statut_user, date_inscrip_user, id_entreprise)
         VALUES
            (:nom, :prenom, :email, :tel, :motpasse, :role, :statut, CURDATE(), :id_entreprise)'
    );
    $stmt->execute([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'email' => $data['email'],
        'tel' => $data['tel'] !== '' ? $data['tel'] : null,
        'motpasse' => password_hash($data['motpasse'], PASSWORD_DEFAULT),
        'role' => $data['role'],
        'statut' => $data['statut'],
        'id_entreprise' => $data['id_entreprise'] !== '' ? (int) $data['id_entreprise'] : null
    ]);

    return (int) $pdo->lastInsertId();
}

const DUREE_BORNES = [
    'moins_2h' => [0, 119],
    'de_2h_a_10h' => [120, 599],
    'de_10h_a_30h' => [600, 1799],
    'plus_30h' => [1800, PHP_INT_MAX]
];

const TRIS_AUTORISES = [
    'populaire' => 'nb_inscrits DESC',
    'recent' => 'f.date_creation_form DESC',
    'prix_asc' => 'f.prix_form ASC',
    'prix_desc' => 'f.prix_form DESC'
];

function get_formations(PDO $pdo, array $filters, string $tri, int $page, int $parPage): array
{
    [$where, $params] = array_values(build_where_clause($filters));

    $havingParts = [];
    if (!empty($filters['durees'])) {
        $orParts = [];
        foreach ($filters['durees'] as $i => $bucket) {
            if (!isset(DUREE_BORNES[$bucket])) {
                continue;
            }
            [$min, $max] = DUREE_BORNES[$bucket];
            $minKey = "dmin{$i}";
            $maxKey = "dmax{$i}";
            $orParts[] = "duree_totale BETWEEN :{$minKey} AND :{$maxKey}";
            $params[$minKey] = $min;
            $params[$maxKey] = $max;
        }
        if ($orParts) {
            $havingParts[] = '(' . implode(' OR ', $orParts) . ')';
        }
    }
    $having = $havingParts ? 'HAVING ' . implode(' AND ', $havingParts) : '';

    $orderBy = TRIS_AUTORISES[$tri] ?? TRIS_AUTORISES['populaire'];
    $offset = max(0, ($page - 1) * $parPage);

    $sql = "SELECT
                f.id_form, f.titre_form, f.descrip_form, f.img_form,
                f.niveau_form, f.prix_form, f.date_creation_form,
                c.nom_cat,
                COALESCE(SUM(m.duree_module), 0) AS duree_totale,
                (SELECT COUNT(*) FROM t_inscription i WHERE i.id_form = f.id_form) AS nb_inscrits
            FROM t_formation f
            JOIN t_categorie c ON c.id_cat = f.id_cat
            LEFT JOIN t_module m ON m.id_form = f.id_form
            {$where}
            GROUP BY f.id_form, f.titre_form, f.descrip_form, f.img_form,
                     f.niveau_form, f.prix_form, f.date_creation_form, c.nom_cat
            {$having}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $parPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $sqlCount = "SELECT COUNT(*) FROM (
                    SELECT f.id_form, COALESCE(SUM(m.duree_module), 0) AS duree_totale
                    FROM t_formation f
                    JOIN t_categorie c ON c.id_cat = f.id_cat
                    LEFT JOIN t_module m ON m.id_form = f.id_form
                    {$where}
                    GROUP BY f.id_form
                    {$having}
                 ) AS t";
    $stmtCount = $pdo->prepare($sqlCount);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $total = (int) $stmtCount->fetchColumn();

    return ['rows' => $rows, 'total' => $total];
}