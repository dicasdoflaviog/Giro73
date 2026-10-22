# Home editorial branca — aplicado e pendência de fotos

Atualização de **22/10/2026**. Substitui a apresentação do relatório 11. O HTML/CSS foi publicado no WordPress; a substituição das cinco imagens está **pendente de liberação da rede do ambiente**.

## Publicado

- Fundo global branco puro, container de 1180 px com padding de 24 px 16 px; sem sombras ou molduras ao redor das notícias do feed e da sidebar.
- Hero desktop em duas colunas iguais e intervalo de 24 px. Manchete tipográfica à esquerda, sem imagem: chapéu, título de 34 px e resumo. Moldura sutil de 1 px, raio de 8 px e padding de 32 px, conforme a exceção solicitada.
- Dois destaques à direita, cada um com 200 px de altura e intervalo de 16 px. Imagem com recorte, degradê escuro na base e título HTML branco de 18 px. A área visual mede **416 px**; o padding de separação inferior de 32 px e a borda ficam abaixo dessa área.
- Corpo com proporção `2.2fr / minmax(280px,1fr)` e intervalo de 48 px. Feed com miniaturas de **240×135 px** no desktop, títulos de 20 px, resumo em duas linhas e datas cinza. Separadores `#EAEAEA`, sem caixas.
- Sidebar com divisor esquerdo e padding de 32 px. “Mais lidas” permanece reservado, conforme a decisão anterior; não há dados reais para classificar cinco matérias. CSS da lista numerada está preparado. Contato e espaço publicitário preservados, sem scripts de anúncios.
- Header amarelo aderente ao topo e footer roxo com identidade oficial e links institucionais preservados.
- Três consultas nativas: post mais recente, os dois seguintes e feed a partir do quarto. Com os cinco posts atuais, ordem 24; 23 e 22; 21 e 20. Nenhuma repetição ou artigo/página criado.

## Validação

Home HTTP 200, com imagens existentes carregadas. Chromium em **360, 390, 800, 1024 e 1440 px**: sem overflow horizontal, sombras ou falhas de recursos. Em desktop, hero com duas colunas iguais, ambas com área visual de 416 px; destaques de 200 px e fotos do feed em 240×135 px. Container, intervalos, tipografia, divisor da sidebar e espaço publicitário de 250 px conferidos.

Inspeção visual em desktop e mobile concluída. CLS observado no carregamento mobile de laboratório: **0**; não é medição de campo ou resultado PageSpeed. Texto bruto, títulos, resumos, slugs, datas, categorias, status e vínculos de mídia dos cinco posts foram comparados antes/depois e preservados; CSS/template publicados correspondem aos arquivos do repositório. Nenhum script ou fonte remota foi adicionado. LiteSpeed **Purge All** executado e confirmado após publicar CSS/template.

## Fotografia: bloqueio concreto

As tentativas HTTPS para Wikimedia, Unsplash e Pexels foram recusadas pelo proxy do ambiente com **CONNECT 403**. A política de saída ativa possui apenas `giro73.com.br` como destino personalizado, além dos presets de desenvolvimento. A Biblioteca do WordPress foi verificada: contém as marcas e as ilustrações anteriores, sem cinco fotografias com origem/licença comprovadas para reutilização.

Foi salvo um rascunho de configuração acrescentando **`commons.wikimedia.org` e `upload.wikimedia.org`**, preservando `giro73.com.br` e os presets existentes. A ferramenta retornou **`status: saved` / `requires_publish: true`**. Salvar o rascunho não atualizou a rede atual; o teste posterior continuou bloqueado. É necessário revisar/salvar nas configurações do ambiente e publicar o ambiente antes de retomar os downloads.

**Não foram baixadas, enviadas ou vinculadas fotografias nesta etapa.** As mídias atuais 84 a 88 continuam nos cinco posts e ainda são as ilustrações anteriores. Não foram apagadas para evitar deixar os artigos sem imagem durante a pendência. Não há reivindicação inventada de licença ou autoria.

## Fluxo preparado para retomar

`gerar-upload-capas.py` agora aceita somente fotografias selecionadas por manifesto. Removeu-se o desenho vetorial e todo fallback de ícones. O fluxo busca metadados oficiais do Commons, valida licença livre explícita e autor, baixa uma variante limitada, recorta sem ampliação e comprime para **1200×675 WebP abaixo de 60.000 bytes**. Títulos e marcas nunca são desenhados na imagem.

O manifesto de cada fotografia deve ser preenchido após seleção visual real; nenhum nome de arquivo fictício foi tratado como fonte existente. O cliente REST opcional reutiliza o upload anterior, preserva os dados editoriais e acrescenta apenas a linha identificada de crédito visível. A importação pelo Easy MCP também está documentada em `implementacao-wp/README-capas.md`.

Sintaxe Python, ajuda da CLI e conversão local de uma fixture de pixels foram verificadas. A fixture não foi publicada nem apresentada como foto. **Busca, licença de arquivos reais, download e upload final ainda não foram validados**, pois dependem da rede liberada.
