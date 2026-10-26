# GA4 e primeiro piloto de pesquisa editorial

**Estado posterior:** o post 481 foi publicado com os recursos ativos, conforme nova orientação do responsável. Veja a [conclusão da publicação](42-publicacao-conta-digital-pj.md). O texto abaixo preserva o registro da preparação e dos bloqueios encontrados naquela etapa.

Execução em 26/10/2026. Esta etapa não publicou uma nova matéria: o artigo 481 foi preparado em rascunho porque a consulta dos primeiros resultados do Google e a leitura das fontes continuam pendentes.

## GA4 conferido

- Propriedade correta: **560739453**, nome `giro73.com.br`, fuso `America/Bahia`.
- Fluxo web: `https://giro73.com.br`; ID de medição `G-VZZWMKPKM9`.
- A propriedade foi criada em 25/10/2026. O relatório dos 28 dias até 25/10, filtrado pelo hostname `giro73.com.br`, retornou **3 sessões, 1 usuário ativo e 5 visualizações**. A amostra não permite avaliar tendência ou qualidade do tráfego.
- O acesso de leitura está liberado. O padrão do Easy MCP ainda apontava para outra propriedade; alterar **Easy MCP AI → Tools → External data → Google Analytics 4 → Default property ID** para `560739453` e salvar. Enquanto isso, as consultas devem passar esse ID explicitamente.

Nenhum relatório de tráfego de outro domínio foi usado como métrica do Giro73.

## DataForSEO: bloqueio identificado

A conexão permitiu consultar o saldo de US$ 1, mas a tentativa de pesquisa retornou **HTTP 403 / código 40104**, com a mensagem de que é necessário verificar a conta antes de utilizar a API. Concluir a verificação indicada em [app.dataforseo.com](https://app.dataforseo.com/).

O saldo observado continuou em US$ 1 após a tentativa. Não foram retornados volume, CPC, concorrência ou SERP pelo DataForSEO. Os números abaixo não são desse fornecedor. Não houve tentativa de contornar a exigência de verificação.

## Pesquisa complementar no SE Ranking

Foram consultados 44 termos sobre serviços financeiros, seguros e pequenos negócios no Brasil, usando o acesso já conectado. O saldo passou de 100.000 para 99.500 créditos: **500 créditos consumidos**.

| Candidato | Volume mensal estimado | CPC retornado | Dificuldade orgânica | Concorrência de anunciantes |
|---|---:|---:|---:|---:|
| conta digital pj | 440 | 4,39 | 7/100 | 0,76 |
| maquininha para mei | 170 | 2,06 | 8/100 | 1,00 |
| conta pj mei | 610 | 1,99 | 8/100 | 0,75 |

“Conta digital PJ” foi selecionada como candidata inicial por reunir o maior CPC entre os termos encontrados do lote com dificuldade orgânica baixa. Isso não significa que seja a melhor palavra de todo o mercado nem que trará esse volume ao Giro73.

O retorno desses pedidos não incluiu um campo explícito de moeda para o CPC; os valores foram preservados como vieram da API, sem conversão para reais. CPC de anúncios não é remuneração garantida ao editor AdSense. A concorrência entre anunciantes é alta nesses candidatos, enquanto a dificuldade orgânica estimada é baixa: não são a mesma medida. Os termos sem dados não foram tratados como busca zero.

Resultados completos: [41-pesquisa-palavras-chave.csv](41-pesquisa-palavras-chave.csv).

## Concorrentes e fontes: o que ainda não foi demonstrado

Foram consultadas no SE Ranking três URLs de serviços bancários. As palavras retornadas eram majoritariamente de marca e não demonstraram quais páginas estão nas primeiras posições da consulta genérica “conta digital PJ”. Não se afirmou que essas três URLs são o top 3 dessa palavra.

A tentativa de acesso direto ao Google e à fonte oficial SUSEP também foi recusada pelo proxy HTTP 403. Não houve leitura dos textos integrais desses sites. A análise dos primeiros colocados e a verificação externa das referências do artigo continuam pendentes; a publicação não foi antecipada como se essas verificações tivessem ocorrido.

## Artigo pronto para a etapa seguinte

- Post: **481**, status **draft**.
- [Editar no WordPress](https://giro73.com.br/wp-admin/post.php?post=481&action=edit).
- Título: **Conta digital PJ: 7 cuidados antes de abrir para seu negócio**.
- Editoria: **Dinheiro & Renda Extra**; responsabilidade institucional da Redação, sem persona ou credencial fictícia.
- Cerca de 1.000 palavras em 39 blocos Gutenberg, com exemplo numérico explicitamente hipotético, critérios de custo, atendimento, segurança e acesso à conta. Não foram inventadas tarifas de bancos, testes de produtos ou condições comerciais.
- SEO: título de 60 caracteres, descrição de 154, palavra-chave de foco, categoria principal, títulos/descrições sociais e robots conferidos no banco. Não foi alterada artificialmente a pontuação do Rank Math.
- Imagem: mídia 96, fotografia WebP 1200×675 já cadastrada, com fotógrafo e referência de licença CC0 registrados. Capa e figura gerenciada no editor; o template existente evita a repetição na página pública.
- Links internos para os guias de cálculo de renda extra, Registrato e proteção de contas. Referências externas são apresentadas como links de consulta, sem alegar que foram lidas nesta execução.

Fonte Gutenberg: [conta-digital-pj.gutenberg.html](conteudos/conta-digital-pj.gutenberg.html).

A criação completa pela integração retornou erro e não deixou post encontrado nas buscas de recuperação. A criação mínima pela REST nativa funcionou, e a configuração completa/SEO foi salva depois pelo Easy MCP. O resultado foi conferido como um único rascunho 481; não foram criados artigos de teste nem cópias públicas.

## Retomada autorizada

Após a verificação da conta DataForSEO, consultar novamente as métricas e a SERP brasileira da candidata, examinar as páginas e fontes que efetivamente forem retornadas e ajustar o artigo 481 às evidências. Então concluir o SEO, publicar esse mesmo post e validar HTML, canonical, imagem, schema e sitemap. A publicação já foi solicitada pelo responsável; não é necessário pedir novamente autorização para esse artigo. Ainda não há aprovação AdSense nem garantia de visualizações ou receita.

Dados de validação: [validacao-piloto-pesquisa-seo.json](implementacao-wp/validacao-piloto-pesquisa-seo.json).
