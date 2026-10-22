# Implantação WordPress — Giro73

Registro: 2026-10-21T23:37:16-03:00.

## Acesso e inventário

- Autenticação REST validada com Application Password. Easy MCP AI 2.2.2 também inicializado por HTTP, com 203 ferramentas anunciadas. Nenhuma credencial foi incluída no repositório.
- Antes: nenhum post, uma página padrão de privacidade em inglês como rascunho e uma categoria Uncategorized sem posts.
- Depois: cinco artigos publicados, cinco páginas institucionais publicadas e cinco categorias, cada uma com um artigo.
- Responsável editorial informado pelo usuário: Elder Mendonça Jr.; sede em Teixeira de Freitas (BA), cobertura do Extremo Sul da Bahia, Bahia e principais notícias do Brasil.
- Contato público informado pelo usuário: comercial@giro73.com.br. Não foi enviado email nem testada a entrega desse endereço.

## Categorias e configuração

| ID | Nome | Slug | Posts |
|---|---|---|---|
| 1 | Notícias & Região | `noticias-regiao` | 1 |
| 10 | Dinheiro & Renda Extra | `dinheiro-renda-extra` | 1 |
| 11 | Tecnologia & Inovação | `tecnologia-inovacao` | 1 |
| 12 | Esportes | `esportes` | 1 |
| 13 | Famosos & Entretenimento | `famosos-entretenimento` | 1 |

- A categoria padrão ID 1 foi renomeada para Notícias & Região, slug noticias-regiao; o default_category continua apontando para ela. Não existem Uncategorized ou Achadinhos entre as categorias atuais.
- Título do site Giro73, descrição regional e fuso America/Bahia configurados. Perfil do autor ID 1 identificado como Elder Mendonça Jr., com bio factual e slug elder-mendonca-jr.
- Descrições de categoria, títulos SEO e metadescrições foram enviados ao Rank Math; o metadado de uma categoria foi relido pelo Easy MCP e os metadados dos artigos foram confirmados no HTML público.
- Identidade do site configurada como Organization/Giro73, sem endereço físico, telefone, registro empresarial ou qualificações inventados. Páginas e posts incluídos no sitemap; referências a imagens destacadas habilitadas.

## Artigos publicados

