<?php

session_start();

// 1. Segurança Básica
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "Projeto não especificado.";
    header("Location: index.php");
    exit();
}

$id_projeto = (int) $_GET['id'];

require_once '../../../config/database.php';
require_once '../../models/Professor.php'; // Para listar todos no select

$database = new Database();
$db = $database->getConnection();

try {
    // 2. Buscar Dados do Projeto
    $query = "SELECT * FROM projeto WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_projeto);
    $stmt->execute();
    $projeto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projeto) {
        throw new Exception("Projeto não encontrado.");
    }

    // 3. Buscar Autores Atuais (IDs) para verificação de permissão e preencher form
    $query_autores = "SELECT id_professor FROM professor_projeto WHERE id_projeto = :id";
    $stmt_autores = $db->prepare($query_autores);
    $stmt_autores->execute([':id' => $id_projeto]);
    $autores_atuais = $stmt_autores->fetchAll(PDO::FETCH_COLUMN); // Retorna array simples: [1, 5, 8]

    // 4. Verificar Permissão (Admin ou Autor)
    $tem_permissao = false;
    if ($_SESSION['usuario_nivel'] == 'admin') {
        $tem_permissao = true;
    } elseif (isset($_SESSION['professor_id']) && in_array($_SESSION['professor_id'], $autores_atuais)) {
        $tem_permissao = true;
    }

    if (!$tem_permissao) {
        $_SESSION['erro'] = "Você não tem permissão para editar este projeto.";
        header("Location: index.php");
        exit();
    }

    // 5. Carregar Lista de Todos Professores (para o Select)
    $profModel = new Professor($db);
    $stmt_lista = $profModel->listar();
    $todos_professores = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
    header("Location: index.php");
    exit();
}

