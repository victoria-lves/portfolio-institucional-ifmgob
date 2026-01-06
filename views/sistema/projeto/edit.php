<?php
// views/projeto/edit.php
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

require_once '../../../config/database.php';
require_once '../../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();
$id_projeto = (int)$_GET['id'];

// Buscar Projeto
$stmt = $db->prepare("SELECT * FROM projeto WHERE id = ?");
$stmt->execute([$id_projeto]);
$projeto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$projeto) die("Projeto não encontrado.");

// Buscar Autores
$stmtAuth = $db->prepare("SELECT id_professor FROM professor_projeto WHERE id_projeto = ?");
$stmtAuth->execute([$id_projeto]);
$autores_atuais = $stmtAuth->fetchAll(PDO::FETCH_COLUMN);

// Verificar Permissão (Admin ou um dos autores)
$pode_editar = ($_SESSION['usuario_nivel'] == 'admin') || (isset($_SESSION['professor_id']) && in_array($_SESSION['professor_id'], $autores_atuais));

if (!$pode_editar) {
    die("Acesso negado.");
}

// Lista para select
$profModel = new Professor($db);
$todos_professores = $profModel->listar()->fetchAll(PDO::FETCH_ASSOC);

$sucesso = $_SESSION['sucesso'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['sucesso'], $_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Projeto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
</head>
<body>
    <div class="container mt-5">
        <?php if ($sucesso): ?> <div class="alert alert-success"><?php echo $sucesso; ?></div> <?php endif; ?>
        <?php if ($erro): ?> <div class="alert alert-danger"><?php echo $erro; ?></div> <?php endif; ?>

        <form action="../../controllers/ProjetoController.php?action=update" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id_projeto; ?>">

            <h3 class="mb-4">Editar Projeto: <?php echo htmlspecialchars($projeto['titulo']); ?></h3>

            <div class="mb-3">
                <label class="form-label">Autores *</label>
                <select class="selectpicker form-control" name="professores[]" multiple data-live-search="true" required>
                    <?php foreach ($todos_professores as $prof): ?>
                        <option value="<?php echo $prof['id']; ?>" 
                            <?php echo in_array($prof['id'], $autores_atuais) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prof['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="titulo" value="<?php echo htmlspecialchars($projeto['titulo']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Autor (Texto)</label>
                <input type="text" class="form-control" name="autor" value="<?php echo htmlspecialchars($projeto['autor']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descrição</label>
                <textarea class="form-control" name="descricao" rows="5"><?php echo htmlspecialchars($projeto['descricao']); ?></textarea>
            </div>
            
             <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Área</label>
                    <select class="form-select" name="area_conhecimento">
                        <?php 
                        $areas = ['Administração', 'Humanas', 'Informática', 'Linguagens', 'Matemática', 'Metalurgia', 'Naturezas', 'Outros'];
                        foreach($areas as $area) {
                            $sel = $projeto['area_conhecimento'] == $area ? 'selected' : '';
                            echo "<option value='$area' $sel>$area</option>";
                        }
                        ?>
                    </select>
                </div>
                 <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="Em andamento" <?php echo $projeto['status'] == 'Em andamento' ? 'selected' : ''; ?>>Em andamento</option>
                        <option value="Concluído" <?php echo $projeto['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Adicionar Imagem</label>
                <input type="file" class="form-control" name="imagem">
            </div>

            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    <script> $(document).ready(function () { $('.selectpicker').selectpicker(); }); </script>
</body>
</html>