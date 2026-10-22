# Home Giro73 — manchete, feed e sidebar

Registro histórico. A estrutura vigente e a pendência de fotografias estão em [12-home-editorial-branca.md](12-home-editorial-branca.md).

Estrutura publicada em **22/10/2026**. Substitui a grade descrita no relatório 10. Usa blocos nativos WordPress e o CSS global de `implementacao-wp/css-g1-techtudo-ultra-leve.css`.

## Hierarquia automática

- **Desktop, a partir de 1.024 px:** abertura 70/30, com intervalo de 24 px. À esquerda, a notícia mais recente em card grande, imagem 16:9 acima do título. À direita, duas secundárias empilhadas, com os limites superior e inferior alinhados ao destaque.
- **Abaixo da abertura:** feed horizontal à esquerda, também em 70/30 com a sidebar. Cada item mostra miniatura, editoria, título preto, data e resumo. A coluna direita contém os blocos de apoio.
- **Mobile/tablet:** os grupos ficam em uma coluna. Em telas menores que 640 px, as secundárias usam miniatura à esquerda para uma apresentação compacta; o feed também mantém esse formato.
- **Mais lidas:** bloco reservado, conforme decisão do responsável. Sem lista inventada ou classificação por data apresentada como popularidade.
- **Publicidade:** espaço identificado na sidebar, com altura reservada de 250 px. Nenhum código ou conta de anúncios foi ativado por este ajuste.

O template `hostinger-ai-theme//front-page` foi exportado para `implementacao-wp/home-grade-automatica.html`. São três `wp:query`, todos com filtro das cinco editorias da Fase 1 e ordem decrescente de publicação:

| Grupo | Itens | Offset | Artigos atuais |
|---|---:|---:|---|
| Manchete principal | 1 | 0 | 24 |
| Secundárias | 2 | 1 | 23 e 22 |
| Feed | até 12 | 3 | 21 e 20 |

A Home se reorganiza quando outro post elegível é publicado. Os grupos não repetem matérias. O feed não usa paginação ou scroll infinito; conteúdos anteriores permanecem acessíveis pelos arquivos das editorias, com paginação nativa. Achadinhos permanece fora da consulta.

## Cards e identidade

Cards brancos, borda cinza sutil, **raio de 8 px**, padding de 16 px e sombra **`0 4px 12px rgba(0,0,0,.06)`**. Títulos pretos, peso 800, sem sublinhados; manchete maior que os demais títulos. As cores de editoria aparecem somente em um pequeno marcador da etiqueta, posicionada **fora da foto**.

O header amarelo `#FFEA02`, com menu roxo `#2C1067`, permanece visível durante a rolagem usando `position: sticky`, sem retirar espaço do fluxo. No mobile, apenas o menu pode rolar horizontalmente. O footer roxo mantém a logo branca e os cinco links institucionais. Logos, favicon e logo da organização no Schema foram preservados.

## Capas sem texto

As cinco capas foram substituídas por ilustrações geométricas originais: **nenhuma letra, título, URL, logo ou selo dentro das imagens**. O padrão do gerador foi alterado para `style: illustration`, sem operações de desenho de texto. As editorias continuam identificadas pelo HTML dos cards.

| Post | Mídia atual | WebP original em bytes |
|---|---:|---:|
| 20 — Notícias & Região | 84 | 12.748 |
| 21 — Dinheiro & Renda Extra | 85 | 8.228 |
| 22 — Tecnologia & Inovação | 86 | 11.070 |
| 23 — Esportes | 87 | 8.802 |
| 24 — Famosos & Entretenimento | 88 | 6.724 |

Todos os arquivos são **1200 × 675**, WebP abaixo de **50.000 bytes**, com título, alt e legenda pertinentes. Foram importados por `wp_upload_media` do Easy MCP e vinculados via `featured_media`. Os arquivos anteriores foram preservados. Os dados públicos estão em `implementacao-wp/marca/midias-wordpress.json`.

## Verificação após publicação

- LiteSpeed **Purge All** e cache de sitemap Rank Math limpos com sucesso após salvar template, CSS e imagens.
- Home HTTP 200 e cinco imagens públicas HTTP 200, com hashes idênticos aos arquivos locais, dimensões e peso conferidos. O sitemap de posts contém as cinco novas imagens.
- Navegador Chromium em **360, 390, 800, 1024 e 1440 px**, sem overflow horizontal da página. As cinco imagens carregaram em 16:9, sem etiquetas sobrepostas às fotos, falhas de recursos ou artigos repetidos.
- Em 1440 px, colunas da abertura e da seção inferior com **823,19 px / 352,81 px**, intervalo de 24 px e proporção 70/30. Destaque e conjunto de secundárias alinhados no topo e na base; feed e sidebar alinhados no topo.
- Header permanece no topo durante a rolagem; footer contém os cinco links institucionais; espaço publicitário mantém 250 px.
- Inspeção visual de desktop e mobile concluída. **CLS observado: 0** no carregamento mobile de laboratório; não é medição de campo ou resultado PageSpeed.
- As cinco gerações locais também passaram com a operação de desenho de texto desabilitada: o modo padrão não tenta escrever nas imagens.
- Texto bruto, títulos, slugs, datas, categorias e status dos cinco posts foram comparados antes/depois e preservados. Nenhum artigo ou página foi recriado.
- CSS publicado comparado com o arquivo do repositório; conteúdo canônico do template exportado após a aplicação.

Nenhuma biblioteca de layout, fonte remota ou script foi acrescentado. A meta PageSpeed Mobile 95+ não foi aferida nesta alteração.
