# Coletor RSS independente — Giro73

Python 3.10+ em Ubuntu 22.04/24.04 ou posterior compatível. Não depende do bot Telegram nem instala plugins no WordPress. O padrão é rascunho; fontes explicitamente configuradas para modo automático podem publicar pelo endpoint existente. Política e implantação: [rodízio e publicação seletiva](../34-rodizio-publicacao-seletiva.md).

## Arquivos

- `coletor_rss.py`: programa completo.
- `configurar_fontes_rss.py`: inclusão em lote das fontes selecionadas e descoberta de RSS de Portal TX/Vem Vê TX Bahia, executada na VPS.
- `configurar_publicacao_rss.py`: aplica a seleção aprovada de fontes automáticas, sem buscar feeds nem criar matérias.
- `expandir-fontes-oficiais.sh`: instala a expansão de fontes municipais e verifica RSS, com backup e sem gerar textos ou criar posts.
- `requirements-rss.txt`: requests, feedparser, BeautifulSoup, Pillow e python-dotenv; SQLite já faz parte do Python.
- `rss_fontes.example.json`: configuração por fonte, sem inventar feeds autorizados.
- `giro73-rss.env.example`: exemplo sem credenciais reais.
- `run-coletor-rss.sh`: wrapper com caminhos absolutos e ambiente.
- `atualizar-vps.sh`: atualização de uma instalação existente, com backup e bloqueio contra coleta simultânea.
- `giro73-rss.cron`: coleta de hora em hora, com limite externo de 50 minutos.
- `giro73-rss.logrotate`: rotação dos logs.
- `tests/test_coletor_rss.py`: 27 testes automatizados com APIs simuladas.
- `tests/test_configurar_fontes_rss.py`: 12 testes de descoberta e atualização segura da configuração.
- `tests/test_imagens_rss.py`: 12 testes de seleção de fotos, validação dos bytes e figura/ALT.
- `tests/test_openrouter.py`: 12 testes do provedor, JSON estruturado, `.env`, retentativas e confirmação da capa.
- `tests/test_rodizio_publicacao.py`: 24 testes de rodízio persistente, publicação seletiva, revisão editorial e migração.

## Revisão editorial antes da publicação automática

A geração recebe a data de referência UTC e instruções para distinguir anúncio, execução e resultado, usar atribuição e evitar aspas não fornecidas, linguagem promocional e detalhes íntimos de crianças. Não gera contexto local sem evidência nem exige 600 palavras.

Mesmo para fontes autorizadas em modo `auto`, o coletor mantém em **rascunho** referências genéricas, datas completas no caminho da URL com mais de sete dias (ou futuras além de um dia), datas inválidas, sinais de acusações sensíveis, algumas expressões promocionais e imagens verificadas com algum lado menor que 400 px. O motivo aparece no log. A imagem pequena pode continuar anexada ao rascunho: não foi criada uma nova trava de upload no PHP. São sinais de revisão, não um verificador universal de fatos ou direitos; datas fora do padrão, alegações implícitas e fotos inadequadas maiores ainda podem passar. As janelas normais de frescor do feed continuam valendo.

Essas regras só entram na VPS depois de instalar esta versão. Não alteram payloads já preparados, posts enviados, o bot Telegram ou outros publicadores. Veja a [revisão editorial e o procedimento de publicação](../39-revisao-editorial-completa.md).

## Modelo OpenRouter

O provedor oficial é **OpenRouter**, via HTTP com `requests` em `https://openrouter.ai/api/v1`. A chave é `OPENROUTER_API_KEY`; o modelo é `AI_MODEL`, com padrão **`openai/gpt-4o-mini`**. Não há SDK de IA adicional nem chamada direta a outro provedor.

Antes da geração, o cliente valida a chave em `/key` e o ID no catálogo `/models`. O modelo precisa aceitar `response_format` com JSON Schema estrito; o pedido usa `/chat/completions` e `provider.require_parameters: true`. Não troca silenciosamente de modelo. `--list-models` lista o catálogo; a disponibilidade efetiva, permissão e saldo também são verificados na chamada de geração. `--check-ai` valida chave e catálogo sem gerar texto nem chamar WordPress.

