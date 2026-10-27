# SE Ranking: 403 do rastreador, sitemap e cache

Execução em 27/10/2026. A nova captura mostra explicitamente **HTTP 403** em categorias, páginas institucionais e matérias existentes. Não foi fornecida uma lista completa de 404 removidos nem os oito caminhos de recursos com erro. O relatório exibido continua sendo de 26/10/2026 às 17:04:04, com fuso não confirmado.

## Resultado verificado e ação já executada

- Quatorze URLs visíveis/identificáveis na captura foram consultadas com identificação de navegador e identificação representativa `SEBot-WA`: **28 respostas HTTP 200**, com indexação permitida, incluindo seis categorias, cinco páginas institucionais, duas matérias e o arquivo de autor.
- Os pedidos não partiram dos IPs reais do SE Ranking. O teste de User-Agent não comprova liberação do IP nem reproduz ritmo, fingerprints ou a resposta de 26/10.
- Plugins ativos conferidos: **Rank Math 1.0.280 e LiteSpeed Cache 7.9.2**. WP Rocket não aparece entre os ativos. O módulo Redirections do Rank Math está desligado; sitemap está ligado.
- Pelo Easy MCP, foi executada a purga direcionada de **Rank Math sitemap** e **cache de páginas LiteSpeed**. Ambas retornaram `cleared`. Não houve purga de CDN ou object cache, remoção manual de arquivos, alteração de robots, DNS, firewall, permalinks ou redirecionamentos.
- O sitemap foi consultado novamente após a purga: índice e três mapas HTTP 200. Posts: 39 artigos publicados e Home, 40 URLs ao todo. Páginas: seis publicadas e Home. Categorias: seis termos com posts. A Home aparecer em mais de um mapa é redundância e não explica os 403.
- Os três rascunhos e 30 posts na lixeira não apareceram no mapa de posts: todos os links desse XML correspondem aos 39 publicados ou à Home pública. Não há evidência atual, nessa comparação, de sitemap listando os 40 removidos afirmados na solicitação.

Evidências: [validação da limpeza e das URLs](implementacao-wp/validacao-correcao-403-sitemap-cache.json). Os recursos próprios também foram reconsultados após a purga; a validação registra o resultado, sem afirmar reprodução dos oito caminhos originais ainda não fornecidos.

## 1. Resolver o 403 do rastreador antes de remover URLs

Uma URL ativa com 403 para um rastreador deve recuperar o acesso HTTP 200. Não deve ser redirecionada para a Home nem receber 410. Os alertas de URL 4xx/noindex no sitemap podem se referir às mesmas respostas de bloqueio ou à classificação de indexabilidade das respostas de erro. A captura não comprova noindex nos documentos legítimos atuais.

Procedimento:

1. No SE Ranking, usar `https://giro73.com.br/` como origem canônica quando o campo for editável; seguir os redirecionamentos válidos de www. Não recriar o projeto ou alterar DNS por causa do rótulo www.
2. Reiniciar a auditoria para obter dados atuais; o agente não tem ferramenta de execução dessa auditoria web na integração Data API.
3. Se persistirem 403, registrar URL, horário e IP de origem do rastreador e cruzar com registros de segurança da hospedagem que serve o WordPress.
4. Usar a lista **oficial e atual** de IPs do SE Ranking, acessível pelo aviso do próprio painel, para eventual exceção restrita no WAF/antibot. Não usar IPs inventados nem liberar qualquer requisição só por declarar um User-Agent de bot.
5. Começar com uma conexão e taxa baixa de rastreamento, se a ferramenta permitir, e observar se 403/429 desaparecem. Não desativar a segurança global nem liberar `/wp-admin` ou o endpoint autenticado de publicação.

Mensagem de diagnóstico para o suporte da hospedagem, se necessário:

> O SE Ranking recebeu HTTP 403 em páginas públicas do Giro73 no relatório de 26/10/2026 às 17:04:04, fuso do relatório a confirmar. Exemplos: /category/noticias-regiao/ e /politica-editorial/. Essas URLs respondem 200 em testes externos atuais. Precisamos cruzar a próxima tentativa com logs de WAF/antibot, identificar a regra e permitir seletivamente os IPs oficiais atuais do rastreador, preservando as demais proteções.

Não se concluiu qual WAF causou o bloqueio, nem se ele ainda ocorre, sem a nova coleta e esses logs.

## 2. Sitemap: painel e WP-CLI

O Rank Math já exclui rascunhos, privados, futuros e lixeira do sitemap público no comportamento normal. Não há um botão para publicar esses estados no XML. Post types, termos noindex e exclusões explícitas também influenciam a geração.

No painel:

1. **Rank Math SEO → Configurações do Sitemap**: manter posts, páginas e categorias relevantes incluídos; anexos e CPTs administrativos/comerciais fora do sitemap editorial, conforme a finalidade.
2. Em categorias, não incluir termos vazios quando a opção estiver disponível; verificar quais termos devem ser indexados. Não existe post_status `ativo` para termos: presença de conteúdo e intenção editorial são os critérios.
3. Para uma exclusão específica, usar o campo de IDs de posts/termos excluídos. Não excluir todo conteúdo indexável para esconder erros do rastreador.
4. Para forçar regeneração pelo painel, alterar temporariamente **Links por Sitemap de 200 para 201**, salvar e restaurar 200, salvando outra vez. É uma alternativa à purga já executada pelo Easy MCP.
5. Se uma página é órfã, mas útil, adicionar links internos relevantes. Não a retirar automaticamente do sitemap. Se não deve mais ser pública, revisar seu status e finalidade antes de excluí-la.

