<?php
// controllers/ProducaoController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Caminhos absolutos
require_once __DIR__ . '/../config/database.php';
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
    // ACTION: CREATE (CRIAR)
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

                // Vínculo com Professor (Automático se for professor logado)
                if ($_SESSION['usuario_nivel'] == 'professor') {
                    $this->producao->id_professor = $_SESSION['professor_id'];
                } else {
                    // Se for admin criando, pode ter vindo de um select (opcional)
                    $this->producao->id_professor = !empty($_POST['id_professor']) ? $_POST['id_professor'] : null;
                }

                // 3. Salvar
                if ($this->producao->criar()) {
                    $_SESSION['sucesso'] = "Produção acadêmica cadastrada com sucesso!";
                    header("Location: ../views/sistema/producao/index.php");
                } else {
                    throw new Exception("Erro ao salvar no banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
                header("Location: ../views/sistema/producao/create.php");
            }
            exit();
        }
    }

    // ==========================================================
    // ACTION: UPDATE (ATUALIZAR)
    // ==========================================================
    public function update() {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $id = $_POST['id'];
                
                // 1. Buscar dados originais para manter o id_professor
                // Isso evita que a produção "perca o dono" e suma da lista do professor
                $dadosOriginais = $this->producao->buscarPorId($id);
                
                if (!$dadosOriginais) {
                    throw new Exception("Produção não encontrada.");
                }

                // 2. Validar Permissão (Admin ou Dono)
                if ($_SESSION['usuario_nivel'] != 'admin' && $dadosOriginais['id_professor'] != $_SESSION['professor_id']) {
                    throw new Exception("Você não tem permissão para editar este registro.");
                }
                
                // 3. Setar Novos Dados
                $this->producao->id = $id;
                $this->producao->titulo = trim($_POST['titulo']);
                $this->producao->autor = trim($_POST['autor']);
                $this->producao->tipo = $_POST['tipo'];
                $this->producao->tipo_outro = ($_POST['tipo'] == 'Outro') ? trim($_POST['tipo_outro']) : null;
                $this->producao->data_pub = !empty($_POST['data_pub']) ? $_POST['data_pub'] : null;
                $this->producao->idioma = $_POST['idioma'];
                $this->producao->link = trim($_POST['link']);
                
                // 4. IMPORTANTE: Manter o dono original
                $this->producao->id_professor = $dadosOriginais['id_professor'];

                // 5. Atualizar
                if ($this->producao->atualizar()) {
                    $_SESSION['sucesso'] = "Produção atualizada com sucesso!";
                } else {
                    throw new Exception("Erro ao atualizar produção no banco de dados.");
                }
                
            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
            }
            
            header("Location: ../views/sistema/producao/index.php");
            exit();
        }
    }

    // ==========================================================
    // ACTION: DELETE (EXCLUIR)
    // ==========================================================
    public function delete()
    {
        $this->checkAuth();

        if (isset($_POST['id'])) {
            try {
                $id = $_POST['id'];
                
                // 1. Validar permissão (Apenas Admin ou o Próprio Dono)
                if ($_SESSION['usuario_nivel'] != 'admin') {
                    $dados = $this->producao->buscarPorId($id);
                    if (!$dados || $dados['id_professor'] != $_SESSION['professor_id']) {
                        throw new Exception("Permissão negada ou registro não encontrado.");
                    }
                }

                // 2. Excluir
                if ($this->producao->delete($id)) {
                    $_SESSION['sucesso'] = "Registro excluído com sucesso!";
                } else {
                    throw new Exception("Erro ao excluir do banco de dados.");
                }
            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
            }
            header("Location: ../views/sistema/producao/index.php");
            exit();
        }
    }

    // Método Auxiliar de Autenticação
    private function checkAuth() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }
    }
}

// Roteador Simples
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