<?php
session_start();

// Se já estiver logado, vai para painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../painel.php");
    exit();
}

// Inicializar variáveis
$erro = '';
$sucesso = '';
$email_preenchido = '';

// Se veio do logout, mostrar mensagem
if (isset($_SESSION['logout_sucesso'])) {
    $sucesso = $_SESSION['logout_sucesso'];
    unset($_SESSION['logout_sucesso']);
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $email_preenchido = htmlspecialchars($email);

    // Conectar ao banco
    require_once '../../config/database.php';

    try {
        $database = new Database();
        $db = $database->getConnection();

        // DEBUG: Ver se a conexão funciona
        if (!$db) {
            $erro = "ERRO: Não foi possível conectar ao banco de dados!";
        } else {
            // DEBUG: Ver se a tabela usuario existe
            $stmt = $db->query("SHOW TABLES LIKE 'usuario'");
            if ($stmt->rowCount() == 0) {
                $erro = "ERRO: Tabela 'usuario' não encontrada no banco!";
            } else {
                // Buscar usuário
                $query = "SELECT id, nome, email, senha, nivel FROM usuario WHERE email = :email LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


                    // Verificar senha
                    if (password_verify($senha, $usuario['senha'])) {
                        // Login bem sucedido

                        //impede a reutilização de um id
                        session_regenerate_id(true);

                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nome'] = $usuario['nome'];
                        $_SESSION['usuario_nivel'] = $usuario['nivel'];

                        // Registrar login
                        $_SESSION['login_time'] = time();

                        // Redirecionar
                        header("Location: ../painel.php");
                        exit();
                    } else {
                        // Senha incorreta
                        $erro = "Senha incorreta!";
                    }
                } else {
                    // Usuário não encontrado
                    $erro = "Email não encontrado!";
                }
            }
        }
    } catch (PDOException $e) {
        $erro = "Erro no banco de dados: " . $e->getMessage();
    } catch (Exception $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="acesso professores IFMG Ouro Branco, atualizar descrição IFMG, sistema professores campus Ouro Branco, portal docente IFMG, login sistema acadêmico">
    <title>Login - IFMG Ouro Branco</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
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

        .login-container {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .login-header h2 {
            margin: 0;
            font-weight: 700;
        }

        .login-body {
            padding: 40px;
        }

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

        .alert {
            border-radius: 8px;
            border: none;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
        }

        .test-credentials {
            background: #e8f4f8;
            border-left: 4px solid #26d0ce;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }

        .password-toggle {
            cursor: pointer;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <!-- Cabeçalho -->
                    <div class="login-header">
                        <h2><i class="bi bi-building me-2"></i> IFMG Ouro Branco</h2>
                        <p class="mb-0">Sistema de Gestão Acadêmica</p>
                    </div>

                    <!-- Corpo do Formulário -->
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

                        <form method="POST" id="loginForm">
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold">
                                    <i class="bi bi-envelope me-1"></i> Email Institucional
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="seu.email@ifmg.edu.br" required
                                    value="<?php echo $email_preenchido; ?>" autofocus>
                            </div>

                            <!-- Senha -->
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

                            <!-- Lembrar de mim -->
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="lembrar" name="lembrar">
                                <label class="form-check-label" for="lembrar">
                                    Lembrar de mim
                                </label>
                            </div>

                            <!-- Botão de Login -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Entrar no Sistema
                                </button>
                            </div>
                        </form>

                        <!-- Links úteis -->
                        <div class="text-center">
                            <div class="btn-group" role="group">
                                <a href="../../index.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-house-door me-1"></i> Site Público
                                </a>
                            </div>
                        </div>

                        <!-- Debug Info (apenas se houver erro) -->
                        <?php if ($erro && isset($debug_info)): ?>
                            <div class="debug-info mt-3">
                                <h6><i class="bi bi-bug me-2"></i> Informações de Debug:</h6>
                                <p class="mb-1"><?php echo $debug_info; ?></p>
                                <small class="text-muted">Esta informação só aparece em caso de erro.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
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

        // Validação do formulário
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email = document.getElementById('email').value;
            const senha = document.getElementById('senha').value;

            if (!email || !senha) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return false;
            }

            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Entrando...';
            submitBtn.disabled = true;

            // Restaurar botão após 5 segundos (caso demore)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);

            return true;
        });

        // Foco no campo de email
        document.getElementById('email').focus();
    </script>
</body>

</html>