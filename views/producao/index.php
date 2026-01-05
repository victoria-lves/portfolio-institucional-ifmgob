<?php
// ==============================================
// producao/index.php - Lista de Produções Acadêmicas
// ==============================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

require_once '../../../config/database.php';
require_once '../../models/Producao.php';
require_once '../../models/Professor.php';

$database = new Database();
$db = $database->getConnection();
$producao = new Producao($db);

// Verificar se é para mostrar apenas as produções do professor
$minhas_producoes = isset($_GET['meus']) && $_SESSION['usuario_nivel'] == 'professor';

// Buscar produções
if ($minhas_producoes && isset($_SESSION['professor_id'])) {
    // Professor vê apenas suas produções
    $stmt = $producao->listarPorProfessor($_SESSION['professor_id']);
    $producoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admin vê todas, ou listagem geral
    $stmt = $producao->listar();
    $producoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estatísticas
$total_producoes = count($producoes);
$total_artigos = 0;
$total_livros = 0;
$total_teses = 0;
$total_outros = 0;

foreach ($producoes as $prod) {
    switch ($prod['tipo']) {
        case 'Artigo':
            $total_artigos++;
            break;
        case 'Livro':
            $total_livros++;
            break;
        case 'Tese':
            $total_teses++;
            break;
        default:
            $total_outros++;
            break;
    }
}

// Mensagens
$sucesso = isset($_SESSION['sucesso']) ? $_SESSION['sucesso'] : '';
$erro = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
unset($_SESSION['sucesso']);
unset($_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produções Acadêmicas - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
            --ifmg-laranja: #ff6b35;
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

        .icon-artigo {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .icon-livro {
            background: rgba(255, 107, 53, 0.1);
            color: var(--ifmg-laranja);
        }

        .icon-tese {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
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

        .producao-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
            border-left: 5px solid;
            position: relative;
        }

        .producao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Cores das bordas por tipo */
        .producao-card[data-type="Artigo"] {
            border-left-color: #28a745;
        }

        .producao-card[data-type="Livro"] {
            border-left-color: var(--ifmg-laranja);
        }

        .producao-card[data-type="Tese"] {
            border-left-color: #17a2b8;
        }

        .producao-card[data-type="Outro"] {
            border-left-color: #6c757d;
        }

        .producao-body {
            padding: 20px;
        }

        .producao-type {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 15px;
            background: #f8f9fa;
            color: #6c757d;
        }

        .producao-titulo {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #343a40;
            padding-right: 80px;
            /* Espaço para o badge */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .producao-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .producao-meta i {
            margin-right: 8px;
            width: 16px;
        }

        .producao-footer {
            border-top: 1px solid #e9ecef;
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .filter-bar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
        }

        .view-btn {
            padding: 8px 20px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            color: #6c757d;
            transition: all 0.3s;
        }

        .view-btn.active {
            border-color: var(--ifmg-azul);
            background: var(--ifmg-azul);
            color: white;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 40px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .table-producoes {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .table-producoes th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 15px;
        }

        .table-producoes td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge-tipo {
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .badge-artigo {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .badge-livro {
            background-color: rgba(255, 107, 53, 0.1);
            color: var(--ifmg-laranja);
        }

        .badge-tese {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .badge-outro {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .view-toggle {
                justify-content: center;
                margin-bottom: 15px;
            }

            .search-box {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="bi bi-journal-text me-2"></i>
                        Produções Acadêmicas
                    </h1>
                    <p class="mb-0">
                        <?php if ($minhas_producoes): ?>
                            Minhas publicações e trabalhos
                        <?php else: ?>
                            Acervo de produções do corpo docente
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-light">
                        <i class="bi bi-plus-circle me-1"></i> Nova Produção
                    </a>
                    <a href="../sistema/painel.php" class="btn btn-outline-light ms-2">
                        <i class="bi bi-arrow-left me-1"></i> Painel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-total">
                            <i class="bi bi-journal-bookmark"></i>
                        </div>
                        <div>
                            <p class="stats-number"><?php echo $total_producoes; ?></p>
                            <p class="stats-label">Total Cadastrado</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-artigo">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <div>
                            <p class="stats-number"><?php echo $total_artigos; ?></p>
                            <p class="stats-label">Artigos</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-livro">
                            <i class="bi bi-book"></i>
                        </div>
                        <div>
                            <p class="stats-number"><?php echo $total_livros; ?></p>
                            <p class="stats-label">Livros</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon icon-tese">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                        <div>
                            <p class="stats-number"><?php echo $total_teses; ?></p>
                            <p class="stats-label">Teses/Dissertações</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="view-toggle">
                        <button class="view-btn active" id="gridViewBtn">
                            <i class="bi bi-grid me-2"></i> Grade
                        </button>
                        <button class="view-btn" id="tableViewBtn">
                            <i class="bi bi-list me-2"></i> Lista
                        </button>
                    </div>
                </div>

                <div class="col-md-5 mb-3 mb-md-0">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" id="searchInput"
                            placeholder="Buscar por título, autor...">
                    </div>
                </div>

                <div class="col-md-4">
                    <select class="form-select filter-select" id="filterTipo">
                        <option value="">Todos os tipos</option>
                        <option value="Artigo">Artigos</option>
                        <option value="Livro">Livros</option>
                        <option value="Tese">Teses</option>
                        <option value="Outro">Outros</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="gridView">
            <?php if (empty($producoes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-journal-x"></i>
                    </div>
                    <h4>Nenhuma produção encontrada</h4>
                    <p class="mb-4">
                        <?php if ($minhas_producoes): ?>
                            Você ainda não cadastrou nenhuma produção acadêmica.
                        <?php else: ?>
                            Nenhuma produção cadastrada no sistema.
                        <?php endif; ?>
                    </p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Cadastrar Primeira Produção
                    </a>
                </div>
            <?php else: ?>
                <div class="row" id="producaoGrid">
                    <?php foreach ($producoes as $prod):
                        $data_pub = $prod['data_pub'] ? date('d/m/Y', strtotime($prod['data_pub'])) : 'Data n/a';
                        $tipo_display = $prod['tipo'];
                        if ($prod['tipo'] == 'Outro' && !empty($prod['tipo_outro'])) {
                            $tipo_display = $prod['tipo_outro'];
                        }
                        ?>
                        <div class="col-xl-4 col-lg-6 mb-4 producao-item" data-tipo="<?php echo $prod['tipo']; ?>"
                            data-titulo="<?php echo htmlspecialchars(strtolower($prod['titulo'])); ?>"
                            data-autor="<?php echo htmlspecialchars(strtolower($prod['autor'])); ?>">
                            <div class="producao-card" data-type="<?php echo $prod['tipo']; ?>">
                                <div class="producao-body">
                                    <span class="producao-type">
                                        <?php echo htmlspecialchars($tipo_display); ?>
                                    </span>

                                    <h5 class="producao-titulo" title="<?php echo htmlspecialchars($prod['titulo']); ?>">
                                        <?php echo htmlspecialchars($prod['titulo']); ?>
                                    </h5>

                                    <div class="producao-meta">
                                        <i class="bi bi-person"></i>
                                        <span><?php echo htmlspecialchars($prod['autor']); ?></span>
                                    </div>

                                    <div class="producao-meta">
                                        <i class="bi bi-calendar"></i>
                                        <span>Publicação: <?php echo $data_pub; ?></span>
                                    </div>

                                    <div class="producao-meta">
                                        <i class="bi bi-translate"></i>
                                        <span>
                                            <?php
                                            echo htmlspecialchars($prod['idioma']);
                                            if ($prod['idioma'] == 'Outro' && $prod['idioma_outro']) {
                                                echo ' (' . htmlspecialchars($prod['idioma_outro']) . ')';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="producao-footer">
                                    <div>
                                        <?php if ($prod['link']): ?>
                                            <a href="<?php echo htmlspecialchars($prod['link']); ?>" target="_blank"
                                                class="btn btn-sm btn-outline-info" title="Acessar publicação">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Acessar
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="btn-group">
                                        <?php if (
                                            $_SESSION['usuario_nivel'] == 'admin' ||
                                            (isset($_SESSION['professor_id']) &&
                                                ($prod['id_professor'] == $_SESSION['professor_id']))
                                        ): ?>
                                            <form method="POST" action="delete.php" class="d-inline"
                                                onsubmit="return confirm('Tem certeza que deseja excluir esta produção?')">
                                                <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="tableView" style="display: none;">
            <?php if (!empty($producoes)): ?>
                <div class="table-producoes">
                    <table class="table table-hover mb-0" id="producoesTable">
                        <thead>
                            <tr>
                                <th width="30%">Título</th>
                                <th>Autor(es)</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Idioma</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($producoes as $prod):
                                $badge_class = 'badge-outro';
                                switch ($prod['tipo']) {
                                    case 'Artigo':
                                        $badge_class = 'badge-artigo';
                                        break;
                                    case 'Livro':
                                        $badge_class = 'badge-livro';
                                        break;
                                    case 'Tese':
                                        $badge_class = 'badge-tese';
                                        break;
                                }

                                $tipo_display = $prod['tipo'];
                                if ($prod['tipo'] == 'Outro' && !empty($prod['tipo_outro'])) {
                                    $tipo_display = $prod['tipo_outro'];
                                }
                                ?>
                                <tr class="producao-item" data-tipo="<?php echo $prod['tipo']; ?>"
                                    data-titulo="<?php echo htmlspecialchars(strtolower($prod['titulo'])); ?>"
                                    data-autor="<?php echo htmlspecialchars(strtolower($prod['autor'])); ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($prod['titulo']); ?></strong>
                                        <?php if ($prod['link']): ?>
                                            <a href="<?php echo htmlspecialchars($prod['link']); ?>" target="_blank"
                                                class="text-info ms-1">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($prod['autor']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-tipo <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($tipo_display); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $prod['data_pub'] ? date('d/m/Y', strtotime($prod['data_pub'])) : '-'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($prod['idioma']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if (
                                                $_SESSION['usuario_nivel'] == 'admin' ||
                                                (isset($_SESSION['professor_id']) &&
                                                    ($prod['id_professor'] == $_SESSION['professor_id']))
                                            ): ?>
                                                <form method="POST" action="delete.php" class="d-inline"
                                                    onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                                    <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

    <script>
        // Alternar entre Grid e Table View
        const gridViewBtn = document.getElementById('gridViewBtn');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');

        gridViewBtn.addEventListener('click', function () {
            this.classList.add('active');
            tableViewBtn.classList.remove('active');
            gridView.style.display = 'block';
            tableView.style.display = 'none';
        });

        tableViewBtn.addEventListener('click', function () {
            this.classList.add('active');
            gridViewBtn.classList.remove('active');
            gridView.style.display = 'none';
            tableView.style.display = 'block';

            // Inicializar DataTables se não estiver inicializado
            if (!$.fn.DataTable.isDataTable('#producoesTable')) {
                $('#producoesTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                    },
                    pageLength: 10,
                    responsive: true,
                    columnDefs: [
                        { orderable: false, targets: [5] } // Desabilitar ordenação na coluna de ações
                    ],
                    order: [[3, 'desc']] // Ordenar por data
                });
            }
        });

        // Filtros
        const searchInput = document.getElementById('searchInput');
        const filterTipo = document.getElementById('filterTipo');
        const producaoItems = document.querySelectorAll('.producao-item');

        function filterProducoes() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const tipoValue = filterTipo.value;

            producaoItems.forEach(item => {
                const titulo = item.getAttribute('data-titulo');
                const autor = item.getAttribute('data-autor');
                const tipo = item.getAttribute('data-tipo');

                const matchesSearch = searchTerm === '' ||
                    titulo.includes(searchTerm) ||
                    autor.includes(searchTerm);

                const matchesTipo = tipoValue === '' || tipo === tipoValue;

                if (matchesSearch && matchesTipo) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Event listeners para filtros
        searchInput.addEventListener('input', filterProducoes);
        filterTipo.addEventListener('change', filterProducoes);

        // Inicializar select com bootstrap-select
        $(document).ready(function () {
            $('.filter-select').selectpicker({
                style: 'btn-light',
                size: 4
            });

            // Tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Adicionar botão de exportação se for admin
            <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                const exportDiv = document.createElement('div');
                exportDiv.className = 'dropdown d-inline-block ms-2';
                exportDiv.innerHTML = `
                <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" 
                        data-bs-toggle="dropdown">
                    <i class="bi bi-download me-2"></i> Exportar
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportProducoes('csv')">
                        <i class="bi bi-filetype-csv me-2"></i> CSV
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportProducoes('json')">
                        <i class="bi bi-filetype-json me-2"></i> JSON
                    </a></li>
                </ul>
            `;

                const controls = document.querySelector('.view-toggle');
                if (controls) {
                    controls.parentNode.insertBefore(exportDiv, controls.nextSibling);
                }
            <?php endif; ?>
        });

        // Função para exportar
        function exportProducoes(format) {
            const producoes = <?php echo json_encode($producoes); ?>;

            if (format === 'csv') {
                let csv = 'Título,Autor,Tipo,Data Publicação,Idioma,Link\n';

                producoes.forEach(prod => {
                    csv += `"${prod.titulo.replace(/"/g, '""')}","${prod.autor}","${prod.tipo}",`;
                    csv += `"${prod.data_pub}","${prod.idioma}","${prod.link || ''}"\n`;
                });

                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `producoes_${new Date().toISOString().split('T')[0]}.csv`;
                a.click();

            } else if (format === 'json') {
                const dataStr = JSON.stringify(producoes, null, 2);
                const blob = new Blob([dataStr], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `producoes_${new Date().toISOString().split('T')[0]}.json`;
                a.click();
            }
        }
    </script>
</body>

</html>