O arquivo oficial é `/opt/giro73-rss/.env`, carregado pelo Python com `python-dotenv`, inclusive sob `sudo -u giro73-rss` e cron. Variáveis já exportadas têm prioridade. Opcionalmente `G73_RSS_ENV` define outro caminho. Para compatibilidade, se faltar `G73_TOKEN`, o arquivo antigo `/etc/giro73-rss.env` pode completar somente `G73_TOKEN`, `G73_PUBLISH_URL` e `RSS_MAX_RUN_SECONDS`; sua configuração de IA é ignorada. O launcher não executa o conteúdo do `.env` como shell.

## Fluxo e garantias locais

1. Ler apenas fontes com `authorized: true`, via HTTPS. A autorização é declarada pelo administrador; o script não verifica licenças de terceiros.
2. Aceitar RSS/Atom de até 2 MB, sem DTD/entidades XML; até 50 entradas por fonte em cada coleta.
3. Registrar GUID e URL, removendo parâmetros de rastreamento conhecidos. Fontes distintas com a mesma URL compartilham o registro.
4. Preparar uma nota factual com OpenRouter; ignorar itens insuficientes ou recusados pelo modelo.
5. Montar o payload localmente com os três campos do Rank Math em `meta`. O padrão é `status: draft`; a política local pode liberar `publish` nas fontes automáticas aptas. O modelo não controla categoria, status, credenciais ou destino.
6. Persistir o payload em SQLite **antes** do POST e enviar `Idempotency-Key: rss-[hash]`.
7. Registrar como `sent` somente após receber ID válido e status compatível do WordPress. Pedidos de publicação podem ser preservados como rascunho pelo servidor quando a capa falhar; o status efetivo fica em `wp_status`. Depois da confirmação, textos completos locais são descartados; identidade, URL e ID permanecem no histórico.

Retentativas de rede/503 podem repetir a chamada HTTP, mas usam o mesmo texto e chave; o endpoint WordPress existente é quem impede que isso crie outro post após uma resposta perdida. Um 422 confirmado de imagem permite persistir uma variante `draft` sem foto e reenviar com a mesma chave. Não existe promessa de transporte HTTP exatamente uma vez. O bloqueio de processo impede dois cron jobs locais simultâneos.

O banco `vistos_rss.sqlite3` guarda fila e histórico de itens. Estados: `queued`, `prepared`, `sent`, `skipped` e `blocked`. Itens bloqueados exigem diagnóstico; não são apagados nem repetidos cegamente. Preserve e faça backup desse banco — mudar/apagar o arquivo remove a memória local do coletor. A deduplicação por identidade não reconhece automaticamente a mesma pauta publicada com URLs/GUIDs diferentes nem compartilha o histórico do bot Telegram.

## Proteção contra duplicatas no WordPress

O payload informa `source_url` a partir da URL real do feed, nunca de uma URL inventada pelo modelo. O Snippet 12 normaliza essa origem e registra `_g73_source_url`. Também reconhece a atribuição final de bots antigos. Homepages, índices e categorias genéricas não são tratados como uma matéria única.

- `409 g73_duplicate_article`: a origem já foi recebida. O coletor registra `skipped` e a referência da matéria existente, sem novo post nem repetição do POST.
- `409 g73_duplicate_review`: título e abertura são semelhantes a uma pauta recente da mesma editoria. O coletor registra `blocked`, preserva o material para comparação editorial e não cria uma segunda página. Uma atualização real pode ser consolidada no artigo existente após revisão.
- A verificação ocorre no servidor antes de baixar a foto e criar o post, sob o lock do motor. Protege tanto RSS quanto Telegram. A identidade de requisições antigas foi preservada.
- O exame de similaridade considera até 60 matérias da mesma categoria nos últimos sete dias. É uma heurística; não promete reconhecer todas as reformulações de um acontecimento. Não exclui automaticamente notícias diferentes por compartilharem cidade ou assunto.

