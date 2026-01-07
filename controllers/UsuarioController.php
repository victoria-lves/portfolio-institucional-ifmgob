<?php
// controllers/UsuarioController.php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private $db;
    private $usuario;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
    }

    public function create()
    {
        // Apenas admin pode criar
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] != 'admin') {
            header("Location: ../views/sistema/painel.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Sanitização básica
            $this->usuario->nome = trim($_POST['nome']);
            $this->usuario->email = trim($_POST['email']);
            
            // 3. Segurança: Criptografar a senha antes de salvar
            // O código original salvava em texto puro, o que é inseguro.
            $senha_plana = $_POST['senha'];
            $this->usuario->senha = password_hash($senha_plana, PASSWORD_DEFAULT);
            
            $this->usuario->nivel = 'professor'; // Padrão ao criar por aqui

            // Verificar duplicidade de email
            if ($this->usuario->emailExiste()) {
                $_SESSION['erro'] = "Este email já está cadastrado!";
                header("Location: ../views/sistema/usuario/create.php");
                exit();
            }

            if ($this->usuario->criar()) {
                $_SESSION['sucesso'] = "Professor cadastrado com sucesso!";
                header("Location: ../views/sistema/usuario/index.php");
            } else {
                $_SESSION['erro'] = "Erro ao cadastrar usuário.";
                header("Location: ../views/sistema/usuario/create.php");
            }
            exit();
        }
    }

    public function delete()
    {
        // 1. Verificação de Segurança
        if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] != 'admin') {
            $_SESSION['erro'] = "Acesso negado.";
            header("Location: ../views/sistema/painel.php");
            exit();
        }

        // 2. Receber ID
        if (!isset($_POST['id'])) {
            header("Location: ../views/sistema/usuario/index.php");
            exit();
        }

        $id_para_deletar = $_POST['id'];

        // 3. Evitar auto-exclusão (Admin não pode se deletar)
        if ($id_para_deletar == $_SESSION['usuario_id']) {
            $_SESSION['erro'] = "Você não pode excluir sua própria conta!";
            header("Location: ../views/sistema/usuario/index.php");
            exit();
        }

        try {
            // 4. Limpeza de Arquivos (Melhoria)
            // Antes de deletar o usuário, verificamos se ele é um professor e se tem foto para apagar.
            // Isso evita "lixo" na pasta de imagens, já que o CASCADE do banco apaga o registro mas não o arquivo.
            $queryFoto = "SELECT pfp FROM professor WHERE id_usuario = :id LIMIT 1";
            $stmtFoto = $this->db->prepare($queryFoto);
            $stmtFoto->execute([':id' => $id_para_deletar]);
            $professor = $stmtFoto->fetch(PDO::FETCH_ASSOC);

            if ($professor && !empty($professor['pfp']) && $professor['pfp'] != 'default.webp') {
                $caminhoFoto = __DIR__ . '/../assets/img/docentes/' . $professor['pfp'];
                if (file_exists($caminhoFoto)) {
                    unlink($caminhoFoto);
                }
            }

            // 5. Executar exclusão via Model
            // O Banco de dados fará o CASCADE apagando o registro na tabela 'professor' automaticamente
            if ($this->usuario->delete($id_para_deletar)) {
                $_SESSION['sucesso'] = "Usuário excluído com sucesso!";
            } else {
                $_SESSION['erro'] = "Erro ao excluir usuário. Verifique vínculos.";
            }

        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro: " . $e->getMessage();
        }

        header("Location: ../views/sistema/usuario/index.php");
        exit();
    }
}

// Roteamento de Ações
if (isset($_GET['action'])) {
    $controller = new UsuarioController();

    switch ($_GET['action']) {
        case 'create':
            $controller->create();
            break;
        case 'delete':
            $controller->delete();
            break;
        default:
            header("Location: ../views/sistema/painel.php");
            break;
    }
}
?>