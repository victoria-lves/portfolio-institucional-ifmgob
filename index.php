<?php
session_start();

// Se estiver logado, vai para painel
if (isset($_SESSION['usuario_id'])) {
    header("Location: views/painel.php");
    exit();
}

// Se não estiver logado, mostra página inicial simples
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IFMG Ouro Branco - Sistema Acadêmico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }

        .welcome-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="welcome-card p-5 text-center">
                    <h1 class="mb-4">IFMG Ouro Branco</h1>
                    <h3 class="mb-3">Sistema de Gestão</h3>

                    <div class="mb-4">
                        <p>Este sistema permite o gerenciamento de:</p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-person-check"></i> Perfis de Professores</li>
                            <li><i class="bi bi-folder"></i> Projetos de Pesquisa</li>
                            <li><i class="bi bi-journal-text"></i> Produções Acadêmicas</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-3">
                        <a href="views/auth/login.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Acessar Sistema
                        </a>

                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <a href="menu-principal.php" class="btn btn-outline-primary w-100">
                                    Ver Portfólio Institucional
                                </a>
                            </div>
                        </div>

                    </div>

                    <hr class="my-4">

                    <div class="text-muted">
                        <small>
                            Sistema restrito a docentes do IFMG Campus Ouro Branco<br>
                            Dúvidas: email.ourobranco@ifmg.edu.br
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>