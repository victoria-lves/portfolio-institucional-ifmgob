<?php
// resetar.php
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// --- CONFIGURAÇÃO ---
$email_alvo = 'marcio.assis@ifmg.edu.br'; // Coloque o email do professor aqui
$nova_senha = '123456';
// --------------------

// Gera o hash seguro
$senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

try {
    $sql = "UPDATE usuario SET senha = :senha WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':senha', $senha_hash);
    $stmt->bindParam(':email', $email_alvo);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo "<h1>Sucesso!</h1>";
            echo "A senha do usuário <b>$email_alvo</b> foi alterada para: <b>$nova_senha</b><br>";
            echo "Hash gerado: " . $senha_hash;
        } else {
            echo "<h1>Atenção</h1>";
            echo "Nenhum usuário encontrado com o email: $email_alvo";
        }
    } else {
        echo "Erro ao atualizar.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>