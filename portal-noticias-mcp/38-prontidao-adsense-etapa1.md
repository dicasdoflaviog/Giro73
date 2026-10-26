# Prontidão AdSense — execução da etapa 1

Verificação em 26/10/2026, horário de referência America/Bahia. O trabalho utilizou o WordPress/Easy MCP, a inspeção de URLs do Google Search Console e requisições HTTPS públicas. Não foi criada conta AdSense nem solicitada análise.

## Indexação: amostra consultada no Google

| Página | Resposta da inspeção |
|---|---|
| Home | Enviada e indexada; rastreamento e indexação permitidos; canonical escolhido coincide com o declarado |
| Editoria Política | Enviada e indexada; rastreamento e indexação permitidos; canonical correto |
| Guia do Registrato | Enviada e indexada; rastreamento e indexação permitidos; canonical correto |
| Notícia do Fies, publicada em 26/10 | O Google não reconhece o URL; sem histórico de rastreamento retornado |

A inspeção informa o estado conhecido pelo Google; não executa um teste ao vivo nem solicita indexação. Uma matéria publicada no mesmo dia ainda desconhecida não comprova bloqueio técnico. A amostra não permite afirmar que todos os 31 artigos estejam indexados. O campo legado `indexed` do relatório de sitemaps não deve ser usado isoladamente como total de páginas indexadas.

## Correções aplicadas no WordPress

- Página 3, Política de Privacidade: descritos a tag Google/Site Kit, reações e enquetes, cookie funcional, armazenamento de clima e frequência de banners, Open-Meteo, acesso ao grupo WhatsApp, terceiros e direitos. A página registra a atualização real de 26/10/2026. Não afirma que o AdSense esteja ativo ou que exista CMP validada.
- Página 7, Sobre Nós: adicionada Política & Poder, com link para a editoria existente, sem recriar categorias ou páginas.
- Artigos 289, 290 e 306: corrigido o destino inexistente de macrodrenagem para a matéria 308 existente. No próprio artigo 308 foi retirada a indicação que apontava ao endereço inexistente do mesmo assunto.
- Mídias 365, 361, 359 e 328: preenchido ALT descritivo após inspeção visual, sem atribuir identidade, cargo ou local apenas pela imagem. Créditos e licenças não foram inventados.
- Mídias 96–99: removido somente o vínculo com os antigos posts 21–24, já na lixeira. Essas quatro fotos continuam utilizadas por seis artigos preservados; arquivos, títulos, ALT, créditos e vínculos `featured_media` foram mantidos. A consulta pública da biblioteca deixava de listá-las porque herdavam o contexto do post-pai na lixeira.

As versões publicadas de privacidade e Sobre Nós estão em [politica-de-privacidade.html](implementacao-wp/politica-de-privacidade.html) e [sobre-nos.html](implementacao-wp/sobre-nos.html). Os backups privados dos seis conteúdos anteriores e das oito mídias foram mantidos fora do repositório.

## Inventário e pendências editoriais

Foram encontrados 31 posts publicados em seis editorias. A checagem inicial percorreu 51 URLs internas distintas e identificou um único destino HTTP 404 referenciado quatro vezes; as quatro ocorrências foram corrigidas. Endereços de artigos retirados anteriormente não foram redirecionados para assuntos sem relação.

A fila [38-fila-revisao-editorial.csv](38-fila-revisao-editorial.csv) contém oito matérias prioritárias:

1. **381:** programação de maio de 2026 descrita como futura em publicação de outubro.
2. **333:** expediente de junho/julho apresentado sem delimitação histórica suficiente.
3. **306:** horário de atendimento informado como atual com fonte apenas em um índice.
4. **335:** conferir data, dia da semana e documentos do primeiro quadrimestre.
5. **370, 340 e 345:** anúncios de maio sobre UBS, ponte e escola precisam de contexto temporal e checagem da situação atual.
6. **308:** localizar referência específica da macrodrenagem e revisar alegações de impacto e a data indicada.

