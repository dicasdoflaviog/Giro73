# Refinamento editorial e fotografias reais

Aplicado no Giro73 em **22/10/2026**, substituindo a apresentação do relatório 12. Os seis ajustes foram concluídos no WordPress; as fotografias foram baixadas com a rede Wikimedia já liberada.

## Interface publicada

1. **Topbar de 32 px:** fundo `#2C1067`, texto branco de 12 px/peso 400, data e hora em português no fuso `America/Sao_Paulo`, região e três atalhos institucionais. Relógio inline de **811 bytes**, sem dependências ou chamadas externas, atualizado a cada minuto. É o horário da barra, separado das datas editoriais dos artigos. Em telas estreitas, data/região são compactadas e os atalhos ficam disponíveis no rodapé.
2. **Header amarelo de 56 px:** logo de 30 px, menu de 14 px/peso 500/intervalo de 28 px e borda inferior sutil. Hover reserva previamente uma linha de 2 px e aplica a cor de cada editoria, sem deslocar o menu. Navegação horizontal no mobile, sem aumentar a altura da barra.
3. **Footer roxo:** logo branca de 26 px, links de 13 px/peso 500 e textos institucionais de 12 px com branco a 75% de opacidade. Os cinco links obrigatórios permanecem publicados.
4. **Hierarquia:** chapéus de 11 px/peso 700/caixa alta, marcador lateral de 3 px e cores próprias; esportes usa `#D97706`. Manchete de 32 px/peso 800/entrelinha 1,15 e duas linhas de tópicos com bullets coloridos, alimentadas automaticamente pelos dois artigos seguintes. Feed com heading de 18 px/peso 700 e linha roxa de 2 px; miniaturas desktop de **210×118 px**, títulos de 19 px/peso 700, resumo de 14 px e data de 12 px. No mobile, miniaturas menores preservam a leitura e evitam overflow.
5. **Sidebar preenchida:** cinco artigos publicados, links automáticos e numeração de 1 a 5 em `#D1D5DB`, 24 px/peso 800. O aviso antigo foi removido. A identificação discreta **“Seleção editorial”** informa a origem da ordenação; não há alegação de audiência medida. A lista acompanha publicações elegíveis, sem edição manual.
6. **Capas reais:** cinco fotografias próprias de bancos/colaboradores com licença livre explícita no Wikimedia Commons. Todas estão vinculadas como `featured_media`, em 1200×675 WebP abaixo de 60.000 bytes. Nenhum ícone vetorial, texto ou selo foi acrescentado aos arquivos. A condição de rede para o plano B não ocorreu.

Fundo branco, ausência de sombras, hero em duas colunas iguais e limites visuais anteriores foram preservados. A pequena moldura da manchete é a exceção solicitada; feed e sidebar continuam separados por linhas finas.

## Fotografias e créditos

| Post | Foto / autor | Licença | Mídia | Bytes |
|---|---|---|---:|---:|
| 20 | Teixeira de Freitas / Kelwy | CC BY-SA 4.0 | 95 | 59.260 |
| 21 | Notebook com estatísticas / Carlos Muza | CC0 | 96 | 53.798 |
| 22 | Digitação em notebook / Simon Hattinga Verschure | CC0 | 97 | 27.626 |
| 23 | Interior do Maracanã em 2013 / Leandro Neumann Ciuffo | CC BY 2.0 | 99 | 59.036 |
| 24 | Controle remoto / Santeri Viinamäki | CC BY-SA 4.0 | 98 | 54.074 |

O registro do estádio é histórico, identificado como tal em alt/legenda; as outras imagens ilustrativas não são apresentadas como notícias factuais novas. O texto jornalístico, títulos, slugs, datas, resumos, categorias e status foram comparados antes/depois e preservados. Acrescentou-se somente uma pequena linha identificada de crédito abaixo da imagem, com autor, fonte, licença e alterações. A foto do estádio recebeu suavização de ruído de 0,6 px para caber no orçamento de imagem, também declarada no crédito.

Os uploads usaram `wp_upload_media` do Easy MCP; metadados e vínculos foram salvos pela API REST nativa. Mídias anteriores foram preservadas. As fontes, licenças, alterações e hashes estão nos manifestos JSON de `implementacao-wp`.

## Validação concluída

- Chromium em **360, 390, 800, 1024 e 1440 px**: sem overflow horizontal, imagens quebradas, erros JavaScript ou sombras no conteúdo da Home.
- Topbar/header com **32/56 px**, logos com **30/26 px**, menu peso 500 e hover nas cinco cores conferidos. Footer, chapéus, títulos, miniaturas e cinco itens numerados conferidos por estilos computados.
- Relógio testado com tempo simulado: **08h50 → 08h51**, em horário de Brasília, sem aguardar um minuto real ou alterar o horário do servidor.
- Inspeção visual de desktop/mobile concluída. **CLS observado: 0**, em laboratório; não é medição de campo nem nota PageSpeed.
- Cinco fotografias públicas HTTP 200, hashes idênticos aos arquivos locais, formato/dimensões/peso corretos. Cinco artigos HTTP 200, crédito visível e `og:image` apontando para a fotografia atual.
- Sitemap de posts HTTP 200, contendo as cinco novas fotografias.
- **LiteSpeed Purge All** e cache de sitemap Rank Math limpos após as alterações; a Home foi invalidada novamente após compactar o espaçamento da lista da sidebar.
- CSS e marcação canônica publicados conferidos com os arquivos exportados. JS validado pelo Node; CSS pelo parser e pelo navegador.

## Arquivos para reutilização

- `implementacao-wp/css-g1-techtudo-ultra-leve.css`
- `implementacao-wp/home-grade-automatica.html`
- `implementacao-wp/header-editorial.html`, `footer-editorial.html` e `topbar-relogio.js`
- `implementacao-wp/gerar-upload-capas.py`
- `implementacao-wp/fotos-editoriais-selecionadas.json` e `fotos-editoriais-publicadas.json`

Dependências adicionais leves (`requests` e `tinycss2`) instaladas e verificadas. O script de instalação reutilizável foi salvo na configuração do ambiente, preservando a rede existente. Isso prepara futuras sessões após revisão/salvamento/publicação do ambiente; não é requisito para visualizar as alterações já publicadas no WordPress.
