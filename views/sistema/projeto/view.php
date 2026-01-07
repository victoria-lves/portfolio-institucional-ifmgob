<?php
// views/sistema/projeto/view.php
session_start();

// 1. Verificações de Acesso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "ID do projeto não fornecido.";
    header("Location: index.php");
    exit();
}

// 2. Configuração e Conexão (3 níveis acima)
require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id_projeto = (int) $_GET['id'];

try {
    // 3. Buscar Projeto
    $stmt = $db->prepare("SELECT * FROM projeto WHERE id = ? LIMIT 1");
    $stmt->execute([$id_projeto]);
    $projeto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projeto)
        throw new Exception("Projeto não encontrado.");

    // 4. Buscar Autores
    $stmtAuth = $db->prepare("SELECT p.nome, p.id FROM professor p 
                              INNER JOIN professor_projeto pp ON p.id = pp.id_professor 
                              WHERE pp.id_projeto = ?");
    $stmtAuth->execute([$id_projeto]);
    $autores = $stmtAuth->fetchAll(PDO::FETCH_ASSOC);
    $ids_autores = array_column($autores, 'id');

    // 5. Buscar Imagens
    $stmtImg = $db->prepare("SELECT * FROM imagens WHERE id_projeto = ?");
    $stmtImg->execute([$id_projeto]);
    $imagens = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
    header("Location: index.php");
    exit();
}

// 6. Verificar Permissão (Admin ou Autor)
$pode_editar = ($_SESSION['usuario_nivel'] == 'admin') ||
    (isset($_SESSION['professor_id']) && in_array($_SESSION['professor_id'], $ids_autores));

// Cores do Status
$status_colors = [
    'Em andamento' => 'success',
    'Concluído' => 'info',
    'Pausado' => 'warning',
    'Cancelado' => 'danger'
];
$bg_status = $status_colors[$projeto['status']] ?? 'secondary';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($projeto['titulo']); ?> - IFMG</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }

        .header-bg {
            background: linear-gradient(90deg, #1a2980 0%, #26d0ce 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .content-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .img-galeria {
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .img-galeria:hover { transform: scale(1.03); }
        
        .badge-area { background-color: rgba(255,255,255,0.9); color: #333; }
    </style>
</head>

<body>

    <div class="header-bg">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="col-lg-9">
                    <span class="badge badge-area mb-2"><?php echo htmlspecialchars($projeto['area_conhecimento']); ?></span>
                    <h1 class="h2 mb-0 fw-bold"><?php echo htmlspecialchars($projeto['titulo']); ?></h1>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-n5 pt-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-primary mb-0">Sobre o Projeto</h4>
                        <span class="badge bg-<?php echo $bg_status; ?> fs-6"><?php echo $projeto['status']; ?></span>
                    </div>
                    <hr class="mb-4">

                    <p class="lead" style="font-size: 1.1rem; line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($projeto['descricao'])); ?>
                    </p>

                    <?php if (!empty($projeto['objetivos'])): ?>
                        <h5 class="mt-4 fw-bold">Objetivos</h5>
                        <p><?php echo nl2br(htmlspecialchars($projeto['objetivos'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($projeto['resultados'])): ?>
                        <h5 class="mt-4 fw-bold">Resultados</h5>
                        <p><?php echo nl2br(htmlspecialchars($projeto['resultados'])); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($imagens)): ?>
                    <div class="content-card">
                        <h5 class="mb-3">Galeria de Imagens</h5>
                        <div class="row g-3">
                            <?php foreach ($imagens as $img): ?>
                                <div class="col-md-4 col-6">
                                    <a href="../../../assets/img/projetos/<?php echo htmlspecialchars($img['caminho']); ?>" target="_blank">
                                        <img src="../../../assets/img/projetos/<?php echo htmlspecialchars($img['caminho']); ?>" class="img-galeria shadow-sm">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <?php if ($pode_editar): ?>
                    <div class="content-card border border-primary">
                        <h5 class="text-primary mb-3"><i class="bi bi-gear-fill me-2"></i> Gerenciar</h5>
                        <div class="d-grid gap-2">
                            <a href="edit.php?id=<?php echo $id_projeto; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-2"></i> Editar Dados
                            </a>

                            <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                                <form action="../../../controllers/ProjetoController.php?action=delete" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este projeto permanentemente?');">
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
                    <h5 class="mb-3 text-secondary">Informações</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <strong><i class="bi bi-people me-2"></i> Autores:</strong><br>
                            <?php foreach ($autores as $aut): ?>
                                <span class="d-block text-muted ms-4">• <?php echo htmlspecialchars($aut['nome']); ?></span>
                            <?php endforeach; ?>
                        </li>
                        
                        <li class="mb-3">
                            <strong><i class="bi bi-calendar-event me-2"></i> Início:</strong><br>
                            <span class="ms-4">
                                <?php echo !empty($projeto['data_inicio']) ? date('d/m/Y', strtotime($projeto['data_inicio'])) : 'Não informado'; ?>
                            </span>
                        </li>

                        <?php if (!empty($projeto['data_fim'])): ?>
                            <li class="mb-3">
                                <strong><i class="bi bi-calendar-check me-2"></i> Término:</strong><br>
                                <span class="ms-4">
                                    <?php echo date('d/m/Y', strtotime($projeto['data_fim'])); ?>
                                </span>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($projeto['financiamento'])): ?>
                            <li class="mb-3">
                                <strong><i class="bi bi-cash-coin me-2"></i> Financiamento:</strong><br>
                                <span class="ms-4">
                                    R$ <?php echo number_format($projeto['financiamento'], 2, ',', '.'); ?>
                                    <?php if (!empty($projeto['agencia_financiadora'])) echo "<br><small class='text-muted'>({$projeto['agencia_financiadora']})</small>"; ?>
                                </span>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($projeto['parceria'])): ?>
                            <li class="mb-3">
                                <strong><i class="bi bi-briefcase me-2"></i> Parceria:</strong><br>
                                <span class="ms-4"><?php echo htmlspecialchars($projeto['parceria']); ?></span>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($projeto['links'])): ?>
                            <li class="mb-3">
                                <strong><i class="bi bi-link-45deg me-2"></i> Links:</strong><br>
                                <a href="<?php echo htmlspecialchars($projeto['links']); ?>" target="_blank" class="ms-4 text-truncate d-block">Acessar Link Externo</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($projeto['alunos_envolvidos'])): ?>
                            <li class="mb-3">
                                <strong><i class="bi bi-mortarboard me-2"></i> Alunos:</strong><br>
                                <span class="ms-4"><?php echo htmlspecialchars($projeto['alunos_envolvidos']); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>