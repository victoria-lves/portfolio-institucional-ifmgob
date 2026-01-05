<?php
// ==============================================
// views/producao/create.php - Cadastrar Produção
// ==============================================

session_start();

// Verificar se está logado
if(!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

// Verificar permissão (admin ou professor)
if($_SESSION['usuario_nivel'] != 'admin' && $_SESSION['usuario_nivel'] != 'professor') {
    header("Location: ../sistema/painel.php");
    exit();
}

require_once '../../../config/database.php';
require_once '../../controllers/ProducaoController.php';
require_once '../../models/Professor.php'; // Para admin selecionar professor

$database = new Database();
$db = $database->getConnection();
$controller = new ProducaoController($db);

// Se for admin, buscar lista de professores
$professores = [];
if($_SESSION['usuario_nivel'] == 'admin') {
    $prof_model = new Professor($db);
    $stmt_p = $prof_model->listar();
    $professores = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Se for professor, verificar se tem perfil configurado
    if(!isset($_SESSION['professor_id'])) {
        $_SESSION['erro'] = "Complete seu perfil antes de cadastrar produções!";
        header("Location: ../professor/create.php?completar=1");
        exit();
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Definir o ID do professor
    if($_SESSION['usuario_nivel'] == 'professor') {
        $_POST['id_professor'] = $_SESSION['professor_id'];
    }
    
    // Tentar criar
    $result = $controller->create($_POST);
    
    if ($result['success']) {
        $_SESSION['sucesso'] = "Produção acadêmica cadastrada com sucesso!";
        header("Location: index.php");
        exit();
    } else {
        $erro = $result['message'];
    }
}

// Listas auxiliares
$tipos = $controller->getTipos();
$idiomas = $controller->getIdiomas();

// Recuperar dados do POST em caso de erro
$titulo = $_POST['titulo'] ?? '';
$autor = $_POST['autor'] ?? '';
$data_pub = $_POST['data_pub'] ?? '';
$tipo_selecionado = $_POST['tipo'] ?? '';
$tipo_outro = $_POST['tipo_outro'] ?? '';
$idioma_selecionado = $_POST['idioma'] ?? '';
$idioma_outro = $_POST['idioma_outro'] ?? '';
$link = $_POST['link'] ?? '';
$id_prof_selecionado = $_POST['id_professor'] ?? '';

// Mensagens
$erro = $erro ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Produção - IFMG</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--ifmg-azul);
            box-shadow: 0 0 0 0.25rem rgba(26, 41, 128, 0.25);
        }
        
        .btn-save {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .campo-condicional {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .campo-condicional.visivel {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--ifmg-azul);
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="bi bi-journal-plus me-2"></i>
                        Nova Produção
                    </h1>
                    <p class="mb-0">Cadastre artigos, livros, teses e outros trabalhos</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Ver produções
                    </a>
                    <a href="../sistema/painel.php" class="btn btn-outline-light ms-2">
                        <i class="bi bi-arrow-left me-1"></i> Painel
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <form method="POST" id="formProducao">
                    <div class="form-card p-4">
                        <h5 class="mb-4 border-bottom pb-2 text-primary">
                            <i class="bi bi-info-circle me-2"></i> Informações da Obra
                        </h5>
                        
                        <?php if($_SESSION['usuario_nivel'] == 'admin'): ?>
                        <div class="mb-3">
                            <label for="id_professor" class="form-label required">Professor Autor</label>
                            <select class="form-select" id="id_professor" name="id_professor" required>
                                <option value="">Selecione o professor...</option>
                                <?php foreach($professores as $prof): ?>
                                    <option value="<?php echo $prof['id']; ?>" 
                                        <?php echo ($id_prof_selecionado == $prof['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Selecione a qual professor esta produção pertence.</div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="titulo" class="form-label required">Título da Produção</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo htmlspecialchars($titulo); ?>" required
                                   placeholder="Ex: Análise de Algoritmos em PHP">
                        </div>
                        
                        <div class="mb-3">
                            <label for="autor" class="form-label required">Autor(es)</label>
                            <input type="text" class="form-control" id="autor" name="autor" 
                                   value="<?php echo htmlspecialchars($autor); ?>" required
                                   placeholder="Ex: SILVA, J.; SANTOS, M.">
                            <div class="form-text">Liste os autores conforme normas da ABNT ou formato preferido.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label required">Tipo de Produção</label>
                                <select class="form-select" id="tipo" name="tipo" required onchange="toggleOutro('tipo')">
                                    <option value="">Selecione...</option>
                                    <?php foreach($tipos as $t): ?>
                                        <option value="<?php echo $t; ?>" <?php echo ($tipo_selecionado == $t) ? 'selected' : ''; ?>>
                                            <?php echo $t; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="data_pub" class="form-label">Data de Publicação</label>
                                <input type="date" class="form-control" id="data_pub" name="data_pub" 
                                       value="<?php echo htmlspecialchars($data_pub); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3 campo-condicional" id="div-tipo-outro">
                            <label for="tipo_outro" class="form-label required">Especifique o Tipo</label>
                            <input type="text" class="form-control" id="tipo_outro" name="tipo_outro" 
                                   value="<?php echo htmlspecialchars($tipo_outro); ?>"
                                   placeholder="Ex: Capítulo de Livro, Anais de Congresso...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="idioma" class="form-label required">Idioma</label>
                                <select class="form-select" id="idioma" name="idioma" required onchange="toggleOutro('idioma')">
                                    <option value="">Selecione...</option>
                                    <?php foreach($idiomas as $i): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($idioma_selecionado == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="link" class="form-label">Link de Acesso</label>
                                <input type="url" class="form-control" id="link" name="link" 
                                       value="<?php echo htmlspecialchars($link); ?>"
                                       placeholder="https://...">
                            </div>
                        </div>
                        
                        <div class="mb-3 campo-condicional" id="div-idioma-outro">
                            <label for="idioma_outro" class="form-label required">Especifique o Idioma</label>
                            <input type="text" class="form-control" id="idioma_outro" name="idioma_outro" 
                                   value="<?php echo htmlspecialchars($idioma_outro); ?>"
                                   placeholder="Ex: Francês, Alemão...">
                        </div>
                        
                        <div class="info-box mt-3 mb-4">
                            <small class="text-muted">
                                <i class="bi bi-lightbulb-fill text-warning me-1"></i>
                                <strong>Dica:</strong> Para melhor indexação no Google Scholar, preencha o link da publicação original (DOI ou repositório oficial).
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between pt-2">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-save">
                                <i class="bi bi-check-lg me-2"></i> Cadastrar Produção
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <div class="form-card p-4">
                    <h6 class="mb-3 fw-bold text-secondary">Instruções</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Preencha todos os campos obrigatórios marcados com (*).</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Se o tipo de produção não estiver na lista, selecione "Outro" e especifique.</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> O link externo facilita o acesso dos alunos e pesquisadores ao seu trabalho.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleOutro(campo) {
            const select = document.getElementById(campo);
            const divOutro = document.getElementById('div-' + campo + '-outro');
            const inputOutro = document.getElementById(campo + '_outro');
            
            if (select.value === 'Outro') {
                divOutro.classList.add('visivel');
                inputOutro.required = true;
                setTimeout(() => inputOutro.focus(), 100);
            } else {
                divOutro.classList.remove('visivel');
                inputOutro.required = false;
                inputOutro.value = ''; // Limpar valor ao esconder
            }
        }
        
        // Inicializar estado dos campos condicionais (caso de erro no post)
        document.addEventListener('DOMContentLoaded', function() {
            toggleOutro('tipo');
            toggleOutro('idioma');
        });
        
        // Validação extra no submit
        document.getElementById('formProducao').addEventListener('submit', function(e) {
            const tipoSelect = document.getElementById('tipo');
            const tipoOutro = document.getElementById('tipo_outro');
            
            if(tipoSelect.value === 'Outro' && !tipoOutro.value.trim()) {
                e.preventDefault();
                alert('Por favor, especifique o tipo da produção.');
                tipoOutro.focus();
            }
        });
    </script>
</body>
</html>