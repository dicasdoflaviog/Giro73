# Alinhamento técnico e operacional — Giro73

Auditoria de leitura em 27/10/2026. Foram consultados WordPress/Easy MCP, HTML público dos 38 artigos publicados, sitemap, Search Console, GA4 e código do Git. Não houve alteração de artigos, status, agenda, cron, credenciais, cache ou configuração de publicação. Nenhuma consulta paga de pesquisa SEO foi feita.

## 1. Agendamento e frequência

O repositório contém um cron de **hora em hora**, `0 * * * *`, com timeout externo de 50 minutos e limite padrão de cinco candidatos. Isso é a configuração fornecida para instalação, não prova da configuração atual da VPS.

O WordPress apresenta quatro criações recentes no endpoint em `2026-10-27T00:00:54Z`, `03:00:54Z`, `06:01:05Z` e `09:00:43Z`, IDs 485, 487, 489 e 491. O espaçamento é próximo de **três horas**. Em America/Bahia correspondem a 21h do dia 26 e 00h, 03h e 06h do dia 27. São evidências de atividade recorrente, mas os logs não registram o processo chamador e não comprovam a expressão cron, a origem RSS/Telegram nem o status escolhido na criação.

Portanto, ainda não se pode afirmar “100% automático via cron/systemd” nem “o RSS está configurado de três em três horas”. É necessário ler o agendador e o banco/log do worker na VPS. Um processo pode ter agendamento horário e produzir posts apenas quando há candidatos; outro pode executar em três horas ou manter agenda própria.

No coletor do Git:

- Descoberta de até 50 entradas por feed autorizado, com filtros de URL, data, resumo mínimo e deduplicação.
- Processamento de até cinco candidatos **globais**, em rodízio persistente entre fontes; `--limit 1` a `--limit 10` muda esse teto.
- Não esvazia toda a fila de uma vez. Os candidatos não atendidos permanecem sujeitos à janela de idade e às regras de revisão.
- Sem daemon/systemd próprio no código do coletor; há um modelo de cron e um lock para evitar duas execuções simultâneas desse mesmo banco.

## 2. Publicação e fila editorial

A política aprovada no projeto habilita Agência Brasil (Últimas e Economia), G1 Bahia e Portal Leo Dias como candidatas a publicação automática. Todas as demais fontes e os novos cadastros institucionais têm destino inicial `draft`. A confirmação das opções atualmente gravadas em `/etc/giro73-rss.json` depende do relatório da VPS; o histórico fornecido já demonstrou publicação direta da Agência Brasil, mas não demonstra sozinho a política atual de todas as fontes.

Mesmo nas fontes automáticas, o código exige, entre outros critérios, pelo menos **600 caracteres** de material e texto gerado, data dentro da janela automática padrão de 24 horas, imagem resolvida e tamanho mínimo de 400 px no menor lado da imagem verificada. Sinais de alegações sensíveis, fonte genérica, determinados formatos de data histórica e linguagem promocional encaminham para revisão. Isso não é checagem factual completa, não equivale a 600 palavras e não garante nota verde no Rank Math. Se a imagem falha, o endpoint pode preservar a matéria em rascunho.

O endpoint tem fallback `publish` quando o status é omitido. O coletor RSS envia `status` explicitamente. Outros emissores, inclusive um bot independente, precisam fazer o mesmo; não se presume que compartilhem a política local do RSS.

As quatro matérias 485/487/489/491, que apareciam em rascunho na leitura anterior, agora estão publicadas com data de publicação posterior ao respectivo registro de criação. Isso não identifica quem aprovou nem permite atribuir a promoção ao RSS. Não foi modificada essa situação na auditoria.

**Não há média diária confiável do RSS medida nesta etapa.** Com um único ciclo horário e limite cinco, o teto teórico é 120 candidatos/dia; com um ciclo de três horas e limite cinco, 40 candidatos/dia. São cenários, não volumes confirmados nem promessas de artigos publicados. O total real depende da fila, das fontes, filtros, falhas, revisão e outras rotinas.

## 3. Uso da planilha e ferramentas SEO

O calendário contém **30 pautas (27 novas e três atualizações), 92 palavras-chave e dez briefings**. Ele orienta uma produção editorial planejada, humana ou assistida por IA, com revisão de fontes. **O coletor não lê essa planilha/CSV, não consulta SE Ranking/DataForSEO para cada RSS e não cruza automaticamente palavras-chave com releases.** Nenhuma agenda automática de produção dessas 30 pautas foi instalada.

Rotina proposta:

1. Selecionar uma pauta do calendário, respeitando prioridade e recursos de apuração; uma por dia é meta flexível, não cota automática.
2. Conferir se já existe URL com a mesma intenção e usar os links internos indicados.
3. Ler e confirmar fontes primárias, data e local; completar cálculo, roteiro, comparação ou contexto próprio com evidência.
4. Produzir em Gutenberg, configurar os três campos principais Rank Math e imagem pertinente/licenciada.
5. Revisar e publicar ou atualizar a URL existente; registrar estado e URL final no controle editorial.
6. Rever Search Console e GA4 semanalmente, com prazo para maturação e sem prometer visualizações.

