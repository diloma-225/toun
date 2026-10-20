<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flash_cles = [
    'success' => [
        'flash_success',
        'formation_success',
        'entreprise_success',
        'success_formateur',
        'user_success',
        'success'
    ],
    'error' => ['flash_error', 'entreprise_error', 'error_formateur', 'user_error', 'error']
];

$flash_messages = [];

foreach ($flash_cles as $flash_type => $cles) {
    foreach ($cles as $cle) {
        if (!empty($_SESSION[$cle]) && is_string($_SESSION[$cle])) {
            $flash_messages[] = [$flash_type, $_SESSION[$cle]];
        }
        unset($_SESSION[$cle]);
    }
}

if (!$flash_messages) {
    return;
}
?>
<div class="flash-stack">
    <?php foreach ($flash_messages as [$flash_type, $flash_texte]): ?>
        <div class="flash-toast flash-<?= $flash_type ?>" role="status">
            <i class="fa-solid <?= $flash_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <span><?= htmlspecialchars($flash_texte, ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" class="flash-close" aria-label="Fermer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .flash-stack {
        position: fixed;
        top: 88px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 380px;
    }

    .flash-toast {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 12px;
        background: #fff;
        border-left: 4px solid;
        box-shadow: 0 12px 30px rgba(16, 41, 77, 0.15);
        font-family: "Poppins", sans-serif;
        font-size: 13px;
        color: #10294d;
        animation: flashIn 0.35s ease both;
        transition:
            opacity 0.3s ease,
            transform 0.3s ease;
    }

    .flash-toast > i {
        font-size: 18px;
    }

    .flash-success {
        border-color: #16a36a;
    }

    .flash-success > i {
        color: #16a36a;
    }

    .flash-error {
        border-color: #d9534f;
    }

    .flash-error > i {
        color: #d9534f;
    }

    .flash-toast span {
        flex: 1;
        line-height: 1.5;
    }

    .flash-close {
        border: 0;
        background: none;
        color: #8a97a8;
        cursor: pointer;
        padding: 4px;
    }

    .flash-toast.is-hidden {
        opacity: 0;
        transform: translateX(30px);
    }

    @keyframes flashIn {
        from {
            opacity: 0;
            transform: translateX(30px);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @media (max-width: 600px) {
        .flash-stack {
            left: 16px;
            right: 16px;
            max-width: none;
        }
    }
</style>

<script>
    document.querySelectorAll('.flash-toast').forEach(function (toast) {
        function fermer() {
            toast.classList.add('is-hidden');
            setTimeout(function () {
                toast.remove();
            }, 300);
        }

        toast.querySelector('.flash-close').addEventListener('click', fermer);
        setTimeout(fermer, 5000);
    });
</script>
