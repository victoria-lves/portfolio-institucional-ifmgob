<?php

session_start();

// 1. Verificações de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Verifica se professor completou perfil
if ($_SESSION['usuario_nivel'] == 'professor' && !isset($_SESSION['professor_id'])) {
    $_SESSION['erro'] = "Complete seu perfil de docente antes de cadastrar projetos!";
    header("Location: ../professor/create.php?completar=1");
    exit();
}

// 2. Configuração e Models
require_once '../../../config/database.php';
require_once '../../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();

// Buscar professores para o Select (Autores)
$profModel = new Professor($db);
$stmt = $profModel->listar();
$professores_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Projeto - IFMG</title>

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
                    <h1 class="h3 mb-1"><i class="bi bi-folder-plus me-2"></i> Novo Projeto</h1>
                    <p class="mb-0 text-white-50">Cadastro de projetos de pesquisa e extensão</p>
                </div>
                <div>
                    <a href="../projeto.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <form action="../../../controllers/ProjetoController.php?action=create" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-card">
                        <h5 class="mb-4 pb-2 border-bottom text-primary">
                            <i class="bi bi-info-circle me-2"></i> Informações Básicas
                        </h5>

                        <div class="mb-4">
                            <label class="form-label required">Título do Projeto</label>
                            <input type="text" class="form-control" name="titulo" required placeholder="Digite o título completo do projeto">
                        </div>

                        <div class="mb-4">
                            <label class="form-label required">Autores (Professores Vinculados)</label>
                            <select class="selectpicker form-control" name="professores[]" multiple data-live-search="true" data-style="btn-white" title="Selecione os professores..." required>
                                <?php foreach ($professores_lista as $prof): ?>
                                    <option value="<?php echo $prof['id']; ?>" 
                                        <?php echo ($_SESSION['usuario_nivel'] == 'professor' && $prof['id'] == $_SESSION['professor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione todos os professores participantes.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label required">Autor Principal (Exibição)</label>
                            <input type="text" class="form-control" name="autor" id="campoAutor" 
                                   value="<?php echo $_SESSION['usuario_nome'] ?? ''; ?>" required>
                            <div class="form-text">Nome que aparecerá em destaque nos cards (ex: Nome do Coordenador).</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Área de Conhecimento</label>
                                <select class="form-select" name="area_conhecimento" required>
                                    <option value="">Selecione...</option>
                                    <option value="Administração">Administração</option>
                                    <option value="Humanas">Humanas</option>
                                    <option value="Informática">Informática</option>
                                    <option value="Linguagens">Linguagens</option>
                                    <option value="Matemática">Matemática</option>
                                    <option value="Metalurgia">Metalurgia</option>
                                    <option value="Naturezas">Naturezas</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Status Atual</label>
                                <select class="form-select" name="status" required>
                                    <option value="Em andamento">Em andamento</option>
                                    <option value="Concluído">Concluído</option>
                                    <option value="Pausado">Pausado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Resumo / Descrição</label>
                            <textarea class="form-control" name="descricao" rows="5" required placeholder="Descreva brevemente o projeto..."></textarea>
                        </div>
                    </div>

                    <div class="form-card">
                        <h5 class="mb-4 pb-2 border-bottom text-primary">
                            <i class="bi bi-journal-text me-2"></i> Detalhes Acadêmicos
                        </h5>

                        <div class="mb-3">
                            <label class="form-label">Objetivos</label>
                            <textarea class="form-control" name="objetivos" rows="3" placeholder="Quais são os objetivos principais?"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resultados Esperados / Alcançados</label>
                            <textarea class="form-control" name="resultados" rows="3" placeholder="Resultados obtidos ou esperados..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alunos Envolvidos</label>
                            <input type="text" class="form-control" name="alunos_envolvidos" placeholder="Nomes dos alunos bolsistas ou voluntários">
                        </div>
                    </div>

                    <div class="form-card">
                        <h5 class="mb-4 pb-2 border-bottom text-primary">
                            <i class="bi bi-calendar-check me-2"></i> Gestão e Mídia
                        </h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Início</label>
                                <input type="date" class="form-control" name="data_inicio">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Término (Previsão)</label>
                                <input type="date" class="form-control" name="data_fim">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Agência Financiadora</label>
                                <input type="text" class="form-control" name="agencia_financiadora" placeholder="Ex: IFMG, FAPEMIG, CNPq...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Valor do Financiamento (R$)</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" name="financiamento" placeholder="0,00">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Parcerias Externas</label>
                            <input type="text" class="form-control" name="parceria" placeholder="Empresas ou instituições parceiras">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Links Relacionados</label>
                            <input type="text" class="form-control" name="links" placeholder="Link para site, repositório ou publicação">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Galeria de Imagens</label>
                            <input type="file" class="form-control" name="imagens[]" multiple accept="image/*">
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> Segure <strong>CTRL</strong> para selecionar múltiplas imagens. A primeira imagem selecionada será usada como capa.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mb-5">
                        <a href="../projeto.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-save">
                            <i class="bi bi-check-circle me-1"></i> Salvar Projeto
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
            // Inicializa o selectpicker
            $('.selectpicker').selectpicker();
        });
    </script>
</body>

</html>
