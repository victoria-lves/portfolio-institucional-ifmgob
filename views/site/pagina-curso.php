<?php
// 1. Configuração da Conexão (PDO)
// Carrega o arquivo de configuração do banco de dados
require_once '../../config/database.php';
// Instancia a classe Database
$database = new Database();
// Obtém a conexão ativa com o banco
$conn = $database->getConnection();

// 2. Pega o ID da URL
// Usa o operador de coalescência nula (??) para pegar o ID ou definir como null se não existir
$id_curso = $_GET['id'] ?? null;

// Verifica se o ID é nulo ou falso
if (!$id_curso) {
    // Interrompe o script e exibe mensagem de erro com link de volta
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>Curso não selecionado. <a href='menu-cursos.php'>Voltar para Cursos</a></div>");
}

// 3. Busca dados no banco (Modo PDO)
// Define a query para buscar o curso específico pelo ID
$query = "SELECT * FROM curso WHERE id = :id LIMIT 1";
$stmt = $conn->prepare($query);
// Vincula o parâmetro :id de forma segura
$stmt->bindParam(':id', $id_curso);
$stmt->execute();

// Recupera os dados do curso como um array associativo
$curso = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o curso foi encontrado no banco
if (!$curso) {
    // Interrompe o script se o ID não corresponder a nenhum registro
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>Curso não encontrado. <a href='menu-cursos.php'>Voltar para Cursos</a></div>");
}

// Imagem padrão se não houver
// Verifica se existe imagem cadastrada; se não, define uma imagem genérica ('escola.png')
$img_capa = !empty($curso['imagem']) ? $curso['imagem'] : 'img/escola.png';

// Verifica se a chave 'nivel' existe para evitar erros de índice indefinido
$nivel_curso = isset($curso['nivel']) ? $curso['nivel'] : '';

// Lógica para definir Keywords baseadas no Título do Curso (assumindo que $curso['nome'] ou $titulo já foi carregado do banco)
$keywords_curso = "";

// Normaliza o texto para comparação (tudo minúsculo)
$nome_curso_check = mb_strtolower($curso['nome'] ?? ''); // Use a variável que guarda o nome no seu código

