<?php
session_start();

if (isset($_SESSION['id_func'])) {
    header('Location: pages/dashboard.php');
} else {
    
    header('Location: login_afp.php');
}
exit;
