# Robustez do RAG e do pipeline de indexação — design

Data: 2026-09-05
Projeto: Gerenciador Projetos (dashboard, id 12)
Débitos técnicos de origem: #51, #52, #53

## Contexto

Uma nova rodada de avaliação do RAG (anotação #45) comparou o índice real no
Qdrant (`project_manager_content`) contra as contagens do banco e achou um
gap grande entre o que o código promete e o que existe de fato:

| type            | no banco | indexado | faltando |
|-----------------|---------:|---------:|---------:|
| project         |        6 |        6 |        0 |
| idea            |        2 |        0 |        2 |
| annotation      |       22 |       21 |        1 |
| milestone       |       44 |        0 |       44 |
| technical_debt  |       47 |        2 |       45 |

Três causas raiz distintas foram confirmadas:

1. **Backfill nunca aplicado ao dado existente** (#51) — o marco #55
   ("Ampliar cobertura do RAG") foi implementado e testado, mas o backfill
   real (`search:rebuild-index`) nunca rodou de fato contra o dataset
   completo. Só entrou no índice o que foi salvo/ressalvo depois do wiring
   do Observer, mais 2 registros de teste manual.
2. **Payload de job incompatível entre deploys** — um job ficou preso em
   `failed_jobs` (`Error: Class "annotation" not found`) porque foi
   enfileirado com a assinatura antiga do job (string de tipo) antes da
   refatoração do débito #26, e processado depois que o worker já rodava o
   código novo (que espera um class-string). Reiniciar o worker não
   previne esse caso — o problema é a forma do payload já serializado.
3. **Órfãos permanecem pesquisáveis para sempre** (#52) — soft-delete de
   `Project`/`Idea` remove o próprio ponto do modelo deletado, mas não
   cascade para milestones/débitos/anotações filhos. Confirmado ao vivo:
   o projeto #10 (removido do disco) ainda tem uma anotação pesquisável no
   índice.
4. **Risco latente de crash** (#53) — `Annotation::searchableContent()`
   acessa `$this->annotatable->name` sem null-safety; como `annotatable`
   é um `MorphTo` sem `withTrashed()`, resolve para `null` quando o pai
   está soft-deleted. Zero ocorrências hoje (nenhuma anotação está sem
   título), mas é um crash garantido no primeiro caso futuro que combinar
   "sem título" + "pai soft-deletado".

Duas lições de infra já documentadas no próprio projeto (anotações #40 e
#41) continuam sem correção estrutural, e a causa raiz #2 acima é uma
recorrência do mesmo padrão:

- `ext-intl` está presente no binário PHP usado pelo PHPUnit (CLI), mas
  ausente no `php-fpm` que serve a aplicação de verdade — confirmado
  agora (`docker compose exec php php -m | grep intl` não retorna nada).
  Isso já quebrou em produção uma vez (`Number::fileSize()`, débito do
  marco #54) e pode quebrar de novo com qualquer helper futuro que
  dependa de uma extensão opcional.
- `queue:work` roda sem `--max-jobs`/`--max-time`, então o processo de
  longa duração nunca recarrega código novo sozinho — depende de alguém
  lembrar de rodar `docker compose restart queue-worker` depois de
  qualquer mudança em Job/Observer/Service usado pela fila.
- Nada monitora `failed_jobs`. O job travado da causa raiz #2 só foi
  descoberto porque esta avaliação foi olhar a tabela manualmente.

## Objetivo

Corrigir os 3 débitos do RAG de forma que o problema não volte a
acontecer silenciosamente, e fechar as duas lições de infra já pagas
duas vezes por este projeto (extensões PHP divergentes entre CLI e
FPM; worker de fila que não recarrega código).

Fora de escopo: qualquer mudança de modelo de embedding, migração para
Horizon ou outra solução de monitoramento de filas, e novas
funcionalidades de produto no Gerenciador de Projetos — esta rodada é
sobre robustez do que já existe, não sobre crescer o escopo.

## Componentes

### 1. Comando de reconciliação do índice (`search:reconcile-index`)

Mecanismo central que resolve #51 e #52 de forma permanente, não como
patch único.

**Algoritmo**, por tipo `Searchable` (`project`, `idea`, `annotation`,
`milestone`, `technical_debt`):

1. Monta o conjunto "deveria estar indexado": todos os IDs do tipo no
   banco, com uma regra extra para os três tipos que pertencem a um
   `Project`/`Idea` — `annotation` (pai polimórfico, Project ou Idea),
   `milestone` e `technical_debt` (pai sempre Project) — só entram se o
   pai resolver (não soft-deletado). `project` e `idea` não têm pai, então
   não têm essa dependência. Sem essa correção, um milestone ou débito
   técnico de um projeto soft-deletado (caso real: o projeto #10 tem 3
   milestones) seria tratado como "deveria estar indexado" e recriaria o
   mesmo bug do #52 por outra porta.
2. Faz `scroll` no Qdrant filtrando por `payload.type`, pegando só
   `source_id` (sem os vetores) — monta o conjunto "está indexado".
3. Diff de conjuntos:
   - **Faltando** (banco, não Qdrant) → `IndexSearchableContent::dispatch()`
     para cada um. Resolve #51 de forma permanente: mesmo que uma rodada
     futura de jobs falhe silenciosamente por qualquer motivo, a próxima
     reconciliação detecta o gap e redespacha do zero com o payload
     atual — isso também neutraliza a causa raiz #2 (job com payload
     obsoleto): o job antigo pode falhar, mas o dado nunca fica
     permanentemente ausente do índice.
   - **Sobrando** (Qdrant, não no "deveria estar", incluindo órfãos com
     pai soft-deletado) → `RemoveFromSearchIndex::dispatch()` para cada
     um. Resolve #52 de forma permanente.
4. Comando idempotente e seguro para rodar a qualquer momento — não
   assume nenhum estado prévio.
5. Agendado via Laravel Scheduler (`routes/console.php`), frequência
   diária como ponto de partida (dataset pequeno, custo de embedding
   desprezível nesse volume). Ajustável se o volume crescer.
6. `search:rebuild-index` continua existindo, sem mudanças — serve para
   o caso de "recriar tudo do zero" (ex.: troca de modelo de embedding).
   A reconciliação cobre o drift incremental do dia a dia; não substitui
   um rebuild completo.

**Testes:** feature test com `Http::fake()` para Qdrant/Ollama e
`Queue::fake()`, cobrindo: registro faltando dispara index job; ponto
órfão (registro apagado) dispara removal job; anotação, milestone e
débito técnico com pai (Project/Idea) soft-deletado são tratados como
"não deveria estar indexado" mesmo que o registro do próprio filho
ainda exista.

### 2. Guarda null-safe em `Annotation::searchableContent()` (débito #53)

Troca `$this->annotatable->name ?? $this->annotatable->title ?? 'Annotation'`
por `$this->annotatable?->name ?? $this->annotatable?->title ?? 'Annotation'`.

Isso não é a correção definitiva do órfão — é uma rede de segurança para
a janela entre "pai foi soft-deletado" e "a próxima reconciliação
rodou". Quem efetivamente resolve o órfão é o componente 1. Sem esta
guarda, essa janela ainda causaria um `Error` fatal no job de indexação
caso a anotação sem título seja salva/ressalva nesse intervalo.

**Teste:** anotação sem título com pai soft-deletado não lança exceção
ao chamar `searchableContent()`; título cai no fallback genérico
`'Annotation'`.

### 3. Auto-restart do queue worker

`docker-compose.yml`, serviço `queue-worker`:

```
command: php artisan queue:work --sleep=3 --tries=3 --max-jobs=100
```

Ao processar 100 jobs o processo termina sozinho; `restart:
unless-stopped` já existente faz o Docker subir um processo novo, que
carrega o código PHP atual do bind mount. Fecha a lição do #41 de forma
estrutural: o pior caso deixa de ser "código desatualizado para
sempre até alguém lembrar" e passa a ser "código desatualizado por até
100 jobs". O valor 100 é um ponto de partida dado o volume atual de
indexação; revisar se o container passar a reiniciar rápido demais ou
devagar demais na prática.

### 4. `ext-intl` no `php-fpm`

Adicionar a instalação da extensão `intl` no `Dockerfile` do serviço
`php` (mesma imagem serve CLI e `php-fpm`). Fecha a classe inteira de
bug do débito do marco #54 (`Number::fileSize()` verde no PHPUnit,
500 em produção) para qualquer helper futuro que dependa de extensões
PHP opcionais.

**Verificação:** `docker compose exec php php -m | grep intl` deve
retornar `intl` depois do rebuild da imagem, e uma chamada real via
`php-fpm` (não só CLI) a um endpoint que usa `Number::fileSize()` ou
equivalente deve responder 200.

### 5. Débito técnico automático em falha de job

Listener no evento `Queue::failing()`, registrado no
`AppServiceProvider` ao lado do `SearchableObserver`. Quando
`IndexSearchableContent` ou `RemoveFromSearchIndex` esgota as 3
tentativas, cria automaticamente um `TechnicalDebt` no projeto
`gerenciador-projetos` com o tipo/ID do job e a mensagem da exceção.

Mesmo padrão que o marco #53 já estabeleceu para sinal automático de
débito técnico (lá via grep de TODO/FIXME; aqui via falha real de
job). Substitui "só descobri porque fui olhar `failed_jobs`
manualmente" — que foi literalmente como a causa raiz #2 foi
encontrada nesta avaliação — por um item visível no próprio board.

**Teste:** job falho após esgotar tentativas cria exatamente um
`TechnicalDebt`; segunda falha do mesmo job não duplica o débito (ideia:
chave por classe do job + ID, evitando duplicar se o problema persistir
entre execuções da fila).

## Ordem de implementação sugerida

1. Guarda null-safe na `Annotation` (item 2) — menor risco, isolado,
   sem dependências.
2. `ext-intl` no Dockerfile (item 4) — isolado, requer rebuild da
   imagem `php`.
3. `--max-jobs` no `queue-worker` (item 3) — isolado, mudança de uma
   linha no `docker-compose.yml`.
4. Comando de reconciliação (item 1) — a peça mais substancial;
   depende do item 2 já estar em produção para não reintroduzir o
   crash durante uma reconciliação real.
5. Débito técnico automático em falha de job (item 5) — depende do
   comando de reconciliação existir, para não gerar ruído duplicado
   quando a própria reconciliação começar a corrigir os gaps
   históricos (rodar a reconciliação uma vez manualmente antes de
   ligar o listener, para não abrir dezenas de débitos automáticos
   referentes ao estado já conhecido).

## Fora de escopo (por quê)

- **Monitoramento externo de filas (Horizon, alertas por email/Slack):**
  ferramenta de uso único, local — o board do próprio Gerenciador de
  Projetos já é o canal de visibilidade adotado pelo projeto (ver
  débito automático, item 5). Adicionar um canal externo seria
  acoplamento sem necessidade real hoje.
- **Reindexação em tempo real via evento de soft-delete:** cascatear a
  remoção no momento exato do `deleted()` do pai (em vez de esperar a
  reconciliação agendada) foi considerado e descartado — exigiria
  carregar todos os filhos (`annotations()`, `milestones()`,
  `technicalDebts()`) dentro do observer do pai, duplicando a lógica de
  "quem depende de quem" que a reconciliação já centraliza de forma
  type-agnostic. A latência de até 1 dia (frequência do schedule) é
  aceitável para este volume de dados.
