# Clube dos Detetives

Portal educacional gamificado para o Ensino Fundamental II. O aluno escolhe entre duas trilhas: **Detetive dos Dados**, com estatística, e **Monte seu Robô**, com estrutura da oração.

## Arquitetura

- **Frontend:** PHP renderizado no servidor, HTML sem dependências de framework e CSS responsivo mobile-first.
- **Backend:** PHP com sessões, autenticação, validação de formulários e regras de pontuação.
- **Banco:** MySQL/MariaDB definido em `database/database.sql`, com usuários, partidas, XP, níveis e conquistas.
- **Segurança:** senhas com `password_hash`, consultas preparadas com `mysqli` e saída textual com `htmlspecialchars`.

## Funcionalidades

- Cadastro, login, logout e perfil do aluno.
- Dashboard com nível, XP, partidas, acertos, erros e medalhas.
- Ranking por XP.
- Jogo de Matemática: Detetive dos Dados, com média, moda, mediana, tabelas e gráficos.
- Jogo de Língua Portuguesa: Monte seu Robô.
- Cada partida valida respostas, calcula pontos, registra o histórico e atualiza XP/conquistas.
- Feedback pós-partida com explicação do conceito trabalhado.
- Recompensa de memória após uma nova conquista, com fila individual, progresso salvo na sessão e conclusão validada no servidor.
- Leitura por voz em português brasileiro nas páginas principais, com controles para pausar, retomar e interromper.

## Roteiro de demonstração

1. Criar uma conta e entrar no dashboard.
2. Abrir a central em **Jogos** e mostrar as duas trilhas.
3. Resolver uma pista de **Detetive dos Dados** e conferir pontos no dashboard.
4. Resolver **Monte seu Robô** montando sujeito + verbo + complemento.
5. Conquistar uma medalha para abrir o jogo da memória; atualize a página durante a partida e confirme que o tabuleiro continua do ponto salvo.
6. Usar **Ler página** para testar a leitura, a pausa, a retomada e a interrupção.
7. Abrir Perfil/Ranking para demonstrar a persistência do progresso.

## Papéis sugeridos para a equipe

- UI/UX: identidade visual, acessibilidade e responsividade.
- Frontend: telas, formulários e estados de feedback.
- Backend/Database: autenticação, regras, SQL e persistência.
- QA: testes de login, pontuação, caminhos e diferentes tamanhos de tela.
- Pedagógico: revisão das questões, pistas e explicações.

## Execução local

1. Coloque a pasta em um servidor PHP local, como XAMPP ou Laragon.
2. Crie o banco importando `database/database.sql`.
3. Configure credenciais em `includes/db.php`.
4. Acesse `http://localhost/detetive-dos-dados/`.

## Publicação via GitHub Actions

O workflow de deploy lê as credenciais do banco pelos secrets do repositório e gera `includes/db.production.php` somente durante a publicação. Esse arquivo não deve ser enviado ao Git.

Configure em **Settings → Secrets and variables → Actions**:

- Banco: `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS`.
- Opcional: `DB_PORT` (padrão `3306`).
- FTP: `FTP_SERVER`, `FTP_USERNAME` e `FTP_PASSWORD`.
