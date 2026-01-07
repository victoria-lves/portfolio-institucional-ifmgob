<?php
// views/producao/edit.php
session_start();

// 1. Verificação de Acesso
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id_producao = (int)$_GET['id'];

// 2. Buscar Dados da Produção
$stmt = $db->prepare("SELECT * FROM producao WHERE id = ? LIMIT 1");
$stmt->execute([$id_producao]);
$producao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producao) {
    $_SESSION['erro'] = "Produção não encontrada.";
    header("Location: index.php");
    exit();
}

// 3. Verificar Permissão (Admin ou Dono)
$pode_editar = ($_SESSION['usuario_nivel'] == 'admin') || 
               (isset($_SESSION['professor_id']) && $producao['id_professor'] == $_SESSION['professor_id']);

if (!$pode_editar) {
    $_SESSION['erro'] = "Você não tem permissão para editar este registro.";
    header("Location: index.php");
    exit();
}

$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Produção - IFMG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .page-header { background: linear-gradient(90deg, #1a2980, #26d0ce); color: white; padding: 30px 0; margin-bottom: 30px; }
        .form-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <h3>Editar Produção</h3>
            <a href="index.php" class="btn btn-outline-light btn-sm">Voltar</a>
        </div>
    </div>

    <div class="container">
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form action="../../../controllers/ProducaoController.php?action=update" method="POST" class="form-card">
                    <input type="hidden" name="id" value="<?php echo $id_producao; ?>">

                    <div class="mb-3">
                        <label class="form-label">Título da Obra *</label>
                        <input type="text" class="form-control" name="titulo" value="<?php echo htmlspecialchars($producao['titulo']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" name="tipo" id="tipoSelect" required>
                                <option value="Artigo" <?php echo $producao['tipo'] == 'Artigo' ? 'selected' : ''; ?>>Artigo</option>
                                <option value="Livro" <?php echo $producao['tipo'] == 'Livro' ? 'selected' : ''; ?>>Livro</option>
                                <option value="Tese" <?php echo $producao['tipo'] == 'Tese' ? 'selected' : ''; ?>>Tese/Dissertação</option>
                                <option value="Outro" <?php echo $producao['tipo'] == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="divOutroTipo" style="display: <?php echo $producao['tipo'] == 'Outro' ? 'block' : 'none'; ?>;">
                            <label class="form-label">Especifique o Tipo</label>
                            <input type="text" class="form-control" name="tipo_outro" value="<?php echo htmlspecialchars($producao['tipo_outro']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autores (Texto) *</label>
                        <input type="text" class="form-control" name="autor" value="<?php echo htmlspecialchars($producao['autor']); ?>" required>
                        <div class="form-text">Ex: SILVA, João; SANTOS, Maria.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Publicação</label>
                            <input type="date" class="form-control" name="data_pub" value="<?php echo $producao['data_pub']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Idioma</label>
                            <select class="form-select" name="idioma">
                                <option value="Português (pt-br)" <?php echo $producao['idioma'] == 'Português (pt-br)' ? 'selected' : ''; ?>>Português</option>
                                <option value="Inglês" <?php echo $producao['idioma'] == 'Inglês' ? 'selected' : ''; ?>>Inglês</option>
                                <option value="Espanhol" <?php echo $producao['idioma'] == 'Espanhol' ? 'selected' : ''; ?>>Espanhol</option>
                                <option value="Outro" <?php echo $producao['idioma'] == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Link de Acesso (DOI/URL)</label>
                        <input type="url" class="form-control" name="link" value="<?php echo htmlspecialchars($producao['link']); ?>">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mostrar campo 'Outro' se selecionado
        document.getElementById('tipoSelect').addEventListener('change', function() {
            var display = this.value === 'Outro' ? 'block' : 'none';
            document.getElementById('divOutroTipo').style.display = display;
        });
    </script>
</body>
</html>