# Giro73 — SEO, indexação e integridade editorial

Verificação em 28/10/2026. Foram auditados os 55 posts publicados encontrados no WordPress e aplicadas correções em 11 posts recentes. A auditoria de metadados não é uma checagem factual completa dos 55 textos.

## Causa confirmada

- A palavra-chave “Bahia x São Paulo” não correspondia a “Bahia e São Paulo” no título, descrição, abertura e URL. O post 559 também estava em Tecnologia por causa da origem do feed.
- No motor em produção, a ausência de `image_alt` era convertida em string vazia, substituindo o ALT do conteúdo. Foram preenchidos os ALTs das 11 capas após inspeção visual, sem identificar pessoas ou locais por suposição.
- Havia metadados longos, palavras-chave pouco naturais e falta de navegação interna no conteúdo recente.

## Correções aplicadas no WordPress

Palavras-chave alinhadas a expressões realmente presentes, descrições objetivas, subtítulos quando pertinentes, links para a editoria e ALT descritivo. Post 559 reclassificado em Esportes, preservando a URL. Nenhuma pontuação Rank Math foi escrita ou simulada.

O post 539 foi revisto a partir do comunicado oficial da Prefeitura de Teixeira de Freitas, com retirada de horários não confirmados e generalizações sobre o município/gestão. O texto anterior está preservado na revisão 540; a versão corrigida tem revisão 577. Não houve exclusão de matérias nem mudança de slug.

O motor do WordPress está ativo, sem erro informado pelo Code Snippets, e passa a preservar em rascunho novas matérias cuja palavra-chave esteja ausente ou incoerente com título, título SEO, descrição ou abertura. A resolução idempotente ocorre antes da nova validação: respostas perdidas de solicitações antigas continuam recuperáveis. O ALT existente é preservado; um valor explícito também pode ser enviado pelo coletor.

## Coletor — preparado no Git, implantação na VPS pendente

Classificação pelo assunto, independente do nome do feed; palavra-chave natural e coerente; validação SEO por item; links para editorias reais; ALT original ou identificação transparente de imagem de apoio. Fontes com `seo_required: true` mantêm inconsistências em rascunho. A atualização da política grava essa opção nas fontes automáticas. Não altera payloads já tentados nem republica matérias antigas.

117 testes Python passaram com APIs simuladas. Sintaxe das duas versões PHP conferida por parser; não foi executada uma publicação real para testar o novo bloqueio do endpoint. A correção do WordPress não instala o Python na VPS.

## Verificação pública das 11 páginas alteradas

HTTP 200, canonical preservado, um H1, palavra-chave no título SEO publicado e um artigo no grafo de dados estruturados com autor e datas. `robots.txt`, índice de sitemaps, sitemap de posts e `llms.txt` responderam HTTP 200. Isso demonstra acessibilidade técnica na conexão testada; não confirma indexação no Google ou citação por LLMs.

## Política de integridade

- Não exigir 600 palavras para notas factuais; ampliar somente com informações confirmadas e úteis.
- Não inserir números, nomes, estatísticas, FAQ, promessas ou contexto local sem fonte.
- Não usar ALT como depósito de palavras-chave; descrever o que foi observado ou preservar a descrição da fonte.
- Não mudar URLs publicadas para pontuar no plugin; novos títulos devem gerar slugs claros.
- Usar fonte específica, atribuição, datas reais e autoria responsável; separar fato, previsão e arquivo.
- Sumário somente em conteúdo longo que o justifique. Links de contexto devem apontar para páginas reais e pertinentes.
- Schema deve refletir o conteúdo visível. Não criar FAQ/schema apenas para buscar destaque.
- O Rank Math é uma ferramenta de revisão. A análise deve ser recalculada no editor; a nota armazenada pode continuar antiga até isso acontecer.

## Pendências editoriais

