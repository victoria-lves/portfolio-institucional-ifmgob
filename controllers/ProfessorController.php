<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações e Imports
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Professor.php';
require_once __DIR__ . '/../utils/ImageHandler.php';

class ProfessorController
{
    private $db;
    private $professor;
    private $uploadDir;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->professor = new Professor($this->db);

        // Caminho da pasta de imagens (ajustado para a estrutura informada no edit.php: ../../assets/img/docentes/)
        $this->uploadDir = __DIR__ . '/../assets/img/docentes/';

        // Garante que a pasta existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function create()
    {
        // 1. Verificar Login
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // 2. Upload de Imagem Seguro (usando ImageHandler)
                // O form create.php usa 'foto_perfil'
                $nome_imagem = null;

                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $novoNome = uniqid('docente_') . '.webp';
                    
                    if (ImageHandler::resizeAndSave($_FILES['foto_perfil'], $this->uploadDir, $novoNome, 800)) {
                        $nome_imagem = $novoNome;
                    } else {
                        throw new Exception("Falha ao processar imagem. Use JPG, PNG ou WEBP.");
                    }
                }

                // 3. Popular Objeto Professor (Mapeando inputs do create.php para colunas do Banco)
                // create.php usa: nome, email, titulacao, area_atuacao, biografia, link_lattes
                
                $this->professor->nome = $_POST['nome'];
                $this->professor->email = $_POST['email'];
                
                // Mapeamento: Titulação (Form) -> Formação (Banco)
                $this->professor->formacao = $_POST['titulacao'] ?? null;
                
                // Mapeamento: Área de Atuação (Form) -> Disciplina (Banco/Conceito mais próximo)
                $this->professor->disciplina = $_POST['area_atuacao'] ?? null;
                
                // Mapeamento: Biografia (Form) -> Bio (Banco)
                $this->professor->bio = $_POST['biografia'] ?? null;
                
                // Mapeamento: Link Lattes (Form) -> Lattes (Banco)
                $this->professor->lattes = $_POST['link_lattes'] ?? null;
                
                $this->professor->pfp = $nome_imagem; // Campo 'pfp' no banco

                // Campos opcionais que não existem no create.php (ficam NULL)
                $this->professor->linkedin = null;
                $this->professor->gabinete = null;
                $this->professor->atendimento = null;

