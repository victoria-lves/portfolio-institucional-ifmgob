<?php
session_start();

if(!isset($_SESSION['usuario_id']) || !isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Producao.php';

$database = new Database();
$db = $database->getConnection();
$producao = new Producao($db);

$id = $_POST['id'];
$dados = $producao->buscarPorId($id);

if(!$dados) {
    $_SESSION['erro'] = "Produção não encontrada.";
    header("Location: index.php");
    exit();
}

// Verificação de permissão (Admin ou Dono)
if($_SESSION['usuario_nivel'] != 'admin') {
    if(!isset($_SESSION['professor_id']) || $dados['id_professor'] != $_SESSION['professor_id']) {
        $_SESSION['erro'] = "Permissão negada.";
        header("Location: index.php");
        exit();
    }
}

if($producao->delete($id)) {
    $_SESSION['sucesso'] = "Produção excluída com sucesso!";
} else {
    $_SESSION['erro'] = "Erro ao tentar excluir.";
}

header("Location: index.php");
exit();
?>