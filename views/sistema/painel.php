<?php

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Configurar timeout da sessão (1 hora)
$timeout = 3600;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Sessão expirou
    session_unset();
    session_destroy();
    $_SESSION['erro'] = "Sessão expirada por inatividade. Faça login novamente.";
    header("Location: ../auth/login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Conectar ao banco
require_once '../../config/database.php';
require_once '../../models/Professor.php';
require_once '../../models/Projeto.php';
require_once '../../models/Producao.php';

$database = new Database();
$db = $database->getConnection();

// Se for professor, verificar se tem perfil
if ($_SESSION['usuario_nivel'] == 'professor' && !isset($_SESSION['professor_id'])) {
    $professor = new Professor($db);
    $stmt = $professor->listarPorUsuario($_SESSION['usuario_id']);

    if ($stmt->rowCount() > 0) {
        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['professor_id'] = $perfil['id'];
    } else {
        // Redirecionar para criar perfil
        header("Location: professor/create.php?completar=1");
        exit();
    }
}

// Buscar estatísticas
$totalProfessores = 0;
$totalProjetos = 0;
$totalProducoes = 0;
$meusProjetos = 0;
$minhasProducoes = 0;

try {
    if ($_SESSION['usuario_nivel'] == 'admin') {
        // Admin vê todas as estatísticas
        $professorModel = new Professor($db);
        $totalProfessores = $professorModel->listar()->rowCount();

        $projetoModel = new Projeto($db);
        $totalProjetos = $projetoModel->listar()->rowCount();

        $producaoModel = new Producao($db);
        $totalProducoes = $producaoModel->listar()->rowCount();
    } else {
        // Professor vê apenas suas estatísticas
        $projetoModel = new Projeto($db);
        $meusProjetos = $projetoModel->listarPorProfessor($_SESSION['professor_id'])->rowCount();

        $producaoModel = new Producao($db);
        $minhasProducoes = $producaoModel->listarPorProfessor($_SESSION['professor_id'])->rowCount();
    }
} catch (Exception $e) {
    // Ignorar erros de tabelas não existentes
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
    <title>Painel - IFMG Ouro Branco</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- Font Awesome (opcional) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
            --ifmg-laranja: #ff6b35;
            --ifmg-roxo: #6a11cb;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand h4 {
            margin: 0;
            font-weight: 700;
        }

        .sidebar-brand p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            color: white;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
            color: white;
            text-decoration: none;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 3px solid white;
            font-weight: 600;
        }

        .nav-icon {
            width: 24px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* User Info */
        .user-info {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 80px;
            width: 100%;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--ifmg-azul);
        }

        .header-title p {
            margin: 5px 0 0;
            color: #666;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border-top: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-professores {
            border-top-color: var(--ifmg-azul);
        }

        .card-projetos {
            border-top-color: var(--ifmg-verde);
        }

        .card-producoes {
            border-top-color: var(--ifmg-laranja);
        }

        .card-meus {
            border-top-color: var(--ifmg-roxo);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .icon-professores {
            background: rgba(26, 41, 128, 0.1);
            color: var(--ifmg-azul);
        }

        .icon-projetos {
            background: rgba(38, 208, 206, 0.1);
            color: var(--ifmg-verde);
        }

        .icon-producoes {
            background: rgba(255, 107, 53, 0.1);
            color: var(--ifmg-laranja);
        }

        .icon-meus {
            background: rgba(106, 17, 203, 0.1);
            color: var(--ifmg-roxo);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: #333;
        }

        .stat-title {
            font-size: 1rem;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-link {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 10px;
        }

        .stat-link:hover {
            text-decoration: underline;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            text-decoration: none;
            color: #555;
            transition: all 0.3s;
            height: 100%;
            text-align: center;
        }

        .action-btn:hover {
            border-color: var(--ifmg-azul);
            background: rgba(26, 41, 128, 0.05);
            color: var(--ifmg-azul);
            transform: translateY(-3px);
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }

            .sidebar span:not(.nav-icon) {
                display: none;
            }

            .sidebar-brand h4,
            .sidebar-brand p {
                display: none;
            }

            .sidebar-brand {
                padding: 15px;
            }

            .main-content {
                margin-left: 70px;
            }

            .user-info {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        /* Logout Button */
        .logout-btn {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-logout {
            background: rgba(220, 53, 69, 0.2);
            color: white;
            border: 1px solid rgba(220, 53, 69, 0.3);
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Welcome Message */
        .welcome-card {
            background: linear-gradient(135deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-card p {
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Alert Styling */
        .alert-painel {
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <h4><i class="bi bi-building"></i> IFMG</h4>
            <p>Ouro Branco</p>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="painel.php">
                        <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
                        <span>Painel</span>
                    </a>
                </li>

                <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuario/index.php">
                            <span class="nav-icon"><i class="bi bi-people"></i></span>
                            <span>Professores</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['usuario_nivel'] == 'professor'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="professor/edit.php?id=<?php echo $_SESSION['professor_id']; ?>">
                            <span class="nav-icon"><i class="bi bi-person-circle"></i></span>
                            <span>Meu Perfil</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link" href="projeto/index.php">
                        <span class="nav-icon"><i class="bi bi-folder"></i></span>
                        <span>Projetos</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="producao/index.php">
                        <span class="nav-icon"><i class="bi bi-journal-text"></i></span>
                        <span>Produções</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="../site/menu-principal.php">
                        <span class="nav-icon"><i class="bi bi-eye"></i></span>
                        <span>Ver Site Público</span>
                    </a>
                </li>


            </ul>
        </nav>

        <!-- User Info -->
        <div class="user-info d-flex align-items-center">
            <div class="user-avatar">
                <i class="bi bi-person"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></div>
                <div class="user-role">
                    <span class="badge bg-<?php echo $_SESSION['usuario_nivel'] == 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst($_SESSION['usuario_nivel']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- LOGOUT BUTTON -->
        <div class="logout-btn">
            <a href="/sistema-crud/controllers/AuthController.php?action=logout" class="btn btn-danger"
                onclick="return confirm('Deseja realmente sair?')">
                <i class="bi bi-box-arrow-right"></i> Sair
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Painel</h1>
                <p>Bem-vindo ao Sistema de Gestão Acadêmica</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show alert-painel" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-painel" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Message -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h2>
                    <p>
                        <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                            Você está no painel de administração do sistema.
                        <?php else: ?>
                            Gerencie seu perfil, projetos e produções acadêmicas.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <i class="bi bi-person-badge display-4 opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4">
            <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                <!-- ADMIN STATS -->
                <div class="col-md-4">
                    <div class="stat-card card-professores">
                        <div class="stat-icon icon-professores">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalProfessores; ?></div>
                        <div class="stat-title">Professores Cadastrados</div>
                        <a href="usuario/index.php" class="stat-link">
                            <i class="bi bi-arrow-right me-1"></i> Gerenciar
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card card-projetos">
                        <div class="stat-icon icon-projetos">
                            <i class="bi bi-folder"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalProjetos; ?></div>
                        <div class="stat-title">Projetos Ativos</div>
                        <a href="projeto/index.php" class="stat-link">
                            <i class="bi bi-arrow-right me-1"></i> Ver todos
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card card-producoes">
                        <div class="stat-icon icon-producoes">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div class="stat-number"><?php echo $totalProducoes; ?></div>
                        <div class="stat-title">Produções Acadêmicas</div>
                        <a href="producao/index.php" class="stat-link">
                            <i class="bi bi-arrow-right me-1"></i> Ver todas
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- PROFESSOR STATS -->
                <div class="col-md-6">
                    <div class="stat-card card-projetos">
                        <div class="stat-icon icon-projetos">
                            <i class="bi bi-folder"></i>
                        </div>
                        <div class="stat-number"><?php echo $meusProjetos; ?></div>
                        <div class="stat-title">Meus Projetos</div>
                        <a href="projeto/index.php?meus=1" class="stat-link">
                            <i class="bi bi-arrow-right me-1"></i> Gerenciar
                        </a>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="stat-card card-producoes">
                        <div class="stat-icon icon-producoes">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div class="stat-number"><?php echo $minhasProducoes; ?></div>
                        <div class="stat-title">Minhas Produções</div>
                        <a href="producao/index.php?meus=1" class="stat-link">
                            <i class="bi bi-arrow-right me-1"></i> Gerenciar
                        </a>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="stat-card card-meus">
                        <div class="stat-icon icon-meus">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="stat-number">Meu Perfil</div>
                        <div class="stat-title">Complete e atualize suas informações</div>
                        <a href="professor/edit.php?id=<?php echo $_SESSION['professor_id']; ?>" class="stat-link">
                            <i class="bi bi-arrow-right me-1"></i> Editar perfil
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h4 class="mb-4">Ações Rápidas</h4>
            <div class="row g-4">
                <?php if ($_SESSION['usuario_nivel'] == 'admin'): ?>
                    <div class="col-md-3">
                        <a href="usuario/create.php" class="action-btn">
                            <div class="action-icon text-primary">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="fw-bold">Cadastrar Professor</div>
                            <small class="text-muted">Novo docente</small>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($_SESSION['usuario_nivel'] == 'professor'): ?>
                    <div class="col-md-3">
                        <a href="professor/edit.php?id=<?php echo $_SESSION['professor_id']; ?>" class="action-btn">
                            <div class="action-icon text-primary">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="fw-bold">Meu Perfil</div>
                            <small class="text-muted">Editar informações</small>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <a href="projeto/create.php" class="action-btn">
                        <div class="action-icon text-success">
                            <i class="bi bi-folder-plus"></i>
                        </div>
                        <div class="fw-bold">Novo Projeto</div>
                        <small class="text-muted">Criar projeto</small>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="producao/create.php" class="action-btn">
                        <div class="action-icon text-warning">
                            <i class="bi bi-journal-plus"></i>
                        </div>
                        <div class="fw-bold">Nova Produção</div>
                        <small class="text-muted">Adicionar produção</small>
                    </a>
                </div>

                <div class="col-md-3">
                    <a href="../site/menu-principal.php" class="action-btn">
                        <div class="action-icon text-info">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div class="fw-bold">Ver Site</div>
                        <small class="text-muted">Site público</small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Confirmar logout
        function confirmLogout() {
            return confirm('Tem certeza que deseja sair do sistema?');
        }

        // Atualizar hora
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            // document.getElementById('current-time').innerText = 
            //     now.toLocaleDateString('pt-BR', options);
        }

        // Atualizar a cada segundo
        setInterval(updateTime, 1000);
        updateTime();

        // Inatividade - redirecionar após 30 minutos
        let inactivityTime = function () {
            let time;

            function resetTimer() {
                clearTimeout(time);
                time = setTimeout(logoutInactive, 30 * 60 * 1000); // 30 minutos
            }

            function logoutInactive() {
                if (confirm('Sua sessão ficou inativa por 30 minutos. Deseja continuar?')) {
                    resetTimer();
                } else {
                    window.location.href = '../../controllers/AuthController.php?action=logout';
                }
            }

            // Eventos que resetam o timer
            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onscroll = resetTimer;
            document.onclick = resetTimer;
        };

        // Iniciar monitor de inatividade
        inactivityTime();

        // Tooltips
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>