<?php
// models/Producao.php

class Producao {
    private $conn;
    private $table_name = "producao";

    // Propriedades do Objeto
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

    // LISTAR TODAS
    public function listar() {
        $query = "SELECT p.*, prof.nome as nome_professor 
                  FROM " . $this->table_name . " p
                  LEFT JOIN professor prof ON p.id_professor = prof.id
                  ORDER BY p.data_pub DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Listar PROFESSORES
    public function listarPorProfessor($id_professor) {
        $query = "SELECT p.*, prof.nome as nome_professor 
                  FROM " . $this->table_name . " p
                  LEFT JOIN professor prof ON p.id_professor = prof.id
                  WHERE p.id_professor = :id_professor
                  ORDER BY p.data_pub DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_professor', $id_professor);
        $stmt->execute();
        return $stmt;
    }

    // BUSCAR POR ID
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->id = $row['id'];
            $this->titulo = $row['titulo'];
            $this->autor = $row['autor'];
            $this->data_pub = $row['data_pub'];
            $this->tipo = $row['tipo'];
            $this->tipo_outro = $row['tipo_outro'];
            $this->idioma = $row['idioma'];
            $this->idioma_outro = $row['idioma_outro'];
            $this->link = $row['link'];
            $this->id_professor = $row['id_professor'];
        }
        return $row;
    }

    // CRIAR
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . "
                (titulo, autor, data_pub, tipo, tipo_outro, idioma, idioma_outro, link, id_professor)
                VALUES
                (:titulo, :autor, :data_pub, :tipo, :tipo_outro, :idioma, :idioma_outro, :link, :id_professor)";

        $stmt = $this->conn->prepare($query);

        // Tratamento de nulos
        $this->tipo_outro = !empty($this->tipo_outro) ? $this->tipo_outro : null;
        $this->idioma_outro = !empty($this->idioma_outro) ? $this->idioma_outro : null;
        $this->link = !empty($this->link) ? $this->link : null;
        $this->data_pub = !empty($this->data_pub) ? $this->data_pub : null;

        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":autor", $this->autor);
        $stmt->bindParam(":data_pub", $this->data_pub);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":tipo_outro", $this->tipo_outro);
        $stmt->bindParam(":idioma", $this->idioma);
        $stmt->bindParam(":idioma_outro", $this->idioma_outro);
        $stmt->bindParam(":link", $this->link);
        $stmt->bindParam(":id_professor", $this->id_professor);

        if ($stmt->execute()) {
            // [MELHORIA] Define o ID do objeto com o ID gerado no banco
            $this->id = $this->conn->lastInsertId(); 
            return true;
        }
        return false;
    }

    // ATUALIZAR
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET titulo = :titulo, autor = :autor, data_pub = :data_pub,
                    tipo = :tipo, tipo_outro = :tipo_outro, 
                    idioma = :idioma, idioma_outro = :idioma_outro,
                    link = :link, id_professor = :id_professor
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Tratamento de nulos
        $this->tipo_outro = !empty($this->tipo_outro) ? $this->tipo_outro : null;
        $this->idioma_outro = !empty($this->idioma_outro) ? $this->idioma_outro : null;
        $this->link = !empty($this->link) ? $this->link : null;
        $this->data_pub = !empty($this->data_pub) ? $this->data_pub : null;

        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":autor", $this->autor);
        $stmt->bindParam(":data_pub", $this->data_pub);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":tipo_outro", $this->tipo_outro);
        $stmt->bindParam(":idioma", $this->idioma);
        $stmt->bindParam(":idioma_outro", $this->idioma_outro);
        $stmt->bindParam(":link", $this->link);
        $stmt->bindParam(":id_professor", $this->id_professor);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // EXCLUIR
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>