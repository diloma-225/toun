<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/connexion.php';

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$active_page = basename($_SERVER['PHP_SELF']);

$stmt = $con->query("
    SELECT 
        c.id_cat,
        c.nom_cat,
        c.descrip_cat,
        c.image_cat,
        COUNT(f.id_form) AS nb_formations
    FROM t_categories c
    LEFT JOIN t_formations f 
        ON f.id_cat = c.id_cat
    GROUP BY 
        c.id_cat,
        c.nom_cat,
        c.descrip_cat,
        c.image_cat
    ORDER BY c.nom_cat ASC
");

$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'menu.php';
?>

<style>
    .cat-page {
        font-family: "Poppins", Arial, sans-serif;
        background: #f5f8fb;
        min-height: 100vh;
        padding: 100px 35px 40px 295px;
        color: #18213f;
    }

    .cat-wrap {
        width: 100%;
        max-width: 1250px;
        margin: 0 auto;
    }

    .cat-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 25px;
        margin-bottom: 28px;
    }

    .cat-title h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        color: #18213f;
    }

    .cat-title p {
        margin: 7px 0 0;
        font-size: 12px;
        color: #7d899b;
    }

    .cat-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 0 17px;
        border-radius: 10px;
        border: none;
        text-decoration: none;
        font-family: "Poppins", Arial, sans-serif;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .cat-btn-primary {
        color: #fff;
        background: linear-gradient(135deg, #20a1b8, #3f68ae);
        box-shadow: 0 7px 18px rgba(32, 161, 184, 0.18);
    }

    .cat-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(32, 161, 184, 0.25);
    }

    .cat-card {
        background: #fff;
        border: 1px solid #e2eaf1;
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(24, 33, 63, 0.055);
        overflow: hidden;
    }

    .cat-toolbar {
        min-height: 72px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        border-bottom: 1px solid #e9eef3;
        background: #fff;
    }

    .cat-toolbar-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cat-toolbar-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e8f7f9;
        color: #16899a;
        font-size: 13px;
    }

    .cat-toolbar-title strong {
        font-size: 12.5px;
        color: #26354d;
    }

    .cat-toolbar-title span {
        display: block;
        margin-top: 2px;
        font-size: 11px;
        color: #8b96a8;
    }

    .cat-search-box {
        position: relative;
        width: 300px;
    }

    .cat-search-box i {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #8b96a8;
        font-size: 11.5px;
    }

    .cat-search {
        width: 100%;
        height: 38px;
        padding: 0 12px 0 34px;
        border: 1px solid #dce6ee;
        border-radius: 9px;
        outline: none;
        background: #fbfdff;
        font:
            11.5px "Poppins",
            Arial,
            sans-serif;
        color: #26354d;
        box-sizing: border-box;
    }

    .cat-search:focus {
        border-color: #20a1b8;
        box-shadow: 0 0 0 3px rgba(32, 161, 184, 0.08);
    }

    .cat-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .cat-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px;
    }

    .cat-table th {
        padding: 14px 18px;
        background: #edf5f8;
        color: #526278;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.45px;
    }

    .cat-table td {
        padding: 16px 18px;
        border-top: 1px solid #edf1f5;
        font-size: 11.5px;
        color: #526278;
        vertical-align: middle;
    }

    .cat-table tbody tr {
        transition: background 0.2s ease;
    }

    .cat-table tbody tr:hover {
        background: #f8fbfd;
    }

    .cat-col-image {
        width: 90px;
    }

    .cat-col-name {
        width: 190px;
    }

    .cat-col-description {
        width: 43%;
    }

    .cat-col-formations {
        width: 120px;
    }

    .cat-col-actions {
        width: 130px;
    }

    .cat-img {
        width: 62px;
        height: 48px;
        display: block;
        border-radius: 9px;
        object-fit: cover;
        background: #edf5f8;
        border: 1px solid #e1ebf0;
    }

    .cat-img-empty {
        width: 62px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: linear-gradient(135deg, #edf7f9, #eef2fa);
        color: #9aabbc;
        font-size: 16px;
    }

    .cat-name {
        font-size: 12px;
        font-weight: 700;
        color: #25344c;
    }

    .cat-description {
        max-width: 430px;
        line-height: 1.6;
        color: #748196;
    }

    .cat-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 9px;
        border-radius: 7px;
        background: #e8f7f9;
        color: #16899a;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .cat-actions {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .cat-action {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.2s ease;
        font-size: 11.5px;
    }

    .cat-action:hover {
        transform: translateY(-2px);
    }

    .cat-view {
        background: #eaf7f8;
        color: #16899a;
    }

    .cat-edit {
        background: #edf2fb;
        color: #3f68ae;
    }

    .cat-delete {
        background: #fff0f2;
        color: #d95368;
    }

    .cat-empty {
        text-align: center;
        padding: 60px 20px !important;
        color: #8b96a8;
    }

    .cat-empty i {
        display: block;
        margin-bottom: 10px;
        font-size: 25px;
        color: #b7c5d1;
    }

    .cat-empty strong {
        display: block;
        margin-bottom: 4px;
        color: #526278;
        font-size: 12px;
    }

    .cat-empty span {
        font-size: 11px;
    }

    @media (max-width: 1000px) {
        .cat-page {
            padding-left: 95px;
            padding-right: 20px;
        }

        .cat-head {
            align-items: flex-start;
        }

        .cat-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .cat-search-box {
            width: 100%;
        }
    }

    @media (max-width: 650px) {
        .cat-page {
            padding: 90px 15px 30px 80px;
        }

        .cat-head {
            flex-direction: column;
        }

        .cat-btn {
            width: 100%;
        }
    }
