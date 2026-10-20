<?php
session_start();
require_once '../config/connexion.php';

if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header('Location: ../login.php');
    exit();
}

$idUser = (int) $_SESSION['id_user'];
$prenom = trim((string) ($_SESSION['prenom_user'] ?? ''));
$nom = trim((string) ($_SESSION['nom_user'] ?? ''));
$photo = trim((string) ($_SESSION['photo_user'] ?? ''));

function tableExiste(PDO $con, string $table): bool
{
    try {
        $req = $con->prepare('SHOW TABLES LIKE ?');
        $req->execute([$table]);
        return (bool) $req->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function photoUrl(string $photo): string
{
    if ($photo === '') {
        return '';
    }

    $photo = str_replace('\\', '/', trim($photo));
    $photo = ltrim($photo, '/');

    if (preg_match('#^(https?:)?//#i', $photo)) {
        return $photo;
    }

    $nomPhoto = basename($photo);

    $candidats = [
        [__DIR__ . '/' . $photo, $photo],
        [__DIR__ . '/../uploads/profils/' . $nomPhoto, '../uploads/profils/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../uploads/' . $nomPhoto, '../uploads/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../images/profils/' . $nomPhoto, '../images/profils/' . rawurlencode($nomPhoto)],
        [__DIR__ . '/../images/' . $nomPhoto, '../images/' . rawurlencode($nomPhoto)]
    ];

    foreach ($candidats as $candidat) {
        if (is_file($candidat[0])) {
            return $candidat[1];
        }
    }

    return '';
}

function afficherDateLive(string $date): array
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return ['jour' => '--', 'mois' => 'DATE'];
    }

    $mois = [
        1 => 'JAN',
        2 => 'FÉV',
        3 => 'MAR',
        4 => 'AVR',
        5 => 'MAI',
        6 => 'JUIN',
        7 => 'JUIL',
        8 => 'AOÛ',
        9 => 'SEP',
        10 => 'OCT',
        11 => 'NOV',
        12 => 'DÉC'
    ];

    return [
        'jour' => date('d', $timestamp),
        'mois' => $mois[(int) date('n', $timestamp)]
    ];
}

function dureeLiveMinutes(string $duree): int
{
    $duree = strtolower(trim($duree));

    if (preg_match('/(\d+)\s*h\s*(\d+)?/', $duree, $m)) {
        return (int) $m[1] * 60 + (int) ($m[2] ?? 0);
    }

    if (preg_match('/(\d+)/', $duree, $m)) {
        return max(1, (int) $m[1]);
    }

    return 60;
}

