<?php
// ==============================================
// views/usuario/index.php - Lista de Usuários
// ==============================================

session_start();

// Verificar se está logado e se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] != 'admin') {
    // Se não for admin, redireciona para painel ou login
    header("Location: ../views/authlogin.php");
    exit();
}

require_once '../../../config/database.php';
// require_once '../../models/Usuario.php'; // O modelo Usuario fornecido não tem método listar, faremos via PDO direto

$database = new Database();
$db = $database->getConnection();

// Buscar todos os usuários
try {
    $query = "SELECT * FROM usuario ORDER BY nome ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao listar usuários: " . $e->getMessage();
}

// Estatísticas
$total_usuarios = count($usuarios);
$total_admins = 0;
$total_professores = 0;

foreach ($usuarios as $user) {
    if ($user['nivel'] == 'admin') {
        $total_admins++;
    } else {
        $total_professores++;
    }
}

// Mensagens de Sessão
$sucesso = isset($_SESSION['sucesso']) ? $_SESSION['sucesso'] : '';
$erro_sessao = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
unset($_SESSION['sucesso']);
unset($_SESSION['erro']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

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
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 15px;
        }

        .icon-total {
            background: rgba(26, 41, 128, 0.1);
            color: var(--ifmg-azul);
        }

        .icon-admin {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .icon-prof {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .stats-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }

        .stats-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 5px 0 0;
        }

        .table-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .user-avatar-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.2rem;
        }

        .badge-admin {
            background-color: #dc3545;
        }

        .badge-professor {
            background-color: #0d6efd;
        }

        .actions-col {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="bi bi-people-fill me-2"></i>
                        Gerenciar Usuários
                    </h1>
                    <p class="mb-0">Controle de acesso e contas do sistema</p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-light">
                        <i class="bi bi-person-plus-fill me-1"></i> Novo Usuário
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

        <?php if ($erro_sessao): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $erro_sessao; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card d-flex align-items-center">
                    <div class="stats-icon icon-total"><i class="bi bi-people"></i></div>
                    <div>
                        <p class="stats-number"><?php echo $total_usuarios; ?></p>
                        <p class="stats-label">Total de Contas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card d-flex align-items-center">
                    <div class="stats-icon icon-prof"><i class="bi bi-person-video3"></i></div>
                    <div>
                        <p class="stats-number"><?php echo $total_professores; ?></p>
                        <p class="stats-label">Professores</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card d-flex align-items-center">
                    <div class="stats-icon icon-admin"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <p class="stats-number"><?php echo $total_admins; ?></p>
                        <p class="stats-label">Administradores</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <table id="usuariosTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Nível de Acesso</th>
                        <th width="150">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div class="user-avatar-sm">
                                    <i class="bi bi-person"></i>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($u['nome']); ?></strong>
                                <?php if ($u['id'] == $_SESSION['usuario_id']): ?>
                                    <span class="badge bg-secondary ms-1">Você</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <?php if ($u['nivel'] == 'admin'): ?>
                                    <span class="badge badge-admin">
                                        <i class="bi bi-shield-fill me-1"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-professor">
                                        <i class="bi bi-person-badge me-1"></i> Professor
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-col">
                                <button class="btn btn-sm btn-outline-primary" title="Editar Usuário" disabled>
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <?php if ($u['id'] != $_SESSION['usuario_id']): // Não pode excluir a si mesmo ?>
                                    <form method="POST" action="../../controllers/UsuarioController.php?action=delete"
                                        class="d-inline"
                                        onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?php echo addslashes($u['nome']); ?>? Esta ação não pode ser desfeita.');">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir Usuário">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar DataTables
            $('#usuariosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                },
                responsive: true,
                order: [[1, 'asc']], // Ordenar por nome
                columnDefs: [
                    { orderable: false, targets: [0, 4] } // Desabilitar ordenação em Avatar e Ações
                ]
            });

            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>