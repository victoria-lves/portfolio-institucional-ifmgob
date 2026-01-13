<?php

session_start();

// 1. Verificações de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/auth/login.php");
    exit();
}

$id_professor = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Se não tiver ID na URL, tenta pegar da sessão se for professor
if (!$id_professor && $_SESSION['usuario_nivel'] == 'professor') {
    $id_professor = $_SESSION['professor_id'];
}

if (!$id_professor) {
    $_SESSION['erro'] = "Professor não identificado.";
    header("Location: ../sistema/painel.php");
    exit();
}

// Verifica permissão: Só o próprio professor ou Admin podem editar
if ($_SESSION['usuario_nivel'] != 'admin' && $_SESSION['professor_id'] != $id_professor) {
    $_SESSION['erro'] = "Você não tem permissão para editar este perfil.";
    header("Location: ../sistema/painel.php");
    exit();
}

require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// 2. Buscar Dados Atuais
$stmt = $db->prepare("SELECT * FROM professor WHERE id = :id LIMIT 1");
$stmt->bindParam(':id', $id_professor);
$stmt->execute();
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prof) {
    die("Professor não encontrado.");
}

// Mensagens Flash
$erro = isset($_SESSION['erro']) ? $_SESSION['erro'] : '';
$sucesso = isset($_SESSION['sucesso']) ? $_SESSION['sucesso'] : '';
unset($_SESSION['erro']);
unset($_SESSION['sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - IFMG</title>

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

        .preview-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
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
                    <h1 class="mb-2"><i class="bi bi-pencil-square me-2"></i> Editar Perfil</h1>
                    <p class="mb-0">Atualize suas informações acadêmicas e pessoais</p>
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

        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="../../../controllers/ProfessorController.php?action=update" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="id" value="<?php echo $id_professor; ?>">

            <div class="row">
                <div class="col-lg-8">
                    <div class="form-card p-4">
                        <h5 class="mb-4 text-primary border-bottom pb-2">Informações Pessoais</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="nome" required 
                                       value="<?php echo htmlspecialchars($prof['nome']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email de Contato *</label>
                                <input type="email" class="form-control" name="email" required 
                                       value="<?php echo htmlspecialchars($prof['email']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Formação / Titulação</label>
                                <input type="text" class="form-control" name="formacao" 
                                       value="<?php echo htmlspecialchars($prof['formacao']); ?>" 
                                       placeholder="Ex: Doutorado em Física (UFMG)">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Disciplinas / Área</label>
                                <input type="text" class="form-control" name="disciplina" 
                                       value="<?php echo htmlspecialchars($prof['disciplina']); ?>"
                                       placeholder="Ex: Física I, Mecânica Quântica">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Biografia</label>
                            <textarea class="form-control" name="bio" rows="5" 
                                      placeholder="Um breve resumo sobre sua trajetória acadêmica..."><?php echo htmlspecialchars($prof['bio']); ?></textarea>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gabinete</label>
                                <select class="form-select" name="gabinete">
                                    <option value="">Selecione...</option>
                                    <?php 
                                    $gabinetes = ['Administração', 'Humanas', 'Informática', 'Linguagens', 'Matemática', 'Metalurgia', 'Naturezas'];
                                    foreach($gabinetes as $gab) {
                                        $sel = ($prof['gabinete'] == $gab) ? 'selected' : '';
                                        echo "<option value='$gab' $sel>$gab</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Horários de Atendimento</label>
                                <input type="text" class="form-control" name="atendimento" 
                                       value="<?php echo htmlspecialchars($prof['atendimento']); ?>"
                                       placeholder="Ex: Terças e Quintas, 14h às 16h">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="form-card p-4 text-center">
                        <h5 class="mb-4 text-primary border-bottom pb-2">Foto de Perfil</h5>

                        <div class="mb-3">
                            <?php
                            $imgSrc = !empty($prof['pfp']) ? "../../../assets/img/docentes/" . $prof['pfp'] : "../../../assets/img/docentes/default-img-pfp.webp";
                            if (strpos($prof['pfp'] ?? '', 'http') === 0) $imgSrc = $prof['pfp'];
                            ?>
                            <img src="<?php echo $imgSrc; ?>" alt="Foto Atual" class="preview-img">
                        </div>

                        <div class="mb-3">
                            <label for="pfp" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-camera me-2"></i> Alterar Foto
                            </label>
                            <input type="file" id="pfp" name="pfp" class="d-none" accept="image/*" onchange="previewImage(this)">
                            <div class="form-text mt-2 text-muted">JPG, PNG ou WEBP (Max: 2MB)</div>
                        </div>
                    </div>

                    <div class="form-card p-4">
                        <h5 class="mb-3 text-primary border-bottom pb-2">Redes Acadêmicas</h5>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-file-earmark-person me-1"></i> Currículo Lattes</label>
                            <input type="url" class="form-control" name="lattes" 
                                   value="<?php echo htmlspecialchars($prof['lattes']); ?>" placeholder="http://lattes.cnpq.br/...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-linkedin me-1"></i> LinkedIn</label>
                            <input type="url" class="form-control" name="linkedin" 
                                   value="<?php echo htmlspecialchars($prof['linkedin']); ?>" placeholder="http://linkedin.com/in/...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-link-45deg me-1"></i> Outro Link</label>
                            <input type="url" class="form-control" name="link" 
                                   value="<?php echo htmlspecialchars($prof['link']); ?>" placeholder="Site pessoal, blog...">
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-save py-2">
                                <i class="bi bi-check-circle-fill me-2"></i> Salvar Alterações
                            </button>
                            <a href="../painel.php" class="btn btn-light border">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview simples da imagem antes do upload
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.querySelector('.preview-img').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>
