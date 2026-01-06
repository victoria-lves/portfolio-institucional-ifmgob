<?php
session_start();

// 1. Verificações de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_professor = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Se não tiver ID na URL, tenta pegar da sessão se for professor
if (!$id_professor && $_SESSION['usuario_nivel'] == 'professor') {
    $id_professor = $_SESSION['professor_id'];
}

if (!$id_professor) {
    $_SESSION['erro'] = "Professor não identificado.";
    header("Location: ../painel.php");
    exit();
}

// Verifica permissão: Só o próprio professor ou Admin podem editar
if ($_SESSION['usuario_nivel'] != 'admin' && $_SESSION['professor_id'] != $id_professor) {
    $_SESSION['erro'] = "Você não tem permissão para editar este perfil.";
    header("Location: ../painel.php");
    exit();
}

require_once '../../../config/database.php';
$database = new Database();
$db = $database->getConnection();

// 2. Buscar Dados Atuais (Apenas para exibir no formulário)
// A lógica de SALVAR (Update) agora está no ProfessorController.php
$stmt = $db->prepare("SELECT * FROM professor WHERE id = :id LIMIT 1");
$stmt->bindParam(':id', $id_professor);
$stmt->execute();
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prof) {
    die("Professor não encontrado.");
}
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
        body {
            background-color: #f8f9fa;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .preview-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-person-lines-fill text-primary"></i> Editar Perfil</h2>
            <a href="../painel.php" class="btn btn-secondary">Painel</a>
        </div>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['sucesso'];
                unset($_SESSION['sucesso']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['erro'];
                unset($_SESSION['erro']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="../../controllers/ProfessorController.php?action=update" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="id" value="<?php echo $id_professor; ?>">

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="form-card text-center h-100">
                        <div class="mb-3">
                            <?php
                            $imgSrc = !empty($prof['pfp']) ? "../../assets/img/docentes/" . $prof['pfp'] : "../../assets/img/docentes/default-img-pfp.webp";
                            // Verifica se é link externo ou arquivo local
                            if (strpos($prof['pfp'] ?? '', 'http') === 0)
                                $imgSrc = $prof['pfp'];
                            ?>
                            <img src="<?php echo $imgSrc; ?>" alt="Foto de Perfil" class="preview-img mb-3">
                        </div>
                        <label for="pfp" class="form-label fw-bold">Alterar Foto</label>
                        <input type="file" class="form-control" name="pfp" id="pfp" accept="image/*">
                        <div class="form-text text-muted small mt-2">Formatos: JPG, PNG, WEBP. Max: 2MB.</div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-card">
                        <h5 class="border-bottom pb-2 mb-3">Informações Pessoais</h5>

                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="nome"
                                value="<?php echo htmlspecialchars($prof['nome']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email de Contato</label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($prof['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Minibiografia</label>
                            <textarea class="form-control" name="bio"
                                rows="4"><?php echo htmlspecialchars($prof['bio']); ?></textarea>
                            <div class="form-text">Um breve resumo sobre sua carreira e interesses.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Formação Acadêmica</label>
                            <textarea class="form-control" name="formacao"
                                rows="3"><?php echo htmlspecialchars($prof['formacao']); ?></textarea>
                            <div class="form-text">Ex: Doutorado em Física (UFMG), Mestrado em...</div>
                        </div>

                        <h5 class="border-bottom pb-2 mb-3 mt-4">Links e Atendimento</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="bi bi-file-earmark-person"></i> Currículo
                                    Lattes</label>
                                <input type="url" class="form-control" name="lattes"
                                    value="<?php echo htmlspecialchars($prof['lattes']); ?>"
                                    placeholder="http://lattes.cnpq.br/...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="bi bi-linkedin"></i> LinkedIn</label>
                                <input type="url" class="form-control" name="linkedin"
                                    value="<?php echo htmlspecialchars($prof['linkedin']); ?>"
                                    placeholder="http://linkedin.com/in/...">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Local do Gabinete</label>
                                <input type="text" class="form-control" name="gabinete"
                                    value="<?php echo htmlspecialchars($prof['gabinete']); ?>"
                                    placeholder="Ex: Prédio 1, Sala 203">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Horários de Atendimento</label>
                                <input type="text" class="form-control" name="atendimento"
                                    value="<?php echo htmlspecialchars($prof['atendimento']); ?>"
                                    placeholder="Ex: Terças e Quintas, 14h às 16h">
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">Salvar Alterações</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>