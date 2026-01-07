<?php
// 1. Configuração do Banco de Dados
require_once '../../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// 2. Verifica ID na URL
$id_projeto = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$id_projeto) {
    die("<div style='text-align:center; padding:50px;'>Projeto não selecionado. <a href='menu-projetos.php'>Voltar</a></div>");
}

// 3. Busca Dados do Projeto
$query = "SELECT * FROM projeto WHERE id = :id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id_projeto);
$stmt->execute();
$proj = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proj) {
    die("<div style='text-align:center; padding:50px;'>Projeto não encontrado. <a href='menu-projetos.php'>Voltar</a></div>");
}

// 4. Busca Imagens da Galeria
$query_img = "SELECT * FROM imagens WHERE id_projeto = :id ORDER BY id ASC";
$stmt_img = $conn->prepare($query_img);
$stmt_img->bindParam(':id', $id_projeto);
$stmt_img->execute();
$imagens = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

// 5. Formatação de Dados
$data_inicio = date('d/m/Y', strtotime($proj['data_inicio']));
$data_fim = $proj['data_fim'] ? date('d/m/Y', strtotime($proj['data_fim'])) : 'Em andamento';

// Classes de Status
$status_class = 'pendente';
$status_formatado = strtolower($proj['status']);

if (strpos($status_formatado, 'andamento') !== false) {
    $status_class = 'ativo';
} elseif (strpos($status_formatado, 'conclu') !== false) {
    $status_class = 'concluido';
} elseif (strpos($status_formatado, 'pausa') !== false) {
    $status_class = 'pausado';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($proj['titulo']); ?> | IFMG Ouro Branco</title>
    <meta name="description" content="<?php echo substr(strip_tags($proj['descricao']), 0, 160); ?>...">
    
    <link rel="stylesheet" href="../../assets/css/style-pagina-projeto.css">
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="menu-principal.php" id="logo-link">
                <img src="../../assets/img/logo-branco.png" alt="IFMG Campus Ouro Branco" id="logo" />
            </a>

            <button class="menu-toggle" onclick="document.querySelector('.nav-items').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="nav-items">
                <a href="menu-cursos.php">Cursos</a>
                <a href="menu-laboratorios.html">Laboratórios</a>
                <a href="menu-professores.php">Docentes</a>
                <a href="menu-projetos.php" class="active">Projetos</a>
                <a href="../auth/login.php" class="btn-login">Acesso Restrito</a>
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
                            <span>Status: <span class="status <?php echo $status_class; ?>"><?php echo htmlspecialchars($proj['status']); ?></span></span>
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
                                            <?php 
                                                // Define legenda padrão se estiver vazia
                                                $legenda = !empty($img['legenda']) ? htmlspecialchars($img['legenda']) : 'Imagem da Galeria';
                                                // Texto alternativo para acessibilidade
                                                $alt_text = !empty($img['legenda']) ? htmlspecialchars($img['legenda']) : 'Foto da galeria do projeto ' . htmlspecialchars($proj['titulo']);
                                                $caminho = htmlspecialchars($img['caminho']);
                                            ?>
                                            <div class="galeria-item">
                                                <a href="../../assets/img/projetos/<?php echo $caminho; ?>" target="_blank" class="galeria-link" title="Clique para ampliar">
                                                    <div class="img-overflow">
                                                        <img src="../../assets/img/projetos/<?php echo $caminho; ?>" alt="<?php echo $alt_text; ?>" loading="lazy">
                                                    </div>
                                                </a>
                                                <div class="galeria-legenda">
                                                    <i class="fas fa-camera"></i> <span><?php echo $legenda; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-gallery">
                                        <i class="far fa-images"></i>
                                        <p>Nenhuma imagem adicional cadastrada para este projeto.</p>
                                    </div>
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
                                        <div class="info-label"><i class="fas fa-graduation-cap"></i> Alunos Envolvidos</div>
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
                                            $links = preg_split('/[\n,]+/', $proj['links']);
                                            foreach ($links as $link):
                                                $link = trim($link);
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
                                <p class="text-small">Dúvidas sobre este projeto? Entre em contato com a coordenação.</p>
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