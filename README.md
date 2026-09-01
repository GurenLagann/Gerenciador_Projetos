# Gerenciador de Projetos

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-3-4E56A6?logo=livewire&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-database-003B57?logo=sqlite&logoColor=white)

Dashboard local, **single-user e sem autenticação**, para gerenciar projetos de software, ideias e anotações — com importação automática de repositórios via varredura do sistema de arquivos e busca semântica (RAG) sobre as anotações.

## Índice

- [Funcionalidades](#funcionalidades)
- [Stack](#stack)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Desenvolvimento](#desenvolvimento)
- [Testes e estilo de código](#testes-e-estilo-de-código)
- [Ambiente Docker](#ambiente-docker-produçãolocal-completo)
- [Varredura de projetos (ProjectScannerService)](#varredura-de-projetos-projectscannerservice)
- [Busca semântica (RAG)](#busca-semântica-rag)
- [Servidor MCP](#servidor-mcp)
- [Convenções de rotas](#convenções-de-rotas)
- [Mais detalhes](#mais-detalhes)

## Funcionalidades

- **Projects** — projetos de software criados manualmente ou importados automaticamente por varredura de repositórios git no host. Guarda stack tecnológica, metadados do git, progresso, marcos (milestones), débitos técnicos, anotações e tags.
- **Ideas** — quadro Kanban leve para conceitos de projeto, com conversão de Ideia → Projeto (`IdeaConversionService::convert()`).
- **Technical Debt** — checklist de débitos técnicos por projeto, com contagem de pendências exibida no dashboard.
- **Annotations** — notas em Markdown (via `league/commonmark`), polimórficas (associáveis a Projects ou Ideas), com opção de fixar (pin) no topo.
- **Tags** — polimórficas e compartilhadas entre Projects e Ideas.
- **Dashboard** — visão geral com estatísticas, gráficos (Chart.js) de status de projetos/ideias e débitos técnicos em aberto, marcos próximos e um quick-switcher (⌘K) para navegar direto a projetos/ideias.
- **Busca semântica (RAG)** — embeddings gerados via Ollama (`bge-m3`) e indexados no Qdrant, permitindo busca por similaridade sobre descrições e anotações.
- **Servidor MCP** — expõe o dashboard como ferramentas MCP para uso com Claude Code / Claude Desktop via STDIO.

## Stack

| Camada       | Tecnologia                                   |
|--------------|-----------------------------------------------|
| Backend      | Laravel 13 / PHP 8.3+                         |
| Frontend     | Livewire 3, Alpine.js, Tailwind CSS 4, Vite   |
| Banco        | SQLite                                        |
| Embeddings   | Ollama (`bge-m3`)                             |
| Vetores      | Qdrant                                        |
| Integração   | MCP server (`laravel/mcp`) via STDIO          |

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

## Testes e estilo de código

```bash
# Roda toda a suíte de testes
composer test

# Roda um teste específico
php artisan test --filter=TestName

# Code style (Laravel Pint)
./vendor/bin/pint

# Build de assets do frontend
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

A aplicação roda em `http://localhost:8082` (via nginx) por padrão.

> **Nota:** todos os comandos `artisan`/`composer` devem ser executados **dentro do container PHP** (`docker compose exec php ...`), não no host — o container roda como `www-data` e comandos executados fora dele podem quebrar as permissões de escrita do SQLite e dos logs.

## Varredura de projetos (ProjectScannerService)

Em Docker, o `/var/www` do host é montado como somente-leitura em `/var/www/host_projects`, permitindo que o scanner "enxergue" projetos irmãos no host. O diretório base é configurável via `SCAN_BASE_PATH` (padrão `/var/www/host_projects`).

- Apenas diretórios contendo uma pasta `.git` são importados.
- Projetos removidos manualmente (soft delete) nunca são recriados pelo scanner.
- O diretório `project-manager` é sempre ignorado.
- Um subdiretório `docker/` dentro do `basePath` também é varrido recursivamente.

## Busca semântica (RAG)

`EmbeddingIndexService` provê busca semântica sobre `Project.description`, `Idea.description`/`content`, `Annotation.content`, `Milestone.title`/`description` e `TechnicalDebt.title`, usada pela tool MCP `SearchNotesTool` (a geração de resposta é feita pelo próprio Claude — este serviço só faz a recuperação, sem chave de API de LLM).

- **Ollama** (container `ollama`, modelo `bge-m3` — multilíngue, adequado ao conteúdo em português) gera os embeddings via `POST /api/embed`.
- **Qdrant** (container `qdrant`, collection `project_manager_content`, exposto em `localhost:6333` para o dashboard de debug) armazena e busca os vetores.
- A indexação roda em background via jobs de fila (`App\Jobs\IndexSearchableContent` / `RemoveFromSearchIndex`), disparados por observers (`App\Observers\{Project,Idea,Annotation}Observer`) e processados pelo container `queue-worker`.
- `php artisan search:rebuild-index` faz o backfill de dados já existentes (os observers só cobrem escritas futuras).

## Servidor MCP

`app/Mcp/Servers/ProjectManagerServer.php` (tools em `app/Mcp/Tools/*.php`) expõe o dashboard como ferramentas MCP para Claude Code / Claude Desktop via transporte **STDIO** (sem autenticação, já que a aplicação não tem). Deleção é deliberadamente **não** exposta como tool MCP.

```bash
# Roda o servidor MCP manualmente sobre STDIO (para depuração)
docker compose exec -T php php artisan mcp:start project-manager
```

O servidor é registrado em escopo de usuário no Claude Code (não via `.mcp.json` do repositório), então fica disponível em qualquer sessão independentemente do diretório de trabalho aberto. Para re-registrar:

```bash
claude mcp add -s user project-manager -- docker compose --project-directory /var/www/Gerenciador_Projetos exec -T php php artisan mcp:start project-manager
```

## Convenções de rotas

- Projects usam `slug` como chave de rota (`getRouteKeyName()`), não `id`.
- Ações em sub-recursos usam `PATCH` (ex.: `/projects/{project}/status`, `/milestones/{milestone}/toggle`).
- Todas as rotas ficam em `routes/web.php` — não há rotas de API.

## Mais detalhes

Consulte [`CLAUDE.md`](./CLAUDE.md) para uma descrição arquitetural mais detalhada (services, observers, jobs, Livewire components e o servidor MCP).
