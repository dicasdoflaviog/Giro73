# Identidade visual Giro73 aplicada

> O refinamento posterior da Home e das capas está em [10-home-grade-limpa.md](10-home-grade-limpa.md).

Data: 22/10/2026. Site: https://giro73.com.br/.

## Recuperação e envio

As logos colorida/branca e o selo já estavam gravados na pasta de uploads e registrados na biblioteca como anexos 57, 58 e 62. Foram baixados por HTTPS e inspecionados. O erro relatado na interface não impediu a recuperação desses originais.

O script `implementacao-wp/otimizar-marca.py` produziu PNGs e WebPs transparentes com nomes simples: `logo-giro73`, `logo-branca` e `selo`. Preservou dimensões, cores e pixels RGBA, sem ampliar as imagens pequenas. As versões WebP são lossless; os arquivos de marca estão versionados em `implementacao-wp/marca/`.

O terminal da nuvem não dispõe de acesso ao sistema de arquivos, SSH/SFTP ou SQL da hospedagem; o WP-CLI do Easy MCP está desativado. Por isso, **não houve gravação pelo terminal no disco remoto nem SQL direto em `wp_posts`**. O envio remoto foi feito com **`wp_upload_media` do Easy MCP**, que criou os anexos no WordPress. Não se utilizou `/wp-json/wp/v2/media` para subir os arquivos. A REST API foi usada para consultar e atualizar metadados, vínculos, configurações e template parts existentes.

Os oito arquivos importados foram confirmados como `type: attachment`, `image/webp`, com caminho em `wp-content/uploads/2026/10/`. A releitura pública retornou HTTP 200 e bytes idênticos aos arquivos locais; transparência e dimensões foram validadas.

## Aplicações publicadas

| Aplicação | Mídia | Arquivo | Dimensões | Bytes |
|---|---|---|---|---|
| Header e `site_logo` | 63 | `logo-giro73.webp` | 221 × 59 | 2.688 |
| Footer | 64 | `logo-branca.webp` | 221 × 59 | 1.306 |
| Selo oficial disponível na Mídia | 65 | `selo.webp` | 125 × 58 | 1.964 |

Header: fundo `#FFEA02`, borda roxa de 3 px, logo colorida à esquerda e navegação roxa peso 800, sem sublinhado. Footer: fundo `#2C1067`, logo branca e links institucionais ciano/branco. As duas logos têm atributos `width="221" height="59"` e apresentação proporcional para reservar espaço antes do carregamento.

O CSS publicado já continha a paleta oficial e foi conferido como idêntico ao arquivo `implementacao-wp/css-g1-techtudo-ultra-leve.css`. Foram preservados os nove tokens `--giro-*`, os cards e as cores das cinco editorias. Nenhum plugin, fonte ou script adicional foi instalado.

O favicon e a logo `Organization.logo` do Rank Math permanecem associados à marca redonda original, mídia 56. O JSON-LD público foi verificado após a aplicação da logo principal.

## Capas com assinatura oficial

O gerador existente recebeu `--seal marca/selo.png`. A assinatura original foi aplicada sem redesenho ou recoloração; as cinco capas foram importadas pelo Easy MCP e vinculadas aos artigos existentes.

| Post | Editoria | Nova mídia | WebP original em bytes |
|---|---|---|---|
| 20 | Notícias & Região | 67 | 30.150 |
| 21 | Dinheiro & Renda Extra | 69 | 28.686 |
| 22 | Tecnologia & Inovação | 70 | 28.290 |
| 23 | Esportes | 71 | 24.558 |
| 24 | Famosos & Entretenimento | 72 | 24.320 |

Todas têm 1200 × 675 pixels, 16:9, qualidade 84 e menos de 50.000 bytes. Alt, título, legenda, associação ao post e `featured_media` foram verificados. As imagens anteriores foram preservadas. Não foram criados posts/páginas; texto, título, slug, categoria, data de publicação e status dos cinco artigos foram comparados antes/depois e permaneceram iguais.

## Cache e validação pública

- Purge All do LiteSpeed e limpeza do cache de sitemap Rank Math confirmados pelo Easy MCP; a validação usou URLs canônicas sem cache-busting.
- Home HTTP 200; logos colorida/branca carregadas no navegador e inspecionadas visualmente em mobile e desktop.
- Cinco cards com imagens 16:9 e selo, dimensões reservadas e consultas nativas automáticas preservadas.
- `sitemap_index.xml`, `post-sitemap.xml`, `page-sitemap.xml` e `category-sitemap.xml`: HTTP 200, XML válido. O sitemap de posts inclui as cinco novas imagens.
- Cinco artigos com `og:image` atualizado, 1200 × 675, e `max-image-preview:large`; acesso público permitido a Googlebot e Mediapartners-Google pelo `robots.txt`.

## Performance observada

Chromium mobile 390 px, DPR 2: **CLS 0** no teste e nenhuma rolagem horizontal. Os arquivos foram buscados por HTTPS com validação TLS mantida; o navegador recebeu respostas verificadas porque seu repositório de certificados não possui a CA do proxy deste ambiente.

- Transferência inicial: **154.459 bytes**, 12 recursos.
- Transferência após carregar toda a Home: **196.751 bytes**, 15 recursos.
- Corpos comprimidos gzip/deflate, sem cabeçalhos HTTP; as duas logos somam apenas **3.994 bytes**.
- HTML da Home: **19.241 bytes** transferidos com Brotli, **75.396 bytes** descomprimidos.
- TTFB mediano: **0,373 s** em cinco requisições a partir deste ambiente. Cache HIT e MISS observados; não é uma garantia de latência para todos os visitantes.

Essas verificações não são Lighthouse nem dados de campo. **PageSpeed Mobile 95+ continua meta não aferida.** CSS do tema e jQuery/jQuery Migrate bloqueantes continuam presentes; esta alteração não os acrescentou nem os removeu.
