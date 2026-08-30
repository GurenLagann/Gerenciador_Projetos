# Gerenciador de Projetos

Dashboard local, single-user e sem autenticação, para gerenciar projetos de software, ideias e anotações — com importação automática de repositórios via varredura do sistema de arquivos e busca semântica (RAG) sobre as anotações.

**Stack**: Laravel 13 / PHP 8.3+, Livewire 3, Alpine.js, Tailwind CSS 4, Vite, SQLite.

## Funcionalidades

- **Projects** — projetos de software criados manualmente ou importados automaticamente por varredura de repositórios git no host. Guarda stack tecnológica, metadados do git, progresso, marcos (milestones), débitos técnicos, anotações e tags.
- **Ideas** — quadro Kanban leve para conceitos de projeto, com conversão de Ideia → Projeto (`IdeaConversionService::convert()`).
- **Technical Debt** — checklist de débitos técnicos por projeto (`TechnicalDebt` / `TechnicalDebtController`), com contagem de pendências exibida no dashboard.
- **Annotations** — notas em Markdown (via `league/commonmark`), polimórficas (associáveis a Projects ou Ideas), com opção de fixar (pin) no topo.
- **Tags** — polimórficas e compartilhadas entre Projects e Ideas.
- **Dashboard** — visão geral com estatísticas, gráficos (Chart.js, via `resources/js/dashboard.js`) de status de projetos/ideias e débitos técnicos em aberto, marcos próximos e um quick-switcher (⌘K) para navegar direto a projetos/ideias.
- **Busca semântica (RAG)** — embeddings gerados via Ollama (`bge-m3`) e indexados no Qdrant, permitindo busca por similaridade sobre descrições e anotações. A indexação roda em background via jobs de fila (`App\Jobs\IndexSearchableContent` / `RemoveFromSearchIndex`), disparados por observers (`App\Observers\{Project,Idea,Annotation}Observer`) nos models.
- **Servidor MCP** — expõe o dashboard como ferramentas MCP (`app/Mcp/Servers/ProjectManagerServer.php`, tools em `app/Mcp/Tools/*.php`) para uso com Claude Code / Claude Desktop via STDIO.

## Requisitos

- PHP 8.3+
- Composer
- Node.js / npm
- Docker e Docker Compose (para o ambiente completo, incluindo Ollama e Qdrant)

## Instalação

```bash
# Configuração inicial (instala dependências, cria .env, roda migrations, builda assets)
composer setup
```

## Desenvolvimento

```bash
# Sobe servidor, worker de fila, logs (Pail) e Vite em paralelo
composer dev
```

## Testes

```bash
# Roda toda a suíte de testes
composer test

# Roda um teste específico
php artisan test --filter=TestName
```

## Estilo de código

```bash
./vendor/bin/pint
```

## Build de assets

```bash
npm run build
```

## Ambiente Docker (produção/local completo)

O `docker-compose.yml` sobe os serviços `nginx`, `php`, `queue-worker`, `ollama` e `qdrant`.

```bash
docker-compose up -d

# Baixa o modelo de embedding local (uma vez, após o primeiro up)
docker compose exec ollama ollama pull bge-m3

# Cria/verifica a collection do Qdrant (idempotente)
docker compose exec php php artisan search:init-collection

# Enfileira jobs de indexação para projetos/ideias/anotações já existentes
docker compose exec php php artisan search:rebuild-index
```

A app roda em `http://localhost:8082` (via nginx) por padrão.

### Varredura de projetos (ProjectScannerService)

Em Docker, o `/var/www` do host é montado como somente-leitura em `/var/www/host_projects`, permitindo que o scanner "enxergue" projetos irmãos no host. O diretório base é configurável via `SCAN_BASE_PATH` (padrão `/var/www/host_projects`). Apenas diretórios contendo uma pasta `.git` são importados; projetos removidos manualmente (soft delete) nunca são recriados pelo scanner.

### Servidor MCP

```bash
# Roda o servidor MCP manualmente sobre STDIO (para depuração)
docker compose exec -T php php artisan mcp:start project-manager
```

O servidor é registrado em escopo de usuário no Claude Code (não via `.mcp.json` do repositório), então fica disponível em qualquer sessão independentemente do diretório de trabalho. Para re-registrar:

```bash
claude mcp add -s user project-manager -- docker compose --project-directory /var/www/Gerenciador_Projetos exec -T php php artisan mcp:start project-manager
```

## Convenções de rotas

- Projects usam `slug` como chave de rota (`getRouteKeyName()`), não `id`.
- Ações em sub-recursos usam `PATCH` (ex.: `/projects/{project}/status`, `/milestones/{milestone}/toggle`).
- Todas as rotas ficam em `routes/web.php` — não há rotas de API.

## Mais detalhes

Consulte `CLAUDE.md` para uma descrição arquitetural mais detalhada (services, observers, jobs, Livewire components e o servidor MCP).
