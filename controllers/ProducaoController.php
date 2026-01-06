<?php
// controllers/ProducaoController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Producao.php';

class ProducaoController
{
    private $db;
    private $producao;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->producao = new Producao($this->db);
    }

    // ==========================================================
    // ACTION: CREATE
    // ==========================================================
    public function create()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // 1. Validar Campos Obrigatórios
                if (empty($_POST['titulo']) || empty($_POST['autor']) || empty($_POST['tipo']) || empty($_POST['idioma'])) {
                    throw new Exception("Preencha todos os campos obrigatórios.");
                }

                // 2. Setar Dados no Model
                $this->producao->titulo = trim($_POST['titulo']);
                $this->producao->autor = trim($_POST['autor']);
                $this->producao->tipo = $_POST['tipo'];
                $this->producao->idioma = $_POST['idioma'];
                
                // Campos opcionais / condicionais
                $this->producao->data_pub = !empty($_POST['data_pub']) ? $_POST['data_pub'] : null;
                $this->producao->tipo_outro = ($_POST['tipo'] === 'Outro') ? trim($_POST['tipo_outro'] ?? '') : null;
                $this->producao->idioma_outro = ($_POST['idioma'] === 'Outro') ? trim($_POST['idioma_outro'] ?? '') : null;
                $this->producao->link = trim($_POST['link'] ?? '');

                // Vínculo com Professor (Automático ou Selecionado pelo Admin)
                if ($_SESSION['usuario_nivel'] == 'professor') {
                    $this->producao->id_professor = $_SESSION['professor_id'];
                } else {
                    $this->producao->id_professor = !empty($_POST['id_professor']) ? $_POST['id_professor'] : null;
                }

                // 3. Salvar
                if ($this->producao->criar()) {
                    $_SESSION['sucesso'] = "Produção acadêmica cadastrada com sucesso!";
                    header("Location: ../views/producao/index.php");
                } else {
                    throw new Exception("Erro ao salvar no banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
                // Redireciona de volta com erro
                header("Location: ../views/producao/create.php");
            }
            exit();
        }
    }

    // ==========================================================
    // ACTION: UPDATE (Se houver edição no futuro)
    // ==========================================================
    public function update()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $this->producao->id = $_POST['id'];
                // ... lógica similar ao create, chamando $this->producao->atualizar()
                // Implemente se tiver a view edit.php
            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
                header("Location: ../views/producao/index.php");
            }
            exit();
        }
    }

    // ==========================================================
    // ACTION: DELETE
    // ==========================================================
    public function delete()
    {
        $this->checkAuth();

        if (isset($_POST['id'])) {
            try {
                $id = $_POST['id'];
                
                // Validar permissão (Apenas Admin ou o Próprio Dono)
                if ($_SESSION['usuario_nivel'] != 'admin') {
                    $dados = $this->producao->buscarPorId($id);
                    if ($dados['id_professor'] != $_SESSION['professor_id']) {
                        throw new Exception("Permissão negada.");
                    }
                }

                if ($this->producao->delete($id)) {
                    $_SESSION['sucesso'] = "Registro excluído com sucesso!";
                } else {
                    throw new Exception("Erro ao excluir.");
                }
            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
            }
            header("Location: ../views/producao/index.php");
            exit();
        }
    }

    private function checkAuth() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }
    }
}

// Roteamento
if (isset($_GET['action'])) {
    $controller = new ProducaoController();
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
    }
}
?>