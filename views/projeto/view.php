<?php
// ==============================================
// views/projeto/view.php - Detalhes do Projeto (Admin/Painel)
// ==============================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Verificar ID
if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "Projeto não especificado.";
    header("Location: index.php");
    exit();
}

$id_projeto = (int) $_GET['id'];

require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

try {
    // 1. Buscar Dados do Projeto
    $query = "SELECT * FROM projeto WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_projeto);
    $stmt->execute();
    $projeto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projeto) {
        throw new Exception("Projeto não encontrado.");
    }

    // 2. Buscar Autores (Professores Vinculados)
    $query_autores = "SELECT p.id, p.nome 
                      FROM professor p 
                      INNER JOIN professor_projeto pp ON p.id = pp.id_professor 
                      WHERE pp.id_projeto = :id";
    $stmt_autores = $db->prepare($query_autores);
    $stmt_autores->execute([':id' => $id_projeto]);
    $autores = $stmt_autores->fetchAll(PDO::FETCH_ASSOC);

    // Lista de IDs dos autores para verificar permissão
    $ids_autores = array_column($autores, 'id');

    // 3. Buscar Imagens
    $query_imgs = "SELECT * FROM imagens WHERE id_projeto = :id";
    $stmt_imgs = $db->prepare($query_imgs);
    $stmt_imgs->execute([':id' => $id_projeto]);
    $imagens = $stmt_imgs->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
    header("Location: index.php");
    exit();
}

// === VERIFICAÇÃO DE PERMISSÃO PARA EDITAR ===
$pode_editar = false;
if ($_SESSION['usuario_nivel'] == 'admin') {
    $pode_editar = true;
} elseif (isset($_SESSION['professor_id']) && in_array($_SESSION['professor_id'], $ids_autores)) {
    $pode_editar = true;
}

// Formatação de Dados
$status_class = match ($projeto['status']) {
    'Em andamento' => 'success',
    'Concluído' => 'info',
    'Pausado' => 'warning',
    'Cancelado' => 'danger',
    default => 'secondary'
};
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Projeto - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

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

        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .label-detalhe {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .texto-detalhe {
            font-size: 1.05rem;
            margin-bottom: 20px;
            color: #212529;
        }

        .img-galeria {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .img-galeria:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }

        .badge-area {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 50px;
        }
    </style>
</head>

<body>

    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-file-text me-2"></i> Detalhes do Projeto</h1>
                <p class="mb-0 opacity-75">Visualização completa dos dados</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <span
                                class="badge bg-<?php echo $status_class; ?> mb-2"><?php echo htmlspecialchars($projeto['status']); ?></span>
                            <h2 class="h4 text-primary fw-bold"><?php echo htmlspecialchars($projeto['titulo']); ?></h2>
                            <span class="badge-area mt-2 d-inline-block">
                                <i class="bi bi-bookmark me-1"></i>
                                <?php echo htmlspecialchars($projeto['area_conhecimento']); ?>
                            </span>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mt-4 mb-3"><i class="bi bi-justify-left me-2"></i> Descrição</h5>
                    <div class="texto-detalhe">
                        <?php echo nl2br(htmlspecialchars($projeto['descricao'])); ?>
                    </div>

                    <?php if (!empty($projeto['objetivos'])): ?>
                        <h5 class="mt-4 mb-3"><i class="bi bi-bullseye me-2"></i> Objetivos</h5>
                        <div class="texto-detalhe">
                            <?php echo nl2br(htmlspecialchars($projeto['objetivos'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($projeto['resultados'])): ?>
                        <h5 class="mt-4 mb-3"><i class="bi bi-graph-up-arrow me-2"></i> Resultados</h5>
                        <div class="texto-detalhe">
                            <?php echo nl2br(htmlspecialchars($projeto['resultados'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($imagens)): ?>
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-images me-2"></i> Galeria de Imagens</h5>
                        <div class="row g-3">
                            <?php foreach ($imagens as $img): ?>
                                <div class="col-md-4 col-6">
                                    <a href="../../img/projetos/<?php echo htmlspecialchars($img['caminho']); ?>"
                                        target="_blank">
                                        <img src="../../img/projetos/<?php echo htmlspecialchars($img['caminho']); ?>"
                                            class="img-galeria">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <?php if ($pode_editar): ?>
                    <div class="content-card bg-light border-primary border">
                        <h5 class="mb-3 text-primary">Ações</h5>
                        <div class="d-grid gap-2">
                            <a href="edit.php?id=<?php echo $id_projeto; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-2"></i> Editar Projeto
                            </a>

                            <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                                <form action="delete.php" method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este projeto permanentemente?');">
                                    <input type="hidden" name="id" value="<?php echo $id_projeto; ?>">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-trash me-2"></i> Excluir Projeto
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <h5 class="mb-4"><i class="bi bi-info-circle me-2"></i> Informações</h5>

                    <div class="mb-3">
                        <span class="label-detalhe">Autores / Professores</span>
                        <?php if (!empty($autores)): ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($autores as $autor): ?>
                                    <li class="mb-1"><i class="bi bi-person-check me-2"></i>
                                        <?php echo htmlspecialchars($autor['nome']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mb-0"><?php echo htmlspecialchars($projeto['autor']); ?></p>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <span class="label-detalhe">Período</span>
                        <p class="mb-0">
                            Início:
                            <strong><?php echo date('d/m/Y', strtotime($projeto['data_inicio'])); ?></strong><br>
                            <?php if ($projeto['data_fim']): ?>
                                Fim: <strong><?php echo date('d/m/Y', strtotime($projeto['data_fim'])); ?></strong>
                            <?php else: ?>
                                <span class="text-success">Em andamento</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($projeto['financiamento'] > 0 || !empty($projeto['agencia_financiadora'])): ?>
                        <hr>
                        <div class="mb-3">
                            <span class="label-detalhe">Fomento</span>
                            <?php if (!empty($projeto['agencia_financiadora'])): ?>
                                <p class="mb-1">Agência: <?php echo htmlspecialchars($projeto['agencia_financiadora']); ?></p>
                            <?php endif; ?>
                            <?php if ($projeto['financiamento'] > 0): ?>
                                <p class="mb-0 fw-bold text-success">
                                    R$ <?php echo number_format($projeto['financiamento'], 2, ',', '.'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($projeto['alunos_envolvidos'])): ?>
                        <hr>
                        <div class="mb-3">
                            <span class="label-detalhe">Alunos Envolvidos</span>
                            <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($projeto['alunos_envolvidos'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($projeto['links'])): ?>
                        <hr>
                        <div class="mb-3">
                            <span class="label-detalhe">Links Relacionados</span>
                            <?php
                            $links = explode(',', $projeto['links']);
                            foreach ($links as $link):
                                $link = trim($link);
                                if (!empty($link)):
                                    ?>
                                    <a href="<?php echo $link; ?>" target="_blank" class="d-block text-truncate mb-1">
                                        <i class="bi bi-link-45deg"></i> Acessar Recurso
                                    </a>
                                <?php endif; endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>