A proteção do WordPress foi instalada em 26/10/2026. Atualizar o coletor da VPS acrescenta o tratamento dos dois códigos; versões anteriores também têm a criação recusada pelo servidor, mas podem registrar esses casos como erros genéricos para revisão. O atualizador preserva modos por fonte e banco de vistos quando executado sem `--apply-publication-policy`.

## Conteúdo e imagens

O modelo recebe somente título, texto fornecido no RSS, URL, identificação da fonte e data. Não navega no artigo completo. `use_full_content: true` permite aproveitar `content:encoded` quando fornecido no próprio feed e autorizado para uso; não contorna paywall ou busca páginas externas. Uma fonte grande que forneça só resumo curto pode continuar gerando rascunhos.

Entradas com menos de 180 caracteres de material textual são ignoradas. Material insuficiente também pode ser recusado pelo OpenRouter. Não se inventam falas, dados ou acontecimentos locais para alongar notícias. Os rascunhos precisam de revisão factual/editorial; esses controles não eliminam alucinações de um modelo.

O coletor agora procura a foto em metadados/enclosures/HTML do feed e, se necessário, nos metadados e imagens da página original. BeautifulSoup seleciona candidatos; Pillow verifica o arquivo e dimensões mínimas de 200×200, até 8 MB e 24 megapixels. Ícones/logos/compartilhamento e arquivos não-imagem são ignorados. A página original é consultada para localizar imagens; seu texto não é acrescentado ao material fornecido ao OpenRouter. Use somente fotos com autorização de uso e crédito adequado.

A URL validada vai em `image_url` e em uma figura depois do primeiro parágrafo, com ALT igual à palavra-chave Rank Math. A versão PHP correspondente remove a whitelist, anexa a imagem e troca a figura por uma URL local do WordPress; ela já foi instalada no Snippet 12, mas a atualização do coletor na VPS não foi executada pelo agente. Detalhes em `../31-fotos-publicas-alt-rankmath.md`. Se não houver foto válida, a matéria continua como rascunho com capa pendente; os demais itens continuam. Não há promessa de nota Rank Math 80+ ou de identificar visualmente toda fotografia usando apenas essas heurísticas.

Opcionalmente configure `image_credit` e `image_credit_url` em cada fonte. O crédito informado no feed/configuração é preservado; sem crédito, a legenda identifica a fonte, sem inventar autoria oficial. `Divulgação / Fonte Oficial` pode ser declarado em `image_credit` quando correto.

Os 422 de imagem reconhecidos geram um fallback imediato sem foto e sem a figura gerenciada no corpo. O payload novo é salvo antes do segundo POST; falhas de rede posteriores mantêm essa variante na fila. O ID e o permalink confirmado ficam em `wp_id`/`wp_url`, com migração automática e aditiva da tabela. `--retry-image-errors` recupera seletivamente bloqueios antigos de imagem, sem chamar APIs. O fluxo anterior está registrado em `../30-imagens-resilientes-endpoint.md`.

## Instalar na VPS Ubuntu

Baixe o pacote completo, copie para sua VPS (por SCP/SFTP) e, na pasta que contém o ZIP, execute:

```bash
sudo apt-get update
sudo apt-get install -y python3 python3-venv ca-certificates cron unzip logrotate curl util-linux
unzip giro73-coletor-rss.zip
cd giro73-rss

id giro73-rss >/dev/null 2>&1 || sudo adduser --system --group \
  --home /var/lib/giro73-rss --no-create-home giro73-rss

sudo install -d -o root -g giro73-rss -m 0750 /opt/giro73-rss
sudo install -d -o giro73-rss -g giro73-rss -m 0700 \
  /var/lib/giro73-rss /var/log/giro73-rss

sudo install -o root -g giro73-rss -m 0640 \
  coletor_rss.py requirements-rss.txt /opt/giro73-rss/
sudo python3 -m venv /opt/giro73-rss/.venv
sudo sh -c 'umask 022; /opt/giro73-rss/.venv/bin/python -m pip install --no-cache-dir -r /opt/giro73-rss/requirements-rss.txt'

sudo install -o root -g giro73-rss -m 0640 \
  rss_fontes.example.json /etc/giro73-rss.json
sudo install -o root -g giro73-rss -m 0640 \
  giro73-rss.env.example /opt/giro73-rss/.env
sudo install -o root -g giro73-rss -m 0750 \
  run-coletor-rss.sh /opt/giro73-rss/run
```

