# Arquivos editoriais, expediente, Schema e ads.txt

Implantação 19, de 22/10/2026, usando blocos WordPress e o Code Snippets existente. Nenhum plugin adicional.

## Templates publicados

O tema ativo é de blocos. Os equivalentes de archive.php/category.php/search.php são templates nativos do tema `hostinger-ai-theme`, gerenciáveis pelo Editor do Site:

| Fonte | Destino |
|---|---|
| `category-editorial.html` | `hostinger-ai-theme//category` |
| `archive-editorial.html` | `hostinger-ai-theme//archive` |
| `search-editorial.html` | `hostinger-ai-theme//search` |
| `author-editorial.html` | `hostinger-ai-theme//author` |
| `sidebar-editorial.html` | Template part `hostinger-ai-theme//giro73-sidebar` |

Home e arquivos apontam para a mesma template part da sidebar, que reúne previsão das onze cidades, Mais lidas (seleção editorial), anúncio lateral e Economia. O script leve já existente desses widgets fica na própria parte e carrega uma vez por página. Não carregá-lo novamente no footer ou nos templates. O single continua sem sidebar.

Os feeds compartilham a geometria: miniaturas de **240×135 px** em telas a partir de 640 px, títulos pretos de 19 px, resumo em duas linhas e separadores. Em mobile, a miniatura reduz para preservar a leitura. Grade desktop `minmax(0,2.2fr) minmax(290px,1fr)`, intervalo de 48 px; abaixo de 1024 px, coluna única.

Cabeçalhos de categoria usam nome, descrição e borda de 3 px na cor da editoria. O cabeçalho é renderizado pelo shortcode `[giro73_cabecalho_arquivo]`, no servidor. Busca usa o termo real e o número real de resultados; consulta sem resultados tem mensagem própria e formulário nativo.

As consultas do feed usam **inherit: true e offset: 0**, preservando filtro da categoria, autor, termo e página atual. A busca é restrita a posts para não misturar páginas institucionais. Paginação numérica, Anterior e Próxima são blocos nativos, sem AJAX. O limite permanece o WordPress existente: dez posts por página. Com cinco artigos totais e um por categoria, ainda não há uma segunda página real para testar; a navegação aparece quando o volume ultrapassar o limite.

Arquivos de data mantêm o redirecionamento 301 para a Home já configurado no Rank Math. Busca permanece noindex/follow. O expediente principal, com bio própria, passa a index/follow.

## Expediente e Schema

`portal-estrutura.php` está ativo no snippet global **8**, prioridade 12.

O perfil público de Elder Mendonça Jr. mantém nome, identidade e URL `/author/elder-mendonca-jr/`. Recebeu a bio institucional fornecida pelo usuário e URL oficial em HTTPS. O box ao final dos artigos mostra **Redação Giro73**, responsável identificado, bio e link para o expediente. Não foi criado um usuário fictício.

O filtro `rank_math/json_ld` complementa o grafo que o Rank Math já emite:

- Converte a entidade Article/BlogPosting em **NewsArticle**, mantendo a identidade e as datas reais do post.
- Usa título editorial, excerto, categoria e autor reais. Preserva o responsável como Person identificado e vincula o publisher à Organization Giro73.
- Mantém uma única Organization, com `name: Giro73`, URL oficial e logo oficial obtido do ícone do site, mídia 56, PNG de 164×164.
- Preserva a imagem destacada de 1200×675. Não injeta outro script JSON-LD ou outra cópia do artigo.
- O meta robots dos cinco posts já continha **max-image-preview:large**; foi conferido e preservado.

Não alterar as datas para simular atualização editorial. Schema não comprova aprovação ou indexação pelo Google.

## Sitemap e robots

Rank Math permanece como gerador. Índice e mapas de posts, páginas e categorias respondem HTTP 200, com cinco URLs esperadas em cada mapa. XML conferido com parser, sem HTML de erro ou prefixo inválido.

`robots.txt` mantém User-agent: *, rastreamento do conteúdo público, proteção de wp-admin e exceção para admin-ajax.php. Declara explicitamente `Sitemap: https://giro73.com.br/sitemap_index.xml`.

## ads.txt e publisher futuro

`https://giro73.com.br/ads.txt` responde **HTTP 200, Content-Type text/plain; charset=utf-8**, por hook nativo `parse_request`, antes da consulta principal. Não precisa de arquivo físico, flush de rewrite ou regra de servidor. GET e HEAD suportados.

Enquanto não houver ID oficial, o conteúdo contém somente comentários de preparação. Nenhuma linha com ID fictício foi publicada.

Quando a conta fornecer o ID, abrir **Configurações → Leitura → AdSense — ID do publisher**, colar `pub-` seguido dos 16 dígitos reais e salvar. A opção também fica disponível a administradores no campo `g73_adsense_publisher_id` da REST Settings API. A rota passa a emitir:

```text
google.com, [ID REAL DO PUBLISHER], DIRECT, f08c47fec0942fa0
```

O salvamento invalida o cache LiteSpeed da URL. O ID é validado; nenhum segredo de API é necessário. Os comentários atuais não constituem uma autorização de venda nem substituem o cadastro da conta.

## Manutenção

A lógica editorial do single continua no snippet 7; publicidade no 6; cotações no 5. O snippet 8 adiciona somente os cabeçalhos de arquivo, ajuste de busca, Schema, robots do expediente e ads.txt.

Ao remover o snippet 8, também substituir os cabeçalhos shortcode dos templates e revisar ads.txt/Schema. Reverter a Home deve preservar exatamente uma sidebar e uma execução do script. Após mudanças globais, limpar LiteSpeed; após alterações de sitemap, incluir rank-math-sitemap e verificar URLs canônicas depois do intervalo indicado pela ferramenta.
