# Data, hora e editoria no feed

Aplicado em 26/10/2026 no Snippet 8, sem editar as datas dos artigos ou substituir os templates ao vivo.

Exemplo: **Há 5 horas · 26/10/2026 às 12h41 — Em Política**.

- Os blocos `core/post-date` com a classe `g73-news-date` da Home, editorias/arquivos e busca recebem a mesma linha editorial.
- A publicação é obtida do contexto do post do Query Loop. Não se usa a data de alteração como se fosse uma nova publicação.
- Horário absoluto no fuso `America/Bahia`, com `<time datetime>` incluindo o offset `-03:00` e título identificando o fuso.
- A categoria principal do Rank Math é usada quando pertence ao artigo; na ausência, utiliza-se uma categoria atribuída. O nome é um link para a editoria.
- No celular, tempo relativo e categoria ficam acima da data e hora exatas. Os grupos evitam pontuação isolada e rolagem horizontal.
- PHP entrega a informação mesmo sem JavaScript. Um relógio de 978 bytes, sem dependências, atualiza o tempo relativo a cada minuto e ao retornar à aba; a data absoluta permanece estável. Isso evita congelar “Há 1 hora” no HTML servido pelo cache.
- O espaço do tempo relativo é reservado para manter a disposição da linha durante a atualização. A verificação de layout do componente não equivale a uma nova medição PageSpeed/Core Web Vitals.
- As versões dos templates no repositório incluem hora no formato de fallback. O filtro é aplicado aos blocos já existentes no site.

Validação: nove verificações PHP de limites, publicação original, contexto do Query Loop, fuso, categoria e preservação de blocos alheios; teste JavaScript com HTML de cache antigo, valor inválido, data futura e aba oculta; HTTP 200 na Home, Política e busca. Componente conferido em 1180 px e 390 px.

Resultados: [validacao-data-hora-feed.json](implementacao-wp/validacao-data-hora-feed.json).