if (strpos($nome_curso_check, 'administração') !== false && strpos($nome_curso_check, 'técnico') !== false) {
    // Técnico em Administração
    $keywords_curso = "técnico em administração integrado IFMG Ouro Branco, curso técnico administração ensino médio Ouro Branco, administração integrado IFMG duração, mercado trabalho técnico administração";
} elseif (strpos($nome_curso_check, 'informática') !== false && strpos($nome_curso_check, 'técnico') !== false) {
    // Técnico em Informática
    $keywords_curso = "técnico em informática IFMG Ouro Branco, curso técnico informática integrado, formação técnica informática campus Ouro Branco, tecnologia informação IFMG";
} elseif (strpos($nome_curso_check, 'metalurgia') !== false && strpos($nome_curso_check, 'técnico') !== false) {
    // Verifica se é subsequente ou integrado
    if (strpos($nome_curso_check, 'subsequente') !== false) {
        $keywords_curso = "técnico metalurgia subsequente IFMG, curso metalurgia para quem já tem ensino médio, metalurgia formação rápida IFMG Ouro Branco";
    } else {
        $keywords_curso = "técnico em metalurgia IFMG Ouro Branco, curso metalurgia integrado ensino médio, formação técnica metalurgia Minas Gerais, metalurgia IFMG mercado trabalho";
    }
} elseif (strpos($nome_curso_check, 'sistemas de informação') !== false) {
    // Sistemas de Informação
    $keywords_curso = "bacharelado sistemas informação IFMG Ouro Branco, curso SI graduação IFMG, tecnologia informação graduação campus Ouro Branco, Sistemas de Informação nota corte IFMG";
} elseif (strpos($nome_curso_check, 'engenharia metalúrgica') !== false) {
    // Engenharia
    $keywords_curso = "engenharia metalúrgica IFMG Ouro Branco, curso engenharia metalúrgica Minas Gerais, graduação metalurgia IFMG, engenheiro metalurgista formação IFMG";
} elseif (strpos($nome_curso_check, 'administração') !== false && strpos($nome_curso_check, 'bacharelado') !== false) {
    // ADM Bacharelado
    $keywords_curso = "administração bacharelado IFMG Ouro Branco, curso administração graduação campus Ouro Branco, ADM IFMG duração, administração empresas formação IFMG";
} elseif (strpos($nome_curso_check, 'pedagogia') !== false) {
    // Pedagogia
    $keywords_curso = "pedagogia licenciatura IFMG Ouro Branco, curso formação professores IFMG, licenciatura pedagogia campus Ouro Branco, pedagogia IFMG grade curricular";
} elseif (strpos($nome_curso_check, 'gestão de negócios') !== false) {
    // Especialização
    $keywords_curso = "especialização gestão negócios IFMG, pós-graduação gestão empresarial Ouro Branco, MBA gestão IFMG, especialização administração IFMG";
} elseif (strpos($nome_curso_check, 'profept') !== false) {
    // PROFEPT
    $keywords_curso = "PROFEPT IFMG Ouro Branco, especialização formação professores educação profissional, pós-graduação educação profissional IFMG, PROFEPT 2025 inscrições";
} else {
    // Padrão caso não ache
    $keywords_curso = "curso IFMG Ouro Branco, ensino gratuito, federal ouro branco";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="<?php echo $keywords_curso; ?>">
    <title><?php echo htmlspecialchars($seo_title); ?></title>

    <link
        href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style-pagina-curso.css">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="menu-principal.php" id="logo-link" aria-label="Voltar para a página inicial">
                <img src="../../assets/img/logo-branco.png" alt="Logo do IFMG Campus Ouro Branco" id="logo" />
            </a>

            <button class="menu-toggle" aria-label="Abrir menu de navegação">
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
        <section class="theme" style="background-image: url('<?php echo htmlspecialchars($img_capa); ?>');">
            <div class="theme-overlay"></div> <a href="menu-cursos.php" class="btn-voltar-canto">
                <i class="fa-solid fa-arrow-left"></i> Voltar
            </a>

            <div class="theme-content">
                <span
                    class="area-badge"><?php echo isset($curso['nivel']) ? htmlspecialchars($curso['nivel']) : 'Curso'; ?></span>
                <h1 class="theme-title"><?php echo htmlspecialchars($curso['nome']); ?></h1>
            </div>
        </section>

        <section class="conteudo-section">
            <div class="container">
                <div class="conteudo-grid">

                    <div class="coluna-principal">

                        <article class="content-card">
                            <div class="card-header">
                                <h2><i class="fa-solid fa-book-open"></i> Sobre o Curso</h2>
                            </div>
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>

                                <?php if (!empty($curso['perfil_egresso'])): ?>
                                    <div class="topico-extra">
                                        <h3>Perfil do Egresso</h3>
                                        <p><?php echo nl2br(htmlspecialchars($curso['perfil_egresso'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($curso['atuacao'])): ?>
                                    <div class="topico-extra">
                                        <h3>Área de Atuação</h3>
                                        <p><?php echo nl2br(htmlspecialchars($curso['atuacao'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>

                    <aside class="coluna-lateral">

                        <div class="sidebar-card">
                            <h3><i class="fa-solid fa-circle-info"></i> Informações</h3>
                            <ul class="info-list">
                                <li>
                                    <span><i class="fa-regular fa-clock"></i> Duração</span>
                                    <strong><?php echo isset($curso['duracao']) ? htmlspecialchars($curso['duracao']) : '-'; ?></strong>
                                </li>
                                <li>
                                    <span><i class="fa-solid fa-sun"></i> Turno</span>
                                    <strong><?php echo isset($curso['turno']) ? htmlspecialchars($curso['turno']) : '-'; ?></strong>
                                </li>
                                <li>
                                    <span><i class="fa-solid fa-location-dot"></i> Campus</span>
                                    <strong>Ouro Branco</strong>
                                </li>
                            </ul>
                        </div>

                        <div class="sidebar-card">
                            <h3><i class="fa-solid fa-user-tie"></i> Coordenação</h3>
                            <div class="coord-info">
                                <p class="coord-nome">
                                    <?php echo isset($curso['coordenador']) ? htmlspecialchars($curso['coordenador']) : 'A definir'; ?>
                                </p>

                                <?php if (!empty($curso['email_coordenador'])): ?>
                                    <p class="coord-email">
                                        <i class="fa-solid fa-envelope"></i>
                                        <?php echo htmlspecialchars($curso['email_coordenador']); ?>
                                    </p>
                                    <a href="mailto:<?php echo htmlspecialchars($curso['email_coordenador']); ?>"
                                        class="btn-contato">
                                        Fale com o Coordenador
                                    </a>
                                <?php endif; ?>
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
        // Adiciona ouvinte de evento para alternar o menu em telas pequenas
        document.querySelector('.menu-toggle').addEventListener('click', function () {
            document.querySelector('.nav-items').classList.toggle('active');
        });
    </script>

</body>

</html>