</style>

<main class="cat-page">

    <div class="cat-wrap">

        <div class="cat-head">

            <div class="cat-title">
                <h1>Catégories</h1>
                <p>
                    Gérez les domaines de formation de la plateforme TOUN.
                </p>
            </div>

            <a
                href="categorie_ajout.php"
                class="cat-btn cat-btn-primary"
            >
                <i class="fa-solid fa-plus"></i>
                Ajouter une catégorie
            </a>

        </div>

        <div class="cat-card">

            <div class="cat-toolbar">

                <div class="cat-toolbar-title">

                    <div class="cat-toolbar-icon">
                        <i class="fa-solid fa-tags"></i>
                    </div>

                    <div>
                        <strong>Liste des catégories</strong>
                        <span>Les domaines disponibles sur TOUN</span>
                    </div>

                </div>

                <div class="cat-search-box">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="catSearch"
                        class="cat-search"
                        placeholder="Rechercher une catégorie..."
                    >

                </div>

            </div>

            <div class="cat-table-wrap">

                <table class="cat-table">

                    <thead>

                        <tr>
                            <th class="cat-col-image">Image</th>
                            <th class="cat-col-name">Nom</th>
                            <th class="cat-col-description">Description</th>
                            <th class="cat-col-formations">Formations</th>
                            <th class="cat-col-actions">Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!$cats): ?>

                            <tr>
                                <td colspan="5" class="cat-empty">

                                    <i class="fa-solid fa-tags"></i>

                                    <strong>Aucune catégorie enregistrée</strong>

                                    <span>
                                        Commencez par ajouter une première catégorie.
                                    </span>

                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($cats as $cat): ?>

                                <?php
$img = '';

if (!empty($cat['image_cat'])) {
    $n = basename($cat['image_cat']);

    $paths = ['../uploads/categories/' . $n, '../uploads/' . $n, '../assets/uploads/' . $n, '../images/' . $n];

    foreach ($paths as $u) {
        if (is_file(__DIR__ . '/' . $u)) {
            $img = $u;
            break;
        }
    }
}
?>

                                <tr>

                                    <td>

                                        <?php if ($img): ?>

                                            <img
                                                src="<?= e($img) ?>"
                                                class="cat-img"
                                                alt="<?= e($cat['nom_cat']) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="cat-img-empty">
                                                <i class="fa-solid fa-image"></i>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="cat-name">
                                            <?= e($cat['nom_cat']) ?>
                                        </div>

                                    </td>

                                    <td>

                                        <div class="cat-description">

                                            <?= e(mb_strimwidth($cat['descrip_cat'] ?? '', 0, 110, '...', 'UTF-8')) ?>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="cat-badge">

                                            <i class="fa-solid fa-book-open"></i>

                                            <?= e($cat['nb_formations']) ?>

                                            formation<?= $cat['nb_formations'] > 1 ? 's' : '' ?>

                                        </span>

                                    </td>

                                    <td>

                                        <div class="cat-actions">

                                            <a
                                                href="voir_categorie.php?id=<?= e($cat['id_cat']) ?>"
                                                class="cat-action cat-view"
                                                title="Voir"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                            </a>

                                            <a
                                                href="modifier_categorie.php?id=<?= e($cat['id_cat']) ?>"
                                                class="cat-action cat-edit"
                                                title="Modifier"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <a
                                                href="supprimer_categorie.php?id=<?= e($cat['id_cat']) ?>"
                                                class="cat-action cat-delete"
                                                title="Supprimer"
                                                onclick="return confirm('Supprimer cette catégorie ?')"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<script>
    const searchInput = document.getElementById('catSearch');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            document.querySelectorAll('.cat-table tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();

                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
</script>