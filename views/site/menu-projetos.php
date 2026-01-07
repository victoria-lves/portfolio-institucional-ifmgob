<?php
// 1. Configuração da Conexão (PDO)
// Importa o arquivo de configuração do banco
require_once '../../config/database.php';
// Instancia a classe Database
$database = new Database();
// Obtém a conexão ativa
$conn = $database->getConnection();

// 2. Executar a consulta (Modo PDO)
// Define a query SQL para buscar todos os projetos, ordenando pelos mais recentes
$sql = "SELECT * FROM projeto ORDER BY data_inicio DESC";

try {
  // Executa a consulta diretamente usando o método query do PDO
  $stmt = $conn->query($sql);
} catch (PDOException $e) {
  // Em caso de erro, exibe a mensagem e encerra o script
  echo "Erro na consulta: " . $e->getMessage();
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="keywords"
    content="projetos pesquisa IFMG Ouro Branco, projetos ensino IFMG Ouro Branco, projetos extensão campus Ouro Branco, trabalhos acadêmicos IFMG, iniciação científica IFMG, inovação tecnologia IFMG projetos, projetos metalurgia, projetos informática, projetos administração">

  <title>Projetos de Pesquisa e Extensão | IFMG Ouro Branco</title>
  <link rel="stylesheet" href="../../assets/css/style-menu-projetos.css">
  <link
    href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

  <header>
    <div class="container header-content">
      <a href="menu-principal.php" id="logo-link" aria-label="Voltar para a página inicial">
        <img src="../../assets/img/logo-branco.png" alt="Logo do IFMG Campus Ouro Branco" id="logo">
      </a>

      <button class="menu-toggle" aria-label="Abrir menu">
        <i class="fa-solid fa-bars"></i>
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
    <section class="theme" style="background-image: url('img/projetos-bg.jpg');">
      <div class="theme-overlay"></div>
      <div class="theme-content">
        <h1 class="theme-title">Nossos Projetos</h1>
        <p class="theme-desc">Ciência, tecnologia e inovação desenvolvidas no campus.</p>
      </div>
    </section>

    <section class="filtro-section">
      <div class="container">
        <div class="filtro-box">

          <div class="filtro-top">
            <div class="search-wrapper">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="searchInput" placeholder="Buscar por título, professor ou palavra-chave...">
            </div>
            <div class="filter-label">
              <i class="fa-solid fa-filter"></i> Áreas de Conhecimento:
            </div>
          </div>

          <form class="filtro-form" onsubmit="event.preventDefault()">
            <div class="filtro-opcoes">
              <?php
              // Define um array estático com as áreas possíveis
              $areas = ['Informática', 'Metalurgia', 'Administração', 'Engenharias', 'Ciências', 'Humanas', 'Pedagogia', 'Linguagens', 'Matemática', 'Outros'];
              // Itera sobre o array para criar os checkboxes dinamicamente
              foreach ($areas as $area) {
                echo "
                    <label class='checkbox-container'>
                      <input type='checkbox' value='$area' class='filtro-area'>
                      <span class='checkmark'></span> $area
                    </label>";
              }
              ?>
            </div>
            <button type="button" id="btn-limpar" class="btn-filtrar">Limpar Filtros</button>
          </form>

        </div>
      </div>
    </section>

    <section class="projetos-section">
      <div class="container">
        <div class="grid-projetos" id="projetosContainer">
          <?php
          // 3. Verificar resultados com rowCount() e fetch() do PDO
          // Verifica se a consulta retornou linhas e se o objeto statement existe
          if ($stmt && $stmt->rowCount() > 0) {
            // Loop while percorre cada linha do banco como um array associativo
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $id = $row['id'];
              // Sanitiza o título para evitar XSS
              $titulo = htmlspecialchars($row['titulo']);
              // Verifica se a área existe, senão define como 'Geral'
              $area = isset($row['area_conhecimento']) ? htmlspecialchars($row['area_conhecimento']) : 'Geral';
              $status = htmlspecialchars($row['status']);
              $autor = htmlspecialchars($row['autor']);

              // Processamento da descrição para o card
              // Remove tags HTML (strip_tags) para ter apenas texto puro no resumo
              $descricao = htmlspecialchars(strip_tags($row['descricao']));
              // Corta o texto se for maior que 120 caracteres para padronizar o tamanho dos cards
              if (strlen($descricao) > 120) {
                $descricao = substr($descricao, 0, 120) . "...";
              }

              // Lógica visual de Status
              // Define a classe CSS base
              $statusClass = 'status-pausado';
              // Altera a classe conforme o texto do status vindo do banco
              if ($status == 'Em andamento')
                $statusClass = 'status-andamento';
              if ($status == 'Concluído')
                $statusClass = 'status-concluido';

              // Renderiza o Card HTML
              // IMPORTANTE: Atributos 'data-*' armazenam dados crus para o filtro JS ler facilmente
              echo "
              <article class='card-projeto' data-titulo='$titulo' data-autor='$autor' data-area='$area'>
                
                <div class='projeto-header'>
                    <div class='badges'>
                        <span class='badge-area'><i class='fa-solid fa-tag'></i> $area</span>
                        <span class='badge-status $statusClass'>$status</span>
                    </div>
                </div>
                
                <div class='projeto-body'>
                  <a href='projeto/$id' class='projeto-titulo'>$titulo</a>
                  
                  <div class='projeto-meta'>
                    <p class='projeto-autor' title='$autor'>
                        <i class='fa-solid fa-users'></i> $autor
                    </p>
                  </div>
                  
                  <p class='projeto-desc'>$descricao</p>
                </div>

                <div class='projeto-footer'>
                    <a href='pagina-projeto.php?id=$id' class='projeto-titulo'>
                     Ver Projeto <i class='fa-solid fa-arrow-right'></i>
                   </a>
                </div>
              </article>
              ";
            }
          } else {
            // Se não houver projetos no banco, exibe mensagem de estado vazio
            echo "<div class='empty-state'><p>Nenhum projeto encontrado.</p></div>";
          }
          ?>
        </div>

        <div id="no-results" style="display: none;">
          <i class="fa-regular fa-folder-open"></i>
          <p>Nenhum projeto corresponde à sua busca.</p>
        </div>

      </div>
    </section>
  </main>

  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h4>Endereço</h4>
          <p><i class="fa-solid fa-location-dot"></i> Rua Afonso Sardinha, 90<br>Ouro Branco, MG - 36420-000</p>
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
    // Menu Mobile
    // Adiciona evento de clique para alternar a classe 'active' na navegação
    document.querySelector('.menu-toggle').addEventListener('click', function () {
      document.querySelector('.nav-items').classList.toggle('active');
    });

    // Lógica de Filtragem (Javascript Puro)
    // Aguarda o DOM carregar completamente antes de rodar o script
    document.addEventListener('DOMContentLoaded', function () {
      // Seleciona os elementos do DOM necessários
      const searchInput = document.getElementById('searchInput');
      const areaCheckboxes = document.querySelectorAll('.filtro-area');
      const cards = document.querySelectorAll('.card-projeto'); // Pega todos os cards gerados pelo PHP
      const noResults = document.getElementById('no-results');
      const btnLimpar = document.getElementById('btn-limpar');

      // Função principal que aplica os filtros
      function filtrar() {
        // Normaliza o termo de busca: converte para minúsculo e remove acentos
        // Ex: "Iniciação" vira "iniciacao"
        const termo = searchInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        // Cria um array apenas com os valores dos checkboxes marcados
        const areasSelecionadas = Array.from(areaCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => cb.value.toLowerCase());

        let visiveis = 0; // Contador de itens visíveis

        // Itera sobre cada card de projeto
        cards.forEach(card => {
          // Recupera os dados armazenados nos atributos 'data-*' do HTML
          const titulo = card.getAttribute('data-titulo').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
          const autor = card.getAttribute('data-autor').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
          const area = card.getAttribute('data-area').toLowerCase();

          // Lógica 1: Verifica se o termo digitado está no título OU no autor
          const matchTexto = termo === '' || titulo.includes(termo) || autor.includes(termo);

          // Lógica 2: Verifica se a área do projeto está na lista de checkboxes marcados
          // Se nenhuma área estiver marcada (length 0), considera como verdadeiro (mostra tudo)
          const matchArea = areasSelecionadas.length === 0 || areasSelecionadas.some(sel => area.includes(sel));

          // Se passar em AMBOS os testes, mostra o card
          if (matchTexto && matchArea) {
            card.style.display = 'flex';
            visiveis++;
          } else {
            // Caso contrário, esconde o card
            card.style.display = 'none';
          }
        });

        // Controla a visibilidade da mensagem de "Sem resultados"
        noResults.style.display = (visiveis === 0) ? 'block' : 'none';
      }

      // Adiciona "ouvintes" para disparar a função filtrar a cada interação
      searchInput.addEventListener('input', filtrar); // Ao digitar
      areaCheckboxes.forEach(cb => cb.addEventListener('change', filtrar)); // Ao clicar num checkbox

      // Configura o botão de limpar filtros
      btnLimpar.addEventListener('click', () => {
        searchInput.value = ''; // Limpa texto
        areaCheckboxes.forEach(cb => cb.checked = false); // Desmarca checkboxes
        filtrar(); // Roda o filtro novamente (resetando a vista)
      });
    });
  </script>
</body>

</html>