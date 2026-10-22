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

`header-editorial.js`, `widgets-editoriais.js` e `artigo-compartilhar.js` são fontes legíveis dos scripts inline já contidos nos respectivos HTMLs. Não carregá-los novamente. `widgets-editoriais.html` documenta os dois blocos HTML de dados, já incluídos na Home. O arquivo histórico `topbar-relogio.js` não é utilizado pelo novo header.

Menu usa dialog nativo; busca usa GET `s` e `post_type=post`. Relógio usa Intl em America/Sao_Paulo. WhatsApp apenas monta o link com título e URL canônica, sem envio automático.

## APIs públicas

- `api.open-meteo.com/v1/forecast`: quatro cidades fixas, sem pedir geolocalização. Horas locais de hoje, temperatura e código meteorológico; máximas/mínimas por período. Atribuição na interface e link de previsão para https://www.climatempo.com.br/.
- `economia.awesomeapi.com.br/json/last/USD-BRL,EUR-BRL`: bid e pctChange, timestamp da fonte.
- `api.hgbrasil.com/finance?format=json-cors`: results.stocks.IBOVESPA.points/variation; a resposta pública verificada não libera CORS para o Giro73 sem chave, portanto o fallback do índice continua necessário. Não alegar cotação ao vivo do índice até habilitar fonte acessível. Não expor chaves secretas no JavaScript para resolver isso.

Cache local contém somente dados públicos: clima 15 min, cotações 5 min. Não há cache estático com números financeiros de exemplo. Fetch sem credenciais, timeout de 6,5 s e carga perto da viewport, depois do load. Falhas mantêm traços e texto de indisponibilidade. Índice sempre em pontos; moedas em reais. Não importar scripts de widgets externos.

Depois de publicar, limpar LiteSpeed via `wp_clear_cache`, scope site para alterações globais, respeitando o limite de uma vez a cada cinco minutos; para correções finais, scope urls. Respeitar verify.after_seconds e conferir URLs canônicas sem cache buster. Verificar Home e artigo em desktop/mobile, menu por teclado, formulário de busca, créditos e fontes antes de anunciar conclusão.
