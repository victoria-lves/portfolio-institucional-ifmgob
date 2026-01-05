<?php
// 1. Configuração do Banco de Dados
// Importa o arquivo de configuração do banco apenas uma vez para evitar conflitos
require_once 'config/database.php';
// Instancia a classe Database criada anteriormente
$database = new Database();
// Obtém a conexão ativa com o banco de dados
$conn = $database->getConnection();

// 2. Verifica ID na URL
// Verifica se o parâmetro 'id' existe na URL (ex: pagina.php?id=5)
// Se existir, converte para inteiro (int) por segurança; se não, define como null
$id_projeto = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Se o ID não for válido (for zero ou nulo), interrompe a execução
if (!$id_projeto) {
    // Encerra o script exibindo uma mensagem de erro e um link para voltar
    die("<div style='text-align:center; padding:50px;'>Projeto não selecionado. <a href='menu-projetos.php'>Voltar</a></div>");
}

// 3. Busca Dados do Projeto
// Define a consulta SQL para buscar um projeto específico pelo ID
$query = "SELECT * FROM projeto WHERE id = :id LIMIT 1";
// Prepara a query para execução (proteção contra SQL Injection)
$stmt = $conn->prepare($query);
// Vincula o parâmetro :id ao valor da variável $id_projeto
$stmt->bindParam(':id', $id_projeto);
// Executa a consulta no banco de dados
$stmt->execute();
// Recupera o resultado como um array associativo (chave => valor)
$proj = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o projeto realmente existe no banco
if (!$proj) {
    // Se não encontrar nada, encerra o script com mensagem de erro
    die("<div style='text-align:center; padding:50px;'>Projeto não encontrado. <a href='menu-projetos.php'>Voltar</a></div>");
}

// 4. Busca Imagens da Galeria (Relação 1 para N)
// Define a consulta para buscar todas as imagens atreladas a este ID de projeto
$query_img = "SELECT * FROM imagens WHERE id_projeto = :id ORDER BY id ASC";
$stmt_img = $conn->prepare($query_img);
$stmt_img->bindParam(':id', $id_projeto);
$stmt_img->execute();
// Recupera TODAS as linhas encontradas (fetchAll)
$imagens = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

// 5. Formatação de Dados
// Formata a data de início do padrão SQL (AAAA-MM-DD) para o padrão brasileiro (DD/MM/AAAA)
$data_inicio = date('d/m/Y', strtotime($proj['data_inicio']));
// Verifica se existe data fim; se sim, formata, senão define como "Em andamento"
$data_fim = $proj['data_fim'] ? date('d/m/Y', strtotime($proj['data_fim'])) : 'Em andamento';

// Classes de Status (Lógica Visual)
// Define uma classe padrão
$status_class = 'pendente';
// Normaliza o texto do status para minúsculas para facilitar a comparação
$status_formatado = strtolower($proj['status']);

