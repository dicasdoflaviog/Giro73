# Fotografias editoriais Giro73

O fluxo vigente exige **fotografias reais**, sem títulos, logos ou selos gravados. A geração de desenhos foi removida de `gerar-upload-capas.py`. Não há mais imagens padrão nem fallback para ícones.

**Situação desta implantação:** o HTML/CSS novo foi publicado, mas o download das cinco fotografias está bloqueado pela política de rede do ambiente. As mídias antigas 84 a 88 continuam vinculadas; nenhuma foto ou licença foi inventada. Foi salvo um rascunho de configuração para permitir `commons.wikimedia.org` e `upload.wikimedia.org`, preservando o acesso ao WordPress. Revisar/salvar e publicar o ambiente é necessário para aplicar essa alteração; salvar o rascunho não libera a rede atual.

## Seleção das fontes

Escolher visualmente uma fotografia para cada tema, abrindo sua página original no Wikimedia Commons e conferindo autor, contexto e licença. Usar apenas JPEG/PNG com resolução suficiente para 1200×675. O programa admite CC BY/CC BY-SA 2.0, 2.5, 3.0 e 4.0, CC0 ou marca de domínio público com URL explícita do Creative Commons; recusa metadados ausentes e licenças fora dessa lista.

| Post | Editoria | Fotografia a selecionar |
|---|---|---|
| 20 | Notícias & Região | Cidade, comércio ou rodovia brasileira; identificar o local real. Se não retratar a região, declarar que é imagem ilustrativa. |
| 21 | Dinheiro & Renda Extra | Finanças ou pessoa usando celular; não alegar que um aplicativo oferece crédito sem comprovação. |
| 22 | Tecnologia & Inovação | Pessoa trabalhando com notebook ou ambiente tecnológico. |
| 23 | Esportes | Estádio brasileiro, gramado ou bola de futebol; não apresentar uma imagem antiga como partida atual. |
| 24 | Famosos & Entretenimento | Sala com TV, controle remoto ou estúdio. |

Licença livre exige cumprir as condições da fonte. O fluxo registra autor, URL da página, licença e alterações; o crédito acompanha a mídia e fica visível no artigo. Não presumir que uma imagem é livre apenas porque está acessível na internet.

## Preparar um manifesto revisado

Usar o título `File:` exato da fotografia escolhida, sem adivinhar nomes ou autores. O exemplo abaixo é um **modelo não executável**; substituir o campo de arquivo e revisar os textos antes de usar:

```json
[
  {
    "post_id": 20,
    "categoria": "noticias-regiao",
    "commons_title": "File:SUBSTITUIR_PELO_ARQUIVO_REAL_REVISADO.jpg",
    "keyword": "economia do Extremo Sul da Bahia",
    "alt_text": "Descrever somente o que aparece na fotografia selecionada.",
    "caption": "Informar o local real e, quando pertinente, que é imagem ilustrativa.",
    "crop_center": [0.5, 0.5]
  }
]
```

Acrescentar as entradas dos outros quatro posts e suas categorias. As cinco editorias da Fase 1 são aceitas; Achadinhos é recusado.

## Baixar e converter

Dependências: Python 3.12, Pillow e requests. Neste ambiente, requests foi instalado somente em `/tmp/giro73-photo-tools`; usar `PYTHONPATH=/tmp/giro73-photo-tools` ao executar aqui. Em outro executor, instalar com TLS verificado:

```bash
python -m pip install 'Pillow==12.3.0' 'requests==2.32.5'
```

Na pasta `implementacao-wp`:

```bash
python gerar-upload-capas.py --manifest fotos-revisadas.json --output-dir /tmp/giro73-fotos
```

A API oficial fornece os metadados e uma variante de até 1600 px de largura. O arquivo baixado é limitado a 12 MB; o script recorta em **1200×675**, sem ampliar imagens pequenas, preserva a orientação e comprime em **WebP abaixo de 60.000 bytes**. Não desenha texto nem marcas. O manifesto guarda os hashes do arquivo de origem e do WebP, dimensões, peso e atribuição.

Conferir visualmente os recortes antes do envio. O filtro de formato não prova sozinho que uma imagem é uma fotografia pertinente.

## Enviar e vincular

O caminho usado nas implantações anteriores foi `wp_upload_media` do Easy MCP, seguido de atualização de metadados e `featured_media` pela API nativa. Reutilizar a mídia pelo slug com hash quando já existir. Preservar créditos, legenda, alt e origem registrados no manifesto. Exibir o crédito no artigo, além de armazená-lo na Biblioteca.

O cliente REST opcional continua disponível quando o processamento de upload da hospedagem estiver funcional:

```bash
python gerar-upload-capas.py --manifest fotos-revisadas.json --output-dir /tmp/giro73-fotos --upload --user admin
```

A senha de aplicativo é solicitada sem exibição. Automação pode usar `WORDPRESS_USERNAME` e `WORDPRESS_APP_PASSWORD` injetados por gerenciador de segredos, ou `--password-stdin`. Nunca gravar credenciais no manifesto ou no repositório.

O cliente verifica posts/categorias, reutiliza mídias pelo slug, preenche metadados e troca `featured_media`. Acrescenta ao início do conteúdo uma pequena linha de crédito HTML identificada, sem reescrever o texto jornalístico. Reexecuções substituem somente esse crédito identificado, sem duplicá-lo. Título, slug, categoria, status e data são preservados e verificados. Arquivos públicos são conferidos por formato, dimensões, peso e SHA256.

Nenhum artigo/página é criado ou publicado pelo script; mídias antigas não são apagadas. Operações são sequenciais, com timeout e TLS verificado. Não executa hooks de cache ou comandos PHP.

## Verificação final

Após o envio real, conferir Home, cinco artigos, alt/legendas/créditos, `og:image`, sitemap e responsividade. Limpar LiteSpeed e cache de sitemap Rank Math. O download e o envio de fotografias **ainda não foram executados nesta etapa**; não tratar este procedimento documentado como confirmação da substituição das mídias.
