<?php

// 1. Verificação de Sessão (Evita avisos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Caminhos Absolutos
require_once __DIR__ . '/../config/database.php';

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

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $senha = $_POST['senha'] ?? '';

            if (empty($email) || empty($senha)) {
                $_SESSION['erro'] = "Preencha todos os campos!";
                header("Location: ../views/auth/login.php");
                exit();
            }

            try {
                // 1. Buscar usuário
                $query = "SELECT id, nome, email, senha, nivel FROM usuario WHERE email = :email LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    // 2. Verificar Senha
                    if (password_verify($senha, $usuario['senha'])) {
                        
                        // Segurança: Regenerar ID da sessão
                        session_regenerate_id(true);

                        // Definir Sessão
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nome'] = $usuario['nome'];
                        $_SESSION['usuario_nivel'] = $usuario['nivel'];
                        $_SESSION['login_time'] = time();

                        // 3. Se for professor, buscar o ID do Perfil Docente
                        if ($usuario['nivel'] == 'professor') {
                            // [CORREÇÃO CRÍTICA] O campo no banco é 'id_usuario', não 'usuario_id'
                            $stmtProf = $this->db->prepare("SELECT id FROM professor WHERE id_usuario = :uid LIMIT 1");
                            $stmtProf->execute([':uid' => $usuario['id']]);
                            
                            if ($prof = $stmtProf->fetch(PDO::FETCH_ASSOC)) {
                                $_SESSION['professor_id'] = $prof['id'];
                            } else {
                                // Usuário é professor mas não tem perfil criado ainda
                            }
                        }

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

            // Falha
            $_SESSION['old_email'] = $email;
            header("Location: ../views/auth/login.php");
            exit();
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();

        // Inicia nova sessão para exibir mensagem
        session_start();
        $_SESSION['logout_sucesso'] = "Você saiu do sistema."; // Mensagem ajustada para o login.php capturar

        header("Location: ../views/auth/login.php");
        exit();
    }
}

// Roteamento
if (isset($_GET['action'])) {
    $controller = new AuthController();
    
    switch ($_GET['action']) {
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        default:
            header("Location: ../views/auth/login.php");
            exit();
    }
} else {
    // Acesso direto sem action redireciona
    header("Location: ../views/auth/login.php");
    exit();
}
?>
