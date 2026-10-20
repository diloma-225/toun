<?php session_start(); ?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Professionnels | TOUN</title>

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
                background: #f5fafc;
                color: #173f70;
            }

            a {
                text-decoration: none;
            }

            img {
                display: block;
                max-width: 100%;
            }

            .about {
                padding: 55px 5%;
                background: #f7fbfc;
            }

            .about-container {
                max-width: 1160px;
                margin: auto;
                display: grid;
                grid-template-columns: 0.9fr 1.1fr;
                align-items: center;
                gap: 55px;
            }

            .about-image {
                position: relative;
            }

            .about-image img {
                width: 100%;
                height: 275px;
                object-fit: cover;
                border-radius: 23px;
                box-shadow: 0 18px 35px rgba(20, 65, 100, 0.12);
            }

            .about-label {
                position: absolute;
                right: -12px;
                bottom: -12px;
                width: 155px;
                padding: 15px;
                color: white;
                background: linear-gradient(135deg, #123e78, #08a7c4);
                border-radius: 14px;
                box-shadow: 0 12px 25px rgba(15, 70, 110, 0.2);
            }

            .about-label strong {
                display: block;
                margin-bottom: 5px;
                font-size: 12.5px;
            }

            .about-label span {
                color: rgba(255, 255, 255, 0.82);
                font-size: 10px;
                line-height: 1.55;
            }

            .section-tag {
                margin-bottom: 8px;
                color: #078fd2;
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 2px;
            }

            .about-content h2 {
                max-width: 570px;
                margin-bottom: 13px;
                color: #173f70;
                font-size: 27px;
                line-height: 1.22;
            }

            .about-content h2 span {
                color: #078fd2;
            }

            .about-content > p {
                max-width: 600px;
                margin-bottom: 9px;
                color: #7088a0;
                font-size: 11.5px;
                line-height: 1.7;
            }

            .check-list {
                margin-top: 15px;
                display: grid;
                gap: 7px;
            }

            .check-item {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #58728c;
                font-size: 11px;
            }

            .check-item i {
                width: 19px;
                height: 19px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                border-radius: 50%;
                color: #078fd2;
                background: #dcf5f8;
                font-size: 10px;
            }

            .experience {
                padding: 40px 5%;
                color: white;
                background: linear-gradient(110deg, #123e78, #078fd2, #087cb5);
            }

            .experience-container {
                max-width: 1100px;
                margin: auto;
            }

            .experience-header {
                margin-bottom: 23px;
                text-align: center;
            }

            .experience-header .section-tag {
                color: #42dce5;
            }

            .experience-header h2 {
                margin-bottom: 6px;
                color: white;
                font-size: 24px;
            }

            .experience-header h2 span {
                color: #fff;
            }

            .experience-header p {
                color: rgba(255, 255, 255, 0.7);
                font-size: 10.5px;
            }

            .experience-list {
                border-top: 1px solid rgba(255, 255, 255, 0.15);
            }

            .experience-item {
                min-height: 62px;
                display: grid;
                grid-template-columns: 48px 180px 1fr;
                align-items: center;
                gap: 18px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            }

            .experience-number {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(65, 221, 232, 0.55);
                border-radius: 50%;
                color: #4ce0e8;
                font-size: 10.5px;
                font-weight: 700;
            }

            .experience-item h3 {
                color: white;
                font-size: 12px;
            }

            .experience-item p {
                color: rgba(255, 255, 255, 0.67);
                font-size: 10.5px;
                line-height: 1.5;
            }

            .parcours {
                padding: 45px 5% 48px;
                background: #edf8fb;
            }

            .parcours-container {
                max-width: 1160px;
                margin: auto;
            }

            .parcours-header {
                margin-bottom: 35px;
                text-align: center;
            }

            .parcours-header h2 {
                margin-bottom: 7px;
                color: #173f70;
                font-size: 26px;
            }

            .parcours-header h2 span {
                color: #078fd2;
            }

            .parcours-header p {
                color: #748ba1;
                font-size: 11px;
            }

            .timeline {
                position: relative;
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 0;
            }

            .timeline::before {
                content: "";
                position: absolute;
                left: 10%;
                right: 10%;
                top: 19px;
                height: 2px;
                background: linear-gradient(90deg, #bed1e7, #77cddd);
            }

            .timeline-step {
                position: relative;
                z-index: 2;
                text-align: center;
            }

            .timeline-number {
                width: 39px;
                height: 39px;
                margin: 0 auto 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 2px solid #3975ba;
                border-radius: 50%;
                color: #3975ba;
                background: #f7fcfd;
                font-size: 11px;
                font-weight: 700;
                box-shadow: 0 7px 14px rgba(30, 80, 130, 0.08);
            }

            .timeline-step.active .timeline-number {
                color: white;
                background: #16aec2;
                border-color: #16aec2;
                box-shadow:
                    0 0 0 6px rgba(22, 174, 194, 0.11),
                    0 8px 16px rgba(22, 174, 194, 0.15);
            }

            .timeline-content {
                padding: 0 12px;
            }

            .timeline-content h3 {
                margin-bottom: 5px;
                color: #173f70;
                font-size: 11.5px;
            }

            .timeline-content p {
                max-width: 170px;
                margin: auto;
                color: #7c91a6;
                font-size: 10px;
                line-height: 1.5;
            }

            .timeline-label {
                display: inline-block;
                margin-bottom: 5px;
                padding: 3px 7px;
                border-radius: 20px;
                color: #078da8;
                background: #d8f2f5;
                font-size: 9px;
                font-weight: 800;
                letter-spacing: 0.5px;
            }

            .final-cta {
                padding: 22px 5%;
                background: #f5fafc;
            }

            .final-cta-box {
                max-width: 1160px;
                min-height: 135px;
                margin: auto;
                padding: 22px 28px;
                display: grid;
                grid-template-columns: 1.35fr 0.65fr;
                align-items: center;
                gap: 30px;
                overflow: hidden;
                border-radius: 20px;
                color: white;
                background: linear-gradient(110deg, #123e78, #087ab6, #10a6c0);
            }

            .final-content small {
                color: #48e0e8;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 2px;
            }

            .final-content h2 {
                max-width: 580px;
                margin: 5px 0;
                font-size: 21px;
                line-height: 1.2;
            }

            .final-content h2 span {
                color: #fff;
            }

            .final-content p {
                max-width: 540px;
                margin-bottom: 10px;
                color: rgba(255, 255, 255, 0.7);
                font-size: 10.5px;
                line-height: 1.5;
            }

            .final-btn {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 8px 15px;
                border-radius: 20px;
                color: #075b91;
                background: white;
                font-size: 11px;
                font-weight: 700;
            }

            .final-image {
                height: 95px;
                overflow: hidden;
                border-radius: 13px;
            }

            .final-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .js .reveal {
                opacity: 0;
                transform: translateY(35px);
                transition:
                    opacity 0.75s ease,
                    transform 0.75s cubic-bezier(0.2, 0.65, 0.2, 1);
            }

            .js .reveal.reveal-left {
                transform: translateX(-35px);
            }

            .js .reveal.reveal-right {
                transform: translateX(35px);
            }

            .js .reveal.is-visible {
                opacity: 1;
                transform: translate(0, 0);
            }

            .js .experience-item:nth-child(2),
            .js .timeline-step:nth-child(2),
            .js .check-item:nth-child(2) {
                transition-delay: 0.08s;
            }

            .js .experience-item:nth-child(3),
            .js .timeline-step:nth-child(3),
            .js .check-item:nth-child(3) {
                transition-delay: 0.16s;
            }

            .js .experience-item:nth-child(4),
            .js .timeline-step:nth-child(4),
            .js .check-item:nth-child(4) {
                transition-delay: 0.24s;
            }

            .js .timeline-step:nth-child(5),
            .js .check-item:nth-child(5) {
                transition-delay: 0.32s;
            }

            @media (prefers-reduced-motion: reduce) {
                *,
                *::before,
                *::after {
                    scroll-behavior: auto !important;
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }

            @media (max-width: 800px) {
                .logo img {
                    width: 125px;
                }

                .about-container {
                    grid-template-columns: 1fr;
                }

                .timeline {
                    grid-template-columns: 1fr;
                    gap: 25px;
                }

                .timeline::before {
                    left: 50%;
                    right: auto;
                    top: 19px;
                    width: 2px;
                    height: calc(100% - 38px);
                    background: linear-gradient(180deg, #bed1e7, #77cddd);
                }

                .timeline-content p {
                    max-width: 250px;
                }

                .final-cta-box {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 600px) {
                .about {
                    padding: 45px 6%;
                }

                .about-image img {
                    height: 230px;
                }

                .about-content h2 {
                    font-size: 23px;
                }

                .experience-item {
                    grid-template-columns: 40px 1fr;
                    padding: 12px 0;
                }

                .experience-item p {
                    grid-column: 2;
                    margin-top: -6px;
                }
            }

            .site-header a,
            .footer a {
                text-decoration: none;
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
                <img class="page-hero-img top" src="images/imag2.png" alt="Professionnel utilisant TOUN">
                <div class="page-hero-overlay"></div>
                <div class="page-hero-inner">
                    <div class="page-hero-copy">
                        <h1>Développez vos compétences <strong>à votre rythme.</strong></h1>
                        <p>TOUN vous permet d'accéder à des formations professionnelles adaptées à vos objectifs et à votre évolution.</p>
                        <div class="page-hero-actions">
                            <a href="formation.php" class="page-hero-btn">Découvrir les formations <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="inscription.php" class="page-hero-btn light">Créer un compte</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="about reveal">

                <div class="about-container">

                    <div class="about-image reveal reveal-left">

                        <img
                            src="images/in.png"
                            alt="Professionnel en formation"
                        >

                    </div>

                    <div class="about-content reveal reveal-right">

                        <div class="section-tag">
                            POURQUOI TOUN ?
                        </div>

                        <h2>
                            Faites évoluer vos compétences
                            <span>sans interrompre votre activité.</span>
                        </h2>

                        <p>
                            Votre temps est précieux. TOUN vous donne accès à un
                            environnement de formation conçu pour s'adapter à votre rythme.
                        </p>

                        <p>
                            Choisissez votre formation, progressez étape par étape
                            et suivez votre évolution depuis votre espace personnel.
                        </p>

                        <div class="check-list">

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Formations adaptées aux besoins professionnels
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Accès en ligne depuis ordinateur ou mobile
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Progression suivie dans votre espace
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Évaluations et certification
                            </div>

                            <div class="check-item">
                                <i class="fa-solid fa-check"></i>
                                Accompagnement par les formateurs
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="experience reveal">

                <div class="experience-container">

                    <div class="experience-header reveal">

                        <div class="section-tag">
                            VOTRE EXPÉRIENCE
                        </div>

                        <h2>
                            Tout ce qu'il vous faut pour
                            <span>progresser.</span>
                        </h2>

                        <p>
                            Une expérience de formation adaptée à votre quotidien professionnel.
                        </p>

                    </div>

                    <div class="experience-list">

                        <div class="experience-item reveal">

                            <div class="experience-number">01</div>

                            <h3>Formation en ligne</h3>

                            <p>
                                Accédez à vos contenus depuis votre ordinateur ou votre smartphone.
                            </p>

                        </div>

                        <div class="experience-item reveal">

                            <div class="experience-number">02</div>

                            <h3>À votre rythme</h3>

                            <p>
                                Organisez votre apprentissage selon votre disponibilité.
                            </p>

                        </div>

                        <div class="experience-item reveal">

                            <div class="experience-number">03</div>

                            <h3>Suivi de progression</h3>

                            <p>
                                Retrouvez votre avancement directement dans votre espace.
                            </p>

                        </div>

                        <div class="experience-item reveal">

                            <div class="experience-number">04</div>

                            <h3>Certification</h3>

                            <p>
                                Validez votre parcours et obtenez votre certificat.
                            </p>

                        </div>

                    </div>

                </div>

            </section>

            <section class="parcours reveal">

                <div class="parcours-container">

                    <div class="parcours-header reveal">

                        <div class="section-tag">
                            VOTRE PARCOURS
                        </div>

                        <h2>
                            Un parcours simple,
                            <span>en 5 étapes.</span>
                        </h2>

                        <p>
                            De l'inscription à la certification, chaque étape reste simple.
                        </p>

                    </div>

                    <div class="timeline">

                        <div class="timeline-step reveal">

                            <div class="timeline-number">
                                01
                            </div>

                            <div class="timeline-content">

                                <h3>
                                    Créez votre compte
                                </h3>

                                <p>
                                    Inscrivez-vous simplement.
                                </p>

                            </div>

                        </div>

                        <div class="timeline-step reveal">

                            <div class="timeline-number">
                                02
                            </div>

                            <div class="timeline-content">

                                <h3>
                                    Choisissez votre formation
                                </h3>

                                <p>
                                    Trouvez le parcours qui vous correspond.
                                </p>

                            </div>

                        </div>

                        <div class="timeline-step active reveal">

                            <div class="timeline-number">
                                03
                            </div>

                            <div class="timeline-content">

                                <span class="timeline-label">
                                    PAIEMENT REQUIS
                                </span>

                                <h3>
                                    Payez votre formation
                                </h3>

                                <p>
                                    Validez votre inscription.
                                </p>

                            </div>

                        </div>

                        <div class="timeline-step reveal">

                            <div class="timeline-number">
                                04
                            </div>

                            <div class="timeline-content">

                                <h3>
                                    Suivez votre formation
                                </h3>

                                <p>
                                    Accédez à vos cours et ressources.
                                </p>

                            </div>

                        </div>

                        <div class="timeline-step reveal">

                            <div class="timeline-number">
                                05
                            </div>

                            <div class="timeline-content">

                                <h3>
                                    Obtenez votre certificat
                                </h3>

                                <p>
                                    Validez votre parcours.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="final-cta reveal">

                <div class="final-cta-box">

                    <div class="final-content reveal reveal-left">

                        <small>
                            PRÊT À PROGRESSER ?
                        </small>

                        <h2>
                            Donnez une nouvelle impulsion à
                            <span>votre parcours.</span>
                        </h2>

                        <p>
                            Explorez les formations disponibles sur TOUN et développez
                            les compétences correspondant à vos objectifs.
                        </p>

                        <a href="formation.php" class="final-btn">
                            Voir les formations
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                    <div class="final-image reveal reveal-right">

                        <img
                            src="images/10.jpg"
                            alt="Apprentissage professionnel TOUN"
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
            document.addEventListener('DOMContentLoaded', () => {
                const elements = document.querySelectorAll('.reveal');

                const observer = new IntersectionObserver(
                    (entries, obs) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                obs.unobserve(entry.target);
                            }
                        });
                    },
                    {
                        threshold: 0.14,
                        rootMargin: '0px 0px -45px 0px'
                    }
                );

                elements.forEach(element => observer.observe(element));
            });
        </script>

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

    </body>

</html>