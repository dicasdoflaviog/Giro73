# Capas, sitemaps e performance — validação final

Registro: 2026-10-22T00:03:09-03:00.

## Alterações executadas

- Geradas cinco ilustrações originais com Pillow, sem fotos, marcas ou personagens de terceiros. Fontes locais DejaVu Sans; arte produzida a partir de primitivas geométricas.
- Upload realizado em /wp-json/wp/v2/media. Título, alt e legenda preenchidos com a palavra-chave correspondente e descrição natural da ilustração.
- Mídias vinculadas aos cinco posts existentes por featured_media. Nenhum post ou página criado; textos, títulos, slugs, categorias e status preservados por comparação com o inventário anterior.
- Reexecução integral do upload reutilizou os mesmos cinco IDs de mídia sem duplicação. Geração local dos cinco arquivos leva aproximadamente um segundo neste ambiente.
- Correção de fonte nos títulos: aplicada herança da fonte de sistema dentro de .g73-portal. O tema ainda solicitava Roboto-Regular.ttf, com 85.565 bytes transferidos; a carga final não faz nenhuma requisição de fonte.

## Imagens servidas pelo WordPress

| Post | Mídia | Dimensões | Bytes do arquivo original | URL |
|---|---|---|---|---|
| 20 | 44 | 1200 × 675 | 24212 | [WebP](https://giro73.com.br/wp-content/uploads/2026/10/giro73-pilar-20-e0e4ef69b7fb.webp) |
| 21 | 45 | 1200 × 675 | 21622 | [WebP](https://giro73.com.br/wp-content/uploads/2026/10/giro73-pilar-21-74a0278ab3b3.webp) |
| 22 | 46 | 1200 × 675 | 20612 | [WebP](https://giro73.com.br/wp-content/uploads/2026/10/giro73-pilar-22-14611a014477.webp) |
| 23 | 47 | 1200 × 675 | 17520 | [WebP](https://giro73.com.br/wp-content/uploads/2026/10/giro73-pilar-23-e531db0c11e4.webp) |
| 24 | 48 | 1200 × 675 | 17580 | [WebP](https://giro73.com.br/wp-content/uploads/2026/10/giro73-pilar-24-e12b49bfdba9.webp) |

Formato e dimensões verificados no arquivo relido por HTTPS, com decodificação WebP. O WordPress também gerou variantes responsivas; as cinco imagens estão em og:image com width=1200 e height=675. max-image-preview:large confirmado nos cinco artigos.

## Sitemaps e robots.txt

| Arquivo | HTTP | Validação |
|---|---|---|
| [sitemap_index.xml](https://giro73.com.br/sitemap_index.xml) | 200 | 3 sitemaps filhos |
| [post-sitemap.xml](https://giro73.com.br/post-sitemap.xml) | 200 | 6 URLs; 5 imagens |
| [page-sitemap.xml](https://giro73.com.br/page-sitemap.xml) | 200 | 6 URLs |
| [category-sitemap.xml](https://giro73.com.br/category-sitemap.xml) | 200 | 5 URLs |

- post-sitemap.xml contém os cinco artigos e a Home; page-sitemap.xml contém as cinco páginas institucionais e a Home. A Home adicional não representa criação de página ou artigo.
- XML analisado por parser: sem HTML, avisos PHP ou saída inválida. Todas as imagens destacadas aparecem no sitemap de posts.
- LiteSpeed Cache Purge All e limpeza do cache de sitemap Rank Math concluídos pelos mecanismos próprios. Cache de objeto e configurações de plugins não foram alterados.
- robots.txt declara https://giro73.com.br/sitemap_index.xml. Googlebot e Mediapartners-Google permitidos para Home, cinco artigos, cinco páginas e cinco imagens. A administração permanece bloqueada, conforme a configuração padrão.

Robots observado:

```text
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: https://giro73.com.br/sitemap_index.xml
```

### Estado externo no Google

O Search Console ainda informa `isPending=true`, `lastDownloaded=null`, `errors=1` para o envio anterior. O XML atual responde HTTP 200 e é válido. Limpar o WordPress não apaga o histórico nem força a releitura pelo Google; a atualização no Search Console permanece pendente. Acompanhar a nova leitura e reenviar o índice pela interface do Search Console se necessário. Não foi alegada indexação concluída.

## Medições de performance

Valores em KB decimais (1 KB = 1.000 bytes). Medição a partir desta máquina, com proxy de saída; não equivale a tráfego real do usuário ou emulação Lighthouse.

| Medida | Resultado |
|---|---|
| TTFB mediano, cinco conexões curl | 0.592 s |
| TTFB mínimo–máximo | 0.220–0.615 s |
| HTML transferido com Brotli | 17.63 KB |
| HTML descomprimido | 71.76 KB |
| Carregamento mobile inicial, corpos comprimidos | 128.02 KB / 10 recursos |
| Home mobile completa após rolagem | 157.61 KB / 12 recursos |
| CLS observado no cenário testado | 0 |
| Overflow horizontal em 390 px | Não observado |

Amostras de TTFB, em segundos: 0.591603, 0.614999, 0.336967, 0.219962, 0.591626. O cabeçalho X-LiteSpeed-Cache mostrou MISS na primeira leitura e HIT nas quatro seguintes. O mesmo URL foi usado sem parâmetros de cache-busting.

O peso mobile é a soma dos corpos dos recursos efetivamente solicitados por Chromium em 390 px e DPR 2, com gzip/deflate, sem cabeçalhos HTTP. As requisições foram feitas por urllib com TLS verificado e entregues ao navegador, cuja loja de certificados não reconhece a CA do proxy. Desktop foi renderizado separadamente e seus downloads adicionais foram excluídos do total mobile. Não houve erro de recurso.

As cinco capas renderizaram com largura/altura declaradas. A primeira imagem tem fetchpriority=high; as duas últimas usam lazy loading. O CLS observado foi zero antes e depois da rolagem, sem anúncios; isso não é garantia de CLS em todos os dispositivos e condições.

### Bloqueadores ainda presentes

| Recurso no cabeçalho | Tipo | Transferência medida |
|---|---|---|
| hostinger-ai-style-css | css | 8.76 KB |
| jquery-core-js | js | 30.36 KB |
| jquery-migrate-js | js | 4.87 KB |

O CSS do tema é síncrono. jQuery e jQuery Migrate continuam sem async/defer no cabeçalho. O ajuste de fonte retirou o download de Roboto, mas não removeu essas dependências. Não foi introduzido código para alterar a ordem dos scripts sem o suporte e a validação das dependências do tema/plugins.

Próxima otimização: avaliar o carregamento adiado de JS na configuração do LiteSpeed e CSS crítico, medindo navegação, formulários e recursos dos plugins. A conexão Easy MCP atual não anuncia um setter dedicado de otimização LiteSpeed; o WP-CLI do conector está desativado. Não foram ativadas ferramentas administrativas nem modificados controles de acesso para contornar essa limitação.

### PageSpeed 95+

A tentativa de acesso à API oficial PageSpeed foi bloqueada pelo proxy de saída com CONNECT 403 antes de alcançar www.googleapis.com. Nenhuma nota PageSpeed/Lighthouse foi produzida. O peso reduzido, TTFB e CLS observados não comprovam nota 95+. A medição formal e os bloqueadores acima continuam pendentes; não houve alteração de políticas de rede nesta rodada.

## Reutilização

- [Script de geração/upload](gerar-upload-capas.py).
- [Instruções e manifesto para os próximos artigos](README-capas.md).
- [Calendário operacional](05-calendario-operacional-15dias.md).

As credenciais não foram gravadas nos arquivos, na configuração do repositório nem nos manifestos. O cliente solicita a senha de aplicativo em prompt ou recebe variáveis de ambiente/entrada padrão de um executor confiável.
