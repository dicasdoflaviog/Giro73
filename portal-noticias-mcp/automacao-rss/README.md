# Coletor RSS independente — Giro73

Python 3.10+ em Ubuntu 22.04/24.04 ou posterior compatível. Não depende do bot Telegram nem instala plugins no WordPress. Cria somente matérias em rascunho pelo endpoint existente.

## Arquivos

- `coletor_rss.py`: programa completo.
- `configurar_fontes_rss.py`: inclusão em lote das fontes selecionadas e descoberta de RSS de Portal TX/Vem Vê TX Bahia, executada na VPS.
- `requirements-rss.txt`: requests e feedparser; SQLite já faz parte do Python.
- `rss_fontes.example.json`: configuração por fonte, sem inventar feeds autorizados.
- `giro73-rss.env.example`: exemplo sem credenciais reais.
- `run-coletor-rss.sh`: wrapper com caminhos absolutos e ambiente.
- `giro73-rss.cron`: coleta de hora em hora, com limite externo de 50 minutos.
- `giro73-rss.logrotate`: rotação dos logs.
- `tests/test_coletor_rss.py`: 27 testes automatizados com APIs simuladas.
- `tests/test_configurar_fontes_rss.py`: 12 testes de descoberta e atualização segura da configuração.

## Modelo Gemini

O padrão solicitado é **gemini-3.8-flash**, configurável em `GEMINI_MODEL`. A disponibilidade desse ID não pôde ser confirmada nesta sessão. Não é feita troca silenciosa por outro modelo: antes de gerar, o script consulta o modelo usando a chave do projeto e exige suporte a `generateContent`. Se o ID não existir ou não estiver autorizado, a execução termina com erro e preserva a fila.

Use `--list-models` para listar os IDs disponíveis à chave; se necessário, selecione um ID retornado e altere explicitamente o arquivo de ambiente. O código usa a API REST Gemini, sem depender de versão de SDK Google.

## Fluxo e garantias locais

1. Ler apenas fontes com `authorized: true`, via HTTPS. A autorização é declarada pelo administrador; o script não verifica licenças de terceiros.
2. Aceitar RSS/Atom de até 2 MB, sem DTD/entidades XML; até 50 entradas por fonte em cada coleta.
3. Registrar GUID e URL, removendo parâmetros de rastreamento conhecidos. Fontes distintas com a mesma URL compartilham o registro.
4. Preparar uma nota factual com Gemini; ignorar itens insuficientes ou recusados pelo modelo.
5. Montar o payload localmente, sempre com `status: draft` e os três campos do Rank Math em `meta`. O modelo não controla categoria, status, credenciais ou destino.
6. Persistir o payload em SQLite **antes** do POST e enviar `Idempotency-Key: rss-[hash]`.
7. Registrar como `sent` somente após receber ID válido e status `draft` do WordPress. Depois da confirmação, textos completos locais são descartados; identidade, URL e ID permanecem no histórico.

Retentativas de rede/503 podem repetir a chamada HTTP, mas usam o mesmo texto e chave; o endpoint WordPress existente é quem impede que isso crie outro post após uma resposta perdida. Um 422 confirmado de imagem permite persistir uma variante `draft` sem foto e reenviar com a mesma chave. Não existe promessa de transporte HTTP exatamente uma vez. O bloqueio de processo impede dois cron jobs locais simultâneos.

O banco `vistos_rss.sqlite3` guarda fila e histórico de itens. Estados: `queued`, `prepared`, `sent`, `skipped` e `blocked`. Itens bloqueados exigem diagnóstico; não são apagados nem repetidos cegamente. Preserve e faça backup desse banco — mudar/apagar o arquivo remove a memória local do coletor. A deduplicação por identidade não reconhece automaticamente a mesma pauta publicada com URLs/GUIDs diferentes nem compartilha o histórico do bot Telegram.

## Conteúdo e imagens

O modelo recebe somente título, texto fornecido no RSS, URL, identificação da fonte e data. Não navega no artigo completo. `use_full_content: true` permite aproveitar `content:encoded` quando fornecido no próprio feed e autorizado para uso; não contorna paywall ou busca páginas externas.

Entradas com menos de 180 caracteres de material textual são ignoradas. Material insuficiente também pode ser recusado pelo Gemini. Não se inventam falas, dados ou acontecimentos locais para alongar notícias. Os rascunhos precisam de revisão factual/editorial; esses controles não eliminam alucinações de um modelo.

O payload envia `image_url: ""`. Seu endpoint permite rascunhos sem imagem. Capas autorizadas são adicionadas na etapa editorial; o coletor não copia automaticamente fotografias de outros portais.

Para coletores personalizados que já preencham `image_url`, os 422 de imagem reconhecidos geram um fallback imediato sem foto. O payload novo é salvo antes do segundo POST; falhas de rede posteriores mantêm essa variante na fila. O ID e o permalink confirmado ficam em `wp_id`/`wp_url`, com migração automática e aditiva da tabela. `--retry-image-errors` recupera seletivamente bloqueios antigos de imagem, sem chamar APIs. Integração completa e mudanças PHP: `../30-imagens-resilientes-endpoint.md`. Preserve a coleta de imagens personalizada da VPS ao aplicar somente os blocos de envio.

