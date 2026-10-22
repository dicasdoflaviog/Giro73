# Giro73 — design system oficial e execução

Data: 22/10/2026. Site: https://giro73.com.br/. Este registro distingue alterações publicadas de preparações locais.

## Publicado e verificado

- CSS adicional do tema de blocos, registro WordPress `global-styles/12`, idêntico ao arquivo `implementacao-wp/css-g1-techtudo-ultra-leve.css`.
- Nove variáveis `--giro-*` oficiais; fundo de página `#F4F5F8`.
- Cabeçalho amarelo `#FFEA02`, borda inferior roxa de 3 px; menu roxo, peso 800, sem sublinhado e hover magenta.
- Rodapé roxo `#2C1067`, texto branco e links ciano/branco, incluindo as cinco páginas institucionais existentes.
- Cards brancos, raio de 10 px, sombra leve, títulos pretos peso 800 sem sublinhado. Barras superiores magenta, azul, roxo, ouro e laranja; Tecnologia inclui borda ciano e Esportes usa preto sobre ouro.
- Cinco consultas nativas da Home e seus cinco blocos `wp:post-featured-image` preservados. Todas as imagens atuais carregaram, sem corte de proporção, em 16:9.
- Cinco capas com a paleta oficial publicadas e vinculadas aos posts existentes: 1200 × 675 WebP, entre 21.512 e 27.284 bytes. A assinatura do selo continua pendente do original.
- Logo redonda oficial enviada pela redação à Biblioteca, mídia **56**, configurada como `site_icon` e logo organizacional do Rank Math (`knowledgegraph_logo`/`knowledgegraph_logo_id`). Favicon e `Organization.logo` conferidos no HTML público, logo em 164 × 164. O módulo Local SEO do Rank Math foi ativado para emitir a logo no Schema; o tipo permaneceu `Organization`, sem horários ou endereço inventados.
- Textos, URLs, categorias e status dos posts/páginas preservados. Nenhum post ou página criado.
- Purge All do LiteSpeed e limpeza do cache de sitemap Rank Math confirmados pelo Easy MCP. Home canônica retornou HTTP 200 e o CSS novo após a limpeza.

## Validação de interface e peso

Chromium mobile 390 px: sem overflow horizontal; títulos e menu com peso computado 800 e `text-decoration-line: none`; cinco cards com cores, raio e imagens carregadas conferidos no DOM. Artigo de Tecnologia manteve fonte de 18 px e entrelinha de 28,8 px.

- CLS observado no teste: **0**; não é dado de campo.
- Transferência inicial com as novas capas e favicon: **145.557 bytes**, 11 recursos.
- Transferência após percorrer a Home e carregar as imagens: **182.073 bytes**, 13 recursos.
- TTFB mediano observado: **0,382 s** em cinco requisições da Home, sem cache-busting; cache MISS seguido de HIT. Medição pontual a partir deste ambiente, não dado de campo.
- Medição soma corpos comprimidos gzip/deflate dos recursos requisitados pelo navegador; não inclui cabeçalhos HTTP. Respostas foram obtidas por HTTPS com TLS validado; não se desativou a validação de certificados.
- Nenhum script ou fonte remota acrescentado. Permanecem CSS do tema e jQuery/jQuery Migrate bloqueantes já existentes. **PageSpeed 95+ permanece meta não aferida.**

Contraste: roxo/branco **15,25:1**; roxo/amarelo **12,36:1**; preto/ouro **12,62:1**; ciano/roxo **10,81:1**. O magenta de hover no amarelo, solicitado na identidade, mede **3,11:1**; não representa uma declaração de conformidade AAA para o menu inteiro.

## Marcas ainda pendentes

Os quatro arquivos aparecem visualmente no chat, mas os anexos não chegaram como binários ou identificadores de download acessíveis no ambiente. A redação ofereceu enviá-los à Biblioteca. A versão redonda já foi encontrada e aplicada; os outros três originais ainda não estavam disponíveis na última consulta autenticada.

| Ativo oficial | Aplicação pendente |
|---|---|
| `Property 1=H_GIRO73.png` | Upload, logo do cabeçalho e `site_logo` |
| `Property 1=H_BRANCO.png` | Upload e logo do rodapé |
| `Property 1=G73_V.png` | Adicionar assinatura às cinco capas já atualizadas e substituir seus vínculos nos posts existentes |

Os cabeçalhos/rodapés continuam exibindo o nome Giro73 em texto até os originais horizontais estarem acessíveis. O favicon e o Schema já usam a marca redonda original. A cópia pública servida pelo WordPress foi preservada em `implementacao-wp/marca/LOGO REDONDA GIRO73.png` para reutilização; não foi redesenhada.

O gerador foi atualizado para a paleta oficial e para receber `--seal` com o PNG original, preservando a marca sem redesenho ou recoloração. Sem esse parâmetro, informa a pendência e registra `official_seal: false` no manifesto. As cinco capas sem selo foram inspecionadas visualmente, publicadas e verificadas por HTTPS. A composição com o selo ainda não foi executada.

| Post existente | Nova mídia | WebP original em bytes |
|---|---|---|
| 20 — Notícias & Região | 51 | 27.284 |
| 21 — Dinheiro & Renda Extra | 52 | 25.874 |
| 22 — Tecnologia & Inovação | 53 | 25.624 |
| 23 — Esportes | 54 | 21.718 |
| 24 — Famosos & Entretenimento | 55 | 21.512 |

Os quatro sitemaps retornaram HTTP 200; o sitemap de posts inclui as cinco novas imagens. Os cinco artigos apresentam `og:image` atualizado, 1200 × 675, e `max-image-preview:large`. `robots.txt` permite os conteúdos e imagens ao Googlebot e Mediapartners-Google.

## Retomada após disponibilização dos arquivos

1. Localizar os três originais restantes na Mídia, baixar por HTTPS, conferir dimensões/transparência e inspecionar. Reutilizar os uploads feitos pela redação e preencher seus metadados.
2. Atualizar os template parts existentes, preservando menus e informações institucionais. Inserir logos com largura/altura declaradas e tamanho visual estável.
3. Configurar `site_logo` com a horizontal colorida. Preservar o favicon e a logo organizacional redonda já configurados e verificar o JSON-LD servido.
4. Executar `gerar-upload-capas.py --seal '/caminho/Property 1=G73_V.png' --upload --user admin`, com senha no prompt/gerenciador de segredos. Conferir manifesto, metadados, cinco vínculos e limite de bytes.
5. Limpar LiteSpeed e cache de sitemap; verificar Home, artigos, favicon, Schema, Open Graph, imagens responsivas e layout mobile com as marcas efetivas.

As instruções operacionais do gerador estão em `implementacao-wp/README-capas.md`. Nenhuma credencial foi salva no repositório.
