<?php
session_start();

// 1. Verificações de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Caminhos e Imports
require_once '../../../config/database.php';
require_once '../../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();

// 3. Buscar Professores (Apenas se for Admin)
$professores = [];
if ($_SESSION['usuario_nivel'] == 'admin') {
    $profModel = new Professor($db);
    $stmt = $profModel->listar();
    $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Se for professor, verifica se tem perfil
    if (!isset($_SESSION['professor_id'])) {
        $_SESSION['erro'] = "Complete seu perfil docente antes de cadastrar produções!";
        header("Location: ../professor/create.php?completar=1");
        exit();
    }
}

// 4. Listas Auxiliares
$tipos = ['Livro', 'Artigo', 'Tese', 'Outro'];
$idiomas = ['Português (pt-br)', 'Inglês', 'Espanhol', 'Outro'];

// 5. Capturar Mensagens da Sessão
$erro = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['erro'], $_SESSION['sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Produção - IFMG</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    
    <style>
        :root { --ifmg-azul: #1a2980; --ifmg-verde: #26d0ce; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .page-header { background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
        .form-card { background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); padding: 30px; margin-bottom: 30px; }
        .required::after { content: " *"; color: #dc3545; }
        .campo-condicional { display: none; animation: fadeIn 0.3s ease-in-out; }
        .campo-condicional.visivel { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .btn-save { background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); color: white; }
    </style>
</head>
<body>

    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2"><i class="bi bi-journal-plus me-2"></i> Nova Produção</h1>
                <p class="mb-0">Cadastre artigos, livros, teses e outros trabalhos</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
                <a href="../painel.php" class="btn btn-outline-light ms-2">Painel</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        
        <?php if($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <form action="../../../controllers/ProducaoController.php?action=create" method="POST" id="formProducao">
                    
                    <div class="form-card">
                        <h5 class="mb-4 border-bottom pb-2 text-primary"><i class="bi bi-info-circle me-2"></i> Informações da Obra</h5>
                        
                        <?php if($_SESSION['usuario_nivel'] == 'admin'): ?>
                        <div class="mb-3">
                            <label for="id_professor" class="form-label required">Professor Autor</label>
                            <select class="selectpicker form-control" id="id_professor" name="id_professor" data-live-search="true" required>
                                <option value="">Selecione...</option>
                                <?php foreach($professores as $prof): ?>
                                    <option value="<?php echo $prof['id']; ?>">
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="titulo" class="form-label required">Título da Produção</label>
                            <input type="text" class="form-control" name="titulo" required placeholder="Ex: Análise de Algoritmos...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="autor" class="form-label required">Autor(es)</label>
                            <input type="text" class="form-control" name="autor" required placeholder="Ex: SILVA, J.; SANTOS, M.">
                            <div class="form-text">Liste os autores conforme normas da ABNT.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label required">Tipo</label>
                                <select class="form-select" id="tipo" name="tipo" required onchange="toggleOutro('tipo')">
                                    <option value="">Selecione...</option>
                                    <?php foreach($tipos as $t): ?>
                                        <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="data_pub" class="form-label">Data de Publicação</label>
                                <input type="date" class="form-control" name="data_pub">
                            </div>
                        </div>
                        
                        <div class="mb-3 campo-condicional" id="div-tipo-outro">
                            <label for="tipo_outro" class="form-label required">Especifique o Tipo</label>
                            <input type="text" class="form-control" id="tipo_outro" name="tipo_outro" placeholder="Ex: Capítulo de Livro...">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="idioma" class="form-label required">Idioma</label>
                                <select class="form-select" id="idioma" name="idioma" required onchange="toggleOutro('idioma')">
                                    <option value="">Selecione...</option>
                                    <?php foreach($idiomas as $i): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="link" class="form-label">Link de Acesso</label>
                                <input type="url" class="form-control" name="link" placeholder="https://...">
                            </div>
                        </div>
                        
                        <div class="mb-3 campo-condicional" id="div-idioma-outro">
                            <label for="idioma_outro" class="form-label required">Especifique o Idioma</label>
                            <input type="text" class="form-control" id="idioma_outro" name="idioma_outro" placeholder="Ex: Francês...">
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn-save">
                                <i class="bi bi-check-lg me-2"></i> Salvar Produção
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    
    <script>
        $(document).ready(function() {
            if($.fn.selectpicker) { $('.selectpicker').selectpicker(); }
        });

        function toggleOutro(campo) {
            const select = document.getElementById(campo);
            const divOutro = document.getElementById('div-' + campo + '-outro');
            const inputOutro = document.getElementById(campo + '_outro');
            
            if (select.value === 'Outro') {
                divOutro.classList.add('visivel');
                inputOutro.required = true;
            } else {
                divOutro.classList.remove('visivel');
                inputOutro.required = false;
                inputOutro.value = '';
            }
        }
        
        // Validação extra no submit para campos "Outro"
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
