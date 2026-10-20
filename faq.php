<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
        <title>FAQ - TOUN</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="pages-common.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <style>
            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                font-family:
                    Inter,
                    Segoe UI,
                    Arial,
                    sans-serif;
                color: #18213f;
                background: #f5f8fc;
            }

            a {
                text-decoration: none;
            }

            .container {
                width: min(1180px, 92%);
                margin: auto;
            }

            .page-hero {
                position: relative;
                min-height: 470px;
                margin-top: 0;
                display: flex;
                align-items: center;
                overflow: hidden;
                background: #10294d;
            }

            .page-hero-bg {
                position: absolute;
                inset: 0;
                background-size: cover;
                background-position: center;
            }

            .page-hero-bg:after {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient( 90deg, rgba(16, 41, 77, 0.97) 0%, rgba(16, 41, 77, 0.86) 43%, rgba(32, 161, 184, 0.38) 100% );
            }

            .page-hero-content {
                position: relative;
                z-index: 2;
                width: min(1180px, 92%);
                margin: auto;
                padding: 100px 0 65px;
                color: #fff;
            }

            .page-hero-content .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                padding: 8px 13px;
                border: 1px solid rgba(255, 255, 255, 0.24);
                border-radius: 30px;
                background: rgba(255, 255, 255, 0.09);
                font-size: 12.5px;
                font-weight: 800;
                letter-spacing: 1.3px;
            }

            .page-hero-content h1 {
                max-width: 720px;
                margin: 20px 0 15px;
                font-size: clamp(38px, 5vw, 62px);
                line-height: 1.03;
                letter-spacing: -1.8px;
            }

            .page-hero-content h1 span {
                display: block;
                color: #6ee0e5;
            }

            .page-hero-content p {
                max-width: 650px;
                margin: 0;
                color: rgba(255, 255, 255, 0.86);
                font-size: 18px;
                line-height: 1.7;
            }

            .page-hero-accent {
                position: absolute;
                right: -90px;
                bottom: -130px;
                width: 390px;
                height: 390px;
                border: 1px solid rgba(255, 255, 255, 0.16);
                border-radius: 50%;
                z-index: 1;
            }

            .page-hero-accent:after {
                content: "";
                position: absolute;
                inset: 42px;
                border: 1px solid rgba(110, 224, 229, 0.22);
                border-radius: 50%;
            }

            .section {
                padding: 86px 0;
            }

            .section.alt {
                background: #fff;
            }

            .section-heading {
                max-width: 760px;
                margin: 0 auto 48px;
                text-align: center;
            }

            .section-heading .tag {
                color: #20a1b8;
                font-size: 12.5px;
                font-weight: 900;
                letter-spacing: 1.5px;
            }

            .section-heading h2 {
                font-size: 38px;
                line-height: 1.15;
                margin: 12px 0;
            }

            .section-heading p {
                color: #68738a;
                line-height: 1.7;
            }

            .grid-3 {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 22px;
            }

            .card {
                background: #fff;
                border: 1px solid #e4eaf2;
                border-radius: 20px;
                padding: 28px;
                box-shadow: 0 12px 35px rgba(24, 33, 63, 0.06);
            }

            .card-icon {
                width: 50px;
                height: 50px;
                border-radius: 15px;
                display: grid;
                place-items: center;
                background: linear-gradient(135deg, #3f68ae, #20a1b8);
                color: #fff;
                font-size: 19px;
            }

            .card h3 {
                margin: 20px 0 9px;
                font-size: 19px;
            }

            .card p {
                margin: 0;
                color: #68738a;
                line-height: 1.65;
            }

            .process {
                background: linear-gradient(135deg, #10294d, #3f68ae);
                color: #fff;
            }

            .process .section-heading p {
                color: rgba(255, 255, 255, 0.72);
            }

            .process-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 18px;
            }

            .step {
                padding: 26px;
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.08);
            }

            .step-number {
                font-size: 14px;
                font-weight: 900;
                color: #6ee0e5;
            }

            .step h3 {
                font-size: 17px;
                margin: 14px 0 8px;
            }

            .step p {
                margin: 0;
                color: rgba(255, 255, 255, 0.7);
                line-height: 1.55;
                font-size: 14px;
            }

            .cta {
                padding: 70px 0;
                background: #fff;
            }

            .cta-box {
                position: relative;
                overflow: hidden;
                border-radius: 28px;
                padding: 55px;
                background: linear-gradient(110deg, #10294d, #3f68ae);
                color: #fff;
            }

            .cta-box h2 {
                max-width: 650px;
                font-size: 36px;
                margin: 8px 0 12px;
            }

            .cta-box p {
                max-width: 610px;
                color: rgba(255, 255, 255, 0.76);
                line-height: 1.6;
            }

            .cta-btn {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin-top: 16px;
                padding: 14px 20px;
                border-radius: 11px;
                background: #fff;
                color: #18213f;
                font-weight: 800;
            }

            .faq-list {
                max-width: 900px;
                margin: auto;
            }

            .faq-item {
                background: #fff;
                border: 1px solid #e4eaf2;
                border-radius: 15px;
                margin-bottom: 12px;
                overflow: hidden;
            }

            .faq-q {
                width: 100%;
                border: 0;
                background: #fff;
                padding: 20px 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: left;
                font-size: 16px;
                font-weight: 800;
                color: #18213f;
                cursor: pointer;
            }

            .faq-a {
                display: none;
                padding: 0 22px 20px;
                color: #68738a;
                line-height: 1.65;
            }

            .faq-item.open .faq-a {
                display: block;
            }

            .faq-item.open .faq-q i {
                transform: rotate(45deg);
            }

            @media (max-width: 850px) {
                .grid-3,
                .process-grid {
                    grid-template-columns: 1fr 1fr;
                }

                .page-hero {
                    min-height: 430px;
                }

                .page-hero-content h1 {
                    font-size: 42px;
                }
            }

            @media (max-width: 600px) {
                .grid-3,
                .process-grid {
                    grid-template-columns: 1fr;
                }

                .page-hero-content {
                    padding: 100px 0 55px;
                }

                .page-hero-content h1 {
                    font-size: 37px;
                }

                .section {
                    padding: 65px 0;
                }

                .cta-box {
                    padding: 34px 25px;
                }

                .cta-box h2 {
                    font-size: 29px;
                }
            }
        </style>
    </head><body>
        <header class="main-header">
            <a href="index.php" class="logo"><img src="images/logo.png" alt="Logo TOUN"></a>
            <nav class="main-nav">
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
                    <li class="nav-item"><a href="formation.php" class="nav-link">Formation</a></li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-link">Solutions <i class="fas fa-chevron-down"></i></a>
                        <div class="dropdown-menu">
                            <a href="professionnels.php" class="dropdown-item">
                                <div class="dropdown-icon"><i class="fas fa-user"></i></div>
                                <div class="dropdown-content"><strong>Professionnels</strong><span>Développez vos compétences</span></div>
                                <i class="fas fa-arrow-right"></i></a><a href="entreprises.php" class="dropdown-item">
                                <div class="dropdown-icon"><i class="fas fa-building"></i></div>
                                <div class="dropdown-content"><strong>Entreprises</strong><span>Formez vos équipes</span></div>
                                <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </li>
                    <li class="nav-item"><a href="tarif.php" class="nav-link">Tarifs</a></li>
                    <li class="nav-item"><a href="a-propos.php" class="nav-link">À propos</a></li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                    <li class="nav-item auth-item"><a href="login.php" class="login-button">Se connecter</a><a href="inscription.php" class="register-button">S'inscrire</a></li>
                </ul>
            </nav>
        </header>
        <section class="page-hero">
            <div class="page-hero-bg" style="background-image:url('images/10.jpg')"></div>
            <div class="page-hero-accent"></div>
            <div class="page-hero-content">
                <span class="eyebrow"><i class="fas fa-circle-question"></i> CENTRE D'AIDE</span>
                <h1>Les réponses aux questions<span>que vous vous posez.</span></h1>
                <p>Retrouvez les informations essentielles sur les comptes, les achats, les formations, la progression et la certification.</p>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="faq-list">
                    <div class="faq-item">
                        <button class="faq-q">Comment fonctionne TOUN ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">TOUN permet aux professionnels et aux entreprises d'accéder à des formations en ligne et de suivre les parcours depuis la plateforme.</div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-q">Faut-il prendre un abonnement ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">Non. Le principe présenté par TOUN est le paiement à la formation : vous payez les formations que vous choisissez.</div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-q">Comment un professionnel commence-t-il une formation ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">Il crée son compte, choisit une formation, effectue le paiement puis suit les modules de son parcours.</div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-q">Une entreprise peut-elle former plusieurs collaborateurs ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">Oui. L'espace entreprise est pensé pour gérer les collaborateurs, attribuer des formations et suivre leur progression.</div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-q">Comment obtenir un certificat ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">Le certificat est délivré après avoir terminé la formation et réussi l'évaluation associée.</div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-q">Puis-je suivre ma progression ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">Oui. La progression fait partie du parcours et permet de visualiser l'avancement dans les modules.</div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-q">Comment contacter TOUN ?<i class="fas fa-plus"></i></button>
                        <div class="faq-a">Vous pouvez utiliser la page Contact pour transmettre votre demande à l'équipe TOUN.</div>
                    </div>
                </div>
            </div>
        </section>
        <section class="cta">
            <div class="container">
                <div class="cta-box">
                    <span class="eyebrow">BESOIN D'UN ACCOMPAGNEMENT ?</span>
                    <h2>Vous ne trouvez pas la réponse ?</h2>
                    <p>Notre équipe peut vous orienter selon votre profil et votre besoin.</p>
                    <a class="cta-btn" href="contact.php">Nous contacter <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </section>
        <footer class="toun-footer">
            <div class="footer-container">
                <div class="footer-content">
                    <div class="footer-brand">
                        <a href="index.php" class="footer-logo"><img src="images/logo1.png" alt="Toun"></a>
                        <p class="footer-slogan">Le savoir qui transforme les compétences.</p>
                    </div>
                    <div class="footer-block">
                        <h3>Navigation</h3>
                        <a href="index.php">Accueil</a><a href="formation.php">Formation</a><a href="professionnels.php">Solutions</a><a href="tarif.php">Tarifs</a><a href="a-propos.php">À propos</a><a href="contact.php">Contact</a>
                    </div>
                    <div class="footer-block">
                        <h3>Solutions</h3>
                        <a href="professionnels.php">Professionnels</a><a href="entreprises.php">Entreprises</a><a href="formation.php">Nos formations</a><a href="faq.php">Questions fréquentes</a>
                    </div>
                    <div class="footer-block">
                        <h3>Contact</h3>
                        <a href="contact.php">Nous contacter</a><a href="faq.php">Centre d'aide</a>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>© 2026 Toun. Tous droits réservés.</p>
                    <div class="footer-links"><a href="confidentialite.php">Confidentialité</a><a href="conditions.php">Conditions d'utilisation</a><a href="mentions-legales.php">Mentions légales</a></div>
                </div>
            </div>
        </footer>
        <script>
            document.querySelectorAll('.faq-q').forEach(function (b) {
                b.addEventListener('click', function () {
                    b.parentElement.classList.toggle('open');
                });
            });
        </script>
    </body>
</html>