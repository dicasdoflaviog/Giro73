# Engajamento, retenção e autoridade regional — Giro73

Implantação de **22/10/2026**, sem instalar plugins. Código nativo no **snippet 9**, templates e CSS do portal. Guia de configuração e API: [README-engajamento.md](implementacao-wp/README-engajamento.md).

## Entregue

1. Card de WhatsApp na sidebar, abaixo de Economia, e no final da matéria, antes do autor. SVG local, CTA e paleta solicitados. Campo `g73_whatsapp_group_url` no painel e na REST. Por solicitação do usuário, fica **vazio com botão desativado**, aguardando o convite oficial.
2. Reações Útil/Surpreendente/Indignado no final do artigo, com contadores reais em post_meta, trava no navegador, deduplicação adicional no servidor e incremento atômico. Micro-enquete opcional com metabox, configuração REST, opções JSON, votos, barras e porcentagens após participar. Nenhuma enquete foi inventada ou ativada nas notícias existentes. Comentários/pingbacks de posts fechados por filtros nativos.
3. Faixa de três espaços **Colunistas Giro73** na Home, configurável até quatro, com autor, área, avatar WebP circular e última coluna marcada como opinião. Permanecem **Em breve**, conforme resposta do usuário, até o cadastro aprovado. Agenda G73 com CPT `g73_event`, campos REST, próximos eventos e selos dia/mês. Três modelos editáveis, IDs **147–149**, em rascunho e identificados como demonstração; não são apresentados ao público como eventos confirmados.
4. Impressão profissional com G73, editoria, título, resumo, assinatura/data, foto/crédito, corpo serifado e URL de origem no rodapé das folhas. Navegação, publicidade, rodapé do site e componentes de participação ficam ocultos.

## Validação

- PHP analisado sintaticamente; snippet ativo sem erro registrado. Fontes publicadas comparadas com o repositório.
- REST: reação gravada, repetição recusada com 409, escolha inválida recusada com 400, voto da enquete persistido, repetição bloqueada, edição da pergunta zerando resultados e versão antiga rejeitada com 409.
- Teste concorrente no servidor, em **rascunho técnico não público**: quatro leitores diferentes tiveram quatro incrementos preservados; quatro tentativas simultâneas do mesmo leitor retornaram uma aceitação e três recusas. Rascunho técnico excluído depois dos testes; nenhum voto de teste foi lançado nas notícias públicas.
- API recusou convite de domínio não WhatsApp e publicação de evento marcado como demonstração. Rascunho de notícia ficou inacessível a visitante anônimo (404); gestão da agenda exigiu autenticação (401).
- Easy MCP leu os campos globais e os três eventos. Metas de enquete/opinião são acessíveis pelos endpoints nativos de posts e ferramentas genéricas do MCP.
- Teste isolado em Chromium em **390 e 1440 px**: clique, contadores, botões travados, resultados 0%/100% e persistência após reload, sem votos adicionais ou erro de JavaScript.
- Home, categoria e matéria abertas em **390 e 1440 px**, sem rolagem horizontal. Posição correta do CTA, agenda sem eventos fictícios, três espaços de colunistas, reações zeradas nas notícias lidas e uma cópia do runtime por artigo. Capturas revisadas.
- PDF A4 gerado e texto extraído para confirmar seis subtítulos, último parágrafo, assinatura e fonte. A revisão visual identificou problemas de fragmentação com rodapé fixo; a impressão passou a usar a margem nativa de página, com fallback em fluxo normal em navegadores antigos. O teste final verifica a URL em todas as folhas, fora da área do texto. Também foi removida a reserva desnecessária de proporção da foto na impressão.
- PDF final confirmado em três páginas: URL em todas as margens inferiores, sem cobrir texto, seis subtítulos e último parágrafo preservados, foto/crédito sem espaço excedente e controles ocultos.
- Purga final do LiteSpeed confirmada pelo Easy MCP, escopo site, incluindo as variantes desktop/mobile. Home canônica conferida com os dois user agents; sitemap segue HTTP 200 sem eventos de demonstração.
- Artigos originais conferidos antes/depois: mesmos títulos, conteúdo, excertos, datas, slugs, status, categorias, imagens, autoria, configuração armazenada de comentários e metadados anteriores. Os novos campos são aditivos; o fechamento de comentários ocorre por filtro.

## Performance e operação

O runtime de participação tem **4.395 bytes sem compressão / 1.807 bytes gzip**, carregado uma vez e apenas onde há componentes. A consulta dos votos é adiada até o bloco se aproximar da tela. CTA, colunistas e agenda usam HTML/CSS do servidor; não há fonte externa, framework, formulário pesado ou rastreamento comercial novo.

A nota PageSpeed não foi medida novamente nesta etapa. As proteções de participação são de navegador/leitor anônimo, não identificação civil nem certificação antifraude. A enquete não é pesquisa científica. Detalhes de armazenamento funcional e manutenção estão no guia.

Configuração: **Configurações → Engajamento G73**, metabox **Enquete e opinião** nas notícias e menu **Agenda G73**. Convite do WhatsApp e colunistas aguardam preenchimento pelo usuário. Os modelos de eventos precisam de confirmação, retirada da marca de demonstração e publicação para entrar na agenda pública.