| ID | Artigo | Palavras no conteúdo | Links para outros pilares |
|---|---|---|---|
| 20 | [Economia do Extremo Sul da Bahia: infraestrutura e desenvolvimento](https://giro73.com.br/extremo-sul-bahia-economia-infraestrutura-desenvolvimento/) | 766 | 21, 22 |
| 21 | [Planejamento financeiro: como organizar renda extra e avaliar crédito com segurança](https://giro73.com.br/planejamento-financeiro-renda-extra-credito-seguro/) | 788 | 20, 22 |
| 22 | [Inteligência artificial no trabalho: usos práticos e cuidados para pequenos negócios](https://giro73.com.br/inteligencia-artificial-trabalho-pequenos-negocios/) | 788 | 21, 24 |
| 23 | [Futebol brasileiro e baiano: como acompanhar o calendário e planejar a torcida](https://giro73.com.br/futebol-brasileiro-baiano-calendario-guia-torcedor/) | 742 | 20, 24 |
| 24 | [TV e streaming no Brasil: como entender tendências e escolher o que assistir](https://giro73.com.br/tv-streaming-brasil-tendencias-entretenimento/) | 755 | 21, 22, 23 |

Os cinco textos são guias de contexto e serviço. Não foram publicados resultados de jogos, tabelas com datas não conferidas, rankings atuais de audiência, indicadores econômicos atuais ou propostas comerciais de crédito. O conteúdo informa o apoio de IA e a responsabilidade editorial.

## Páginas publicadas

- [Política de Privacidade](https://giro73.com.br/politica-de-privacidade/) — ID 3.
- [Sobre Nós](https://giro73.com.br/sobre-nos/) — ID 7.
- [Política Editorial](https://giro73.com.br/politica-editorial/) — ID 8.
- [Termos de Uso](https://giro73.com.br/termos-de-uso/) — ID 10.
- [Contato](https://giro73.com.br/contato/) — ID 11.

A página de privacidade ID 3 foi atualizada em português, substituindo o texto padrão em inglês. A política descreve a operação inicial; novos cookies, medição, formulários e publicidade exigem revisão correspondente. As páginas não constituem parecer jurídico nem asseguram aprovação do AdSense.

## Layout e desempenho

- Tema ativo mantido: Hostinger AI. Personalizações feitas em modelos nativos e estilos globais do WordPress; nenhum arquivo do tema foi editado e nenhum plugin adicional foi instalado por esta execução.
- Capa com cinco seções de editoria em grade responsiva: uma coluna mobile, duas intermediárias e três desktop. Cada consulta retorna até três posts da categoria em ordem de publicação. Categorias e artigos possuem modelos próprios; cabeçalho e rodapé compartilhados.
- Cores semânticas: #C4170C, #005A9C, #0077CC, #06AA48 e #FF6700. Tipografia de sistema, leitura mobile de 18px/28,8px, sem slider ou biblioteca de layout adicional.
- CSS próprio: 8056 bytes, 2289 bytes gzip. O orçamento é do CSS próprio, não do total da página.
- Hostinger Reach desativado para retirar scripts de newsletter carregados na capa vazia. Plugin não removido e dados não apagados. LiteSpeed Cache e Rank Math mantidos. Elementor foi observado ativo durante a sessão, mas não foi utilizado para construir o layout nem alterado.
- Estilos e modelos continuam no banco WordPress. O CSS publicado corresponde ao arquivo css-g1-techtudo-ultra-leve.css deste diretório.

## Validação realizada

- HTTP 200 em 17 URLs: capa, índice de sitemap, cinco páginas, cinco artigos e cinco categorias. Cada página HTML tem um H1; canonical e robots index/follow conferidos. Artigos com metadescrição, links entre pilares e referências externas.
- Índice XML e post-sitemap.xml acessíveis; os cinco artigos foram encontrados no sitemap de posts.
- Navegador: capa mobile em 390px e artigo em 360px sem overflow horizontal; grade desktop em 1440px; cinco cards renderizados; fonte do corpo 18px. Revisão visual de capturas realizada.
- O navegador não reconheceu inicialmente a CA do proxy. A renderização foi validada entregando ao navegador recursos obtidos por urllib com TLS verificado, sem desabilitar verificação de certificados. Isso valida layout, não latência real de navegação.
- CLS observado igual a zero no cenário de renderização testado, sem imagens nem anúncios. Não é dado de campo, teste PageSpeed ou garantia após implantação de publicidade. Nota PageSpeed Mobile 95+ ainda não medida.
- Cache LiteSpeed e cache de sitemap limpos pelos mecanismos próprios; cache de objeto não foi esvaziado.

## Search Console e pendências concretas

- Propriedade https://giro73.com.br/ acessível pelo Easy MCP com nível siteFullUser.
- Consulta de 22/09 a 19/10/2026: nenhuma linha em Search nem Discover. Não há base observada para declarar volume por pauta, CPC, crescimento, CTR ou ganhos.
- O Search Console ainda lista um envio anterior do sitemap com isPending=true e um erro. O XML atual responde HTTP 200; aguardar nova leitura e verificar/reencaminhar o sitemap no Search Console se o erro persistir. Não foi reivindicada indexação imediata nem enviado pedido de aprovação do AdSense.
- Biblioteca de mídia vazia no inventário. Os cinco posts estão sem imagem destacada; providenciar imagens pertinentes e licenciadas de pelo menos 1.200px, com versões WebP responsivas e dimensões, antes de tratar a preparação visual para Discover como concluída.
- A amostra de consultas a fontes oficiais externas foi bloqueada pelo proxy da máquina. Links externos foram incluídos como referências para acompanhamento; não se afirmou que essas páginas foram consultadas ao vivo nem que dados temporais foram confirmados.
- Consentimento/CMP, inventário detalhado de cookies, retenção por fornecedor e integração publicitária precisam ser conferidos conforme os serviços realmente ativados. Não há AdSense publisher ID configurado nesta execução.
- O calendário de 15 dias em Markdown e CSV está preparado com IDs reais. A fila depende de apuração e revisão; nenhum disparo futuro foi agendado ou ativado.

## Próximas ações priorizadas

1. Incluir imagens licenciadas nos cinco pilares e testar novamente mobile/LCP/CLS.
2. Conferir a nova leitura do sitemap pelo Google e a cobertura de indexação; corrigir avisos reais da propriedade.
3. Apurar as pautas locais e de serviço da fila, publicar gradualmente e atualizar os links de retorno dos pilares.
4. Medir PageSpeed Mobile e Core Web Vitals com conteúdo e recursos finais; manter inventário de scripts.
5. Revisar operação de dados e requisitos reais da conta antes de solicitar o AdSense. Nenhum número fixo de artigos, pontuação de plugin ou página institucional garante aprovação.
