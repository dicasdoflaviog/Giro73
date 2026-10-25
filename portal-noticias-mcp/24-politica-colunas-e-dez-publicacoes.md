# Política, colunas institucionais e dez publicações

Atualização dos avatares enviados pelo administrador em 25/10/2026: [32-avatares-colunistas.md](32-avatares-colunistas.md).

Aplicado em **22/10/2026**, horário de Brasília/Bahia. A editoria Política é o termo **18**, slug `politica`, com cabeçalho **Política & Poder** e cor **#B91C1C**. Está na topbar depois de notícias, no menu gaveta e no rodapé. Os três blocos de destaque e o feed incluem o novo termo.

## Publicações

Dez textos originais de serviço/análise, com aproximadamente **500–570 palavras** cada. Seis são colunas de opinião; quatro são guias utilitários. Não anunciam novas obras, repasses, resultados de partidas ou acontecimentos sem confirmação.

| ID | Coluna / tipo | Publicação |
|---|---|---|
| 208 | Bússola do Bolso | [Renda extra: quanto sobra de verdade depois dos custos?](https://giro73.com.br/renda-extra-calcular-ganho-real/) |
| 209 | Radar Político & Gestão | [Orçamento municipal: cinco documentos para entender as prioridades da prefeitura](https://giro73.com.br/orcamento-municipal-documentos-prioridades-prefeitura/) |
| 210 | Futuro Prático | [Conta invadida? A ordem dos primeiros passos faz diferença](https://giro73.com.br/conta-invadida-primeiros-passos-seguranca/) |
| 211 | Arquibancada & Resenha | [Ba-Vi: a torcida começa antes do jogo — e exige conferir onde comprar](https://giro73.com.br/ba-vi-torcedor-tabela-ingressos-seguranca/) |
| 212 | Veneno & VIP | [Streaming no orçamento: o que olhar antes de contratar outro plano](https://giro73.com.br/streaming-comparar-planos-assinatura-vitalicia/) |
| 213 | Raiz & Vitalidade | [Chás e remédios: por que “natural” não significa livre de risco](https://giro73.com.br/chas-remedios-cuidados-produtos-naturais/) |
| 214 | Serviço | [Recursos para os municípios: como acompanhar emendas e transferências no Extremo Sul](https://giro73.com.br/transferencias-emendas-municipios-extremo-sul-bahia/) |
| 215 | Serviço | [BR-101 no Extremo Sul: o que conferir antes de sair de Teixeira ou seguir para Porto Seguro](https://giro73.com.br/br-101-extremo-sul-bahia-checar-viagem/) |
| 216 | Serviço | [Empréstimo no seu CPF: como organizar a consulta ao Registrato e contestar o que não reconhece](https://giro73.com.br/registrato-consultar-emprestimos-cpf/) |
| 217 | Serviço | [Jogos de Bahia e Vitória: onde conferir a tabela antes de planejar a ida ao estádio](https://giro73.com.br/jogos-bahia-vitoria-conferir-tabela-oficial/) |

## Autoria e imagens

As seis colunas usam a assinatura institucional **Redação Giro73**. Os slugs legados continuam como identificadores técnicos de vínculo na API. Os cadastros têm o nome das colunas; bios descrevem assuntos e responsabilidade editorial institucional.

O subtítulo público é exatamente: “Análise, bastidores e opinião especializada sobre o Extremo Sul da Bahia e o Brasil.” Home, assinaturas e bios usam a identidade das colunas e da Redação. Os artigos identificam o responsável editorial, e a política editorial descreve o processo e a responsabilidade da publicação. O artigo sobre inteligência artificial permanece como conteúdo de tecnologia.

As iniciais foram substituídas por **fotografias temáticas reais**, sem fingir que os fotografados integram a equipe: gráficos, Congresso Nacional, notebook, estádio, controle remoto e chá. Os seis arquivos têm **200×200 px**, WebP, entre **3,1 e 9,0 KB**, exibidos em círculos de 64 px. Créditos e licenças ficam acessíveis na seção. Não são headshots de colunistas reais.

As dez capas usam fotografias licenciadas já disponíveis e três novas imagens de arquivo: Congresso Nacional, xícara de chá e BR-101 em Itamaraju. Todas têm **1200×675 px**, WebP abaixo de **60.000 bytes**. As legendas distinguem arquivo/ilustração da situação atual. Fontes, autores, licenças e arquivos derivados estão em `editorial-outubro/fotografias-fontes.json` e nos registros de mídia.

## Layout e implementação

- Feed: **dez posts**, offset **3**, além dos três destaques do hero. Consulta limitada aos seis termos ativos.
- Miniagenda removida da sidebar; a vitrine grande Agenda 73 permanece abaixo.
- Grid principal conserva `align-items:start`. No desktop, somente o feed acompanha a altura disponível, com linhas distribuídas por Grid/Flex em CSS. Não há altura fixa, medição de DOM por JavaScript ou aumento artificial do número de posts. No mobile o fluxo volta ao tamanho natural.
- O déficit inicial entre feed e sidebar era de aproximadamente **636 px**. O ajuste foi provado com as dimensões reais dos banners antes da implantação.
- Créditos de capa são inseridos pelo servidor em `figcaption` quando o texto ainda não contém crédito. Os cinco artigos-base preservam seus créditos existentes. A leitura por voz ignora créditos e metadados.
- Schema distingue `OpinionNewsArticle` nas seis colunas e `NewsArticle` nos quatro guias, mantendo a autoria WordPress responsável. Não emite pessoas fictícias como profissionais.

## SEO e operação

Rank Math recebeu, nos dez posts: título SEO, descrição, palavra-chave de foco, categoria primária, canonical e imagem Open Graph. As gravações foram confirmadas pelo Easy MCP. Títulos, descrições, og:url e og:image foram conferidos nas dez páginas públicas com HTTP 200. Não foi inventada pontuação de SEO.

O tema Hostinger falha quando `featured_media` é enviado na criação de um post ainda sem ID. A implantação separou as operações: criar rascunho sem imagem, atualizar pelo ID com `featured_media`, gravar SEO e publicar. A listagem confirmou que as tentativas recusadas não criaram duplicatas. Os posts finais são **208 a 217**.

Os portais oficiais externos retornaram bloqueio 403 do proxy, mesmo após concessão de rede. Por isso não se publicou cobertura factual recente nem se afirmou ter conferido valores, agendas ou ocorrências em tempo real. Os textos são roteiros de consulta e educação geral, com referências oficiais para o leitor; essa limitação não deve ser confundida com apuração atualizada.

## Verificação

- Dez páginas públicas responderam HTTP 200, com metadados SEO/Open Graph e créditos.
- Vínculo de seis colunas confirmado por `g73_columnist_slug`; autoria pública institucional em todos os casos.
- Fotografias originais e licenças verificadas no Wikimedia Commons; variantes otimizadas registradas na biblioteca.
- Sintaxe PHP/JavaScript e testes de leitura por voz conferidos.
- Purga final solicitada ao Easy MCP para Rank Math Sitemap e LiteSpeed, escopo site. As URLs canônicas são conferidas sem parâmetro de bypass após a purga.

Não houve nova medição de nota PageSpeed. Os componentes continuam sem frameworks, fontes novas ou bibliotecas de interface.

A validação final após a purga confirmou, em **1440 px**, feed e sidebar com **2612,14 px** de altura e `align-items:start`, sem overflow. Em **390 px**, o fluxo permanece natural, sem rolagem horizontal. Topbar, gaveta, arquivo Política e artigo da coluna Radar foram conferidos; seis fotos temáticas carregadas e seis links de última coluna presentes. Os dois textos de Política constam do arquivo.

Os quatro sitemaps responderam HTTP 200 com XML válido. Os dez artigos e a categoria Política estão incluídos. O artigo de Política emitiu `index, follow` e `max-image-preview:large`. Além da purga de site de Rank Math/LiteSpeed, os dez posts e páginas que os listam receberam purga após o refinamento final de CSS.

Para categoria primária na integração Rank Math do Easy MCP, envie string (por exemplo, `"18"`), conforme o schema registrado. Testes: `tests/leitura-browser.py` e `tests/politica-home-live.py`.