                // 4. Vincular ID do Usuário
                if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_nivel'] == 'professor') {
                    $this->professor->id_usuario = $_SESSION['usuario_id'];
                } else if (isset($_POST['usuario_id'])) {
                    $this->professor->id_usuario = $_POST['usuario_id']; // Caso Admin esteja criando para outro
                } else {
                    throw new Exception("ID do usuário obrigatório.");
                }

                // 5. Salvar no Banco
                if ($this->professor->criar()) {
                    if ($_SESSION['usuario_nivel'] == 'professor') {
                        $_SESSION['professor_id'] = $this->db->lastInsertId();
                        $_SESSION['sucesso'] = "Perfil criado com sucesso!";
                        header("Location: ../views/sistema/painel.php");
                    } else {
                        $_SESSION['sucesso'] = "Professor cadastrado com sucesso!";
                        header("Location: ../views/sistema/professor/edit.php"); // Ou lista de professores
                    }
                } else {
                    throw new Exception("Erro ao inserir registro no banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
                header("Location: ../views/sistema/professor/create.php");
            }
            exit();
        }
    }

    public function update()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: ../views/auth/login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // O edit.php não passa ID hidden normalmente, mas pega da URL ou Sessão. 
                // Assumindo que o form envie um input hidden 'id' ou que peguemos da sessão.
                $id = $_POST['id'] ?? $_SESSION['professor_id'];

                if (!$id) {
                    throw new Exception("ID do professor não identificado.");
                }

                // Verificação de permissão
                if ($_SESSION['usuario_nivel'] != 'admin') {
                    if (!isset($_SESSION['professor_id']) || $_SESSION['professor_id'] != $id) {
                        throw new Exception("Sem permissão para editar este perfil.");
                    }
                }

                // Buscar dados atuais para manter a foto se não for trocada
                $dadosAtuais = $this->professor->buscarPorId($id);
                if (!$dadosAtuais) throw new Exception("Professor não encontrado.");

                $this->professor->id = $id;

                // 1. Processar Imagem (edit.php usa name='pfp')
                if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] === UPLOAD_ERR_OK) {
                    $novoNome = uniqid('docente_') . '.webp';
                    
                    if (ImageHandler::resizeAndSave($_FILES['pfp'], $this->uploadDir, $novoNome, 800)) {
                        $this->professor->pfp = $novoNome;

                        // Apagar imagem antiga se existir e não for padrão
                        if (!empty($dadosAtuais['pfp']) && 
                            $dadosAtuais['pfp'] != 'default.webp' && 
                            file_exists($this->uploadDir . $dadosAtuais['pfp'])) {
                            unlink($this->uploadDir . $dadosAtuais['pfp']);
                        }
                    } else {
                        throw new Exception("Falha ao salvar a nova imagem.");
                    }
                } else {
                    // Mantém a imagem atual
                    $this->professor->pfp = $dadosAtuais['pfp'];
                }

                // 2. Popular Campos (Suportando nomes do edit.php E create.php para flexibilidade)
                
                $this->professor->nome = $_POST['nome'];
                $this->professor->email = $_POST['email'];
                
                // Mapeamentos Flexíveis (Pega o que vier)
                $this->professor->bio = $_POST['bio'] ?? $_POST['biografia'] ?? $dadosAtuais['bio'];
                $this->professor->formacao = $_POST['formacao'] ?? $_POST['titulacao'] ?? $dadosAtuais['formacao'];
                $this->professor->disciplina = $_POST['disciplina'] ?? $_POST['area_atuacao'] ?? $dadosAtuais['disciplina'];
                $this->professor->lattes = $_POST['lattes'] ?? $_POST['link_lattes'] ?? $dadosAtuais['lattes'];
                
                // Novos campos específicos do edit.php/Banco atualizado
                $this->professor->linkedin = $_POST['linkedin'] ?? $dadosAtuais['linkedin'];
                $this->professor->gabinete = $_POST['gabinete'] ?? $dadosAtuais['gabinete'];
                $this->professor->atendimento = $_POST['atendimento'] ?? $dadosAtuais['atendimento'];

                // 3. Atualizar
                if ($this->professor->atualizar()) {
                    $_SESSION['sucesso'] = "Perfil atualizado com sucesso!";
                    
                    // Se for o próprio usuário, atualiza o nome na sessão também
                    if (isset($_SESSION['professor_id']) && $_SESSION['professor_id'] == $id) {
                        $_SESSION['usuario_nome'] = $_POST['nome'];
                    }

                    // Redireciona
                    if ($_SESSION['usuario_nivel'] == 'admin') {
                        // Se admin editou, volta para lista ou para o mesmo edit
                         header("Location: ../views/sistema/professor/edit.php?id=" . $id);
                    } else {
                        header("Location: ../views/sistema/professor/edit.php?id=" . $id);
                    }
                } else {
                    throw new Exception("Erro ao atualizar o banco de dados.");
                }

            } catch (Exception $e) {
                $_SESSION['erro'] = $e->getMessage();
                // Tenta voltar para a página de edição correta
                $idRedirect = isset($_POST['id']) ? "?id=" . $_POST['id'] : "";
                header("Location: ../views/sistema/professor/edit.php" . $idRedirect);
            }
            exit();
        }
    }

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
                    // Apagar foto se existir
                    if ($dados && !empty($dados['pfp']) && $dados['pfp'] != 'default.webp') {
                        $caminho = $this->uploadDir . $dados['pfp'];
                        if (file_exists($caminho)) {
                            unlink($caminho);
                        }
                    }
                    $_SESSION['sucesso'] = "Professor excluído com sucesso!";
                } else {
                    throw new Exception("Não foi possível excluir. Verifique se há projetos vinculados.");
                }
            } catch (Exception $e) {
                $_SESSION['erro'] = "Erro: " . $e->getMessage();
            }

            // Redireciona para listagem
            header("Location: ../views/sistema/professor.php"); 
            exit();
        }
    }
}

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
    }
}
?>