// ==============================================
// PROCESSAMENTO DO FORMULÁRIO (POST)
// ==============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Validação (REMOVIDO data_inicio DAQUI)
        if (empty($_POST['titulo']) || empty($_POST['descricao'])) {
            throw new Exception("Preencha os campos obrigatórios (Título e Descrição).");
        }

        // --- ATUALIZAR TABELA PROJETO ---
        $query_update = "UPDATE projeto SET 
            titulo = :titulo,
            autor = :autor,
            descricao = :descricao,
            data_inicio = :data_inicio,
            status = :status,
            data_fim = :data_fim,
            links = :links,
            parceria = :parceria,
            objetivos = :objetivos,
            resultados = :resultados,
            area_conhecimento = :area_conhecimento,
            alunos_envolvidos = :alunos_envolvidos,
            agencia_financiadora = :agencia_financiadora,
            financiamento = :financiamento
            WHERE id = :id";

        $stmt_up = $db->prepare($query_update);
        $stmt_up->execute([
            ':titulo' => $_POST['titulo'],
            ':autor' => $_POST['autor'],
            ':descricao' => $_POST['descricao'],
            // TRATAMENTO PARA NULO SE VAZIO
            ':data_inicio' => !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
            ':status' => $_POST['status'],
            ':data_fim' => !empty($_POST['data_fim']) ? $_POST['data_fim'] : null,
            ':links' => $_POST['links'] ?? null,
            ':parceria' => $_POST['parceria'] ?? null,
            ':objetivos' => $_POST['objetivos'] ?? null,
            ':resultados' => $_POST['resultados'] ?? null,
            ':area_conhecimento' => $_POST['area_conhecimento'] ?? null,
            ':alunos_envolvidos' => $_POST['alunos_envolvidos'] ?? null,
            ':agencia_financiadora' => $_POST['agencia_financiadora'] ?? null,
            ':financiamento' => !empty($_POST['financiamento']) ? str_replace(',', '.', $_POST['financiamento']) : null,
            ':id' => $id_projeto
        ]);

        // --- ATUALIZAR AUTORES (Muitos-para-Muitos) ---
        // 1. Pegar lista enviada
        $ids_novos_autores = $_POST['professores'] ?? [];

        // 2. Se for professor editando, garantir que ele não se remova acidentalmente
        if ($_SESSION['usuario_nivel'] == 'professor') {
            if (!in_array($_SESSION['professor_id'], $ids_novos_autores)) {
                $ids_novos_autores[] = $_SESSION['professor_id'];
            }
        }

        if (empty($ids_novos_autores)) {
            throw new Exception("O projeto deve ter pelo menos um autor.");
        }

        // 3. Limpar autores antigos
        $sql_del = "DELETE FROM professor_projeto WHERE id_projeto = :id";
        $stmt_del = $db->prepare($sql_del);
        $stmt_del->execute([':id' => $id_projeto]);

        // 4. Inserir novos autores
        $sql_ins = "INSERT INTO professor_projeto (id_professor, id_projeto) VALUES (:id_prof, :id_proj)";
        $stmt_ins = $db->prepare($sql_ins);

        foreach ($ids_novos_autores as $id_prof) {
            $stmt_ins->execute([':id_prof' => $id_prof, ':id_proj' => $id_projeto]);
        }

        // --- ATUALIZAR IMAGEM (CAPA) ---
        // Aqui estamos adicionando uma nova imagem na tabela 'imagens'. 
        // Se quiser substituir a capa, a lógica seria deletar a antiga antes.
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extensao, $permitidos)) {
                $dir = "../../img/projetos/";
                if (!is_dir($dir))
                    mkdir($dir, 0777, true); // Garante que a pasta existe

                $novo_nome = uniqid('proj_') . '.' . $extensao;

                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . $novo_nome)) {
                    $sql_img = "INSERT INTO imagens (caminho, id_projeto, legenda) VALUES (:c, :id, 'Capa')";
                    $stmt_img = $db->prepare($sql_img);
                    $stmt_img->execute([':c' => $novo_nome, ':id' => $id_projeto]);
                }
            }
        }

        $db->commit();
        $_SESSION['sucesso'] = "Projeto atualizado com sucesso!";
        header("Location: index.php");
        exit();

    } catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();
        $erro = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Projeto - IFMG</title>

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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .section-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: var(--ifmg-azul);
            font-weight: 600;
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
                <h1 class="h3 mb-1"><i class="bi bi-pencil-square me-2"></i> Editar Projeto</h1>
                <p class="mb-0">Atualizando: <?php echo htmlspecialchars($projeto['titulo']); ?></p>
            </div>
            <a href="index.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Cancelar</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <div class="form-card">

                        <div class="alert alert-light border-primary border mb-4">
                            <label class="form-label fw-bold text-primary required">Autores do Projeto</label>
                            <select class="selectpicker form-control" name="professores[]" id="selectProfessor" multiple
                                data-live-search="true" data-selected-text-format="count > 3"
                                title="Selecione os autores..." required onchange="atualizarCampoAutor()">

                                <?php foreach ($todos_professores as $prof): ?>
                                    <?php
                                    // Verifica se este professor já é autor
                                    $selected = in_array($prof['id'], $autores_atuais) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $prof['id']; ?>"
                                        data-nome="<?php echo htmlspecialchars($prof['nome']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($prof['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <h5 class="section-title">Dados Básicos</h5>

                        <div class="mb-3">
                            <label class="form-label required">Título do Projeto</label>
                            <input type="text" class="form-control" name="titulo"
                                value="<?php echo htmlspecialchars($projeto['titulo']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Área de Conhecimento</label>
                                <select class="form-select" name="area_conhecimento" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $areas = ['Administração', 'Humanas', 'Informática', 'Linguagens', 'Matemática', 'Metalurgia', 'Naturezas', 'Outros'];
                                    foreach ($areas as $area) {
                                        $sel = ($projeto['area_conhecimento'] == $area) ? 'selected' : '';
                                        echo "<option value='$area' $sel>$area</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="Em andamento" <?php echo ($projeto['status'] == 'Em andamento') ? 'selected' : ''; ?>>Em andamento</option>
                                    <option value="Concluído" <?php echo ($projeto['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                                    <option value="Pausado" <?php echo ($projeto['status'] == 'Pausado') ? 'selected' : ''; ?>>Pausado</option>
                                    <option value="Cancelado" <?php echo ($projeto['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Descrição</label>
                            <textarea class="form-control" name="descricao" rows="5"
                                required><?php echo htmlspecialchars($projeto['descricao']); ?></textarea>
                        </div>

                        <h5 class="section-title mt-4">Detalhes Acadêmicos</h5>

                        <div class="mb-3">
                            <label class="form-label">Objetivos</label>
                            <textarea class="form-control" name="objetivos"
                                rows="3"><?php echo htmlspecialchars($projeto['objetivos']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resultados</label>
                            <textarea class="form-control" name="resultados"
                                rows="3"><?php echo htmlspecialchars($projeto['resultados']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Parcerias</label>
                            <input type="text" class="form-control" name="parceria"
                                value="<?php echo htmlspecialchars($projeto['parceria']); ?>">
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-card">
                        <h5 class="section-title">Configurações</h5>

                        <div class="mb-3">
                            <label class="form-label required">Orientador(es)</label>
                            <input type="text" class="form-control" name="autor" id="campoAutor"
                                value="<?php echo htmlspecialchars($projeto['autor']); ?>" required>
                            <div class="form-text">Este campo é atualizado automaticamente ao selecionar os autores
                                acima.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data Início</label>
                            <input type="date" class="form-control" name="data_inicio"
                                value="<?php echo $projeto['data_inicio']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data Fim</label>
                            <input type="date" class="form-control" name="data_fim"
                                value="<?php echo $projeto['data_fim']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adicionar Nova Imagem</label>
                            <input type="file" class="form-control" name="imagem" accept="image/*">
                            <div class="form-text small">Adiciona à galeria existente.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Links</label>
                            <input type="text" class="form-control" name="links"
                                value="<?php echo htmlspecialchars($projeto['links']); ?>">
                        </div>
                    </div>

                    <div class="form-card">
                        <h5 class="section-title">Financiamento</h5>
                        <div class="mb-3">
                            <label class="form-label">Alunos Envolvidos</label>
                            <textarea class="form-control" name="alunos_envolvidos"
                                rows="2"><?php echo htmlspecialchars($projeto['alunos_envolvidos']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Agência</label>
                            <input type="text" class="form-control" name="agencia_financiadora"
                                value="<?php echo htmlspecialchars($projeto['agencia_financiadora']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Valor (R$)</label>
                            <input type="number" step="0.01" class="form-control" name="financiamento"
                                value="<?php echo htmlspecialchars($projeto['financiamento']); ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-lg"></i> Salvar
                            Alterações</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
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
        });

        function atualizarCampoAutor() {
            var selectedOptions = document.querySelectorAll('#selectProfessor option:checked');
            var nomes = [];
            selectedOptions.forEach(function (option) {
                nomes.push(option.getAttribute('data-nome'));
            });

            var campoAutor = document.getElementById('campoAutor');
            campoAutor.value = nomes.join(', ');

            campoAutor.style.backgroundColor = "#e8f0fe";
            setTimeout(() => { campoAutor.style.backgroundColor = ""; }, 500);
        }
    </script>
</body>

</html>