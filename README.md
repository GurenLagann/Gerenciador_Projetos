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

- **Projects** — projetos de software criados manualmente ou importados automaticamente por varredura de repositórios git no host. Guarda stack tecnológica, metadados do git, progresso, marcos (milestones), débitos técnicos, anotações e tags. A página do projeto mostra só um resumo de uma frase da descrição — o texto longo vive nas anotações.
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

Em Docker, o `/var/www` do host é montado como somente-leitura em `/var/www/host_projects`, permitindo que o scanner "enxergue" projetos irmãos no host. O diretório base é configurável via `SCAN_BASE_PATH` (padrão `/var/www/host_projects`) e sempre lido por `config('services.scanner.base_path')` — nunca por `env()` direto, que devolveria `null` sob `config:cache`.

### O que vira projeto

- Diretório com pasta `.git` própria.
- Dentro de um **diretório-contêiner** (`docker/`, `microservices/` — ver `App\Support\ProjectContainerDirectories`), basta um arquivo marcador (`composer.json`, `artisan`, `spark`, `package.json`, `pyproject.toml`, `requirements.txt`): os serviços de um monorepo não têm `.git` próprio, quem tem é o pai. O contêiner em si nunca é importado, mesmo sendo um repositório.
- O scanner desce **um** nível dentro do contêiner, e o projeto fica com o path `contêiner/serviço`.
- O diretório `project-manager` é sempre ignorado.
- Projetos removidos manualmente (soft delete) nunca são recriados.

Para um serviço de monorepo, o git é escopado na subpasta (`App\Support\GitRepositoryRoot` acha o repositório subindo até o `.git`): commits, autores e estado sujo são os daquele serviço, não os do repositório inteiro. O branch não é escopado — é literalmente o branch em que aquele código está.

### Curadoria ganha do scanner

Num projeto que já existe, a varredura **nunca sobrescreve nem apaga** o que está salvo — boa parte vem do MCP, escrita à mão ou a partir de notas, e a detecção automática é mais pobre que isso. A regra:

| campo | comportamento |
|---|---|
| `tech_stack` | união. Chip detectado que o salvo já cobre é descartado (`PHP` ao lado de `PHP 8.2`); chip detectado mais específico **refina o salvo no lugar** (`PHP` vira `PHP 8.2`) |
| `runtime_version`, `framework_version`, `database_engine` | preenche se estiver vazio, nunca sobrescreve |
| `detected_files`, `git_info`, `size_bytes`, `last_scanned_at` | sempre atualizados — são fato puro do filesystem |

### Detecção de stack

Os chips saem de uma **tabela de reconhecimento** (`COMPOSER_PACKAGES`, `PYTHON_PACKAGES`, `NPM_PACKAGES` em `ProjectScannerService`) aplicada aos manifestos: `composer.json` (require **e** require-dev), `requirements.txt`/`pyproject.toml`, `package.json`, `.tool-versions`, `Dockerfile` e `docker-compose.yml`.

- **Só entra o que está na tabela.** Dependência desconhecida é ignorada de propósito — biblioteca de apoio (`orjson`, `httpx`, `nanoid`) não é identidade do projeto e não merece espaço no card. Tecnologia nova exige uma linha na tabela.
- Versão só aparece quando a fonte é confiável: lock file > `.tool-versions` > constraint declarada. Uma constraint com range (`^7.4 || ^8.0`) não diz o que roda e não vira versão nenhuma.
- Um `package.json` em projeto PHP é tratado como pipeline de assets: rende chips de ferramenta (Vite, Tailwind), mas não rotula o projeto como Node.js.
- Teto de 12 chips por projeto.

Nem tudo é detectável: `Kafka` via HTTP, `SAP B1 Service Layer`, `WebSocket` e afins não estão declarados em manifesto nenhum e existem só porque alguém escreveu — daí a regra de curadoria acima.

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
