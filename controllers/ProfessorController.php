<?php
// ==============================================
// controllers/ProfessorController.php
// ==============================================

// 1. Correção da Sessão: Só inicia se não houver uma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Correção dos Caminhos: Usa __DIR__ para garantir o caminho correto independente de onde é chamado
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Professor.php';

class ProfessorController
{
    private $db;
    private $professor;
    // Caminho absoluto para evitar erros ao mover arquivos ou incluir de outros locais
    private $uploadDir;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->professor = new Professor($this->db);

        // Define o diretório de upload relativo à localização deste arquivo de controller
        $this->uploadDir = __DIR__ . '/../assets/img/docentes/';
    }

    // ==========================================================
    // CRIAR PERFIL
    // ==========================================================
    public function create()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $foto_nome = 'default.webp';

                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $foto_nome = $this->uploadImagem($_FILES['foto_perfil']);
                }

                $this->professor->nome = $_POST['nome'];
                $this->professor->email = $_POST['email'];
                $this->professor->titulacao = $_POST['titulacao'];
                $this->professor->area_atuacao = $_POST['area_atuacao'];
                $this->professor->biografia = $_POST['biografia'];
                $this->professor->link_lattes = $_POST['link_lattes'];
                $this->professor->foto_perfil = $foto_nome;

                // Vínculo com Usuario
                if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_nivel'] == 'professor') {
                    $this->professor->usuario_id = $_SESSION['usuario_id'];
                } else if (isset($_POST['usuario_id'])) {
                    $this->professor->usuario_id = $_POST['usuario_id'];
                }

                if ($this->professor->criar()) {
                    if ($_SESSION['usuario_nivel'] == 'professor') {
                        // Atualiza sessão se for o próprio professor
                        $_SESSION['professor_id'] = $this->db->lastInsertId();
                        $_SESSION['sucesso'] = "Perfil criado com sucesso!";
                        header("Location: ../views/sistema/painel.php");
                    } else {
                        $_SESSION['sucesso'] = "Professor cadastrado com sucesso!";
                        header("Location: ../views/professor/edit.php");
                    }
                } else {
                    throw new Exception("Erro ao salvar dados no banco.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
                // Redireciona de volta para a origem se possível, ou para o create
                header("Location: ../views/professor/create.php");
            }
            exit();
        }
    }

    // ==========================================================
    // ATUALIZAR PERFIL
    // ==========================================================
    public function update()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $id = $_POST['id'];

                if ($_SESSION['usuario_nivel'] != 'admin') {
                    if (!isset($_SESSION['professor_id']) || $_SESSION['professor_id'] != $id) {
                        throw new Exception("Sem permissão para editar este perfil.");
                    }
                }

                $dadosAtuais = $this->professor->buscarPorId($id);
                $this->professor->id = $id;
                $this->professor->nome = $_POST['nome'];
                $this->professor->email = $_POST['email'];
                $this->professor->titulacao = $_POST['titulacao'];
                $this->professor->area_atuacao = $_POST['area_atuacao'];
                $this->professor->biografia = $_POST['biografia'];
                $this->professor->link_lattes = $_POST['link_lattes'];

                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $this->professor->foto_perfil = $this->uploadImagem($_FILES['foto_perfil']);

                    if ($dadosAtuais['foto_perfil'] != 'default.webp' && file_exists($this->uploadDir . $dadosAtuais['foto_perfil'])) {
                        unlink($this->uploadDir . $dadosAtuais['foto_perfil']);
                    }
                } else {
                    $this->professor->foto_perfil = $dadosAtuais['foto_perfil'];
                }

                if ($this->professor->atualizar()) {
                    $_SESSION['sucesso'] = "Perfil atualizado com sucesso!";

                    if ($_SESSION['usuario_nivel'] == 'admin') {
                        header("Location: ../views/professor/edit.php");
                    } else {
                        header("Location: ../views/professor/edit.php?id=" . $id);
                    }
                } else {
                    throw new Exception("Erro ao atualizar o banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
                header("Location: ../views/professor/edit.php");
            }
            exit();
        }
    }

    // ==========================================================
    // EXCLUIR PROFESSOR
    // ==========================================================
    public function delete()
    {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] != 'admin') {
            $_SESSION['erro'] = "Acesso negado.";
            header("Location: ../views/sistema/painel.php");
            exit();
        }

        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            $dados = $this->professor->buscarPorId($id);

            try {
                if ($this->professor->delete($id)) {
                    if ($dados && $dados['foto_perfil'] != 'default.webp') {
                        $caminho = $this->uploadDir . $dados['foto_perfil'];
                        if (file_exists($caminho)) {
                            unlink($caminho);
                        }
                    }
                    $_SESSION['sucesso'] = "Professor excluído com sucesso!";
                } else {
                    throw new Exception("Não foi possível excluir. Verifique vínculos (projetos/produções).");
                }
            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
            }

            header("Location: ../views/professor/edit.php");
            exit();
        }
    }

    // ==========================================================
    // MÉTODO AUXILIAR DE UPLOAD
    // ==========================================================
    private function uploadImagem($arquivo)
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extensao, $permitidos)) {
            throw new Exception("Formato inválido. Use JPG, PNG ou WEBP.");
        }

        if ($arquivo['size'] > 5 * 1024 * 1024) {
            throw new Exception("Imagem muito grande. Máximo 5MB.");
        }

        $novoNome = uniqid('prof_') . '.' . $extensao;
        $caminhoCompleto = $this->uploadDir . $novoNome;

        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            return $novoNome;
        } else {
            throw new Exception("Falha ao salvar a imagem no servidor.");
        }
    }
}

// ==========================================================
// ROTEAMENTO
// ==========================================================
// Verifica se há uma ação definida na URL (GET) e executa o método correspondente
if (isset($_GET['action'])) {
    $controller = new ProfessorController();

    switch ($_GET['action']) {
        case 'create':
            $controller->create();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        // Não adicionamos default redirect aqui para evitar loops se incluído sem action
    }
}
?>