# Fechamento da Etapa 1: arquivos, expediente, Schema e ads.txt

Aplicado em **22/10/2026**, sem instalar plugins extras. Tema de blocos e rotinas existentes preservados.

## Entregue

1. Templates nativos **category, archive, search e author**, com cabeçalhos editoriais, consultas herdadas corretamente, feed horizontal, paginação nativa e sidebar oficial compartilhada com a Home. Miniaturas desktop 240×135 e grid 2,2fr/1fr com mínimo lateral de 290 px e gap de 48 px.
2. Expediente da Redação com a bio institucional solicitada e Elder Mendonça Jr. identificado como responsável. Perfil mantém nome e URL originais. Schema **NewsArticle + Organization**, logo oficial e max-image-preview:large nos cinco artigos. O expediente foi tornado indexável; a busca continua noindex.
3. Índice Rank Math e mapas de posts, páginas e categorias **HTTP 200**, contendo as cinco URLs esperadas de cada tipo. robots.txt HTTP 200, conteúdo público rastreável e sitemap explicitamente declarado.
4. **ads.txt HTTP 200, text/plain**, pronto para o ID real. Atualmente só comentários, sem publisher inventado. Campo em **Configurações → Leitura → AdSense — ID do publisher**.

A sidebar é uma template part única, `giro73-sidebar`, com clima das onze cidades, cinco matérias selecionadas, anúncio e economia. O single mantém a leitura sem sidebar da implantação 17. Nenhuma segunda cópia de script dos widgets foi adicionada.

O arquivo `portal-estrutura.php`, snippet **8**, contém as rotinas novas. Guias e correspondência dos arquivos: `implementacao-wp/README-arquivos-schema.md`.

## Integridade

Conteúdo, títulos, slugs, datas, categorias e imagens dos artigos/páginas não foram reescritos. O perfil recebeu a bio institucional e URL oficial em HTTPS. Durante a configuração, um arquivo temporário de template foi usado por engano no perfil e alterou seu slug; a URL original foi restaurada e conferida antes da validação final. Não permaneceu alteração de identidade ou de vínculo dos posts.

Arquivos de data continuam com o redirecionamento 301 existente do Rank Math para a Home. A paginação foi publicada com blocos nativos e limite existente de dez posts; os cinco artigos atuais ainda não geram página 2.

## Validação final

- LiteSpeed e cache do sitemap Rank Math limpos; cache do expediente atualizado após o ajuste de indexação.
- Navegação real pelos cinco links superiores em 390 e 1440 px: nome, cor e filtro de posts corretos em todas as editorias.
- Busca enviada pelo formulário do cabeçalho: termo `torcida` retornou os dois artigos correspondentes, conferidos com a busca nativa. Consulta sem resultado apresentou a mensagem e o formulário adequados.
- Larguras 360, 390, 800, 1024 e 1440 px sem rolagem horizontal. No desktop, miniaturas 240×135 px, gap de 48 px e sidebar com mínimo de 290 px. Capturas mobile e desktop revisadas.
- Sidebar única com onze cidades, anúncio e uma cópia do script; clima e economia alcançaram o estado carregado. Nenhum erro de JavaScript nos testes. Cotações dependem da disponibilidade das fontes externas e preservam o fallback existente.
- Expediente indexável com os cinco artigos, bio e responsável; link do autor validado em uma matéria, que continua sem sidebar.
- Schema conferido nos cinco artigos: um NewsArticle por matéria, Organization com logo oficial acessível, autor e datas preservados e diretiva max-image-preview:large.
- Sitemap index e mapas de posts, páginas e categorias: HTTP 200, XML válido e cinco URLs esperadas por tipo. robots.txt e ads.txt: HTTP 200; ads.txt também validado via HEAD e com Content-Type text/plain.
- Templates, CSS e snippet publicados comparados com os arquivos versionados. Integridade dos cinco posts e cinco páginas confirmada, sem alteração de conteúdo ou mídia. Nenhuma credencial incluída no repositório.

Não foi medida uma nova nota PageSpeed nesta etapa. Estrutura válida e Schema correto não garantem aprovação no AdSense nem distribuição no Discover.
