# Fotos públicas, ALT e imagem no corpo — nova versão para substituição

Arquivos completos:

- `implementacao-wp/motor-publicacao.php`
- `automacao-rss/coletor_rss.py`
- `automacao-rss/requirements-rss.txt`

PHP instalado e confirmado ativo no Snippet 12 em **25/10/2026**, após a verificação de que o site ainda utilizava a versão anterior. Prioridade 16; código conferido contra o arquivo sem a abertura PHP; `code_error: null`. O endpoint informa `image_policy: public_urls_no_allowlist` e mínimo de 200 pixels. Cache de páginas LiteSpeed limpo e página inicial HTTP 200. O agente não atualizou o Python da VPS.

Resultado sem credenciais: `implementacao-wp/validacao-snippet12-seo-imagens.json`. A conferência encontrou rascunhos anteriores com capas pendentes; essa atualização não recupera automaticamente imagens de matérias já criadas. Nenhum artigo foi alterado ou criado para validar a instalação.

## PHP

- Remove a allowlist: qualquer host público HTTP(S) pode fornecer imagens. A opção antiga de domínios é preservada para rollback, mas não participa da validação; o campo deixa de aparecer no painel. `/g73/v1/publisher/status` passa a informar `image_policy: public_urls_no_allowlist`, `image_hosts: []` e `image_min_pixels: 200`.
- Mantém o transporte seguro do WordPress, até cinco redirecionamentos, URLs públicas, TLS para HTTPS, limite de 8 MB, 24 megapixels e verificação do formato real. Endereços privados/arquivos inválidos não são imagens públicas válidas.
- Aceita JPEG, PNG, WebP, GIF e AVIF com largura e altura de pelo menos 200 pixels. Tenta produzir WebP 1200×675 <60 KB; falhas do editor preservam o original, com metadados básicos se necessário.
- ALT do attachment usa o valor de `meta.rank_math_focus_keyword`, o mesmo texto salvo no Rank Math. A gravação é repetida após confirmar a imagem destacada. Se uma mídia local já pertence a outro artigo e tem ALT diferente, registra uma cópia para não mudar o ALT dos artigos anteriores.
- A sanitização preserva `figure`, `figcaption` e `img`, sem atributos de execução como `onerror`. O servidor substitui a figura gerenciada pelo coletor por uma única figura com a URL local da mídia anexada, depois do primeiro parágrafo. Isso evita depender de hotlink externo no conteúdo final. Width/height, lazy loading e classe `wp-image-ID` permitem reservar espaço e usar o processamento responsivo do WordPress.
- Falhas de imagem não retornam 422 para cancelar a matéria: o texto fica em `draft` com avisos. Se foi solicitado `publish`, a publicação só ocorre após confirmar a capa e os metadados. Não há promessa de imagem quando a origem não oferece um arquivo acessível e válido.

## Python

- Usa `BeautifulSoup` para imagens de RSS/Atom, enclosures, conteúdo HTML do feed, `og:image`, `twitter:image` e imagens do corpo da página original. A página é consultada para localizar fotos; seu texto não é enviado ao OpenRouter como material adicional.
- Rejeita URLs/atributos com `facebook`, `logo`, `icon`, `share`, `avatar` e `banner-default`, cabeçalhos/rodapés/navegação e extensões não-imagem. Endpoints como `Handler.ashx?f=i&query=...` continuam válidos: a extensão não decide o tipo do arquivo.
- Pillow verifica os bytes, formato e dimensões, mínimo 200×200 e máximo de 24 megapixels, em downloads limitados a 8 MB. O resolvedor tem orçamento curto por notícia, timeouts e limite de candidatos; uma foto inválida dá lugar à próxima candidata. Erros de DNS/HTTP de imagem não encerram as outras matérias.
- O payload envia a URL selecionada em `image_url` e insere uma figura depois do primeiro parágrafo, com ALT exatamente igual à palavra-chave gerada. O PHP troca o `src` pelo arquivo anexado localmente.
- Créditos fornecidos no RSS ou configurados na fonte são preservados. Para a legenda literal solicitada, declare `"image_credit": "Divulgação / Fonte Oficial"` somente nas fontes em que esse crédito for correto. Sem crédito informado, usa `Imagem da fonte: <nome da fonte>`, sem atribuir autoria/licença oficial por suposição.
- Mantém `status: draft`, os três metadados SEO, deduplicação, fila SQLite e retentativas do OpenRouter. No fallback 422 de um endpoint antigo, retira também a figura gerenciada antes de persistir e reenviar o payload sem foto.
- Payloads já tentados permanecem imutáveis para preservar idempotência. Esta alteração não reescreve automaticamente matérias antigas nem garante capa para rascunhos já existentes.

## Rank Math e qualidade da imagem

A presença de imagem com ALT contendo a palavra-chave é um dos critérios de análise, não uma condição suficiente para nota 80/100. O prompt pede a palavra-chave em título, resumo, abertura e subtítulo quando natural e fundamentado. O código não escreve uma nota artificial em `rank_math_seo_score`, não inventa fatos para alongar uma nota e não promete ranqueamento no Google. A análise do Rank Math pode depender do processamento do editor/plugin.

O filtro é uma heurística de seleção e validação de arquivo, não um classificador visual capaz de provar que toda imagem é fotografia. O mínimo de 200 pixels é de compatibilidade; para grandes prévias do Discover, prefira fotos de pelo menos 1200 pixels de largura. Originais preservados podem exceder 60 KB; não há promessa automática de PageSpeed 95+.

## Substituir os arquivos

No WordPress, substituir integralmente o código do Snippet 12. Se o editor já acrescentar `<?php`, omitir essa linha inicial. Após salvar, confirmar que está ativo; na implantação anterior foi necessária uma segunda ativação após salvar o código.

Na VPS, fazer backup do coletor atual, substituir o Python completo e atualizar as dependências no mesmo ambiente virtual:

```bash
/opt/giro73-rss/.venv/bin/pip install -r /opt/giro73-rss/requirements-rss.txt
/opt/giro73-rss/.venv/bin/python -m py_compile /opt/giro73-rss/coletor_rss.py
sudo -u giro73-rss /opt/giro73-rss/run --check
sudo -u giro73-rss /opt/giro73-rss/run --limit 1
```

Preservar `/opt/giro73-rss/.env`, `/etc/giro73-rss.json` e `/var/lib/giro73-rss/vistos_rss.sqlite3`. O coletor atual usa OpenRouter (`OPENROUTER_API_KEY` e `AI_MODEL`); consulte `automacao-rss/README.md` e `33-openrouter-coletor-vps.md` para atualizar também o launcher e as dependências. Não colocar chaves no código ou apagar o banco de vistos. `--check` valida o modelo, autenticação e feeds; a extração da fotografia é exercitada ao processar o primeiro candidato.

## Verificação offline

51 testes Python e 24 verificações PHP com APIs WordPress simuladas; sintaxe PHP e Python verificadas. Inclui o caso `facebook.jpg` como OG inválido, foto de `Handler.ashx`, lazy loading/srcset, dimensões reais, redirecionamento privado rejeitado, figura/ALT, manutenção da fila e não alteração do ALT de mídia compartilhada. Não foram criadas matérias reais nem feitas alterações no site para esses testes.
