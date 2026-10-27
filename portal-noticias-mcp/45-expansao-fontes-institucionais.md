# Fontes institucionais do Extremo Sul — expansão

Preparado em 27/10/2026, conforme os endereços indicados pelo responsável. Escopo: cadastro validado de RSS e explicação da capacidade do coletor. Não houve alteração de status editorial, publicações ou scripts de frontend no WordPress.

## Conexão WordPress conferida

O endpoint administrativo `/g73/v1/publisher/status` respondeu HTTP 200 pelo Easy MCP e informou `image_policy: public_urls_no_allowlist`, com lista de hosts vazia. Assim, a função PHP `return array(...)` fornecida na conversa não representa um cadastro de feeds e não é necessária para liberar imagens desses sites no motor atual. As verificações de URL pública, TLS, arquivo e tamanho continuam distintas de uma whitelist por domínio.

## Alterações no Git

- Limite de fontes do coletor e configurador ampliado de 20 para 100; padrão de cinco candidatos processados por execução preservado.
- Catálogo de 19 municípios, com Prefeitura e Câmara: **38 endereços candidatos**. Não se declarou que todos os domínios ou caminhos `/feed/` sejam válidos.
- Novo modo `configurar_fontes_rss.py --only-government`, com `--city` repetível para lotes pequenos.
- RSS declarados no HTML e caminhos candidatos verificados antes de cadastrar. Prioridade de tentativa para `/feed/` e `?feed=rss2` após os links declarados.
- Novas fontes em rascunho; Prefeitura em Notícias & Região, Câmara em Política. Categorias são destinos iniciais por fonte, não classificação individual por IA.
- Metadados de cidade, tipo de órgão e site de origem na configuração, sem afirmar que já existe filtro de cidade no portal.
- Registros existentes preservados, inclusive autorização e política automática. URLs redirecionadas identificadas pelo site de origem para não duplicar o cadastro ao repetir a descoberta.
- Instalador enxuto com backup, lock compartilhado com a coleta e validação dos scripts. Não altera segredo, arquivo de vistos, cron ou Snippet 12 e não cria posts.

## O que foi validado

Passaram **98 testes Python**, incluindo cadastro com mais de 20 fontes, teto de 100, preservação de fontes automáticas, mapeamento dos dois órgãos, repetição após descoberta de um RSS em outro host e registro parcial sem apagar fontes antigas. A sintaxe Bash do instalador foi conferida.

A tentativa de consultar os 38 caminhos `/feed/` neste ambiente não atravessou o proxy: o diagnóstico de uma chamada confirmou `Tunnel connection failed: 403 Forbidden`. Isso não equivale a HTTP 403 de cada município, nem prova inexistência de RSS. A confirmação final será executada pelo instalador na VPS. Sem SSH, nenhuma fonte foi declarada instalada/ativa na VPS nesta etapa.

## Quantos artigos por vez

1. **Descoberta:** até 50 entradas de cada feed cadastrado e autorizado. Aplicam-se os filtros de data, URL, tamanho de texto e deduplicação antes de inserir candidatos na fila.
2. **Processamento editorial:** até cinco candidatos no total por execução, por padrão; `--limit 1` a `--limit 10` altera esse teto global. Não são cinco por fonte.
3. **Rodízio:** a próxima vaga vai para uma fonte elegível menos atendida, com estado persistente em SQLite. Ao ampliar as fontes, cresce a espera por uma vaga; aumentar o catálogo não multiplica automaticamente as publicações por hora.
4. **Publicação efetiva:** pode ser menor que o limite, por falta de candidatos aptos, duplicados, revisão, erros ou adiamentos. A política aprovada e o estado real retornado pelo WordPress determinam rascunho/publicado.
5. **Cron de exemplo:** de hora em hora. Cinco candidatos por hora dariam teto teórico de 120 por dia, nunca garantia de 120 artigos publicados. Não foi verificado o cron efetivamente instalado na VPS.

Usar primeiro o limite atual e conferir logs/qualidade antes de ampliar para dez. Sites institucionais são fontes atribuídas, não confirmação independente das próprias alegações. Cada release exige datas, contexto e foto com direitos conferidos, especialmente antes de publicar automaticamente.

Instalação e comandos: [README do coletor](automacao-rss/README.md#expansão-de-prefeituras-e-câmaras--27102026). Catálogo indicado: [CSV de fontes candidatas](automacao-rss/fontes-institucionais-candidatas.csv). A confirmação vem das linhas `RSS VALIDADO`/`JÁ CADASTRADA`/`EXIGEM REVISÃO` na VPS, não da presença do domínio no PHP.
