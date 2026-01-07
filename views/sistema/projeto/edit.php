<?php
// views/sistema/projeto/edit.php - Editar Projeto

session_start();

// 1. Verificações de Acesso
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    // Redireciona para a listagem
    header("Location: index.php"); 
    exit();
}

// 2. Configuração e Models (Caminho ajustado para 3 níveis)
require_once '../../../config/database.php';
require_once '../../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();
$id_projeto = (int)$_GET['id'];

// 3. Buscar Dados do Projeto
$stmt = $db->prepare("SELECT * FROM projeto WHERE id = ? LIMIT 1");
$stmt->execute([$id_projeto]);
$projeto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$projeto) {
    $_SESSION['erro'] = "Projeto não encontrado.";
    header("Location: index.php");
    exit();
}

// 4. Buscar Autores Vinculados (IDs)
$stmtAuth = $db->prepare("SELECT id_professor FROM professor_projeto WHERE id_projeto = ?");
$stmtAuth->execute([$id_projeto]);
$autores_atuais = $stmtAuth->fetchAll(PDO::FETCH_COLUMN);

// 5. Verificar Permissão (Admin ou Autor do projeto)
$pode_editar = false;
if ($_SESSION['usuario_nivel'] == 'admin') {
    $pode_editar = true;
} elseif (isset($_SESSION['professor_id']) && in_array($_SESSION['professor_id'], $autores_atuais)) {
    $pode_editar = true;
}

if (!$pode_editar) {
    $_SESSION['erro'] = "Você não tem permissão para editar este projeto.";
    header("Location: index.php");
    exit();
}

// 6. Lista de Todos Professores (para o Select)
$profModel = new Professor($db);
$todos_professores = $profModel->listar()->fetchAll(PDO::FETCH_ASSOC);

// Mensagens Flash
$erro = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['erro'], $_SESSION['sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Projeto - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .btn-save {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        .bootstrap-select .dropdown-toggle {
            border: 1px solid #ced4da;
            background-color: white;
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-pencil-square me-2"></i> Editar Projeto</h1>
                    <p class="mb-0 text-white-50">Atualize as informações do projeto</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        
        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <form action="../../../controllers/ProjetoController.php?action=update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $id_projeto; ?>">
                    
                    <div class="form-card">
                        <h5 class="mb-4 pb-2 border-bottom text-primary">
                            <i class="bi bi-info-circle me-2"></i> Informações Básicas
                        </h5>

                        <div class="mb-4">
                            <label class="form-label required">Título do Projeto</label>
                            <input type="text" class="form-control" name="titulo" required 
                                   value="<?php echo htmlspecialchars($projeto['titulo']); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label required">Autores (Professores Vinculados)</label>
                            <select class="selectpicker form-control" name="professores[]" multiple data-live-search="true" data-style="btn-white" required>
                                <?php foreach ($todos_professores as $prof): ?>
                                    <option value="<?php echo $prof['id']; ?>" 
                                        <?php echo in_array($prof['id'], $autores_atuais) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione todos os professores participantes.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label required">Autor Principal (Exibição)</label>
                            <input type="text" class="form-control" name="autor" 
                                   value="<?php echo htmlspecialchars($projeto['autor']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Área de Conhecimento</label>
                                <select class="form-select" name="area_conhecimento" required>
                                    <?php 
                                    $areas = ['Administração', 'Humanas', 'Informática', 'Linguagens', 'Matemática', 'Metalurgia', 'Naturezas', 'Pedagogia', 'Outros'];
                                    foreach($areas as $area) {
                                        $sel = $projeto['area_conhecimento'] == $area ? 'selected' : '';
                                        echo "<option value='$area' $sel>$area</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Status Atual</label>
                                <select class="form-select" name="status" required>
                                    <option value="Em andamento" <?php echo $projeto['status'] == 'Em andamento' ? 'selected' : ''; ?>>Em andamento</option>
                                    <option value="Concluído" <?php echo $projeto['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                                    <option value="Pausado" <?php echo $projeto['status'] == 'Pausado' ? 'selected' : ''; ?>>Pausado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Resumo / Descrição</label>
                            <textarea class="form-control" name="descricao" rows="5" required><?php echo htmlspecialchars($projeto['descricao']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-card">
                        <h5 class="mb-4 pb-2 border-bottom text-primary">
                            <i class="bi bi-journal-text me-2"></i> Detalhes Acadêmicos
                        </h5>

                        <div class="mb-3">
                            <label class="form-label">Objetivos</label>
                            <textarea class="form-control" name="objetivos" rows="3"><?php echo htmlspecialchars($projeto['objetivos'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resultados Esperados / Alcançados</label>
                            <textarea class="form-control" name="resultados" rows="3"><?php echo htmlspecialchars($projeto['resultados'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alunos Envolvidos</label>
                            <input type="text" class="form-control" name="alunos_envolvidos" 
                                   value="<?php echo htmlspecialchars($projeto['alunos_envolvidos'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-card">
                        <h5 class="mb-4 pb-2 border-bottom text-primary">
                            <i class="bi bi-calendar-event me-2"></i> Gestão e Mídia
                        </h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Início</label>
                                <input type="date" class="form-control" name="data_inicio" 
                                       value="<?php echo $projeto['data_inicio']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Término</label>
                                <input type="date" class="form-control" name="data_fim" 
                                       value="<?php echo $projeto['data_fim']; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Agência Financiadora</label>
                                <input type="text" class="form-control" name="agencia_financiadora" 
                                       value="<?php echo htmlspecialchars($projeto['agencia_financiadora'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Valor do Financiamento (R$)</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" name="financiamento" 
                                           value="<?php echo $projeto['financiamento']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Parcerias Externas</label>
                            <input type="text" class="form-control" name="parceria" 
                                   value="<?php echo htmlspecialchars($projeto['parceria'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Links Relacionados</label>
                            <input type="text" class="form-control" name="links" 
                                   value="<?php echo htmlspecialchars($projeto['links'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Adicionar Imagens à Galeria</label>
                            <input type="file" class="form-control" name="imagens[]" multiple accept="image/*">
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> Segure <strong>CTRL</strong> para selecionar múltiplas imagens. As novas imagens serão adicionadas à galeria existente.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mb-5">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-check-circle me-1"></i> Salvar Alterações
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    
    <script>
        $(document).ready(function () {
            $('.selectpicker').selectpicker();
        });
    </script>
</body>

</html>