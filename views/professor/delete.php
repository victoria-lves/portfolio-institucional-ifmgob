<?php
session_start();

// APENAS ADMIN PODE EXCLUIR PROFESSORES
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] != 'admin') {
    header("Location: ../sistema/painel.php");
    exit();
}

if (!isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

require_once '../../../config/database.php';
require_once '../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();
$professor = new Professor($db);

$id = $_POST['id'];
$dados = $professor->buscarPorId($id);

if (!$dados) {
    $_SESSION['erro'] = "Professor não encontrado.";
    header("Location: index.php");
    exit();
}

try {
    // 1. Tentar excluir a imagem de perfil
    if (!empty($dados['foto_perfil'])) {
        $caminho_foto = "../../assets/img/docentes/" . $dados['foto_perfil'];
        // Verifica se não é uma imagem padrão antes de deletar (opcional)
        if (file_exists($caminho_foto)) {
            unlink($caminho_foto);
        }
    }

    // 2. Excluir registro do banco
    // OBS: Se o banco não tiver CASCADE configurado nas chaves estrangeiras,
    // isso falhará se o professor tiver projetos ou produções.
    if ($professor->delete($id)) {
        $_SESSION['sucesso'] = "Professor excluído com sucesso!";
    } else {
        throw new Exception("Não foi possível excluir. Verifique se o professor possui vínculos ativos.");
    }

} catch (PDOException $e) {
    // Captura erro de chave estrangeira (Integrity constraint violation)
    if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
        $_SESSION['erro'] = "Não é possível excluir: Este professor possui Projetos ou Produções vinculadas. Exclua-os primeiro.";
    } else {
        $_SESSION['erro'] = "Erro no banco de dados: " . $e->getMessage();
    }
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}

header("Location: index.php");
exit();
?>