# UI WordPress e Core Web Vitals

## Direção visual

Inspirar-se na hierarquia jornalística do G1 e na distribuição automática de cards de TechTudo/Catraca Livre, com identidade própria, sem copiar marcas, código ou materiais protegidos. Usar tema de blocos leve ou tema próprio enxuto; dispensar construtores visuais pesados.

| Editoria | Cor de destaque |
|---|---|
| Notícias & Região | `#FF006E` |
| Dinheiro & Renda Extra | `#3A86FF` |
| Tecnologia & Inovação | `#2C1067`, detalhe `#03F1F4` |
| Esportes | `#FFBE0B`, texto `#000000` |
| Famosos & Entretenimento | `#FB5607` |

Paleta oficial Giro73, atualizada em 22/10/2026. Aplicar cores de editoria em bordas, marcadores e rótulos; títulos dos cards em preto. Não depender apenas da cor para identificar a categoria. Tecnologia usa roxo para leitura e ciano como acento; esportes usa preto sobre ouro.

## Cabeçalho e Home editorial

- Fundo global **branco `#FFFFFF !important`**. Container central de até **1180 px**, margem automática e padding **24 px 16 px**. Sem sombras ou molduras individuais nas notícias; separação por linhas `#EAEAEA`.
- Header amarelo `#FFEA02`, borda inferior roxa de 3 px e logo oficial com dimensões declaradas. Navegação roxa, peso 800, sem sublinhado; cabeçalho aderente ao topo. Apenas o menu pode rolar horizontalmente no mobile. Footer roxo com logo branca e cinco links institucionais; preservar favicon e Schema.
- **Hero desktop:** duas colunas iguais, intervalo de 24 px. Área visual de 416 px (duas fotos de 200 px + intervalo de 16 px), abaixo de 420 px; separador inferior com padding de 32 px e margem de 40 px.
- **Manchete esquerda:** única moldura permitida, borda de 1 px `#EAEAEA`, raio de 8 px e padding de 32 px; conteúdo centralizado verticalmente. Sem foto. Chapéu de 12 px/peso 700/caixa alta; título de 34 px/entrelinha 1,15/peso 800/cor `#111111`, margem 12 px 0 16 px; resumo de 16 px/cor `#555555`/entrelinha 1,5.
- **Dois destaques direitos:** fotografia ocupando cada bloco de 200 px, raio de 8 px, recorte `object-fit: cover`; degradê escuro na base e título branco de 18 px/peso 700, a 16 px das bordas. Título é HTML separado, nunca texto gravado na fotografia.
- **Corpo:** `minmax(0,2.2fr) minmax(280px,1fr)`, intervalo de 48 px. Feed horizontal com colunas de 240 px e restante livre, intervalo de 20 px, padding de 24 px 0 e borda inferior de 1 px. Foto 240×135/raio de 6 px; chapéu 12 px; título 20 px/peso 800/entrelinha 1,25; resumo de 14 px em duas linhas; data de 12 px cinza.
- **Sidebar:** borda esquerda de 1 px `#EAEAEA`, padding esquerdo de 32 px. “Mais lidas” reservado até existir fonte real de leituras; CSS de lista numerada pronto, números grandes `#CCCCCC` e títulos de 15 px. Contato e publicidade com altura reservada de 250 px; nenhum código de anúncios acrescentado.
- Três consultas nativas com as cinco editorias e ordem decrescente de publicação: uma matéria com offset 0, duas com offset 1 e até 12 no feed com offset 3. Sem repetição, JavaScript de layout ou edição manual da capa. O feed não usa scroll infinito/paginação; arquivos das editorias mantêm paginação nativa HTML.
- Abaixo de 1024 px, hero e corpo passam a uma coluna. Em telas abaixo de 640 px, título principal de 28 px e miniaturas do feed de 112 px; manter os destaques de 200 px sem overflow.
- **Imagens obrigatórias:** fotografias reais pertinentes, em **1200×675 WebP abaixo de 60.000 bytes**, com fonte/licença verificadas e crédito visível quando exigido. Sem desenhos vetoriais, títulos, logos ou selos gravados. Identificar quando a foto for ilustrativa ou retratar outro local/data.
- **Pendência em 22/10/2026:** o layout novo está publicado, mas a rede do ambiente bloqueou o download das fotos. As mídias ilustradas anteriores continuam vinculadas até a liberação de `commons.wikimedia.org` e `upload.wikimedia.org`; não afirmar que já foram substituídas.

## Artigo e anúncios

- Coluna única no mobile; área de leitura até aproximadamente 720 px no desktop. Texto de 18 px, entrelinha de 1,6, parágrafos curtos e subtítulos hierárquicos.
- Ordem: breadcrumbs, H1, resumo, autor e datas, imagem principal com legenda/crédito, conteúdo, fontes, caixa de autor e relacionados.
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