Nenhuma dessas oito matérias foi apagada, despublicada ou reescrita como fato confirmado nesta etapa. A tentativa de consultar a fonte oficial do artigo 381 foi bloqueada pelo proxy com HTTP 403 antes de chegar ao destino. A revisão não deve transformar publicidade institucional em apuração independente nem alongar textos para obter uma nota de plugin.

As 31 matérias possuem imagem destacada associada, usando 28 arquivos distintos. Doze matérias têm capas originais menores que 1.200 px de largura: 414, 372, 336, 341, 346, 333, 351, 289, 290, 306, 317 e 319. Isso é uma oportunidade de melhoria para grandes prévias; não foi aplicada ampliação artificial, nem declarada inelegibilidade automática para anúncios. Conferir créditos e condições de reutilização continua necessário. As quatro fotos compartilhadas 96–99 já possuem fotógrafo, referência e licença registrados; ausência em uma consulta pública não significava ausência de ALT ou de crédito.

## Medição e consentimento

A conta de serviço do Easy MCP retornou **zero contas/propriedades GA4 acessíveis**. Isso pode indicar falta de permissão ou configuração; não prova ausência de propriedade nem ausência de coleta. A Home carrega uma tag Google via Site Kit. Antes de configurar outra propriedade, conferir a existente e conceder à conta de serviço exibida no Easy MCP acesso de leitura à propriedade correta.

A política atualizada descreve os recursos observados, mas sua publicação não implementa consentimento. Ainda é preciso conferir a configuração efetiva da tag, a base aplicável aos tratamentos e os controles necessários. Ao integrar AdSense, verificar as exigências de mensagens/CMP para os públicos atendidos.

Não houve consulta paga ao SE Ranking ou ao DataForSEO nesta etapa. A pesquisa de concorrentes e pautas fica na etapa seguinte; o acesso à Data API do SE Ranking não inclui ferramentas de criação de projeto nem de execução da auditoria técnica do painel.

## Próxima execução

1. Validar fontes e corrigir as oito matérias da fila, começando por datas e informações de serviço; registrar correções substanciais sem alterar artificialmente a data original.
2. Conferir direitos e créditos das fotografias importadas e obter arquivos maiores quando disponíveis e autorizados.
3. Validar o acesso ao GA4 e as configurações de consentimento.
4. Retomar a pesquisa regional no SE Ranking e transformar resultados em pautas originais, com fontes e links internos.
5. Solicitar análise AdSense quando essas pendências estiverem tratadas. Não há mínimo universal de artigos, nota Rank Math ou tráfego que assegure aprovação.

Os resultados públicos e o resumo das inspeções estão em [validacao-prontidao-etapa1.json](implementacao-wp/validacao-prontidao-etapa1.json). Nenhuma credencial integra os artefatos versionados.

## Validação após as alterações

- As duas páginas continuam publicadas; seu HTML salvo corresponde aos arquivos versionados.
- Os quatro artigos continuam publicados e conservaram suas datas originais de publicação.
- As 50 URLs internas ainda referenciadas responderam HTTP 200 e não declararam `noindex`. A verificação cobre esse conjunto, não todo endereço possível do site.
- As 28 mídias usadas como capas estão acessíveis na consulta pública e todas possuem ALT. As quatro mídias compartilhadas estão sem post-pai, mantendo os créditos existentes.
- Índice, sitemap de posts, de páginas e de categorias responderam HTTP 200 com XML válido. Robots continua declarando o índice correto.
- A limpeza direcionada do LiteSpeed confirmou 15 conteúdos e suas páginas relacionadas; o cache do sitemap Rank Math também foi limpo. Assets do Elementor não foram alterados nem tiveram cache regenerado.
- As verificações não instalaram plugins, não criaram notícias de teste e não alteraram o cron ou a política de publicação da VPS.