// Verifica palavras-chave no status para definir a cor correta (via classe CSS)
if (strpos($status_formatado, 'andamento') !== false) {
    $status_class = 'ativo'; // Define cor verde/azul
} elseif (strpos($status_formatado, 'conclu') !== false) {
    $status_class = 'concluido'; // Define cor verde
} elseif (strpos($status_formatado, 'pausa') !== false) {
    $status_class = 'pausado'; // Define cor amarela/laranja
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $projeto['titulo']; ?> | IFMG Ouro Branco</title>
    <meta name="description" content="<?php echo substr(strip_tags($projeto['descricao']), 0, 160); ?>...">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="article">
    <meta property="og:url" content="https://seusite.com/pagina-projeto.php?id=<?php echo $id; ?>">
    <meta property="og:title" content="<?php echo $projeto['titulo']; ?>">
    <meta property="og:description" content="<?php echo substr(strip_tags($projeto['descricao']), 0, 160); ?>...">
    <meta property="og:image" content="https://seusite.com/img/projetos/<?php echo $projeto['imagem']; ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $projeto['titulo']; ?>">
    <meta name="twitter:image" content="https://seusite.com/img/projetos/<?php echo $projeto['imagem']; ?>">


    <link rel="stylesheet" href="css/style-pagina-projeto.css">
    <link
        href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="menu-principal.php" id="logo-link">
                <img src="img/logo-branco.png" alt="IFMG Campus Ouro Branco" id="logo" />
            </a>

            <button class="menu-toggle" onclick="document.querySelector('.nav-items').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="nav-items">
                <a href="menu-cursos.php">Cursos</a>
                <a href="menu-laboratorios.html">Laboratórios</a>
                <a href="menu-docentes.php">Docentes</a>
                <a href="menu-projetos.php" class="active">Projetos</a>
                <a href="views/auth/login.php" class="btn-login">Acesso Restrito</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="projeto-header">
            <div class="container">
                <div class="breadcrumb">
                    <a href="menu-projetos.php"><i class="fas fa-arrow-left"></i> Voltar para Projetos</a>
                </div>
                <div class="header-content">
                    <h1 class="projeto-titulo"><?php echo htmlspecialchars($proj['titulo']); ?></h1>

                    <div class="projeto-meta">
                        <?php if ($proj['data_inicio']): ?>
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Início: <strong><?php echo $data_inicio; ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($proj['data_fim']): ?>
                            <div class="meta-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>Término: <strong><?php echo $data_fim; ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <div class="meta-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Status: <span
                                    class="status <?php echo $status_class; ?>"><?php echo htmlspecialchars($proj['status']); ?></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="projeto-conteudo">
            <div class="container">
                <div class="conteudo-grid">

                    <div class="coluna-principal">
                        <div class="card">
                            <h2><i class="fas fa-align-left"></i> Descrição do Projeto</h2>
                            <div class="card-content">
                                <p><?php echo nl2br(htmlspecialchars($proj['descricao'])); ?></p>

                                <?php if (!empty($proj['objetivos'])): ?>
                                    <h3 class="mt-4">Objetivos</h3>
                                    <p><?php echo nl2br(htmlspecialchars($proj['objetivos'])); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($proj['resultados'])): ?>
                                    <h3 class="mt-4">Resultados Esperados/Alcançados</h3>
                                    <p><?php echo nl2br(htmlspecialchars($proj['resultados'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <h2><i class="fas fa-images"></i> Galeria do Projeto</h2>
                            <div class="card-content">
                                <?php if (count($imagens) > 0): ?>
                                    <div class="galeria-grid">
                                        <?php foreach ($imagens as $img): ?>
                                            <div class="galeria-item">
                                                <img src="img/projetos/<?php echo htmlspecialchars($img['caminho']); ?>"
                                                    alt="<?php echo htmlspecialchars($img['legenda'] ?? 'Imagem do projeto'); ?>"
                                                    onclick="window.open(this.src, '_blank')">

                                                <?php if (!empty($img['legenda'])): ?>
                                                    <p class="legenda-img"><?php echo htmlspecialchars($img['legenda']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color: #666; font-style: italic;">Nenhuma imagem adicional cadastrada para
                                        este projeto.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="coluna-lateral">
                        <div class="card sticky-card">
                            <h2><i class="fas fa-info-circle"></i> Informações</h2>
                            <div class="card-content">

                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-user"></i> Orientador(es)</div>
                                    <div class="info-value">
                                        <strong><?php echo htmlspecialchars($proj['autor']); ?></strong>
                                    </div>
                                </div>

                                <?php if (!empty($proj['alunos_envolvidos'])): ?>
                                    <div class="info-item">
                                        <div class="info-label"><i class="fas fa-graduation-cap"></i> Alunos Envolvidos
                                        </div>
                                        <div class="info-value text-small">
                                            <?php echo nl2br(htmlspecialchars($proj['alunos_envolvidos'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($proj['agencia_financiadora'])): ?>
                                    <div class="info-item">
                                        <div class="info-label"><i class="fas fa-university"></i> Agência Fomento</div>
                                        <div class="info-value">
                                            <?php echo htmlspecialchars($proj['agencia_financiadora']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($proj['financiamento']) && $proj['financiamento'] > 0): ?>
                                    <div class="info-item">
                                        <div class="info-label"><i class="fas fa-money-bill-wave"></i> Valor</div>
                                        <div class="info-value destaque-valor">
                                            R$ <?php echo number_format($proj['financiamento'], 2, ',', '.'); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($proj['links'])): ?>
                                    <div class="info-item border-0">
                                        <div class="info-label"><i class="fas fa-link"></i> Links Relacionados</div>
                                        <div class="links-list mt-2">
                                            <?php
                                            // Expressão Regular (Regex) divide a string por quebra de linha ou vírgula
                                            $links = preg_split('/[\n,]+/', $proj['links']);
                                            foreach ($links as $link):
                                                $link = trim($link); // Remove espaços em branco extras
                                                if (!empty($link)):
                                                    ?>
                                                    <a href="<?php echo $link; ?>" target="_blank" class="btn-link-externo">
                                                        <i class="fas fa-external-link-alt"></i> Acessar Recurso
                                                    </a>
                                                <?php endif; endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                        <div class="card mt-4">
                            <h2><i class="fas fa-envelope"></i> Contato</h2>
                            <div class="card-content">
                                <p class="text-small">Dúvidas sobre este projeto? Entre em contato com a coordenação de
                                    pesquisa.</p>
                                <a href="mailto:secretaria.ourobranco@ifmg.edu.br" class="btn-contato-full">
                                    Fale Conosco
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Endereço</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Rua Afonso Sardinha, 90<br>Ouro Branco, MG - 36420-000</p>
                </div>
                <div class="footer-section">
                    <h4>Contato</h4>
                    <p><i class="fas fa-envelope"></i> secretaria.ourobranco@ifmg.edu.br</p>
                    <p><i class="fas fa-phone"></i> (31) 2137-5700</p>
                </div>
                <div class="footer-section">
                    <h4>Redes Sociais</h4>
                    <div class="social-icons">
                        <a href="#"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Instituto Federal de Minas Gerais - Campus Ouro Branco</p>
            </div>
        </div>
    </footer>
</body>

</html>