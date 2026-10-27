# SE Ranking — diagnóstico da auditoria e ordem de correção

Data da análise: 27/10/2026. Capturas do responsável: relatório de **26/10/2026 às 17:04:04**, projeto exibido como `www.giro73.com.br`. Nenhuma configuração do WordPress, firewall, DNS, cache ou SE Ranking foi alterada nesta etapa.

## O que as capturas demonstram

- Saúde técnica 77/100; recomendação da ferramenta de 90+.
- 46 páginas rastreadas: quatro com resposta 2xx e 42 com resposta 4xx.
- 145 ocorrências: 131 erros, oito avisos e seis notificações.
- Entre os erros: 42 respostas 4xx; 40 URLs 4xx no sitemap; 40 URLs noindex no sitemap; quatro erros de arquivo JS; quatro de CSS; uma ocorrência de página sem links de entrada.
- Detalhes por URL bloqueados na tela de problemas pela assinatura. A conexão Data API e seus créditos não liberam esse recurso web automaticamente. As ferramentas SE Ranking disponíveis no Easy MCP consultam palavras-chave/domínios/backlinks; não fornecem os detalhes dessa auditoria web.

Os totais podem repetir a mesma URL em várias regras. A captura não mostra quais são as URLs, qual status 4xx receberam, o IP do rastreador ou o conteúdo retornado. Não comprova bloqueio de firewall: a faixa sobre permitir IPs é uma orientação genérica, não um log de bloqueio.

## Testes diretos realizados

Home, artigo `conta-digital-pj-como-escolher/` e `sitemap_index.xml` foram consultados com e sem `www`, usando identificação de navegador e uma identificação representativa de robô `SEBot-WA`.

- Home e artigo sem www: HTTP 200 e canonical correspondente.
- Home e artigo com www: 301 para a mesma rota sem www, seguida de HTTP 200.
- Sitemap com e sem www: HTTP 200. A versão XML com www não redirecionou neste teste, mas isso não produziu 4xx.
- Artigo: index/follow e max-image-preview:large; sem noindex.
- Nove arquivos CSS/JS próprios referenciados na Home e no artigo: **18 testes, todos HTTP 200**, com as duas identificações.

Os pedidos saíram do ambiente deste agente, não dos IPs reais do SE Ranking. Trocar o User-Agent não simula origem de rede, limite de requisições ou todas as características do rastreador. Terceiros, como Google Analytics, não foram testados como recursos próprios. Responder HTTP 200 não comprova execução correta ou ausência de JavaScript não utilizado.

A auditoria técnica anterior deste mesmo dia também verificou 38 artigos publicados: todos responderam 200, com canonical correspondente, sem noindex e presentes no sitemap. Isso não prova que as 42 URLs da captura sejam essas mesmas páginas nem que todas já estejam indexadas no Google.

## Prioridades e soluções condicionadas à evidência

| Ordem | Problema | Ação correta |
|---|---|---|
| 1 | 42 respostas 4xx | Obter as URLs/códigos em Páginas rastreadas, exportação acessível ou nova auditoria. Conferir se ainda ocorre hoje. |
| 2 | Se forem 403 ou 429 | Consultar registros de segurança da hospedagem e ritmo da auditoria. Quando comprovado bloqueio, permitir seletivamente os IPs oficiais atuais do SE Ranking e reduzir a taxa de rastreamento. Não desativar a proteção global. |
| 3 | Se forem 404/410 | Identificar URL incorreta, removida, antiga ou recurso ausente. Corrigir links e sitemap. Criar 301 apenas para substituto editorial equivalente; não mandar todos os erros para a Home nem restaurar automaticamente conteúdo retirado. |
| 4 | 40 URLs 4xx/noindex no sitemap | Testar as URLs citadas. Uma página de erro pode emitir noindex e gerar os dois alertas como consequência. Manter no sitemap somente conteúdo canônico que deve ser indexado; preservar a exclusão de rascunhos e removidos. |
| 5 | Quatro JS e quatro CSS | Verificar os caminhos exatos e o conteúdo de resposta. Arquivo removido/versão antiga: corrigir o enqueue e limpar o cache pertinente depois; bloqueio: corrigir a regra comprovada. Não desinstalar plugins por esse número isolado. |
| 6 | ALT, títulos, H1 e links | Corrigir caso a caso nas URLs identificadas: ALT descritivo em imagem informativa, título claro, H1 editorial e links internos contextuais para páginas órfãs. Comprimento isolado não justifica mutilar título ou inflar conteúdo. |
| 7 | Validar resultado | Reexecutar a auditoria após obter a linha de base atual; comparar as mesmas URLs e verificar regressões na navegação, busca, artigos e recursos. |

**O header `X-Robots-Tag: noindex` do arquivo XML de sitemap é normal e não impede usar o sitemap.** Ele se refere ao próprio XML; é diferente de colocar noindex nas matérias listadas. Não remover esse header para tentar apagar o alerta de páginas noindex no sitemap.

“Não há links de entrada”, nesta auditoria de site, deve ser investigado como possível página sem links internos de entrada. Não constitui prova de que o domínio precise comprar backlinks. Já links externos com 4xx precisam ser conferidos no destino e substituídos ou removidos conforme o contexto; falhas de rede deste ambiente não comprovam status de todos esses destinos.

## Como continuar sem contratar para liberar a tela

1. Abrir **Páginas rastreadas**, cuja disponibilidade nesta conta ainda precisa ser confirmada. Se mostrar URLs/status, filtrar 4xx e registrar exemplos.
2. Se permitido, usar o relatório para convidado ou a exportação oferecida pela conta. Não foi alegado que esses recursos estejam liberados no teste.
3. Usar `https://giro73.com.br/` como URL canônica inicial da auditoria, se o campo for editável, permitindo seguir os redirects corretos de www. Não recriar o projeto nem alterar DNS/permalinks apenas porque a etiqueta exibe www.
4. Reexecutar o rastreamento pelo botão disponível, se a conta permitir, porque o relatório exibido é do dia anterior. Não se alterou a agenda semanal da ferramenta nem se acionou rastreamento pelo agente.
5. Se as páginas rastreadas também estiverem bloqueadas, usar a verificação independente documentada aqui para investigar URLs do próprio site. Não contornar a assinatura para ler dados privados da auditoria.

A renderização JS desabilitada na captura não explica, por si só, respostas 4xx. Títulos, textos e links dos artigos são entregues no HTML. Não se ativou uma renderização mais cara ou pesada como correção automática.

Saúde 77 no SE Ranking, nota Rank Math dos artigos e PageSpeed são medidas distintas. A prioridade é eliminar falhas reais e confirmar o rastreamento atual, não prometer 100/100 ou aprovação AdSense pela mudança de uma nota.

Evidências: [testes de host/User-Agent e recursos](implementacao-wp/validacao-auditoria-se-ranking.json). Os dados de 38 artigos são da [auditoria operacional anterior do mesmo dia](49-alinhamento-tecnico-operacional.md).
