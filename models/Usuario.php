<?php
class Usuario {
    private $conn;
    private $table_name = "usuario";

    public $id;
    public $nome;
    public $email;
    public $senha;
    public $nivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo usuário
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nome, email, senha, nivel) 
                  VALUES (:nome, :email, :senha, :nivel)";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nivel = htmlspecialchars(strip_tags($this->nivel));
        
        // Hash da senha (Segurança)
        $senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha", $senha_hash);
        $stmt->bindParam(":nivel", $this->nivel);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Excluir usuário
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se email existe (para evitar duplicidade)
    public function emailExiste() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>