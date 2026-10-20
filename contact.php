<?php
session_start();

require_once 'config/connexion.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$active = basename($_SERVER['PHP_SELF']);

$success = isset($_GET['message']) && $_GET['message'] === 'success';
?>

<!DOCTYPE html>

<html lang="fr">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Contact | TOUN</title>

        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

        <link
            rel="stylesheet"
            href="assets/css/toun.css"
        >

        <style>
            html,
            body {
                margin: 0;
                padding: 0;
            }

            body {
                background: #f5f8fc;
                color: #173f70;
            }

            main {
                padding-top: 0;
            }

            .contact-section {
                padding: 34px 20px 46px;
                background: #f5f8fc;
            }

            .contact-container {
                width: 100%;
                max-width: 880px;
                margin: 0 auto;
            }

            .contact-intro {
                max-width: 560px;
                margin: 0 auto 17px;
                text-align: center;
            }

            .contact-intro h2 {
                margin: 0 0 5px;
                color: #173f70;
                font-size: 20px;
            }

            .contact-intro p {
                margin: 0;
                color: #7f899b;
                font-size: 11px;
            }

            .contact-layout {
                display: grid;
                grid-template-columns: 235px minmax(0, 560px);
                justify-content: center;
                gap: 0;
                align-items: stretch;
                filter: drop-shadow(0 13px 25px rgba(24, 33, 63, 0.08));
            }

            .contact-info {
                padding: 24px 22px;
                border-radius: 13px 0 0 13px;
                background: linear-gradient(155deg, #073e73, #24568d);
            }

            .contact-info h3 {
                margin: 0 0 5px;
                color: #ffffff;
                font-size: 16px;
            }

            .contact-info > p {
                margin: 0 0 22px;
                color: rgba(255, 255, 255, 0.7);
                font-size: 11px;
                line-height: 1.55;
            }

            .contact-item {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 17px;
            }

            .contact-item:last-child {
                margin-bottom: 0;
            }

            .contact-icon {
                width: 35px;
                height: 34px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: rgba(103, 215, 223, 0.14);
                color: #55dce0;
                font-size: 12.5px;
            }

            .contact-item strong {
                display: block;
                margin: 2px 0 3px;
                color: #ffffff;
                font-size: 11.5px;
            }

            .contact-item span {
                color: rgba(255, 255, 255, 0.76);
                font-size: 11px;
                line-height: 1.55;
            }

            .contact-form-box {
                padding: 52px 25px;
                border: 1px solid #e7ecf2;
                border-left: 0;
                border-radius: 0 13px 13px 0;
                background: #ffffff;
            }

            .contact-form-header {
                margin-bottom: 15px;
                text-align: center;
            }

            .contact-form-header h3 {
                margin: 0 0 3px;
                color: #073e73;
                font-size: 16px;
            }

            .contact-form-header p {
                margin: 0;
                color: #8a93a4;
                font-size: 11px;
            }

            .contact-form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px 11px;
            }

            .contact-field {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .contact-field.full {
                grid-column: 1 / -1;
            }

            .contact-field label {
                color: #35405a;
                font-size: 11px;
                font-weight: 600;
            }

            .contact-field input,
            .contact-field textarea {
                box-sizing: border-box;
                width: 100%;
                border: 1px solid #dfe5ec;
                border-radius: 7px;
                outline: none;
                color: #173f70;
                font-family: "Poppins", sans-serif;
                font-size: 11px;
                transition: 0.2s ease;
            }

            .contact-field input {
                height: 34px;
                padding: 0 9px;
            }

            .contact-field textarea {
                min-height: 72px;
                padding: 9px;
                resize: vertical;
            }

            .contact-field input:focus,
            .contact-field textarea:focus {
                border-color: #20a1b8;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.07);
            }

            .contact-field input::placeholder,
            .contact-field textarea::placeholder {
                color: #a4adba;
            }

            .contact-submit {
                margin-top: 12px;
                text-align: right;
            }

            .contact-submit .btn {
                min-width: 155px;
                height: 36px;
                border: 0;
                border-radius: 7px;
                background: #073e73;
                color: #ffffff;
                font-family: "Poppins", sans-serif;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.2s ease;
            }

            .contact-submit .btn:hover {
                background: linear-gradient(135deg, #20a1b8, #078fd2);
                transform: translateY(-1px);
            }

            .contact-cards-section {
                padding: 42px 20px 64px;
                background: linear-gradient(135deg, #062f5d, #073e73 52%, #1688a4);
            }

            .contact-cards-head {
                max-width: 560px;
                margin: 0 auto 22px;
                text-align: center;
            }

            .contact-cards-head span {
                color: #55dce0;
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .contact-cards-head h2 {
                margin: 5px 0;
                color: #ffffff;
                font-size: 20px;
            }

            .contact-cards-head p {
                margin: 0;
                color: rgba(255, 255, 255, 0.7);
                font-size: 11px;
            }

            .contact-cards {
                max-width: 880px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }

            .contact-card {
                min-height: 112px;
                padding: 17px;
                border: 1px solid rgba(255, 255, 255, 0.13);
                border-radius: 11px;
                background: rgba(255, 255, 255, 0.09);
                transition: 0.25s ease;
            }

            .contact-card:hover {
                transform: translateY(-4px);
                border-color: rgba(103, 215, 223, 0.38);
                background: rgba(255, 255, 255, 0.13);
            }

            .contact-card-icon {
                width: 34px;
                height: 34px;
                margin-bottom: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                background: rgba(103, 215, 223, 0.14);
                color: #55dce0;
                font-size: 13px;
            }

            .contact-card h3 {
                margin: 0 0 5px;
                color: #ffffff;
                font-size: 12.5px;
            }

            .contact-card p {
                margin: 0;
                color: rgba(255, 255, 255, 0.7);
                font-size: 11px;
                line-height: 1.6;
            }

            .footer {
                position: relative;
                overflow: hidden;
                border-top: 1px solid rgba(103, 215, 223, 0.08);
                padding: 43px 20px 20px;
                background: #173f70;
                color: #ffffff;
            }

            .footer::before {
                content: "";
                position: absolute;
                top: -120px;
                right: -80px;
                width: 310px;
                height: 220px;
                border-radius: 50%;
                background: rgba(63, 104, 174, 0.1);
            }

            .footer .container {
                position: relative;
                z-index: 2;
                max-width: 1160px;
                margin: 0 auto;
            }

            .footer-grid {
                display: grid;
                grid-template-columns: 1.3fr 0.85fr 0.85fr 1.1fr 1.05fr;
                gap: 32px;
                padding-bottom: 34px;
            }

            .footer-about .footer-logo img {
                width: 130px;
                margin-bottom: 13px;
            }

            .footer-about p {
                max-width: 225px;
                margin: 0 0 15px;
                color: rgba(255, 255, 255, 0.62);
                font-size: 11px;
                line-height: 1.65;
            }

            .footer .loc {
                display: flex;
                align-items: center;
                gap: 7px;
                color: rgba(255, 255, 255, 0.67);
                font-size: 11px;
            }

            .footer .loc i {
                color: #20a1b8;
            }

            .socials {
                display: flex;
                gap: 7px;
                margin-top: 16px;
            }

            .socials a {
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.03);
                color: #d7deea;
                font-size: 12px;
                text-decoration: none;
                transition: 0.2s ease;
            }

            .socials a:hover {
                background: #20a1b8;
                border-color: #20a1b8;
                color: #ffffff;
            }

            .footer-grid > div:not(.footer-about) h3 {
                margin: 3px 0 15px;
                color: #ffffff;
                font-size: 12.5px;
            }

            .footer-grid > div:not(.footer-about) > a {
                display: block;
                margin-bottom: 9px;
                color: rgba(255, 255, 255, 0.62);
                font-size: 11px;
                text-decoration: none;
            }

            .footer-grid > div:not(.footer-about) > a:hover {
                color: #55dce0;
            }

            .contact-line {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 9px;
                color: rgba(255, 255, 255, 0.64);
                font-size: 11px;
            }

            .contact-line i {
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 7px;
                background: rgba(32, 161, 184, 0.12);
                color: #20a1b8;
            }

            .contact-success {
                display: flex;
                align-items: center;
                gap: 12px;
                max-width: 880px;
                margin: 0 auto 18px;
                padding: 13px 16px;
                border: 1px solid #b9e8d0;
                border-radius: 10px;
                background: #f0fbf5;
                color: #176b45;
                box-shadow: 0 7px 18px rgba(24, 33, 63, 0.05);
                animation: successIn 0.35s ease both;
            }

            .contact-success-icon {
                width: 30px;
                height: 30px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: #21a366;
                color: #ffffff;
                font-size: 12.5px;
            }

            .contact-success strong {
                display: block;
                margin-bottom: 2px;
                color: #176b45;
                font-size: 11.5px;
                font-weight: 700;
            }

            .contact-success span {
                color: #4f7b66;
                font-size: 11px;
                line-height: 1.45;
            }

            @keyframes successIn {
                from {
                    opacity: 0;
                    transform: translateY(-6px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @media (max-width: 780px) {
                .contact-layout {
                    grid-template-columns: 1fr;
                    max-width: 620px;
                    margin: 0 auto;
                }

                .contact-info {
                    border-radius: 12px 12px 0 0;
                }

                .contact-form-box {
                    border-left: 1px solid #e7ecf2;
                    border-radius: 0 0 12px 12px;
                }

                .contact-cards {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 520px) {
                .contact-section {
                    padding: 32px 15px 38px;
                }

                .contact-form-box {
                    padding: 22px 18px;
                }

                .contact-form-grid {
                    grid-template-columns: 1fr;
                }

                .contact-field.full {
                    grid-column: auto;
                }

                .contact-submit {
                    text-align: center;
                }

                .contact-submit .btn {
                    width: 100%;
                }

                .footer-grid {
                    grid-template-columns: 1fr;
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

                        <a href="tarif.php" class="nav-link">Tarifs</a>
                        <a href="a-propos.php" class="nav-link">À propos</a>
                        <a href="contact.php" class="nav-link active">Contact</a>
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
                <img class="page-hero-img" src="images/fffff.jpg" alt="Contactez TOUN">
                <div class="page-hero-overlay"></div>
                <div class="page-hero-inner">
                    <div class="page-hero-copy">
                        <h1>Contactez-nous</h1>
                        <p>Une question sur TOUN ? Notre équipe est là pour vous répondre.</p>
                    </div>
                </div>
            </section>

            <section class="contact-section">

                <div class="contact-container">

                    <div class="contact-intro">

                        <h2>
                            Parlons de votre besoin
                        </h2>

                        <p>
                            Envoyez-nous votre message,
                            nous vous répondrons rapidement.
                        </p>

                    </div>

                    <?php if ($success): ?>
                        <div class="contact-success" role="status" aria-live="polite">
                            <div class="contact-success-icon">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <strong>Message envoyé avec succès !</strong>
                                <span>Merci pour votre message. Notre équipe vous répondra dans les meilleurs délais.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="contact-layout">

                        <aside class="contact-info reveal-left">

                            <h3>
                                Écrivez-nous
                            </h3>

                            <p>
                                Un seul canal pour nous contacter
                                simplement.
                            </p>

                            <div class="contact-item">

                                <div class="contact-icon">

                                    <i class="fa-solid fa-location-dot"></i>

                                </div>

                                <div>

                                    <strong>
                                        Nos bureaux
                                    </strong>

                                    <span>
                                        Côte d'Ivoire<br>
                                        Abidjan, Cocody
                                    </span>

                                </div>

                            </div>

                            <div class="contact-item">

                                <div class="contact-icon">

                                    <i class="fa-regular fa-envelope"></i>

                                </div>

                                <div>

                                    <strong>
                                        Email
                                    </strong>

                                    <span>
                                        contact@toun.com
                                    </span>

                                </div>

                            </div>

                            <div class="contact-item">

                                <div class="contact-icon">

                                    <i class="fa-solid fa-phone"></i>

                                </div>

                                <div>

                                    <strong>
                                        Téléphone
                                    </strong>

                                    <span>
                                        +225 01 02 30 59 52  </span>

                                </div>

                            </div>

                        </aside>

                        <div class="contact-form-box reveal-right">

                            <div class="contact-form-header">

                                <h3>
                                    Envoyer un message
                                </h3>

                                <p>
                                    Quelques informations suffisent.
                                </p>

                            </div>

                            <form
                                method="POST"
                                action="admin/contact_traitement.php"   >

                                <div class="contact-form-grid">

                                    <div class="contact-field">

                                        <label for="nom">
                                            Nom
                                        </label>

                                        <input
                                            id="nom"
                                            type="text"
                                            name="nom"
                                            placeholder="Votre nom"
                                            required
                                        >

                                    </div>

                                    <div class="contact-field">

                                        <label for="email">
                                            Email
                                        </label>

                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            placeholder="vous@exemple.com"
                                            required
                                        >

                                    </div>

                                    <div class="contact-field full">

                                        <label for="sujet">
                                            Sujet
                                        </label>

                                        <input
                                            id="sujet"
                                            type="text"
                                            name="sujet"
                                            placeholder="Objet de votre message"
                                            required
                                        >

                                    </div>

                                    <div class="contact-field full">

                                        <label for="message">
                                            Message
                                        </label>

                                        <textarea
                                            id="message"
                                            name="message"
                                            placeholder="Écrivez votre message..."
                                            required
                                        ></textarea>

                                    </div>

                                    <input
                                        type="hidden"
                                        name="prenom"
                                        value=""
                                    >

                                    <input
                                        type="hidden"
                                        name="telephone"
                                        value=""
                                    >

                                    <input
                                        type="hidden"
                                        name="profil"
                                        value=""
                                    >

                                </div>

                                <div class="contact-submit">

                                    <button
                                        type="submit"
                                        class="btn"
                                    >
                                        Envoyer le message

                                        <i class="fa-solid fa-paper-plane"></i>

                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </section>

            <section class="contact-cards-section">

                <div class="contact-cards-head reveal">

                    <span>
                        Besoin d'aide ?
                    </span>

                    <h2>
                        Nous sommes là pour vous
                    </h2>

                    <p>
                        Choisissez simplement le sujet
                        qui correspond à votre besoin.
                    </p>

                </div>

                <div class="contact-cards stagger">

                    <article class="contact-card">

                        <div class="contact-card-icon">

                            <i class="fa-solid fa-headset"></i>

                        </div>

                        <h3>
                            Assistance
                        </h3>

                        <p>
                            Un problème avec votre compte
                            ou l'accès à une formation ?
                        </p>

                    </article>

                    <article class="contact-card">

                        <div class="contact-card-icon">

                            <i class="fa-solid fa-building"></i>

                        </div>

                        <h3>
                            Entreprises
                        </h3>

                        <p>
                            Vous souhaitez former plusieurs
                            collaborateurs avec TOUN ?
                        </p>

                    </article>

                    <article class="contact-card">

                        <div class="contact-card-icon">

                            <i class="fa-solid fa-circle-question"></i>

                        </div>

                        <h3>
                            Informations
                        </h3>

                        <p>
                            Une question sur nos formations,
                            nos services ou notre fonctionnement ?
                        </p>

                    </article>

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

        <script
            src="assets/js/toun.js"
            defer
        ></script>

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
