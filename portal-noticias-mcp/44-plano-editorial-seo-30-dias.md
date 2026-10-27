# Plano de pesquisa e produção — 30 dias

Pesquisa concluída em **27/10/2026**. Calendário proposto de **27/10 a 25/11/2026**, no contexto editorial de America/Bahia. Foram preparadas **30 pautas: 27 novas e 3 atualizações**, com dez briefings detalhados. Nenhum post, configuração, status de publicação, cron ou rotina da VPS foi alterado nesta etapa.

## Arquivos para trabalhar

- [Prévia navegável, com filtro e impressão](planejamento-seo/plano-editorial.html).
- [Calendário de 30 dias — CSV para Excel/Sheets](planejamento-seo/calendario-30-dias.csv).
- [Dez primeiros briefings](planejamento-seo/briefings-primeiro-lote.md).
- [Banco consolidado de 92 palavras-chave](planejamento-seo/banco-palavras-chave.csv).
- [Pautas estruturadas para integração futura](planejamento-seo/pautas-30-dias.json).
- [Evidências e consumo das APIs](planejamento-seo/evidencias-pesquisa-2026-10-27.json).

## O que foi conferido

O WordPress retornou **42 artigos: 34 publicados e 8 rascunhos**. Foi usado esse inventário para indicar links reais e três atualizações em vez de criar versões concorrentes: posts 211 (ingressos), 212 (streaming) e 435 (acesso à informação). A pesquisa não prova ausência de duplicados no conteúdo integral: esta etapa comparou títulos, status, URLs e temas, sem reabrir todos os textos. Os rascunhos 485 e o publicado 433 tratam de regularização no Pereirão, com números diferentes; revisar datas e documentos antes de publicar 485, sem presumir que sejam duplicados ou que 325 substitua 322.

Search Console, busca web, 29/09 a 26/10, agrupado por consulta: **zero linhas retornadas**. Isso não equivale a prova de ausência de indexação ou impressões; consultas podem não aparecer e os dados recentes têm atraso. Não há base para projetar crescimento do portal a partir desse retorno.

SE Ranking: lote de **56 palavras-chave, com dados em 29**. O banco consolida o lote anterior sem repetir consultas desnecessárias. Os números são estimativas para o **Brasil**, não volume exclusivo das cidades. Valores ausentes permanecem vazios, nunca convertidos em zero. A coluna de CPC preserva o número da API sem moeda, pois o retorno não informou a unidade monetária; CPC de anunciante não representa ganho do editor AdSense.

Foram consultados Sul Bahia News, Liberdade News e Radar News como referências regionais. Solicitados 40 registros por domínio, retornaram dez por domínio. A amostra foi dominada por termos de marca dos próprios veículos; isso não demonstra que sejam concorrentes orgânicos diretos em todas as pautas e não justifica copiar suas notícias ou mirar os nomes deles como palavras-chave.

DataForSEO: cinco consultas Google Brasil em português, mobile. Foram guardados os três primeiros itens classificados como orgânicos, inclusive quando eram vídeos ou redes sociais. Foram examinados títulos e descrições; **não houve leitura integral dos concorrentes**. Resumos de IA e instruções presentes em páginas não foram tratados como fontes factuais. A pesquisa Procon retornou localidades divergentes: endereço, unidade e telefone precisam de confirmação primária.

## Prioridade prática

As dez primeiras pautas combinam dinheiro (MEI, custos e benefícios), utilidade regional, tecnologia preventiva e transparência municipal. A seleção considera dificuldade estimada, utilidade para o leitor, complementaridade com o acervo e viabilidade de apuração; não é uma promessa matemática de tráfego. Não criar vários posts para as variações “seguro residencial barato”, “preço” e “vendaval”: uma pauta reúne essas dúvidas inicialmente.

As fontes dos briefings são **candidatas a verificar**, não fontes alegadamente lidas. Os cinco resultados Google servem para orientar a pesquisa; a validação das demais SERPs fica na produção das pautas, conforme necessidade e saldo. Não é necessário gastar o crédito inteiro para utilizá-lo bem.

## Uso do teste e orçamento

| Recurso | Antes | Depois | Consumo medido |
|---|---:|---:|---:|
| SE Ranking | 99.500 créditos | 99.300 créditos | 200 créditos |
| DataForSEO | US$ 0,908 | US$ 0,898 | US$ 0,010 |

O custo observado é a diferença entre saldos vivos. Não extrapolar custo por palavra a partir dessa diferença: cache, lote e cobrança do fornecedor podem ter regras próprias. Cada SERP retornou custo de US$ 0,002; soma e diferença do saldo coincidiram. Não houve nova pesquisa paga de volume no DataForSEO, recarga ou assinatura.

SE Ranking informou validade até `2026-11-09 19:52:54`, sem fuso explícito. É a validade retornada pela **Data API**, não comprovação das condições de todos os recursos da interface web do teste. Não assumir que o saldo do DataForSEO expira nessa mesma data; sua validade não foi verificada.

Marcos de trabalho, ainda sem agendamento:

1. **27/10:** banco, calendário e dez briefings concluídos nesta etapa.
2. **28/10 a 06/11:** produzir o primeiro lote com fontes verificadas; guardar os dados de pesquisa e exportações no Git. As metas diárias são flexíveis e dependem de apuração.
3. **Até 06/11:** complementar lacunas úteis no SE Ranking e exportar qualquer auditoria acessível pela interface web. As ferramentas MCP disponíveis consultam a Data API, mas não oferecem criação de projeto, rastreamento de posições ou auditoria técnica completa da interface.
4. **Até 08/11:** conferir saldo/validade e concluir a revisão do banco antes da expiração informada, sem gastar créditos em consultas repetidas.
5. **Semanalmente:** rever Search Console (consultas, páginas, cliques, impressões e CTR) e GA4 da propriedade Giro73 `560739453`, filtrando domínio e considerando a amostra pequena. Manter acompanhamento com esses dados após o teste. Esse roteiro não criou rotinas automáticas de consulta.

## Fluxo editorial obrigatório

1. Buscar a pauta no acervo e na fila RSS; atualizar uma URL existente quando a intenção for a mesma. Não apagar artigos diferentes por mera semelhança de título.
2. Conferir fonte primária, data, localidade e eventual prazo; não inventar reportagem presencial, entrevistas, especialista ou calendário.
3. Acrescentar valor próprio: cálculo didático, roteiro local testado, documentos contextualizados ou comparação com condições datadas. Reescrever RSS não garante originalidade nem direitos de reprodução das fotos.
4. Selecionar imagem pertinente com licença, origem, autor e crédito verificados. Não reutilizar foto antiga como prova de um evento atual.
5. Produzir em Gutenberg; revisar título, abertura, descrição, H2, links internos e ALT natural. Não perseguir Rank Math 100, mínimo arbitrário de palavras ou repetição de keyword.
6. Revisar e publicar apenas após a apuração; validar URL, canonical, imagem, OG e schema. Conferir que a capa não aparece duplicada no corpo.
7. Registrar link e data de revisão no calendário. As datas propostas não são agendamentos WordPress. Pautas não apuradas permanecem pendentes; não publicar para cumprir uma cota.

O cadastro AdSense depende de políticas e avaliação do Google, sem número mágico de artigos ou promessa de aprovação. Manter transparência de autoria, navegação funcional, privacidade e direitos do conteúdo antes de solicitar. A produção de pautas originais deve melhorar a utilidade do portal, não apenas o volume.
