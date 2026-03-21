<?php
session_start();

if (isset($_SESSION['id_func'])) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: public/login_afp.php');
}
exit;