Os comandos de cópia de exemplos são para a instalação inicial: em atualizações, preserve `/opt/giro73-rss/.env`, `/etc/giro73-rss.json` e o banco de vistos já preenchidos.

O endpoint atual possui um único token ativo. Use o token já utilizado pelo bot, mantendo processos/filas separados. Gerar outro token no painel invalida o anterior e exige atualizar os dois clientes.

Edite o ambiente e as fontes:

```bash
sudoedit /opt/giro73-rss/.env
sudoedit /etc/giro73-rss.json
```

Ambiente:

```sh
OPENROUTER_API_KEY='SUA_CHAVE_OPENROUTER'
AI_MODEL='openai/gpt-4o-mini'
G73_TOKEN='SEU_TOKEN_X_G73_TOKEN'
G73_PUBLISH_URL='https://giro73.com.br/wp-json/g73/v1/publish'
RSS_MAX_RUN_SECONDS='1800'
```

Proteja o `.env` com proprietário `root:giro73-rss` e modo `0640`. Não o envie ao Git nem cole chaves nos logs. O token WordPress é o `X-G73-Token` do motor de publicação, diferente da chave OpenRouter e do token Easy MCP.

Exemplo de fonte — substitua a URL antes de habilitar:

```json
{
  "feeds": [
    {
      "id": "fonte_regional_autorizada",
      "name": "Nome da fonte autorizada",
      "url": "https://SEU-DOMINIO-AUTORIZADO/feed/",
      "category": "noticias-regiao",
      "authorized": true,
      "use_full_content": false,
      "allow_undated": false,
      "max_age_hours": 72
    }
  ]
}
```

Mantenha IDs estáveis. Categorias aceitas: `noticias-regiao`, `politica`, `dinheiro-renda-extra`, `tecnologia-inovacao`, `esportes`, `famosos-entretenimento`. Para outra fonte, acrescente outro objeto ao array `feeds`.

`allow_undated: false` evita importar material sem data como notícia recente; entradas futuras ou mais antigas que `max_age_hours` também são ignoradas. Feeds com pouca retenção podem perder itens entre coletas horárias. O padrão de processamento é cinco candidatos por execução, compartilhando a cota WordPress com o bot Telegram.

## Conferir antes de ativar o cron

```bash
# Lista modelos disponíveis; não gera texto nem cria notícias.
sudo -u giro73-rss /opt/giro73-rss/run --list-models

# Valida apenas a configuração OpenRouter, sem gerar texto.
sudo -u giro73-rss /opt/giro73-rss/run --check-ai

# Confira o ID escolhido em /opt/giro73-rss/.env.
# Valida modelo, token WordPress e leitura dos feeds.
sudo -u giro73-rss /opt/giro73-rss/run --check

# Gera até cinco payloads e salva a fila local, sem POST de notícias ao WP.
# Esta etapa utiliza a API de geração OpenRouter e está sujeita a custos/cotas.
sudo -u giro73-rss /opt/giro73-rss/run --dry-run
sudo -u giro73-rss /opt/giro73-rss/run --status

# Envia os payloads preparados, respeitando os modos por fonte.
sudo -u giro73-rss /opt/giro73-rss/run
```

`--check` testa o token WordPress com um payload deliberadamente incompleto, que deve retornar HTTP 400 `g73_invalid_title`. Isso pode gerar um registro de validação nos logs do endpoint, mas não cria uma matéria.

## Atualizar uma instalação existente

Baixe `atualizar-vps.sh` deste mesmo diretório no GitHub, de preferência fixando o commit, e execute como root:

```bash
bash /root/atualizar-giro73-rss.sh COMMIT_OU_MAIN

# Para aplicar também a seleção de fontes automáticas aprovada:
bash /root/atualizar-giro73-rss.sh COMMIT_OU_MAIN --apply-publication-policy
```

