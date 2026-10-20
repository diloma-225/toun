<?php
session_start();

if (!empty($_SESSION['id_user'])) {
    $_SESSION['notifications_seen_at'] = date('Y-m-d H:i:s');
}

http_response_code(204);
exit();