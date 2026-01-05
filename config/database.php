<?php
// Define a classe 'Database' que gerencia a conexão com o banco de dados
class Database {
    // Define propriedades privadas para as credenciais (encapsulamento)
    private $host = "localhost";      // O endereço do servidor de banco de dados
    private $db_name = "sistema_ifmg"; // O nome do banco de dados específico
    private $username = "root";       // O usuário do banco (padrão em servidores locais)
    private $password = "root";       // A senha do banco
    public $conn;                     // A propriedade pública que armazena a conexão ativa

    // Define o método público responsável por estabelecer a conexão
    public function getConnection() {
        // Inicializa a variável de conexão como nula para garantir um estado limpo
        $this->conn = null;

        // Inicia um bloco 'try' para tentar executar a conexão segura
        try {
            // Cria uma nova instância da classe PDO (PHP Data Objects)
            // Passa a string de conexão (DSN), usuário e senha
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );

            // Configura o modo de erro do PDO para lançar exceções (Exceptions)
            // Permite que erros de SQL parem o script e sejam capturados
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Executa um comando SQL para definir a codificação de caracteres como UTF-8
            // Garante que acentos e caracteres especiais apareçam corretamente
            $this->conn->exec("set names utf8");

        } catch(PDOException $exception) {
            // Bloco 'catch': Captura erros caso a conexão falhe
            // Exibe uma mensagem de erro concatenada com a mensagem técnica do sistema
            echo "Erro de conexão: " . $exception->getMessage();
        }

        // Retorna o objeto de conexão (ou null se falhar) para quem chamou o método
        return $this->conn;
    }
}
?>