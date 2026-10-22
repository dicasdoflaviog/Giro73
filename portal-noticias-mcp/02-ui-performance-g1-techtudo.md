# UI WordPress e Core Web Vitals

## Direção visual

Inspirar-se na hierarquia jornalística do G1 e na distribuição automática de cards de TechTudo/Catraca Livre, com identidade própria, sem copiar marcas, código ou materiais protegidos. Usar tema de blocos leve ou tema próprio enxuto; dispensar construtores visuais pesados.

| Editoria | Cor de destaque |
|---|---|
| Notícias & Região | `#FF006E` |
| Dinheiro & Renda Extra | `#3A86FF` |
| Tecnologia & Inovação | `#2C1067`, detalhe `#03F1F4` |
| Esportes | `#D97706` nos chapéus/hover; ouro `#FFBE0B` na paleta da marca |
| Famosos & Entretenimento | `#FB5607` |

Paleta oficial Giro73, atualizada em 22/10/2026. Aplicar cores de editoria em bordas, marcadores e rótulos; títulos dos cards em preto. Não depender apenas da cor para identificar a categoria. Tecnologia usa roxo para leitura e ciano como acento; esportes usa o acento editorial `#D97706`.

## Cabeçalho e Home editorial

- Fundo branco puro e container de 1180 px com padding de 24 px 16 px; sem sombras ou molduras no feed; sidebar usa os três cards solicitados.
- Topbar branca de 36 px com borda inferior de 1 px. Cinco links curtos em lowercase, 13 px/peso 700/intervalo de 20 px, nas cores editoriais. Data/hora em português no fuso de Brasília e região à direita; abaixo de 1024 px, esse trecho é ocultado para preservar os cinco atalhos sem overflow.
- Header ouro `#FFBE0B`, 58 px e grid `1fr auto 1fr`: botão MENU à esquerda, logo oficial G73-BRANCO-SELO de 34 px no centro absoluto e BUSCAR à direita. Menu lateral branco usa dialog nativo, foco contido, Escape e dez links; busca usa formulário GET nativo. Nenhum menu de editorias dentro da barra ouro.
- Footer `#F8F9FA`, borda superior de 3 px ouro, padding de 40 px 16 px, logo compacta preta de 28 px, links `#111111` de 13 px/peso 600 e texto `#666666` de 12 px. Cinco links institucionais; favicon e Schema oficiais preservados.
- Hero desktop em duas colunas iguais e intervalo de 24 px, área visual de 416 px. Manchete esquerda tipográfica, sem foto, única moldura sutil permitida, padding de 32 px. Título de 32 px/peso 800/entrelinha 1,15/espaçamento -0,025em, resumo de 16 px e dois tópicos automáticos com bullets coloridos. À direita, dois destaques de 200 px, intervalo de 16 px, foto, degradê e título HTML branco de 18 px.
- Chapéus de 11 px/peso 700/caixa alta/espaçamento 0,05em, texto colorido e borda lateral de 3 px. Cores: notícias `#FF006E`, dinheiro `#3A86FF`, tecnologia `#2C1067`, esportes `#D97706`, entretenimento `#FB5607`.
- Corpo `2.2fr / minmax(280px,1fr)`, intervalo de 48 px. Feed horizontal com imagem **210×118 px**, intervalo de 20 px, padding de 24 px 0 e separador de 1 px. Título de 19 px/peso 700, resumo de 14 px em duas linhas e data de 12 px. Heading “Últimas notícias” de 18 px/peso 700 com linha inferior roxa de 2 px.
- Sidebar com três cards brancos: borda `#EAEAEA`, raio de 8 px, padding de 20 px e margem inferior de 24 px. Clima via Open-Meteo para quatro cidades, cinco artigos numerados com números `#CCCCCC` e identificação “Seleção editorial”, e tabela econômica. Dólar/euro via AwesomeAPI em reais; Ibovespa em pontos via HG Brasil quando o navegador puder acessar. Se uma fonte falhar ou não liberar CORS, manter traços e indicação de indisponibilidade, sem valores inventados. Link de previsão confirmado: https://www.climatempo.com.br/.
- Consultas principais: uma matéria com offset 0, duas com offset 1 e até 12 no feed com offset 3. Os tópicos apontam aos dois seguintes e a sidebar lista cinco recentes. Nenhuma edição manual de capa ou JavaScript de layout; arquivos das editorias mantêm paginação HTML.
- Abaixo de 1024 px, grupos em coluna única; miniaturas do feed ficam menores no mobile. Manter as alturas da topbar e do header, sem empurrar conteúdo.
- Fotografias reais pertinentes em **1200×675 WebP abaixo de 60.000 bytes**, fonte/licença verificadas e crédito visível. Sem texto, logo, selo ou ícone acrescentado ao arquivo. Títulos sobre as fotos dos destaques são elementos HTML separados.
- A pendência de download foi resolvida: as cinco fotos estão publicadas, com metadados, créditos e `featured_media` conferidos. Registro completo em `13-refinamento-editorial-e-fotografias.md`.

