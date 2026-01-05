<?php
// controllers/AuthController.php
session_start();

// Importar configurações e modelos necessários
// Ajuste os caminhos conforme a estrutura de pastas
require_once '../config/database.php';
// Se tiver um Model Usuario, seria ideal usá-lo, mas vamos manter PDO direto por simplicidade e compatibilidade
// require_once '../models/Usuario.php'; 

class AuthController
{

    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login()
    {
        // Se já estiver logado, redireciona
        if (isset($_SESSION['usuario_id'])) {
            header("Location: ../views/sistema/painel.php");
            exit();
        }

        // Verifica se é POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            // Validação básica
            if (empty($email) || empty($senha)) {
                $_SESSION['erro'] = "Preencha todos os campos!";
                header("Location: ../views/auth/login.php");
                exit();
            }

            try {
                // Buscar usuário pelo email
                $query = "SELECT id, nome, email, senha, nivel FROM usuario WHERE email = :email LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Verificar senha (Hash)
                    if (password_verify($senha, $usuario['senha'])) {
                        // SUCESSO!

                        // Segurança: Regenerar ID da sessão para evitar fixação
                        session_regenerate_id(true);

                        // Definir variáveis de sessão
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nome'] = $usuario['nome'];
                        $_SESSION['usuario_nivel'] = $usuario['nivel'];

                        // Se for professor, tenta buscar o ID de professor (opcional, mas útil)
                        if ($usuario['nivel'] == 'professor') {
                            $stmtProf = $this->db->prepare("SELECT id FROM professor WHERE usuario_id = :uid LIMIT 1");
                            $stmtProf->execute([':uid' => $usuario['id']]);
                            if ($prof = $stmtProf->fetch(PDO::FETCH_ASSOC)) {
                                $_SESSION['professor_id'] = $prof['id'];
                            }
                        }

                        $_SESSION['login_time'] = time();

                        header("Location: ../views/sistema/painel.php");
                        exit();
                    } else {
                        $_SESSION['erro'] = "Senha incorreta!";
                    }
                } else {
                    $_SESSION['erro'] = "E-mail não encontrado!";
                }
            } catch (PDOException $e) {
                $_SESSION['erro'] = "Erro no sistema: " . $e->getMessage();
            }

            // Se chegou aqui, deu erro, volta para o login
            // Preserva o email para não precisar digitar de novo
            $_SESSION['old_email'] = $email;
            header("Location: ../views/auth/login.php");
            exit();
        }
    }

    public function logout()
    {
        // Destruir sessão
        session_unset();
        session_destroy();

        // Iniciar nova sessão apenas para mensagem de sucesso
        session_start();
        $_SESSION['logout_sucesso'] = "Você saiu do sistema.";

        header("Location: ../views/auth/login.php");
        exit();
    }
}

// Roteamento Simples
// Verifica a "action" na URL (ex: AuthController.php?action=login)
$action = $_GET['action'] ?? null;
$controller = new AuthController();

if ($action == 'login') {
    $controller->login();
} elseif ($action == 'logout') {
    $controller->logout();
} else {
    // Se acessar direto, redireciona para a view de login
    header("Location: ../views/auth/login.php");
    exit();
}
?>