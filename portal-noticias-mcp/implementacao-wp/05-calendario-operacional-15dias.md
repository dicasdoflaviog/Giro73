# Calendário operacional — 22/10 a 05/11/2026

Um artigo de apoio por dia, três por editoria, às 08h de America/Bahia. As cinco bases já estão publicadas. Horários são sugestões de operação, sem alegação de horário ótimo comprovado.

**Estado da fila: pautas para apuração, não agendamentos ativos.** O CSV contém IDs reais das categorias e dos pilares, autor 1 e `wp_status=draft`. Nenhum serviço de disparo automático foi instalado ou ativado. A publicação depende de apuração e liberação editorial.

**Base de decisão:** consulta Search Console de 22/09 a 19/10/2026, para Search e Discover, sem linhas retornadas. As prioridades são hipóteses editoriais de utilidade local e intenção de busca; volume, CPC e receita não foram medidos.

| Data | Editoria (ID) | Pauta | Pilar a interligar |
|---|---|---|---|
| 2026-10-22 | noticias-regiao (1) | Obra anunciada ou em execução? Como conferir contratos em Teixeira de Freitas | [Pilar 20](https://giro73.com.br/extremo-sul-bahia-economia-infraestrutura-desenvolvimento/) |
| 2026-10-23 | dinheiro-renda-extra (10) | CET no empréstimo: o que entra na conta e como comparar propostas | [Pilar 21](https://giro73.com.br/planejamento-financeiro-renda-extra-credito-seguro/) |
| 2026-10-24 | tecnologia-inovacao (11) | Como ativar a verificação em duas etapas do WhatsApp | [Pilar 22](https://giro73.com.br/inteligencia-artificial-trabalho-pequenos-negocios/) |
| 2026-10-25 | esportes (12) | Futebol baiano: onde conferir tabelas e alterações de jogos | [Pilar 23](https://giro73.com.br/futebol-brasileiro-baiano-calendario-guia-torcedor/) |
| 2026-10-26 | famosos-entretenimento (13) | Onde assistir a uma série? Como conferir o catálogo oficial no Brasil | [Pilar 24](https://giro73.com.br/tv-streaming-brasil-tendencias-entretenimento/) |
| 2026-10-27 | noticias-regiao (1) | IBGE Cidades: como consultar dados de Teixeira de Freitas | [Pilar 20](https://giro73.com.br/extremo-sul-bahia-economia-infraestrutura-desenvolvimento/) |
| 2026-10-28 | dinheiro-renda-extra (10) | Renda extra digital: como calcular preço, custos e resultado de um serviço | [Pilar 21](https://giro73.com.br/planejamento-financeiro-renda-extra-credito-seguro/) |
| 2026-10-29 | tecnologia-inovacao (11) | Wi-Fi no pequeno negócio: o que testar antes de trocar o roteador | [Pilar 22](https://giro73.com.br/inteligencia-artificial-trabalho-pequenos-negocios/) |
| 2026-10-30 | esportes (12) | Ingresso online: cuidados para evitar golpes antes de ir ao estádio | [Pilar 23](https://giro73.com.br/futebol-brasileiro-baiano-calendario-guia-torcedor/) |
| 2026-10-31 | famosos-entretenimento (13) | Streaming com anúncios: o que comparar antes de contratar um plano | [Pilar 24](https://giro73.com.br/tv-streaming-brasil-tendencias-entretenimento/) |
| 2026-11-01 | noticias-regiao (1) | Alertas meteorológicos: como acompanhar avisos para o Extremo Sul da Bahia | [Pilar 20](https://giro73.com.br/extremo-sul-bahia-economia-infraestrutura-desenvolvimento/) |
| 2026-11-02 | dinheiro-renda-extra (10) | MEI e serviços digitais: o que verificar antes de formalizar a atividade | [Pilar 21](https://giro73.com.br/planejamento-financeiro-renda-extra-credito-seguro/) |
| 2026-11-03 | tecnologia-inovacao (11) | Dados de clientes em ferramentas de IA: checklist para pequenos negócios | [Pilar 22](https://giro73.com.br/inteligencia-artificial-trabalho-pequenos-negocios/) |
| 2026-11-04 | esportes (12) | Onde passa o jogo? Como confirmar canal, território e assinatura | [Pilar 23](https://giro73.com.br/futebol-brasileiro-baiano-calendario-guia-torcedor/) |
| 2026-11-05 | famosos-entretenimento (13) | Audiência de TV e streaming: por que rankings diferentes não são equivalentes | [Pilar 24](https://giro73.com.br/tv-streaming-brasil-tendencias-entretenimento/) |

## Regra prática de links

1. Cada artigo novo aponta para o pilar da própria categoria, com âncora descritiva e contexto real.
2. Depois de publicado, atualizar o pilar com um link contextual de retorno, se a nova matéria acrescentar detalhe útil. Não criar links para rascunhos ou para pautas que ainda não possuem URL pública.
3. Sugerir até três relacionados da mesma editoria entre posts publicados e pertinentes. Com apenas um pilar por categoria, esse conjunto ainda precisa crescer.
4. Links entre editorias são permitidos quando respondem à necessidade do leitor: negócio local → finanças/tecnologia; transmissão de futebol → streaming. Evitar recomendações artificiais só para aumentar o número de links.
5. Fontes externas: apontar para o documento primário relevante, com data de referência. Não aplicar nofollow a todas as fontes. Identificar conteúdo patrocinado/afiliado se vier a existir; Achadinhos permanece fora desta fase.

## Execução da automação

- Ler a linha do CSV, conferir que não existe post para a mesma intenção e buscar fontes primárias autorizadas.
- Produzir rascunho com o prompt mestre. Taxonomia e autor usam os IDs reais; não inventar imagens, preços, datas, citações ou experiências.
- Validar informações e fontes. Só após aprovação editorial mudar o estado para publicação ou agendamento, respeitando o fuso do site.
- Incluir imagem pertinente e licenciada com versão de pelo menos 1.200 px, variantes WebP, dimensões e alt descritivo. Os pilares atuais ainda não têm imagem destacada; a biblioteca estava vazia.
- Após publicar: verificar HTTP 200, canonical, metadescrição, H1, links, presença na capa/categoria e sitemap. Invalidar caches afetados quando necessário.

## Medição e manutenção

- D5 e D10: conferir indexação, erros de rastreamento e consultas disponíveis; corrigir informações efetivamente desatualizadas.
- D15: comparar impressões, cliques, CTR e páginas por editoria quando existir amostra; separar Search e Discover. Não concluir tendência com poucos dados.
- Priorizar imagens e cobertura local original. Não substituir apuração por escala de conteúdo gerado. Os cinco guias-base não constituem, por si só, aprovação do AdSense ou presença no Discover.

## Checagens de cada pauta

- **2026-10-22** — Portal oficial da Prefeitura e portal de transparência; contrato e etapa de execução. Localizar os canais atuais e registrar um exemplo documental real; não inventar obra.
- **2026-10-23** — Banco Central e documentos das instituições comparadas. Usar exemplo explicitamente hipotético ou propostas reais anonimizadas; revisão de cálculos.
- **2026-10-24** — Central de Ajuda oficial do WhatsApp. Testar os passos nas versões atuais; não pedir senhas nem códigos ao leitor.
- **2026-10-25** — Federação Bahiana de Futebol e regulamento da edição. Conferir edição e canais oficiais; não inserir datas ou participantes por suposição.
- **2026-10-26** — Página oficial da obra e do serviço de streaming. Testar consulta e registrar data; não assumir disponibilidade ou preços.
- **2026-10-27** — IBGE Cidades e Estados e notas metodológicas. Diferenciar ano de referência, fonte e território; capturas e exemplos conferidos.
- **2026-10-28** — Sebrae, regras tributárias oficiais e cálculo próprio identificado. Não prometer renda; separar horas, taxas, tributos e receita recebida.
- **2026-10-29** — Documentação do fabricante e recomendações de segurança da Anatel. Validar procedimentos; não declarar teste de equipamento que não foi feito.
- **2026-10-30** — Organizador, clube e bilheteria oficial do evento escolhido. Confirmar canais e regras reais; sem links de revenda não autorizada.
- **2026-10-31** — Termos e páginas oficiais dos planos comparados. Registrar preços e condições da data da consulta; diferenciar plano e adicionais.
- **2026-11-01** — INMET e canais oficiais da Defesa Civil. Explicar consulta sem inventar previsão ou alerta vigente.
- **2026-11-02** — Portal do Empreendedor e Receita Federal. Checar ocupação e regras vigentes; não afirmar que toda atividade digital pode ser MEI.
- **2026-11-03** — ANPD e termos do fornecedor exemplificado. Minimização de dados e exemplos fictícios; não prometer conformidade automática.
- **2026-11-04** — Organizador da competição e grade oficial do veículo. Escolher jogo real apenas após conferência; sem data ou direito de transmissão inventado.
- **2026-11-05** — Metodologia do levantamento original e dados públicos da Ancine. Identificar métrica, período e universo; não comprar ou reproduzir dados sem licença.
