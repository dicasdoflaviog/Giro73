# Fotografias editoriais Giro73

As cinco capas dos posts existentes **20 a 24 foram substituídas por fotografias reais**, com créditos e licenças registrados, em **1200×675 WebP abaixo de 60.000 bytes**. Nenhum texto, logo, selo ou desenho foi acrescentado às imagens. A rede Wikimedia funcionou nesta execução; o plano B abstrato não foi necessário.

As escolhas estão em `fotos-editoriais-selecionadas.json`. IDs de mídia, URLs, autor, licença, alterações, dimensões, bytes e hashes estão em `fotos-editoriais-publicadas.json`.

| Post | Fotografia | Autor | Licença | Mídia | Bytes |
|---|---|---|---|---:|---:|
| 20 | Terminal urbano de Teixeira de Freitas | Kelwy | CC BY-SA 4.0 | 95 | 59.260 |
| 21 | Notebook com gráficos e estatísticas | Carlos Muza | CC0 | 96 | 53.798 |
| 22 | Pessoa digitando em notebook | Simon Hattinga Verschure | CC0 | 97 | 27.626 |
| 23 | Interior do Maracanã, registro de 2013 | Leandro Neumann Ciuffo | CC BY 2.0 | 99 | 59.036 |
| 24 | Controle remoto de televisão | Santeri Viinamäki | CC BY-SA 4.0 | 98 | 54.074 |

As fotografias de arquivo/ilustrativas não são apresentadas como registro de acontecimentos atuais. O arquivo de finanças ilustra análise de dados, sem alegar rentabilidade ou oferta de crédito. Os créditos aparecem abaixo da imagem de cada artigo, com links para a fonte e a licença. As versões CC BY-SA continuam identificadas com a mesma licença após recorte/compressão.

## Reproduzir a geração

Dependências: Python 3.12, Pillow e requests. Neste ambiente, usar `PYTHONPATH=/tmp/giro73-tools`; a configuração reutilizável instala requests e o validador CSS nesse diretório, sem alterar o repositório. Em outro executor:

```bash
python -m pip install 'Pillow==12.3.0' 'requests==2.32.5'
```

Na pasta `implementacao-wp`:

```bash
python gerar-upload-capas.py --manifest fotos-editoriais-selecionadas.json --output-dir /tmp/giro73-fotos
```

O script consulta a API oficial do Commons, verifica autor e licença livre explícita e baixa o original JPEG/PNG de até 12 MB por arquivo. O domínio `thumb.wikimedia.org` passou a aparecer em miniaturas do provedor; o fluxo usa os originais em `upload.wikimedia.org`, já permitido, sem depender desse domínio adicional.

Faz orientação EXIF, recorte 16:9 e redução sem ampliar imagens pequenas. Compressão WebP é ajustada até ficar abaixo de 60.000 bytes. A foto do estádio usa suavização de ruído de **0,6 px**, declarada no crédito, para caber no orçamento; o limite permitido é 0,8 px. Não há operações de desenho de texto, ícones ou marcas.

O manifesto guarda os hashes da origem e do WebP, dimensões, peso, qualidade, recorte e atribuição. Conferir visualmente o arquivo final antes de enviar. O fato de uma imagem estar disponível na internet não comprova licença.

## Próximos artigos

Criar o post pelo fluxo editorial existente e informar seu ID, uma das cinco categorias da Fase 1, o título `File:` exato selecionado no Commons, palavra-chave, alt natural e legenda factual. `crop_center` é opcional; usar `[0.5,0.5]` por padrão. `denoise_radius` é opcional, padrão zero.

Selecionar visualmente fotografias pertinentes e verificar contexto/autor/licença. O script admite CC BY/CC BY-SA 2.0, 2.5, 3.0 e 4.0, CC0 e marca de domínio público com URL Creative Commons explícita. Aceita URLs de licença com sufixo de idioma `/deed.en`; recusa licenças fora da lista e metadados ausentes. Achadinhos permanece fora da Fase 1.

## Enviar e vincular

Nesta implantação, o envio binário usou **`wp_upload_media` do Easy MCP**. A API nativa depois atualizou slug, associação, alt/título/legenda/descrição e `featured_media`. O texto jornalístico, títulos, datas, categorias, status e resumos foram preservados. Foi acrescentada apenas uma linha identificada de crédito no início do conteúdo, logo abaixo da imagem no template de artigo. Mídias antigas foram preservadas.

Reutilizar o slug com ID do post e hash quando já existir. O cliente REST opcional continua disponível quando o upload REST da hospedagem estiver funcional:

```bash
python gerar-upload-capas.py --manifest fotos-editoriais-selecionadas.json --output-dir /tmp/giro73-fotos --upload --user admin
```

Senha de aplicativo por prompt sem exibição, `WORDPRESS_APP_PASSWORD` injetada pelo gerenciador de segredos ou `--password-stdin`; usuário por `--user` ou `WORDPRESS_USERNAME`. Não gravar credenciais em arquivos ou no GitHub.

O cliente reutiliza mídias por slug, verifica os dados editoriais e acrescenta/substitui somente o bloco de crédito identificado, sem duplicá-lo. Confere os arquivos públicos por formato, dimensões, peso e SHA256. Nunca cria/publica artigos ou páginas, apaga mídias ou executa PHP no servidor. Operações sequenciais, com timeout e TLS verificado.

## Verificação concluída

Cinco imagens e artigos públicos HTTP 200, arquivos remotos idênticos aos locais, créditos visíveis e `og:image` atualizado. Sitemap Rank Math contendo as cinco fotografias. LiteSpeed Purge All e cache de sitemap limpos, seguidos de invalidação final da Home após compactar a sidebar.

Validar novamente esses pontos a cada troca de capa. Nota PageSpeed e dados de campo precisam ser medidos; dimensões e peso das fotos, isoladamente, não garantem aprovação no Discover ou AdSense.
