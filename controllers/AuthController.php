<?php
// controllers/AuthController.php
session_start();

// Verificar se é uma requisição de logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    // Destruir a sessão completamente
    session_unset();
    session_destroy();

    // Redirecionar para login
    header("Location: ../views/auth/login.php");
    exit();
}

// Se não for logout, redirecionar para login
header("Location: ../views/auth/login.php");
exit();
?>