## Artigo e anúncios

- Artigo em coluna única: container máximo de 740 px, margem de 32 px auto e padding lateral de 16 px. H1 de 38 px/peso 800/entrelinha 1,15, resumo de 19 px/1,45 e corpo de 19 px/1,68. H2 com borda esquerda de 4 px na cor da editoria.
- Ordem: chapéu de editoria de 13 px, H1, resumo, assinatura “Por Redação Giro73 — Teixeira de Freitas, BA” com data/hora nativa e botão WhatsApp, imagem 16:9 com crédito factual de 12 px, conteúdo, fontes, responsabilidade editorial e relacionados. O botão só abre o compartilhamento por iniciativa do leitor; nenhum envio automático.
- Fonte de sistema por padrão. Se indispensável, uma família WOFF2 local, poucos pesos e fallback de métricas compatíveis; evitar troca de fonte que desloque o texto.
- Reservar espaço para cada anúncio antes da primeira pintura. Exemplo inicial a validar com formatos suportados: área mobile de 320 × 100 para anúncio horizontal e 300 × 250 dentro do artigo; desktop pode ter área de 728 × 90 onde couber.
- O contêiner deve manter altura estável por breakpoint, inclusive sem preenchimento. Não colapsar anúncios vazios acima da área já lida; não aceitar criativo maior que a reserva. Anúncios responsivos exigem reserva compatível com os tamanhos reais contratados.
- Identificar “Publicidade”, separar de links e botões e carregar posições inferiores perto da viewport. Começar com poucas posições manuais; testar impacto antes de ativar Auto Ads.
- Não inserir anúncios em sobreposições que bloqueiem o texto ou empurrem conteúdo após a carga. Consentimento, quando exigido, deve preceder a personalização publicitária.

## Regras rígidas de performance

| Área | Regra |
|---|---|
| Imagens | WebP obrigatório para fotografias, variantes responsivas `srcset`/`sizes`, compressão visualmente adequada e `width`/`height`. SVG apenas para arte vetorial confiável. |
| LCP | Imagem principal no HTML inicial, sem lazy loading e com prioridade alta quando for o elemento LCP; lazy loading apenas abaixo da dobra. |
| JavaScript | Proibidos sliders pesados, carrosséis automáticos, feeds sociais e widgets externos desnecessários. Carregar scripts indispensáveis com estratégia compatível com suas dependências. |
| CSS | CSS enxuto por template; sem framework completo para poucos componentes, sem `@import` e sem animações que alterem geometria. |
| Cache | Um cache leve de página, compressão Brotli/Gzip e cache longo de assets versionados. Excluir login, administração, preview e páginas personalizadas. Não sobrepor plugins de cache. |
| Banco/servidor | PHP em versão suportada pelo WordPress/plugins, OPcache, consultas limitadas e sem chamadas externas no caminho de renderização. Cache de objeto só se houver benefício medido. |
| Terceiros | Inventariar scripts de publicidade e medição; carregar somente os necessários. Nenhum script externo pode ser adicionado sem medir impacto. |

## Critérios de aceite

- Meta PageSpeed Mobile: **95+**, medida em capa, categoria e artigo. Executar três medições por template e registrar a mediana, dispositivo e condições; repetir com anúncios e consentimento reais.
- Metas de campo no percentil 75: LCP ≤ 2,5 s, INP ≤ 200 ms, CLS ≤ 0,1. A ambição visual é CLS 0; publicidade e dados reais precisam confirmar o resultado.
- Orçamento inicial próprio por página: CSS comprimido ≤ 50 KB, JS comprimido ≤ 50 KB, imagem LCP preferencialmente ≤ 150 KB. Medir terceiros separadamente e junto no resultado final.
- Testar em 360 px e desktop: sem overflow, saltos de imagens/fontes/anúncios, falhas de teclado ou links invisíveis sem JS. Verificar atualização automática da capa após publicação.
- PageSpeed 95+ é uma meta de engenharia; laboratório, Core Web Vitals de campo e aprovação do AdSense são avaliações distintas.
