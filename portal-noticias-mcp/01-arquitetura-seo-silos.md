# Arquitetura SEO e silos — Fase 1

## Taxonomia WordPress

Usar categorias nativas, uma categoria principal por artigo e URLs permanentes `/%postname%/`. Manter slugs estáveis; qualquer mudança de URL publicada exige redirecionamento 301. Não criar categorias vazias ou páginas indexáveis sem conteúdo útil.

| Categoria | Slug | Descrição SEO da página de categoria | Cor |
|---|---|---|---|
| Notícias & Região | `noticias-regiao` | Notícias de [cidade/UF], política local, serviços públicos e acontecimentos da região, com apuração, contexto e fontes identificadas. | `#C4170C` |
| Dinheiro & Renda Extra | `dinheiro-renda-extra` | Finanças pessoais, organização do orçamento e renda extra com exemplos práticos, custos, riscos e informações verificadas. | `#005A9C` |
| Tecnologia & Inovação | `tecnologia-inovacao` | Tecnologia no cotidiano: segurança digital, aplicativos, inovação e tutoriais testados para resolver problemas reais. | `#0077CC` |
| Esportes | `esportes` | Esporte regional e nacional, calendário, resultados confirmados e informações de serviço para acompanhar as competições. | `#06AA48` |
| Famosos & Entretenimento | `famosos-entretenimento` | Cultura, televisão e entretenimento com agenda, informações confirmadas e cobertura responsável de artistas e produções. | `#FF6700` |

Substituir `[cidade/UF]` pela área efetivamente coberta antes da publicação. Política integra Notícias & Região; criar subcategoria apenas quando houver cobertura recorrente. Remover a dependência da categoria padrão “Sem categoria” no fluxo de publicação.

**Achadinhos & Reviews:** fora da Fase 1: sem itens no menu, cards, sitemap ou automação. Ativar somente na Fase 2, após aprovação do AdSense e revisão editorial de publicidade e afiliação. Se já houver URLs públicas, avaliar individualmente antes de remover ou aplicar `noindex`.

## Indexação e descoberta

- Categoria: título e descrição únicos, introdução curta e paginação HTML com links rastreáveis; cada página paginada usa canonical próprio.
- Artigo: título descritivo, URL curta, canonical próprio, breadcrumbs e presença no sitemap XML. Usar apenas um plugin SEO para evitar metadados duplicados.
- Não indexar busca interna, anexos sem conteúdo próprio, arquivos de data e tags redundantes. Autores com biografia e produção própria podem ter arquivo indexável.
- Marcar artigos jornalísticos com `NewsArticle` e navegação com `BreadcrumbList`, sem inventar autor, datas ou entidade editorial. Validar o JSON-LD.
- Imagens relevantes com pelo menos 1.200 px de largura para elegibilidade a imagens grandes no Discover; permitir `max-image-preview:large`. Isso não garante distribuição.

## Links internos automáticos

1. Na publicação/atualização, selecionar até três artigos publicados da mesma categoria principal, excluindo o próprio artigo, duplicatas, conteúdo privado, programado e URLs não indexáveis.
2. Ordenar por pertinência temática; usar recência como desempate. Se não houver candidatos relevantes, mostrar menos links ou omitir o bloco.
3. Renderizar “Leia também” em HTML no servidor, após o conteúdo. Âncoras são os títulos reais; incluir também um link para a página da categoria.
4. Acrescentar links contextuais no corpo somente com correspondência editorial aprovada. Não inserir palavras-chave mecanicamente nem alterar citações. Em automação sem revisão, limitar-se ao bloco de relacionados.
5. Consultar via `WP_Query`, com limite pequeno e sem cálculo de paginação; armazenar IDs em cache. Invalidar o cache da categoria ao publicar, editar, excluir ou mudar categoria/status de um artigo.
6. Não usar JavaScript de recomendação externo, autolinks em massa ou abertura forçada de nova aba. Evitar leituras extras no banco por card.

**Aceite:** publicar um artigo de teste deve atualizar automaticamente categoria, capa e relacionados; nenhum link quebrado ou cruzamento irrelevante. Medir cliques em relacionados e sessões engajadas, sem prometer aumento de tempo de leitura.
