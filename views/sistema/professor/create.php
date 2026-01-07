<?php
// views/professor/create.php

session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

// Verificar Permissão: Apenas Admin ou Professor
if ($_SESSION['usuario_nivel'] != 'admin' && $_SESSION['usuario_nivel'] != 'professor') {
    header("Location: ../painel.php");
    exit();
}

// Capturar mensagens de erro/sucesso da sessão
$erro = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
$sucesso = isset($_SESSION['sucesso']) ? $_SESSION['sucesso'] : '';
unset($_SESSION['erro']);
unset($_SESSION['sucesso']);

// Parâmetro para indicar que é o primeiro acesso (completar cadastro)
$completar = isset($_GET['completar']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Professor - IFMG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

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

        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .btn-save {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 10px 30px;
            width: 100%;
        }

        .btn-save:hover {
            opacity: 0.9;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        <?php echo $completar ? 'Completar Perfil' : 'Cadastrar Professor'; ?>
                    </h1>
                    <p class="mb-0">
                        <?php echo $completar ? 'Complete seus dados para acessar todas as funcionalidades' : 'Dados acadêmicos e biografia'; ?>
                    </p>
                </div>
                <div>
                    <a href="../painel.php" class="btn btn-outline-light">
                        <i class="bi bi-speedometer2 me-1"></i> Painel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">

        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($completar): ?>
            <div class="alert alert-warning border-start border-4 border-warning shadow-sm mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="bi bi-info-circle-fill fs-3 text-warning"></i>
                    </div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Perfil Incompleto</h6>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="../../../controllers/ProfessorController.php?action=create" method="POST"
            enctype="multipart/form-data">

            <div class="row">
                <div class="col-lg-8">
                    <div class="form-card p-4">
                        <h5 class="mb-4 text-primary border-bottom pb-2">Informações Pessoais</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required
                                    value="<?php echo isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : ''; ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email de Contato *</label>
                                <input type="email" class="form-control" name="email" required
                                    value="<?php echo isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Titulação *</label>
                                <select class="form-select" name="titulacao" required>
                                    <option value="">Selecione...</option>
                                    <option value="Doutor">Doutor(a)</option>
                                    <option value="Mestre">Mestre(a)</option>
                                    <option value="Especialista">Especialista</option>
                                    <option value="Graduado">Graduado(a)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Área de Atuação *</label>
                                <input type="text" class="form-control" name="area_atuacao" required
                                    placeholder="Ex: Biologia">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Biografia Resumida</label>
                            <textarea class="form-control" name="biografia" rows="5"
                                placeholder="Um breve resumo sobre sua trajetória acadêmica..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-card p-4">
                        <h5 class="mb-4 text-primary border-bottom pb-2">Foto e Links</h5>

                        <div class="mb-4 text-center">
                            <div class="bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3"
                                style="width: 120px; height: 120px; border: 3px dashed #ccc;">
                                <i class="bi bi-camera text-secondary fs-1"></i>
                            </div>
                            <label class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-upload me-2"></i> Escolher Foto
                                <input type="file" name="foto_perfil" class="d-none" accept="image/*">
                            </label>
                            <div class="form-text mt-2">Formatos: JPG, PNG ou WEBP</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link do Currículo Lattes</label>
                            <input type="url" class="form-control" name="link_lattes"
                                placeholder="http://lattes.cnpq.br/...">
                        </div>

                        <?php if ($_SESSION['usuario_nivel'] == 'admin' && isset($_GET['usuario_id'])): ?>
                            <input type="hidden" name="usuario_id" value="<?php echo $_GET['usuario_id']; ?>">
                        <?php endif; ?>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-save py-2">
                                <i class="bi bi-check-circle-fill me-2"></i> Salvar Perfil
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>