## Instalar na VPS Ubuntu

Baixe o pacote completo, copie para sua VPS (por SCP/SFTP) e, na pasta que contém o ZIP, execute:

```bash
sudo apt-get update
sudo apt-get install -y python3 python3-venv ca-certificates cron unzip logrotate
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
sudo /opt/giro73-rss/.venv/bin/pip install --no-cache-dir \
  -r /opt/giro73-rss/requirements-rss.txt

sudo install -o root -g giro73-rss -m 0640 \
  rss_fontes.example.json /etc/giro73-rss.json
sudo install -o root -g giro73-rss -m 0640 \
  giro73-rss.env.example /etc/giro73-rss.env
sudo install -o root -g giro73-rss -m 0750 \
  run-coletor-rss.sh /opt/giro73-rss/run
```

Os comandos de cópia de exemplos são para a instalação inicial: em atualizações, preserve os arquivos `/etc/giro73-rss.env` e `/etc/giro73-rss.json` já preenchidos.

O endpoint atual possui um único token ativo. Use o token já utilizado pelo bot, mantendo processos/filas separados. Gerar outro token no painel invalida o anterior e exige atualizar os dois clientes.

Edite o ambiente e as fontes:

```bash
sudoedit /etc/giro73-rss.env
sudoedit /etc/giro73-rss.json
```

Ambiente:

```sh
GEMINI_API_KEY='SUA_CHAVE_GOOGLE'
GEMINI_MODEL='gemini-3.8-flash'
G73_TOKEN='SEU_TOKEN_X_G73_TOKEN'
G73_PUBLISH_URL='https://giro73.com.br/wp-json/g73/v1/publish'
RSS_MAX_RUN_SECONDS='1800'
```

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

# Confira o ID escolhido em /etc/giro73-rss.env.
# Valida modelo, token WordPress e leitura dos feeds.
sudo -u giro73-rss /opt/giro73-rss/run --check

# Gera até cinco payloads e salva a fila local, sem POST de notícias ao WP.
# Esta etapa utiliza a API de geração Gemini e está sujeita a custos/cotas.
sudo -u giro73-rss /opt/giro73-rss/run --dry-run
sudo -u giro73-rss /opt/giro73-rss/run --status

# Envia os payloads preparados como rascunhos.
sudo -u giro73-rss /opt/giro73-rss/run
```

`--check` testa o token WordPress com um payload deliberadamente incompleto, que deve retornar HTTP 400 `g73_invalid_title`. Isso pode gerar um registro de validação nos logs do endpoint, mas não cria uma matéria.

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

Somente fontes novas com RSS/Atom reconhecido são adicionadas. Fonte existente é preservada com o mesmo ID, categoria e estado de habilitação. O utilitário não altera a fila SQLite e não chama Gemini ou WordPress. Cria backup de `/etc/giro73-rss.json`, grava por substituição atômica e preserva o proprietário/grupo. Se outra pessoa editar a configuração durante os testes, interrompe a gravação para não perder essa edição.

As fontes adicionadas são marcadas `authorized: true` conforme seleção do administrador; a verificação técnica não confirma licenças de reutilização. Portal TX e Vem Vê TX Bahia são destinados a `noticias-regiao`. A ordem de verificação não muda a prioridade dos itens que já estão na fila. Os feeds gerais também permanecem em `noticias-regiao`: esta versão não classifica automaticamente os assuntos e não inclui feed exclusivo de Política.

O utilitário não pressupõe que `/feed/` exista. Nesta sessão, as consultas externas a Portal TX e Vem Vê TX Bahia foram bloqueadas pela conexão do ambiente (túnel HTTP 403); os endereços reais precisam ser confirmados pelo utilitário na VPS. Se um site não publicar RSS ou bloquear a consulta, ele aparece em `EXIGEM REVISÃO` e não é cadastrado como fonte nova. O código de saída 1 indica que houve fonte não adicionada; as fontes novas válidas já foram salvas.

O prazo padrão é de até 60 segundos por fonte nova, configurável com `--timeout 10..120`. Para a checagem geral após o cadastro, use `sudo -u giro73-rss /opt/giro73-rss/run --check`; ela também depende da disponibilidade da API Gemini. O número de entradas no RSS não garante que todas cumpram os critérios de idade, data e texto mínimo do coletor.

Para verificar somente Portal TX e Vem Vê TX Bahia: `/opt/giro73-rss/.venv/bin/python /root/configurar_fontes_rss.py --only-local`. A busca inclui `rss.php`, `/rss` e `/feed` sem barra final, `feed.xml`, `rss2.xml` e o formato de feed via `index.php`, além dos candidatos anteriores. Esses caminhos são tentativas, não feeds confirmados. O HTML trazido pelo usuário revelou templates de `srvsite.com` nos dois sites, sem link RSS declarado; isso não identifica um endpoint RSS nem comprova que não exista. Os erros de DTD podem vir de HTML retornado como fallback: mantenha o bloqueio de DTD/entidades do coletor.

### XMLs convertidos do Portal TX / fontes regionais

Foram acrescentados os três endereços informados pelo usuário:

- `https://politepaul.com/fd/GIKEXW1XNI1F.xml`
- `https://politepaul.com/fd/Fqe4CqOn1xIz.xml`
- `https://politepaul.com/fd/PjntToq6bO88.xml`

