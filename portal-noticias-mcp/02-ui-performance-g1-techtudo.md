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

## Cabeçalho e capa automática

- Cabeçalho `#FFEA02`, borda inferior roxa de 3 px, logo horizontal colorida oficial com dimensões declaradas. Navegação roxa, peso 800, sem sublinhado, hover magenta; links visíveis com quebra de linha no mobile, sem JavaScript de menu.
- Rodapé `#2C1067`, logo branca oficial, texto branco e links institucionais ciano/branco sem sublinhado. Favicon e logo do Schema usam a marca redonda oficial.
- Corpo com largura máxima próxima de 1.200 px. Cards em CSS Grid: uma coluna em telas estreitas, duas em tablets e três em desktop, sem biblioteca de layout.
- Uma consulta nativa principal, 12 matérias por página, abrangendo as cinco editorias e ordenada por publicação. Cada artigo aparece uma vez; o primeiro recebe título maior. Sem cinco seções fixas disputando a grade.
- Desktop: três colunas; quando a divisão deixa dois itens de resto, o primeiro ocupa 2/3 da abertura; quando deixa um, o primeiro ocupa a largura inteira. As demais matérias preenchem linhas completas de três. Tablet usa duas colunas e destaque inicial inteiro para quantidades ímpares; mobile, uma coluna. Distribuição CSS automática pelo número de itens, sem JavaScript.
- Cada card: fundo branco sobre página `#F4F5F8`, borda cinza de 1 px, raio de 10 px e sombra `0 4px 12px rgba(44,16,103,.06)`. Sem tarjas de editoria. Imagem 16:9 com dimensões declaradas, etiqueta branca discreta com ponto na cor da editoria, título preto peso 800 sem sublinhado e data cinza. A cor fica concentrada no pequeno indicador da etiqueta.
- Capas editoriais próprias em 1200 × 675 WebP abaixo de 50.000 bytes, com o selo verificado original fornecido pela redação. Arte neutra sem tarja, selo “GUIA” ou nome de editoria gravado na imagem; a identificação de categoria é dinâmica no HTML. Não redesenhar as marcas.
- Usar paginação com links HTML; não exigir scroll infinito. Invalidar cache da capa e da categoria no evento de publicação. Nenhuma edição manual de capa é necessária.

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
