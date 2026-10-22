# Home com grade automática e apresentação limpa

Registro histórico. A estrutura vigente está em [11-home-hero-feed-sidebar.md](11-home-hero-feed-sidebar.md), com abertura 70/30, feed horizontal e capas sem texto ou selo.

Aplicado ao Giro73 em 22/10/2026. Substitui a divisão da Home em cinco seções independentes.

## Blocos publicados

O template `hostinger-ai-theme//front-page` agora contém **uma consulta nativa WordPress**, com 12 posts por página, ordem decrescente de publicação e filtro para as cinco editorias da Fase 1. A categoria Achadinhos não integra a consulta. Paginação nativa e mensagem de ausência de resultados estão incluídas.

Cada item tem imagem destacada 16:9, etiqueta dinâmica de categoria sobre a foto, título H2 com link, data e resumo. O primeiro post recebe título maior. O nome da editoria vem de `wp:post-terms`; não depende de editar a Home a cada publicação. Os template parts de header e footer com as logos oficiais foram preservados.

O conteúdo canônico salvo pelo WordPress foi exportado para `implementacao-wp/home-grade-automatica.html`. O CSS publicado corresponde ao arquivo `implementacao-wp/css-g1-techtudo-ultra-leve.css`.

## Distribuição sem célula vazia no fim da linha

- **Desktop:** seis trilhas CSS formam três colunas de cards. Com quantidade divisível por três, todos ocupam duas trilhas. Se o resto for dois, o primeiro ocupa quatro trilhas, criando abertura de 2/3 + 1/3; se o resto for um, o primeiro ocupa a largura inteira. Os demais completam linhas de três.
- **Tablet:** duas colunas; com número ímpar, o primeiro ocupa a largura inteira e os demais formam pares.
- **Mobile:** uma coluna, mantendo a ordem de publicação.

Com os cinco artigos atuais, a abertura mostra o primeiro em 2/3 da largura, o segundo ao lado e os outros três em uma linha completa. Seletores CSS `:first-child:nth-last-child(...)` ajustam a distribuição pelo total real de itens; não há JavaScript de layout, conteúdo duplicado ou placeholders para preencher espaço. As margens verticais herdadas dos itens foram zeradas para alinhar o topo dos cards.

## Cards e imagens

Fundo branco, borda cinza de 1 px, raio de 10 px e sombra `0 4px 12px rgba(44,16,103,0.06)`. Títulos pretos, peso 800, sem sublinhado; datas cinza e links de leitura pretos. O destaque usa título de 28 px no desktop, ante 20 px nos demais.

A etiqueta é branca, pequena, com texto preto e um ponto na cor oficial da editoria. As cores de categoria não formam bordas ou tarjas no card. Apenas a primeira categoria nativa é exibida quando houver mais de uma; a automação deve atribuir uma editoria principal por matéria.

As faixas também existiam dentro das imagens antigas. As cinco capas foram refeitas com arte neutra, sem tarja superior, selo “GUIA” ou categoria gravada na imagem, mantendo a ilustração de cada assunto e o selo oficial G73. O gerador usa esse estilo limpo por padrão nos próximos disparos.

| Post existente | Nova mídia | WebP em bytes |
|---|---|---|
| 20 — Notícias & Região | 77 | 22.866 |
| 21 — Dinheiro & Renda Extra | 78 | 21.346 |
| 22 — Tecnologia & Inovação | 79 | 20.760 |
| 23 — Esportes | 80 | 18.432 |
| 24 — Famosos & Entretenimento | 81 | 16.986 |

Todos os arquivos permanecem em **1200 × 675**, WebP abaixo de 50.000 bytes, com alt/título/legenda pertinentes ao artigo. Os uploads usaram `wp_upload_media` do Easy MCP. As mídias anteriores foram preservadas. Texto, título, slug, categoria, data e status dos cinco posts foram comparados antes/depois e não mudaram.

## Verificação

- LiteSpeed Purge All e cache de sitemap Rank Math limpos após aplicar blocos, CSS e capas.
- Home e cinco imagens retornaram HTTP 200; os arquivos públicos correspondem aos locais, sem faixa colorida na parte superior.
- Navegador em **390, 800 e 1440 px**: **36 cenários**, de 1 a 12 itens por largura, com todas as linhas preenchidas e sem overflow horizontal. A simulação ocorreu somente no DOM local do navegador; não criou posts no WordPress.
- Cinco cards reais com imagem carregada em 16:9, borda cinza de 1 px, sombra solicitada, títulos pretos e etiquetas nas cores corretas. No desktop, o destaque mede 792 px e os cards regulares 384 px, com intervalo de 24 px.
- Inspeção visual de mobile e desktop concluída. **CLS observado: 0** no carregamento mobile; não é dado de campo nem medição PageSpeed.

Nenhuma biblioteca de layout, fonte remota ou script foi acrescentado. O ajuste utiliza blocos nativos e CSS. PageSpeed 95+ permanece meta não aferida.
