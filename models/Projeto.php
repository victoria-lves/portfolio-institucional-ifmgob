<?php
// models/Projeto.php

class Projeto {
    private $conn;
    private $table_name = "projeto";

    // Propriedades (Colunas da tabela 'projeto')
    public $id;
    public $titulo;
    public $autor; // Nome do autor principal (texto)
    public $descricao;
    public $data_inicio;
    public $status;
    public $data_fim;
    public $links;
    public $parceria;
    public $objetivos;
    public $resultados;
    public $area_conhecimento;
    public $alunos_envolvidos;
    public $agencia_financiadora;
    public $financiamento;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================================
    // CRIAR (INSERT)
    // ==========================================================
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . "
                (titulo, autor, descricao, data_inicio, status, data_fim, links, parceria, 
                 objetivos, resultados, area_conhecimento, alunos_envolvidos, agencia_financiadora, financiamento)
                VALUES
                (:titulo, :autor, :descricao, :data_inicio, :status, :data_fim, :links, :parceria, 
                 :objetivos, :resultados, :area_conhecimento, :alunos_envolvidos, :agencia_financiadora, :financiamento)";
        
        $stmt = $this->conn->prepare($query);

        // Tratamento de dados (Sanitização básica e Nulos)
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->autor = htmlspecialchars(strip_tags($this->autor));
        
        // Campos opcionais devem ser NULL se vazios
        $this->data_inicio = !empty($this->data_inicio) ? $this->data_inicio : null;
        $this->data_fim = !empty($this->data_fim) ? $this->data_fim : null;
        $this->financiamento = !empty($this->financiamento) ? $this->financiamento : null;

        // Bind
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":autor", $this->autor);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":data_inicio", $this->data_inicio);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":data_fim", $this->data_fim);
        $stmt->bindParam(":links", $this->links);
        $stmt->bindParam(":parceria", $this->parceria);
        $stmt->bindParam(":objetivos", $this->objetivos);
        $stmt->bindParam(":resultados", $this->resultados);
        $stmt->bindParam(":area_conhecimento", $this->area_conhecimento);
        $stmt->bindParam(":alunos_envolvidos", $this->alunos_envolvidos);
        $stmt->bindParam(":agencia_financiadora", $this->agencia_financiadora);
        $stmt->bindParam(":financiamento", $this->financiamento);

        if ($stmt->execute()) {
            // Captura o ID gerado para uso no Controller
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // ==========================================================
    // ATUALIZAR (UPDATE)
    // ==========================================================
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET titulo = :titulo, 
                    autor = :autor, 
                    descricao = :descricao, 
                    data_inicio = :data_inicio, 
                    status = :status, 
                    data_fim = :data_fim,
                    links = :links, 
                    parceria = :parceria, 
                    objetivos = :objetivos,
                    resultados = :resultados, 
                    area_conhecimento = :area_conhecimento,
                    alunos_envolvidos = :alunos_envolvidos, 
                    agencia_financiadora = :agencia_financiadora,
                    financiamento = :financiamento
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Tratamento de opcionais
        $this->data_inicio = !empty($this->data_inicio) ? $this->data_inicio : null;
        $this->data_fim = !empty($this->data_fim) ? $this->data_fim : null;
        $this->financiamento = !empty($this->financiamento) ? $this->financiamento : null;

        // Bind
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":autor", $this->autor);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":data_inicio", $this->data_inicio);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":data_fim", $this->data_fim);
        $stmt->bindParam(":links", $this->links);
        $stmt->bindParam(":parceria", $this->parceria);
        $stmt->bindParam(":objetivos", $this->objetivos);
        $stmt->bindParam(":resultados", $this->resultados);
        $stmt->bindParam(":area_conhecimento", $this->area_conhecimento);
        $stmt->bindParam(":alunos_envolvidos", $this->alunos_envolvidos);
        $stmt->bindParam(":agencia_financiadora", $this->agencia_financiadora);
        $stmt->bindParam(":financiamento", $this->financiamento);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // ==========================================================
    // EXCLUIR (DELETE)
    // ==========================================================
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // ==========================================================
    // LISTAR TODOS (Admin ou Visão Geral)
    // ==========================================================
    public function listar() {
        // Traz os dados do projeto E os IDs dos professores vinculados (ids_autores)
        $query = "SELECT p.*, GROUP_CONCAT(pp.id_professor) as ids_autores
                  FROM " . $this->table_name . " p
                  LEFT JOIN professor_projeto pp ON p.id = pp.id_projeto
                  GROUP BY p.id
                  ORDER BY p.data_inicio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // ==========================================================
    // LISTAR POR PROFESSOR
    // ==========================================================
    public function listarPorProfessor($id_professor) {
        // Seleciona projetos onde o professor é um dos autores
        // Também traz o GROUP_CONCAT para saber quem são os co-autores
        $query = "SELECT p.*, GROUP_CONCAT(pp_todos.id_professor) as ids_autores
                  FROM " . $this->table_name . " p
                  INNER JOIN professor_projeto pp_filtro ON p.id = pp_filtro.id_projeto
                  LEFT JOIN professor_projeto pp_todos ON p.id = pp_todos.id_projeto
                  WHERE pp_filtro.id_professor = :id
                  GROUP BY p.id
                  ORDER BY p.data_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_professor);
        $stmt->execute();
        return $stmt;
    }

    // ==========================================================
    // BUSCAR POR ID (Detalhes)
    // ==========================================================
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Preenche as propriedades do objeto se encontrou
        if($row) {
            $this->id = $row['id'];
            $this->titulo = $row['titulo'];
            $this->autor = $row['autor'];
            $this->descricao = $row['descricao'];
            $this->data_inicio = $row['data_inicio'];
            $this->status = $row['status'];
            $this->data_fim = $row['data_fim'];
            $this->links = $row['links'];
            $this->parceria = $row['parceria'];
            $this->objetivos = $row['objetivos'];
            $this->resultados = $row['resultados'];
            $this->area_conhecimento = $row['area_conhecimento'];
            $this->alunos_envolvidos = $row['alunos_envolvidos'];
            $this->agencia_financiadora = $row['agencia_financiadora'];
            $this->financiamento = $row['financiamento'];
        }
        
        return $row;
    }
}
?>