<?php
session_start();

if(!isset($_SESSION['usuario_id']) || !isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Projeto.php';

$database = new Database();
$db = $database->getConnection();
$projeto = new Projeto($db);

$id = $_POST['id'];
$dados_projeto = $projeto->buscarPorId($id);

// Verifica se o projeto existe
if(!$dados_projeto) {
    $_SESSION['erro'] = "Projeto não encontrado.";
    header("Location: index.php");
    exit();
}

// VERIFICAÇÃO DE SEGURANÇA:
// Apenas Admin OU o Professor dono do projeto podem excluir
if($_SESSION['usuario_nivel'] != 'admin') {
    if(!isset($_SESSION['professor_id']) || $dados_projeto['id_professor'] != $_SESSION['professor_id']) {
        $_SESSION['erro'] = "Você não tem permissão para excluir este projeto.";
        header("Location: index.php");
        exit();
    }
}

// 1. Excluir imagem do servidor se existir
if(!empty($dados_projeto['imagem'])) {
    $caminho_imagem = "../../img/projetos/" . $dados_projeto['imagem'];
    if(file_exists($caminho_imagem)) {
        unlink($caminho_imagem);
    }
}

// 2. Excluir registro do banco
if($projeto->delete($id)) {
    $_SESSION['sucesso'] = "Projeto excluído com sucesso!";
} else {
    $_SESSION['erro'] = "Erro ao excluir o projeto do banco de dados.";
}

header("Location: index.php");
exit();
?>