As pautas planejadas e a fila RSS são duas entradas editoriais distintas. Integrá-las é uma próxima implementação, não funcionalidade existente. Evitar adaptar uma notícia sem relação para caber em uma keyword de CPC alto.

Na última pesquisa, em 27/10, SE Ranking tinha 99.300 créditos e validade retornada pela Data API até 09/11/2026 (horário sem fuso explícito); DataForSEO tinha US$ 0,898. Esses saldos não foram consultados novamente nesta auditoria. As exportações no Git permanecem utilizáveis depois do teste. Validade da Data API não comprova condições de todos os recursos web do teste.

## 4. Panorama conferido em produção

| Item | Resultado desta leitura |
|---|---|
| Artigos | **38 publicados e quatro rascunhos**, 42 no total consultado |
| Editorias | Seis: Região, Política, Dinheiro, Tecnologia, Esportes e Famosos |
| Páginas publicadas | Cinco institucionais e Agenda 73 |
| Campos principais SEO | Palavra-chave, título SEO e descrição preenchidos nos 38 publicados |
| Acesso público dos artigos | 38/38 HTTP 200, um H1, canonical correspondente, sem noindex detectado |
| Schema de artigo | NewsArticle/OpinionNewsArticle presente nos 38; presença não equivale a validação completa de cada propriedade |
| Sitemap | Índice, posts, páginas e categorias HTTP 200; todos os 38 links públicos encontrados no sitemap de posts |
| Robots | Permite rastreamento público, restringe wp-admin e declara sitemap_index.xml |
| Capa | 37 dos 38 têm mídia destacada; **435 está sem capa e sem og:image** |
| Endpoint | Habilitado, token configurado; quatro criações mais recentes com HTTP 201, cerca de 3,3–4,4 s cada |

Os 100 registros mantidos pelo endpoint contêm 56 `created`, um `replayed`, 38 respostas 422 e cinco respostas 400. O último erro dessa amostra ocorreu em 25/10/2026, ligado a dimensões de imagem; os erros de whitelist/editor de imagem são históricos. Não se usa essa janela truncada como taxa de disponibilidade, e esta auditoria não realizou um novo POST de criação autenticado.

Indexável é diferente de indexado. Na URL Inspection, a **Home está “Enviada e indexada”**. O artigo `conta-digital-pj-como-escolher/` ainda aparece como **“O Google não reconhece o URL”**, embora esteja público e no sitemap. Não foram inspecionados todos os artigos no Google. A API de sitemaps informa download em 27/10 e zero erros/avisos; seus contadores não substituem o inventário atual de cobertura por URL.

Métricas consultadas, período 29/09 a 26/10/2026:

- Search Console, busca web agregada: **quatro impressões, zero cliques**. A consulta anterior agrupada por palavra não ter retornado linhas não significava zero impressões; consultas podem ser omitidas.
- GA4, propriedade Giro73 `560739453`, hostname Giro73: **sete sessões, quatro usuários ativos e 11 visualizações**. O período selecionado não significa que a propriedade tivesse dados durante os 28 dias inteiros; foi criada em 25/10. Não é possível estimar crescimento ou receita a partir dessa amostra.

## Pendências editoriais e operacionais reais

- Confirmar cron/timers, worker responsável, políticas efetivamente instaladas e produção real com logs/SQLite da VPS.
- Continuar a validar novas fontes; o último log de cadastro informado tinha 17, sem confirmação de funcionamento de todas. Os testes falhos de RSS são falhas de descoberta/ingestão, não evidência de falha no endpoint WordPress.
- Revisar datas e contexto: o **post 485**, hoje publicado, usa como fonte um endereço de Itabela com referência `09-09-2026` e texto em tom de novidade. A data no URL deve ser confirmada no original; não tratar a coleta/publicação de outubro como prova de fato novo. O post 433 usa outra URL de `23-09-2026` e já contextualiza o histórico; mesma cidade/bairro não prova duplicidade. Não houve exclusão ou alteração de status.
- Resolver a capa ausente do 435 com imagem pertinente e autorizada. Preencher os campos Rank Math não assegura nota 80+, originalidade ou direitos das fotos.
- Acompanhar indexação e iniciar a produção planejada com fontes verificadas. Não há novos dados de PageSpeed/CWV nesta auditoria.
- Não há comprovação de solicitação/aprovação AdSense; esta conferência técnica não certifica elegibilidade editorial ou aprovação pelo Google.

Foi preparado `automacao-rss/auditar_operacao_vps.py` para preencher a lacuna operacional: lê expressões cron, timers candidatos, fontes sem segredos, fila SQLite e amostra resumida de conclusões. Não lê `.env`, não imprime comandos completos e não altera agendamentos ou publicações. Também não identifica automaticamente workers com nomes/caminhos desconhecidos.

Evidências consolidadas: [validação do alinhamento operacional](implementacao-wp/validacao-alinhamento-operacional.json). A falha do lote Porto Seguro/Eunápolis/Itamaraju foi informada sem log detalhado nesta solicitação; não se atribuiu automaticamente a ela a causa encontrada nas fontes de Belmonte/Itapebi. A expansão fica pausada enquanto se alinha o funcionamento atual.
