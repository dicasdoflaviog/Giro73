# Parceiros G73 — dimensões naturais e modo de comparação

Aplicado em **22/10/2026**, sem novos plugins nem uploads. As seis artes WebP já existiam na biblioteca, com dimensões exatas e tamanhos entre **4,6 KB e 31,9 KB**.

## Renderização corrigida

- Containers transparentes, sem bordas, padding, min-height, altura fixa ou reserva de proporção do maior anúncio da posição.
- Imagens sem cortes e com altura automática. Cada peça possui uma micro-aba PUBLICIDADE, alinhada à esquerda e colada ao topo, com a tipografia, cores e raio solicitados.
- Topo desktop limitado a 970 px, sem depender de uma classificação incorreta de faixa mobile. Até 768 px usa a arte compacta 320×50.
- Sidebar: arte 300×250 centralizada antes de Economia e 300×600 centralizada depois de Economia, com respiro de 24 px. A regra supera o reset de margens dos filhos da sidebar que deixava o banner grudado no widget.
- Miolos centralizados após os parágrafos 3 e 6, com margem de 28 px. O Leia também continua no sexto parágrafo, depois do segundo banner.
- Nova posição independente `home_entre_secoes`, entre o hero e Últimas notícias. O segundo miolo não é mais reutilizado na Home. Nova `sidebar_base` para separar o vertical do anúncio antes de Economia. Ambas aparecem no painel e na REST.
- PHP já entrega as peças completas, com dimensões intrínsecas e picture responsivo, antes do JavaScript. Não há espera por preenchimento de uma caixa vazia. Sem campanha elegível a posição fica oculta.

## Campanhas ativas de comparação

| Campanha | Posição | Mídia desktop | Mídia mobile |
|---|---|---|---|
| **135** | `topo_portal` | **134**, 970×250, billboard | **131**, 320×50 |
| **163** | `sidebar_lateral` | **129**, 300×250 | Reutiliza desktop |
| **164** | `sidebar_base` | **130**, 300×600 | Reutiliza desktop |
| **136** | `in_article_1` | **132**, 336×280 | Reutiliza desktop |
| **165** | `in_article_2` | **129**, 300×250 | Reutiliza desktop |
| **166** | `home_entre_secoes` | **133**, 970×90, leaderboard | **131**, 320×50 |

São as seis posições pedidas, com uma campanha por posição, publicadas e ativas, sem agenda de expiração. Destino: `https://giro73.com.br/contato/`, mantendo sponsored/noopener e abertura em outra aba. As artes originais não foram alteradas. A chave geral da sobreposição mantém a configuração anterior; não participa dos seis locais de comparação no fluxo.

## Validação

- Sintaxe PHP e JavaScript conferida; snippet 6 ativo sem erro e comparado com a fonte local.
- Testes anteriores de rotação, empilhamento, agenda, expiração, frequência e fechamento do modal preservados. A imagem mínima do teste usa dimensões declaradas de fixture; a produção usa imagens reais da biblioteca.
- Navegação de Home e matéria em 390/1440 px: todas as posições esperadas preenchidas, arquivos carregados, caixas transparentes sem borda/padding, aba alinhada e sem espaço entre aba e imagem.
- Espaçamento antes e depois do card Economia verificado em 24 px. Banner da Home confirmado entre hero e corpo, sem misturar com o sexto parágrafo das notícias.
- Limites 360/768/769/1024 px e renderização sem JavaScript conferidos. Imagens completas e sem rolagem horizontal. Larguras fracionárias do navegador são comparadas com tolerância subpixel.

- Purga final LiteSpeed confirmada pelo Easy MCP, escopo site e status cleared. Home canônica HTTP 200 conferida com user agents desktop/mobile depois da purga, com as novas posições e dimensões no HTML.

Guia atual: [README-publicidade.md](implementacao-wp/README-publicidade.md). Componentes de engajamento, opinião, agenda, leitura e impressão permanecem integrados. Não houve nova medição de nota PageSpeed. Para futuros rodízios com altura constante, usar artes da mesma proporção em cada posição.
