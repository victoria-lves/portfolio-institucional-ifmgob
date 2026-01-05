<?php

session_start();

// 1. Verificações de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

// Apenas Admin ou Professor podem acessar
if ($_SESSION['usuario_nivel'] != 'admin' && $_SESSION['usuario_nivel'] != 'professor') {
    $_SESSION['erro'] = "Acesso não autorizado!";
    header("Location: ../painel.php");
    exit();
}

// Se for professor, precisa ter o perfil criado na tabela 'professor'
if ($_SESSION['usuario_nivel'] == 'professor' && !isset($_SESSION['professor_id'])) {
    $_SESSION['erro'] = "Complete seu perfil de docente antes de cadastrar projetos!";
    header("Location: ../professor/create.php?completar=1");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();

// 2. Preparação de Dados (Lista de Professores para o Select)
$profModel = new Professor($db);
$stmt = $profModel->listar();
$professores_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nome padrão para o campo de texto Autor
$nome_autor_padrao = '';
if ($_SESSION['usuario_nivel'] == 'professor') {
    // Busca o nome do professor logado
    foreach ($professores_lista as $p) {
        if ($p['id'] == $_SESSION['professor_id']) {
            $nome_autor_padrao = $p['nome'];
            break;
        }
    }
}

// 3. Processamento do Formulário (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // --- VALIDAÇÃO (DATA_INICIO REMOVIDA) ---
        if (empty($_POST['titulo']) || empty($_POST['descricao']) || empty($_POST['area_conhecimento'])) {
            throw new Exception("Preencha os campos obrigatórios (Título, Área e Descrição).");
        }

        // LÓGICA DE MULTIPLOS PROFESSORES
        $ids_professores = [];

        if (isset($_POST['professores']) && is_array($_POST['professores'])) {
            $ids_professores = $_POST['professores'];
        }

        // Se for professor logado, garante que ele está na lista
        if ($_SESSION['usuario_nivel'] == 'professor') {
            if (!in_array($_SESSION['professor_id'], $ids_professores)) {
                array_unshift($ids_professores, $_SESSION['professor_id']);
            }
        }

        // Verifica se tem pelo menos um autor
        if (empty($ids_professores)) {
            throw new Exception("Selecione pelo menos um professor autor.");
        }

        // ==========================================================
        // ETAPA 1: INSERIR O PROJETO
        // ==========================================================
        $query = "INSERT INTO projeto (
            titulo, autor, descricao, data_inicio, status, data_fim,
            links, parceria, objetivos, resultados, area_conhecimento,
            alunos_envolvidos, agencia_financiadora, financiamento
        ) VALUES (
            :titulo, :autor, :descricao, :data_inicio, :status, :data_fim,
            :links, :parceria, :objetivos, :resultados, :area_conhecimento,
            :alunos_envolvidos, :agencia_financiadora, :financiamento
        )";

        $stmt = $db->prepare($query);

        // Tratamento da Data Fim para NULL se estiver vazia
        $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

        // Tratamento da Data Início para NULL se estiver vazia
        $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;

        $params = [
            ':titulo' => $_POST['titulo'],
            ':autor' => $_POST['autor'],
            ':descricao' => $_POST['descricao'],
            ':data_inicio' => $data_inicio,
            ':status' => $_POST['status'],
            ':data_fim' => $data_fim,
            ':links' => $_POST['links'] ?? null,
            ':parceria' => $_POST['parceria'] ?? null,
            ':objetivos' => $_POST['objetivos'] ?? null,
            ':resultados' => $_POST['resultados'] ?? null,
            ':area_conhecimento' => $_POST['area_conhecimento'],
            ':alunos_envolvidos' => $_POST['alunos_envolvidos'] ?? null,
            ':agencia_financiadora' => $_POST['agencia_financiadora'] ?? null,
            ':financiamento' => !empty($_POST['financiamento']) ? str_replace(',', '.', $_POST['financiamento']) : null
        ];

        if (!$stmt->execute($params)) {
            // Se der erro no execute, lança exceção para cair no catch
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erro SQL ao inserir projeto: " . $errorInfo[2]);
        }

        $id_projeto_criado = $db->lastInsertId();

        // ==========================================================
        // ETAPA 2: VINCULAR PROFESSORES
        // ==========================================================
        $sql_vinculo = "INSERT INTO professor_projeto (id_professor, id_projeto) VALUES (:id_prof, :id_proj)";
        $stmt_vinculo = $db->prepare($sql_vinculo);

        foreach ($ids_professores as $id_prof) {
            if (
                !$stmt_vinculo->execute([
                    ':id_prof' => $id_prof,
                    ':id_proj' => $id_projeto_criado
                ])
            ) {
                $errorInfo = $stmt_vinculo->errorInfo();
                throw new Exception("Erro SQL ao vincular professor ($id_prof): " . $errorInfo[2]);
            }
        }

        // ==========================================================
        // ETAPA 3: SALVAR A IMAGEM
        // ==========================================================
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extensao, $permitidos)) {
                $diretorio_upload = "../../img/projetos/";
                if (!is_dir($diretorio_upload))
                    mkdir($diretorio_upload, 0777, true);

                $novo_nome = uniqid('proj_') . '.' . $extensao;

                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $diretorio_upload . $novo_nome)) {
                    $sql_img = "INSERT INTO imagens (caminho, id_projeto, legenda) VALUES (:caminho, :id_projeto, :legenda)";
                    $stmt_img = $db->prepare($sql_img);
                    if (
                        !$stmt_img->execute([
                            ':caminho' => $novo_nome,
                            ':id_projeto' => $id_projeto_criado,
                            ':legenda' => 'Capa'
                        ])
                    ) {
                        throw new Exception("Erro SQL ao salvar imagem no banco.");
                    }
                } else {
                    throw new Exception("Erro ao mover arquivo de imagem para a pasta.");
                }
            }
        }

        $db->commit();
        $_SESSION['sucesso'] = "Projeto cadastrado com sucesso!";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();

        // =====================================================
        // BLOCO DE DEBUG - MOSTRA O ERRO NA TELA
        // =====================================================
        echo "<div style='background:white; color:black; padding:20px;'>";
        echo "<h1>ERRO AO SALVAR!</h1>";
        echo "<h3>Mensagem:</h3>";
        echo "<pre>" . $e->getMessage() . "</pre>";
        echo "<h3>Info do Banco de Dados:</h3>";
        print_r($db->errorInfo());
        echo "</div>";
        die(); // Para a execução aqui
        // =====================================================

        $erro = $e->getMessage();
        $dados_form = $_POST;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Projeto - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
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
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            color: var(--ifmg-azul);
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2"><i class="bi bi-folder-plus me-2"></i> Novo Projeto</h1>
                <p class="mb-0">Cadastre um novo projeto de pesquisa ou extensão</p>
            </div>
            <a href="index.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Cancelar</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="formProjeto">
            <div class="row">
                <div class="col-lg-8">
                    <div class="form-card">

                        <div class="alert alert-light border-primary border mb-4">
                            <label class="form-label fw-bold text-primary required">Autores do Projeto
                                (Professores)</label>

                            <select class="selectpicker form-control" name="professores[]" id="selectProfessor" multiple
                                data-live-search="true" data-selected-text-format="count > 3"
                                title="Selecione um ou mais professores..." required onchange="atualizarCampoAutor()">

                                <?php foreach ($professores_lista as $prof): ?>
                                    <?php
                                    $selected = '';
                                    // Se for professor logado ou se já foi enviado no post (em caso de erro)
                                    if (
                                        ($_SESSION['usuario_nivel'] == 'professor' && $prof['id'] == $_SESSION['professor_id']) ||
                                        (isset($dados_form['professores']) && in_array($prof['id'], $dados_form['professores']))
                                    ) {
                                        $selected = 'selected';
                                    }
                                    ?>
                                    <option value="<?php echo $prof['id']; ?>"
                                        data-nome="<?php echo htmlspecialchars($prof['nome']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">O campo "Autor Principal" abaixo será preenchido automaticamente com
                                os nomes.</div>
                        </div>

                        <h5 class="section-title">Dados Básicos</h5>

                        <div class="mb-3">
                            <label for="titulo" class="form-label required">Título do Projeto</label>
                            <input type="text" class="form-control" name="titulo" id="titulo"
                                value="<?php echo htmlspecialchars($dados_form['titulo'] ?? ''); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="area_conhecimento" class="form-label required">Área de Conhecimento</label>
                                <select class="form-select" name="area_conhecimento" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $areas = ['Administração', 'Humanas', 'Informática', 'Linguagens', 'Matemática', 'Metalurgia', 'Naturezas', 'Outros'];
                                    $area_selecionada = $dados_form['area_conhecimento'] ?? '';

                                    foreach ($areas as $area) {
                                        $selected = ($area_selecionada == $area) ? 'selected' : '';
                                        echo "<option value='$area' $selected>$area</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label required">Status Atual</label>
                                <select class="form-select" name="status" required>
                                    <option value="Em andamento">Em andamento</option>
                                    <option value="Concluído">Concluído</option>
                                    <option value="Pausado">Pausado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label required">Resumo / Descrição</label>
                            <textarea class="form-control" name="descricao" rows="5"
                                required><?php echo htmlspecialchars($dados_form['descricao'] ?? ''); ?></textarea>
                        </div>

                        <h5 class="section-title mt-4">Detalhes Acadêmicos</h5>

                        <div class="mb-3">
                            <label for="objetivos" class="form-label">Objetivos</label>
                            <textarea class="form-control" name="objetivos"
                                rows="3"><?php echo htmlspecialchars($dados_form['objetivos'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="resultados" class="form-label">Resultados Esperados/Obtidos</label>
                            <textarea class="form-control" name="resultados"
                                rows="3"><?php echo htmlspecialchars($dados_form['resultados'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="parceria" class="form-label">Parcerias</label>
                            <input type="text" class="form-control" name="parceria"
                                value="<?php echo htmlspecialchars($dados_form['parceria'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-card">
                        <h5 class="section-title">Configurações</h5>

                        <div class="mb-3">
                            <label for="autor" class="form-label required">Autor Principal (Texto)</label>
                            <input type="text" class="form-control" name="autor" id="campoAutor"
                                value="<?php echo htmlspecialchars($dados_form['autor'] ?? $nome_autor_padrao); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="data_inicio" class="form-label">Data Início</label>
                            <input type="date" class="form-control" name="data_inicio"
                                value="<?php echo htmlspecialchars($dados_form['data_inicio'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="data_fim" class="form-label">Data Fim (Opcional)</label>
                            <input type="date" class="form-control" name="data_fim"
                                value="<?php echo htmlspecialchars($dados_form['data_fim'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="imagem" class="form-label">Imagem de Capa</label>
                            <input type="file" class="form-control" name="imagem" accept="image/*">
                            <div class="form-text small">Max: 2MB.</div>
                        </div>

                        <div class="mb-3">
                            <label for="links" class="form-label">Links</label>
                            <input type="text" class="form-control" name="links" placeholder="http://..."
                                value="<?php echo htmlspecialchars($dados_form['links'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-card">
                        <h5 class="section-title">Financiamento</h5>
                        <div class="mb-3">
                            <label class="form-label">Alunos Envolvidos</label>
                            <textarea class="form-control" name="alunos_envolvidos"
                                rows="2"><?php echo htmlspecialchars($dados_form['alunos_envolvidos'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Agência</label>
                            <input type="text" class="form-control" name="agencia_financiadora"
                                value="<?php echo htmlspecialchars($dados_form['agencia_financiadora'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor (R$)</label>
                            <input type="number" step="0.01" class="form-control" name="financiamento"
                                placeholder="0,00"
                                value="<?php echo htmlspecialchars($dados_form['financiamento'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Salvar Projeto</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

    <script>
        $(document).ready(function () {
            if ($.fn.selectpicker) {
                $('.selectpicker').selectpicker();
            }
            // Atualiza o campo autor ao carregar se já tiver dados
            atualizarCampoAutor();
        });

        function atualizarCampoAutor() {
            var selectedOptions = document.querySelectorAll('#selectProfessor option:checked');
            var nomes = [];
            selectedOptions.forEach(function (option) {
                nomes.push(option.getAttribute('data-nome'));
            });

            var campoAutor = document.getElementById('campoAutor');
            if (nomes.length > 0) {
                campoAutor.value = nomes.join(', ');
                campoAutor.style.backgroundColor = "#e8f0fe";
                setTimeout(() => { campoAutor.style.backgroundColor = ""; }, 500);
            }
        }
    </script>
</body>

</html>