O atualizador baixa coletor, dependências, launcher e configurador de publicação da referência informada, confere a sintaxe, aguarda até 30 segundos pelo lock da coleta e cria backup privado dos arquivos/configurações e um backup SQLite consistente em `/root/giro73-rss-backup.*`. Depois instala as dependências, substitui os arquivos e corrige permissões. Mantém o `.env`, o banco de vistos e o cron; só altera a política por fonte quando recebe `--apply-publication-policy`. Também atualiza `/opt/giro73-rss/run`.

A instalação das bibliotecas usa `umask 022`, isolado em um subshell; backup, `.env` e SQLite continuam restritos. O atualizador testa os imports como `giro73-rss` antes de substituir o coletor. Se `python-dotenv` não puder ser importado, reinstala somente esse pacote com as permissões corretas, inclusive quando o pip o considera já instalado.

Se houver coleta ativa além desse prazo, interrompe antes de substituir os arquivos: aguarde terminar e rode novamente. Ao concluir, executa `--check-ai` e `--limit 1`. Este último processa até um candidato e pode publicar se a política automática estiver habilitada e os requisitos forem atendidos. Também pode não criar matéria se não houver item apto. A confirmação informa ID, status real, ID da capa e link. `capa: 0` significa imagem pendente.

Payloads já preparados continuam com o mesmo conteúdo e chave de idempotência; não são regenerados na migração. Rascunhos preparados não são promovidos automaticamente. `--dry-run` pode preparar payloads `publish` que serão enviados pelo cron depois; não equivale a pausar publicações. O atualizador não altera o Snippet 12 nem o bot Telegram.

### Erro `cannot import name 'dotenv_values' from 'dotenv' (unknown location)`

Esse erro ocorre antes de autenticar no OpenRouter. Uma causa é a instalação feita por root com `umask 077`: os arquivos do pacote podem ficar inacessíveis ao usuário de serviço. Também pode haver um módulo local com o mesmo nome. O atualizador atual repara a instalação do pacote e testa os imports sob o usuário correto. Não altere as permissões do `.env` para tentar corrigir um problema nas bibliotecas.

Para reparar apenas o pacote numa instalação já atualizada, execute como root:

```bash
(
  umask 022
  /opt/giro73-rss/.venv/bin/python -m pip install --force-reinstall --no-deps \
    --no-cache-dir --timeout 30 --retries 2 'python-dotenv>=1.1,<2'
) && \
sudo -u giro73-rss /opt/giro73-rss/run --check-ai
```

Se o import continuar falhando, confira os caminhos e permissões antes de reinstalar novamente. Não imprima o conteúdo do `.env` para diagnosticar bibliotecas.

## Cron de hora em hora

Só habilite após as checagens anteriores:

```bash
sudo install -o root -g root -m 0644 giro73-rss.cron /etc/cron.d/giro73-rss
sudo install -o root -g root -m 0644 giro73-rss.logrotate /etc/logrotate.d/giro73-rss
sudo systemctl enable --now cron

sudo cat /etc/cron.d/giro73-rss
sudo tail -n 50 /var/log/giro73-rss/coletor.log
sudo -u giro73-rss /opt/giro73-rss/run --status
```

A linha instalada é:

```cron
0 * * * * giro73-rss /usr/bin/timeout 50m /opt/giro73-rss/run >> /var/log/giro73-rss/coletor.log 2>&1
```

O usuário está presente porque o arquivo é de `/etc/cron.d`, não de um crontab pessoal. Para pausar, remova ou mova esse arquivo para fora de `/etc/cron.d`; preserve o banco local.

## Adicionar as fontes selecionadas em lote

O utilitário `configurar_fontes_rss.py` pode ser enviado separadamente à VPS onde o coletor já está instalado. No computador em que o arquivo foi baixado:

```bash
scp configurar_fontes_rss.py root@IP_DA_VPS:/root/
```

Na VPS, como root:

```bash
/opt/giro73-rss/.venv/bin/python /root/configurar_fontes_rss.py
```

