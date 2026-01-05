<?php
// 1. Configuração e Conexão
// Importa as credenciais e instancia a conexão segura via PDO
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// 2. Roteamento de Visualização
// Verifica se o parâmetro 'id' existe na URL. Se existir, define o modo 'perfil', senão, modo 'lista'.
$id_professor = isset($_GET['id']) ? (int) $_GET['id'] : null;
$view = 'lista'; // Define a visualização padrão
$prof = null;

// 3. Controlador Lógico
if ($id_professor) {
    // MODO PERFIL: Exibe detalhes de um único professor
    
    // Busca os dados cadastrais do professor
    $query = "SELECT * FROM professor WHERE id = :id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id_professor);
    $stmt->execute();
    $prof = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prof) {
        $view = 'perfil'; // Confirma a mudança de visualização

        // Busca as produções acadêmicas (Livros, Artigos) vinculadas
        $query_prod = "SELECT * FROM producao WHERE id_professor = :id ORDER BY data_pub DESC";
        $stmt_prod = $conn->prepare($query_prod);
        $stmt_prod->bindParam(':id', $id_professor);
        $stmt_prod->execute();
        $producoes = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

        // Busca os projetos de pesquisa (Relação N:N via tabela professor_projeto)
        // O INNER JOIN cruza as tabelas para trazer dados do projeto onde o professor atua
        $query_proj = "SELECT p.* FROM projeto p
                       INNER JOIN professor_projeto pp ON p.id = pp.id_projeto
                       WHERE pp.id_professor = :id 
                       ORDER BY p.data_inicio DESC";
        $stmt_proj = $conn->prepare($query_proj);
        $stmt_proj->bindParam(':id', $id_professor);
        $stmt_proj->execute();
        $projetos = $stmt_proj->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $view = 'erro'; // Define estado de erro se o ID não existir no banco
    }
} else {
    // MODO LISTA: Exibe todos os professores
    // Busca apenas os dados básicos para montar os cards da galeria
    $query = "SELECT * FROM professor ORDER BY nome ASC";
    $stmt_lista = $conn->prepare($query);
    $stmt_lista->execute();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="keywords" content="corpo docente IFMG Ouro Branco, professores campus Ouro Branco, qualificação professores IFMG, coordenadores cursos IFMG, lista de professores IFMG">
    <title>
        <?php echo ($view === 'perfil' && $prof) ? 'Perfil - ' . htmlspecialchars($prof['nome']) : 'Nossos Docentes'; ?>
        | IFMG
    </title>

    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />

    <?php if ($view === 'perfil'): ?>
        <link rel="stylesheet" href="css/style-pagina-docente.css">
    <?php else: ?>
        <link rel="stylesheet" href="css/style-menu-docentes.css" />
    <?php endif; ?>
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
          <a href="menu-laboratorios.html">Laboratórios</a>
          <a href="menu-docentes.php" class="active">Docentes</a>
          <a href="menu-projetos.php">Projetos</a>
          <a href="views/auth/login.php" class="btn-login">Acesso Restrito</a>
        </nav>
      </div>
    </header>

    <main>
        <?php if ($view === 'perfil' && $prof): ?>
            <section class="profile-hero">
                <?php
                // Lógica de Prioridade da Foto de Perfil
                $pfpName = $prof['pfp'] ?? '';
                $localPath = 'img/docentes/' . $pfpName;
                $defaultImg = 'img/docentes/default-pfp.webp';

                // Decide qual imagem mostrar: Link Externo > Arquivo Local > Imagem Padrão
                if (empty($pfpName)) {
                    $src = $defaultImg;
                } elseif (strpos($pfpName, 'http') === 0) {
                    $src = $pfpName; // É um link externo
                } elseif (file_exists($localPath)) {
                    $src = $localPath; // É um arquivo local válido
                } else {
                    $src = $defaultImg; // Fallback
                }
                ?>
                
                <div class="profile-header-wrapper">
                    <img src="<?php echo $src; ?>" alt="<?php echo htmlspecialchars($prof['nome']); ?>" class="profile-img">
                    <h1 class="profile-name"><?php echo htmlspecialchars($prof['nome']); ?></h1>
                </div>
            </section>

            <section class="conteudo-section">
                <div class="container">
                    <a href="menu-docentes.php" class="btn-voltar">
                        <i class="fa-solid fa-arrow-left"></i> Voltar para Docentes
                    </a>

                    <div class="conteudo-grid">
                        <div class="coluna-principal">
                            <article class="content-card">
                                <div class="card-header">
                                    <h2><i class="fa-solid fa-user"></i> Sobre</h2>
                                </div>
                                <div class="card-body">
                                    <p class="text-bio"><?php echo nl2br(htmlspecialchars($prof['bio'])); ?></p>
                                </div>
                            </article>

                            <?php if (!empty($prof['formacao'])): ?>
                                <article class="content-card">
                                    <div class="card-header">
                                        <h2><i class="fa-solid fa-graduation-cap"></i> Formação Acadêmica</h2>
                                    </div>
                                    <div class="card-body">
                                        <p><?php echo nl2br(htmlspecialchars($prof['formacao'])); ?></p>
                                    </div>
                                </article>
                            <?php endif; ?>

                            <article class="content-card">
                                <div class="card-header">
                                    <h2><i class="fa-solid fa-book-open"></i> Produções Acadêmicas</h2>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($producoes)): ?>
                                        <ul class="lista-documentos">
                                            <?php foreach ($producoes as $prod): ?>
                                                <li>
                                                    <div class="doc-item">
                                                        <span class="doc-type"><?php echo htmlspecialchars($prod['tipo']); ?></span>
                                                        <span class="doc-title"><?php echo htmlspecialchars($prod['titulo']); ?></span>
                                                        <?php if ($prod['link']): ?>
                                                            <a href="pagina-producao.php?id=<?php echo $prod['id']; ?>" target="_blank" class="doc-link">
                                                                Ver detalhes <i class="fa-solid fa-external-link-alt"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="empty-state-small">Nenhuma produção cadastrada.</div>
                                    <?php endif; ?>
                                </div>
                            </article>

                            <?php if (!empty($projetos)): ?>
                                <article class="content-card mt-4">
                                    <div class="card-header">
                                        <h2><i class="fa-solid fa-lightbulb"></i> Projetos de Pesquisa e Extensão</h2>
                                    </div>
                                    <div class="card-body">
                                        <div class="grid-projetos-mini">
                                            <?php foreach ($projetos as $proj): ?>
                                                <div class="projeto-mini-card">
                                                    <div class="proj-status <?php echo strtolower(str_replace(' ', '-', $proj['status'])); ?>">
                                                        <?php echo htmlspecialchars($proj['status']); ?>
                                                    </div>
                                                    <h4><?php echo htmlspecialchars($proj['titulo']); ?></h4>
                                                    <small>Área: <?php echo htmlspecialchars($proj['area_conhecimento']); ?></small>
                                                    <a href="pagina-projeto.php?id=<?php echo $proj['id']; ?>" class="btn-link">
                                                        Ver detalhes <i class="fa-solid fa-arrow-right"></i>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endif; ?>
                        </div>

                        <aside class="coluna-lateral">
                            <div class="sidebar-card">
                                <h3><i class="fa-solid fa-envelope"></i> Contato</h3>
                                <div class="card-body">
                                    <?php if (!empty($prof['email'])): ?>
                                        <div class="contact-item">
                                            <i class="fa-solid fa-at"></i>
                                            <span><?php echo htmlspecialchars($prof['email']); ?></span>
                                        </div>
                                        <a href="mailto:<?php echo htmlspecialchars($prof['email']); ?>" class="btn-primary-full">Enviar E-mail</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($prof['lattes']) || !empty($prof['linkedin'])): ?>
                                <div class="sidebar-card">
                                    <h3><i class="fa-solid fa-link"></i> Links Externos</h3>
                                    <ul class="info-list">
                                        <?php if (!empty($prof['lattes'])): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars($prof['lattes']); ?>" target="_blank" class="external-link">
                                                    <i class="fa-solid fa-file-contract"></i> Currículo Lattes
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($prof['linkedin'])): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars($prof['linkedin']); ?>" target="_blank" class="external-link">
                                                    <i class="fa-brands fa-linkedin"></i> LinkedIn
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($prof['atendimento']) || !empty($prof['gabinete'])): ?>
                                <div class="sidebar-card">
                                    <h3><i class="fa-solid fa-clock"></i> Atendimento</h3>
                                    <div class="card-body">
                                        <?php if (!empty($prof['gabinete'])): ?>
                                            <div class="info-block">
                                                <strong>Gabinete/Área:</strong>
                                                <p><?php echo htmlspecialchars($prof['gabinete']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($prof['atendimento'])): ?>
                                            <div class="info-block">
                                                <strong>Horários:</strong>
                                                <p><?php echo htmlspecialchars($prof['atendimento']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </aside>
                    </div>
                </div>
            </section>

        <?php elseif ($view === 'erro'): ?>
            <section class="error-section">
                <div class="container text-center">
                    <i class="fa-solid fa-user-slash icon-error"></i>
                    <h2>Professor não encontrado</h2>
                    <p>O professor solicitado não foi localizado.</p>
                    <a href="menu-docentes.php" class="btn-voltar">Voltar para Lista</a>
                </div>
            </section>

        <?php else: ?>
            <section class="theme">
                <div class="theme-overlay"></div>
                <div class="theme-content">
                    <h1 class="theme-title">Corpo Docente</h1>
                    <p class="theme-desc">Conheça os professores mestres e doutores dedicados à excelência no ensino.</p>
                </div>
            </section>

            <section class="filtro-section">
                <div class="container">
                    <div class="filtro-box">
                        <div class="filtro-top">
                            <div class="search-wrapper">
                                <i class="fa-solid fa-search"></i>
                                <input type="search" id="searchInput" placeholder="Busque por nome...">
                            </div>
                            <div class="filter-label">
                                <i class="fa-solid fa-filter"></i> Filtrar por Área:
                            </div>
                        </div>

                        <form class="filtro-form" onsubmit="event.preventDefault()">
                            <div class="filtro-opcoes">
                                <?php
                                $areas = ['Administração', 'Humanas', 'Informática', 'Linguagens', 'Matemática', 'Metalurgia', 'Naturezas'];
                                foreach ($areas as $area) {
                                    echo '
                                    <label class="checkbox-container">
                                        <input type="checkbox" value="' . $area . '" class="gabinete-checkbox">
                                        <span class="checkmark"></span> ' . $area . '
                                    </label>';
                                }
                                ?>
                            </div>
                            <button type="button" id="btn-limpar" class="btn-filtrar">Limpar Filtros</button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="docentes-section">
                <div class="container">
                    <div class="galeria-grid" id="docentesContainer">
                        <?php
                        // Verifica se há docentes cadastrados
                        if ($stmt_lista && $stmt_lista->rowCount() > 0) {
                            while ($row = $stmt_lista->fetch(PDO::FETCH_ASSOC)) {
                                
                                // Processamento da Imagem para o Card (Mesma lógica do perfil)
                                $nome_arquivo = $row['pfp'];
                                $caminho_local = 'img/docentes/' . $nome_arquivo;
                                $img_padrao = 'img/docentes/default-pfp.webp';

                                if (empty($nome_arquivo)) {
                                    $img = $img_padrao;
                                } elseif (strpos($nome_arquivo, 'http') === 0) {
                                    $img = htmlspecialchars($nome_arquivo);
                                } elseif (file_exists($caminho_local)) {
                                    $img = $caminho_local;
                                } else {
                                    $img = $img_padrao;
                                }

                                $nome = htmlspecialchars($row['nome']);
                                // O campo 'gabinete' é usado aqui como categoria/área para o filtro
                                $area_gabinete = htmlspecialchars($row['gabinete']);
                                $formacao = htmlspecialchars($row['formacao']);
                                $id = $row['id'];

                                // Renderiza o card com data-attributes para filtragem via JS
                                echo "
                                <article class='docente-card' data-nome='$nome' data-gabinete='$area_gabinete'>
                                  <div class='card-image'>
                                    <a href='?id=$id'>
                                      <img src='$img' alt='Foto de $nome' loading='lazy'>
                                    </a>
                                  </div>
                                  <div class='card-content'>
                                    <h3 class='card-title'>
                                      <a href='?id=$id'>$nome</a>
                                    </h3>
                                    <p class='card-info'>$formacao</p>
                                    <a href='?id=$id' class='btn-detalhes'>
                                      Ver Perfil <i class='fa-solid fa-arrow-right'></i>
                                    </a>
                                  </div>
                                </article>";
                            }
                        } else {
                            echo "<div class='empty-state'><p>Nenhum docente cadastrado ainda.</p></div>";
                        }
                        ?>
                    </div>

                    <div id="no-results" style="display: none; text-align: center; padding: 50px; color: #666;">
                        <i class="fa-solid fa-user-slash" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>Nenhum professor encontrado com os filtros atuais.</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer>
      <div class="container">
        <div class="footer-content">
          <div class="footer-section">
            <h4>Endereço</h4>
            <p><i class="fa-solid fa-location-dot"></i> Rua Afonso Sardinha, 90<br />Ouro Branco, MG - 36420-000</p>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Lógica do Menu Mobile
            const menuToggle = document.querySelector('.menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    document.querySelector('.nav-items').classList.toggle('active');
                });
            }

            // Lógica de Filtragem (Carregada apenas na visualização de lista)
            <?php if ($view === 'lista'): ?>
                const searchInput = document.getElementById('searchInput');
                const btnLimpar = document.getElementById('btn-limpar');
                const gabineteCheckboxes = document.querySelectorAll('.gabinete-checkbox');
                const docenteCards = document.querySelectorAll('.docente-card');
                const noResultsMsg = document.getElementById('no-results');

                if (searchInput) {
                    function filtrarDocentes() {
                        // Normaliza texto da busca (minúsculo e sem acentos)
                        const searchTerm = searchInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                        
                        // Cria lista de áreas selecionadas
                        const selectedGabinetes = Array.from(gabineteCheckboxes)
                            .filter(cb => cb.checked)
                            .map(cb => cb.value);

                        let visiveis = 0;

                        docenteCards.forEach(card => {
                            // Recupera dados do HTML (data-attributes)
                            const nomeRaw = card.getAttribute('data-nome').toLowerCase();
                            const nomeNormalized = nomeRaw.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                            const gabinete = card.getAttribute('data-gabinete');

                            // Lógica E/OU
                            const nomeMatch = searchTerm === '' || nomeNormalized.includes(searchTerm);
                            // Se nenhuma checkbox marcada, mostra tudo. Senão, exige correspondência exata da área.
                            const gabineteMatch = selectedGabinetes.length === 0 || selectedGabinetes.includes(gabinete);

                            // Aplica visibilidade
                            if (nomeMatch && gabineteMatch) {
                                card.style.display = 'flex';
                                visiveis++;
                            } else {
                                card.style.display = 'none';
                            }
                        });

                        // Controle da mensagem "Sem resultados"
                        noResultsMsg.style.display = (visiveis === 0) ? 'block' : 'none';
                    }

                    // Listeners de eventos
                    searchInput.addEventListener('input', filtrarDocentes);
                    gabineteCheckboxes.forEach(checkbox => checkbox.addEventListener('change', filtrarDocentes));
                    
                    // Botão de reset
                    btnLimpar.addEventListener('click', function () {
                        searchInput.value = '';
                        gabineteCheckboxes.forEach(cb => cb.checked = false);
                        filtrarDocentes();
                    });
                }
            <?php endif; ?>
        });
    </script>
</body>

</html>