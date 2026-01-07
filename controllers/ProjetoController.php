<?php
// controllers/ProjetoController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Imports com caminhos absolutos
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Projeto.php';

// Verifica se o manipulador de imagens existe (Opcional, mas recomendado)
if (file_exists(__DIR__ . '/../utils/ImageHandler.php')) {
    require_once __DIR__ . '/../utils/ImageHandler.php';
}

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

        // Pasta onde as imagens serão salvas
        $this->uploadDir = __DIR__ . '/../assets/img/projetos/';

        // Cria a pasta se não existir, com permissões de escrita
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    // ==========================================================
    // ACTION: CREATE (CRIAR)
    // ==========================================================
    public function create()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Iniciar Transação
                $this->db->beginTransaction();

                // 1. Validar e Preencher Dados do Projeto
                if (empty($_POST['titulo']) || empty($_POST['descricao']) || empty($_POST['area_conhecimento'])) {
                    throw new Exception("Preencha os campos obrigatórios.");
                }
                $this->preencherDadosProjeto();

                // 2. Salvar Projeto no Banco
                if (!$this->projeto->criar()) {
                    throw new Exception("Erro ao salvar dados do projeto.");
                }

                // Recupera o ID gerado automaticamente
                $id_projeto = $this->projeto->id;

                // 3. Vincular Professores
                $this->vincularProfessores($id_projeto);

                // 4. Upload de Múltiplas Imagens
                $this->uploadImagens($id_projeto);

                // Confirmar Transação
                $this->db->commit();

                $_SESSION['sucesso'] = "Projeto cadastrado com sucesso!";
                header("Location: ../views/sistema/projeto.php");
                exit();

            } catch (Exception $e) {
                // Reverte tudo se der erro
                if ($this->db->inTransaction())
                    $this->db->rollBack();
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
                header("Location: ../views/sistema/projeto/create.php");
                exit();
            }
        }
    }

    // ==========================================================
    // ACTION: UPDATE (ATUALIZAR)
    // ==========================================================
    public function update()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $id_projeto = $_POST['id'] ?? null;
                if (!$id_projeto)
                    throw new Exception("ID inválido.");

                // Verificar Permissão (Admin ou Dono)
                if ($_SESSION['usuario_nivel'] != 'admin') {
                    $stmtAuth = $this->db->prepare("SELECT COUNT(*) FROM professor_projeto WHERE id_projeto = ? AND id_professor = ?");
                    $stmtAuth->execute([$id_projeto, $_SESSION['professor_id']]);
                    if ($stmtAuth->fetchColumn() == 0)
                        throw new Exception("Você não tem permissão para editar este projeto.");
                }

                $this->db->beginTransaction();

                // 1. Atualizar Dados
                $this->projeto->id = $id_projeto;
                $this->preencherDadosProjeto();

                if (!$this->projeto->atualizar()) {
                    throw new Exception("Falha ao atualizar tabela.");
                }

                // 2. Atualizar Professores (Remove vínculos antigos e insere novos)
                $stmtDel = $this->db->prepare("DELETE FROM professor_projeto WHERE id_projeto = ?");
                $stmtDel->execute([$id_projeto]);
                $this->vincularProfessores($id_projeto);

                // 3. Adicionar Novas Imagens (Não apaga as antigas, apenas adiciona)
                $this->uploadImagens($id_projeto);

                $this->db->commit();

                $_SESSION['sucesso'] = "Projeto atualizado com sucesso!";
                header("Location: ../views/sistema/projeto/edit.php?id=" . $id_projeto);
                exit();

            } catch (Exception $e) {
                if ($this->db->inTransaction())
                    $this->db->rollBack();
                $_SESSION['erro'] = $e->getMessage();
                header("Location: ../views/sistema/projeto/edit.php?id=" . $_POST['id']);
                exit();
            }
        }
    }

    // ==========================================================
    // ACTION: DELETE (EXCLUIR)
    // ==========================================================
    public function delete()
    {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
            $id = $_POST['id'];

            try {
                // Verificar Permissão
                $pode_deletar = false;
                if ($_SESSION['usuario_nivel'] == 'admin') {
                    $pode_deletar = true;
                } else {
                    $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM professor_projeto WHERE id_projeto = ? AND id_professor = ?");
                    $stmtCheck->execute([$id, $_SESSION['professor_id']]);
                    if ($stmtCheck->fetchColumn() > 0)
                        $pode_deletar = true;
                }

                if (!$pode_deletar)
                    throw new Exception("Permissão negada.");

                // 1. Buscar imagens para remover arquivos físicos
                $stmtImgs = $this->db->prepare("SELECT caminho FROM imagens WHERE id_projeto = ?");
                $stmtImgs->execute([$id]);
                $imagens = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

                // 2. Excluir do Banco (CASCADE cuida dos vínculos)
                if ($this->projeto->delete($id)) {
                    // 3. Remover arquivos físicos
                    foreach ($imagens as $arquivo) {
                        $caminhoCompleto = $this->uploadDir . $arquivo;
                        if (file_exists($caminhoCompleto))
                            unlink($caminhoCompleto);
                    }
                    $_SESSION['sucesso'] = "Projeto excluído com sucesso!";
                } else {
                    throw new Exception("Erro ao excluir do banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
            }

            header("Location: ../views/sistema/projeto.php");
            exit();
        }

        header("Location: ../views/sistema/projeto.php");
        exit();
    }

    // ==========================================================
    // MÉTODOS AUXILIARES (PRIVADOS)
    // ==========================================================

    /**
     * Preenche as propriedades do objeto Projeto com dados do POST
     */
    private function preencherDadosProjeto()
    {
        $this->projeto->titulo = $_POST['titulo'];
        $this->projeto->autor = $_POST['autor'];
        $this->projeto->descricao = $_POST['descricao'];
        $this->projeto->status = $_POST['status'];
        $this->projeto->area_conhecimento = $_POST['area_conhecimento'];

        // Tratamento de datas e nulos
        $this->projeto->data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
        $this->projeto->data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

        // Campos extras
        $this->projeto->links = $_POST['links'] ?? null;
        $this->projeto->parceria = $_POST['parceria'] ?? null;
        $this->projeto->objetivos = $_POST['objetivos'] ?? null;
        $this->projeto->resultados = $_POST['resultados'] ?? null;
        $this->projeto->alunos_envolvidos = $_POST['alunos_envolvidos'] ?? null;
        $this->projeto->agencia_financiadora = $_POST['agencia_financiadora'] ?? null;

        // Formata moeda (R$ 1.000,00 -> 1000.00)
        $this->projeto->financiamento = !empty($_POST['financiamento']) ? str_replace(',', '.', $_POST['financiamento']) : null;
    }

    /**
     * Vincula IDs de professores ao projeto na tabela N:N
     */
    private function vincularProfessores($id_projeto)
    {
        $ids = $_POST['professores'] ?? [];

        // Se for professor editando, garante que ele não se removeu da lista
        if ($_SESSION['usuario_nivel'] == 'professor' && !in_array($_SESSION['professor_id'], $ids)) {
            $ids[] = $_SESSION['professor_id'];
        }

        if (empty($ids))
            throw new Exception("Selecione ao menos um autor.");

        $stmt = $this->db->prepare("INSERT INTO professor_projeto (id_professor, id_projeto) VALUES (?, ?)");
        foreach ($ids as $id_prof) {
            $stmt->execute([$id_prof, $id_projeto]);
        }
    }

    /**
     * Processa o upload de múltiplas imagens (imagens[])
     */
    private function uploadImagens($id_projeto)
    {
        // Verifica se existem imagens enviadas
        if (isset($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {

            $stmtImg = $this->db->prepare("INSERT INTO imagens (caminho, id_projeto, legenda) VALUES (?, ?, ?)");
            $files = $_FILES['imagens'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {

                    // Monta array simula $_FILES para um único arquivo
                    $fileData = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    // Nome único: idproj_timestamp_indice.ext
                    $novoNome = "proj_{$id_projeto}_" . time() . "_{$i}." . $ext;

                    $salvo = false;

                    // Tenta usar ImageHandler se disponível, senão usa nativo
                    if (class_exists('ImageHandler')) {
                        if (ImageHandler::resizeAndSave($fileData, $this->uploadDir, $novoNome, 1024)) {
                            $salvo = true;
                        }
                    } else {
                        if (move_uploaded_file($fileData['tmp_name'], $this->uploadDir . $novoNome)) {
                            $salvo = true;
                        }
                    }

                    if ($salvo) {
                        // Legenda padrão. Você pode criar lógica para 'Capa' se for a primeira imagem.
                        $legenda = ($i == 0) ? 'Capa' : 'Galeria';
                        $stmtImg->execute([$novoNome, $id_projeto, $legenda]);
                    }
                }
            }
        }
    }

    private function checkAuth()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }
        if ($_SESSION['usuario_nivel'] == 'professor' && !isset($_SESSION['professor_id'])) {
            $_SESSION['erro'] = "Complete seu perfil antes de gerenciar projetos.";
            header("Location: ../views/sistema/professor/create.php?completar=1");
            exit();
        }
    }
}

// Roteador Simples
if (isset($_GET['action'])) {
    $controller = new ProjetoController();
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        $controller->$action();
    }
}
?>