Ele verifica Portal TX e Vem Vê TX Bahia primeiro, procurando links RSS/Atom declarados no HTML e testando caminhos comuns quando necessário. Depois verifica os endereços informados para Liberdade News, Bahia Notícias Municípios/Esportes, G1 Bahia/Brasil, Agência Brasil Últimas/Economia, Bahia Extremo Sul, Extremo Sul Notícias, Olhar Digital, Portal Leo Dias, Sul Bahia News e Radar News. São até 15 fontes contando o Liberdade News, desde que todas sejam válidas e não haja outras fontes extras cadastradas.

Somente fontes novas com RSS/Atom reconhecido são adicionadas. Fonte existente é preservada com o mesmo ID, categoria e estado de habilitação. O utilitário não altera a fila SQLite e não chama OpenRouter ou WordPress. Cria backup de `/etc/giro73-rss.json`, grava por substituição atômica e preserva o proprietário/grupo. Se outra pessoa editar a configuração durante os testes, interrompe a gravação para não perder essa edição.

As fontes adicionadas são marcadas `authorized: true` conforme seleção do administrador; a verificação técnica não confirma licenças de reutilização. Portal TX e Vem Vê TX Bahia são destinados a `noticias-regiao`. A ordem de verificação não muda a prioridade dos itens que já estão na fila. Os feeds gerais também permanecem em `noticias-regiao`: esta versão não classifica automaticamente os assuntos e não inclui feed exclusivo de Política.

O utilitário não pressupõe que `/feed/` exista. Nesta sessão, as consultas externas a Portal TX e Vem Vê TX Bahia foram bloqueadas pela conexão do ambiente (túnel HTTP 403); os endereços reais precisam ser confirmados pelo utilitário na VPS. Se um site não publicar RSS ou bloquear a consulta, ele aparece em `EXIGEM REVISÃO` e não é cadastrado como fonte nova. O código de saída 1 indica que houve fonte não adicionada; as fontes novas válidas já foram salvas.

O prazo padrão é de até 60 segundos por fonte nova, configurável com `--timeout 10..120`. Para a checagem geral após o cadastro, use `sudo -u giro73-rss /opt/giro73-rss/run --check`; ela também depende da disponibilidade da API OpenRouter. O número de entradas no RSS não garante que todas cumpram os critérios de idade, data e texto mínimo do coletor.

Para verificar somente Portal TX e Vem Vê TX Bahia: `/opt/giro73-rss/.venv/bin/python /root/configurar_fontes_rss.py --only-local`. A busca inclui `rss.php`, `/rss` e `/feed` sem barra final, `feed.xml`, `rss2.xml` e o formato de feed via `index.php`, além dos candidatos anteriores. Esses caminhos são tentativas, não feeds confirmados. O HTML trazido pelo usuário revelou templates de `srvsite.com` nos dois sites, sem link RSS declarado; isso não identifica um endpoint RSS nem comprova que não exista. Os erros de DTD podem vir de HTML retornado como fallback: mantenha o bloqueio de DTD/entidades do coletor.

### XMLs convertidos do Portal TX / fontes regionais

Foram acrescentados os três endereços informados pelo usuário:

- `https://politepaul.com/fd/GIKEXW1XNI1F.xml`
- `https://politepaul.com/fd/Fqe4CqOn1xIz.xml`
- `https://politepaul.com/fd/PjntToq6bO88.xml`

Use `/opt/giro73-rss/.venv/bin/python /root/configurar_fontes_rss.py --only-converted` para verificar/adicionar somente esses três. A execução padrão inclui também essas definições. O coletor principal não precisa ser substituído. Com as 13 fontes confirmadas na VPS do usuário e os três XMLs novos, o total será 16, se todos forem aceitos e não houver outras fontes cadastradas.

São destinados a `noticias-regiao`. O cadastro usa o nome `Portal TX` quando os links de artigos encontrados apontam apenas para `portaltx.site`; se apontarem para outro domínio, usa esse domínio como identificação provisória, sem inventar o nome do criador. Feed sem links HTTP(S) para matérias fora de `politepaul.com` exige revisão e não é cadastrado. O configurador informa domínios originais, resumos com ao menos 180 caracteres, datas, itens que passam no pré-filtro e sobreposição de URLs entre os XMLs testados na mesma execução. A comparação não abrange feeds já cadastrados que foram ignorados pelo configurador; o coletor mantém sua própria deduplicação por URL/GUID.