- **535, transparência em Itabela:** a referência é `/noticias?pg=2`, uma listagem. Ela não sustenta os trechos sobre corrupção histórica, criação recente de portal e desempenho da gestão. O texto não foi tratado como validado nem reescrito com novos fatos; exige fonte específica e revisão.
- **533, Dia do Servidor Público:** a fonte específica não ficou acessível pela ferramenta de consulta. SEO e ALT foram corrigidos, mas as afirmações administrativas e os trechos de contexto continuam exigindo conferência documental.
- **542, boicote a empresas:** alegações e consequências econômicas descritas exigem revisão da fonte e atribuição. A correção técnica de SEO não certifica essas afirmações.
- **564, João Pedro:** a capa contém jogador com uniforme azul e amarelo; o ALT descreve a imagem sem presumir identidade. A adequação editorial da imagem ao fato narrado deve ser conferida.

## Inventário dos 55 posts

“Coerente” verifica presença literal da palavra-chave no título, título SEO, descrição e primeiro parágrafo. Falhar nessa checagem não demonstra, por si só, falha de indexação. “ALT vazio” refere-se às imagens no conteúdo cadastrado, sem avaliar todo o template.

| Post | Matéria | Coerência | ALT vazio | Link interno no conteúdo | Situação |
|---|---|---|---|---|---|
| 562 | [Dólar cai quase 1% e Ibovespa recua após reeleição de Lula](https://giro73.com.br/dolar-cai-quase-1-e-ibovespa-recua-apos-reeleicao-de-lula/) | Sim | Não | Sim | SEO corrigido |
| 542 | [Bolsonaristas em Teixeira e Medeiros Neto organizam boicote a empresas](https://giro73.com.br/bolsonaristas-em-teixeira-e-medeiros-neto-organizam-boicote-a-empresas/) | Sim | Não | Sim | SEO corrigido |
| 564 | [João Pedro sofre lesão e pode ficar fora da Seleção Brasileira](https://giro73.com.br/joao-pedro-sofre-lesao-e-pode-ficar-fora-da-selecao-brasileira/) | Sim | Não | Sim | SEO corrigido |
| 559 | [Bahia e São Paulo se enfrentam pelo Brasileirão na Arena Fonte Nova](https://giro73.com.br/bahia-e-sao-paulo-se-enfrentam-pelo-brasileirao-na-arena-fonte-nova/) | Sim | Não | Sim | SEO corrigido |
| 556 | [Psicóloga de 71 anos morre após acidente na BR-101 em Mucuri](https://giro73.com.br/psicologa-de-71-anos-morre-apos-acidente-na-br-101-em-mucuri/) | Sim | Não | Sim | SEO corrigido |
| 553 | [Valzinho solicita cadastro para 100 casas populares em Itabatã](https://giro73.com.br/valzinho-solicita-cadastro-para-100-casas-populares-em-itabata/) | Sim | Não | Sim | SEO corrigido |
| 550 | [Finalistas do Prêmio TV Brasil Petrobras Para Elas no meio-campo](https://giro73.com.br/finalistas-do-premio-tv-brasil-petrobras-para-elas-no-meio-campo/) | Sim | Não | Sim | SEO corrigido |
| 547 | [Alta nos preços da arroba do boi na Bahia e outros estados](https://giro73.com.br/alta-nos-precos-da-arroba-do-boi-na-bahia-e-outros-estados/) | Sim | Não | Sim | SEO corrigido |
| 544 | [Dolphin GT: O Novo Rival de Polo e Onix no Brasil](https://giro73.com.br/dolphin-gt-o-novo-rival-de-polo-e-onix-no-brasil/) | Sim | Não | Sim | SEO corrigido |
| 539 | [Inscrições para seleção das creches municipais de Teixeira de Freitas seguem até 10 de novembro](https://giro73.com.br/inscricoes-para-selecao-das-creches-municipais-de-teixeira-de-freitas-seguem-ate-10-de-novembro/) | Sim | Não | Sim | SEO corrigido e texto conferido na fonte |
| 535 | [Transparência na Gestão Pública: Acesso à Informação em Itabela](https://giro73.com.br/transparencia-na-gestao-publica-acesso-a-informacao-em-itabela/) | Revisar | Sim | Sim | Revisão editorial prioritária: fonte genérica |
| 533 | [CMTF transfere comemoração do Dia do Servidor Público para 30 de outubro](https://giro73.com.br/cmtf-transfere-comemoracao-do-dia-do-servidor-publico-para-30-de-outubro/) | Sim | Não | Sim | SEO corrigido |
| 502 | [Vereadores repercutem eleição presidencial e aprovam PL do Executivo em Teixeira de Freitas](https://giro73.com.br/vereadores-repercutem-eleicao-presidencial-e-aprovam-pl-do-executivo-em-teixeira-de-freitas/) | Revisar | Não | Sim | Auditado; sem alteração |
| 505 | [Itabela é contemplada com projeto de robótica e inovação para estudantes](https://giro73.com.br/itabela-e-contemplada-com-projeto-de-robotica-e-inovacao-para-estudantes/) | Revisar | Não | Sim | Auditado; sem alteração |
| 500 | [Dança na Praça: Evento reúne mais de 65 pessoas em Teixeira de Freitas](https://giro73.com.br/danca-na-praca-evento-reune-mais-de-65-pessoas-em-teixeira-de-freitas/) | Sim | Não | Sim | Auditado; sem alteração |
| 497 | [Arquivo: Itabela anunciou luto por Dona Didi em agosto de 2026](https://giro73.com.br/prefeitura-de-itabela-decreta-luto-oficial-de-tres-dias-pela-morte-de-dona-didi/) | Revisar | Não | Não | Auditado; sem alteração |
| 489 | [Arquivo: anúncio de setembro previa Supercross em Itabela](https://giro73.com.br/copa-brasil-de-supercross-em-itabela-velocidade-e-emocao-na-4a-etapa-com-r-35-mil-em-premiacao/) | Revisar | Não | Não | Auditado; sem alteração |
| 491 | [Arquivo: nota de Itabela sobre pregão tem referência de agosto](https://giro73.com.br/nota-de-esclarecimento-sobre-pregao-eletronico-no-007-2026-em-itabela/) | Revisar | Não | Não | Auditado; sem alteração |
| 487 | [Arquivo: Defesa Civil de Itabela relatou capacitação em setembro](https://giro73.com.br/defesa-civil-de-itabela-participa-de-capacitacao-sobre-mapeamento-de-areas-de-risco-geologico/) | Revisar | Não | Não | Auditado; sem alteração |
| 485 | [Arquivo: Itabela anunciou regularização de 325 imóveis em setembro](https://giro73.com.br/prefeitura-de-itabela-avanca-na-regularizacao-de-325-imoveis-no-bairro-pereirao/) | Revisar | Não | Não | Auditado; sem alteração |
| 481 | [Conta digital PJ: 7 cuidados antes de abrir para seu negócio](https://giro73.com.br/conta-digital-pj-como-escolher/) | Sim | Não | Sim | Auditado; sem alteração |
| 479 | [Lamine Yamal e Vicky López vencem Troféu Kopa 2026](https://giro73.com.br/lamine-yamal-e-vicky-lopez-vencem-trofeu-kopa-2026/) | Revisar | Não | Não | Auditado; sem alteração |
| 441 | [Itabela brilha na estreia da Copa CONDESC e vence Itagimirim por 3 a 2 no Manzolão](https://giro73.com.br/itabela-brilha-na-estreia-da-copa-condesc-e-vence-itagimirim-por-3-a-2-no-manzolao/) | Revisar | Não | Sim | Auditado; sem alteração |
| 439 | [Pauta da Reunião Ordinária de 27 de outubro de 2026 em Teixeira de Freitas](https://giro73.com.br/pauta-da-reuniao-ordinaria-de-27-de-outubro-de-2026-em-teixeira-de-freitas/) | Sim | Não | Sim | Auditado; sem alteração |
| 433 | [Itabela: anúncio de setembro previa 322 títulos no bairro Pereirão](https://giro73.com.br/prefeitura-de-itabela-garante-322-titulos-de-propriedade-para-familias-do-bairro-pereirao/) | Sim | Não | Sim | Auditado; sem alteração |
| 435 | [SIC da Câmara de Itabela: como pedir informações públicas](https://giro73.com.br/camara-municipal-de-itabela-transparencia-e-acesso-a-informacao/) | Sim | Não | Sim | Auditado; sem alteração |
| 409 | [Eleitora vinda da Espanha chega após fechamento das urnas na Bahia, relata portal](https://giro73.com.br/eleitora-da-espanha-chega-tarde-e-perde-chance-de-votar-na-bahia/) | Sim | Não | Sim | Auditado; sem alteração |
| 414 | [Teixeira de Freitas mobiliza equipes após vendaval de 25 de outubro](https://giro73.com.br/prefeito-de-teixeira-de-freitas-mobiliza-equipes-para-recuperacao-apos-vendaval/) | Revisar | Não | Sim | Auditado; sem alteração |
| 411 | [Fies: inscrições para vagas remanescentes têm prazo até 3 de novembro](https://giro73.com.br/inscricoes-para-vagas-remanescentes-do-fies-estao-abertas/) | Sim | Não | Sim | Auditado; sem alteração |
| 381 | [Costa das Baleias: anúncio de maio previa missão turística no Ceará](https://giro73.com.br/missao-empresarial-leva-empresarios-da-costa-das-baleias-para-imersao-em-turismo-de-experiencia-no-ceara/) | Revisar | Não | Sim | Auditado; sem alteração |
| 377 | [CRAS Vila Vargas recebe ação do Outubro Rosa em Teixeira de Freitas](https://giro73.com.br/prefeitura-de-teixeira-de-freitas-realiza-acao-do-outubro-rosa-no-cras-vila-vargas/) | Revisar | Não | Sim | Auditado; sem alteração |
| 372 | [Itabela relata participação em encontro de Defesa Civil realizado em outubro](https://giro73.com.br/itabela-e-representada-no-2o-encontro-nacional-das-defesas-civis-municipais-e-debate-impactos-do-el-nino-2026-2027/) | Revisar | Não | Sim | Auditado; sem alteração |
| 370 | [Cumuruxatiba: anúncio de maio relatava reforma da UBS](https://giro73.com.br/saude-fortalecida-ubs-de-cumuruxatiba-e-reinaugurada-com-estrutura-moderna/) | Revisar | Não | Sim | Auditado; sem alteração |
| 340 | [Ponte sobre o Rio Jucuruçu: anúncio de maio descrevia obras em Prado](https://giro73.com.br/obras-da-nova-ponte-sobre-o-rio-jucurucu-avancam-em-ritmo-acelerado/) | Sim | Não | Sim | Auditado; sem alteração |
| 346 | [Basevi: relato de relatório ambiental de 2025 aguarda revisão](https://giro73.com.br/devastacao-ambiental-em-prado-relatorio-da-secretaria-de-meio-ambiente-revela-situacao-alarmante/) | Revisar | Sim | Sim | Auditado; sem alteração |
| 335 | [Prado: referência de maio trata das metas fiscais do primeiro quadrimestre](https://giro73.com.br/audiencia-publica-de-avaliacao-das-metas-fiscais-em-prado-compromisso-com-a-transparencia/) | Revisar | Não | Sim | Auditado; sem alteração |
| 333 | [Câmara de Teixeira de Freitas: como conferir alterações de expediente](https://giro73.com.br/camara-municipal-de-teixeira-de-freitas-importancia-da-cultura-e-alteracoes-administrativas/) | Revisar | Não | Sim | Auditado; sem alteração |
| 345 | [Guarani: anúncio de maio previa escola modelo no distrito de Prado](https://giro73.com.br/escolha-da-educacao-guarani-vai-receber-uma-das-maiores-e-mais-modernas-escolas-da-regiao/) | Revisar | Não | Sim | Auditado; sem alteração |
| 351 | [Itabela divulgou luto oficial pela morte de Lourival da Silva Rocha](https://giro73.com.br/prefeitura-de-itabela-decreta-luto-oficial-de-tres-dias-pela-morte-de-lourival/) | Revisar | Não | Sim | Auditado; sem alteração |
| 289 | [Prado: divulgação de setembro registra prestação de contas do segundo quadrimestre](https://giro73.com.br/prado-realiza-audiencia-publica-de-prestacao-de-contas-do-2o-quadrimestre-de-2026/) | Revisar | Não | Sim | Auditado; sem alteração |
| 290 | [Prado: Câmara relatou aprovação de projeto para secretaria de igualdade racial](https://giro73.com.br/camara-municipal-de-prado-aprova-criacao-da-secretaria-municipal-de-politicas-de-promocao-da-igualdade-racial/) | Revisar | Não | Sim | Auditado; sem alteração |
| 306 | [Câmara de Teixeira de Freitas: como confirmar o horário de atendimento](https://giro73.com.br/atencao-horario-de-funcionamento-da-camara-de-teixeira-de-freitas/) | Revisar | Não | Sim | Auditado; sem alteração |
| 308 | [Macrodrenagem em Prado: como conferir documentos e andamento das obras](https://giro73.com.br/macrodrenagem-em-prado-avancos-e-impactos-na-comunidade/) | Sim | Não | Sim | Auditado; sem alteração |
| 317 | [Oficiais da 87ª CIPM concluem formação de atendimento pré-hospitalar tático](https://giro73.com.br/oficiais-da-87a-cipm-concluem-curso-de-aph-tatico-ministrado-pela-forca-nacional-em-teixeira-de-freitas/) | Revisar | Não | Sim | Auditado; sem alteração |
| 319 | [Criança de 4 anos morre após queda em cisterna em Teixeira de Freitas](https://giro73.com.br/crianca-de-4-anos-morre-apos-cair-em-cisterna-em-teixeira-de-freitas/) | Sim | Não | Sim | Auditado; sem alteração |
| 217 | [Jogos de Bahia e Vitória: onde conferir a tabela antes de planejar a ida ao estádio](https://giro73.com.br/jogos-bahia-vitoria-conferir-tabela-oficial/) | Sim | Não | Sim | Auditado; sem alteração |
| 216 | [Empréstimo no seu CPF: como organizar a consulta ao Registrato e contestar o que não reconhece](https://giro73.com.br/registrato-consultar-emprestimos-cpf/) | Revisar | Não | Sim | Auditado; sem alteração |
| 215 | [BR-101 no Extremo Sul: o que conferir antes de sair de Teixeira ou seguir para Porto Seguro](https://giro73.com.br/br-101-extremo-sul-bahia-checar-viagem/) | Sim | Não | Sim | Auditado; sem alteração |
| 214 | [Recursos para os municípios: como acompanhar emendas e transferências no Extremo Sul](https://giro73.com.br/transferencias-emendas-municipios-extremo-sul-bahia/) | Revisar | Não | Sim | Auditado; sem alteração |
| 213 | [Chás e remédios: por que “natural” não significa livre de risco](https://giro73.com.br/chas-remedios-cuidados-produtos-naturais/) | Revisar | Não | Sim | Auditado; sem alteração |
| 212 | [Streaming no orçamento: o que olhar antes de contratar outro plano](https://giro73.com.br/streaming-comparar-planos-assinatura-vitalicia/) | Sim | Não | Sim | Auditado; sem alteração |
| 211 | [Ba-Vi: a torcida começa antes do jogo — e exige conferir onde comprar](https://giro73.com.br/ba-vi-torcedor-tabela-ingressos-seguranca/) | Revisar | Não | Sim | Auditado; sem alteração |
| 210 | [Conta invadida? A ordem dos primeiros passos faz diferença](https://giro73.com.br/conta-invadida-primeiros-passos-seguranca/) | Sim | Não | Sim | Auditado; sem alteração |
| 209 | [Orçamento municipal: cinco documentos para entender as prioridades da prefeitura](https://giro73.com.br/orcamento-municipal-documentos-prioridades-prefeitura/) | Revisar | Não | Sim | Auditado; sem alteração |
| 208 | [Renda extra: quanto sobra de verdade depois dos custos?](https://giro73.com.br/renda-extra-calcular-ganho-real/) | Revisar | Não | Sim | Auditado; sem alteração |

## Referências

- [Comunicado oficial sobre seleção nas creches](https://www.teixeiradefreitas.ba.gov.br/inscricoes-para-selecao-das-creches-municipais-de-teixeira-de-freitas-seguem-ate-10-de-novembro/).
- [Google: conteúdo útil e confiável](https://developers.google.com/search/docs/fundamentals/creating-helpful-content).
- [Google: recursos de IA e seu site](https://developers.google.com/search/docs/appearance/ai-features): aplicam-se as boas práticas de SEO; não há marcação especial obrigatória para AI Overviews/AI Mode.
- [Google: dados estruturados Article](https://developers.google.com/search/docs/appearance/structured-data/article).
