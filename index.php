<?php
session_start();
require_once __DIR__ . '/config/connexion.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function resolveImage($value, string $folders = 'formations'): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    $name = basename(str_replace('\\', '/', $value));
    $paths =
        $folders === 'profils'
            ? [
                "uploads/photos/$name",
                "uploads/profils/$name",
                "uploads/$name",
                "images/profils/$name",
                "images/$name",
                "assets/uploads/$name"
            ]
            : ["uploads/formations/$name", "uploads/$name", "images/$name"];

    foreach ($paths as $path) {
        if (is_file(__DIR__ . '/' . $path)) {
            return $path;
        }
    }

    return '';
}

function formatDuration($minutes): string
{
    $minutes = (int) $minutes;

    if ($minutes <= 0) {
        return 'À définir';
    }

    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $rest = $minutes % 60;

    return floor($minutes / 60) . ' h' . ($rest ? ' ' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT) : '');
}

function initials(string $name, string $fallback = 'T'): string
{
    $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

    if (!$parts) {
        return $fallback;
    }

    $first = mb_substr($parts[0], 0, 1);
    $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

    return mb_strtoupper($first . $last);
}

$categories = [];
$formations = [];

try {
    $categories = $con
        ->query('SELECT id_cat, nom_cat FROM t_categories ORDER BY nom_cat ASC')
        ->fetchAll(PDO::FETCH_ASSOC);

    $formations = $con
        ->query(
            "
        SELECT f.id_form, f.titre_form, f.img_form, f.niveau_form, f.prix_form, f.duree_form,
               f.date_creation_form, c.nom_cat, u.nom_user, u.prenom_user, u.photo_user
        FROM t_formations f
        LEFT JOIN t_categories c ON c.id_cat = f.id_cat
        LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur
        LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user
        WHERE LOWER(f.statut_form) IN ('publie', 'publiee', 'publiée', 'published')
        ORDER BY f.date_creation_form DESC, f.id_form DESC
        LIMIT 4
    "
        )
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
}

$slides = [
    [
        'image' => 'images/jl.png',
        'alt' => 'Professionnelle en formation',
        'eyebrow' => 'Formation en ligne pour les professionnels',
        'title' => 'Développez<br>vos <strong>compétences</strong><br>aujourd’hui',
        'text' => 'Des formations de qualité pour révéler votre potentiel et construire votre avenir.',
        'label' => 'Découvrir les formations',
        'link' => 'formation.php'
    ],
    [
        'image' => 'images/imgj.png',
        'alt' => 'Professionnelles en formation',
        'eyebrow' => 'Un parcours complet, à votre rythme',
        'title' => 'Apprenez.<br>Progressez.<br><strong>Certifiez-vous.</strong>',
        'text' => 'Des parcours structurés, des évaluations et un certificat pour valoriser vos acquis.',
        'label' => 'Explorer le catalogue',
        'link' => 'formation.php'
    ],
    [
        'image' => 'images/se.png',
        'alt' => 'Professionnelle en entreprise',
        'eyebrow' => 'Des solutions adaptées aux entreprises',
        'title' => 'Faites grandir<br><strong>vos équipes</strong>',
        'text' =>
            'Formez vos collaborateurs, suivez leur progression et développez la performance de votre entreprise.',
        'label' => 'Découvrir nos solutions',
        'link' => 'entreprises.php'
    ]
];

$benefits = [
    ['fa-graduation-cap', 'Des formations de qualité', 'Des contenus adaptés aux professionnels.'],
    ['fa-laptop', 'Accessible à votre rythme', 'Apprenez où et quand vous voulez.'],
    ['fa-users', 'Des formateurs experts', 'Un accompagnement orienté pratique.'],
    ['fa-certificate', 'Un certificat reconnu', 'Valorisez les compétences acquises.']
];

$aboutCards = [
    ['fa-solid fa-bullseye', 'Notre mission', 'Rendre la formation professionnelle accessible et utile à tous.'],
    ['fa-solid fa-eye', 'Notre vision', 'Des compétences pour un monde meilleur.'],
    ['fa-regular fa-heart', 'Nos valeurs', 'Qualité, accessibilité, impact et innovation.'],
    ['fa-solid fa-chart-column', 'Notre impact', 'Des talents plus performants dans les entreprises.']
];

