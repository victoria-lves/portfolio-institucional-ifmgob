class SessionTimeout {
    // O construtor define as propriedades iniciais da classe
    // Aceita parâmetros para o tempo total e tempo de aviso (com valores padrão)
    constructor(timeoutMinutes = 15, warningMinutes = 5) {
        // Converte os minutos do timeout total para milissegundos
        this.timeout = timeoutMinutes * 60 * 1000; // ms
        // Converte os minutos do aviso prévio para milissegundos
        this.warning = warningMinutes * 60 * 1000; // ms
        // Define uma flag para controlar se o modal de aviso já está visível
        this.warningShown = false;
        // Inicia o processo de monitoramento
        this.init();
    }
    
    init() {
        // Define o tempo inicial de atividade imediatamente ao carregar
        this.resetTimer();
        
        // Configura "ouvintes" (listeners) para resetar o timer quando o usuário interage
        // Detecta cliques, digitação, movimento do mouse e rolagem da página
        document.addEventListener('click', () => this.resetTimer());
        document.addEventListener('keypress', () => this.resetTimer());
        document.addEventListener('mousemove', () => this.resetTimer());
        document.addEventListener('scroll', () => this.resetTimer());
        
        // Cria um intervalo que executa a verificação de tempo repetidamente
        // A função checkTimeout roda a cada 60.000ms (1 minuto)
        setInterval(() => this.checkTimeout(), 60000); 
    }
    
    resetTimer() {
        // Salva o timestamp (horário) atual no LocalStorage do navegador
        // Isso permite sincronizar a atividade entre diferentes abas abertas
        localStorage.setItem('lastActivity', Date.now());
        // Garante que o sistema saiba que o aviso não está sendo exibido agora
        this.warningShown = false;
    }
    
    checkTimeout() {
        // Recupera o horário da última atividade salva
        const lastActivity = localStorage.getItem('lastActivity');
        // Se não houver registro, interrompe a função
        if (!lastActivity) return;
        
        // Pega o horário exato de agora
        const now = Date.now();
        // Calcula quanto tempo passou desde a última interação (diferença)
        const elapsed = now - parseInt(lastActivity);
        
        // Verifica se o tempo decorrido ultrapassa o tempo de aviso
        // E verifica se o aviso ainda não está na tela
        if (!this.warningShown && elapsed > this.warning) {
            this.showWarning();   // Chama o modal
            this.warningShown = true; // Marca que o aviso está ativo
        }
        
        // Verifica se o tempo decorrido ultrapassa o limite total da sessão
        if (elapsed > this.timeout) {
            this.forceLogout(); // Derruba a sessão
        }
    }
    
    showWarning() {
        // Constrói o HTML do modal usando classes do Bootstrap 5
        // Inclui ícones, barra de progresso e botões
        const modalHtml = `
            <div class="modal fade" id="timeoutModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Sessão prestes a expirar
                            </h5>
                        </div>
                        <div class="modal-body">
                            <p>Sua sessão irá expirar em <strong id="countdown">5:00</strong> minutos por inatividade.</p>
                            <div class="progress">
                                <div class="progress-bar bg-warning" id="timeoutProgress" 
                                     style="width: 100%"></div>
                            </div>
                            <p class="mt-3">Clique em "Continuar" para manter a sessão ativa.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" id="continueBtn">
                                <i class="bi bi-play-circle me-2"></i> Continuar
                            </button>
                            <button type="button" class="btn btn-secondary" id="logoutBtn">
                                <i class="bi bi-box-arrow-right me-2"></i> Sair Agora
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Verifica se o modal já existe no DOM para não duplicar
        if (!document.getElementById('timeoutModal')) {
            // Insere o HTML do modal no final do corpo da página
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Inicializa o componente Modal do Bootstrap
            const modal = new bootstrap.Modal(document.getElementById('timeoutModal'));
            modal.show(); // Exibe o modal na tela
            
            // Define variáveis para controlar a contagem regressiva visual
            let timeLeft = 5 * 60; // 5 minutos convertidos em segundos
            const countdownEl = document.getElementById('countdown');
            const progressEl = document.getElementById('timeoutProgress');
            
            // Cria um intervalo de 1 segundo para atualizar o timer na tela
            const countdown = setInterval(() => {
                timeLeft--; // Diminui 1 segundo
                
                // Calcula minutos e segundos restantes para formatar o texto
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                // Atualiza o texto (ex: 4:59) e a largura da barra de progresso
                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                progressEl.style.width = `${(timeLeft / (5 * 60)) * 100}%`;
                
                // Se o tempo visual acabar, força o logout e limpa o intervalo
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    this.forceLogout();
                }
            }, 1000);
            
            // Configura o evento de clique no botão "Continuar"
            document.getElementById('continueBtn').addEventListener('click', () => {
                clearInterval(countdown); // Para o contador
                this.resetTimer();        // Reseta o tempo de inatividade
                modal.hide();             // Esconde o modal
            });
            
            // Configura o evento de clique no botão "Sair"
            document.getElementById('logoutBtn').addEventListener('click', () => {
                clearInterval(countdown); // Para o contador
                this.forceLogout();       // Encerra a sessão imediatamente
            });
        }
    }
    
    forceLogout() {
        // Exibe um alerta nativo do navegador informando o usuário
        alert('Sua sessão expirou por inatividade. Você será redirecionado para a página de login.');
        
        // Faz uma requisição assíncrona (POST) para o backend destruir a sessão PHP
        fetch('../../controllers/AuthController.php?action=logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(() => {
            // Se o backend responder com sucesso:
            localStorage.clear();   // Limpa dados locais
            sessionStorage.clear(); // Limpa dados da sessão do navegador
            
            // Redireciona o usuário para a tela de login
            window.location.href = '../views/auth/login.php';
        })
        .catch(() => {
            // Mesmo se a requisição falhar (erro de rede), redireciona por segurança
            window.location.href = '../views/auth/login.php';
        });
    }
}

// Aguarda o DOM (estrutura da página) carregar completamente
document.addEventListener('DOMContentLoaded', () => {
    // Instancia a classe definindo 60 minutos totais e aviso nos últimos 5 minutos
    new SessionTimeout(60, 5); 
});