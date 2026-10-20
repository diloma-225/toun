<?php
require_once __DIR__ . '/config/adresse.php';

$tounCles = is_file(__DIR__ . '/config/paiement_cles.php') ? require __DIR__ . '/config/paiement_cles.php' : [];
$tounCles = is_array($tounCles) ? $tounCles : [];

define('TOUN_CINETPAY_APIKEY', (string) ($tounCles['apikey'] ?? 'A_REMPLACER_PAR_VOTRE_APIKEY'));
define('TOUN_CINETPAY_SITE_ID', (string) ($tounCles['site_id'] ?? 'A_REMPLACER_PAR_VOTRE_SITE_ID'));
define('TOUN_CINETPAY_API_URL', 'https://api-checkout.cinetpay.com/v2/payment');
define('TOUN_CINETPAY_VERIFY_URL', 'https://api-checkout.cinetpay.com/v2/payment/check');
define('TOUN_PAIEMENT_CURRENCY', 'XOF');
define('TOUN_PAYMENT_BASE_URL', tounAdresseSite((string) ($tounCles['base_url'] ?? 'http://localhost/application%20toun')));
define('TOUN_PAIEMENT_SIMULATION', ($tounCles['simulation'] ?? false) === true);

define('TOUN_FOURNISSEUR', strtolower((string) ($tounCles['fournisseur'] ?? 'cinetpay')));
define('TOUN_FEDAPAY_CLE_SECRETE', (string) ($tounCles['fedapay_cle_secrete'] ?? ''));
define('TOUN_FEDAPAY_MODE', ($tounCles['fedapay_mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox');
define('TOUN_JEKO_API_URL', 'https://api.jeko.africa/partner_api');
define('TOUN_JEKO_CLE', (string) ($tounCles['jeko_cle'] ?? ''));
define('TOUN_JEKO_CLE_ID', (string) ($tounCles['jeko_cle_id'] ?? ''));
define('TOUN_JEKO_BOUTIQUE', (string) ($tounCles['jeko_boutique'] ?? ''));
define('TOUN_FEDAPAY_API_URL', TOUN_FEDAPAY_MODE === 'live' ? 'https://api.fedapay.com/v1' : 'https://sandbox-api.fedapay.com/v1');
unset($tounCles);

function tounPaymentReady(): bool
{
    if (TOUN_FOURNISSEUR === 'jeko') {
        return TOUN_JEKO_CLE !== '' && TOUN_JEKO_CLE !== 'A_REMPLACER_PAR_VOTRE_CLE_API' &&
            TOUN_JEKO_CLE_ID !== '' && TOUN_JEKO_CLE_ID !== 'A_REMPLACER_PAR_VOTRE_ID_DE_CLE';
    }
    if (TOUN_FOURNISSEUR === 'fedapay') {
        return TOUN_FEDAPAY_CLE_SECRETE !== '' && TOUN_FEDAPAY_CLE_SECRETE !== 'A_REMPLACER_PAR_VOTRE_CLE_SECRETE';
    }
    return TOUN_CINETPAY_APIKEY !== '' && TOUN_CINETPAY_APIKEY !== 'A_REMPLACER_PAR_VOTRE_APIKEY' &&
        TOUN_CINETPAY_SITE_ID !== '' && TOUN_CINETPAY_SITE_ID !== 'A_REMPLACER_PAR_VOTRE_SITE_ID';
}

function tounSimulationAutorisee(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return TOUN_PAIEMENT_SIMULATION && !tounPaymentReady() && in_array($ip, ['127.0.0.1', '::1'], true);
}
function tounPaymentUrl(string $file): string
{
    return rtrim(TOUN_PAYMENT_BASE_URL, '/') . '/' . ltrim($file, '/');
}
function tounEnsurePaymentTable(PDO $con): void
{
    $con->exec("CREATE TABLE IF NOT EXISTS t_paiements (
        id_paiement INT NOT NULL AUTO_INCREMENT,
        id_user INT DEFAULT NULL,
        id_entreprise INT DEFAULT NULL,
        id_form INT NOT NULL,
        quantite INT NOT NULL DEFAULT 1,
        montant_total DECIMAL(10,2) NOT NULL,
        mode_paiement VARCHAR(50) DEFAULT NULL,
        ref_paiement VARCHAR(100) DEFAULT NULL,
        numero_recu VARCHAR(100) DEFAULT NULL,
        lien_recu VARCHAR(255) DEFAULT NULL,
        statut_paiement ENUM('en_attente','paye','echoue','rembourse') NOT NULL DEFAULT 'en_attente',
        date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id_paiement),
        UNIQUE KEY ref_paiement (ref_paiement),
        UNIQUE KEY numero_recu (numero_recu),
        KEY id_user (id_user),
        KEY id_entreprise (id_entreprise),
        KEY id_form (id_form)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $col = $con->query("SHOW COLUMNS FROM t_paiements LIKE 'ref_fournisseur'")->fetch();
    if (!$col) {
        $con->exec("ALTER TABLE t_paiements ADD COLUMN ref_fournisseur VARCHAR(100) DEFAULT NULL, ADD KEY ref_fournisseur (ref_fournisseur)");
    }
}
function tounGenererRecu(int $idPaiement): string
{
    return 'REC-' . date('Ymd') . '-' . str_pad((string) $idPaiement, 6, '0', STR_PAD_LEFT);
}
function tounFormationAcquise(PDO $con, int $userId, int $formationId): bool
{
    tounEnsurePaymentTable($con);

    $paiement = $con->prepare(
        "SELECT 1 FROM t_paiements WHERE id_user = ? AND id_form = ? AND statut_paiement = 'paye' LIMIT 1"
    );
    $paiement->execute([$userId, $formationId]);
    if ($paiement->fetchColumn()) {
        return true;
    }

    $inscription = $con->prepare(
        "SELECT 1 FROM t_inscriptions WHERE id_user = ? AND id_form = ? AND statut_inscrip = 'active' LIMIT 1"
    );
    $inscription->execute([$userId, $formationId]);
    return (bool) $inscription->fetchColumn();
}

function tounCreateInscription(PDO $con, int $userId, int $formationId, ?int $idPaiement = null): void
{
    $check = $con->prepare('SELECT id_inscrip FROM t_inscriptions WHERE id_user = :u AND id_form = :f LIMIT 1');
    $check->execute([':u' => $userId, ':f' => $formationId]);
    $idInscription = $check->fetchColumn();

    if ($idInscription) {
        $update = $con->prepare(
            "UPDATE t_inscriptions SET statut_inscrip = 'active', id_paiement = COALESCE(id_paiement, :p) WHERE id_inscrip = :id"
        );
        $update->execute([':p' => $idPaiement, ':id' => $idInscription]);
        return;
    }

    $insert = $con->prepare("INSERT INTO t_inscriptions (id_user, id_form, id_paiement, statut_inscrip, date_inscrip_form)
        VALUES (:u, :f, :p, 'active', NOW())");
    $insert->execute([':u' => $userId, ':f' => $formationId, ':p' => $idPaiement]);
}

function tounAppelHttp(string $methode, string $url, ?array $corps, array $entetes = []): ?array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_CUSTOMREQUEST => $methode,
        CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json', 'Accept: application/json'], $entetes),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ];
    if (defined('CURLSSLOPT_NATIVE_CA')) {
        $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
    }
    if ($corps !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($corps, JSON_UNESCAPED_UNICODE);
    } elseif ($methode === 'POST') {
        $options[CURLOPT_POSTFIELDS] = '';
    }
    curl_setopt_array($ch, $options);
    $brut = curl_exec($ch);
    $erreur = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($brut === false || $erreur) {
        error_log('TOUN paiement : passerelle injoignable (' . $erreur . ')');
        return null;
    }
    if ($code >= 400) {
        error_log('TOUN paiement : réponse HTTP ' . $code . ' de ' . $url . ' : ' . substr((string) $brut, 0, 500));
    }

    $donnees = json_decode((string) $brut, true);
    return is_array($donnees) ? $donnees : null;
}

function tounFedapay(string $methode, string $chemin, ?array $corps = null): ?array
{
    return tounAppelHttp($methode, TOUN_FEDAPAY_API_URL . $chemin, $corps, ['Authorization: Bearer ' . TOUN_FEDAPAY_CLE_SECRETE]);
}

function tounFedapayTransaction(?array $reponse): array
{
    if (!is_array($reponse)) {
        return [];
    }
    foreach (['v1/transaction', 'transaction'] as $cle) {
        if (isset($reponse[$cle]) && is_array($reponse[$cle])) {
            return $reponse[$cle];
        }
    }
    return isset($reponse['id']) ? $reponse : [];
}

function tounJeko(string $methode, string $chemin, ?array $corps = null): ?array
{
    return tounAppelHttp($methode, TOUN_JEKO_API_URL . $chemin, $corps, [
        'X-API-KEY: ' . TOUN_JEKO_CLE,
        'X-API-KEY-ID: ' . TOUN_JEKO_CLE_ID
    ]);
}

function tounJekoBoutique(): string
{
    if (TOUN_JEKO_BOUTIQUE !== '') {
        return TOUN_JEKO_BOUTIQUE;
    }

    $boutiques = tounJeko('GET', '/stores');
    $liste = isset($boutiques['data']) && is_array($boutiques['data']) ? $boutiques['data'] : $boutiques;

    return is_array($liste) && isset($liste[0]['id']) ? (string) $liste[0]['id'] : '';
}

function tounJekoMoyen(string $mode): string
{
    return ['WAVE' => 'wave', 'OM' => 'orange', 'MOOV' => 'moov', 'MTN' => 'mtn'][$mode] ?? 'wave';
}

function tounCreerLienPaiement(PDO $con, array $c): array
{
    if (TOUN_FOURNISSEUR === 'jeko') {
        $boutique = tounJekoBoutique();
        if ($boutique === '') {
            return ['erreur' => 'Impossible de contacter la passerelle de paiement.'];
        }

        $retour = str_replace(
            '://localhost',
            '://127.0.0.1',
            tounPaymentUrl('paiement-retour.php?transaction_id=' . rawurlencode($c['reference']))
        );
        if (!empty($c['retour_app'])) {
            $retour .= '&app=' . rawurlencode((string) $c['retour_app']);
        }
        $reponse = tounJeko('POST', '/payment_requests', [
            'storeId' => $boutique,
            'amountCents' => (int) $c['montant'] * 100,
            'currency' => TOUN_PAIEMENT_CURRENCY,
            'reference' => $c['reference'],
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => tounJekoMoyen((string) $c['mode']),
                    'successUrl' => $retour,
                    'errorUrl' => $retour
                ]
            ]
        ]);

        if ($reponse === null) {
            return ['erreur' => 'Impossible de contacter la passerelle de paiement.'];
        }
        if (empty($reponse['id']) || empty($reponse['redirectUrl'])) {
            return ['erreur' => 'La passerelle a refusé l\'initialisation du paiement.'];
        }

        $u = $con->prepare('UPDATE t_paiements SET ref_fournisseur = ? WHERE id_paiement = ?');
        $u->execute([(string) $reponse['id'], $c['id_paiement']]);

        return ['url' => (string) $reponse['redirectUrl']];
    }

    if (TOUN_FOURNISSEUR === 'fedapay') {
        $charge = [
            'description' => $c['description'],
            'amount' => (int) $c['montant'],
            'currency' => ['iso' => TOUN_PAIEMENT_CURRENCY],
            'callback_url' => tounPaymentUrl('paiement-retour.php')
        ];
        if (!empty($c['email'])) {
            $charge['customer'] = [
                'firstname' => (string) ($c['prenom'] ?: 'Client'),
                'lastname' => (string) ($c['nom'] ?: 'TOUN'),
                'email' => (string) $c['email']
            ];
        }

        $reponse = tounFedapay('POST', '/transactions', $charge);
        if ($reponse === null) {
            return ['erreur' => 'Impossible de contacter la passerelle de paiement.'];
        }
        $transaction = tounFedapayTransaction($reponse);
        if (empty($transaction['id'])) {
            return ['erreur' => 'La passerelle a refusé l\'initialisation du paiement.'];
        }

        $u = $con->prepare('UPDATE t_paiements SET ref_fournisseur = ? WHERE id_paiement = ?');
        $u->execute([(string) $transaction['id'], $c['id_paiement']]);

        $jeton = tounFedapay('POST', '/transactions/' . rawurlencode((string) $transaction['id']) . '/token');
        if (empty($jeton['url'])) {
            return ['erreur' => 'La passerelle a refusé l\'initialisation du paiement.'];
        }
        return ['url' => (string) $jeton['url']];
    }

    $donnees = tounAppelCinetpay(TOUN_CINETPAY_API_URL, [
        'apikey' => TOUN_CINETPAY_APIKEY,
        'site_id' => TOUN_CINETPAY_SITE_ID,
        'transaction_id' => $c['reference'],
        'amount' => (int) $c['montant'],
        'currency' => TOUN_PAIEMENT_CURRENCY,
        'description' => $c['description'],
        'notify_url' => tounPaymentUrl('paiement-notification.php'),
        'return_url' => tounPaymentUrl('paiement-retour.php'),
        'channels' => 'MOBILE_MONEY,WALLET',
        'lang' => 'fr',
        'metadata' => json_encode(
            ['reference' => $c['reference'], 'id_user' => $c['id_user'], 'id_form' => $c['id_form'], 'mode' => $c['mode']],
            JSON_UNESCAPED_UNICODE
        ),
        'customer_id' => (string) $c['id_user'],
        'customer_name' => (string) ($c['nom'] ?? ''),
        'customer_surname' => (string) ($c['prenom'] ?? ''),
        'customer_email' => (string) ($c['email'] ?? ''),
        'customer_phone_number' => (string) ($c['tel'] ?? ''),
        'customer_country' => 'CI'
    ]);

    if ($donnees === null) {
        return ['erreur' => 'Impossible de contacter la passerelle de paiement.'];
    }
    if (($donnees['code'] ?? '') !== '201' || empty($donnees['data']['payment_url'])) {
        error_log('TOUN paiement : initialisation refusée (' . ($donnees['message'] ?? '') . ')');
        return ['erreur' => 'La passerelle a refusé l\'initialisation du paiement.'];
    }
    return ['url' => (string) $donnees['data']['payment_url']];
}

