<?php session_start(); ?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Entreprises | TOUN</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                font-family: "Poppins", sans-serif;
                background: #f2f8fb;
                color: #123b6d;
                overflow-x: hidden;
            }

            a {
                text-decoration: none;
            }

            img {
                display: block;
                max-width: 100%;
            }

            .section-tag {
                color: #39d5ec;
                font-size: 11.5px;
                font-weight: 800;
                letter-spacing: 2px;
                text-transform: uppercase;
            }

            .about {
                padding: 58px 5%;
                background: #f6fafc;
            }

            .about-grid {
                max-width: 1320px;
                margin: auto;
                display: grid;
                grid-template-columns: 0.9fr 1.1fr;
                gap: 50px;
                align-items: center;
            }

            .about-image {
                position: relative;
            }

            .about-image img {
                width: 100%;
                height: 325px;
                object-fit: cover;
                border-radius: 22px;
                box-shadow: 0 18px 40px rgba(14, 62, 104, 0.12);
            }

            .image-label {
                position: absolute;
                right: -10px;
                bottom: 15px;
                width: 180px;
                padding: 14px;
                border-radius: 14px;
                color: white;
                background: linear-gradient(135deg, #123d78, #079dd3);
                box-shadow: 0 12px 30px rgba(11, 76, 130, 0.22);
            }

            .image-label strong {
                display: block;
                margin-bottom: 4px;
                font-size: 14px;
            }

            .image-label span {
                font-size: 11px;
                line-height: 1.5;
            }

            .about-content .section-tag {
                margin-bottom: 10px;
            }

            .about-content h2 {
                max-width: 650px;
                margin-bottom: 15px;
                color: #123b6d;
                font-size: 32px;
                line-height: 1.18;
            }

            .about-content h2 span {
                color: #078fd2;
            }

            .about-content p {
                max-width: 650px;
                margin-bottom: 10px;
                color: #647f9e;
                font-size: 12.5px;
                line-height: 1.7;
            }

            .check-list {
                margin-top: 17px;
                display: grid;
                gap: 8px;
            }

            .check-item {
                display: flex;
                align-items: center;
                gap: 9px;
                color: #31577f;
                font-size: 12px;
            }

            .check-item i {
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                border-radius: 50%;
                color: #078fd2;
                background: #d9f4fa;
                font-size: 10.5px;
            }

            .benefits {
                padding: 42px 5%;
                color: white;
                background: linear-gradient(120deg, #062a4b, #0a5a8e);
            }

            .benefits-container {
                max-width: 1250px;
                margin: auto;
            }

            .benefits-header {
                max-width: 680px;
                margin: auto;
                text-align: center;
            }

            .benefits-header h2 {
                margin: 7px 0;
                font-size: 28px;
                line-height: 1.2;
            }

            .benefits-header h2 span {
                color: #fff;
            }

            .benefits-header p {
                color: rgba(255, 255, 255, 0.72);
                font-size: 12px;
            }

            .benefit-list {
                margin-top: 22px;
                border-top: 1px solid rgba(255, 255, 255, 0.14);
            }

            .benefit-row {
                min-height: 66px;
                display: grid;
                grid-template-columns: 44px 230px 1fr;
                align-items: center;
                gap: 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.14);
            }

            .benefit-number {
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                color: #43d8ef;
                background: rgba(61, 216, 239, 0.12);
                border: 1px solid rgba(61, 216, 239, 0.3);
                font-size: 11.5px;
                font-weight: 700;
            }

            .benefit-title {
                font-size: 13px;
                font-weight: 700;
            }

            .benefit-description {
                color: rgba(255, 255, 255, 0.68);
                font-size: 11.5px;
                line-height: 1.5;
            }

            .enterprise-cta {
                padding: 38px 5%;
                background: #eaf7fb;
            }

            .enterprise-cta-box {
                max-width: 1250px;
                min-height: 205px;
                margin: auto;
                padding: 25px 38px;
                display: grid;
                grid-template-columns: 1fr 0.85fr;
                align-items: center;
                gap: 40px;
                overflow: hidden;
                border-radius: 22px;
                color: #054273;
            }

            .enterprise-cta-content {
                max-width: 620px;
            }

            .enterprise-cta-content small {
                color: #00817f;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 2px;
            }

            .enterprise-cta-content h2 {
                margin: 7px 0 8px;
                font-size: 27px;
                line-height: 1.2;
            }

            .enterprise-cta-content h2 span {
                color: #00817f;
            }

            .enterprise-cta-content p {
                max-width: 570px;
                color: #054273;
                font-size: 12px;
                line-height: 1.6;
            }

            .enterprise-cta-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 16px;
                padding: 11px 18px;
                border-radius: 22px;
                color: #087eb8;
                background: white;
                font-size: 12px;
                font-weight: 700;
                transition: 0.3s;
            }

            .enterprise-cta-btn:hover {
                transform: translateY(-2px);
            }

            .enterprise-cta-image {
                height: 155px;
                overflow: hidden;
                border-radius: 16px;
            }

            .enterprise-cta-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            @media (max-width: 1050px) {
                .nav-menu {
                    gap: 20px;
                }

                .about-grid {
                    grid-template-columns: 1fr;
                }

                .about-image {
                    max-width: 650px;
                }

                .enterprise-cta-box {
                    grid-template-columns: 1fr;
                }

                .enterprise-cta-image {
                    max-width: 650px;
                }
            }

            @media (max-width: 760px) {
                .logo img {
                    width: 120px;
                }

                .about,
                .benefits {
                    padding: 42px 6%;
                }

                .about-content h2 {
                    font-size: 28px;
                }

                .about-image img {
                    height: 280px;
                }

                .image-label {
                    right: 8px;
                }

                .benefits-header h2 {
                    font-size: 25px;
                }

                .benefit-row {
                    grid-template-columns: 40px 1fr;
                    gap: 12px;
                    padding: 14px 0;
                }

                .benefit-description {
                    grid-column: 2;
                }

                .enterprise-cta {
                    padding: 30px 6%;
                }

                .enterprise-cta-box {
                    padding: 25px;
                    gap: 22px;
                }

                .enterprise-cta-content h2 {
                    font-size: 23px;
                }

                .enterprise-cta-image {
                    height: 170px;
                }
            }

            .site-header a,
            .footer a {
                text-decoration: none;
            }

            .js .reveal,
            .js .reveal-left,
            .js .reveal-right,
            .js .reveal-zoom {
                opacity: 0;
                transition:
                    opacity 0.8s cubic-bezier(0.22, 0.8, 0.3, 1),
                    transform 0.8s cubic-bezier(0.22, 0.8, 0.3, 1);
            }

            .js .reveal {
                transform: translateY(30px);
            }

            .js .reveal-left {
                transform: translateX(-50px);
            }

            .js .reveal-right {
                transform: translateX(50px);
            }

            .js .reveal-zoom {
                transform: scale(0.94);
            }

            .js .reveal.visible,
            .js .reveal-left.visible,
            .js .reveal-right.visible,
            .js .reveal-zoom.visible {
                opacity: 1;
                transform: none;
            }

            .js .stagger > * {
                opacity: 0;
                transform: translateY(26px) scale(0.97);
                transition:
                    opacity 0.6s cubic-bezier(0.22, 0.8, 0.3, 1),
                    transform 0.6s cubic-bezier(0.22, 0.8, 0.3, 1);
            }

            .js .stagger.visible > * {
                opacity: 1;
                transform: none;
            }

            .js .stagger.done > * {
                transition:
                    transform 0.3s ease,
                    box-shadow 0.3s ease,
                    border-color 0.3s ease,
                    background-color 0.3s ease;
                transition-delay: 0s;
            }

            .site-header {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 80px;
                display: flex;
                align-items: center;
                background: rgba(255, 255, 255, 0.97);
                backdrop-filter: blur(14px);
                z-index: 1000;
                transition: box-shadow 0.3s ease;
                animation: tounSlideDown 0.7s cubic-bezier(0.22, 0.8, 0.3, 1) both;
            }

            .site-header.scrolled {
                box-shadow: 0 8px 26px rgba(16, 55, 85, 0.1);
            }

            .site-header .nav-wrap {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                gap: 24px;
                padding: 0 3.6%;
            }

            .site-header .brand img {
                width: 150px;
                height: auto;
            }

            .site-header .main-nav {
                flex: 1;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .site-header .nav-links {
                height: 100%;
                display: flex;
                align-items: center;
                margin: 0 auto;
            }

            .site-header .nav-link {
                position: relative;
                height: 100%;
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 0 17px;
                color: #173f70;
                background: transparent;
                border: 0;
                font-family: "Poppins", sans-serif;
                font-size: 15px;
                font-weight: 500;
                white-space: nowrap;
                cursor: pointer;
                transition: color 0.25s ease;
            }

            .site-header .nav-link::after {
                content: "";
                position: absolute;
                left: 17px;
                right: 17px;
                bottom: 0;
                height: 3px;
                border-radius: 3px 3px 0 0;
                background: linear-gradient(90deg, #0c84b8, #20a1b8);
                transform: scaleX(0);
                transition: transform 0.3s cubic-bezier(0.22, 0.8, 0.3, 1);
            }

            .site-header .nav-link:hover,
            .site-header .nav-link.active {
                color: #078fd2;
            }

            .site-header .nav-link:hover::after,
            .site-header .nav-link.active::after {
                transform: scaleX(1);
            }

            .site-header .nav-dropdown {
                position: relative;
                height: 100%;
            }

            .site-header .nav-dropdown-btn i {
                font-size: 11.5px;
                transition: transform 0.3s ease;
            }

            .site-header .nav-dropdown:hover .nav-dropdown-btn i,
            .site-header .nav-dropdown.open .nav-dropdown-btn i {
                transform: rotate(180deg);
            }

            .site-header .dropdown-panel {
                position: absolute;
                top: calc(100% - 8px);
                left: 50%;
                width: 270px;
                padding: 8px;
                background: #fff;
                border: 1px solid #e5edf6;
                border-radius: 16px;
                box-shadow: 0 18px 45px rgba(21, 56, 91, 0.14);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transform: translate(-50%, 10px);
                transition:
                    opacity 0.25s ease,
                    transform 0.25s ease,
                    visibility 0.25s;
            }

            .site-header .nav-dropdown:hover .dropdown-panel,
            .site-header .nav-dropdown:focus-within .dropdown-panel,
            .site-header .nav-dropdown.open .dropdown-panel {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
                transform: translate(-50%, 0);
            }

            .site-header .dropdown-panel > a {
                display: grid;
                grid-template-columns: 40px 1fr 16px;
                align-items: center;
                gap: 12px;
                padding: 12px;
                border-radius: 12px;
                color: #173f70;
                transition: background 0.25s ease;
            }

            .site-header .dropdown-panel > a:hover {
                background: #f1fbfe;
                color: #078fd2;
            }

            .site-header .dropdown-panel b {
                display: block;
                font-size: 13.5px;
            }

            .site-header .dropdown-panel small {
                display: block;
                margin-top: 2px;
                color: #8190a5;
                font-size: 12px;
                font-weight: 400;
            }

            .site-header .dropdown-panel > a > i {
                font-size: 12px;
            }

            .site-header .drop-icon {
                width: 40px;
                height: 40px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                color: #20a1b8;
                background: #e7f7fa;
            }

            .site-header .nav-actions {
                display: flex;
                gap: 12px;
            }

            .site-header .btn-login,
            .site-header .btn-register {
                height: 46px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 24px;
                border-radius: 99px;
                font-family: "Poppins", sans-serif;
                font-size: 14px;
                font-weight: 600;
                transition:
                    transform 0.25s ease,
                    box-shadow 0.25s ease,
                    background 0.25s ease;
            }

            .site-header .btn-login {
                color: #173f70;
                background: #fff;
                border: 1px solid #dfe8f0;
            }

            .site-header .btn-login:hover {
                background: #f2f8fc;
                transform: translateY(-2px);
            }

            .site-header .btn-register {
                color: #fff;
                background: linear-gradient(100deg, #0f3f78, #0980c2);
                box-shadow: 0 8px 20px rgba(9, 128, 194, 0.24);
                border: 0;
            }

            .site-header .btn-register:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 26px rgba(9, 128, 194, 0.34);
            }

            .site-header .menu-toggle {
                display: none;
                width: 42px;
                height: 42px;
                margin-left: auto;
                place-items: center;
                border-radius: 12px;
                color: #078fd2;
                background: #e7f7fa;
                border: 0;
                font-size: 18px;
                cursor: pointer;
            }

            body {
                padding-top: 80px;
            }

            @keyframes tounSlideDown {
                from {
                    transform: translateY(-100%);
                }
            }

            @media (max-width: 960px) {
                .site-header {
                    height: 70px;
                }

                body {
                    padding-top: 70px;
                }

                .site-header .menu-toggle {
                    display: grid;
                }

                .site-header .brand img {
                    width: 115px;
                }

                .site-header .main-nav {
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    height: auto;
                    flex-direction: column;
                    align-items: stretch;
                    gap: 0;
                    padding: 8px 4% 18px;
                    background: #fff;
                    border-bottom: 1px solid #e6eef5;
                    box-shadow: 0 18px 30px rgba(16, 55, 85, 0.1);
                    opacity: 0;
                    visibility: hidden;
                    transform: translateY(-10px);
                    transition:
                        opacity 0.3s ease,
                        transform 0.3s ease,
                        visibility 0.3s;
                }

                .site-header.mobile-open .main-nav {
                    opacity: 1;
                    visibility: visible;
                    transform: none;
                }

                .site-header .nav-links {
                    height: auto;
                    flex-direction: column;
                    align-items: stretch;
                    margin: 0;
                }

                .site-header .nav-link {
                    width: 100%;
                    height: auto;
                    justify-content: space-between;
                    padding: 13px 4px;
                }

                .site-header .nav-link::after {
                    display: none;
                }

                .site-header .nav-dropdown {
                    height: auto;
                }

                .site-header .dropdown-panel {
                    position: static;
                    width: 100%;
                    display: none;
                    padding: 0 0 6px;
                    border: 0;
                    box-shadow: none;
                    opacity: 1;
                    visibility: visible;
                    pointer-events: auto;
                    transform: none;
                }

                .site-header .nav-dropdown.open .dropdown-panel {
                    display: block;
                }

                .site-header .nav-actions {
                    margin-top: 10px;
                }

                .site-header .btn-login,
                .site-header .btn-register {
                    flex: 1;
                }
            }

            .footer {
                padding: 48px 0 20px;
                color: #9daabd;
                background: #102f5d;
                font-family: "Poppins", sans-serif;
            }

            .footer .container {
                width: 90%;
                max-width: 1180px;
                margin: 0 auto;
            }

            .footer-grid {
                display: grid;
                grid-template-columns: 1.4fr 1fr 1fr 0.9fr;
                gap: 40px;
            }

            .footer-logo {
                width: 140px;
                margin-bottom: 14px;
            }

            .footer-brand p {
                max-width: 380px;
                font-size: 13.5px;
                line-height: 1.75;
            }

            .footer h4 {
                margin-bottom: 16px;
                color: #fff;
                font-size: 15px;
                font-weight: 700;
            }

            .footer-grid > div > a,
            .footer-grid > div > span {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 11px;
                color: #9daabd;
                font-size: 13.5px;
            }

            .footer-grid > div > a {
                width: fit-content;
                transition:
                    color 0.2s ease,
                    transform 0.2s ease;
            }

            .footer-grid > div > a:hover {
                color: #fff;
                transform: translateX(4px);
            }

            .footer-grid i {
                width: 14px;
                color: #3dd8ef;
                font-size: 13px;
                text-align: center;
            }

            .footer-bottom {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                margin-top: 26px;
                padding-top: 20px;
                border-top: 1px solid #2d4870;
                color: #71839e;
                font-size: 13px;
            }

            .footer-bottom > div {
                display: flex;
                gap: 8px;
            }

            .footer-bottom a {
                color: #71839e;
                transition: color 0.2s ease;
            }

            .footer-bottom a:hover {
                color: #fff;
            }

            @media (max-width: 960px) {
                .footer-grid {
                    grid-template-columns: 1fr 1fr;
                    gap: 26px;
                }
            }

            @media (max-width: 600px) {
                .footer-grid {
                    grid-template-columns: 1fr;
                }

                .footer-bottom {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }
        </style>
        <link rel="stylesheet" href="assets/css/page-hero.css">
    </head>

    <body>

        <script>
            document.documentElement.classList.add('js');
        </script>

        <header class="site-header">
            <div class="container nav-wrap">
                <a href="index.php" class="brand"><img src="images/logo.png" alt="TOUN"></a>

                <nav class="main-nav" id="main-nav">
                    <div class="nav-links">
                        <a href="index.php" class="nav-link">Accueil</a>
                        <a href="formation.php" class="nav-link">Formation</a>

                        <div class="nav-dropdown">
                            <button class="nav-link nav-dropdown-btn active" type="button" aria-expanded="false">Solutions <i class="fa-solid fa-chevron-down"></i></button>
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

            <section class="page-hero">
                <img class="page-hero-img" src="images/IMG_0746.JPG.jpeg" alt="Formation professionnelle en entreprise">
                <div class="page-hero-overlay"></div>
                <div class="page-hero-inner">
                    <div class="page-hero-copy">
                        <h1>Développez les compétences de vos équipes <strong>plus simplement.</strong></h1>
                        <p>TOUN permet aux entreprises de former leurs collaborateurs, suivre leur progression et accompagner leur développement professionnel.</p>
                        <div class="page-hero-actions">
                            <a href="inscription.php" class="page-hero-btn">Créer un compte entreprise <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="contact.php" class="page-hero-btn light">Nous contacter</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="about">

                <div class="about-grid">

                    <div class="about-image reveal-left">

                        <img
                            src="images/ens.png"
                            alt="Espace entreprise TOUN"
                        >

                    </div>

                    <div class="about-content reveal-right">

                        <div class="section-tag">
                            POURQUOI TOUN ?
                        </div>

                        <h2>
                            Faites évoluer vos équipes sans
                            <span>complexifier votre organisation.</span>
                        </h2>

                        <p>
                            La formation professionnelle représente un véritable
                            levier de développement pour les entreprises.
                        </p>

                        <p>
                            TOUN permet de centraliser les parcours de formation
                            et de donner à vos collaborateurs un accès simple
                            à leurs apprentissages.
                        </p>

                        <div class="check-list">

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Formations adaptées aux besoins de vos équipes
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Gestion des collaborateurs
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Suivi de la progression
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Évaluations et certifications
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Accès depuis ordinateur ou mobile
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="benefits">

                <div class="benefits-container">

                    <div class="benefits-header reveal">

                        <div class="section-tag">
                            UNE SOLUTION POUR VOTRE ENTREPRISE
                        </div>

                        <h2>
                            Un environnement pensé pour
                            <span>vos équipes.</span>
                        </h2>

                        <p>
                            Une expérience de formation centralisée,
                            accessible et adaptée aux réalités professionnelles.
                        </p>

                    </div>

                    <div class="benefit-list stagger">

                        <div class="benefit-row">

                            <div class="benefit-number">
                                01
                            </div>

                            <div class="benefit-title">
                                Former vos collaborateurs
                            </div>

                            <div class="benefit-description">
                                Sélectionnez les formations correspondant
                                aux compétences à développer au sein de
                                votre organisation.
                            </div>

                        </div>

                        <div class="benefit-row">

                            <div class="benefit-number">
                                02
                            </div>

                            <div class="benefit-title">
                                Attribuer les formations
                            </div>

                            <div class="benefit-description">
                                Donnez à chaque collaborateur accès aux
                                parcours correspondant à ses objectifs.
                            </div>

                        </div>

                        <div class="benefit-row">

                            <div class="benefit-number">
                                03
                            </div>

                            <div class="benefit-title">
                                Suivre les progressions
                            </div>

                            <div class="benefit-description">
                                Consultez l'évolution des parcours et
                                accompagnez vos équipes dans leur apprentissage.
                            </div>

                        </div>

                        <div class="benefit-row">

                            <div class="benefit-number">
                                04
                            </div>

                            <div class="benefit-title">
                                Valoriser les compétences
                            </div>

                            <div class="benefit-description">
                                Les évaluations et certifications permettent
                                de valoriser les compétences développées.
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="enterprise-cta">

                <div class="enterprise-cta-box reveal-zoom">

                    <div class="enterprise-cta-content">

                        <small>
                            ESPACE ENTREPRISE
                        </small>

                        <h2>
                            Donnez à vos équipes les moyens de
                            <span>progresser.</span>
                        </h2>

                        <p>
                            Centralisez vos formations, accompagnez vos collaborateurs
                            et suivez leur évolution depuis un espace pensé pour
                            les besoins des entreprises.
                        </p>

                        <a href="inscription.php" class="enterprise-cta-btn">
                            Créer un compte entreprise
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                    <div class="enterprise-cta-image">

                        <img
                            src="images/imageu.png"
                            alt="Espace entreprise TOUN"
                        >

                    </div>

                </div>

            </section>

        </main>

        <footer class="footer">
            <div class="container footer-grid">
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

        <script>
            (function () {
                var header = document.querySelector('.site-header');
                if (!header) {
                    return;
                }
                var toggle = header.querySelector('.menu-toggle');
                var dropdown = header.querySelector('.nav-dropdown');
                var dropdownButton = header.querySelector('.nav-dropdown-btn');

                var onScroll = function () {
                    header.classList.toggle('scrolled', window.scrollY > 10);
                };
                onScroll();
                window.addEventListener('scroll', onScroll, { passive: true });

                var setMenu = function (open) {
                    header.classList.toggle('mobile-open', open);
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', String(open));
                        var icon = toggle.querySelector('i');
                        if (icon) {
                            icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
                        }
                    }
                };

                if (toggle) {
                    toggle.addEventListener('click', function () {
                        setMenu(!header.classList.contains('mobile-open'));
                    });
                }

                header.querySelectorAll('.main-nav > .nav-links > a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        setMenu(false);
                    });
                });

                if (dropdown && dropdownButton) {
                    dropdownButton.addEventListener('click', function (event) {
                        event.stopPropagation();
                        var open = dropdown.classList.toggle('open');
                        dropdownButton.setAttribute('aria-expanded', String(open));
                    });

                    document.addEventListener('click', function (event) {
                        if (!dropdown.contains(event.target)) {
                            dropdown.classList.remove('open');
                            dropdownButton.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                window.addEventListener('resize', function () {
                    if (window.innerWidth > 960) {
                        setMenu(false);
                    }
                });
            })();
        </script>

        <script>
            (function () {
                var targets = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom, .stagger');

                document.querySelectorAll('.stagger').forEach(function (group) {
                    Array.prototype.forEach.call(group.children, function (child, index) {
                        child.style.transitionDelay = index * 90 + 'ms';
                    });
                });

                var reveal = function (element) {
                    element.classList.add('visible');
                    if (element.classList.contains('stagger')) {
                        var total = element.children.length * 90 + 800;
                        setTimeout(function () {
                            element.classList.add('done');
                            Array.prototype.forEach.call(element.children, function (child) {
                                child.style.transitionDelay = '';
                            });
                        }, total);
                    }
                };

                if (!('IntersectionObserver' in window)) {
                    Array.prototype.forEach.call(targets, reveal);
                } else {
                    var observer = new IntersectionObserver(
                        function (entries) {
                            entries.forEach(function (entry) {
                                if (entry.isIntersecting) {
                                    reveal(entry.target);
                                    observer.unobserve(entry.target);
                                }
                            });
                        },
                        { threshold: 0.15, rootMargin: '0px 0px -6% 0px' }
                    );
                    Array.prototype.forEach.call(targets, function (t) {
                        observer.observe(t);
                    });
                }
            })();
        </script>

    </body>
</html>