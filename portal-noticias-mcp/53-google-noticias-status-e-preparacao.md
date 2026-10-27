# Google Notícias — situação observável do Giro73

Conferência de leitura em 27/10/2026. O responsável confirmou que o cadastro foi feito no **Google Publisher Center**, mas ainda não forneceu o status exibido. A integração disponível consulta WordPress e Search Console; não fornece acesso ao painel privado do Publisher Center. Não se declarou o cadastro aprovado, recusado ou em revisão.

## Tráfego consultado

Search Console, propriedade `https://giro73.com.br/`, período **29/09 a 26/10/2026**:

| Superfície | Tipo consultado na API | Impressões retornadas | Cliques retornados |
|---|---|---:|---:|
| Google Notícias, site/app | googleNews | 0 | 0 |
| Aba Notícias da Pesquisa Google | news | 0 | 0 |
| Discover | discover | 0 | 0 |

Não há exposição registrada nessas consultas para o período, mas isso não comprova recusa, inelegibilidade ou ausência absoluta de exibição. Dados recentes têm atraso e o período não inclui 27/10; o cadastro pode ser recente. Não é possível inferir um status privado do Publisher Center a partir do tráfego.

O sitemap regular foi baixado pelo Google em 27/10, segundo a API de sitemaps, com zero erros e avisos reportados. Isso não comprova indexação de todos os artigos ou inclusão no Google Notícias.

## Base técnica

- `/feed/`: HTTP 200, RSS reconhecido com dez itens.
- Sitemap comum ativo; o índice permanece submetido no Search Console.
- Módulo `news-sitemap` do Rank Math: **desativado**. A rota `/news-sitemap.xml` respondeu 404, compatível com a ausência do recurso; não foi tratada como sitemap publicado/quebrado.
- Artigo recente 497: HTTP 200, canonical correspondente, index/follow, `max-image-preview:large` e JSON-LD NewsArticle com autor, editor, imagem e datas.
- Na amostra, a imagem é de 800×533. Uma imagem original adequada de pelo menos 1200 px pode ajudar a apresentação em superfícies como Discover; isso não foi apresentado como requisito universal obrigatório do Google Notícias nem resolvido com ampliação artificial.

NewsArticle e um news sitemap ajudam a descrever/descobrir notícias, mas não constituem aprovação nem garantem distribuição. O news sitemap é opcional. Se for habilitado futuramente, deve identificar corretamente publicação/idioma e seguir as regras de notícias recentes; o sitemap comum continua servindo ao acervo.

## Pendência editorial identificada

Foram lidos os corpos atuais dos posts 497 e 489, inclusive seus links de fonte:

- **497 — luto por Dona Didi**: publicado no Giro73 em 27/10; o texto menciona falecimento em 22/08/2026 e usa uma fonte cujo URL termina em `23-08-2026`.
- **489 — Supercross em Itabela**: publicado em 27/10; usa uma fonte cujo URL termina em `01-09-2026` e anuncia os dias 12 e 13 de setembro no futuro, sem contextualizar que a referência é anterior.

Os artigos oficiais de origem não foram baixados nesta etapa. As datas no texto e nas referências internas do Giro73 são evidência suficiente de que o enquadramento temporal precisa de revisão; não se afirmou checagem independente de todos os fatos narrados. Arquivos antigos podem ser preservados e contextualizados, mas não devem ser apresentados como agenda ou novidade atual sem informação nova confirmada.

Antes de ampliar a distribuição, priorizar data original/contexto, informação nova verificável, atribuição, autoria responsável e direitos das imagens. Reescrever um release, inserir palavra-chave ou obter nota verde no plugin não substitui essa revisão. Nenhum artigo foi editado ou retirado nesta conferência.

## Como interpretar o cadastro

Google Notícias seleciona conteúdos elegíveis automaticamente. A descrição retornada para o [comunicado oficial do Google](https://support.google.com/news/publisher-center/answer/15898024?hl=en) confirma a transição completa para páginas de publicação geradas automaticamente no fim de março de 2025. Cadastrar uma organização/publicação no Publisher Center não garante que todas as matérias serão selecionadas ou que receberão tráfego. Publisher Center, Search Console e AdSense têm funções distintas; aprovação/monetização AdSense é outro processo. Não interpretar automaticamente um status de outro produto do Publisher Center como aprovação de distribuição no Google Notícias.

Para confirmar o cadastro específico mencionado, falta o texto do status exibido ao abrir Giro73 no Publisher Center. O serviço já foi identificado pelo responsável, sem pedir credenciais. Não foram solicitadas mudanças de permissão ou novo login.

Próximas ações recomendadas: revisar os exemplos históricos, manter produção regional original e datada, acompanhar os três relatórios do Search Console e, como melhoria técnica opcional, avaliar a ativação do news sitemap após definir os conteúdos que devem integrá-lo. Não foi prometido prazo de inclusão, nota mínima ou quantidade de artigos para seleção.

## Limites e evidências

As tentativas de ler diretamente três páginas oficiais de documentação Google foram impedidas pelo proxy deste ambiente (`Tunnel connection failed: 403 Forbidden`). Em seguida, uma consulta Google Live pelo DataForSEO retornou título, URL e descrição do comunicado oficial `15898024`, entre outros resultados. Foi examinada a descrição desse resultado oficial, não o artigo integral nem o resumo gerado por IA da SERP. O custo retornado foi US$ 0,002, coincidente com a diferença do saldo de US$ 0,898 para US$ 0,896. Não houve recarga ou outra consulta paga nesta conferência.

Nenhum módulo, sitemap, cadastro, status de post, cron ou cache foi alterado nesta etapa. Evidências: [validação Google Notícias](implementacao-wp/validacao-google-noticias.json).