RSS válido pode ser cadastrado mesmo sem itens que passem no pré-filtro naquele momento, com aviso explícito. Ele permanece com `use_full_content: false`, `allow_undated: false`, janela de 72 horas e os mesmos requisitos de conteúdo do coletor; não gera notícias a partir de títulos insuficientes. O diagnóstico considera as primeiras 50 entradas e o resumo, como o coletor, e não garante que o OpenRouter aceitará esses itens. O serviço de conversão pode fornecer data de coleta em vez da data original da notícia; a revisão editorial deve conferir isso. Terceiros também podem mudar limites ou atualização do feed. A conversão não concede direitos de republicação.

As consultas a esses três XMLs foram bloqueadas pela rede deste ambiente com túnel HTTP 403; a validação real é feita pela VPS, sem uso de chaves OpenRouter/WordPress e sem criar matérias.

## Resiliência e operação

- OpenRouter: até quatro tentativas em 429/500/502/503/504 ou falhas de conexão; espera exponencial com jitter e respeito a Retry-After. Encerradas as tentativas, a fila fica para o próximo cron.
- Modelo inexistente, chave inválida, saldo insuficiente (402), acesso negado ou formato incompatível: termina com erro, sem escolher outro modelo e sem marcar como enviado.
- WordPress: busy pode ser repetido; conflito de identificador, dados inválidos e outros erros permanentes ficam para revisão. Estado pausado/configuração inválida não provoca retentativas cegas de 503.
- Payload gerado é imutável até confirmação, exceto pela retirada da imagem após 422 de imagem confirmado. Itens preparados podem ser enviados mesmo se a API OpenRouter estiver temporariamente indisponível.
- Credenciais somente no ambiente; logs não incluem corpo da notícia, resposta bruta, URLs de provedores ou chaves. O banco e o ambiente têm permissões restritas.
- `--limit 1` a `--limit 10` controla candidatos processados; padrão 5. Nem todo candidato vira rascunho, pois fontes insuficientes são ignoradas/bloqueadas.

### Expansão de prefeituras e câmaras — 27/10/2026

A lista de hosts do PHP não cadastra RSS. O motor WordPress atual informa `image_policy: public_urls_no_allowlist`: imagens públicas não dependem dessa whitelist. Não substituir o Snippet 12 por uma lista de domínios para cadastrar fontes.

O configurador passou a incluir **38 endereços candidatos de 19 municípios**, conforme indicação do administrador, por meio de `--only-government`. O limite da configuração subiu de 20 para **100 fontes**; os tetos de cinco candidatos por execução e dez com `--limit 10` foram preservados. A descoberta examina até 50 entradas de cada feed, sujeitas a data, conteúdo mínimo e deduplicação; isso não significa 50 artigos publicados por fonte.

As novas Prefeituras entram em `noticias-regiao` e as novas Câmaras em `politica`, com `publication_mode: draft`. Não há classificação automática do assunto de cada release: o editor pode ajustar a categoria na revisão. As regras e IDs das fontes já cadastradas são preservados. As novas fontes incluem `city`, `city_slug`, `site_url` e `source_type` para identificação na configuração; isso ainda não cria uma taxonomia de cidades nem um filtro novo no WordPress.

Atualização e descoberta na VPS, como root, substituindo `REF` pelo commit informado na entrega:

```bash
RSS_REF='REF'
curl --fail --location --retry 3 --max-time 60 \
  "https://raw.githubusercontent.com/dicasdoflaviog/Giro73/${RSS_REF}/portal-noticias-mcp/automacao-rss/expandir-fontes-oficiais.sh" \
  --output /root/expandir-fontes-oficiais.sh && \
bash -n /root/expandir-fontes-oficiais.sh && \
bash /root/expandir-fontes-oficiais.sh "$RSS_REF"
```

