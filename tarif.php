<?php require_once __DIR__ . '/config/connexion.php'; ?>

<!DOCTYPE html>
<html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Tarifs | TOUN</title>

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
                color: #173d69;
                background: #f5f9fc;
            }

            a {
                text-decoration: none;
            }

            img {
                display: block;
                max-width: 100%;
            }

            .container {
                width: min(1180px, 92%);
                margin: auto;
            }

            .section-label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: #20d6e5;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 1.7px;
            }

            .hero-badge {
                position: absolute;
                left: 5px;
                bottom: 25px;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 14px;
                color: #173d69;
                font-size: 10.5px;
                font-weight: 600;
                background: #fff;
                border-radius: 13px;
                box-shadow: 0 12px 25px rgba(0, 30, 70, 0.18);
            }

            .hero-badge i {
                width: 27px;
                height: 27px;
                display: grid;
                place-items: center;
                color: #078fd2;
                background: #e0f7fa;
                border-radius: 50%;
            }

            .parcours {
                padding: 50px 0 55px;
                background: #f7fafc;
            }

            .heading {
                max-width: 700px;
                margin: auto;
                text-align: center;
            }

            .heading h2 {
                margin: 9px 0;
                color: #172f55;
                font-size: 30px;
                font-weight: 800;
                line-height: 1.25;
            }

            .heading h2 span {
                color: #3d70ba;
            }

            .heading p {
                color: #71869e;
                font-size: 11px;
                line-height: 1.75;
            }

            .steps {
                position: relative;
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                margin-top: 42px;
            }

            .steps-line {
                position: absolute;
                top: 27px;
                left: 10%;
                right: 10%;
                height: 2px;
                background: linear-gradient(90deg, #b9cfe6, #23ccdd);
            }

            .step {
                position: relative;
                z-index: 2;
                padding: 0 10px;
                text-align: center;
            }

            .step-number {
                width: 54px;
                height: 54px;
                display: grid;
                place-items: center;
                margin: 0 auto 14px;
                color: #3d70ba;
                font-size: 11.5px;
                font-weight: 700;
                background: #f7fafc;
                border: 3px solid #3d70ba;
                border-radius: 50%;
                box-shadow: 0 7px 18px rgba(30, 80, 130, 0.1);
                transition: 0.3s;
            }

            .step:hover .step-number {
                color: #fff;
                border-color: #1acbdc;
                background: linear-gradient(135deg, #078fd2, #1acbdc);
                transform: scale(1.07);
            }

            .step.active .step-number {
                color: #fff;
                border-color: #1acbdc;
                background: linear-gradient(135deg, #078fd2, #1acbdc);
                box-shadow:
                    0 0 0 7px rgba(26, 203, 220, 0.12),
                    0 9px 20px rgba(20, 130, 185, 0.2);
            }

            .payment-required {
                display: inline-block;
                margin-bottom: 5px;
                padding: 3px 7px;
                color: #0780a7;
                font-size: 9.5px;
                font-weight: 700;
                background: #def5f9;
                border-radius: 15px;
            }

            .step h3 {
                margin-bottom: 5px;
                color: #173052;
                font-size: 11.5px;
                font-weight: 700;
            }

            .step p {
                color: #7b8da2;
                font-size: 10px;
                line-height: 1.65;
            }

            .pricing {
                padding: 48px 0;
                color: #fff;
                background:
                    radial-gradient(circle at 8% 30%, rgba(40, 210, 230, 0.15), transparent 25%),
                    linear-gradient(120deg, #07316d, #075fb5);
            }

            .pricing .heading h2,
            .pricing .heading h2 span {
                color: #fff;
            }

            .pricing .heading p {
                color: #c9deef;
            }

            .pricing-items {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                margin-top: 32px;
                border: 1px solid rgba(255, 255, 255, 0.14);
                border-radius: 16px;
                overflow: hidden;
                background: rgba(255, 255, 255, 0.05);
            }

            .pricing-item {
                min-height: 135px;
                padding: 20px;
                border-right: 1px solid rgba(255, 255, 255, 0.12);
                transition: 0.25s;
            }

            .pricing-item:last-child {
                border-right: 0;
            }

            .pricing-item:hover {
                background: rgba(255, 255, 255, 0.07);
            }

            .pricing-icon {
                width: 37px;
                height: 37px;
                display: grid;
                place-items: center;
                margin-bottom: 11px;
                color: #fff;
                background: linear-gradient(135deg, #13cce0, #078fd2);
                border-radius: 10px;
            }

            .pricing-item h3 {
                margin-bottom: 5px;
                font-size: 11.5px;
            }

            .pricing-item p {
                color: #c8ddec;
                font-size: 10px;
                line-height: 1.7;
            }

            .payment-section {
                padding: 48px 0;
                background: #eaf7fb;
            }

            .payment-grid {
                display: grid;
                grid-template-columns: 1fr 0.85fr;
                align-items: center;
                gap: 45px;
            }

            .payment-text h2 {
                margin: 8px 0 11px;
                color: #173d69;
                font-size: 28px;
                font-weight: 800;
                line-height: 1.25;
            }

            .payment-text h2 span {
                color: #078fd2;
            }

            .payment-text p {
                max-width: 510px;
                color: #6d839a;
                font-size: 11px;
                line-height: 1.75;
            }

            .methods {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 11px;
                max-width: 700px;
                margin-top: 23px;
            }

            .method {
                min-height: 78px;
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 11px 15px;
                background: #fff;
                border: 1px solid #dcebf1;
                border-radius: 14px;
                box-shadow: 0 7px 18px rgba(25, 80, 120, 0.06);
                transition: 0.25s;
            }

            .method:hover {
                transform: translateY(-2px);
                box-shadow: 0 11px 22px rgba(25, 80, 120, 0.1);
            }

            .method-logo {
                width: 45px;
                height: 45px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                border-radius: 11px;
            }

            .method-logo img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }

            .wave-method .method-logo {
                background: #fff;
            }

            .moov-method .method-logo {
                background: #fff;
            }

            .orange-method .method-logo {
                background: #fff;
            }

            .card-method .method-logo {
                color: #fff;
                background: #176bb5;
            }

            .card-method .method-logo i {
                font-size: 18px;
            }

            .method strong {
                display: block;
                color: #173d69;
                font-size: 11px;
                font-weight: 700;
            }

            .method small {
                display: block;
                margin-top: 3px;
                color: #8294a7;
                font-size: 10px;
            }

            .payment-visual {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .payment-image {
                width: 100%;
                max-width: 450px;
                height: 265px;
                object-fit: contain;
            }

            .faq {
                padding: 45px 0;
                background: linear-gradient(120deg, #062a4b, #1c93b1);
            }

            .faq .heading h2,
            .faq .heading h2 span {
                color: #fff;
            }

            .faq .heading p {
                color: #c8dced;
            }

            .faq-list {
                max-width: 850px;
                margin: 26px auto 0;
            }

            .faq-item {
                margin-bottom: 7px;
                overflow: hidden;
                background: rgba(255, 255, 255, 0.07);
                border: 1px solid rgba(255, 255, 255, 0.14);
                border-radius: 10px;
            }

            .faq-question {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 13px 17px;
                color: #fff;
                font-family: inherit;
                font-size: 11px;
                font-weight: 600;
                text-align: left;
                background: transparent;
                border: 0;
                cursor: pointer;
            }

            .faq-question i {
                color: #31d7e6;
                font-size: 10.5px;
                transition: 0.25s;
            }

            .faq-answer {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }

            .faq-answer p {
                padding: 0 17px 13px;
                color: #c8ddec;
                font-size: 10px;
                line-height: 1.7;
            }

            .faq-item.open .faq-answer {
                max-height: 100px;
            }

            .faq-item.open .faq-question i {
                transform: rotate(180deg);
            }

            .final-section {
                padding: 34px 0;
                background: #f5f9fc;
            }

            .final-box {
                display: grid;
                grid-template-columns: 1.2fr 0.65fr;
                align-items: center;
                gap: 25px;
                min-height: 205px;
                padding: 28px 35px;
                overflow: hidden;
                background: linear-gradient(120deg, #e8f6fa, #f5fafc);
                border: 1px solid #dcecf2;
                border-radius: 20px;
            }

            .final-content h2 {
                max-width: 550px;
                margin: 8px 0 9px;
                color: #173d69;
                font-size: 25px;
                font-weight: 800;
                line-height: 1.25;
            }

            .final-content h2 span {
                color: #078fd2;
            }

            .final-content p {
                max-width: 510px;
                color: #6e839a;
                font-size: 11px;
                line-height: 1.7;
            }

            .final-button {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 14px;
                padding: 10px 17px;
                color: #fff;
                font-size: 11px;
                font-weight: 600;
                background: linear-gradient(135deg, #123d76, #078bd4);
                border-radius: 20px;
            }

            .final-image {
                width: 100%;
                height: 175px;
                object-fit: contain;
            }

            @media (max-width: 1000px) {
                .payment-grid,
                .final-box {
                    grid-template-columns: 1fr;
                }

                .steps {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 30px;
                }

                .steps-line {
                    display: none;
                }

                .pricing-items {
                    grid-template-columns: repeat(2, 1fr);
                }

                .pricing-item:nth-child(2) {
                    border-right: 0;
                }

                .pricing-item:nth-child(-n + 2) {
                    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
                }

                .payment-visual {
                    order: -1;
                }

                .final-box {
                    text-align: center;
                }

                .final-content p {
                    margin: auto;
                }
            }

            @media (max-width: 600px) {
                .logo img {
                    width: 98px;
                }

                .hero-badge {
                    left: 5%;
                    bottom: 0;
                }

                .heading h2 {
                    font-size: 25px;
                }

                .steps {
                    grid-template-columns: 1fr;
                    gap: 27px;
                }

                .pricing-items {
                    grid-template-columns: 1fr;
                }

                .pricing-item {
                    border-right: 0;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
                }

                .pricing-item:last-child {
                    border-bottom: 0;
                }

                .methods {
                    grid-template-columns: 1fr;
                }

                .payment-text h2 {
                    font-size: 25px;
                }

                .payment-image {
                    height: 210px;
                }

                .final-box {
                    padding: 25px 18px;
                }

                .final-content h2 {
                    font-size: 23px;
                }

                .final-image {
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

                        <a href="tarif.php" class="nav-link active">Tarifs</a>
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
                <img class="page-hero-img contain" src="images/img4.png" alt="Tarification TOUN">
                <div class="page-hero-overlay"></div>
                <div class="page-hero-inner">
                    <div class="page-hero-copy">
                        <h1>Formez-vous simplement, <strong>sans abonnement.</strong></h1>
                        <p>Sur TOUN, chaque formation possède son propre tarif. Vous payez uniquement la formation que vous choisissez, sans engagement mensuel.</p>
                        <div class="page-hero-actions">
                            <a href="formation.php" class="page-hero-btn">Découvrir les formations <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="parcours">

                <div class="container">

                    <div class="heading">

                        <span class="section-label">
                            <i class="fa-solid fa-route"></i>
                            VOTRE PARCOURS
                        </span>

                        <h2>
                            Comment ça
                            <span>fonctionne ?</span>
                        </h2>

                        <p>
                            Un parcours simple pour accéder à votre formation.
                        </p>

                    </div>

                    <div class="steps stagger">

                        <div class="steps-line"></div>

                        <div class="step">

                            <div class="step-number">
                                01
                            </div>

                            <h3>
                                Créez votre compte
                            </h3>

                            <p>
                                Inscrivez-vous simplement.
                            </p>

                        </div>

                        <div class="step">

                            <div class="step-number">
                                02
                            </div>

                            <h3>
                                Choisissez votre formation
                            </h3>

                            <p>
                                Trouvez celle qui vous correspond.
                            </p>

                        </div>

                        <div class="step active">

                            <div class="step-number">
                                03
                            </div>

                            <span class="payment-required">
                                PAIEMENT REQUIS
                            </span>

                            <h3>
                                Payez votre formation
                            </h3>

                            <p>
                                Validez votre inscription.
                            </p>

                        </div>

                        <div class="step">

                            <div class="step-number">
                                04
                            </div>

                            <h3>
                                Suivez votre formation
                            </h3>

                            <p>
                                Accédez à vos cours.
                            </p>

                        </div>

                        <div class="step">

                            <div class="step-number">
                                05
                            </div>

                            <h3>
                                Obtenez votre certificat
                            </h3>

                            <p>
                                Validez votre parcours.
                            </p>

                        </div>

                    </div>

                </div>

            </section>

            <section class="pricing">

                <div class="container">

                    <div class="heading">

                        <span class="section-label">
                            UNE TARIFICATION SIMPLE
                        </span>

                        <h2>
                            Un prix clair,
                            <span>sans surprise.</span>
                        </h2>

                        <p>
                            TOUN privilégie une expérience transparente
                            à chaque étape de votre parcours.
                        </p>

                    </div>

                    <div class="pricing-items stagger">

                        <div class="pricing-item">

                            <div class="pricing-icon">
                                <i class="fa-solid fa-tag"></i>
                            </div>

                            <h3>
                                Paiement à l'unité
                            </h3>

                            <p>
                                Payez uniquement la formation
                                que vous avez choisie.
                            </p>

                        </div>

                        <div class="pricing-item">

                            <div class="pricing-icon">
                                <i class="fa-solid fa-eye"></i>
                            </div>

                            <h3>
                                Prix transparent
                            </h3>

                            <p>
                                Le tarif est visible avant
                                la validation de votre achat.
                            </p>

                        </div>

                        <div class="pricing-item">

                            <div class="pricing-icon">
                                <i class="fa-solid fa-calendar-xmark"></i>
                            </div>

                            <h3>
                                Aucun abonnement
                            </h3>

                            <p>
                                Pas de forfait mensuel obligatoire
                                pour accéder à une formation.
                            </p>

                        </div>

                        <div class="pricing-item">

                            <div class="pricing-icon">
                                <i class="fa-solid fa-unlock"></i>
                            </div>

                            <h3>
                                Accès après paiement
                            </h3>

                            <p>
                                Votre formation devient accessible
                                après confirmation du paiement.
                            </p>

                        </div>

                    </div>

                </div>

            </section>

            <section class="payment-section">

                <div class="container payment-grid">

                    <div class="payment-text">

                        <span class="section-label">
                            MOYENS DE PAIEMENT
                        </span>

                        <h2>
                            Payez simplement avec
                            <span>TOUN.</span>
                        </h2>

                        <p>
                            Sélectionnez votre formation, consultez son tarif
                            puis choisissez le moyen de paiement qui vous convient.
                            Une fois la transaction confirmée, votre accès est
                            associé à votre compte.
                        </p>

                        <div class="methods stagger">

                            <div class="method wave-method">

                                <div class="method-logo">

                                    <img
                                        src="images/paiement/wave.png"
                                        alt="Wave"
                                    >

                                </div>

                                <div>

                                    <strong>
                                        Wave
                                    </strong>

                                    <small>
                                        Paiement mobile
                                    </small>

                                </div>

                            </div>

                            <div class="method moov-method">

                                <div class="method-logo">

                                    <img
                                        src="images/paiement/moov-money.png"
                                        alt="Moov Money"
                                    >

                                </div>

                                <div>

                                    <strong>
                                        Moov Money
                                    </strong>

                                    <small>
                                        Paiement mobile
                                    </small>

                                </div>

                            </div>

                            <div class="method orange-method">

                                <div class="method-logo">

                                    <img
                                        src="images/paiement/orange-money.png"
                                        alt="Orange Money"
                                    >

                                </div>

                                <div>

                                    <strong>
                                        Orange Money
                                    </strong>

                                    <small>
                                        Paiement mobile
                                    </small>

                                </div>

                            </div>

                            <div class="method card-method">

                                <div class="method-logo">

                                    <i class="fa-solid fa-credit-card"></i>

                                </div>

                                <div>

                                    <strong>
                                        Carte bancaire
                                    </strong>

                                    <small>
                                        Paiement sécurisé
                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="payment-visual">

                        <img
                            src="images/img3.png"
                            alt="Paiement sécurisé TOUN"
                            class="payment-image"
                        >

                    </div>

                </div>

            </section>

            <section class="faq">

                <div class="container">

                    <div class="heading">

                        <span class="section-label">
                            QUESTIONS FRÉQUENTES
                        </span>

                        <h2>
                            Tout savoir sur les
                            <span>tarifs.</span>
                        </h2>

                        <p>
                            Retrouvez les réponses aux principales questions
                            concernant le paiement et l'accès aux formations.
                        </p>

                    </div>

                    <div class="faq-list">

                        <div class="faq-item">

                            <button class="faq-question">

                                Comment sont définis les prix des formations ?

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div class="faq-answer">

                                <p>
                                    Chaque formation possède son propre tarif,
                                    affiché directement sur sa fiche avant l'achat.
                                </p>

                            </div>

                        </div>

                        <div class="faq-item">

                            <button class="faq-question">

                                Est-ce qu'il y a un abonnement mensuel ?

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div class="faq-answer">

                                <p>
                                    Non. TOUN fonctionne sur un modèle
                                    de paiement par formation.
                                </p>

                            </div>

                        </div>

                        <div class="faq-item">

                            <button class="faq-question">

                                Que se passe-t-il après mon paiement ?

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div class="faq-answer">

                                <p>
                                    Après confirmation du paiement,
                                    votre formation est associée à votre compte.
                                </p>

                            </div>

                        </div>

                        <div class="faq-item">

                            <button class="faq-question">

                                Quand puis-je accéder à ma formation ?

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div class="faq-answer">

                                <p>
                                    L'accès est disponible après validation
                                    de votre inscription et du paiement.
                                </p>

                            </div>

                        </div>

                        <div class="faq-item">

                            <button class="faq-question">

                                Une entreprise peut-elle acheter plusieurs accès ?

                                <i class="fa-solid fa-chevron-down"></i>

                            </button>

                            <div class="faq-answer">

                                <p>
                                    Oui. TOUN prévoit une solution permettant
                                    aux entreprises de former leurs collaborateurs.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="final-section">

                <div class="container">

                    <div class="final-box reveal-zoom">

                        <div class="final-content">

                            <span class="section-label">
                                PRÊT À COMMENCER ?
                            </span>

                            <h2>
                                Choisissez votre parcours
                                <span>et commencez à apprendre.</span>
                            </h2>

                            <p>
                                Explorez les formations disponibles sur TOUN
                                et trouvez celle qui correspond à vos objectifs
                                professionnels.
                            </p>

                            <a
                                href="formation.php"
                                class="final-button"
                            >
                                Voir les formations
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>

                        </div>

                        <div>

                            <img
                                src="images/10.jpg"
                                alt="Apprentissage en ligne TOUN"
                                class="final-image"
                            >

                        </div>

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
            const faqItems = document.querySelectorAll('.faq-item');

            faqItems.forEach(function (item) {
                const button = item.querySelector('.faq-question');

                button.addEventListener('click', function () {
                    faqItems.forEach(function (other) {
                        if (other !== item) {
                            other.classList.remove('open');
                        }
                    });

                    item.classList.toggle('open');
                });
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