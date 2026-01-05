<?php
session_start();
session_destroy(); // destrói a sessão
header("Location: views/auth/login.php"); // redireciona para a página de login
exit();
?>