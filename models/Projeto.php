<?php
class Projeto {
    private $conn;
    private $table_name = "projeto";

    // Propriedades do objeto
    public $id;
    public $titulo;
    public $autor;
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

    // =================================================================
    // 1. LISTAR TODOS (Para Admin ou visão geral)
    // =================================================================
    public function listar() {
    // Busca os dados do projeto E uma lista separada por vírgulas dos IDs dos professores (ids_autores)
    $query = "SELECT p.*, GROUP_CONCAT(pp.id_professor) as ids_autores
              FROM " . $this->table_name . " p
              LEFT JOIN professor_projeto pp ON p.id = pp.id_projeto
              GROUP BY p.id
              ORDER BY p.data_inicio DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}

    // =================================================================
    // 2. LISTAR POR PROFESSOR (CORRIGIDO COM JOIN)
    // =================================================================
    public function listarPorProfessor($id_professor) {
        // A QUERY FOI ALTERADA AQUI:
        // Usamos INNER JOIN para ligar 'projeto' com 'professor_projeto'
        $query = "SELECT p.* FROM " . $this->table_name . " p
                  INNER JOIN professor_projeto pp ON p.id = pp.id_projeto
                  WHERE pp.id_professor = :id
                  ORDER BY p.data_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_professor);
        $stmt->execute();
        return $stmt;
    }

    // =================================================================
    // 3. BUSCAR POR ID
    // =================================================================
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

    // =================================================================
    // 4. MÉTODOS DE MANIPULAÇÃO (INSERT/UPDATE/DELETE)
    // Geralmente são feitos direto no Controller (create.php), 
    // mas se tiver lógica aqui, mantenha-a.
    // =================================================================
    
    // Adicione isto no models/Projeto.php

    public $id_professor; // Necessário definir esta propriedade

    public function criar() {
        $query = "INSERT INTO " . $this->table_name . "
                (titulo, autor, descricao, data_inicio, status, data_fim, links, parceria, objetivos, resultados, area_conhecimento, alunos_envolvidos, agencia_financiadora, financiamento)
                VALUES
                (:titulo, :autor, :descricao, :data_inicio, :status, :data_fim, :links, :parceria, :objetivos, :resultados, :area_conhecimento, :alunos_envolvidos, :agencia_financiadora, :financiamento)";

        // Nota: O insert na tabela de ligação (professor_projeto) deve ser feito logo após,
        // ou precisa ajustar a query para salvar o vínculo.
        // Vou assumir que você salvará o vínculo separadamente ou precisa de uma trigger.
        // MAS, o mais comum no seu sistema parece ser salvar na tabela de ligação manual.
        
        $stmt = $this->conn->prepare($query);

        // Bind dos valores
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
            $this->id = $this->conn->lastInsertId();

            // === VINCULAR PROFESSOR (IMPORTANTE) ===
            // Como seu select usa JOIN com professor_projeto, se não salvar aqui, o projeto não aparece na lista!
            if(!empty($this->id_professor)) {
                $queryRel = "INSERT INTO professor_projeto (id_professor, id_projeto) VALUES (:id_prof, :id_proj)";
                $stmtRel = $this->conn->prepare($queryRel);
                $stmtRel->bindParam(":id_prof", $this->id_professor);
                $stmtRel->bindParam(":id_proj", $this->id);
                $stmtRel->execute();
            }
            return true;
        }
        return false;
    }

    public function atualizar() {
        $query = "UPDATE " . $this->table_name . "
                SET titulo = :titulo, autor = :autor, descricao = :descricao, 
                    data_inicio = :data_inicio, status = :status, data_fim = :data_fim,
                    links = :links, parceria = :parceria, objetivos = :objetivos,
                    resultados = :resultados, area_conhecimento = :area_conhecimento,
                    alunos_envolvidos = :alunos_envolvidos, agencia_financiadora = :agencia_financiadora,
                    financiamento = :financiamento
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Bind (igual ao criar, mais o ID)
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

        if($stmt->execute()) {
            // Atualizar vínculo se necessário (opcional para update, mas bom ter)
            return true;
        }
        return false;
    }

    // Método DELETE genérico se precisar
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>