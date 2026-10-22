# Interface editorial Giro73

Implementação publicada no tema de blocos `hostinger-ai-theme`, descrita em `../14-home-artigos-widgets-editoriais.md`.

| Arquivo | Destino WordPress |
|---|---|
| `header-editorial.html` | Template part `hostinger-ai-theme//header` |
| `footer-editorial.html` | Template part `hostinger-ai-theme//footer` |
| `home-grade-automatica.html` | Template `hostinger-ai-theme//front-page` |
| `single-editorial.html` | Template `hostinger-ai-theme//single` |
| `css-g1-techtudo-ultra-leve.css` | `styles.css` dos estilos globais, ID 12 |

Não inserir estes templates como conteúdo de uma nova página. Ler o estado atual antes de publicar e preservar outras propriedades dos estilos globais. As consultas de posts são nativas; não substituí-las por listas fixas.

`header-editorial.js` e `widgets-editoriais.js` são fontes legíveis dos scripts inline já contidos nos respectivos HTMLs. O compartilhamento dos artigos agora é gerado por PHP no snippet 7; o script anterior foi removido. Não carregá-los novamente. `widgets-editoriais.html` documenta os dois blocos HTML de dados, já incluídos na Home. O arquivo histórico `topbar-relogio.js` não é utilizado pelo novo header.

Menu usa dialog nativo; busca usa GET `s` e `post_type=post`. Relógio usa Intl em America/Sao_Paulo. WhatsApp apenas monta o link com título e URL canônica, sem envio automático.

## Dados da sidebar

Clima continua via Open-Meteo, com onze pontos de referência municipais fixos em `cidades-clima-extremo-sul.json`. Teixeira de Freitas é o padrão, seguido de Porto Seguro, Eunápolis, Itamaraju, Prado, Mucuri, Caravelas, Medeiros Neto, Nova Viçosa, Itanhém e Alcobaça. A previsão consulta horas locais de hoje, temperatura e código meteorológico; máximas/mínimas por período, atribuição visível e link para https://www.climatempo.com.br/. Cache público local de 15 minutos por cidade/dia. Não há consulta de geolocalização do leitor nem geocodificação no caminho de renderização.

O financeiro usa exclusivamente `GET /wp-json/giro73/v1/cotacoes`, na própria origem. Não consulta AwesomeAPI/HG Brasil diretamente no navegador. `cotacoes-servidor.php` contém a rotina publicada como snippet PHP global **ID 5** no **Code Snippets 4.0.0**, instalado pela API nativa porque a conexão não escreve arquivos PHP do tema. O plugin não adicionou assets ao frontend. WPCode existente foi preservado; nenhum código dele foi alterado. Não executar cópia simultânea da mesma rotina no tema.

- `wp_remote_get` consulta duas URLs fixas, com TLS padrão verificado, timeout de quatro segundos por fonte, sem redirecionamentos e com corpo limitado a 64 KB.
- AwesomeAPI é a primeira fonte de dólar/euro. HG Brasil fornece Ibovespa e moedas alternativas se a primeira fonte falhar. Cada indicador identifica sua fonte na resposta JSON. Índice em pontos, moedas em reais. `as_of` é a data da fonte quando disponível; `fetched_at` identifica somente a consulta do servidor.
- `set_transient('g73_market_v2', ..., 15 * MINUTE_IN_SECONDS)` guarda respostas completas por 900 segundos. Falha/resultado parcial recebe cache curto de 60 segundos para permitir recuperação. Trava de atualização com `add_option`, sem autoload, limita consultas frias simultâneas; limpa em finally e expira após 15 segundos.
- O endpoint público não aceita URL externa ou parâmetros de provedor. Não devolve credenciais, respostas brutas ou erros internos. Valida JSON, valores finitos/positivos e variações antes de publicar indicadores.
- O navegador respeita `expires_at`, consulta novamente quando o card está visível e mantém traços se não receber dados válidos. Não há cache financeiro em localStorage com números antigos das consultas diretas.

Rollback: desativar apenas o snippet 5 em Snippets, ou POST `/code-snippets/v1/snippets/5/deactivate`. O widget passa a indicar indisponibilidade. Não desativar o plugin enquanto este for o único mecanismo que carrega a rotina. Para migrar ao functions.php de um tema filho ou a MU-plugin, instalar o arquivo, desativar o snippet e confirmar que a rota continua registrada; manter somente uma cópia carregada. Mudanças no código não limpam automaticamente o transient; versionar a chave quando a estrutura do cache mudar.

Depois de publicar, limpar LiteSpeed via `wp_clear_cache`, scope site para alterações globais, respeitando o limite de uma vez a cada cinco minutos; para correções finais, scope urls. Respeitar verify.after_seconds e conferir URLs canônicas sem cache buster. Validar seletor, coordenadas, endpoint anônimo, cache e layout mobile/desktop antes de anunciar conclusão.

## Publicidade local

A implantação 16 acrescentou slots laterais na Home e nos artigos, além de banner automático após o terceiro parágrafo editorial. Gestão: **Configurações → Publicidade Giro73**, snippet PHP **6**. Ver `README-publicidade.md`. O single agora tem uma coluna de apoio à direita no desktop, preservando leitura de até 740 px; mobile segue em coluna única. O mecanismo de cotações, snippet 5, foi preservado.

## Template oficial dos artigos

A implantação **17** removeu a sidebar do single e centralizou a leitura em 740 px. `single-editorial.php`, snippet 7, concentra inserção do anúncio após o terceiro parágrafo, Leia também após o sexto e compartilhamento WhatsApp sem JS. Ver `README-single.md`; essa configuração substitui a descrição de duas colunas da implantação 16.

## Fechamento da fundação

A implantação 19 publicou category/archive/search/author e a template part única `giro73-sidebar`, compartilhada com a Home. O script de widgets passou da Home para essa parte; não duplicar. Miniaturas de feed desktop agora têm 240×135 px. O snippet 8 acrescenta cabeçalhos de arquivo, NewsArticle/Organization e ads.txt. Ver `README-arquivos-schema.md`; single permanece sem sidebar.

## Parceiros G73 — implantação 20

A gestão antiga foi substituída pelo CPT e painel **Parceiros G73**, sem novo plugin. Classes neutras, cinco posições, sete formatos, campanhas agendadas, rotação/empilhamento e modal nativo. Fonte principal continua no snippet 6; inserções no snippet 7. Consulte [README-publicidade.md](README-publicidade.md) e [registro da implantação 20](../20-gerenciador-parceiros-g73.md). Os dois banners existentes foram migrados, e o modal permanece desligado até haver campanha configurada.

## Engajamento e autoridade regional — implantação 21

CTA WhatsApp configurável, reações/enquetes, colunistas, agenda regional e impressão foram integrados sem plugin novo. Fonte no snippet 9, independente das rotinas 5–8. Convite vazio, colunistas Em breve e três eventos-modelo em rascunho conforme orientação do usuário. Consulte [README-engajamento.md](README-engajamento.md) e [validação da implantação 21](../21-engajamento-retencao-autoridade-regional.md).

## Dimensões dos parceiros — implantação 22

Banners agora usam imagens completas com altura natural e micro-aba PUBLICIDADE, sem caixa cinza. `sidebar_base` e `home_entre_secoes` são posições independentes; as seis posições no fluxo estão preenchidas para comparação. Relação de campanhas/mídias e validação: [implantação 22](../22-parceiros-dimensoes-comparacao.md). O guia de publicidade contém a API atualizada.
