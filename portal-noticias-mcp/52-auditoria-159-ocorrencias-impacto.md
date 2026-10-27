# Auditoria nova: 159 erros não equivalem a 159 causas

Capturas fornecidas em 27/10/2026: relatório SE Ranking de 13:45:26, comparado ao de 12:17:38; fuso da interface não confirmado. A pontuação é 83, com 53 páginas rastreadas, 159 erros e um aviso. Este é um relatório novo: não foi descartado como simples registro de 26/10.

## Leitura das contagens

| Regra | Ocorrências |
|---|---:|
| Respostas HTTP 4xx | 52 |
| URLs 4xx em sitemap XML | 52 |
| URLs noindex em sitemap XML | 52 |
| Arquivo JavaScript 4xx/5xx | 1 |
| Arquivo CSS 4xx/5xx | 1 |
| Ausência de links de entrada | 1 |
| Total de erros | **159** |

Os três grupos de 52 somam 156 ocorrências e parecem se sobrepor nas URLs problemáticas. A captura agregada, sem a lista completa por regra, não demonstra identidade perfeita entre esses conjuntos; não se contou como 156 defeitos independentes. Há ainda um aviso de link externo 4xx, totalizando 160 ocorrências.

O próprio gráfico atribui a não indexabilidade a respostas não-200: robots.txt, metatag, X-Robots e canonical não aparecem como causa nesse gráfico. A classificação de erro/noindex pelo rastreador não demonstra 52 configurações atuais de noindex no Rank Math. Isso não torna os 4xx irrelevantes: o SE Ranking considerou 52 de 53 páginas não acessíveis/indexáveis naquela coleta.

## Impacto

A nota e a contagem do SE Ranking não são fatores de classificação nem penalidades emitidas pelo Google. Se páginas públicas realmente retornam 4xx a leitores, Googlebot ou ao acesso de avaliação do AdSense, podem impedir leitura/rastreamento e prejudicar descoberta, tráfego e avaliação. Se o problema estiver restrito ao serviço de auditoria, não prova que os demais agentes recebem a mesma resposta. Não foi diagnosticada penalidade manual ou queda de posições a partir dessas capturas.

## Conferência independente desta etapa

Home, `/category/noticias-regiao/` e `/politica-editorial/` foram consultadas por GET e HEAD com três identificações: navegador, SEBot-WA e Googlebot. **18 respostas HTTP 200**. Os pedidos não partiram dos IPs reais do Google nem do SE Ranking: User-Agent não autentica a origem e esses resultados não anulam a falha observada pelo serviço.

Consulta adicional de URL Inspection no Search Console:

- Notícias & Região: `PASS`, “Enviada e indexada”, rastreamento permitido e fetch bem-sucedido. O último crawl informado é de 23/10 às 00:35 UTC (22/10 às 21:35 em America/Bahia); a inspeção não constitui um novo teste ao vivo do Google.
- Artigo Fies: “O Google não reconhece o URL”, sem crawl informado. Isso não identifica a causa nem permite afirmar que o Google recebeu 403 nesse artigo.

Logo, não há base para declarar que todo o portal esteja fora do Google, mas também não se certifica indexação de todas as matérias.

## Próximo diagnóstico necessário

O responsável forneceu três URLs da execução nova: a matéria Itabela/Itagimirim (Copa CONDESC), a matéria de Supercross e Sobre Nós. Os códigos individuais exibidos nessa nova coleta não foram copiados; o resumo só mostra 4xx, portanto não se presumiu que todos sejam 403 como na captura anterior.

As três URLs foram testadas por GET/HEAD e pelas três identificações de User-Agent, com **18 respostas HTTP 200**, separadas das primeiras 18 verificações. No Search Console, Sobre Nós aparece como “Enviada e indexada”, com último crawl de 23/10/2026 às 05:23 UTC; a matéria da Copa CONDESC aparece como URL desconhecida, sem crawl ou causa informada. Não foi inspecionada a indexação do Supercross no Google. Esses dados não confirmam acesso dos IPs reais dos robôs no instante da auditoria.

Com essa evidência e o retorno da Hostinger relatando acessos SEBot-WA bem-sucedidos, o próximo diagnóstico útil é solicitar ao SE Ranking os dados das requisições que originaram os erros. Não repetir indefinidamente purgas ou exclusões de conteúdo sem uma causa reproduzida. Texto de solicitação para o responsável encaminhar, não enviado por este agente:

> A auditoria de giro73.com.br em 27/10/2026 às 13:45:26 (fuso a confirmar) aponta 52 respostas HTTP 4xx. As URLs abaixo respondem 200 em testes externos por GET e HEAD, inclusive com User-Agent SEBot-WA. A Hostinger relata acessos do rastreador bem-sucedidos e não localizou 403 correspondente. Precisamos do código exato, horário/fuso, IP de saída, URL final e resposta recebida (cabeçalhos e conteúdo ou identificação de requisição) para correlacionar com os logs e localizar a camada da divergência.

- `https://giro73.com.br/itabela-brilha-na-estreia-da-copa-condesc-e-vence-itagimirim-por-3-a-2-no-manzolao/`
- `https://giro73.com.br/copa-brasil-de-supercross-em-itabela-velocidade-e-emocao-na-4a-etapa-com-r-35-mil-em-premiacao/`
- `https://giro73.com.br/sobre-nos/`

Se forem 403, obter do SE Ranking URL, horário/fuso, IP de saída e resposta recebida (cabeçalhos/conteúdo ou identificação da requisição) e cruzar com a hospedagem. A verificação trazida pelo responsável informa acessos SEBot-WA bem-sucedidos e nenhum 403 identificado no WAF/origem da Hostinger; a divergência precisa ser localizada, não resolvida por novas liberações indiscriminadas ou exclusão de páginas legítimas.

Se houver 404/410, separar remoções intencionais de links incorretos. Investigar o JS e CSS pelos caminhos exatos e corrigir a página sem links internos depois de confirmar a URL. Não comprar backlinks nem remover conteúdo válido por esse alerta isolado.

Nenhum firewall, status editorial, sitemap, cache, redirecionamento ou configuração de auditoria foi modificado nesta etapa. Não houve nova consulta paga de pesquisa SEO. Evidências: [validação desta conferência](implementacao-wp/validacao-159-ocorrencias.json).
