<?php
session_start();

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Projeto.php';

$database = new Database();
$db = $database->getConnection();
$projetoModel = new Projeto($db);

// 2. Buscar Projetos (Lógica de Permissão)
try {
    if ($_SESSION['usuario_nivel'] == 'admin') {
        // Admin vê TUDO
        $stmt = $projetoModel->listar();
    } else {
        // Professor vê apenas os DELE
        // Certifica-se que o usuario_nivel é 'professor' e tem um professor_id na sessão
        if (isset($_SESSION['professor_id'])) {
            $stmt = $projetoModel->listarPorProfessor($_SESSION['professor_id']);
        } else {
            // Se for professor mas não tiver perfil docente, lista vazia ou redireciona
            $stmt = null; 
        }
    }

    $projetos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (PDOException $e) {
    $erro = "Erro ao buscar dados: " . $e->getMessage();
}

// 3. Estatísticas Rápidas para o Topo
$total = count($projetos);
$ativos = 0;
foreach($projetos as $p) {
    if(strpos($p['status'], 'andamento') !== false) $ativos++;
}

// Mensagens Flash
$sucesso = $_SESSION['sucesso'] ?? '';
$erro_sessao = $_SESSION['erro'] ?? '';
unset($_SESSION['sucesso'], $_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Projetos - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        :root { --ifmg-azul: #1a2980; --ifmg-verde: #26d0ce; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        
        .page-header {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .table-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .badge-status { font-size: 0.8rem; }
        .bg-andamento { background-color: #28a745; }
        .bg-concluido { background-color: #17a2b8; }
        .bg-pausado { background-color: #ffc107; color: #000; }
        
        .actions-col { white-space: nowrap; width: 150px; }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2"><i class="bi bi-folder-fill me-2"></i> Gerenciar Projetos</h1>
                    <p class="mb-0">
                        <?php echo $_SESSION['usuario_nivel'] == 'admin' ? 'Visão geral de todos os projetos' : 'Meus projetos de pesquisa e extensão'; ?>
                    </p>
                </div>
                <div>
                    <a href="../projeto/create.php" class="btn btn-light">
                        <i class="bi bi-plus-circle me-1"></i> Novo Projeto
                    </a>
                    <a href="painel.php" class="btn btn-outline-light ms-2">
                        <i class="bi bi-arrow-left me-1"></i> Painel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($erro_sessao): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro_sessao; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-secondary">Lista de Projetos (<?php echo $total; ?>)</h5>
                <span class="badge bg-success"><?php echo $ativos; ?> em andamento</span>
            </div>

            <table id="projetosTable" class="table table-hover table-striped w-100">
                <thead>
                    <tr>
                        <th width="40%">Título</th>
                        <th>Autor Principal</th>
                        <th>Área</th>
                        <th>Status</th>
                        <th>Início</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projetos as $p): 
                        // Formatação de Status
                        $statusClass = 'bg-secondary';
                        if(strpos($p['status'], 'andamento') !== false) $statusClass = 'bg-andamento';
                        elseif(strpos($p['status'], 'Conclu') !== false) $statusClass = 'bg-concluido';
                        elseif(strpos($p['status'], 'Pausa') !== false) $statusClass = 'bg-pausado';
                        
                        // Data
                        $dataInicio = $p['data_inicio'] ? date('d/m/Y', strtotime($p['data_inicio'])) : '-';
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['titulo']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($p['autor']); ?></td>
                            <td><?php echo htmlspecialchars($p['area_conhecimento']); ?></td>
                            <td>
                                <span class="badge badge-status <?php echo $statusClass; ?>">
                                    <?php echo $p['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $dataInicio; ?></td>
                            <td class="text-end actions-col">
                                <a href="projeto/view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-info" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="projeto/edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="POST" action="../../controllers/ProjetoController.php?action=delete" 
                                      class="d-inline"
                                      onsubmit="return confirm('Tem certeza que deseja excluir o projeto \'<?php echo addslashes($p['titulo']); ?>\'?');">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#projetosTable').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
                responsive: true,
                order: [[4, 'desc']], // Ordenar por Data de Início (coluna 4)
                columnDefs: [
                    { orderable: false, targets: 5 } // Desabilitar ordenação na coluna de Ações
                ]
            });
        });
    </script>
</body>
</html>
