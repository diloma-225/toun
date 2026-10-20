<?php
session_start();
require_once __DIR__ . '/../config/connexion.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role_user'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function query(PDO $con, string $sql, array $params = []): ?PDOStatement
{
    try {
        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Throwable $e) {
        return null;
    }
}

$total = 0;
$rows = [];

$stmt = query($con, 'SELECT COUNT(*) FROM t_certificats');
if ($stmt) {
    $total = (int) $stmt->fetchColumn();
}

$stmt = query(
    $con,
    "SELECT
        c.id_certificat,
        c.numero_certificat,
        c.date_obtention,
        c.lien_certificat,
        i.id_user,
        u.prenom_user,
        u.nom_user,
        f.titre_form
     FROM t_certificats c
     LEFT JOIN t_inscriptions i ON i.id_inscrip = c.id_inscrip
     LEFT JOIN t_utilisateurs u ON u.id_user = i.id_user
     LEFT JOIN t_formations f ON f.id_form = i.id_form
     ORDER BY c.id_certificat DESC
     LIMIT 100"
);

if ($stmt) {
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$learners = count(array_unique(array_filter(array_column($rows, 'id_user'))));
$forms = count(array_unique(array_filter(array_column($rows, 'titre_form'))));
$documents = count(array_filter($rows, fn($row) => !empty($row['lien_certificat'])));
?>

<style>
    .cert-page {
        min-height: calc(100vh - 62px);
        padding: 18px 24px 28px;
        background: #eef4f7;
        font-family: "Poppins", Arial, sans-serif;
    }

    .cert-hero {
        padding: 7px 10px 14px;
        margin-bottom: 8px;
        text-align: center;
    }

    .cert-hero h1 {
        margin: 0;
        color: #18213f;
        font-size: 25px;
        font-weight: 700;
    }

    .cert-hero p {
        margin: 5px 0 0;
        color: #7b899b;
        font-size: 11.5px;
    }

    .cert-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 14px;
    }

    .cert-stat {
        height: 84px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border: 1px solid #dce6ec;
        border-top: 3px solid #20a1b8;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(24, 33, 63, 0.055);
    }

    .cert-stat:nth-child(2) {
        border-top-color: #20a1b8;
    }

    .cert-stat:nth-child(3) {
        border-top-color: #3f68ae;
    }

    .cert-stat:nth-child(4) {
        border-top-color: #8b61c7;
    }

    .cert-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background: #eaf3f9;
        color: #0b5a91;
        font-size: 15px;
    }

    .cert-stat:nth-child(2) .cert-icon {
        background: #e5f5f7;
        color: #20a1b8;
    }

    .cert-stat:nth-child(3) .cert-icon {
        background: #eaf0fa;
        color: #3f68ae;
    }

    .cert-stat:nth-child(4) .cert-icon {
        background: #f1eaf9;
        color: #8b61c7;
    }

    .cert-stat small {
        display: block;
        color: #7b899b;
        font-size: 10.5px;
    }

    .cert-stat strong {
        display: block;
        margin-top: 2px;
        color: #18213f;
        font-size: 19px;
    }

    .cert-panel {
        overflow: hidden;
        border: 1px solid #dce7ed;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 33, 63, 0.06);
    }

    .cert-toolbar {
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        background: #f2f7f9;
        border-bottom: 1px solid #dce7ed;
    }

    .cert-search {
        width: 340px;
        height: 36px;
        padding: 0 11px;
        border: 1px solid #d1e0e7;
        border-radius: 9px;
        background: #fff;
        outline: 0;
        color: #18213f;
        font:
            11px "Poppins",
            sans-serif;
    }

    .cert-count {
        color: #8190a1;
        font-size: 10.5px;
    }

    .cert-table-wrap {
        overflow-x: auto;
    }

    .cert-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cert-table th {
        padding: 10px 14px;
        background: #e5eff3;
        color: #536a7f;
        text-align: left;
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.65px;
    }

    .cert-table td {
        padding: 11px 14px;
        border-top: 1px solid #ecf1f4;
        color: #45566a;
        font-size: 11px;
    }

    .cert-table td strong {
        color: #18213f;
    }

    .cert-number {
        color: #0b5a91 !important;
        font-weight: 700;
    }

    .cert-document {
        width: 29px;
        height: 29px;
        display: grid;
        place-items: center;
        border-radius: 7px;
        background: #f1eaf9;
        color: #8b61c7;
        text-decoration: none;
    }

    .cert-empty {
        padding: 46px 20px;
        text-align: center;
        color: #8996a5;
        font-size: 11px;
        background: #f7fafb;
    }

    .cert-empty i {
        display: block;
        margin-bottom: 8px;
        color: #20a1b8;
        font-size: 27px;
    }

    @media (max-width: 900px) {
        .cert-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .cert-page {
            padding: 13px 10px 20px;
        }

        .cert-stats {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .cert-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .cert-search {
            width: 100%;
        }
    }

    @media (max-width: 430px) {
        .cert-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main">
    <section class="cert-page">
        <header class="cert-hero">
            <div>
                <h1>Certificats</h1>
                <p>Suivez les certificats obtenus par les apprenants TOUN.</p>
            </div>
        </header>

        <div class="cert-stats">
            <div class="cert-stat">
                <div class="cert-icon"><i class="fa-solid fa-award"></i></div>
                <div>
                    <small>Total certificats</small>
                    <strong><?= $total ?></strong>
                </div>
            </div>
            <div class="cert-stat">
                <div class="cert-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <div>
                    <small>Apprenants certifiés</small>
                    <strong><?= $learners ?></strong>
                </div>
            </div>
            <div class="cert-stat">
                <div class="cert-icon"><i class="fa-solid fa-book-open"></i></div>
                <div>
                    <small>Formations concernées</small>
                    <strong><?= $forms ?></strong>
                </div>
            </div>
            <div class="cert-stat">
                <div class="cert-icon"><i class="fa-solid fa-file-pdf"></i></div>
                <div>
                    <small>Documents disponibles</small>
                    <strong><?= $documents ?></strong>
                </div>
            </div>
        </div>

        <section class="cert-panel">
            <div class="cert-toolbar">
                <input
                    id="certSearch"
                    class="cert-search"
                    type="search"
                    placeholder="Rechercher un certificat, un apprenant..."
                    autocomplete="off"
                >
                <span class="cert-count" id="certCount"><?= count($rows) ?> résultat(s)</span>
            </div>

            <div class="cert-table-wrap">
                <table class="cert-table" id="certTable">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Apprenant</th>
                            <th>Formation</th>
                            <th>Date</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="cert-empty">
                                        <i class="fa-solid fa-award"></i>
                                        Aucun certificat délivré pour le moment.
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="cert-number"><?= e($row['numero_certificat'] ?? '—') ?></td>
                                    <td>
                                        <?= e(trim(($row['prenom_user'] ?? '') . ' ' . ($row['nom_user'] ?? ''))) ?>
                                    </td>
                                    <td><?= e($row['titre_form'] ?? '—') ?></td>
                                    <td><?= e($row['date_obtention'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($row['lien_certificat'])): ?>
                                            <a
                                                class="cert-document"
                                                href="<?= e($row['lien_certificat']) ?>"
                                                target="_blank"
                                                title="Ouvrir le certificat"
                                            >
                                                <i class="fa-solid fa-file-arrow-down"></i>
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

<script>
    document.getElementById('certSearch')?.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        let visible = 0;

        document.querySelectorAll('#certTable tbody tr').forEach(function (row) {
            const match = !query || row.innerText.toLowerCase().includes(query);
            row.style.display = match ? '' : 'none';
            if (match) {
                visible++;
            }
        });

        const count = document.getElementById('certCount');
        if (count) {
            count.textContent = visible + ' résultat(s)';
        }
    });
</script>

<?php
$active_page = 'certificats.php';
include 'menu.php';
?>
