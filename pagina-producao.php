<?php
// Carrega as configurações de conexão com o banco de dados
require_once 'config/database.php';

// 1. Verificar ID
// Verifica se o ID foi passado na URL. Se sim, converte para inteiro (segurança), senão define como null
$id_producao = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Se o ID não for válido (nulo ou zero), o script redireciona o usuário
if (!$id_producao) {
    // Envia um cabeçalho HTTP instruindo o navegador a ir para a lista de docentes
    header("Location: menu-docentes.php");
    // Interrompe imediatamente a execução do script para garantir o redirecionamento
    exit;
}

// 2. Buscar Dados
// Instancia a classe de conexão e obtém o objeto PDO
$database = new Database();
$conn = $database->getConnection();

// Define a consulta SQL com uma junção (JOIN) de tabelas
// Busca todos os dados da produção (p.*) e o nome do professor vinculado (prof.nome)
// A cláusula LEFT JOIN associa a tabela 'producao' com a tabela 'professor'
$query = "SELECT p.*, prof.nome as nome_autor_sistema, prof.id as id_autor_sistema 
          FROM producao p 
          LEFT JOIN professor prof ON p.id_professor = prof.id 
          WHERE p.id = :id LIMIT 1";

// Prepara a query para execução segura
$stmt = $conn->prepare($query);
// Vincula o valor do ID ao parâmetro :id da query
$stmt->bindParam(':id', $id_producao);
// Executa a consulta no banco de dados
$stmt->execute();
// Recupera o resultado como um array associativo
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se a produção existe no banco
if (!$prod) {
    // Se não encontrar, exibe uma mensagem simples e encerra o script
    echo "Produção não encontrada.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($prod['titulo']); ?> | IFMG</title>

    <link
        href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />

    <link rel="stylesheet" href="css/style-pagina-producao.css">
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="menu-principal.php" id="logo-link" aria-label="Voltar para a página inicial">
                <img src="img/logo-branco.png" alt="Logo do IFMG Campus Ouro Branco" id="logo" />
            </a>

            <button class="menu-toggle" aria-label="Abrir menu de navegação">
                <i class="fa-solid fa-bars"></i>
            </button>

            <nav class="nav-items">
                <a href="menu-cursos.php">Cursos</a>
                <a href="menu-laboratorios.html" class="active">Laboratórios</a>
                <a href="menu-docentes.php">Docentes</a>
                <a href="menu-projetos.php">Projetos</a>
                <a href="views/auth/login.php" class="btn-login">Acesso Restrito</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="prod-hero">
            <div class="container">
                <span class="prod-type-badge"><?php echo htmlspecialchars($prod['tipo']); ?></span>
                <h1 class="prod-title"><?php echo htmlspecialchars($prod['titulo']); ?></h1>
            </div>
        </section>

        <section class="conteudo-section">
            <div class="container">
                <a href="menu-docentes.php?id=<?php echo $prod['id_autor_sistema']; ?>" class="btn-voltar">
                    <i class="fa-solid fa-arrow-left"></i> Voltar ao Perfil
                </a>

                <div class="conteudo-grid">

                    <div class="coluna-principal">
                        <article class="content-card">
                            <div class="card-header">
                                <h2><i class="fa-solid fa-file-lines"></i> Ficha Técnica</h2>
                            </div>
                            <div class="card-body">
                                <div class="info-block-main" style="margin-bottom: 25px;">
                                    <strong style="display:block; color:#333; margin-bottom:5px; font-size:1.1rem;">
                                        <i class="fa-solid fa-users"
                                            style="color:var(--ifmg-green); margin-right:8px;"></i>
                                        Autor(es):
                                    </strong>
                                    <p
                                        style="font-size: 1.1rem; color: #555; background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid var(--ifmg-green);">
                                        <?php echo htmlspecialchars($prod['autor']); ?>
                                    </p>
                                </div>

                                <?php if (!empty($prod['idioma'])): ?>
                                    <div class="info-block-main" style="margin-bottom: 25px;">
                                        <strong style="display:block; color:#333; margin-bottom:5px;">
                                            <i class="fa-solid fa-language"
                                                style="color:var(--ifmg-green); margin-right:8px;"></i>
                                            Idioma:
                                        </strong>
                                        <p style="color: #666; margin: 0; padding-left: 28px;">
                                            <?php echo htmlspecialchars($prod['idioma']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>

                        <?php if (!empty($prod['link'])): ?>
                            <div class="btn-container">
                                <a href="<?php echo htmlspecialchars($prod['link']); ?>" target="_blank"
                                    class="btn-externo">
                                    Acessar Conteúdo Original <i class="fa-solid fa-external-link-alt"></i>
                                </a>
                                <p class="redirect-notice">
                                    Você será redirecionado para a fonte original da publicação.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert-box"
                                style="text-align:center; padding: 30px; background: #fff; border-radius: 8px; border: 1px dashed #ccc; color: #777;">
                                <i class="fa-solid fa-link-slash"
                                    style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                                Link externo não disponível para esta produção.
                            </div>
                        <?php endif; ?>
                    </div>

                    <aside class="coluna-lateral">
                        <div class="sidebar-card">
                            <h3><i class="fa-solid fa-circle-info"></i> Dados do Sistema</h3>
                            <div class="card-body">
                                <div class="info-block">
                                    <strong><i class="fa-solid fa-calendar"></i> Data de Publicação:</strong>
                                    <p><?php echo date('d/m/Y', strtotime($prod['data_pub'])); ?></p>
                                </div>

                                <div class="info-block">
                                    <strong><i class="fa-solid fa-tag"></i> Tipo de Obra:</strong>
                                    <p><?php echo htmlspecialchars($prod['tipo']); ?></p>
                                </div>

                                <div class="info-block">
                                    <strong><i class="fa-solid fa-chalkboard-user"></i> Docente Vinculado:</strong>
                                    <p>Prof. <?php echo htmlspecialchars($prod['nome_autor_sistema']); ?></p>
                                </div>
                            </div>
                        </div>
                    </aside>

                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Endereço</h4>
                    <p>
                        <i class="fa-solid fa-location-dot"></i> Rua Afonso Sardinha,
                        90<br />Ouro Branco, MG - 36420-000
                    </p>
                </div>
                <div class="footer-section">
                    <h4>Funcionamento</h4>
                    <p><i class="fa-regular fa-clock"></i> Seg a Sex: 08h - 22h</p>
                    <p>Sábado: 12h - 20h</p>
                </div>
                <div class="footer-section">
                    <h4>Contato</h4>
                    <p>
                        <i class="fa-regular fa-envelope"></i>
                        secretaria.ourobranco@ifmg.edu.br
                    </p>
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
                <p>
                    &copy; 2025 Instituto Federal de Minas Gerais - Campus Ouro Branco
                </p>
            </div>
        </div>
    </footer>
</body>

</html>