**O próprio XML pode responder `X-Robots-Tag: noindex`; isso é normal.** Esse header evita indexar o arquivo técnico como resultado de busca e não impede a descoberta das matérias listadas. Não removê-lo para corrigir o alerta de páginas noindex dentro do sitemap.

Se houver WP-CLI, executar **na pasta do WordPress, no servidor que hospeda o portal**, com o usuário de manutenção do site. Não executar esses comandos em `/opt/giro73-rss` supondo que o robô seja o WordPress:

```bash
wp core is-installed
wp option get home
wp plugin list --status=active --fields=name,version
```

Para repetir somente a invalidação do Rank Math em versões com a API de cache disponível:

```bash
wp eval '
$cache = "RankMath\\Sitemap\\Cache";
if (!is_callable([$cache, "invalidate_storage"])) {
    WP_CLI::error("API de cache indisponível nesta versão; use o painel do Rank Math.");
}
$method = new ReflectionMethod($cache, "invalidate_storage");
if ($method->getNumberOfRequiredParameters() > 0) {
    WP_CLI::error("Assinatura diferente; use o painel do Rank Math.");
}
call_user_func([$cache, "invalidate_storage"]);
WP_CLI::success("Cache do sitemap invalidado.");
'

wp litespeed-purge all
```

O comando do Rank Math usa uma classe interna e inclui verificação de disponibilidade/assinatura; não foi executado por SSH nesta etapa. A limpeza real foi feita pela integração nativa do Easy MCP. Não é necessário repetir os comandos agora para concluir a mesma purga, nem apagar todos os transients do site.

## 3. Redirecionamentos e 410: somente para removidos reais

Não transformar os 403 da captura em redirecionamentos. Para cada URL comprovadamente retirada:

- Substituto editorial equivalente: 301 para essa matéria, não para um destino genérico.
- Remoção definitiva sem substituto: 404 ou 410 são respostas válidas; retirar do sitemap e corrigir links internos. O 410 é opcional, não uma obrigação para todo 404.
- URL útil sem links de entrada: corrigir linkagem; não tratar como conteúdo removido.

No Rank Math, ativar **Dashboard/Painel → Redirections/Redirecionamentos** somente quando for gerenciar regras, depois adicionar a origem exata e destino equivalente com 301, ou escolher 410 para remoção definitiva. O módulo está desligado atualmente e não foi ativado por esta auditoria. Recursos de importação em massa podem depender da edição; não se presumiu que CSV esteja liberado na licença atual.

Se o servidor que realmente serve o WordPress for Nginx, estas são estruturas de exemplo dentro do `server { ... }` existente. Os caminhos abaixo são placeholders e devem ser substituídos por URLs revisadas, nunca pelas categorias/páginas com 403 da captura:

```nginx
location = /url-antiga-com-substituta/ {
    return 301 https://giro73.com.br/url-equivalente/;
}

location = /url-removida-definitivamente/ {
    return 410;
}
```

Depois de editar e revisar o arquivo correto do Nginx:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Não executar isso na VPS do coletor se ela não hospedar o portal. Não se identificou acesso ao Nginx do WordPress nem se gravou configuração de servidor. Se a hospedagem usa LiteSpeed/Apache, regras Nginx não são o mecanismo aplicável. O 301 específico já existente no código do portal para uma cobertura duplicada foi preservado.

## 4. CSS e JavaScript

O cache ativo identificado foi LiteSpeed; WP Rocket não está ativo. Os recursos próprios observados antes da purga vieram do tema, WordPress e Site Kit, sem caminhos gerados `/wp-content/litespeed/css/` ou `/wp-content/litespeed/js/`. Isso não demonstra a causa dos quatro JS/quatro CSS da captura, cujas URLs ainda não foram mostradas.

1. Em **Recursos encontrados**, filtrar os oito arquivos com 4xx/5xx e registrar URL/código. Repetir o teste após uma auditoria atualizada.
2. Se o erro estiver em arquivo gerado pelo LiteSpeed: **LiteSpeed Cache → Toolbox/Caixa de ferramentas → Purge/Limpar → CSS/JS** e depois Purge All. O nome traduzido pode variar. Isso é diferente da purga de páginas já executada aqui.
3. Se voltar a ocorrer, testar temporariamente apenas a minificação/combinação relacionada ao recurso afetado, limpar CSS/JS e reativar uma opção por vez. Não desligar todas as otimizações de páginas que estão funcionando.
4. Se o caminho for do tema/plugin original: conferir existência e versão do arquivo e o enqueue que o referencia. Purgar cache não recria um arquivo original ausente.
5. Se a resposta for 403/429, investigar a mesma proteção do rastreador. Se for 500/502/503, consultar o erro do servidor naquele momento. Não resolver todos os casos desinstalando plugins.
6. Purgar CDN/hospedagem somente se existir essa camada e ainda houver referência antiga; ela não foi purgada nesta execução.

Teste externo de GET, útil para verificar uma URL específica sem depender do relatório antigo:

```bash
curl --silent --show-error --location --max-time 20 \
  --output /dev/null --write-out '%{http_code} %{url_effective}\n' \
  'https://giro73.com.br/category/noticias-regiao/'
```

Executá-lo em outra máquina não reproduz automaticamente o IP e as condições de rede do SE Ranking.

## 5. Critério de conclusão

Reexecutar a auditoria e confirmar que o rastreador recebe 200 nas páginas públicas, 301 apenas nos substitutos revisados, e que XML, CSS e JS esperados respondem corretamente. Reavaliar avisos de ALT, links internos e títulos depois da camada de acesso. Saúde 90+ é uma meta a medir na nova coleta, não resultado garantido pela purga. Não remover conteúdo válido, noindex técnico do XML ou segurança global para perseguir a nota.
