<?php
// 1. Incluir a configuração do banco (caminho relativo à raiz)
require_once '../../config/database.php';

// 2. Instanciar a conexão PDO
$database = new Database();
$conn = $database->getConnection();

// 3. Executar a consulta (Modo PDO)
$sql = "SELECT * FROM curso ORDER BY nome ASC";
try {
    $stmt = $conn->query($sql);
} catch (PDOException $e) {
    echo "Erro na consulta: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nossos Cursos | IFMG</title>

    <link rel="stylesheet" href="../../assets/css/style-menu-cursos.css" />

    <link
        href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="menu-principal.php" id="logo-link" aria-label="Voltar para a página inicial">
                <img src="../../assets/img/logo-branco.png" alt="Logo do IFMG" id="logo" />
            </a>

            <button class="menu-toggle" aria-label="Abrir menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <nav class="nav-items">
                <a href="menu-cursos.php" class="active">Cursos</a>
                <a href="menu-laboratorios.html">Laboratórios</a>
                <a href="menu-professores.php">Docentes</a>
                <a href="menu-projetos.php">Projetos</a>
                <a href="../auth/login.php" class="btn-login">Acesso Restrito</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="theme">
            <div class="theme-overlay"></div>
            <div class="theme-content">
                <h1 class="theme-title">Nossos Cursos</h1>
                <p class="theme-desc">Descubra oportunidades de formação técnica e superior com excelência e inovação.
                </p>
            </div>
        </section>

        <section class="cursos-section">
            <div class="container">
                <div class="galeria-grid">
                    <?php
                    // 4. Verificar se há resultados (Modo PDO)
                    if ($stmt && $stmt->rowCount() > 0) {
                        // Loop usando fetch PDO
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // Define imagem padrão se não houver
                            // 1. Pega o nome do arquivo do banco
                            $nome_arquivo = $row['imagem'];

                            // 2. Lógica de tratamento da Imagem
                            if (empty($nome_arquivo)) {
                                // CASO 1: Campo vazio -> Usa imagem padrão
                                // Certifique-se de ter essa imagem na pasta ou ajuste o nome aqui
                                $img = '../../assets/img/default-img-curso.webp';
                            } elseif (strpos($nome_arquivo, 'http') === 0) {
                                // CASO 2: É um link externo (começa com http/https)
                                $img = htmlspecialchars($nome_arquivo);
                            } else {
                                // CASO 3: É um arquivo local -> Adiciona a pasta 'img/cursos/'
                                $img = 'img/cursos/' . htmlspecialchars($nome_arquivo);
                            }
                            $nome = htmlspecialchars($row['nome']);
                            $id = $row['id'];
                            // Pega o nível do curso (ex: Técnico, Bacharelado)
                            $nivel = isset($row['nivel']) ? htmlspecialchars($row['nivel']) : 'Curso';

                            echo "
              <article class='curso-card'>
                <div class='card-image'>
                  <a href='pagina-curso.php?id=$id' aria-label='Ver detalhes do curso de $nome'>
                    <img src='$img' alt='Foto ilustrativa do curso de $nome'>
                    <span class='card-badge'>$nivel</span>
                  </a>
                </div>
                <div class='card-content'>
                  <h3 class='card-title'>
                    <a href='pagina-curso.php?id=$id'>$nome</a>
                  </h3>
                  <a href='pagina-curso.php?id=$id' class='btn-detalhes'>
                    Saiba Mais <i class='fa-solid fa-arrow-right'></i>
                  </a>
                </div>
              </article>
              ";
                        }
                    } else {
                        echo "
            <div class='empty-state'>
              <i class='fa-solid fa-graduation-cap'></i>
              <p>Nenhum curso cadastrado no momento.</p>
            </div>
            ";
                    }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Endereço</h4>
                    <p><i class="fa-solid fa-location-dot"></i> Rua Afonso Sardinha, 90<br />Ouro Branco, MG - 36420-000
                    </p>
                </div>
                <div class="footer-section">
                    <h4>Funcionamento</h4>
                    <p><i class="fa-regular fa-clock"></i> Seg a Sex: 08h - 22h</p>
                    <p>Sábado: 12h - 20h</p>
                </div>
                <div class="footer-section">
                    <h4>Contato</h4>
                    <p><i class="fa-regular fa-envelope"></i> secretaria.ourobranco@ifmg.edu.br</p>
                    <p><i class="fa-solid fa-phone"></i> (31) 2137-5700</p>
                </div>
                <div class="footer-section">
                    <h4>Redes Sociais</h4>
                    <div class="social-icons">
                        <a href="#" aria-label="Youtube"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Instituto Federal de Minas Gerais - Campus Ouro Branco</p>
            </div>
        </div>
    </footer>

    <script>
        document.querySelector('.menu-toggle').addEventListener('click', function () {
            document.querySelector('.nav-items').classList.toggle('active');
        });
    </script>
</body>

</html>