<?php
// views/auth/login.php
session_start();

// Se já estiver logado, redireciona para o painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../painel.php");
    exit();
}

// Capturar mensagens da sessão (Flash Messages)
$erro = '';
$sucesso = '';
$email_preenchido = '';

if (isset($_SESSION['erro'])) {
    $erro = $_SESSION['erro'];
    unset($_SESSION['erro']);
}

if (isset($_SESSION['logout_sucesso'])) {
    $sucesso = $_SESSION['logout_sucesso'];
    unset($_SESSION['logout_sucesso']);
}

if (isset($_SESSION['old_email'])) {
    $email_preenchido = htmlspecialchars($_SESSION['old_email']);
    unset($_SESSION['old_email']);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="acesso professores IFMG Ouro Branco, atualizar descrição IFMG, sistema professores campus Ouro Branco, portal docente IFMG, login sistema acadêmico">
    <title>Login - IFMG Ouro Branco</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <style>
        :root {
            --ifmg-azul: #1a2980;
            --ifmg-verde: #26d0ce;
        }

        body {
            background: linear-gradient(135deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container { animation: fadeIn 0.5s ease-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header h2 { margin: 0; font-weight: 700; }
        .login-body { padding: 40px; }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--ifmg-azul);
            box-shadow: 0 0 0 0.25rem rgba(26, 41, 128, 0.25);
        }

        .btn-login {
            background: linear-gradient(90deg, var(--ifmg-azul) 0%, var(--ifmg-verde) 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .password-toggle { cursor: pointer; color: #666; }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <h2><i class="bi bi-building me-2"></i> IFMG Ouro Branco</h2>
                        <p class="mb-0">Sistema de Gestão Acadêmica</p>
                    </div>

                    <div class="login-body">
                        
                        <?php if ($sucesso): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $sucesso; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($erro): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $erro; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="../../controllers/AuthController.php?action=login" method="POST" id="loginForm">
                            
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold">
                                    <i class="bi bi-envelope me-1"></i> Email Institucional
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="seu.email@ifmg.edu.br" required
                                    value="<?php echo $email_preenchido; ?>" autofocus>
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label fw-bold">
                                    <i class="bi bi-lock me-1"></i> Senha
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="senha" name="senha"
                                        placeholder="Digite sua senha" required>
                                    <button class="btn btn-outline-secondary password-toggle" type="button"
                                        id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="lembrar" name="lembrar">
                                <label class="form-check-label" for="lembrar">
                                    Lembrar de mim
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Entrar no Sistema
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <div class="btn-group" role="group">
                                <a href="../../index.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-house-door me-1"></i> Site Público
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Mostrar/ocultar senha
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('senha');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });

        // Efeito de loading no botão ao enviar
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Entrando...';
            submitBtn.disabled = true;

            // Timeout de segurança caso o servidor não responda
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    </script>
</body>
</html>