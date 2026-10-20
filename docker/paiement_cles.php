<?php

return [
    'fournisseur' => getenv('PAIEMENT_FOURNISSEUR') ?: 'jeko',

    'fedapay_cle_secrete' => getenv('FEDAPAY_CLE_SECRETE') ?: '',
    'fedapay_mode' => getenv('FEDAPAY_MODE') ?: 'sandbox',

    'jeko_cle' => getenv('JEKO_CLE') ?: '',
    'jeko_cle_id' => getenv('JEKO_CLE_ID') ?: '',
    'jeko_boutique' => getenv('JEKO_BOUTIQUE') ?: '',

    'apikey'  => getenv('CINETPAY_APIKEY') ?: '',
    'site_id' => getenv('CINETPAY_SITE_ID') ?: '',

    'base_url' => getenv('SITE_URL') ?: '',

    'simulation' => getenv('PAIEMENT_SIMULATION') === 'true',
];
