# Capas editoriais Giro73 — geração e upload

O script `gerar-upload-capas.py` produz arte original com formas geométricas, tipografia e o selo oficial fornecido pela redação. Não utiliza fotos, marcas ou personagens de terceiros. As fontes locais DejaVu Sans/Liberation Sans possuem licenças permissivas; não se redistribuem arquivos de fonte.

## Especificação validada

- 1200 × 675 pixels, 16:9, RGB, WebP estritamente abaixo de **50.000 bytes**.
- Paleta oficial: magenta `#FF006E` (Notícias), azul `#3A86FF` (Dinheiro), roxo `#2C1067` com ciano `#03F1F4` (Tecnologia), ouro `#FFBE0B` com preto (Esportes), laranja `#FB5607` (Famosos). Apoios em amarelo `#FFEA02`, preto e branco; Achadinhos não é aceito.
- O selo PNG original entra sem recoloração e com transparência preservada. Antialiasing e compressão WebP produzem tons intermediários nas bordas; as cores de desenho vêm da paleta oficial.
- Sem downloads de bibliotecas gráficas, imagens ou fontes durante a geração.
- Ambiente testado: Python 3.12, Pillow 12.3.0 e DejaVu Sans instalado em `/usr/share/fonts/truetype/dejavu/`.
- As imagens são ilustrações editoriais; não representam fotografias de locais, dados de mercado, audiência, mapas geográficos ou partidas reais.

## Gerar sem alterar o WordPress

Na pasta `portal-noticias-mcp/implementacao-wp`:

```bash
python gerar-upload-capas.py --seal '/caminho/Property 1=G73_V.png' --output-dir /tmp/giro73-capas
```

Se Pillow estiver ausente, instalar a dependência verificada no ambiente de execução:

```bash
python -m pip install 'Pillow==12.3.0'
```

O diretório escolhido recebe as imagens e `manifesto-geracao.json`. Por padrão, o script gera as cinco capas dos posts existentes 20, 21, 22, 23 e 24.

Sem `--seal`, as capas e o manifesto registram `official_seal: false`, e o script informa que a assinatura está pendente. É possível atualizar somente a paleta sem inventar uma assinatura para substituir a marca; fornecer o PNG original para concluir a identidade visual.

## Fazer upload e vincular

```bash
python gerar-upload-capas.py --seal '/caminho/Property 1=G73_V.png' --upload --user admin
```

A senha de aplicativo é solicitada sem exibição. Para automação, injetar `WORDPRESS_USERNAME` e `WORDPRESS_APP_PASSWORD` pelo gerenciador de segredos do executor e executar:

```bash
python gerar-upload-capas.py --seal '/caminho/Property 1=G73_V.png' --upload
```

`--password-stdin` permite fornecer somente a senha pela entrada padrão de um executor confiável. Não passar senhas em argumentos, escrever valores em scripts nem versionar arquivos de credenciais. O token Easy MCP não substitui Basic Auth neste cliente REST.

## Comportamento de atualização

1. Gera e verifica os WebP localmente antes de usar a rede.
2. Confere se cada post já existe e pertence à categoria indicada. Aceita posts publicados, rascunhos, pendentes ou agendados; preserva o status.
3. Localiza a mídia pelo slug com ID do post e hash do arquivo. Arquivos idênticos são reutilizados; mudanças na arte geram outro hash e outra mídia, preservando a anterior.
4. Preenche título, alt, legenda e associação ao post na biblioteca. Vincula apenas `featured_media` no artigo existente.
5. Relê o post e confirma preservação do título, slug, texto, categoria, status e data. Verifica o arquivo servido por HTTPS, incluindo formato, dimensões e peso.
6. Salva `manifesto-upload.json` com IDs, URLs, tamanhos e metadados públicos. Nenhuma credencial é gravada.

O script nunca cria posts/páginas, publica rascunhos ou apaga mídias. Uploads e atualizações são sequenciais, com timeout e TLS verificado. Redirecionamentos de API são recusados para evitar encaminhar autenticação para outro destino. CDNs com outra origem exigem adaptação explícita da verificação pública.

Reexecução da versão anterior testada: as cinco mídias originais foram reutilizadas, sem duplicação. A reprodução dos mesmos hashes depende das versões do Pillow, das fontes, do selo e do conteúdo do manifesto.

## Usar nos próximos artigos do calendário

Criar primeiro o rascunho no fluxo editorial existente, obter seu ID e preparar um manifesto JSON. Exemplo: substituir `123` por um ID real e usar textos curtos para a capa.

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

```bash
python gerar-upload-capas.py --manifest fila-capas.json --seal '/caminho/Property 1=G73_V.png' --upload
```

Categorias aceitas: `noticias-regiao`, `dinheiro-renda-extra`, `tecnologia-inovacao`, `esportes` e `famosos-entretenimento`. A categoria seleciona a cor e a ilustração correspondente. Título auxiliar, alt e legenda devem descrever a imagem com naturalidade e corresponder ao assunto do post.

## Depois do upload

- Invalidar os caches pertinentes pelos mecanismos do WordPress/LiteSpeed e do Rank Math. O script não tenta registrar hooks ou executar comandos de cache não documentados.
- Verificar capa, categoria e artigo, `og:image`, dimensões, `max-image-preview:large`, sitemap e layout mobile.
- As variantes responsivas são geradas pelo WordPress; o arquivo original permanece em 1200 × 675. A primeira imagem visível deve ter prioridade; imagens inferiores podem usar lazy loading.
- Dimensões reservadas ajudam a evitar CLS. Elegibilidade ao Discover e nota PageSpeed dependem também do conteúdo, do tema, de terceiros, do servidor e de medição; não são garantidas pelo tamanho das capas.
