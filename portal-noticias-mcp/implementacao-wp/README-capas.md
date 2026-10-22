# Capas editoriais Giro73 — geração e upload

O padrão atual de `gerar-upload-capas.py` produz **somente ilustrações geométricas originais, sem texto, títulos, URLs, logos ou selos**. Categoria, manchete, legenda e crédito ficam no HTML do WordPress, fora da imagem. Não utiliza fotos, marcas ou personagens de terceiros.

## Especificação

- 1200 × 675 pixels, 16:9, RGB, WebP estritamente abaixo de **50.000 bytes**.
- Composição neutra: branco, cinza `#F4F5F8` e roxo `#2C1067`, com contornos suaves. A cor da editoria aparece apenas na etiqueta HTML do card.
- Sem downloads de imagens, fontes ou bibliotecas durante a geração. O modo padrão não usa fontes nem operações de desenho de texto.
- Ambiente validado: Python 3.12 e Pillow 12.3.0.
- Ilustrações abstratas, não fotografias de locais, mapas geográficos, dados de mercado, audiência ou partidas reais.
- Manifesto padrão: `style: illustration` e `official_seal: false`. O antigo modo `--classic` permanece por compatibilidade histórica e não integra o fluxo visual atual; não usá-lo nos disparos.

## Gerar sem alterar o WordPress

Na pasta `portal-noticias-mcp/implementacao-wp`:

```bash
python gerar-upload-capas.py --output-dir /tmp/giro73-capas
```

Se Pillow estiver ausente:

```bash
python -m pip install 'Pillow==12.3.0'
```

O diretório recebe as cinco imagens e `manifesto-geracao.json`. Os IDs padrão são os posts existentes 20 a 24; o script não cria artigos. O argumento `--seal` é exclusivo do modo histórico e não acrescenta marca às ilustrações atuais.

## Enviar e vincular

Nesta implantação, o envio binário usou **`wp_upload_media` do Easy MCP**, com nomes simples, título, alt e legenda; depois, a API REST atualizou os metadados e `featured_media`. Esse caminho contorna o erro de processamento observado no upload REST anterior. Para repeti-lo, gerar localmente, procurar mídia existente pelo slug do manifesto, importar somente se ausente e atualizar o vínculo do post.

O cliente REST opcional do script continua disponível quando o processamento de upload da hospedagem estiver funcionando:

```bash
python gerar-upload-capas.py --upload --user admin
```

A senha de aplicativo é solicitada sem exibição. Para automação, injetar `WORDPRESS_USERNAME` e `WORDPRESS_APP_PASSWORD` pelo gerenciador de segredos do executor:

```bash
python gerar-upload-capas.py --manifest fila-capas.json --upload
```

`--password-stdin` também aceita a senha pela entrada padrão. Não passar segredos como argumentos nem gravá-los em scripts, manifestos ou no GitHub. O token Easy MCP não substitui Basic Auth no cliente REST.

## Atualização segura

1. Gera e valida os WebP localmente antes de usar a rede.
2. Confere existência, categoria e status do post; preserva publicados, rascunhos, pendentes e agendados.
3. Busca a mídia por slug com ID do post e hash. Arquivos idênticos são reutilizados; arte diferente recebe outra mídia, preservando a anterior.
4. Preenche título, alt, legenda e associação na Biblioteca. No artigo, altera somente `featured_media`.
5. Relê o post e compara título, texto bruto, slug, categoria, status e data. Verifica formato, dimensões e peso do arquivo servido por HTTPS.
6. Salva `manifesto-upload.json` com dados públicos, sem credenciais.

O script nunca cria posts/páginas, publica rascunhos ou apaga mídias. As operações são sequenciais, com timeout e TLS verificado. A API recusa redirecionamentos autenticados para outros destinos. A reprodução dos mesmos hashes depende da versão do Pillow e do conteúdo do manifesto.

## Próximos artigos do calendário

Criar primeiro o rascunho no fluxo editorial, obter o ID e preparar um manifesto. Substituir `123` por um ID real:

```json
[
  {
    "post_id": 123,
    "categoria": "tecnologia-inovacao",
    "headline": ["SEGURANÇA", "DIGITAL"],
    "apoio": "Cuidados no dia a dia",
    "keyword": "segurança digital",
    "alt_text": "Ilustração sobre segurança digital com computador e conexões.",
    "caption": "Segurança digital: ilustração editorial original Giro73."
  }
]
```

Os campos `headline` e `apoio` continuam obrigatórios por compatibilidade do manifesto, mas **não são desenhados na imagem padrão**. `keyword`, `alt_text` e `caption` preenchem metadados; descrevê-los com naturalidade, sem repetir palavras-chave artificialmente.

```bash
python gerar-upload-capas.py --manifest fila-capas.json --output-dir /tmp/giro73-capas
```

Categorias aceitas: `noticias-regiao`, `dinheiro-renda-extra`, `tecnologia-inovacao`, `esportes` e `famosos-entretenimento`. Cada categoria seleciona uma ilustração temática. Achadinhos não é aceito nesta fase. Para assuntos específicos, produzir arte fiel à pauta antes de publicar; não apresentar um desenho genérico como registro de um fato real.

## Depois do envio

- Invalidar os caches do WordPress/LiteSpeed e do Rank Math pelos mecanismos disponíveis. O script não executa hooks de cache não documentados.
- Conferir Home, categoria e artigo, `og:image`, dimensões, `max-image-preview:large`, sitemap e layout mobile.
- Manter dimensões reservadas, `srcset`/`sizes` e prioridade para a imagem LCP. Lazy loading somente nas imagens inferiores.
- Meta PageSpeed 95+, elegibilidade ao Discover e CLS de campo dependem de medições, conteúdo, anúncios e hospedagem. O peso das capas, isoladamente, não garante esses resultados.