$solutions = [
    [
        'link' => 'professionnels.php',
        'image' => 'images/imas.png',
        'alt' => 'Professionnels',
        'icon' => 'fa-user',
        'title' => 'Pour les professionnels',
        'points' => ['Développez vos compétences', 'Apprenez à votre rythme', 'Obtenez des certificats reconnus']
    ],
    [
        'link' => 'entreprises.php',
        'image' => 'images/IMG_0746.JPG.jpeg',
        'alt' => 'Entreprises',
        'icon' => 'fa-users',
        'title' => 'Pour les entreprises',
        'points' => ['Formez vos collaborateurs', 'Suivez leur progression', 'Boostez la performance de votre équipe']
    ]
];

$steps = [
    ['01', 'Créez votre compte', 'Inscrivez-vous simplement.', false],
    ['02', 'Choisissez votre formation', 'Trouvez celle qui vous correspond.', false],
    ['03', 'Payez votre formation', 'Validez votre inscription.', true],
    ['04', 'Suivez votre formation', 'Accédez à vos cours.', false],
    ['05', 'Obtenez votre certificat', 'Validez votre parcours.', false]
];

$testimonials = [
    [
        'Aïcha Koné',
        'Professionnelle du digital',
        'TOUN m’a permis de développer mes compétences tout en gardant une grande flexibilité dans mon parcours.'
    ],
    [
        'M. Kouassi',
        'Responsable RH, Entreprise',
        'Une solution efficace pour former nos équipes. Le suivi et les statistiques sont vraiment pratiques.'
    ],
    ['Grâce N’Guessan', 'Consultante', 'Une expérience simple et adaptée aux besoins des professionnels.'],
    ['Mariam Traoré', 'Entrepreneure', 'J’apprécie la simplicité d’utilisation et la qualité des contenus proposés.']
];

