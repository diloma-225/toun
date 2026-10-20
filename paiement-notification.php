<?php
require_once __DIR__ . '/config/connexion.php';
require_once __DIR__ . '/paiement_config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => true]);
    exit();
}

try {
    $site = (string) ($_POST['cpm_site_id'] ?? '');
    $trans = (string) ($_POST['cpm_trans_id'] ?? '');
    if ($site === '' || $trans === '') {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit();
    }
    if ($site !== (string) TOUN_CINETPAY_SITE_ID) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit();
    }
    tounEnsurePaymentTable($con);
    $stmt = $con->prepare('SELECT * FROM t_paiements WHERE ref_paiement=:ref LIMIT 1');
    $stmt->execute([':ref' => $trans]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['ok' => false]);
        exit();
    }
    tounVerifierPaiement($con, $payment);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('TOUN paiement : erreur notification (' . $e->getMessage() . ')');
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
