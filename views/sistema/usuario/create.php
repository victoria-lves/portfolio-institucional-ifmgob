<?php
// views/usuario/create.php

session_start();

// 1. Verificação de Segurança (Apenas Admin)
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] != 'admin') {
    $_SESSION['erro'] = "Acesso restrito a administradores.";
    header("Location: ../painel.php");
    exit();
}

$erro = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['erro'], $_SESSION['sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Usuário - IFMG</title>
    
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
        
        .header-bar {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            max-width: 600px;
            margin: -60px auto 40px; /* Sobe o card para sobrepor o header */
            position: relative;
            z-index: 2;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
        
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--ifmg-azul);
        }
        
        .btn-primary-gradient {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            border: none;
            color: white;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(26, 41, 128, 0.2);
        }
    </style>
</head>

<body>

    <div class="header-bar pb-5"> <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-3 mb-md-0">
                    <h2 class="h4 mb-1 fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Cadastrar Usuário</h2>
                    <p class="mb-0 text-white-50 small">Criação de acesso para novos professores</p>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </a>
                    <a href="../painel.php" class="btn btn-light btn-sm text-primary fw-bold">
                        <i class="bi bi-speedometer2 me-1"></i> Painel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show max-width-600 mx-auto mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i> <?php echo $erro; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show max-width-600 mx-auto mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i> <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="../../../controllers/UsuarioController.php?action=create" method="POST">
                
                <h5 class="mb-4 text-primary fw-bold border-bottom pb-3">Dados de Acesso</h5>

                <div class="mb-3">
                    <label for="nome" class="form-label fw-bold small text-uppercase text-muted">Nome Completo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person text-primary"></i></span>
                        <input type="text" class="form-control" name="nome" id="nome" required placeholder="Ex: João da Silva" autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold small text-uppercase text-muted">E-mail Institucional</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope text-primary"></i></span>
                        <input type="email" class="form-control" name="email" id="email" required placeholder="nome.sobrenome@ifmg.edu.br">
                    </div>
                    <div class="form-text small">Este será o login do professor no sistema.</div>
                </div>

                <div class="mb-4">
                    <label for="senha" class="form-label fw-bold small text-uppercase text-muted">Senha Inicial</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key text-primary"></i></span>
                        <input type="password" class="form-control" name="senha" id="senha" required minlength="6" placeholder="******">
                    </div>
                    <div class="form-text small">Mínimo de 6 caracteres. O professor poderá alterá-la depois.</div>
                </div>

                <div class="d-grid gap-2 mt-5">
                    <button type="submit" class="btn btn-primary-gradient py-2">
                        <i class="bi bi-check-lg me-2"></i> Confirmar Cadastro
                    </button>
                    <a href="index.php" class="btn btn-link text-decoration-none text-muted">Cancelar</a>
                </div>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>