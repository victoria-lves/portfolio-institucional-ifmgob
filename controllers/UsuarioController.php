<?php
// ==============================================
// controllers/UsuarioController.php
// ==============================================

session_start();

require_once '../../config/database.php';
require_once '../models/Usuario.php';

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
            $this->usuario->senha = $_POST['senha'];
            $this->usuario->nivel = 'professor'; // Padrão ao criar por aqui

            // Verificar duplicidade de email
            if ($this->usuario->emailExiste()) {
                $_SESSION['erro'] = "Este email já está cadastrado!";
                header("Location: ../views/usuario/create.php");
                exit();
            }

            if ($this->usuario->criar()) {
                $_SESSION['sucesso'] = "Professor cadastrado com sucesso!";
                header("Location: ../views/usuario/index.php");
            } else {
                $_SESSION['erro'] = "Erro ao cadastrar usuário.";
                header("Location: ../views/usuario/create.php");
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
            header("Location: ../views/usuario/index.php");
            exit();
        }

        $id_para_deletar = $_POST['id'];

        // 3. Evitar auto-exclusão (Admin não pode se deletar)
        if ($id_para_deletar == $_SESSION['usuario_id']) {
            $_SESSION['erro'] = "Você não pode excluir sua própria conta!";
            header("Location: ../views/usuario/index.php");
            exit();
        }

        // 4. Executar exclusão via Model
        if ($this->usuario->delete($id_para_deletar)) {
            $_SESSION['sucesso'] = "Usuário excluído com sucesso!";
        } else {
            $_SESSION['erro'] = "Erro ao excluir usuário. Verifique se ele possui vínculos.";
        }

        header("Location: ../views/usuario/index.php");
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