function dateLongueLive(int $timestamp): string
{
    $mois = [
        1 => 'janvier',
        2 => 'février',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'août',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'décembre'
    ];

    return date('d', $timestamp) . ' ' . $mois[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

$pageActuelle = basename($_SERVER['SCRIPT_NAME']);
$nomAffiche = trim($prenom . ' ' . $nom) ?: 'Apprenant';
$initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: 'TO';
$photoUrl = photoUrl($photo);

$livesEnCours = [];

$livesProchains = [
    [
        'titre' => 'Marketing digital : stratégies efficaces',
        'formation' => 'Marketing digital',
        'formateur' => 'Session avec votre formateur',
        'date' => '30 septembre 2026',
        'heure' => '15:00',
        'duree' => '1 h 30',
        'participants' => 24
    ],
    [
        'titre' => 'Excel professionnel : niveau avancé',
        'formation' => 'Bureautique',
        'formateur' => 'Session pratique',
        'date' => '02 octobre 2026',
        'heure' => '10:00',
        'duree' => '1 h 00',
        'participants' => 18
    ],
    [
        'titre' => 'Communication professionnelle',
        'formation' => 'Communication',
        'formateur' => 'Atelier en direct',
        'date' => '05 octobre 2026',
        'heure' => '14:30',
        'duree' => '1 h 30',
        'participants' => 31
    ]
];

$livesTermines = [
    ['titre' => 'Introduction au marketing digital', 'date' => '25 septembre 2026', 'duree' => '1 h 20'],
    ['titre' => 'Les bases de la communication professionnelle', 'date' => '20 septembre 2026', 'duree' => '1 h 10'],
    ['titre' => 'Excel : tableaux et formules', 'date' => '15 septembre 2026', 'duree' => '1 h 30']
];

if (tableExiste($con, 't_lives')) {
    try {
        $colonnes = $con->query('SHOW COLUMNS FROM t_lives')->fetchAll(PDO::FETCH_COLUMN);

        if (in_array('titre', $colonnes, true) && in_array('date_live', $colonnes, true)) {
            $sql = 'SELECT * FROM t_lives';
            $parametres = [];

            if (in_array('id_user', $colonnes, true)) {
                $sql .= ' WHERE id_user = :id_user OR id_user IS NULL';
                $parametres[':id_user'] = $idUser;
            }

            $sql .= ' ORDER BY date_live ASC';

            $req = $con->prepare($sql);
            $req->execute($parametres);
            $lives = $req->fetchAll(PDO::FETCH_ASSOC);

            if ($lives) {
                $livesProchains = [];
                $livesTermines = [];
                $maintenant = time();

                foreach ($lives as $live) {
                    $heure = !empty($live['heure_live']) ? substr($live['heure_live'], 0, 5) : '00:00';
                    $debut = strtotime(($live['date_live'] ?? '') . ' ' . $heure);

                    if (!$debut) {
                        continue;
                    }

                    $fin = $debut + dureeLiveMinutes($live['duree'] ?? '') * 60;
                    $session = [
                        'titre' => $live['titre'] ?? 'Session en direct',
                        'formation' => $live['formation'] ?? ($live['nom_formation'] ?? 'Formation TOUN'),
                        'formateur' => $live['formateur'] ?? 'Formateur TOUN',
                        'date' => date('Y-m-d', $debut),
                        'heure' => $heure,
                        'duree' => $live['duree'] ?? '—',
                        'lien' => $live['lien_live'] ?? '',
                        'participants' => (int) ($live['participants'] ?? 0)
                    ];

                    if ($maintenant >= $debut && $maintenant <= $fin) {
                        $livesEnCours[] = $session;
                    } elseif ($maintenant < $debut) {
                        $livesProchains[] = $session;
                    } else {
                        $session['date'] = dateLongueLive($debut);
                        $livesTermines[] = $session;
                    }
                }

                $livesTermines = array_reverse($livesTermines);
            }
        }
    } catch (Throwable $e) {
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mes Lives | TOUN</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
        <link rel="stylesheet" href="lives.css?v=<?= filemtime(__DIR__ . '/lives.css') ?>">
    </head>
    <body>
        <div class="layout">
            <?php include __DIR__ . '/menu.php'; ?>

            <main class="page">
                <header class="topbar">
                    <button class="mobile-menu" type="button" id="mobileMenu"><i class="fa-solid fa-bars"></i></button>
                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Rechercher une formation...">
                    </div>
                    <div class="top-right">
                        <a href="rendez-vous.php" class="top-action"><i class="fa-regular fa-calendar-check"></i><span>Mes rendez-vous</span></a>
                        <a href="notifications.php" class="notification" aria-label="Notifications"><i class="fa-regular fa-bell"></i><span></span></a>
                        <a href="profil.php" class="profile">
                            <div class="avatar">
                                <?php if ($photoUrl): ?>
                                    <img src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Photo">
                                <?php else: ?>
                                    <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <strong><?= htmlspecialchars($nomAffiche, ENT_QUOTES, 'UTF-8') ?></strong>
                                <small>Apprenant</small>
                            </div>
                        </a>
                    </div>
                </header>

                <section class="content">
                    <div class="hero">
                        <div class="hero-content">
                            <div class="eyebrow"><span class="live-dot"></span> TOUN LIVE</div>
                            <h1>Apprenez en direct avec vos formateurs</h1>
                            <p>Participez à vos sessions en direct, échangez avec votre formateur et retrouvez vos prochaines classes au même endroit.</p>
                        </div>
                    </div>

                    <?php if (!empty($livesEnCours)): ?>
                        <div class="section-head">
                            <h2>En direct maintenant</h2>
                            <span>Session active</span>
                        </div>
                        <div class="live-now">
                            <article class="live-card">
                                <div class="live-preview">
                                    <div class="live-badge"><span class="live-dot"></span> EN DIRECT</div>
                                    <div class="live-preview-icon"><i class="fa-solid fa-video"></i></div>
                                </div>
                                <div class="live-info">
                                    <h3><?= htmlspecialchars($livesEnCours[0]['titre'] ?? 'Session en direct', ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="meta">
                                        <span><i class="fa-solid fa-user"></i><?= htmlspecialchars($livesEnCours[0]['formateur'] ?? 'Formateur TOUN', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><i class="fa-solid fa-users"></i><?= (int) ($livesEnCours[0]['participants'] ?? 0) ?> participants</span>
                                    </div>
                                    <a href="<?= htmlspecialchars($livesEnCours[0]['lien'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-play"></i> Rejoindre le live</a>
                                </div>
                            </article>
                            <aside class="live-card side-live">
                                <h3>Votre session</h3>
                                <p>Préparez votre connexion avant le début de la classe.</p>
                                <div class="live-feature">
                                    <div class="feature-icon"><i class="fa-solid fa-microphone"></i></div>
                                    <div><strong>Microphone</strong><span>Autorisez votre micro si nécessaire.</span></div>
                                </div>
                                <div class="live-feature">
                                    <div class="feature-icon"><i class="fa-solid fa-video"></i></div>
                                    <div><strong>Caméra</strong><span>Activez votre caméra pour participer.</span></div>
                                </div>
                                <div class="live-feature">
                                    <div class="feature-icon"><i class="fa-regular fa-comments"></i></div>
                                    <div><strong>Chat</strong><span>Posez vos questions pendant la session.</span></div>
                                </div>
                            </aside>
                        </div>
                    <?php endif; ?>

                    <div class="section-head">
                        <h2>Prochains lives</h2>
                        <span><?= count($livesProchains) ?> session<?= count($livesProchains) > 1 ? 's' : '' ?> programmée<?= count($livesProchains) > 1 ? 's' : '' ?></span>
                    </div>

                    <div class="upcoming-grid">
                        <?php foreach ($livesProchains as $live): ?>
                            <?php $dateLive = afficherDateLive($live['date']); ?>
                            <article class="upcoming-card">
                                <div class="date-row">
                                    <div class="date-box">
                                        <strong><?= htmlspecialchars($dateLive['jour']) ?></strong>
                                        <span><?= htmlspecialchars($dateLive['mois']) ?></span>
                                    </div>
                                    <span class="type"><?= htmlspecialchars($live['formation']) ?></span>
                                </div>
                                <h3><?= htmlspecialchars($live['titre'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <div class="meta">
                                    <span><i class="fa-regular fa-clock"></i><?= htmlspecialchars($live['heure']) ?> · <?= htmlspecialchars($live['duree']) ?></span>
                                    <span><i class="fa-solid fa-user"></i><?= htmlspecialchars($live['formateur']) ?></span>
                                    <span><i class="fa-solid fa-users"></i><?= (int) $live['participants'] ?> participants attendus</span>
                                </div>
                                <a href="#" class="outline-btn"><i class="fa-regular fa-calendar-plus"></i> Voir les détails</a>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($livesTermines)): ?>
                    <div class="section-head">
                        <h2>Lives terminés</h2>
                        <span>Vos dernières sessions</span>
                    </div>

                    <div class="history-card">
                        <div class="history-list">
                            <?php foreach ($livesTermines as $live): ?>
                                <div class="history-item">
                                    <div class="history-icon"><i class="fa-solid fa-video"></i></div>
                                    <div class="history-info">
                                        <strong><?= htmlspecialchars($live['titre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= htmlspecialchars($live['date']) ?> · <?= htmlspecialchars($live['duree']) ?></span>
                                    </div>
                                    <a href="#" class="replay">Revoir <i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>

        <script src="lives.js?v=<?= filemtime(__DIR__ . '/lives.js') ?>"></script>
    </body>
</html>
