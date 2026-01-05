<?php
class Producao {
    private $conn;
    private $table = "producao";

    public $id;
    public $titulo;
    public $autor;
    public $data_pub;
    public $tipo;
    public $tipo_outro;
    public $idioma;
    public $idioma_outro;
    public $link;
    public $id_professor;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY data_pub DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function listarPorProfessor($id_professor) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_professor = :id_professor ORDER BY data_pub DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_professor", $id_professor);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Adicione dentro da classe Producao
    public function delete($id) {
        $query = "DELETE FROM producao WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>