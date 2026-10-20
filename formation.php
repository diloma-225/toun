<?php
session_start();
require_once 'config/connexion.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formationImage(?string $image): string
{
    $image = trim((string) $image);

    if ($image === '') {
        return 'images/formation-hero.jpg';
    }

    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    $name = basename(str_replace('\\', '/', $image));

    foreach (['uploads/formations/' . $name, 'uploads/' . $name, 'images/' . $name] as $path) {
        if (is_file(__DIR__ . '/' . $path)) {
            return $path;
        }
    }

    return 'images/formation-hero.jpg';
}

function formateurImage(?string $photo): string
{
    $photo = trim((string) $photo);

    if ($photo === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $photo)) {
        return $photo;
    }

    $name = basename(str_replace('\\', '/', $photo));

    foreach (
        [
            'uploads/photos/' . $name,
            'uploads/profils/' . $name,
            'uploads/' . $name,
            'images/profils/' . $name,
            'images/' . $name,
            'assets/uploads/' . $name
        ]
        as $path
    ) {
        if (is_file(__DIR__ . '/' . $path)) {
            return $path;
        }
    }

    return '';
}

$search = trim($_GET['q'] ?? '');
$cat = (int) ($_GET['cat'] ?? 0);
$niveau = trim($_GET['niveau'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

$categories = $con
    ->query(
        "
    SELECT id_cat, nom_cat
    FROM t_categories
    ORDER BY nom_cat ASC
"
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$where = ["f.statut_form = 'publie'"];
$params = [];

if ($search !== '') {
    $where[] = '(f.titre_form LIKE :q OR f.descrip_form LIKE :q OR c.nom_cat LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}

if ($cat > 0) {
    $where[] = 'f.id_cat = :cat';
    $params[':cat'] = $cat;
}

if ($niveau !== '') {
    $where[] = 'f.niveau_form = :niveau';
    $params[':niveau'] = $niveau;
}

switch ($sort) {
    case 'popular':
        $order = 'f.id_form DESC';
        break;
    case 'price_asc':
        $order = 'f.prix_form ASC';
        break;
    case 'price_desc':
        $order = 'f.prix_form DESC';
        break;
    default:
        $order = 'f.date_creation_form DESC';
        break;
}

$sql =
    "
    SELECT
        f.id_form,
        f.titre_form,
        f.descrip_form,
        f.img_form,
        f.niveau_form,
        f.prix_form,
        f.duree_form,
        f.date_creation_form,
        c.nom_cat,
        u.nom_user,
        u.prenom_user,
        u.photo_user
    FROM t_formations f
    LEFT JOIN t_categories c ON c.id_cat = f.id_cat
    LEFT JOIN t_formateurs fo ON fo.id_formateur = f.id_formateur
    LEFT JOIN t_utilisateurs u ON u.id_user = fo.id_user
    WHERE " .
    implode(' AND ', $where) .
    "
    ORDER BY $order
";

$stmt = $con->prepare($sql);
$stmt->execute($params);
$formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalFormations = (int) $con
    ->query(
        "
    SELECT COUNT(*) FROM t_formations WHERE statut_form = 'publie'
"
    )
    ->fetchColumn();

$totalFormateurs = (int) $con
    ->query(
        "
    SELECT COUNT(*) FROM t_formateurs WHERE statut_formateur = 'actif'
"
    )
    ->fetchColumn();

$totalCategories = count($categories);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Formations | TOUN</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap");

            * {
                box-sizing: border-box;
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                margin: 0;
                background: #f4f8fb;
                color: #10294d;
                font-family: "Poppins", Arial, sans-serif;
                overflow-x: hidden;
            }

            body,
            a,
            button,
            input,
            select {
                -webkit-tap-highlight-color: transparent;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            button,
            input,
            select {
                font: inherit;
            }

            img {
                max-width: 100%;
            }

            .container {
                width: min(1160px, 90%);
                margin: 0 auto;
            }

            .catalogue {
                position: relative;
                padding: 56px 0 48px;
                background:
                    radial-gradient(circle at 7% 8%, rgba(32, 161, 184, 0.1), transparent 25%),
                    linear-gradient(180deg, #e8f6fb 0%, #f5fafc 54%, #e9f5f9 100%);
            }

            .catalogue::before {
                content: "";
                position: absolute;
                left: -100px;
                bottom: 80px;
                width: 260px;
                height: 260px;
                border-radius: 50%;
                background: rgba(32, 161, 184, 0.05);
                filter: blur(5px);
                pointer-events: none;
            }

            .section-title {
                position: relative;
                z-index: 1;
                margin: 0 auto 25px;
                text-align: center;
            }

            .section-title small {
                display: block;
                margin-bottom: 7px;
                color: #087eb8;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 2px;
            }

            .section-title h2 {
                margin: 0;
                color: #123f78;
                font-size: 28px;
                line-height: 1.18;
                letter-spacing: -0.5px;
            }

            .section-title h2 span {
                color: #20a1b8;
            }

            .section-title p {
                max-width: 530px;
                margin: 8px auto 0;
                color: #8291a3;
                font-size: 11px;
                line-height: 1.7;
            }

            .domain-list {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
                margin: 0 0 27px;
            }

            .domain {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                min-height: 34px;
                padding: 0 12px;
                border: 1px solid #d3e7ee;
                border-radius: 20px;
                color: #5d7388;
                background: rgba(255, 255, 255, 0.76);
                font-size: 10.5px;
                font-weight: 600;
                box-shadow: 0 5px 15px rgba(17, 62, 91, 0.035);
                transition:
                    transform 0.25s ease,
                    border-color 0.25s ease,
                    color 0.25s ease,
                    background 0.25s ease,
                    box-shadow 0.25s ease;
            }

            .domain:hover {
                transform: translateY(-3px);
                color: #087eb8;
                border-color: #a9d8e1;
                background: #fff;
                box-shadow: 0 9px 20px rgba(17, 62, 91, 0.08);
            }

            .domain.active {
                color: #fff;
                border-color: transparent;
                background: linear-gradient(135deg, #087eb8, #20a1b8);
                box-shadow: 0 8px 18px rgba(8, 126, 184, 0.18);
            }

            .catalogue-layout {
                display: grid;
                grid-template-columns: 220px minmax(0, 1fr);
                gap: 22px;
                align-items: start;
            }

            .filters {
                position: sticky;
                top: 96px;
                padding: 17px;
                border: 1px solid #d5e7ed;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.92);
                box-shadow: 0 12px 28px rgba(17, 62, 91, 0.065);
                backdrop-filter: blur(10px);
            }

            .filters form {
                margin: 0;
            }

            .filters-title {
                margin: 0 0 14px;
                color: #123f78;
                font-size: 12px;
                line-height: 1.35;
            }

            .filter-search {
                position: relative;
                margin-bottom: 16px;
            }

            .filter-search i {
                position: absolute;
                left: 11px;
                top: 50%;
                transform: translateY(-50%);
                color: #8fa2b2;
                font-size: 10.5px;
            }

            .filter-search input {
                width: 100%;
                height: 35px;
                padding: 0 10px 0 29px;
                border: 1px solid #dceaf0;
                border-radius: 10px;
                outline: none;
                color: #34536e;
                background: #f8fcfd;
                font-size: 10.5px;
                transition:
                    border-color 0.2s ease,
                    box-shadow 0.2s ease,
                    background 0.2s ease;
            }

            .filter-search input:focus {
                border-color: #8fd0da;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
            }

            .filter-group {
                padding-top: 14px;
                margin-top: 14px;
                border-top: 1px solid #e5eff3;
            }

            .filter-group h3 {
                margin: 0 0 9px;
                color: #234a6f;
                font-size: 10.5px;
                font-weight: 800;
            }

            .check {
                display: flex;
                align-items: center;
                gap: 7px;
                margin: 8px 0;
                color: #73879a;
                font-size: 10px;
                line-height: 1.3;
                cursor: pointer;
            }

            .check input {
                width: 11px;
                height: 11px;
                margin: 0;
                accent-color: #0b8fb0;
            }

            .filter-button {
                width: 100%;
                height: 35px;
                margin-top: 15px;
                border: 0;
                border-radius: 10px;
                color: #fff;
                background: linear-gradient(135deg, #087eb8, #20a1b8);
                font-size: 10.5px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 8px 17px rgba(8, 126, 184, 0.16);
                transition:
                    transform 0.25s ease,
                    box-shadow 0.25s ease;
            }

            .filter-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 11px 22px rgba(8, 126, 184, 0.22);
            }

            .catalogue-main {
                min-width: 0;
            }

            .results-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
                margin-bottom: 13px;
            }

            .results-top strong {
                color: #123f78;
                font-size: 14px;
            }

            .results-top span {
                margin-left: 4px;
                color: #8495a5;
                font-size: 10.5px;
            }

            .sort {
                min-width: 145px;
                height: 34px;
                padding: 0 10px;
                border: 1px solid #d4e6ec;
                border-radius: 10px;
                outline: none;
                color: #506a80;
                background: #fff;
                font-size: 10px;
                cursor: pointer;
            }

            .formation-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 15px;
            }

            .formation-card {
                overflow: hidden;
                min-width: 0;
                border: 1px solid #d4e6ec;
                border-radius: 16px;
                background: linear-gradient(180deg, #fff 0%, #f9fcfd 100%);
                box-shadow: 0 10px 24px rgba(19, 64, 96, 0.065);
                transition:
                    transform 0.3s ease,
                    box-shadow 0.3s ease,
                    border-color 0.3s ease;
            }

            .formation-card:hover {
                transform: translateY(-7px);
                border-color: #a8d5de;
                box-shadow: 0 19px 38px rgba(19, 64, 96, 0.13);
            }

            .formation-image {
                position: relative;
                height: 132px;
                overflow: hidden;
                background: #ddeef3;
            }

            .formation-image::after {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(6, 42, 75, 0.02), rgba(6, 42, 75, 0.12));
                pointer-events: none;
            }

            .formation-image img {
                width: 100%;
                height: 100%;
                display: block;
                object-fit: cover;
                transition: transform 0.55s ease;
            }

            .formation-card:hover .formation-image img {
                transform: scale(1.06);
            }

            .formation-favorite {
                position: absolute;
                z-index: 2;
                top: 9px;
                right: 9px;
                width: 27px;
                height: 27px;
                display: grid;
                place-items: center;
                border-radius: 50%;
                color: #557188;
                background: rgba(255, 255, 255, 0.92);
                box-shadow: 0 5px 12px rgba(10, 40, 65, 0.12);
                font-size: 11px;
            }

            .badge {
                position: absolute;
                z-index: 2;
                left: 9px;
                top: 9px;
                padding: 5px 8px;
                border-radius: 10px;
                color: #fff;
                background: #20a1b8;
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .formation-body {
                padding: 12px;
            }

            .formation-category {
                display: inline-flex;
                align-items: center;
                min-height: 21px;
                padding: 0 7px;
                border: 1px solid #cdecf1;
                border-radius: 8px;
                color: #14729b;
                background: #eaf8fb;
                font-size: 10px;
                font-weight: 800;
            }

            .formation-body h3 {
                min-height: 28px;
                margin: 7px 0 7px;
                color: #153f70;
                font-size: 11.5px;
                line-height: 1.35;
            }

            .trainer-box {
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 6px 0;
                border-top: 1px solid #edf3f6;
                border-bottom: 1px solid #edf3f6;
            }

            .trainer-photo {
                width: 29px;
                height: 29px;
                flex: 0 0 29px;
                display: grid;
                place-items: center;
                overflow: hidden;
                border-radius: 50%;
                color: #087eb8;
                background: #e7f7fa;
                font-size: 10px;
                font-weight: 800;
            }

            .trainer-photo img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .trainer-info {
                min-width: 0;
            }

            .trainer-label,
            .trainer-name {
                display: block;
            }

            .trainer-label {
                margin-bottom: 2px;
                color: #9aa9b6;
                font-size: 9.5px;
            }

            .trainer-name {
                overflow: hidden;
                color: #365670;
                font-size: 10px;
                font-weight: 700;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .rating {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 7px 0;
                color: #20a1b8;
                font-size: 10px;
            }

            .rating span {
                color: #95a4b1;
                font-size: 10px;
            }

            .meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 9px;
                color: #8193a3;
                font-size: 10px;
            }

            .meta span {
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }

            .meta i {
                color: #20a1b8;
            }

            .price-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .price {
                color: #0b70b5;
                font-size: 12.5px;
            }

            .view {
                width: 30px;
                height: 30px;
                display: grid;
                place-items: center;
                border-radius: 10px;
                color: #fff;
                background: linear-gradient(135deg, #087eb8, #20a1b8);
                font-size: 10.5px;
                box-shadow: 0 7px 14px rgba(8, 126, 184, 0.16);
                transition:
                    transform 0.25s ease,
                    box-shadow 0.25s ease;
            }

            .view:hover {
                transform: translateX(3px);
                box-shadow: 0 10px 20px rgba(8, 126, 184, 0.23);
            }

            .empty {
                min-height: 220px;
                display: grid;
                place-items: center;
                align-content: center;
                padding: 25px;
                border: 1px dashed #bfdce5;
                border-radius: 16px;
                color: #7b8e9e;
                background: rgba(255, 255, 255, 0.78);
                text-align: center;
                font-size: 10.5px;
            }

            .empty i {
                margin-bottom: 6px;
                color: #20a1b8;
                font-size: 25px;
            }

            .stats {
                padding: 34px 0;
                background: linear-gradient(110deg, #082f66 0%, #0b4f86 52%, #109db0 100%);
                overflow: hidden;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0;
            }

            .stat {
                position: relative;
                min-height: 92px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 8px 18px;
                color: #fff;
                transition: transform 0.3s ease;
            }

            .stat:hover {
                transform: translateY(-4px);
            }

            .stat-icon {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                margin-bottom: 8px;
                color: #72e3e5;
                font-size: 13px;
                opacity: 0.95;
                transition:
                    transform 0.3s ease,
                    opacity 0.3s ease;
            }

            .stat:hover .stat-icon {
                transform: translateY(-2px) scale(1.08);
                opacity: 1;
            }

            .stat-number {
                display: block;
                color: #fff;
                font-size: 27px;
                line-height: 1;
                font-weight: 800;
                letter-spacing: -0.8px;
            }

            .stat-label {
                display: block;
                margin-top: 6px;
                color: rgba(255, 255, 255, 0.68);
                font-size: 10px;
                line-height: 1.3;
                letter-spacing: 0.15px;
            }

            .why {
                padding: 52px 0 48px;
                background: #f7fbfd;
                overflow: hidden;
            }

            .why-inner {
                width: min(1160px, 90%);
                margin: 0 auto;
                display: grid;
                grid-template-columns: 31% 69%;
                gap: 38px;
                align-items: center;
            }

            .why-heading {
                position: relative;
                padding: 0;
            }

            .why-heading h2 {
                margin: 8px 0 12px;
                max-width: 340px;
                color: #123f78;
                font-size: 29px;
                line-height: 1.1;
                letter-spacing: -0.7px;
            }

            .why-heading h2 span {
                color: #20a1b8;
            }

            .why-heading p {
                max-width: 310px;
                margin: 0 0 18px;
                color: #8291a3;
                font-size: 10.5px;
                line-height: 1.7;
            }

            .eyebrow {
                display: inline-block;
                color: #087eb8;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 2px;
            }

            .why-more {
                display: inline-flex;
                align-items: center;
                min-height: 32px;
                padding: 0 14px;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #087eb8, #20a1b8);
                font-size: 10px;
                font-weight: 700;
                box-shadow: 0 8px 18px rgba(8, 126, 184, 0.16);
                transition:
                    transform 0.25s ease,
                    box-shadow 0.25s ease;
            }

            .why-more:hover {
                transform: translateY(-3px);
                box-shadow: 0 13px 25px rgba(8, 126, 184, 0.22);
            }

            .why-list {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 13px;
            }

            .why-row {
                min-height: 205px;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                justify-content: flex-start;
                padding: 21px 19px 20px;
                border: 1px solid #dcecf1;
                border-radius: 16px;
                color: #fff;
                text-align: left;
                overflow: hidden;
                box-shadow: 0 9px 24px rgba(17, 62, 91, 0.055);
                transition:
                    transform 0.3s ease,
                    box-shadow 0.3s ease,
                    border-color 0.3s ease;
            }

            .why-row:nth-child(1) {
                background: linear-gradient(155deg, #075b8c, #0a3e73);
            }

            .why-row:nth-child(2) {
                background: linear-gradient(155deg, #10aeb3, #168fa1);
            }

            .why-row:nth-child(3) {
                background: linear-gradient(155deg, #438fb8, #51b4d0);
            }

            .why-row:hover {
                transform: translateY(-6px);
                border-color: rgba(255, 255, 255, 0.28);
                box-shadow: 0 17px 32px rgba(17, 62, 91, 0.14);
            }

            .why-number {
                margin-bottom: 17px;
                color: rgba(255, 255, 255, 0.52);
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 1.5px;
            }

            .why-symbol {
                width: 43px;
                height: 43px;
                margin-bottom: 15px;
                display: grid;
                place-items: center;
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 12px;
                color: #fff;
                background: rgba(255, 255, 255, 0.1);
                font-size: 14px;
                transition:
                    transform 0.3s ease,
                    background 0.3s ease;
            }

            .why-row:hover .why-symbol {
                transform: translateY(-3px);
                background: rgba(255, 255, 255, 0.17);
            }

            .why-content {
                width: 100%;
            }

            .why-content h3 {
                margin: 0 0 8px;
                color: #fff;
                font-size: 11.5px;
                line-height: 1.35;
            }

            .why-content p {
                max-width: 210px;
                margin: 0;
                color: rgba(255, 255, 255, 0.76);
                font-size: 10px;
                line-height: 1.65;
            }

            .certification-cta {
                width: 100%;
                padding: 0;
                background: #f7fbfd;
            }

            .certification-inner {
                position: relative;
                width: 100%;
                min-height: 300px;
                margin: 0;
                display: grid;
                grid-template-columns: 48% 52%;
                align-items: center;
                gap: 0;
                padding: 42px 7%;
                overflow: hidden;
                border-radius: 0;
                background: linear-gradient(105deg, #e7f6fa 0%, #f7fcfd 52%, #42b8cd 100%);
                box-shadow: none;
            }

            .certification-inner::before {
                display: none;
            }

            .certificate-stage {
                position: relative;
                z-index: 2;
                min-height: 220px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding-right: 30px;
            }

            .certificate-image {
                width: 100%;
                max-width: 560px;
                height: 230px;
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.22);
                transition:
                    transform 0.4s ease,
                    box-shadow 0.4s ease;
            }

            .certificate-stage:hover .certificate-image {
                transform: translateY(-5px);
                box-shadow: 0 18px 35px rgba(18, 62, 120, 0.1);
            }

            .certification-copy {
                position: relative;
                z-index: 3;
                padding-left: 30px;
            }

            .certification-kicker {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 8px;
                color: #087eb8;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 1.7px;
            }

            .certification-copy h2 {
                max-width: 540px;
                margin: 0 0 10px;
                color: #123f78;
                font-size: 27px;
                line-height: 1.16;
                letter-spacing: -0.5px;
            }

            .certification-copy h2 span {
                color: #087eb8;
            }

            .certification-copy p {
                max-width: 500px;
                margin: 0 0 15px;
                color: #657f92;
                font-size: 10.5px;
                line-height: 1.65;
            }

            .certification-points {
                display: flex;
                flex-wrap: wrap;
                gap: 7px 15px;
                margin-bottom: 17px;
            }

            .certification-points span {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                color: #49677c;
                font-size: 10px;
            }

            .certification-points i {
                color: #20a1b8;
            }

            .certification-button {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 33px;
                padding: 0 15px;
                border-radius: 9px;
                color: #fff;
                background: linear-gradient(135deg, #087eb8, #20a1b8);
                font-size: 10px;
                font-weight: 700;
                box-shadow: 0 8px 18px rgba(8, 126, 184, 0.16);
                transition:
                    transform 0.25s ease,
                    box-shadow 0.25s ease;
            }

            .certification-button:hover {
                transform: translateY(-3px);
                box-shadow: 0 13px 24px rgba(8, 126, 184, 0.22);
            }

            .section-reveal,
            .section-reveal-left,
            .section-reveal-right,
            .section-reveal-zoom,
            .section-reveal-soft {
                opacity: 0;
                will-change: transform, opacity;
            }

            .section-reveal {
                transform: translateY(35px);
            }

            .section-reveal-left {
                transform: translateX(-55px);
            }

            .section-reveal-right {
                transform: translateX(55px);
            }

            .section-reveal-zoom {
                transform: scale(0.94);
            }

            .section-reveal-soft {
                transform: translateY(18px) scale(0.985);
            }

            .section-visible {
                opacity: 1;
                transform: none;
                transition:
                    opacity 0.8s ease,
                    transform 0.9s cubic-bezier(0.22, 1, 0.36, 1);
            }

            .hero-buttons {
                animation: heroItemReveal 0.8s 0.62s cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            @keyframes heroItemReveal {
                from {
                    opacity: 0;
                    transform: translateY(18px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .stats-grid .stat,
            .why-list .why-row {
                opacity: 0;
            }

            .section-visible .stat,
            .section-visible .why-row {
                animation: cardRise 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
            }

            .section-visible .stat:nth-child(2),
            .section-visible .why-row:nth-child(2) {
                animation-delay: 0.08s;
            }

            .section-visible .stat:nth-child(3),
            .section-visible .why-row:nth-child(3) {
                animation-delay: 0.16s;
            }

            .section-visible .stat:nth-child(4) {
                animation-delay: 0.24s;
            }

            @keyframes cardRise {
                from {
                    opacity: 0;
                    transform: translateY(22px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @media (max-width: 900px) {
                .why-inner {
                    grid-template-columns: 1fr;
                    gap: 28px;
                }

                .why-heading {
                    padding: 0;
                    text-align: center;
                }

                .why-heading h2,
                .why-heading p {
                    margin-left: auto;
                    margin-right: auto;
                }

                .why-list {
                    grid-template-columns: repeat(3, 1fr);
                }

                .why-row {
                    min-height: 165px;
                }
            }

            @media (max-width: 600px) {
                .why {
                    padding: 34px 0 30px;
                }

                .why-list {
                    grid-template-columns: 1fr;
                    gap: 10px;
                }

                .why-row {
                    min-height: 145px;
                }

                .certification-inner {
                    grid-template-columns: 1fr;
                    padding: 22px 22px 28px;
                }

                .certificate-stage {
                    min-height: 180px;
                }

                .certificate-image {
                    height: 180px;
                }

                .certification-copy {
                    padding-left: 0;
                }
            }

            @media (max-width: 1000px) {
                .catalogue-layout {
                    grid-template-columns: 190px minmax(0, 1fr);
                    gap: 17px;
                }

                .formation-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .why-list {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 16px;
                }

                .certification-inner {
                    grid-template-columns: 0.8fr 1.2fr;
                }
            }

            @media (max-width: 820px) {
                .main-header {
                    padding: 0 4%;
                }

                .nav-link {
                    padding-left: 8px;
                    padding-right: 8px;
                    font-size: 10.5px;
                }

                .nav-link::after {
                    left: 8px;
                    right: 8px;
                }

                .auth-item {
                    margin-left: 5px;
                    gap: 5px;
                }

                .login-button,
                .register-button {
                    padding: 0 10px;
                    font-size: 10px;
                }

                .stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .stat {
                    min-height: 82px;
                }

                .certification-inner {
                    grid-template-columns: 1fr;
                    gap: 10px;
                    padding: 34px 7%;
                }

                .certificate-stage {
                    min-height: 190px;
                    padding-right: 0;
                }

                .certification-copy {
                    padding-left: 0;
                }
            }

            @media (max-width: 680px) {
                body {
                    padding-top: 70px;
                }

                .main-header {
                    height: 70px;
                    flex-wrap: wrap;
                    align-content: center;
                }

                .logo img {
                    width: 92px;
                }

                .main-nav {
                    order: 3;
                    width: 100%;
                    overflow-x: auto;
                    margin: 0;
                }

                .nav-list {
                    width: max-content;
                    min-width: 100%;
                    justify-content: center;
                }

                .nav-link {
                    padding: 7px 7px 8px;
                    font-size: 10px;
                }

                .auth-item {
                    margin-left: 4px;
                }

                .catalogue {
                    padding-top: 42px;
                }

                .section-title h2,
                .why-heading h2 {
                    font-size: 25px;
                }

                .catalogue-layout {
                    grid-template-columns: 1fr;
                }

                .filters {
                    position: relative;
                    top: auto;
                }

                .formation-grid {
                    grid-template-columns: 1fr;
                }

                .formation-image {
                    height: 165px;
                }

                .stats-grid {
                    grid-template-columns: 1fr;
                }

                .stat {
                    min-height: 65px;
                }

                .why-list {
                    grid-template-columns: 1fr;
                    gap: 14px;
                }

                .why-row {
                    min-height: 145px;
                }

                .certification-inner {
                    width: 100%;
                    padding: 25px 20px;
                    border-radius: 0;
                }

                .certification-copy h2 {
                    font-size: 22px;
                }

                .certification-points {
                    flex-direction: column;
                    gap: 7px;
                }
            }

            @media (max-width: 460px) {
                .domain-list {
                    justify-content: flex-start;
                    overflow-x: auto;
                    flex-wrap: nowrap;
                    padding-bottom: 4px;
                }

                .domain {
                    flex: 0 0 auto;
                }

                .results-top {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .sort {
                    width: 100%;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                html {
                    scroll-behavior: auto;
                }

                *,
                *::before,
                *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }

                .section-reveal,
                .section-reveal-left,
                .section-reveal-right,
                .section-reveal-zoom,
                .section-reveal-soft {
                    opacity: 1 !important;
                    transform: none !important;
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

        <header class="site-header">
            <div class="container nav-wrap">
                <a href="index.php" class="brand"><img src="images/logo.png" alt="TOUN"></a>

                <nav class="main-nav" id="main-nav">
                    <div class="nav-links">
                        <a href="index.php" class="nav-link">Accueil</a>
                        <a href="formation.php" class="nav-link active">Formation</a>

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

        <section class="page-hero">
            <img class="page-hero-img" src="images/img heroù.png" alt="Formation professionnelle TOUN">
            <div class="page-hero-overlay"></div>
            <div class="page-hero-inner">
                <div class="page-hero-copy">
                    <h1>Développez vos <strong>compétences.</strong></h1>
                    <p>Des formations concrètes, animées par des experts, pour acquérir des compétences utiles et faire évoluer votre parcours professionnel.</p>
                </div>
            </div>
        </section>

        <section class="catalogue" id="catalogue">

            <div class="container">

                <div class="section-title">

                    <small>NOS FORMATIONS</small>

                    <h2>
                        Développez vos <span>compétences</span>
                    </h2>

                    <p>
                        Découvrez notre catalogue de formations professionnelles.
                    </p>

                </div>

                <div class="domain-list">

                    <a href="formation.php" class="domain <?= $cat === 0 ? 'active' : '' ?>">
                        <i class="fas fa-grip"></i>
                        <span>Toutes</span>
                    </a>

                    <?php foreach ($categories as $category): ?>

                        <a
                            href="formation.php?cat=<?= (int) $category['id_cat'] ?>"
                            class="domain"
                        >
                            <i class="fas fa-layer-group"></i>
                            <span><?= e($category['nom_cat']) ?></span>
                        </a>

                    <?php endforeach; ?>

                </div>

                <div class="catalogue-layout">

                    <aside class="filters">

                        <form method="get">

                            <h3 class="filters-title">
                                Filtrer les formations
                            </h3>

                            <div class="filter-search">

                                <i class="fas fa-search"></i>

                                <input
                                    type="text"
                                    name="q"
                                    value="<?= e($search) ?>"
                                    placeholder="Rechercher..."
                                >

                            </div>

                            <div class="filter-group">

                                <h3>Catégorie</h3>

                                <label class="check">
                                    <input
                                        type="radio"
                                        name="cat"
                                        value="0"
                                        <?= $cat === 0 ? 'checked' : '' ?>
                                    >
                                    Toutes
                                </label>

                                <?php foreach ($categories as $category): ?>

                                    <label class="check">

                                        <input
                                            type="radio"
                                            name="cat"
                                            value="<?= (int) $category['id_cat'] ?>"
                                            <?= $cat === (int) $category['id_cat'] ? 'checked' : '' ?>
                                        >

                                        <?= e($category['nom_cat']) ?>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                            <div class="filter-group">

                                <h3>Niveau</h3>

                                <?php foreach (['Débutant','Intermédiaire','Avancé','Tous niveaux'] as $level): ?>

                                    <label class="check">

                                        <input
                                            type="radio"
                                            name="niveau"
                                            value="<?= e($level) ?>"
                                            <?= $niveau === $level ? 'checked' : '' ?>
                                        >

                                        <?= e($level) ?>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                            <button class="filter-button" type="submit">
                                Appliquer
                            </button>

                        </form>

                    </aside>

                    <div class="catalogue-main">

                        <div class="results-top">

                            <div>
                                <strong><?= count($formations) ?></strong>
                                <span>
                                    formation<?= count($formations) > 1 ? 's' : '' ?> disponible<?= count($formations) > 1 ? 's' : '' ?>
                                </span>
                            </div>

                            <form method="get">

                                <?php if ($search !== ''): ?>
                                    <input type="hidden" name="q" value="<?= e($search) ?>">
                                <?php endif; ?>

                                <?php if ($cat > 0): ?>
                                    <input type="hidden" name="cat" value="<?= $cat ?>">
                                <?php endif; ?>

                                <?php if ($niveau !== ''): ?>
                                    <input type="hidden" name="niveau" value="<?= e($niveau) ?>">
                                <?php endif; ?>

                                <select
                                    name="sort"
                                    class="sort"
                                    onchange="this.form.submit()"
                                >
                                    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>
                                        Les plus récentes
                                    </option>
                                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>
                                        Les plus populaires
                                    </option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>
                                        Prix croissant
                                    </option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>
                                        Prix décroissant
                                    </option>
                                </select>

                            </form>

                        </div>

                        <?php if (!$formations): ?>

                            <div class="empty">
                                <i class="fas fa-book-open"></i>
                                <br><br>
                                Aucune formation ne correspond à vos critères.
                            </div>

                        <?php else: ?>

                            <div class="formation-grid">

                                <?php foreach ($formations as $formation): ?>

                                    <article class="formation-card">

                                        <div class="formation-image">

                                            <img
                                                src="<?= e(formationImage($formation['img_form'])) ?>"
                                                alt="<?= e($formation['titre_form']) ?>"
                                            >

                                            <span class="formation-favorite" aria-hidden="true">
                                                <i class="far fa-heart"></i>
                                            </span>

                                            <?php if (strtotime($formation['date_creation_form']) >= strtotime('-30 days')): ?>

                                                <span class="badge">
                                                    Nouveau
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                        <div class="formation-body">

                                            <span class="formation-category">
                                                <?= e($formation['nom_cat'] ?: 'Formation') ?>
                                            </span>

                                            <h3>
                                                <?= e($formation['titre_form']) ?>
                                            </h3>

                                            <?php
$formateurNom = trim(($formation['prenom_user'] ?? '') . ' ' . ($formation['nom_user'] ?? ''));

$formateurPhoto = formateurImage($formation['photo_user'] ?? '');
$parts = $formateurNom !== '' ? preg_split('/\s+/', $formateurNom) : [];
$initiales = '';

if ($parts) {
    $initiales = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[count($parts) - 1] ?? '', 0, 1));
}
?>

                                            <div class="trainer-box">
                                                <div class="trainer-photo">
                                                    <?php if ($formateurPhoto !== ''): ?>
                                                        <img src="<?= e($formateurPhoto) ?>" alt="<?= e($formateurNom) ?>">
                                                    <?php else: ?>
                                                        <span><?= e($initiales ?: 'F') ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="trainer-info">
                                                    <span class="trainer-label">Formateur</span>
                                                    <span class="trainer-name">
                                                        <?= e($formateurNom ?: 'Formateur TOUN') ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="rating">
                                                ★★★★★
                                                <span>Formation professionnelle</span>
                                            </div>

                                            <div class="meta">

                                                <span>
                                                    <i class="far fa-clock"></i>

                                                    <?= (int) $formation['duree_form'] >= 60 ? e(floor($formation['duree_form'] / 60) . ' h') : e($formation['duree_form'] . ' min') ?>
                                                </span>

                                                <span>
                                                    <?= e($formation['niveau_form']) ?>
                                                </span>

                                            </div>

                                            <div class="price-row">

                                                <strong class="price">
                                                    <?= number_format((float) $formation['prix_form'], 0, ',', ' ') ?>
                                                    FCFA
                                                </strong>

                                                <a
                                                    href="formation-details.php?id=<?= (int) $formation['id_form'] ?>"
                                                    class="view"
                                                    aria-label="Voir la formation"
                                                >
                                                    <i class="fas fa-arrow-right"></i>
                                                </a>

                                            </div>

                                        </div>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </section>

        <section class="stats">

            <div class="container stats-grid">

                <div class="stat">

                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>

                    <div>
                        <strong class="stat-number" data-target="<?= $totalFormations ?>">0</strong>
                        <span class="stat-label">Formations disponibles</span>
                    </div>

                </div>

                <div class="stat">

                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>

                    <div>
                        <strong class="stat-number" data-target="5000">0</strong>
                        <span class="stat-label">Apprenants satisfaits</span>
                    </div>

                </div>

                <div class="stat">

                    <div class="stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>

                    <div>
                        <strong class="stat-number" data-target="<?= $totalCategories ?>">0</strong>
                        <span class="stat-label">Domaines de formation</span>
                    </div>

                </div>

                <div class="stat">

                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>

                    <div>
                        <strong class="stat-number" data-target="<?= $totalFormateurs ?>">0</strong>
                        <span class="stat-label">Formateurs actifs</span>
                    </div>

                </div>

            </div>

        </section>

        <section class="why">
            <div class="why-inner">

                <div class="why-heading">
                    <span class="eyebrow">POURQUOI TOUN</span>
                    <h2>Une expérience<br><span>d’apprentissage unique.</span></h2>
                    <p>Des formations concrètes, animées par des experts, pour vous aider à atteindre vos objectifs professionnels.</p>
                    <a href="a-propos.php" class="why-more">
                        En savoir plus
                    </a>
                </div>

                <div class="why-list">

                    <article class="why-row">
                        <div class="why-number">01</div>
                        <div class="why-symbol"><i class="fas fa-laptop"></i></div>
                        <div class="why-content">
                            <h3>Plateforme moderne</h3>
                            <p>Accédez à vos formations depuis tous vos appareils, où que vous soyez.</p>
                        </div>
                        <i class="fas fa-arrow-right why-arrow"></i>
                    </article>

                    <article class="why-row">
                        <div class="why-number">02</div>
                        <div class="why-symbol"><i class="fas fa-file-lines"></i></div>
                        <div class="why-content">
                            <h3>Des contenus de qualité</h3>
                            <p>Des formations conçues par des experts, adaptées aux réalités du monde professionnel.</p>
                        </div>
                        <i class="fas fa-arrow-right why-arrow"></i>
                    </article>

                    <article class="why-row">
                        <div class="why-number">03</div>
                        <div class="why-symbol"><i class="fas fa-chart-line"></i></div>
                        <div class="why-content">
                            <h3>Des résultats concrets</h3>
                            <p>Développez des compétences reconnues et boostez votre évolution professionnelle.</p>
                        </div>
                        <i class="fas fa-arrow-right why-arrow"></i>
                    </article>

                </div>
            </div>
        </section>

        <section class="certification-cta">
            <div class="certification-inner">

                <div class="certificate-stage">
                    <div class="certificate-image">
                        <img src="images/image3.png" alt="">
                    </div>
                </div>

                <div class="certification-copy">
                    <span class="certification-kicker">
                        <i class="fas fa-award"></i>
                        CERTIFICATION TOUN
                    </span>

                    <h2>
                        Développez vos compétences<br>
                        <span>et valorisez votre parcours.</span>
                    </h2>

                    <p>
                        À la fin de votre formation, validez vos acquis avec une
                        certification professionnelle TOUN et donnez une valeur
                        concrète aux compétences développées.
                    </p>

                    <div class="certification-points">
                        <span><i class="fas fa-check"></i> Parcours validé</span>
                        <span><i class="fas fa-check"></i> Compétences acquises</span>
                        <span><i class="fas fa-check"></i> Certificat TOUN</span>
                    </div>

                    <a href="inscription.php" class="certification-button">
                        Commencer une formation
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

            </div>
        </section>
        <BR>
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
                const sections = [
                    ['.catalogue', 'section-reveal-left'],
                    ['.stats', 'section-reveal-zoom'],
                    ['.why', 'section-reveal-right'],
                    ['.certification-cta', 'section-reveal-soft'],
                    ['.footer', 'section-reveal']
                ];

                sections.forEach(function (item) {
                    const elements = document.querySelectorAll(item[0]);
                    elements.forEach(function (element) {
                        element.classList.add(item[1]);
                    });
                });

                const observer = new IntersectionObserver(
                    function (entries, obs) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;
                            entry.target.classList.add('section-visible');
                            obs.unobserve(entry.target);
                        });
                    },
                    {
                        threshold: 0.12,
                        rootMargin: '0px 0px -45px 0px'
                    }
                );

                document
                    .querySelectorAll(
                        '.section-reveal,.section-reveal-left,.section-reveal-right,.section-reveal-zoom,.section-reveal-soft'
                    )
                    .forEach(function (element) {
                        observer.observe(element);
                    });

                const counters = document.querySelectorAll('.stat-number');

                const counterObserver = new IntersectionObserver(
                    function (entries, obs) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;

                            const counter = entry.target;
                            const target = Number(counter.dataset.target);
                            const duration = 1400;
                            const start = performance.now();

                            function update(time) {
                                const progress = Math.min((time - start) / duration, 1);
                                const eased = 1 - Math.pow(1 - progress, 3);
                                counter.textContent = Math.floor(target * eased).toLocaleString('fr-FR');

                                if (progress < 1) requestAnimationFrame(update);
                            }

                            requestAnimationFrame(update);
                            obs.unobserve(counter);
                        });
                    },
                    {
                        threshold: 0.4
                    }
                );

                counters.forEach(function (counter) {
                    counterObserver.observe(counter);
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

    </body>
</html>