function tounAppelCinetpay(string $url, array $charge): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($charge),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    if (defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }
    $brut = curl_exec($ch);
    $erreur = curl_error($ch);
    curl_close($ch);

    if ($brut === false || $erreur) {
        error_log('TOUN paiement : CinetPay injoignable (' . $erreur . ')');
        return null;
    }

    $donnees = json_decode((string) $brut, true);
    return is_array($donnees) ? $donnees : null;
}

function tounVerifierPaiement(PDO $con, array $paiement): string
{
    if ($paiement['statut_paiement'] !== 'en_attente') {
        return (string) $paiement['statut_paiement'];
    }

    if (!tounPaymentReady()) {
        return 'en_attente';
    }

    if (TOUN_FOURNISSEUR === 'jeko') {
        if (empty($paiement['ref_fournisseur'])) {
            return 'en_attente';
        }
        $info = tounJeko('GET', '/payment_requests/' . rawurlencode((string) $paiement['ref_fournisseur']));
        if (!$info || ($info['reference'] ?? '') !== $paiement['ref_paiement']) {
            return 'en_attente';
        }
        $statut = [
            'success' => 'ACCEPTED',
            'error' => 'FAILED',
            'failed' => 'FAILED',
            'expired' => 'FAILED',
            'cancelled' => 'CANCELED',
            'canceled' => 'CANCELED'
        ][strtolower((string) ($info['status'] ?? ''))] ?? '';
        $montant = (float) ($info['transaction']['amount']['amount'] ?? 0);
        $attendu = (float) $paiement['montant_total'];
        $info['amount'] = abs($montant - $attendu * 100) < 0.5 ? $attendu : $montant;
        $info['currency'] = (string) ($info['transaction']['amount']['currency'] ?? '');
        $info['payment_method'] = (string) ($info['paymentMethod'] ?? $paiement['mode_paiement']);
    } elseif (TOUN_FOURNISSEUR === 'fedapay') {
        if (empty($paiement['ref_fournisseur'])) {
            return 'en_attente';
        }
        $info = tounFedapayTransaction(tounFedapay('GET', '/transactions/' . rawurlencode((string) $paiement['ref_fournisseur'])));
        if (!$info) {
            return 'en_attente';
        }
        $statut = [
            'approved' => 'ACCEPTED', 'transferred' => 'ACCEPTED',
            'declined' => 'REFUSED', 'canceled' => 'CANCELED', 'expired' => 'FAILED'
        ][strtolower((string) ($info['status'] ?? ''))] ?? '';
        $info['currency'] = TOUN_PAIEMENT_CURRENCY;
        $info['payment_method'] = (string) ($info['mode'] ?? $paiement['mode_paiement']);
    } else {
        $donnees = tounAppelCinetpay(TOUN_CINETPAY_VERIFY_URL, [
            'apikey' => TOUN_CINETPAY_APIKEY,
            'site_id' => TOUN_CINETPAY_SITE_ID,
            'transaction_id' => $paiement['ref_paiement']
        ]);

        if ($donnees === null) {
            return 'en_attente';
        }

        $info = is_array($donnees['data'] ?? null) ? $donnees['data'] : [];
        $statut = strtoupper((string) ($info['status'] ?? ''));
    }

    if ($statut === 'ACCEPTED' || $statut === 'SUCCESS') {
        $montantPaye = (int) round((float) ($info['amount'] ?? 0));
        $devise = strtoupper((string) ($info['currency'] ?? ''));
        if ($montantPaye !== (int) round((float) $paiement['montant_total']) || $devise !== TOUN_PAIEMENT_CURRENCY) {
            error_log('TOUN paiement : montant ou devise incorrects pour ' . $paiement['ref_paiement']);
            return 'en_attente';
        }

        $mode = (string) ($info['payment_method'] ?? $paiement['mode_paiement']);
        $up = $con->prepare(
            "UPDATE t_paiements SET statut_paiement = 'paye', mode_paiement = :mode, numero_recu = :recu, lien_recu = :lien
             WHERE id_paiement = :id AND statut_paiement = 'en_attente'"
        );
        $up->execute([
            ':mode' => $mode,
            ':recu' => tounGenererRecu((int) $paiement['id_paiement']),
            ':lien' => 'recu.php?ref=' . rawurlencode($paiement['ref_paiement']),
            ':id' => $paiement['id_paiement']
        ]);

        if ($up->rowCount() === 1 && $paiement['id_user'] !== null) {
            tounCreateInscription(
                $con,
                (int) $paiement['id_user'],
                (int) $paiement['id_form'],
                (int) $paiement['id_paiement']
            );
        }
        return 'paye';
    }

    if (in_array($statut, ['REFUSED', 'CANCELED', 'FAILED'], true)) {
        $up = $con->prepare(
            "UPDATE t_paiements SET statut_paiement = 'echoue' WHERE id_paiement = :id AND statut_paiement = 'en_attente'"
        );
        $up->execute([':id' => $paiement['id_paiement']]);
        return 'echoue';
    }

    return 'en_attente';
}
