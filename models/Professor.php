<?php

class Professor {
    private $conn;
    private $table = "professor";

    // Propriedades do Banco de Dados (Nomes Reais das Colunas)
    public $id;
    public $id_usuario;
    public $nome;
    public $email;
    public $pfp;        
    public $bio;        
    public $disciplina; 
    public $formacao;   
    public $atendimento;
    public $lattes;     
    public $linkedin;
    public $gabinete;

    public $foto_perfil;
    public $biografia;
    public $titulacao;
    public $area_atuacao;
    public $link_lattes;
    public $usuario_id;

    public function __construct($db) {
        $this->conn = $db;
    }


    public function criar() {
        // Mapeamento de Compatibilidade:
        // Se o controller definiu 'foto_perfil', movemos para 'pfp', e assim por diante.
        $this->pfp = $this->foto_perfil ?? $this->pfp;
        $this->bio = $this->biografia ?? $this->bio;
        $this->formacao = $this->titulacao ?? $this->formacao;
        $this->disciplina = $this->area_atuacao ?? $this->disciplina;
        $this->lattes = $this->link_lattes ?? $this->lattes;
        $this->id_usuario = $this->usuario_id ?? $this->id_usuario;

        $query = "INSERT INTO " . $this->table . " 
                  (id_usuario, nome, email, pfp, bio, disciplina, formacao, lattes, atendimento, linkedin, gabinete) 
                  VALUES 
                  (:id_usuario, :nome, :email, :pfp, :bio, :disciplina, :formacao, :lattes, :atendimento, :linkedin, :gabinete)";

        $stmt = $this->conn->prepare($query);

        // Sanitização básica
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Binding dos parâmetros
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":pfp", $this->pfp);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":disciplina", $this->disciplina);
        $stmt->bindParam(":formacao", $this->formacao);
        $stmt->bindParam(":lattes", $this->lattes);
        
        // Tratamento de opcionais (evita erro se for null)
        $atend = $this->atendimento ?? null;
        $linkd = $this->linkedin ?? null;
        $gab = $this->gabinete ?? null;
        
        $stmt->bindParam(":atendimento", $atend);
        $stmt->bindParam(":linkedin", $linkd);
        $stmt->bindParam(":gabinete", $gab);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function atualizar() {
        // Mapeamento de Compatibilidade
        $this->pfp = $this->foto_perfil ?? $this->pfp;
        $this->bio = $this->biografia ?? $this->bio;
        $this->formacao = $this->titulacao ?? $this->formacao;
        $this->disciplina = $this->area_atuacao ?? $this->disciplina;
        $this->lattes = $this->link_lattes ?? $this->lattes;

        $query = "UPDATE " . $this->table . " SET
                    nome = :nome,
                    email = :email,
                    pfp = :pfp,
                    bio = :bio,
                    disciplina = :disciplina,
                    formacao = :formacao,
                    atendimento = :atendimento,
                    lattes = :lattes,
                    linkedin = :linkedin,
                    gabinete = :gabinete
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        // Sanitização
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Binding
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":pfp", $this->pfp);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":disciplina", $this->disciplina);
        $stmt->bindParam(":formacao", $this->formacao);
        $stmt->bindParam(":atendimento", $this->atendimento);
        $stmt->bindParam(":lattes", $this->lattes);
        $stmt->bindParam(":linkedin", $this->linkedin);
        $stmt->bindParam(":gabinete", $this->gabinete);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }


    public function listar() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nome ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function listarPorUsuario($id_usuario) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Preenche propriedades principais (banco)
            $this->id = $row['id'];
            $this->id_usuario = $row['id_usuario'];
            $this->nome = $row['nome'];
            $this->pfp = $row['pfp'];
            $this->bio = $row['bio'];
            $this->disciplina = $row['disciplina'];
            $this->formacao = $row['formacao'];
            $this->atendimento = $row['atendimento'];
            $this->email = $row['email'];
            $this->lattes = $row['lattes'];
            $this->linkedin = $row['linkedin'];
            $this->gabinete = $row['gabinete'];
            
            // Preenche propriedades auxiliares (para leitura legada)
            $this->foto_perfil = $row['pfp'];
            $this->biografia = $row['bio'];
            $this->titulacao = $row['formacao'];
            $this->area_atuacao = $row['disciplina'];
            $this->link_lattes = $row['lattes'];
            $this->usuario_id = $row['id_usuario'];
        }
        return $row;
    }
}
?>
