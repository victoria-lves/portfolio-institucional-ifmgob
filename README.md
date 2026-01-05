# 📘 Portfólio Institucional IFMG Campus Ouro Branco

> **Resumo:** Plataforma web para gerenciamento e exibição de projetos acadêmicos e perfil de docentes. Desenvolvido com foco em escalabilidade, segurança e experiência do usuário (UX), servindo como ponto central de divulgação da produção técnica da instituição.

---

## 📑 Índice

1. [Visão Geral e Objetivos](https://www.google.com/search?q=%23-vis%C3%A3o-geral-e-objetivos)
2. [Público-Alvo](https://www.google.com/search?q=%23-p%C3%BAblico-alvo)
3. [Stack Tecnológico](https://www.google.com/search?q=%23-stack-tecnol%C3%B3gico)
4. [Arquitetura de Software](https://www.google.com/search?q=%23-arquitetura-de-software)
5. [Banco de Dados e Mídia](https://www.google.com/search?q=%23-banco-de-dados-e-m%C3%ADdia)
6. [Segurança e Conformidade](https://www.google.com/search?q=%23-seguran%C3%A7a-e-conformidade)
7. [Design e UX/UI](https://www.google.com/search?q=%23-design-e-uxui)
8. [Performance e SEO](https://www.google.com/search?q=%23-performance-e-seo)
9. [Roadmap e Melhorias Futuras](https://www.google.com/search?q=%23-roadmap-e-melhorias-futuras)

---

## 🎯 Visão Geral e Objetivos

Este projeto resolve o problema da **dispersão de informações acadêmicas**, centralizando portfólios em um ambiente institucional padronizado.

* **Motivação:** Criar uma ferramenta que não apenas armazene dados, mas valorize a produção intelectual através de um design limpo e acessível.
* **Diferencial:** Ao contrário de soluções genéricas, este sistema oferece controle total sobre a taxonomia dos dados e níveis de acesso (RBAC) personalizados para a realidade da instituição.

---

## 👥 Público-Alvo

O sistema foi mapeado para atender três perfis distintos:

1. **Visitantes (Parceiros/Comunidade):** Busca rápida por projetos e competências.
2. **Docentes/Alunos (Autores):** Interface intuitiva para cadastro de portfólio sem necessidade de conhecimento técnico.
3. **Administradores:** Ferramentas de gestão, moderação e auditoria.

---

## 🛠 Stack Tecnológico

A escolha das ferramentas priorizou a robustez, a facilidade de manutenção e a compatibilidade com a infraestrutura legada de servidores comuns (Apache/Nginx).

* **Back-end:** PHP 8+ (Foco em Orientação a Objetos e tipagem forte).
* **Banco de Dados:** MySQL (Motor InnoDB para suporte a transações ACID).
* **Front-end:** HTML5, CSS3, JavaScript (ES6+) e Bootstrap 5 (Customizado para layout responsivo).

---

## 🏗 Arquitetura de Software

O projeto segue padrões de design para garantir **baixa acoplagem e alta coesão**:

* **Padrão MVC (Model-View-Controller):** Separação clara entre a lógica de negócios, a camada de dados e a interface do usuário.
* **MVC com DAO (Data Access Object):** A lógica de acesso a dados e regras de negócio está encapsulada nos Models. Isso centraliza as consultas SQL, evitando código duplicado e facilitando a manutenção, mantendo os Controllers leves ("Skinny Controllers").
* **API-Ready:** O back-end foi estruturado para devolver dados estruturados (JSON) quando necessário, facilitando a criação futura de um aplicativo móvel.
* **Tratamento de Erros:** Implementação de `try/catch` global. Erros críticos geram logs no servidor (para auditoria), mas exibem mensagens amigáveis ao usuário final, evitando *stack traces* expostos (Security by Obscurity).

---

## 🗄 Banco de Dados e Mídia

### Modelagem de Dados

* **Normalização:** O banco está normalizado até a 3ª Forma Normal (3FN) para evitar redundância.
* **Integridade Referencial:** Uso estrito de Foreign Keys com restrições (`ON DELETE CASCADE` ou `RESTRICT`) para evitar registros órfãos.
* **Transações (ACID):** Operações complexas (ex: cadastro de projeto + vínculo de tags) são encapsuladas em transações (`START TRANSACTION`, `COMMIT`, `ROLLBACK`) para garantir consistência.

### Gestão de Arquivos (Uploads)

* **Armazenamento:** Imagens **não** são salvas como BLOB no banco. Elas são armazenadas no sistema de arquivos do servidor, e apenas o caminho relativo (path) é salvo no banco. Isso garante leveza no backup do banco e melhor performance de leitura.
* **Processamento:** Implementação de script (GD Library) para redimensionamento e compressão de imagens no upload, evitando arquivos 4K desnecessários que consomem banda.

---

## 🔒 Segurança e Conformidade

Seguindo as diretrizes da **OWASP Top 10** e **LGPD**:

1. **SQL Injection:** 100% das consultas utilizam **Prepared Statements** (PDO).
2. **XSS (Cross-Site Scripting):** Toda saída de dados (output) passa por funções de sanitização (`htmlspecialchars`) para impedir injeção de scripts.
3. **CSRF:** Tokens de validação em formulários de estado crítico.
4. **Autenticação:** Senhas armazenadas com hash **Bcrypt** (ou Argon2). Gerenciamento de sessão com regeneração de ID no login para evitar *Session Hijacking*.
5. **Upload Seguro:** Verificação rigorosa de MIME Types (não apenas extensão) para impedir upload de scripts maliciosos (ex: `.php` disfarçado de `.jpg`).
6. **LGPD:** Funcionalidades preparadas para "Direito ao Esquecimento" e logs de acesso transparentes.

---

## 🎨 Design e UX/UI

* **Abordagem Desktop-First:** O desenvolvimento priorizou a experiência em telas maiores (Desktop), considerando que a visualização detalhada de portfólios e a gestão administrativa ocorrem predominantemente em computadores no ambiente institucional.
* **Responsividade (Graceful Degradation):** A adaptação para dispositivos móveis foi realizada através de Media Queries (focadas em `max-width`), garantindo que o layout se ajuste e permaneça funcional em telas menores, sem perder a riqueza visual da versão principal.
* **Arquitetura da Informação:** Navegação planejada para regra dos "3 cliques" (usuário chega ao conteúdo desejado em no máximo 3 interações).
* **Acessibilidade (WCAG):** Uso de tags semânticas, alto contraste nas cores e atributos `aria-label` e `alt` em imagens para leitores de tela.

---

## 🚀 Performance e SEO

* **Indexação:** Uso de URLs amigáveis (mod_rewrite) e meta tags dinâmicas para melhor ranqueamento no Google.
* **Otimização de Consultas:** Índices criados em colunas de busca frequente (além das Primary Keys) para garantir velocidade mesmo com milhares de registros.
* **Assets:** Minificação de CSS/JS (em ambiente de produção) para reduzir o tempo de carregamento.

---


## 🔮 Roadmap e Melhorias Futuras (Dívida Técnica)

Embora funcional, o projeto prevê evoluções contínuas:

* [ ] **Soft Delete:** Implementar sistema de "lixeira" (marcar como deletado ao invés de remover fisicamente) para maior segurança de dados.
* [ ] **API RESTful Completa:** Desacoplar totalmente o front-end usando um framework JS (React/Vue).
* [ ] **Cache:** Implementar Redis ou Memcached para consultas pesadas.
* [ ] **Testes Automatizados:** Ampliar a cobertura de testes unitários (PHPUnit) e testes E2E.

