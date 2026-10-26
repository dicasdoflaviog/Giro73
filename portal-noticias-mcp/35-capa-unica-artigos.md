# Capa única nas matérias importadas

Aplicado no WordPress em 26/10/2026, no snippet **7 — Template editorial de artigos**, ativo, prioridade 11, sem erro de código registrado. Fonte: `implementacao-wp/single-editorial.php`.

## Problema e correção

O template já exibia a imagem destacada. O motor também salva uma figura `g73-imported-image` após o primeiro parágrafo para incluir imagem/ALT no conteúdo analisado pelo Rank Math. Isso fazia a mesma mídia aparecer duas vezes ao leitor.

O template agora reconhece a figura automática que corresponde à capa por ID de attachment ou pelas URLs de seus tamanhos registrados. Quando o bloco da capa já foi renderizado, omite somente essa repetição do corpo e preserva seu crédito junto à capa. Outras fotos, figuras manuais e figuras com várias imagens não são removidas. Sem capa renderizada, mantém a imagem do corpo.

A rotina atua no HTML renderizado da matéria principal. Não altera `post_content`, o attachment, o coletor RSS, o payload de publicação ou a idempotência. Editor, REST e feeds preservam o conteúdo original. Não adiciona CSS para esconder duplicatas nem JavaScript.

## Validação

- Snippet anterior salvo em backup privado e conferido contra a versão do projeto antes da alteração.
- 20 verificações PHP offline com as classes HTML API reais do WordPress 6.8.3; sintaxe PHP válida.
- Artigo de referência: [Inscrições para vagas remanescentes do Fies estão abertas](https://giro73.com.br/inscricoes-para-vagas-remanescentes-do-fies-estao-abertas/), ID 411, mídia 410.
- Após limpar o cache LiteSpeed, HTTP 200 com uma capa e nenhuma figura automática repetida no corpo; anúncios dos dois pontos internos preservados.
- Conteúdo bruto, campos meta expostos pela REST, mídia destacada, ALT e legenda da mídia iguais nas leituras anteriores/posteriores. Os campos `modified` e `modified_gmt` mudaram durante a janela de comparação, sem alteração no texto bruto; esta implantação escreveu somente o snippet.
- Open Graph `og:title`, `og:description`, `og:image` e `og:url` preservados.
- Chromium em 390 px e 1440 px, usando HTML público e recursos próprios obtidos por HTTPS verificado: uma capa, crédito e ALT presentes, sem transbordamento horizontal. O navegador usou interceptação local de recursos para contornar apenas a diferença de confiança de certificados do ambiente de testes; não é uma nova medição PageSpeed.

Resultado sem credenciais: `implementacao-wp/validacao-capa-unica.json`. Nenhuma matéria foi criada ou republicada para o teste. A regra já atende novas matérias e as existentes que tenham a figura automática repetida, sem edição individual.
