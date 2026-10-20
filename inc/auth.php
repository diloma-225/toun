<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['id_user']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id_user' => (int) $_SESSION['id_user'],
        'nom_user' => $_SESSION['nom_user'],
        'prenom_user' => $_SESSION['prenom_user'],
        'email_user' => $_SESSION['email_user'],
        'role_user' => $_SESSION['role_user']
    ];
}

function require_login(int $profondeur = 0): void
{
    if (is_logged_in()) {
        return;
    }

    $prefixe = str_repeat('../', $profondeur);
    $retour = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ' . $prefixe . 'connexion.php?retour=' . $retour);
    exit();
}

function require_role(array $rolesAutorises, int $profondeur = 0): void
{
    require_login($profondeur);

    $user = current_user();
    if (!in_array($user['role_user'], $rolesAutorises, true)) {
        http_response_code(403);
        $prefixe = str_repeat('../', $profondeur);
        echo "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'>" .
            "<title>Accès refusé</title></head><body style='font-family:sans-serif;padding:3rem;text-align:center'>" .
            '<h1>403 — Accès refusé</h1>' .
            "<p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>" .
            "<p><a href='{$prefixe}formations.php'>&larr; Retour aux formations</a></p>" .
            '</body></html>';
        exit();
    }
}

function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}