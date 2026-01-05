<?php
// public/professor.php - Página pública SEM curtidas
require_once '../../config/database.php';
require_once '../models/Professor.php';
require_once '../models/Producao.php';

if (!isset($_GET['id'])) {
    header("Location: professores.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$professor = new Professor($db);
$dados = $professor->buscarPorId($_GET['id']);

if (!$dados) {
    header("Location: professores.php?erro=professor_nao_encontrado");
    exit();
}

$producao = new Producao($db);
$producoes = $producao->listarPorProfessor($dados['id'])->fetchAll();

// Assumindo que a variável $professor['nome'] contém o nome do docente
$nome_prof = htmlspecialchars($professor['nome'] ?? 'Docente');
$area_prof = htmlspecialchars($professor['area_atuacao'] ?? 'Ensino');

$keywords_docente = "Professor " . $nome_prof . " IFMG Ouro Branco, " . $area_prof . " IFMG, corpo docente Ouro Branco, currículo professor IFMG";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="<?php echo $keywords_docente; ?>">
    <title><?php echo $dados['nome']; ?> - IFMG Ouro Branco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
        }

        .profile-header {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            padding: 60px 0 30px;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-building"></i> IFMG Ouro Branco
            </a>
        </div>
    </nav>

    <!-- Perfil -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <?php if ($dados['pfp']): ?>
                        <img src="../../assets/img/docentes<?php echo $dados['pfp']; ?>" class="profile-avatar"
                            alt="<?php echo $dados['nome']; ?>">
                    <?php else: ?>
                        <div class="profile-avatar bg-light d-flex align-items-center justify-content-center mx-auto">
                            <i class="bi bi-person display-4 text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-2"><?php echo $dados['nome']; ?></h1>
                    <p class="lead mb-3"><?php echo $dados['disciplina']; ?></p>
                    <div class="d-flex gap-3">
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-building me-1"></i> <?php echo $dados['gabinete']; ?>
                        </span>
                        <?php if ($dados['email']): ?>
                            <a href="mailto:<?php echo $dados['email']; ?>"
                                class="badge bg-light text-dark text-decoration-none">
                                <i class="bi bi-envelope me-1"></i> <?php echo $dados['email']; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row">
            <!-- Coluna Esquerda -->
            <div class="col-lg-4">
                <!-- Contato -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i> Contato</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($dados['atendimento']): ?>
                            <h6>Horário de Atendimento:</h6>
                            <p class="text-muted"><?php echo nl2br($dados['atendimento']); ?></p>
                            <hr>
                        <?php endif; ?>

                        <?php if ($dados['lattes']): ?>
                            <a href="<?php echo $dados['lattes']; ?>" target="_blank"
                                class="btn btn-outline-primary btn-sm w-100 mb-2">
                                <i class="bi bi-file-earmark-text me-2"></i> Currículo Lattes
                            </a>
                        <?php endif; ?>

                        <?php if ($dados['linkedin']): ?>
                            <a href="<?php echo $dados['linkedin']; ?>" target="_blank"
                                class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-linkedin me-2"></i> LinkedIn
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formação -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Formação Acadêmica</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo nl2br($dados['formacao']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita -->
            <div class="col-lg-8">
                <!-- Biografia -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-file-text me-2"></i> Biografia</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo nl2br($dados['bio']); ?></p>
                    </div>
                </div>

                <!-- Produções Acadêmicas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i> Produções Acadêmicas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($producoes) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($producoes as $prod): ?>
                                    <div class="list-group-item border-0">
                                        <h6 class="mb-1"><?php echo $prod['titulo']; ?></h6>
                                        <p class="text-muted mb-1">
                                            <small>
                                                <?php echo $prod['autor']; ?> |
                                                <?php echo date('d/m/Y', strtotime($prod['data_pub'])); ?> |
                                                <?php echo $prod['tipo']; ?>
                                                <?php if ($prod['tipo_outro']): ?>
                                                    (<?php echo $prod['tipo_outro']; ?>)
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        <?php if ($prod['link']): ?>
                                            <a href="<?php echo $prod['link']; ?>" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-link-45deg me-1"></i> Acessar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">Nenhuma produção cadastrada.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Link para projetos -->
                <div class="text-center mt-4">
                    <a href="projetos.php?professor=<?php echo $dados['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-folder me-2"></i> Ver Projetos deste Professor
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4 border-top">
        <div class="container text-center">
            <p class="mb-0">IFMG Campus Ouro Branco &copy; <?php echo date('Y'); ?></p>
            <small class="text-muted">Portal Acadêmico</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>