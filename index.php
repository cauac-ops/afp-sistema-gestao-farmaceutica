<?php
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['id_func'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/public/login_afp.php');
}
exit;
