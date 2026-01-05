<?php
// Inicia ou resume a sessão existente
// Isso permite acessar variáveis globais como $_SESSION['usuario_id'] para verificar login
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="IFMG Ouro Branco, Instituto Federal Minas Gerais, cursos técnicos gratuitos, ensino de qualidade Ouro Branco, estrutura organizacional IFMG, direção campus Ouro Branco, documentos institucionais IFMG, regulamentos IFMG campus Ouro Branco">
    <title>IFMG Ouro Branco | Futuro em Movimento</title>

    <link rel="stylesheet" href="css/style-menu-principal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet" />
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="menu-principal.php" id="logo-link">
                <img src="img/logo-branco.png" alt="Logo IFMG" id="logo" />
            </a>

            <button class="menu-toggle" id="menuToggle">
                <i class="fa-solid fa-bars"></i>
            </button>

            <nav class="nav-items" id="navMenu">
                <a href="menu-cursos.php">Cursos</a>
                <a href="menu-laboratorios.html">Laboratórios</a>
                <a href="menu-docentes.php">Docentes</a>
                <a href="menu-projetos.php">Projetos</a>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="views/painel.php" class="btn-login">
                        <i class="fa-solid fa-user"></i> Painel
                    </a>
                <?php else: ?>
                    <a href="views/auth/login.php" class="btn-login">Acesso Restrito</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <section class="theme">
            <div class="theme-overlay"></div> <div class="container theme-content">
                <h1 class="theme-title">Transforme o seu <br>Futuro Agora</h1>
                <p class="theme-desc">Ensino público, gratuito e de excelência. Conectando teoria e prática em um
                    ambiente inovador.</p>

                <div class="hero-buttons">
                    <a href="menu-cursos.php" class="btn-primary-glow">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Conheça os Cursos
                    </a>
                    <a href="#diferenciais" class="btn-outline-light">
                        <i class="fa-solid fa-arrow-down"></i>
                        Saiba Mais
                    </a>
                </div>
            </div>
        </section>

        <section id="diferenciais" class="diferenciais-section">
            <div class="container">
                <div class="section-header">
                    <h2>Nossos Diferenciais</h2>
                    <p>Descubra por que somos referência em educação técnica e tecnológica</p>
                </div>
                <div class="diferenciais-grid">
                    <div class="diferencial-card">
                        <div class="diferencial-icon">
                            <i class="fa-solid fa-trophy"></i>
                        </div>
                        <h3>Excelência Acadêmica</h3>
                        <p>Nota máxima no MEC e reconhecimento nacional pela qualidade do ensino e infraestrutura.</p>
                        <div class="diferencial-stats">
                            <div class="stat-item">
                                <span class="stat-number">100%</span>
                                <span class="stat-label">Avaliação MEC</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">25+</span>
                                <span class="stat-label">Cursos</span>
                            </div>
                        </div>
                    </div>
                    <div class="diferencial-card">
                        <div class="diferencial-icon">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <h3>Inserção no Mercado</h3>
                        <p>Mais de 85% dos nossos alunos estão empregados ou empreendendo no primeiro ano após a
                            formatura.</p>
                        <div class="diferencial-stats">
                            <div class="stat-item">
                                <span class="stat-number">85%</span>
                                <span class="stat-label">Empregabilidade</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">40+</span>
                                <span class="stat-label">Parcerias</span>
                            </div>
                        </div>
                    </div>
                    <div class="diferencial-card">
                        <div class="diferencial-icon">
                            <i class="fa-solid fa-flask-vial"></i>
                        </div>
                        <h3>Infraestrutura Completa</h3>
                        <p>Laboratórios modernos, biblioteca atualizada e espaços de convivência que estimulam o
                            aprendizado.</p>
                        <div class="diferencial-stats">
                            <div class="stat-item">
                                <span class="stat-number">30+</span>
                                <span class="stat-label">Laboratórios</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">10k+</span>
                                <span class="stat-label">Livros</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <div class="section-header">
                    <h2>Informações Institucionais</h2>
                    <p>Conheça mais sobre nossa estrutura e organização</p>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <h4><i class="fa-solid fa-calendar-alt"></i> Calendário Acadêmico</h4>
                        <ul>
                            <li>Inscrições: Janeiro/Fevereiro</li>
                            <li>Início das Aulas: Março</li>
                            <li>Período de Matrículas: Dezembro a Fevereiro</li>
                            <li>Férias: Julho e Dezembro/Janeiro</li>
                        </ul>
                    </div>
                    <div class="info-item">
                        <h4><i class="fa-solid fa-handshake"></i> Programas Institucionais</h4>
                        <ul>
                            <li>Iniciação Científica</li>
                            <li>Estágios Supervisionados</li>
                            <li>Programa de Monitoria</li>
                            <li>Projetos de Extensão</li>
                        </ul>
                    </div>
                    <div class="info-highlight">
                        <h4><i class="fa-solid fa-medal"></i> Reconhecimento Nacional</h4>
                        <p>O IFMG Campus Ouro Branco é referência em educação técnica e tecnológica em Minas Gerais...</p>
                        <p>Nossos cursos são constantemente atualizados para atender às demandas do mercado...</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="parallax-separator">
            <div class="parallax-overlay"></div>
            <div class="container parallax-content">
                <h2>Faça parte da nossa história</h2>
                <p>Venha construir conhecimento no IFMG Campus Ouro Branco...</p>
                <a href="https://www.ifmg.edu.br/ourobranco/" target="_blank" class="btn-white">
                    <i class="fa-solid fa-external-link-alt"></i>
                    Acesse nosso Portal
                </a>
            </div>
        </section>
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

    <script>
        // Seleciona elementos para o menu mobile
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');

        // Adiciona evento de clique no botão hambúrguer
        menuToggle.addEventListener('click', () => {
            // Alterna a classe 'active' para mostrar/esconder o menu
            navMenu.classList.toggle('active');
            
            // Alterna o ícone entre Barras (fechado) e X (aberto)
            menuToggle.innerHTML = navMenu.classList.contains('active')
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';
        });

        // Fecha o menu automaticamente ao clicar em qualquer link
        document.querySelectorAll('.nav-items a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
            });
        });

        // Lógica para rolagem suave (Smooth Scroll) em links internos (#)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault(); // Impede o pulo brusco padrão
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    // Rola suavemente até o elemento, descontando 80px do cabeçalho fixo
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>

</html>