Use `/opt/giro73-rss/.venv/bin/python /root/configurar_fontes_rss.py --only-converted` para verificar/adicionar somente esses três. A execução padrão inclui também essas definições. O coletor principal não precisa ser substituído. Com as 13 fontes confirmadas na VPS do usuário e os três XMLs novos, o total será 16, se todos forem aceitos e não houver outras fontes cadastradas.

São destinados a `noticias-regiao`. O cadastro usa o nome `Portal TX` quando os links de artigos encontrados apontam apenas para `portaltx.site`; se apontarem para outro domínio, usa esse domínio como identificação provisória, sem inventar o nome do criador. Feed sem links HTTP(S) para matérias fora de `politepaul.com` exige revisão e não é cadastrado. O configurador informa domínios originais, resumos com ao menos 180 caracteres, datas, itens que passam no pré-filtro e sobreposição de URLs entre os XMLs testados na mesma execução. A comparação não abrange feeds já cadastrados que foram ignorados pelo configurador; o coletor mantém sua própria deduplicação por URL/GUID.

RSS válido pode ser cadastrado mesmo sem itens que passem no pré-filtro naquele momento, com aviso explícito. Ele permanece com `use_full_content: false`, `allow_undated: false`, janela de 72 horas e os mesmos requisitos de conteúdo do coletor; não gera notícias a partir de títulos insuficientes. O diagnóstico considera as primeiras 50 entradas e o resumo, como o coletor, e não garante que o Gemini aceitará esses itens. O serviço de conversão pode fornecer data de coleta em vez da data original da notícia; a revisão editorial deve conferir isso. Terceiros também podem mudar limites ou atualização do feed. A conversão não concede direitos de republicação.

As consultas a esses três XMLs foram bloqueadas pela rede deste ambiente com túnel HTTP 403; a validação real é feita pela VPS, sem uso de chaves Gemini/WordPress e sem criar matérias.

## Resiliência e operação

- Gemini: até quatro tentativas em 429/500/502/503/504 ou falhas de conexão; espera exponencial com jitter e respeito a Retry-After. Encerradas as tentativas, a fila fica para o próximo cron.
- Modelo inexistente, chave inválida ou acesso negado: termina com erro, sem escolher outro modelo e sem marcar como enviado.
- WordPress: busy pode ser repetido; conflito de identificador, dados inválidos e outros erros permanentes ficam para revisão. Estado pausado/configuração inválida não provoca retentativas cegas de 503.
- Payload gerado é imutável até confirmação, exceto pela retirada da imagem após 422 de imagem confirmado. Itens preparados podem ser enviados mesmo se a API Gemini estiver temporariamente indisponível.
- Credenciais somente no ambiente; logs não incluem corpo da notícia, resposta bruta, URLs de provedores ou chaves. O banco e o ambiente têm permissões restritas.
- `--limit 1` a `--limit 10` controla candidatos processados; padrão 5. Nem todo candidato vira rascunho, pois fontes insuficientes são ignoradas/bloqueadas.
- O código prepara matérias; não cadastra eventos no CPT da Agenda.

Códigos de saída: 0 para execução concluída/sem novidades/lock já ocupado; 1 para indisponibilidade parcial ou itens bloqueados/adiados; 2 para configuração ou interrupção que exige atenção. Código 124 é do `timeout` externo; a fila já persistida será reutilizada depois.

## Testes desta entrega

```bash
python3 -m venv .venv-test
.venv-test/bin/pip install -r requirements-rss.txt
.venv-test/bin/python -m unittest discover -s tests -v
```

Passaram **39 testes locais**: 27 do coletor, incluindo RSS → Gemini → WordPress com APIs simuladas, 503, fallback 422 de imagem, retomada após resposta perdida, migração do banco, recuperação seletiva de bloqueados, chave idempotente, fontes autorizadas, modo dry-run e lock concorrente; mais 12 do configurador de fontes, incluindo descoberta pelo HTML, rotas PHP e sem barra final, fallback com página bloqueada, preservação de IDs/backup, repetição sem duplicatas, falha parcial, edição concorrente e pré-filtro/origem dos XMLs convertidos. Nenhuma chave real foi utilizada nem rascunho real criado por esses testes. O agente não acessou a VPS; a instalação e os testes reais relatados no chat foram executados pelo usuário.
