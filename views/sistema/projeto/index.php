<?php
// views/projeto/index.php - Listagem de Projetos

session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../../config/database.php';
require_once '../../../models/Projeto.php';
require_once '../../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();
$projeto = new Projeto($db);

// Verificar se é para mostrar apenas os projetos do professor
$meus_projetos = isset($_GET['meus']) && $_SESSION['usuario_nivel'] == 'professor';

// Buscar projetos
if ($meus_projetos && isset($_SESSION['professor_id'])) {
    // Professor vê apenas seus projetos
    $stmt = $projeto->listarPorProfessor($_SESSION['professor_id']);
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admin vê todos os projetos, professor vê todos se não for "meus"
    $stmt = $projeto->listar();
    $projetos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estatísticas
$total_projetos = count($projetos);
$projetos_ativos = 0;
$projetos_concluidos = 0;
$projetos_pausados = 0;

foreach ($projetos as $proj) {
    // Normaliza o status para evitar erros de case sensitive
    $status_lower = mb_strtolower($proj['status'], 'UTF-8');

    if (strpos($status_lower, 'andamento') !== false) {
        $projetos_ativos++;
    } elseif (strpos($status_lower, 'conclu') !== false) {
        $projetos_concluidos++;
    } elseif (strpos($status_lower, 'pausa') !== false) {
        $projetos_pausados++;
    }
}

// Mensagens
$sucesso = $_SESSION['sucesso'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['sucesso'], $_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projetos - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
            --status-ativo: #28a745;
            --status-concluido: #17a2b8;
            --status-pausado: #ffc107;
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

        /* Stats Cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-3px);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-right: 15px;
        }

        .icon-total {
            background: rgba(26, 41, 128, 0.1);
            color: var(--ifmg-azul);
        }

        .icon-ativo {
            background: rgba(40, 167, 69, 0.1);
            color: var(--status-ativo);
        }

        .icon-concluido {
            background: rgba(23, 162, 184, 0.1);
            color: var(--status-concluido);
        }

        .icon-pausado {
            background: rgba(255, 193, 7, 0.1);
            color: var(--status-pausado);
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .stats-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 5px 0 0;
        }

        /* Projeto Card */
        .projeto-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
            border-top: 4px solid #6c757d;
            display: flex;
            flex-direction: column;
        }

        .projeto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .projeto-card.ativo {
            border-top-color: var(--status-ativo);
        }

        .projeto-card.concluido {
            border-top-color: var(--status-concluido);
        }

        .projeto-card.pausado {
            border-top-color: var(--status-pausado);
        }

        .projeto-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-content {
            padding-right: 10px;
            flex: 1;
        }

        .projeto-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-ativo {
            background: rgba(40, 167, 69, 0.1);
            color: var(--status-ativo);
        }

        .status-concluido {
            background: rgba(23, 162, 184, 0.1);
            color: var(--status-concluido);
        }

        .status-pausado {
            background: rgba(255, 193, 7, 0.1);
            color: var(--status-pausado);
        }

        .projeto-titulo {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1.1rem;
            color: #343a40;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .projeto-titulo a {
            text-decoration: none;
            color: inherit;
        }

        .projeto-titulo a:hover {
            color: var(--ifmg-azul);
        }

        .projeto-body {
            padding: 0 20px 20px;
            flex-grow: 1;
        }

        .projeto-descricao {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .projeto-meta {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .projeto-meta i {
            width: 20px;
            margin-right: 8px;
        }

        .projeto-footer {
            border-top: 1px solid #e9ecef;
            padding: 15px 20px;
            background: #f8f9fa;
        }

        .projeto-area {
            background: rgba(26, 41, 128, 0.1);
            color: var(--ifmg-azul);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .filter-bar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .view-btn.active {
            border-color: var(--ifmg-azul);
            background: var(--ifmg-azul);
            color: white;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2"><i class="bi bi-folder me-2"></i> Projetos</h1>
                    <p class="mb-0">
                        <?php echo $meus_projetos ? 'Meus projetos de pesquisa e extensão' : 'Todos os projetos do IFMG'; ?>
                    </p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-light"><i class="bi bi-plus-circle me-1"></i> Novo Projeto</a>
                    <a href="../painel.php" class="btn btn-outline-light ms-2"><i
                            class="bi bi-arrow-left me-1"></i> Painel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-total"><i class="bi bi-folder"></i></div>
                        <div>
                            <p class="stats-number"><?php echo $total_projetos; ?></p>
                            <p class="stats-label">Total</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-ativo"><i class="bi bi-play-circle"></i></div>
                        <div>
                            <p class="stats-number"><?php echo $projetos_ativos; ?></p>
                            <p class="stats-label">Em Andamento</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-concluido"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <p class="stats-number"><?php echo $projetos_concluidos; ?></p>
                            <p class="stats-label">Concluídos</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-pausado"><i class="bi bi-pause-circle"></i></div>
                        <div>
                            <p class="stats-number"><?php echo $projetos_pausados; ?></p>
                            <p class="stats-label">Pausados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="btn-group w-100">
                        <button class="btn btn-outline-secondary active view-btn" id="gridViewBtn"><i
                                class="bi bi-grid"></i> Grade</button>
                        <button class="btn btn-outline-secondary view-btn" id="tableViewBtn"><i class="bi bi-list"></i>
                            Lista</button>
                    </div>
                </div>
                <div class="col-md-5 mb-3 mb-md-0">
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Buscar por título ou autor...">
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="filterStatus">
                        <option value="">Todos os status</option>
                        <option value="Em andamento">Em andamento</option>
                        <option value="Concluído">Concluído</option>
                        <option value="Pausado">Pausado</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="gridView">
            <?php if (empty($projetos)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle me-2"></i> Nenhum projeto encontrado.
                    <a href="create.php" class="alert-link">Clique aqui</a> para cadastrar.
                </div>
            <?php else: ?>
                <div class="row" id="projetoGrid">
                    <?php foreach ($projetos as $proj):
                        // Define classe CSS do card
                        $status_class = match ($proj['status']) {
                            'Em andamento' => 'ativo',
                            'Concluído' => 'concluido',
                            'Pausado' => 'pausado',
                            default => 'secondary'
                        };

                        // Lógica de Permissão
                        $pode_editar = false;
                        if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] == 'admin') {
                            $pode_editar = true;
                        } elseif (isset($_SESSION['professor_id'])) {
                            if ($meus_projetos) {
                                $pode_editar = true;
                            } elseif (isset($proj['ids_autores']) && in_array($_SESSION['professor_id'], explode(',', $proj['ids_autores'] ?? ''))) {
                                $pode_editar = true;
                            }
                        }

                        $link_destino = $pode_editar ? "edit.php?id={$proj['id']}" : "view.php?id={$proj['id']}";
                        // [CORREÇÃO] Evitar 1970 se data for nula
                        $data_display = $proj['data_inicio'] ? date('d/m/Y', strtotime($proj['data_inicio'])) : 'N/A';
                        ?>
                        <div class="col-xl-4 col-lg-6 mb-4 projeto-item" data-status="<?php echo $proj['status']; ?>"
                            data-titulo="<?php echo htmlspecialchars(strtolower($proj['titulo'])); ?>"
                            data-autor="<?php echo htmlspecialchars(strtolower($proj['autor'])); ?>">

                            <div class="projeto-card <?php echo $status_class; ?>">
                                <div class="projeto-header">
                                    <div class="header-content">
                                        <h5 class="projeto-titulo">
                                            <a href="<?php echo $link_destino; ?>">
                                                <?php echo htmlspecialchars($proj['titulo']); ?>
                                            </a>
                                        </h5>
                                        <div class="projeto-meta">
                                            <i class="bi bi-person"></i>
                                            <span><?php echo htmlspecialchars($proj['autor']); ?></span>
                                        </div>
                                    </div>
                                    <span class="projeto-status status-<?php echo $status_class; ?>">
                                        <?php echo $proj['status']; ?>
                                    </span>
                                </div>

                                <div class="projeto-body">
                                    <p class="projeto-descricao">
                                        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($proj['descricao']), 0, 150, "...")); ?>
                                    </p>
                                    <div class="projeto-meta">
                                        <i class="bi bi-calendar"></i>
                                        <span>Início: <?php echo $data_display; ?></span>
                                    </div>
                                </div>

                                <div class="projeto-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span
                                            class="projeto-area"><?php echo htmlspecialchars($proj['area_conhecimento']); ?></span>
                                        <div>
                                            <a href="view.php?id=<?php echo $proj['id']; ?>" class="btn btn-sm btn-outline-info"
                                                title="Ver Detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($pode_editar): ?>
                                                <a href="edit.php?id=<?php echo $proj['id']; ?>"
                                                    class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="tableView" style="display: none;">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 w-100" id="projetosTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Título</th>
                                    <th>Autor</th>
                                    <th>Área</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projetos as $proj):
                                    // Recalcula permissão
                                    $pode_editar = ($meus_projetos) ||
                                        ($_SESSION['usuario_nivel'] == 'admin') ||
                                        (isset($proj['ids_autores']) && in_array($_SESSION['professor_id'], explode(',', $proj['ids_autores'] ?? '')));

                                    $data_display = $proj['data_inicio'] ? date('d/m/Y', strtotime($proj['data_inicio'])) : '-';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($proj['titulo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($proj['autor']); ?></td>
                                        <td><?php echo htmlspecialchars($proj['area_conhecimento']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $proj['status']; ?></span></td>
                                        <td><?php echo $data_display; ?></td>
                                        <td class="text-end">
                                            <a href="view.php?id=<?php echo $proj['id']; ?>"
                                                class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i></a>
                                            <?php if ($pode_editar): ?>
                                                <a href="edit.php?id=<?php echo $proj['id']; ?>"
                                                    class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Toggle de Visualização
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');
        const gridBtn = document.getElementById('gridViewBtn');
        const tableBtn = document.getElementById('tableViewBtn');

        gridBtn.addEventListener('click', () => {
            gridView.style.display = 'block';
            tableView.style.display = 'none';
            gridBtn.classList.add('active');
            tableBtn.classList.remove('active');
        });

        tableBtn.addEventListener('click', () => {
            gridView.style.display = 'none';
            tableView.style.display = 'block';
            tableBtn.classList.add('active');
            gridBtn.classList.remove('active');

            // Inicializa DataTable apenas quando visível para ajustar larguras corretamente
            if (!$.fn.DataTable.isDataTable('#projetosTable')) {
                $('#projetosTable').DataTable({
                    language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json" },
                    responsive: true,
                    columnDefs: [{ orderable: false, targets: 5 }]
                });
            }
        });

        // Filtro em Tempo Real (Grid)
        document.getElementById('searchInput').addEventListener('keyup', function () {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.projeto-item');

            items.forEach(item => {
                let text = item.innerText.toLowerCase();
                item.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('filterStatus').addEventListener('change', function () {
            let status = this.value;
            let items = document.querySelectorAll('.projeto-item');

            items.forEach(item => {
                let itemStatus = item.getAttribute('data-status');
                if (status === "" || itemStatus === status) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>