$faq = [
    [
        'Comment fonctionne TOUN ?',
        'TOUN permet aux professionnels et aux entreprises d’accéder à des formations adaptées à leurs besoins. Créez votre compte, choisissez une formation, effectuez le paiement puis commencez votre parcours.'
    ],
    [
        'Comment choisir une formation ?',
        'Parcourez le catalogue et consultez les objectifs, le contenu, la durée, le niveau et le public concerné avant de choisir votre formation.'
    ],
    [
        'Comment effectuer le paiement ?',
        'Après avoir choisi votre formation, vous passez à l’étape de paiement. Une fois le paiement confirmé, la formation devient accessible dans votre espace.'
    ],
    [
        'Quand puis-je commencer ma formation ?',
        'L’accès est disponible après confirmation du paiement. Vous pouvez alors commencer votre parcours depuis votre espace personnel.'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="TOUN, la plateforme de formation en ligne pour les professionnels et les entreprises.">
        <title>TOUN | Formation professionnelle en ligne</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="stylesheet" href="style.css?v=9">
        <script>
            document.documentElement.classList.add('js');
        </script>
    </head>
    <body>

        <header class="site-header">
            <div class="container nav-wrap">
                <a href="index.php" class="brand"><img src="images/logo.png" alt="TOUN"></a>

                <nav class="main-nav" id="main-nav">
                    <div class="nav-links">
                        <a href="index.php" class="nav-link active">Accueil</a>
                        <a href="formation.php" class="nav-link">Formation</a>

                        <div class="nav-dropdown">
                            <button class="nav-link nav-dropdown-btn" type="button" aria-expanded="false">Solutions <i class="fa-solid fa-chevron-down"></i></button>
                            <div class="dropdown-panel">
                                <a href="professionnels.php">
                                    <span class="drop-icon"><i class="fa-solid fa-user"></i></span>
                                    <span><b>Professionnels</b><small>Développez vos compétences</small></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="entreprises.php">
                                    <span class="drop-icon"><i class="fa-solid fa-building"></i></span>
                                    <span><b>Entreprises</b><small>Formez vos équipes</small></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>

                        <a href="tarif.php" class="nav-link">Tarifs</a>
                        <a href="a-propos.php" class="nav-link">À propos</a>
                        <a href="contact.php" class="nav-link">Contact</a>
                    </div>

                    <div class="nav-actions">
                        <a href="login.php" class="btn-login">Se connecter</a>
                        <a href="inscription.php" class="btn-register">S'inscrire</a>
                    </div>
                </nav>

                <button class="menu-toggle" type="button" aria-label="Menu" aria-controls="main-nav" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="hero-slides">
                    <?php foreach ($slides as $index => $slide): ?>
                        <article class="hero-slide<?= $index === 0 ? ' active' : '' ?>">
                            <img src="<?= e($slide['image']) ?>" alt="<?= e($slide['alt']) ?>">
                            <div class="hero-overlay"></div>
                            <span class="hero-shape shape-one"></span>
                            <span class="hero-shape shape-two"></span>
                            <div class="container hero-inner">
                                <div class="hero-copy">
                                    <span class="eyebrow"><?= e($slide['eyebrow']) ?></span>
                                    <h1><?= $slide['title'] ?></h1>
                                    <p><?= e($slide['text']) ?></p>
                                    <a href="<?= e($slide['link']) ?>" class="hero-btn"><?= e($slide['label']) ?> <i class="fa-solid fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <button class="hero-arrow hero-prev" type="button" aria-label="Précédent"><i class="fa-solid fa-chevron-left"></i></button>
                <button class="hero-arrow hero-next" type="button" aria-label="Suivant"><i class="fa-solid fa-chevron-right"></i></button>

                <div class="hero-dots">
                    <?php foreach ($slides as $index => $slide): ?>
                        <button class="hero-dot<?= $index === 0 ? ' active' : '' ?>" type="button" aria-label="Diapositive <?= $index + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="formations-section">
                <div class="container">
                    <div class="section-head reveal">
                        <span>Nos formations</span>
                        <h2>Des formations pour tous les <strong>domaines</strong></h2>
                        <p>Choisissez un domaine et découvrez les formations adaptées à vos objectifs professionnels.</p>
                    </div>

                    <div class="category-row stagger">
                        <a href="formation.php" class="category active">Tous</a>
                        <?php foreach ($categories as $category): ?>
                            <a href="formation.php?cat=<?= (int) $category['id_cat'] ?>" class="category"><?= e($category['nom_cat']) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <div class="formation-head reveal">
                        <h3>Formations populaires</h3>
                        <a href="formation.php">Voir toutes les formations <i class="fa-solid fa-arrow-right"></i></a>
                    </div>

                    <div class="courses-grid stagger">
                        <?php if ($formations): ?>
                            <?php foreach ($formations as $position => $formation): ?>
                                <?php
$image = resolveImage($formation['img_form']);
$trainerName = trim(($formation['prenom_user'] ?? '') . ' ' . ($formation['nom_user'] ?? ''));
$trainerPhoto = resolveImage($formation['photo_user'] ?? '', 'profils');
$isNew =
    $position === 0 ||
    (!empty($formation['date_creation_form']) && strtotime($formation['date_creation_form']) >= strtotime('-30 days'));
?>
                                <article class="course-card">
                                    <div class="course-image">
                                        <?php if ($image !== ''): ?>
                                            <img src="<?= e($image) ?>" alt="<?= e($formation['titre_form']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <span class="course-placeholder"><i class="fa-solid fa-book-open"></i></span>
                                        <?php endif; ?>

                                        <?php if ($isNew): ?>
                                            <span class="course-badge">Nouveau</span>
                                        <?php endif; ?>

                                        <button class="favorite" type="button" data-id="<?= (int) $formation['id_form'] ?>" aria-label="Ajouter aux favoris" aria-pressed="false">
                                            <i class="fa-regular fa-heart"></i>
                                        </button>
                                    </div>

                                    <div class="course-content">
                                        <span class="course-category"><?= e($formation['nom_cat'] ?: 'Formation') ?></span>
                                        <h3><?= e($formation['titre_form']) ?></h3>

                                        <div class="course-instructor">
                                            <span class="trainer-photo">
                                                <?php if ($trainerPhoto !== ''): ?>
                                                    <img src="<?= e($trainerPhoto) ?>" alt="<?= e($trainerName) ?>">
                                                <?php else: ?>
                                                    <?= e(initials($trainerName, 'F')) ?>
                                                <?php endif; ?>
                                            </span>
                                            <span>
                                                <small>Formateur</small>
                                                <b><?= e($trainerName ?: 'Formateur TOUN') ?></b>
                                            </span>
                                        </div>

                                        <div class="course-rating">★★★★★ <span>Formation professionnelle</span></div>

                                        <div class="course-meta">
                                            <span><i class="fa-regular fa-clock"></i> <?= e(formatDuration($formation['duree_form'])) ?></span>
                                            <span><?= e($formation['niveau_form'] ?: 'Tous niveaux') ?></span>
                                        </div>

                                        <div class="course-footer">
                                            <strong><?= number_format((float) $formation['prix_form'], 0, ',', ' ') ?> FCFA</strong>
                                            <a href="formation-details.php?id=<?= (int) $formation['id_form'] ?>" aria-label="Voir la formation"><i class="fa-solid fa-arrow-right"></i></a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-courses">
                                <i class="fa-solid fa-book-open"></i>
                                <p>Aucune formation publiée pour le moment.</p>
                                <a href="formation.php">Voir le catalogue</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="benefits-strip">
                <div class="container">
                    <div class="benefits-wrap stagger">
                        <?php foreach ($benefits as [$icon, $title, $text]): ?>
                            <article>
                                <span class="benefit-icon"><i class="fa-solid <?= $icon ?>"></i></span>
                                <div>
                                    <b><?= e($title) ?></b>
                                    <small><?= e($text) ?></small>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="about-section">
                <div class="container about-grid">
                    <div class="about-image reveal-left">
                        <img src="images/4.jpg" alt="Équipe TOUN">
                    </div>

                    <div class="about-copy reveal-right">
                        <span class="section-kicker">À propos de TOUN</span>
                        <h2>Une nouvelle façon d’<strong>apprendre et de progresser.</strong></h2>
                        <p>TOUN est une plateforme de formation en ligne dédiée aux professionnels et aux entreprises. Nous croyons en un avenir où chaque talent peut évoluer, se former et contribuer à un monde plus compétitif et innovant.</p>

                        <div class="about-cards stagger">
                            <?php foreach ($aboutCards as [$icon, $title, $text]): ?>
                                <article>
                                    <span class="about-icon"><i class="<?= $icon ?>"></i></span>
                                    <b><?= e($title) ?></b>
                                    <small><?= e($text) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <a href="a-propos.php" class="outline-btn">Découvrir notre histoire <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </section>

            <section class="solutions-section">
                <div class="container solutions-layout">
                    <div class="solutions-intro reveal-left">
                        <span class="section-kicker">Nos solutions</span>
                        <h2>Deux solutions,<br><strong>une même ambition</strong></h2>
                        <p>Que vous soyez un professionnel ou une entreprise, TOUN vous accompagne avec des solutions adaptées à vos objectifs.</p>
                        <div class="solution-buttons">
                            <a href="professionnels.php" class="solution-btn primary">Pour les professionnels <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="entreprises.php" class="solution-btn">Pour les entreprises <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>

                    <div class="solution-cards stagger">
                        <?php foreach ($solutions as $solution): ?>
                            <a href="<?= e($solution['link']) ?>" class="solution-card">
                                <div class="solution-photo">
                                    <img src="<?= e($solution['image']) ?>" alt="<?= e($solution['alt']) ?>" loading="lazy">
                                </div>
                                <div class="solution-body">
                                    <span class="solution-icon"><i class="fa-solid <?= $solution['icon'] ?>"></i></span>
                                    <h3><?= e($solution['title']) ?></h3>
                                    <ul>
                                        <?php foreach ($solution['points'] as $point): ?>
                                            <li><i class="fa-solid fa-check"></i><?= e($point) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="process-section">
                <div class="container">
                    <div class="section-head reveal">
                        <span>Votre parcours</span>
                        <h2>Comment ça <strong>fonctionne ?</strong></h2>
                        <p>Un parcours simple pour accéder à votre formation.</p>
                    </div>

                    <div class="process stagger">
                        <?php foreach ($steps as [$number, $title, $text, $featured]): ?>
                            <article class="process-step<?= $featured ? ' featured' : '' ?>">
                                <span class="step-number"><?= $number ?></span>
                                <?php if ($featured): ?>
                                    <em class="step-tag">Paiement requis</em>
                                <?php endif; ?>
                                <b><?= e($title) ?></b>
                                <small><?= e($text) ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="testimonials-section">
                <div class="container">
                    <div class="section-head reveal">
                        <span>Ils parlent de nous</span>
                        <h2>Des expériences qui font la <strong>différence.</strong></h2>
                        <p>Professionnels, apprenants et entreprises partagent leur expérience avec TOUN.</p>
                    </div>
                </div>

                <div class="marquee reveal" data-marquee>
                    <div class="marquee-track">
                        <?php foreach ($testimonials as [$name, $role, $text]): ?>
                            <article class="testimonial">
                                <div class="person">
                                    <span class="avatar"><?= e(initials($name)) ?></span>
                                    <div>
                                        <b><?= e($name) ?></b>
                                        <small><?= e($role) ?></small>
                                    </div>
                                    <i class="fa-solid fa-quote-right"></i>
                                </div>
                                <div class="stars">★★★★★</div>
                                <p><?= e($text) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="faq-section">
                <div class="container faq-layout">
                    <div class="section-head light faq-head reveal">
                        <span>Questions fréquentes</span>
                        <h2>Vous avez une question ? <strong>Nous avons la réponse.</strong></h2>
                        <p>Retrouvez les réponses aux questions les plus fréquentes concernant TOUN et nos formations.</p>
                    </div>

                    <div class="faq-list stagger">
                        <?php foreach ($faq as [$question, $answer]): ?>
                            <article class="faq-item">
                                <button class="faq-question" type="button" aria-expanded="false"><?= e($question) ?><i class="fa-solid fa-plus"></i></button>
                                <div class="faq-answer">
                                    <p><?= e($answer) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <aside class="faq-contact reveal-right">
                        <span class="faq-icon"><i class="fa-regular fa-circle-question"></i></span>
                        <small>Besoin d’aide ?</small>
                        <h3>Une question<br><strong>plus précise ?</strong></h3>
                        <p>Notre équipe est disponible pour vous accompagner et répondre à vos questions.</p>
                        <a href="contact.php">Nous contacter <i class="fa-solid fa-arrow-right"></i></a>
                    </aside>
                </div>
            </section>

            <section class="cta-section">
                <div class="container">
                    <div class="cta reveal-zoom">
                        <div class="cta-content">
                            <span class="section-kicker">Prêt à développer vos compétences ?</span>
                            <h2>Rejoignez TOUN et formez-vous auprès des meilleurs experts.</h2>
                            <a href="inscription.php" class="hero-btn">Commencer dès maintenant <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                        <div class="cta-image">
                            <img src="images/hero-entreprise.jpg" alt="Formation TOUN" loading="lazy">
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="container footer-grid stagger">
                <div class="footer-brand">
                    <img src="images/logo1.png" class="footer-logo" alt="TOUN">
                    <p>Une plateforme dédiée à la formation professionnelle en ligne pour les professionnels et les entreprises.</p>
                </div>
                <div>
                    <h4>Navigation</h4>
                    <a href="index.php">Accueil</a>
                    <a href="formation.php">Formation</a>
                    <a href="tarif.php">Tarifs</a>
                    <a href="a-propos.php">À propos</a>
                    <a href="contact.php">Contact</a>
                </div>
                <div>
                    <h4>Solutions</h4>
                    <a href="professionnels.php">Professionnels</a>
                    <a href="entreprises.php">Entreprises</a>
                    <a href="formation.php">Nos formations</a>
                </div>
                <div>
                    <h4>Contact</h4>
                    <span><i class="fa-solid fa-location-dot"></i>Abidjan, Côte d'Ivoire</span>
                    <a href="mailto:contact@toun.com"><i class="fa-solid fa-envelope"></i>contact@toun.com</a>
                    <a href="tel:+2250102305952"><i class="fa-solid fa-phone"></i>+225 01 02 30 59 52</a>
                </div>
            </div>

            <div class="container footer-bottom">
                <span>© <?= date('Y') ?> TOUN. Tous droits réservés.</span>
                <div>
                    <a href="confidentialite.php">Confidentialité</a>
                    <span>·</span>
                    <a href="conditions.php">Conditions</a>
                    <span>·</span>
                    <a href="mentions-legales.php">Mentions légales</a>
                </div>
            </div>
        </footer>

        <script src="script.js?v=7" defer></script>
    </body>
</html>
