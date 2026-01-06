<?php
// controllers/ProjetoController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [CORREÇÃO 1] Caminho ajustado para subir apenas um nível (../)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Projeto.php';
// Importante: ImageHandler para uploads seguros
require_once __DIR__ . '/../utils/ImageHandler.php';

class ProjetoController
{
    private $db;
    private $projeto;
    private $uploadDir;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->projeto = new Projeto($this->db);
        $this->uploadDir = __DIR__ . '/../assets/img/projetos/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    // ==========================================================
    // ACTION: CREATE
    // ==========================================================
    public function create()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Iniciar Transação (ACID)
                $this->db->beginTransaction();

                // 1. Validação Básica
                if (empty($_POST['titulo']) || empty($_POST['descricao']) || empty($_POST['area_conhecimento'])) {
                    throw new Exception("Preencha os campos obrigatórios (Título, Área e Descrição).");
                }

                // 2. Preparar Autores (Professores)
                $ids_professores = $_POST['professores'] ?? [];
                
                // Se for professor logado, garante que ele está incluído
                if ($_SESSION['usuario_nivel'] == 'professor' && !in_array($_SESSION['professor_id'], $ids_professores)) {
                    array_unshift($ids_professores, $_SESSION['professor_id']);
                }

                if (empty($ids_professores)) {
                    throw new Exception("Selecione pelo menos um professor autor.");
                }

                // 3. Setar dados no Model
                $this->projeto->titulo = $_POST['titulo'];
                $this->projeto->autor = $_POST['autor'];
                $this->projeto->descricao = $_POST['descricao'];
                $this->projeto->data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
                $this->projeto->status = $_POST['status'];
                $this->projeto->data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
                $this->projeto->links = $_POST['links'] ?? null;
                $this->projeto->parceria = $_POST['parceria'] ?? null;
                $this->projeto->objetivos = $_POST['objetivos'] ?? null;
                $this->projeto->resultados = $_POST['resultados'] ?? null;
                $this->projeto->area_conhecimento = $_POST['area_conhecimento'];
                $this->projeto->alunos_envolvidos = $_POST['alunos_envolvidos'] ?? null;
                $this->projeto->agencia_financiadora = $_POST['agencia_financiadora'] ?? null;
                $this->projeto->financiamento = !empty($_POST['financiamento']) ? str_replace(',', '.', $_POST['financiamento']) : null;

                // 4. Salvar Projeto
                if (!$this->projeto->criar()) {
                    throw new Exception("Erro ao salvar dados do projeto.");
                }

                $id_projeto = $this->projeto->id;

                // 5. Vincular Professores (Tabela de Ligação)
                $stmtVinculo = $this->db->prepare("INSERT INTO professor_projeto (id_professor, id_projeto) VALUES (?, ?)");
                foreach ($ids_professores as $id_prof) {
                    $stmtVinculo->execute([$id_prof, $id_projeto]);
                }

                // 6. Upload de Imagem (Capa)
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                    $novoNome = uniqid('proj_') . '.webp';
                    
