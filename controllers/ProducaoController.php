<?php
// controllers/ProducaoController.php

class ProducaoController {
    private $conn;
    
    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
        } else {
            require_once '../config/database.php';
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }
    
    public function create($data) {
        try {
            // Validar dados
            $titulo = trim($data['titulo'] ?? '');
            $autor = trim($data['autor'] ?? '');
            $tipo = $data['tipo'] ?? '';
            $idioma = $data['idioma'] ?? '';
            
            if (empty($titulo) || empty($autor) || empty($tipo) || empty($idioma)) {
                return ['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos.'];
            }
            
            // Preparar dados
            $data_pub = !empty($data['data_pub']) ? $data['data_pub'] : null;
            $tipo_outro = ($tipo === 'Outro' && !empty($data['tipo_outro'])) ? trim($data['tipo_outro']) : null;
            $idioma_outro = ($idioma === 'Outro' && !empty($data['idioma_outro'])) ? trim($data['idioma_outro']) : null;
            $link = !empty($data['link']) ? trim($data['link']) : null;
            $id_professor = !empty($data['id_professor']) ? (int)$data['id_professor'] : null;
            
            // Validar data
            if ($data_pub && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_pub)) {
                return ['success' => false, 'message' => 'Formato de data inválido. Use AAAA-MM-DD.'];
            }
            
            // Inserir no banco
            $sql = "INSERT INTO producao (titulo, autor, data_pub, tipo, tipo_outro, idioma, idioma_outro, link, id_professor) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $titulo, 
                $autor, 
                $data_pub, 
                $tipo, 
                $tipo_outro, 
                $idioma, 
                $idioma_outro, 
                $link, 
                $id_professor
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Produção acadêmica cadastrada com sucesso!', 'id' => $this->conn->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Erro ao cadastrar produção.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    public function getProfessores() {
        try {
            $sql = "SELECT p.id, p.nome 
                    FROM professor p
                    INNER JOIN usuario u ON p.id_usuario = u.id
                    WHERE u.nivel = 'professor'
                    ORDER BY p.nome";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getTipos() {
        return ['Livro', 'Artigo', 'Tese', 'Outro'];
    }
    
    public function getIdiomas() {
        return ['Português (pt-br)', 'Inglês', 'Espanhol', 'Outro'];
    }
}
?>