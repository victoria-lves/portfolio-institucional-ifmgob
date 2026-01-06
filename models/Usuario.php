<?php
// models/Usuario.php

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

        // Limpar dados (Sanitização)
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nivel = htmlspecialchars(strip_tags($this->nivel));
        
        // [CORREÇÃO] NÃO fazer hash aqui, pois o Controller já enviou a senha criptografada.
        // Se fizer aqui de novo, a senha no banco ficará inválida.

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha", $this->senha); // Salva o hash recebido do controller
        $stmt->bindParam(":nivel", $this->nivel);

        if ($stmt->execute()) {
            // [MELHORIA] Captura o ID gerado (útil se precisar logar logo após criar)
            $this->id = $this->conn->lastInsertId();
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