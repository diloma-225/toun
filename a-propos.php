<?php
require_once __DIR__ . '/config/connexion.php';

$formateurs = [];

$nbApprenants = 0;
$nbFormateurs = 0;
$nbFormations = 0;

try {
    $sql = "
        SELECT
            u.id_user,
            u.nom_user,
            u.prenom_user,
            u.email_user,
            u.photo_user
        FROM t_formateurs f
        INNER JOIN t_utilisateurs u
            ON u.id_user = f.id_user
        WHERE u.statut_user = 'actif'
        ORDER BY u.id_user DESC
    ";

    $stmt = $con->prepare($sql);
    $stmt->execute();

    $formateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $formateurs = [];
}

try {
    $stmt = $con->query("
        SELECT COUNT(*)
        FROM t_utilisateurs
        WHERE role_user = 'apprenant'
    ");

    $nbApprenants = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    $nbApprenants = 0;
}

try {
    $stmt = $con->query("
        SELECT COUNT(*)
        FROM t_formateurs
    ");

    $nbFormateurs = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    $nbFormateurs = 0;
}

try {
    $stmt = $con->query("
        SELECT COUNT(*)
        FROM t_formations
    ");

    $nbFormations = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    $nbFormations = 0;
}

function trouverPhotoFormateur($photo)
{
    if (empty($photo)) {
        return null;
    }

    $photo = trim(str_replace('\\', '/', $photo));
    $photo = ltrim($photo, '/');

    $chemins = [
        $photo,

        'uploads/' . $photo,

        'uploads/photos/' . $photo,

        'uploads/profils/' . $photo,

        'images/' . $photo,

        'assets/uploads/' . $photo,

        'assets/images/' . $photo
    ];

    foreach ($chemins as $chemin) {
        if (is_file(__DIR__ . '/' . $chemin)) {
            return $chemin;
        }
    }

    return null;
}

$partenaires = [
    [
        'sigle' => 'P01',
        'nom' => 'Partenaire 01'
    ],

    [
        'sigle' => 'P02',
        'nom' => 'Partenaire 02'
    ],

    [
        'sigle' => 'P03',
        'nom' => 'Partenaire 03'
    ],

    [
        'sigle' => 'P04',
        'nom' => 'Partenaire 04'
    ],

    [
        'sigle' => 'P05',
        'nom' => 'Partenaire 05'
    ],

    [
        'sigle' => 'P06',
        'nom' => 'Partenaire 06'
    ]
];
?>

<!DOCTYPE html>

<html lang="fr">

    <head>

        <script>
            document.documentElement.classList.add('js');
        </script>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>À propos | TOUN</title>

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
        >

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        >

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
                color: #153f6c;
                background: #f4f9fc;
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

            .site-header a,
            .footer a {
                text-decoration: none;
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

            .section-tag {
                display: inline-block;
                color: #0786d7;
                font-size: 10.5px;
                font-weight: 700;
                letter-spacing: 1.5px;
            }

            .section-title h2 {
                margin: 8px 0 11px;
                color: #153f6c;
                font-size: 28px;
                line-height: 1.25;
            }

            .section-title h2 span {
                color: #0788d8;
            }

            .section-title p {
                color: #69819a;
                font-size: 11.5px;
                line-height: 1.8;
            }

            .presentation {
                padding: 60px 0;
                background: #fafdfe;
            }

            .presentation-grid {
                display: grid;
                grid-template-columns: 0.82fr 1.18fr;
                align-items: center;
                gap: 55px;
            }

            .presentation-image {
                position: relative;
            }

            .presentation-image > img {
                width: 100%;
                height: 320px;
                object-fit: cover;
                border-radius: 20px;
                box-shadow: 0 18px 40px rgba(0, 55, 105, 0.12);
            }

            .presentation-badge {
                position: absolute;
                right: -14px;
                bottom: -14px;
                width: 175px;
                padding: 15px;
                color: #fff;
                border-radius: 15px;
                background: linear-gradient(135deg, #0877ce, #0dc2d8);
                box-shadow: 0 12px 27px rgba(0, 85, 150, 0.2);
            }

            .presentation-badge strong {
                display: block;
                margin-bottom: 4px;
                font-size: 17px;
            }

            .presentation-badge span {
                font-size: 10.5px;
                line-height: 1.7;
            }

            .presentation-text {
                margin-top: 13px;
            }

            .presentation-text p {
                margin-bottom: 10px;
                color: #69819a;
                font-size: 11.5px;
                line-height: 1.8;
            }

            .check-list {
                margin-top: 17px;
            }

            .check-item {
                display: flex;
                align-items: center;
                gap: 9px;
                margin-bottom: 8px;
                color: #315879;
                font-size: 11px;
            }

            .check-item i {
                width: 21px;
                height: 21px;
                display: grid;
                place-items: center;
                flex-shrink: 0;
                color: #0786d7;
                background: #dcf6fa;
                border-radius: 50%;
                font-size: 10.5px;
            }

            .identity {
                padding: 60px 0;
                background:
                    radial-gradient(circle at 10% 20%, rgba(39, 225, 240, 0.13), transparent 25%),
                    linear-gradient(120deg, #062a4b, #0a5a8e);
            }

            .identity-header {
                max-width: 720px;
                margin: auto;
                text-align: center;
            }

            .identity-header h2,
            .identity-header h2 span {
                color: #fff;
            }

            .identity-header p {
                color: #c8ddf1;
            }

            .identity-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 14px;
                margin-top: 30px;
            }

            .identity-card {
                min-height: 175px;
                padding: 20px 17px;
                background: rgba(255, 255, 255, 0.075);
                border: 1px solid rgba(255, 255, 255, 0.13);
                border-radius: 16px;
                transition: 0.3s;
            }

            .identity-card:hover {
                transform: translateY(-5px);
                background: rgba(255, 255, 255, 0.12);
            }

            .identity-icon {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                margin-bottom: 13px;
                color: #fff;
                border-radius: 12px;
                background: linear-gradient(135deg, #18d5e8, #0787d7);
            }

            .identity-card h3 {
                margin-bottom: 7px;
                color: #fff;
                font-size: 13px;
            }

            .identity-card p {
                color: #c9def1;
                font-size: 11px;
                line-height: 1.75;
            }

            .stats {
                padding: 58px 0 63px;
                background: #eaf7fb;
            }

            .stats-header {
                display: flex;
                align-items: end;
                justify-content: space-between;
                gap: 30px;
                margin-bottom: 40px;
            }

            .stats-header > p {
                max-width: 370px;
                color: #64809b;
                font-size: 11px;
                line-height: 1.8;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 30px;
            }

            .stat {
                text-align: center;
            }

            .stat-circle {
                width: 78px;
                height: 78px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 14px;
                color: #fff;
                border-radius: 50%;
                background: linear-gradient(145deg, #078fd2, #17cddd);
                box-shadow:
                    0 0 0 7px rgba(255, 255, 255, 0.72),
                    0 12px 25px rgba(0, 100, 175, 0.17);
                animation: statPulse 3s ease-in-out infinite;
            }

            .stat-circle::after {
                content: "";
                position: absolute;
                inset: -11px;
                border: 1px dashed rgba(7, 135, 216, 0.28);
                border-radius: 50%;
            }

            .stat-circle i {
                font-size: 21px;
            }

            .stat-number {
                color: #075baa;
                font-size: 30px;
                font-weight: 800;
                line-height: 1;
                margin-bottom: 6px;
            }

            .stat-label {
                color: #607d98;
                font-size: 11px;
            }

            @keyframes statPulse {
                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.05);
                }
            }

            .formateurs {
                padding: 62px 0 72px;
                background:
                    radial-gradient(circle at 90% 15%, rgba(45, 225, 239, 0.16), transparent 25%),
                    linear-gradient(120deg, #062a4b, #0a5a8e);
            }

            .formateurs-header {
                max-width: 700px;
                margin: auto;
                text-align: center;
            }

            .formateurs-header h2 {
                margin-top: 8px;
                color: #fff;
                font-size: 28px;
            }

            .formateurs-header h2 span {
                color: #fff;
            }

            .formateurs-header p {
                margin-top: 10px;
                color: #c8def1;
                font-size: 11.5px;
                line-height: 1.8;
            }

            .formateurs-grid {
                display: flex;
                justify-content: center;
                align-items: stretch;
                gap: 30px;
                margin-top: 72px;
            }

            .formateur-card {
                position: relative;
                width: 315px;
                padding-top: 78px;
                background: #fbfdfe;
                border-radius: 20px;
                box-shadow: 0 18px 40px rgba(0, 25, 65, 0.22);
                transition: 0.3s;
            }

            .formateur-card:hover {
                transform: translateY(-7px);
                box-shadow: 0 25px 45px rgba(0, 25, 65, 0.3);
            }

            .formateur-photo {
                position: absolute;
                top: -58px;
                left: 50%;
                width: 158px;
                height: 158px;
                transform: translateX(-50%);
                overflow: hidden;
                border: 6px solid #fff;
                border-radius: 50%;
                background: #e7f7fb;
                box-shadow: 0 14px 30px rgba(0, 45, 90, 0.25);
                z-index: 2;
            }

            .formateur-photo img {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
                object-position: center 18%;
            }

            .formateur-avatar {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 30px;
                font-weight: 700;
                background: linear-gradient(135deg, #078fd2, #19d1df);
            }

            .formateur-info {
                padding: 18px 23px 23px;
            }

            .formateur-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 8px;
            }

            .formateur-role {
                color: #0786d7;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 1.2px;
            }

            .formateur-status {
                display: inline-flex;
                align-items: center;
                flex-shrink: 0;
                gap: 5px;
                padding: 5px 9px;
                color: #16835e;
                font-size: 10px;
                font-weight: 600;
                background: #fff;
                border: 1px solid #e0eee9;
                border-radius: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
            }

            .formateur-status span {
                width: 6px;
                height: 6px;
                display: inline-block;
                border-radius: 50%;
                background: #20bd7d;
            }

            .formateur-info h3 {
                margin: 0 0 7px;
                color: #153f6c;
                font-size: 17px;
                font-weight: 700;
            }

            .formateur-info p {
                margin: 0;
                color: #71859d;
                font-size: 11px;
                line-height: 1.75;
            }

            .formateur-link {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-top: 14px;
                color: #0784d7;
                font-size: 11px;
                font-weight: 600;
            }

            .formateur-link i {
                transition: 0.25s;
            }

            .formateur-link:hover i {
                transform: translateX(4px);
            }

            .formateurs-empty {
                max-width: 450px;
                margin: 45px auto 0;
                padding: 30px;
                text-align: center;
                color: #fff;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.14);
                border-radius: 17px;
            }

            .formateurs-empty i {
                margin-bottom: 10px;
                color: #3bddec;
                font-size: 26px;
            }

            .formateurs-empty h3 {
                margin-bottom: 5px;
                font-size: 14px;
            }

            .formateurs-empty p {
                color: #c8def1;
                font-size: 11px;
            }

            .partners {
                padding: 60px 0;
                overflow: hidden;
                background: #fafdfe;
            }

            .partners-header {
                max-width: 720px;
                margin: auto;
                text-align: center;
            }

            .partners-slider {
                width: 100%;
                overflow: hidden;
                margin-top: 32px;
            }

            .partners-track {
                display: flex;
                width: max-content;
                animation: partnersMove 25s linear infinite;
            }

            .partner {
                width: 185px;
                height: 75px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 9px;
                margin-right: 14px;
                background: #eef8fb;
                border: 1px solid #dcecf2;
                border-radius: 14px;
            }

            .partner-logo {
                width: 39px;
                height: 39px;
                display: grid;
                place-items: center;
                color: #0785d7;
                background: #d9f5fa;
                border-radius: 10px;
                font-size: 11.5px;
                font-weight: 800;
            }

            .partner-name {
                color: #173f6b;
                font-size: 11px;
                font-weight: 600;
            }

            @keyframes partnersMove {
                from {
                    transform: translateX(0);
                }

                to {
                    transform: translateX(-50%);
                }
            }

            .cta {
                padding: 55px 0;
                background: #eaf7fb;
            }

            .cta-box {
                min-height: 235px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 35px;
                padding: 38px 45px;
                overflow: hidden;
                border-radius: 22px;
                background:
                    radial-gradient(circle at 90% 20%, rgba(45, 225, 239, 0.22), transparent 27%),
                    linear-gradient(120deg, #062d68, #087bd2);
            }

            .cta-content {
                max-width: 650px;
            }

            .cta-content h2 {
                margin: 8px 0 10px;
                color: #fff;
                font-size: 28px;
                line-height: 1.25;
            }

            .cta-content h2 span {
                color: #fff;
            }

            .cta-content p {
                max-width: 500px;
                margin-bottom: 18px;
                color: #cce2f5;
                font-size: 11px;
                line-height: 1.8;
            }

            .cta-actions {
                display: flex;
                gap: 9px;
            }

            .cta-btn {
                padding: 11px 18px;
                border-radius: 23px;
                font-size: 11px;
                font-weight: 600;
            }

            .cta-btn.primary {
                color: #075cae;
                background: #fff;
            }

            .cta-btn.secondary {
                color: #fff;
                border: 1px solid rgba(255, 255, 255, 0.45);
            }

            .cta-symbol {
                width: 140px;
                height: 140px;
                flex-shrink: 0;
                display: grid;
                place-items: center;
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.07);
            }

            .cta-symbol i {
                color: #3bddec;
                font-size: 50px;
            }

            @media (max-width: 1000px) {
                .presentation-grid {
                    grid-template-columns: 1fr;
                    gap: 45px;
                }

                .identity-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .stats-header {
                    display: block;
                }

                .stats-header > p {
                    margin-top: 10px;
                }

                .formateurs-grid {
                    flex-wrap: wrap;
                }

                .cta-box {
                    flex-direction: column;
                    text-align: center;
                }

                .cta-content p {
                    margin-left: auto;
                    margin-right: auto;
                }

                .cta-actions {
                    justify-content: center;
                }
            }

            @media (max-width: 600px) {
                .presentation,
                .identity,
                .stats,
                .formateurs,
                .partners,
                .cta {
                    padding: 50px 0;
                }

                .presentation-image > img {
                    height: 280px;
                }

                .presentation-badge {
                    right: 8px;
                }

                .identity-grid,
                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .identity-card {
                    min-height: auto;
                }

                .formateurs-grid {
                    margin-top: 65px;
                }

                .formateur-card {
                    width: min(100%, 320px);
                }

                .formateur-photo {
                    width: 145px;
                    height: 145px;
                    top: -52px;
                }

                .formateur-top {
                    align-items: flex-start;
                }

                .formateur-role {
                    max-width: 150px;
                }

                .cta-box {
                    padding: 32px 20px;
                }

                .cta-content h2 {
                    font-size: 25px;
                }

                .cta-actions {
                    flex-direction: column;
                }
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
        </style>

        <link rel="stylesheet" href="assets/css/page-hero.css">
    </head>

    <body>

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
                        <a href="a-propos.php" class="nav-link active">À propos</a>
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
                <img class="page-hero-img contain" src="images/4.png" alt="Formation professionnelle TOUN">
                <div class="page-hero-overlay"></div>
                <div class="page-hero-inner">
                    <div class="page-hero-copy">
                        <h1>Des compétences <strong>pour construire demain.</strong></h1>
                        <p>TOUN est une plateforme de formation professionnelle pensée pour accompagner les professionnels et les entreprises dans le développement de leurs compétences.</p>
                        <div class="page-hero-actions">
                            <a href="formation.php" class="page-hero-btn">Découvrir les formations <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="contact.php" class="page-hero-btn light">Nous contacter</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="presentation">

                <div class="container presentation-grid">

                    <div class="presentation-image reveal-left">

                        <img
                            src="images/4.jpg"
                            alt="Formation professionnelle">

                    </div>

                    <div class="reveal-right">

                        <div class="section-title">

                            <span class="section-tag">
                                QUI SOMMES-NOUS ?
                            </span>

                            <h2>

                                Une plateforme pensée pour ceux qui
                                <span>veulent progresser.</span>

                            </h2>

                        </div>

                        <div class="presentation-text">

                            <p>

                                TOUN est née d'un constat simple :
                                se former ne devrait pas être compliqué.

                            </p>

                            <p>

                                Dans un environnement professionnel en constante évolution,
                                les entreprises et les professionnels doivent pouvoir
                                développer leurs compétences facilement.

                            </p>

                            <p>

                                TOUN propose une expérience de formation en ligne simple,
                                flexible et adaptée aux réalités professionnelles.

                            </p>

                        </div>

                        <div class="check-list">

                            <div class="check-item">

                                <i class="fa-solid fa-check"></i>

                                Formations adaptées aux besoins professionnels

                            </div>

                            <div class="check-item">

                                <i class="fa-solid fa-check"></i>

                                Apprentissage accessible en ligne

                            </div>

                            <div class="check-item">

                                <i class="fa-solid fa-check"></i>

                                Progression suivie et certification

                            </div>

                            <div class="check-item">

                                <i class="fa-solid fa-check"></i>

                                Accompagnement des professionnels et entreprises

                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="identity">

                <div class="container">

                    <div class="section-title identity-header reveal">

                        <span class="section-tag">
                            NOTRE IDENTITÉ
                        </span>

                        <h2>

                            Ce qui fait la force de
                            <span>TOUN.</span>

                        </h2>

                        <p>

                            Notre démarche place l'apprenant,
                            l'entreprise et le développement des compétences
                            au centre de la plateforme.

                        </p>

                    </div>

                    <div class="identity-grid stagger">

                        <article class="identity-card">

                            <div class="identity-icon">

                                <i class="fa-solid fa-bullseye"></i>

                            </div>

                            <h3>
                                Notre mission
                            </h3>

                            <p>

                                Rendre la formation professionnelle
                                plus accessible et permettre aux professionnels
                                de développer leurs compétences.

                            </p>

                        </article>

                        <article class="identity-card">

                            <div class="identity-icon">

                                <i class="fa-solid fa-eye"></i>

                            </div>

                            <h3>
                                Notre vision
                            </h3>

                            <p>

                                Construire un environnement professionnel
                                capable d'évoluer grâce à l'apprentissage
                                et au développement continu.

                            </p>

                        </article>

                        <article class="identity-card">

                            <div class="identity-icon">

                                <i class="fa-solid fa-lightbulb"></i>

                            </div>

                            <h3>
                                Notre approche
                            </h3>

                            <p>

                                Proposer des parcours simples,
                                pratiques et flexibles selon les objectifs
                                et les disponibilités de chacun.

                            </p>

                        </article>

                        <article class="identity-card">

                            <div class="identity-icon">

                                <i class="fa-solid fa-heart"></i>

                            </div>

                            <h3>
                                Nos valeurs
                            </h3>

                            <p>

                                Excellence, innovation, accessibilité,
                                engagement et accompagnement sont au cœur
                                de notre démarche.

                            </p>

                        </article>

                    </div>

                </div>

            </section>

            <section class="stats">

                <div class="container">

                    <div class="stats-header reveal">

                        <div class="section-title">

                            <span class="section-tag">
                                TOUN EN QUELQUES REPÈRES
                            </span>

                            <h2>

                                Une plateforme construite autour de
                                <span>l'apprentissage.</span>

                            </h2>

                        </div>

                        <p>

                            Les données affichées sont alimentées directement
                            par les informations disponibles sur la plateforme TOUN.

                        </p>

                    </div>

                    <div class="stats-grid stagger">

                        <div class="stat">

                            <div class="stat-circle">

                                <i class="fa-solid fa-user-graduate"></i>

                            </div>

                            <div
                                class="stat-number"
                                data-target="<?= $nbApprenants ?>"
                            >
                                0
                            </div>

                            <div class="stat-label">
                                Apprenants inscrits
                            </div>

                        </div>

                        <div class="stat">

                            <div class="stat-circle">

                                <i class="fa-solid fa-chalkboard-user"></i>

                            </div>

                            <div
                                class="stat-number"
                                data-target="<?= $nbFormateurs ?>"
                            >
                                0
                            </div>

                            <div class="stat-label">
                                Formateurs
                            </div>

                        </div>

                        <div class="stat">

                            <div class="stat-circle">

                                <i class="fa-solid fa-book-open"></i>

                            </div>

                            <div
                                class="stat-number"
                                data-target="<?= $nbFormations ?>"
                            >
                                0
                            </div>

                            <div class="stat-label">
                                Formations
                            </div>

                        </div>

                        <div class="stat">

                            <div class="stat-circle">

                                <i class="fa-solid fa-laptop"></i>

                            </div>

                            <div class="stat-number">
                                24/7
                            </div>

                            <div class="stat-label">
                                Accessible en ligne
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            <section class="formateurs">

                <div class="container">

                    <div class="formateurs-header reveal">

                        <span class="section-tag">
                            NOTRE ÉQUIPE PÉDAGOGIQUE
                        </span>

                        <h2>

                            Nos
                            <span>formateurs.</span>

                        </h2>

                        <p>

                            Découvrez les professionnels qui contribuent
                            au développement des compétences sur TOUN.

                        </p>

                    </div>

                    <?php if (!empty($formateurs)): ?>

                        <div class="formateurs-grid stagger">

                            <?php foreach ($formateurs as $formateur): ?>

                                <?php
$prenom = trim($formateur['prenom_user'] ?? '');

$nom = trim($formateur['nom_user'] ?? '');

$nomComplet = trim($prenom . ' ' . $nom);

$initiales = strtoupper(substr($prenom, 0, 1)) . strtoupper(substr($nom, 0, 1));

$photo = trouverPhotoFormateur($formateur['photo_user'] ?? '');
?>

                                <article class="formateur-card">

                                    <div class="formateur-photo">

                                        <?php if ($photo): ?>

                                            <img
                                                src="<?= htmlspecialchars($photo) ?>"
                                                alt="<?= htmlspecialchars($nomComplet) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="formateur-avatar">

                                                <?= htmlspecialchars($initiales ?: 'TO') ?>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <div class="formateur-info">

                                        <div class="formateur-top">

                                            <span class="formateur-role">
                                                ÉQUIPE PÉDAGOGIQUE
                                            </span>

                                            <span class="formateur-status">

                                                <span></span>

                                                Formateur

                                            </span>

                                        </div>

                                        <h3>

                                            <?= htmlspecialchars($nomComplet ?: 'Formateur TOUN') ?>

                                        </h3>

                                        <p>

                                            Professionnel intervenant sur la
                                            plateforme TOUN pour partager son
                                            expertise et accompagner les apprenants.

                                        </p>

                                        <a
                                            href="contact.php"
                                            class="formateur-link"
                                        >

                                            Contacter

                                            <i class="fa-solid fa-arrow-right"></i>

                                        </a>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="formateurs-empty">

                            <i class="fa-solid fa-chalkboard-user"></i>

                            <h3>
                                Aucun formateur à afficher
                            </h3>

                            <p>

                                Les formateurs enregistrés sur TOUN
                                apparaîtront automatiquement ici.

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <section class="partners">

                <div class="container">

                    <div class="partners-header reveal">

                        <span class="section-tag">
                            NOS PARTENAIRES
                        </span>

                        <div class="section-title">

                            <h2>

                                Ils nous accompagnent
                                <span>dans notre développement.</span>

                            </h2>

                            <p>

                                TOUN s'inscrit dans une dynamique de collaboration
                                et de développement des compétences.

                            </p>

                        </div>

                    </div>

                </div>

                <div class="partners-slider">

                    <div class="partners-track">

                        <?php foreach (array_merge($partenaires, $partenaires) as $partenaire): ?>

                            <div class="partner">

                                <div class="partner-logo">

                                    <?= htmlspecialchars($partenaire['sigle']) ?>

                                </div>

                                <span class="partner-name">

                                    <?= htmlspecialchars($partenaire['nom']) ?>

                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </section>

            <section class="cta">

                <div class="container">

                    <div class="cta-box reveal-zoom">

                        <div class="cta-content">

                            <span class="section-tag">
                                PRÊT À COMMENCER ?
                            </span>

                            <h2>

                                Faites évoluer vos compétences
                                avec <span>TOUN.</span>

                            </h2>

                            <p>

                                Explorez notre catalogue de formations
                                et trouvez le parcours adapté à vos objectifs
                                professionnels.

                            </p>

                            <div class="cta-actions">

                                <a
                                    href="formation.php"
                                    class="cta-btn primary"
                                >

                                    Voir les formations

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                                <a
                                    href="contact.php"
                                    class="cta-btn secondary"
                                >

                                    Échanger avec nous

                                </a>

                            </div>

                        </div>

                        <div class="cta-symbol">

                            <i class="fa-solid fa-graduation-cap"></i>

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
            document.addEventListener('DOMContentLoaded', function () {
                const counters = document.querySelectorAll('.stat-number[data-target]');

                counters.forEach(function (counter) {
                    const target = parseInt(counter.dataset.target, 10) || 0;

                    const duration = 1300;

                    const start = performance.now();

                    function update(current) {
                        const progress = Math.min((current - start) / duration, 1);

                        const easing = 1 - Math.pow(1 - progress, 3);

                        counter.textContent = Math.floor(target * easing);

                        if (progress < 1) {
                            requestAnimationFrame(update);
                        } else {
                            counter.textContent = target;
                        }
                    }

                    requestAnimationFrame(update);
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