                    if (ImageHandler::resizeAndSave($_FILES['imagem'], $this->uploadDir, $novoNome, 1024)) {
                        $stmtImg = $this->db->prepare("INSERT INTO imagens (caminho, id_projeto, legenda) VALUES (?, ?, 'Capa')");
                        $stmtImg->execute([$novoNome, $id_projeto]);
                    }
                }

                // Confirmar Transação
                $this->db->commit();
                
                $_SESSION['sucesso'] = "Projeto criado com sucesso!";
                header("Location: ../views/sistema/projeto.php");
                exit();

            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
                header("Location: ../views/projeto/create.php");
                exit();
            }
        }
    }

    // ==========================================================
    // ACTION: UPDATE
    // ==========================================================
    public function update()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $id_projeto = $_POST['id'];
                if (!$id_projeto) throw new Exception("ID do projeto inválido.");

                // Validar Permissão de Edição
                if ($_SESSION['usuario_nivel'] != 'admin') {
                    $stmtAuth = $this->db->prepare("SELECT COUNT(*) FROM professor_projeto WHERE id_projeto = ? AND id_professor = ?");
                    $stmtAuth->execute([$id_projeto, $_SESSION['professor_id']]);
                    if ($stmtAuth->fetchColumn() == 0) {
                        throw new Exception("Você não tem permissão para editar este projeto.");
                    }
                }

                $this->db->beginTransaction();

                // 1. Atualizar Dados
                $this->projeto->id = $id_projeto;
                $this->projeto->titulo = $_POST['titulo'];
                $this->projeto->autor = $_POST['autor'];
                $this->projeto->descricao = $_POST['descricao'];
                $this->projeto->data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
                $this->projeto->status = $_POST['status'];
                $this->projeto->data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
                $this->projeto->links = $_POST['links'] ?? null;
                $this->projeto->parceria = $_POST['parceria'] ?? null;
                $this->projeto->objetivos = $_POST['objetivos'] ?? null;
                $this->projeto->resultados = $_POST['resultados'] ?? null;
                $this->projeto->area_conhecimento = $_POST['area_conhecimento'];
                $this->projeto->alunos_envolvidos = $_POST['alunos_envolvidos'] ?? null;
                $this->projeto->agencia_financiadora = $_POST['agencia_financiadora'] ?? null;
                $this->projeto->financiamento = !empty($_POST['financiamento']) ? str_replace(',', '.', $_POST['financiamento']) : null;

                if (!$this->projeto->atualizar()) {
                    throw new Exception("Falha ao atualizar tabela de projetos.");
                }

                // 2. Atualizar Autores
                $ids_professores = $_POST['professores'] ?? [];
                if ($_SESSION['usuario_nivel'] == 'professor' && !in_array($_SESSION['professor_id'], $ids_professores)) {
                    $ids_professores[] = $_SESSION['professor_id'];
                }

                if (empty($ids_professores)) throw new Exception("O projeto deve ter pelo menos um autor.");

                $stmtDel = $this->db->prepare("DELETE FROM professor_projeto WHERE id_projeto = ?");
                $stmtDel->execute([$id_projeto]);

                $stmtIns = $this->db->prepare("INSERT INTO professor_projeto (id_professor, id_projeto) VALUES (?, ?)");
                foreach ($ids_professores as $id_prof) {
                    $stmtIns->execute([$id_prof, $id_projeto]);
                }

                // 3. Upload de Nova Imagem
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                    $novoNome = uniqid('proj_') . '.webp';
                    if (ImageHandler::resizeAndSave($_FILES['imagem'], $this->uploadDir, $novoNome, 1024)) {
                        $stmtImg = $this->db->prepare("INSERT INTO imagens (caminho, id_projeto, legenda) VALUES (?, ?, 'Capa')");
                        $stmtImg->execute([$novoNome, $id_projeto]);
                    }
                }

                $this->db->commit();
                $_SESSION['sucesso'] = "Projeto atualizado com sucesso!";
                header("Location: ../views/projeto/edit.php?id=" . $id_projeto);
                exit();

            } catch (Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                $_SESSION['erro'] = $e->getMessage();
                header("Location: ../views/projeto/edit.php?id=" . $_POST['id']);
                exit();
            }
        }
    }

    // ==========================================================
    // ACTION: DELETE
    // ==========================================================
    public function delete()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
            $id = $_POST['id'];

            try {
                // [CORREÇÃO 2] Verificação de Permissão: Admin OU Dono do projeto
                $pode_deletar = false;
                if ($_SESSION['usuario_nivel'] == 'admin') {
                    $pode_deletar = true;
                } else {
                    // Verifica se o professor logado é um dos autores
                    $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM professor_projeto WHERE id_projeto = ? AND id_professor = ?");
                    $stmtCheck->execute([$id, $_SESSION['professor_id']]);
                    if ($stmtCheck->fetchColumn() > 0) {
                        $pode_deletar = true;
                    }
                }

                if (!$pode_deletar) {
                    throw new Exception("Permissão negada. Você não pode excluir este projeto.");
                }

                // 1. Buscar imagens para remover arquivos físicos
                $stmtImgs = $this->db->prepare("SELECT caminho FROM imagens WHERE id_projeto = ?");
                $stmtImgs->execute([$id]);
                $imagens = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

                // 2. Excluir do Banco (CASCADE cuida das tabelas relacionadas)
                if ($this->projeto->delete($id)) {
                    // 3. Remover arquivos físicos
                    foreach ($imagens as $arquivo) {
                        $caminhoCompleto = $this->uploadDir . $arquivo;
                        if (file_exists($caminhoCompleto)) {
                            unlink($caminhoCompleto);
                        }
                    }
                    $_SESSION['sucesso'] = "Projeto excluído permanentemente!";
                } else {
                    throw new Exception("Erro ao excluir o projeto do banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
            }

            header("Location: ../views/projeto/index.php");
            exit();
        }
        
        header("Location: ../views/projeto/index.php");
        exit();
    }

    private function checkAuth() {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }
        if ($_SESSION['usuario_nivel'] == 'professor' && !isset($_SESSION['professor_id'])) {
            $_SESSION['erro'] = "Complete seu perfil antes de gerenciar projetos.";
            header("Location: ../views/professor/create.php?completar=1");
            exit();
        }
    }
}

// Roteamento
if (isset($_GET['action'])) {
    $controller = new ProjetoController();
    if ($_GET['action'] == 'create') $controller->create();
    if ($_GET['action'] == 'update') $controller->update();
    if ($_GET['action'] == 'delete') $controller->delete();
}
?>