O instalador baixa somente `coletor_rss.py` e `configurar_fontes_rss.py`, valida sintaxe/importações, usa o lock da coleta e salva os arquivos antigos/configuração em `/root/giro73-fontes-backup.*`. Não modifica `.env`, cron, banco de vistos ou política das fontes existentes. Enquanto verifica, impede que uma coleta leia uma configuração em transição. Não chama OpenRouter nem o endpoint WordPress. O cron existente poderá usar os novos feeds válidos na execução seguinte.

Para fazer lotes menores, acrescente os municípios ao comando do instalador:

```bash
bash /root/expandir-fontes-oficiais.sh "$RSS_REF" \
  --city porto-seguro --city eunapolis --city itamaraju
```

Para novos lotes ou repetir a descoberta depois de instalar, sem baixar arquivos novamente:

```bash
/opt/giro73-rss/.venv/bin/python -B /opt/giro73-rss/configurar_fontes_rss.py \
  --only-government --city alcobaca --city caravelas --city nova-vicosa --timeout 20
```

`--city` pode ser repetido; sem ele são verificadas todas as 19 cidades. O timeout é **por site**, padrão de 20 segundos no instalador. Uma verificação completa com sites indisponíveis pode levar vários minutos; isso ocorre no terminal da VPS, não em uma requisição PHP. A ajuda do configurador lista todos os slugs aceitos.

`RSS VALIDADO` confirma o XML lido, não a elegibilidade de seus itens, os direitos de textos/fotos ou a apuração do release. `JÁ CADASTRADA` preserva o registro existente, sem revalidar seu RSS nem mudar sua política. `EXIGEM REVISÃO` identifica sites sem RSS validado nessa tentativa; não são cadastrados como se fossem feeds funcionais. Se um site não oferecer RSS, será necessário localizar outro canal oficial ou criar um adaptador próprio de HTML, fora desta expansão.

Códigos de saída: 0 para verificação sem pendências, 1 para resultado parcial (fontes válidas salvas, outras pendentes), 2 para falha de configuração/instalação. Não desabilitar TLS nem aceitar HTML como se fosse XML para contornar esses resultados.

A consulta de `/feed/` aos 38 endereços neste ambiente foi impedida pelo proxy (`Tunnel connection failed: 403 Forbidden`), não por um status HTTP verificado dos servidores municipais. A confirmação final precisa ser feita na VPS; nenhuma dessas 38 URLs foi declarada como RSS confirmado na entrega.

Se o cron estiver instalado exatamente como no exemplo, roda de hora em hora com o limite padrão de **cinco candidatos totais**, em rodízio persistente entre fontes. `--limit 10` permite até dez candidatos totais por execução, sem alterar a coleta das 50 entradas iniciais de cada feed. Um candidato pode ser antigo, duplicado, insuficiente, bloqueado ou adiado; o número real de posts pode ser menor. A instalação do cron e a versão efetivamente em uso na VPS não foram inspecionadas por SSH nesta etapa.
- O código prepara matérias; não cadastra eventos no CPT da Agenda.

Códigos de saída: 0 para execução concluída/sem novidades/lock já ocupado; 1 para indisponibilidade parcial ou itens bloqueados/adiados; 2 para configuração ou interrupção que exige atenção. Código 124 é do `timeout` externo; a fila já persistida será reutilizada depois.

## Testes desta entrega

```bash
python3 -m venv .venv-test
.venv-test/bin/pip install -r requirements-rss.txt
.venv-test/bin/python -m unittest discover -s tests -v
```

Passaram **93 testes locais**: 27 do coletor, 12 do configurador, 12 das imagens, 12 do OpenRouter, 24 de rodízio/publicação e 6 de duplicatas. Incluem RSS → OpenRouter → WordPress com APIs simuladas, 429/503, saldo insuficiente, JSON Schema estrito, `.env`, fallback 422, resposta perdida, migração do banco, deduplicação, fotos em Handler.ashx, figura/ALT, rodízio entre execuções, prioridade para notícias recentes, publicação seletiva, sinais de revisão editorial e confirmação do status real. Nenhuma chave real foi utilizada nem matéria real criada por esses testes. O agente não acessou a VPS; a validação real deve ser feita com os comandos acima.
