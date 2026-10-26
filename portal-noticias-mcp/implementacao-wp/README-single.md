# Template oficial dos artigos Giro73

Ativo em 22/10/2026. Fonte visual: `single-editorial.html` e `css-g1-techtudo-ultra-leve.css`. Lógica de servidor: `single-editorial.php`, snippet global **7**, prioridade 11, no Code Snippets existente.

## Leitura

Coluna central com máximo de 740 px, margem de 32 px auto, padding lateral de 16 px e fundo branco. Nenhuma sidebar ou anúncio lateral no single. A Home mantém a sidebar e o espaço comercial já existentes.

Chapéu colorido de 13 px, H1 de 36 px/800/1,15, resumo de 19 px/1,45 e metadados entre duas linhas finas. Imagem 16:9 com raio de 6 px e crédito factual abaixo, de 12 px com margens de 6/28 px. Corpo `.entry-content` de 19 px/1,7, parágrafos com margem inferior de 24 px e H2 de 24 px/800 com borda de 4 px na cor da editoria. Links internos no corpo usam essa cor e sublinhado de 1 px.

A variável `--cor-editoria` acompanha as cinco cores já mapeadas no chapéu. Fontes do sistema, sem biblioteca ou fonte remota adicionada.

## Capa única nos artigos importados

Correção de 26/10/2026 no mesmo snippet 7: quando o template já exibiu a imagem destacada, o conteúdo renderizado omite somente a figura automática `g73-imported-image` que repete essa mídia. Identifica o attachment por `wp-image-ID` ou pelas URLs/tamanhos registrados no WordPress, usando a HTML API nativa. Não adiciona JavaScript nem CSS para esconder uma imagem que continuaria carregando.

A legenda da figura importada é preservada junto à capa. Outras fotos, figuras manuais e figuras com várias imagens permanecem intactas. Sem bloco de capa renderizado, a imagem do corpo é mantida. Editor, REST e feeds não recebem a remoção; o `post_content` salvo continua com figura/ALT para a análise do Rank Math e a retomada idempotente do motor de publicação. Não é necessário atualizar o coletor da VPS nem recriar matérias.

`tests/single-cover-unit.php` cobre 20 casos com classes HTML API reais do WordPress 6.8.3 e stubs das demais APIs. Para executar, disponibilize nesse diretório externo as classes `class-wp-token-map.php`, `html5-named-character-references.php`, `class-wp-html-span.php`, `class-wp-html-attribute-token.php`, `class-wp-html-text-replacement.php`, `class-wp-html-decoder.php`, `class-wp-html-doctype-info.php` e `class-wp-html-tag-processor.php`:

```bash
G73_WP_HTML_API=/caminho/das/classes php tests/single-cover-unit.php
```

## Inserções automáticas

O filtro `render_block_core/post-content` atua só no post principal de uma página singular. Calcula os finais dos parágrafos editoriais uma vez e insere os componentes a partir do final, preservando os offsets e o HTML original:

- Após o terceiro parágrafo: `.ad-slot-article`, usando `g73_ads_render('article')` do snippet **6**. Gestão permanece em **Configurações → Publicidade Giro73**. Sem banner, usa o e-mail comercial confirmado pelo usuário.
- Após o sexto parágrafo: caixa **Leia também**, sem imagem ou carregamento externo. Prioriza um post publicado da mesma categoria; quando não há outro, usa um artigo publicado já linkado no próprio conteúdo. Exclui o post atual, páginas e artigos com senha. Se não houver candidato, omite a caixa em vez de inventar relação.

Crédito da foto, metadados, citações, figuras, listas, tabelas e áreas de apoio não entram na contagem. Com menos de três parágrafos editoriais, o anúncio vai ao final do último; com menos de seis, a indicação vai ao meio dos parágrafos disponíveis. Sem parágrafos editoriais, não insere. Atributos identificadores evitam duplicação de componentes já presentes. A inserção modifica apenas a renderização, nunca o texto armazenado.

O parser e a inserção comercial saíram do snippet 6 e passaram à rotina única do snippet 7; não manter simultaneamente o filtro antigo. O snippet 6 continua com configuração, seleção de mídia, shortcodes comerciais e renderização dos banners. O snippet **5**, das cotações, foi preservado.

## WhatsApp sem JavaScript

O bloco Shortcode `[giro73_compartilhar_whatsapp]` gera no servidor o link `https://api.whatsapp.com/send?text=...`, com `Título da matéria - Leia em Giro73: URL canônica` codificado com rawurlencode. O título Open Graph usa o título editorial completo por filtro do Rank Math; o título SEO de busca permanece independente. O link só abre o compartilhamento por iniciativa do leitor. Nenhuma mensagem é enviada automaticamente. O antigo script `artigo-compartilhar.js` foi removido do template e do repositório.

Layout, anúncio, indicação e compartilhamento funcionam com JavaScript desativado. Os controles leves já existentes do header permanecem independentes deste template.

## Operação

Ao mudar PHP/template/CSS, limpar LiteSpeed e conferir URLs canônicas depois de `verify.after_seconds`. Ao publicar novas matérias, invalidar as páginas relacionadas que precisem refletir imediatamente a nova seleção; o restante segue o cache de página e as consultas nativas do WordPress.

Para restaurar o layout anterior, usar o commit `0856ffc` como referência para template, CSS e snippet 6, desativando o snippet 7. Não remover configurações comerciais ou fotografias. Manter uma única versão do filtro de inserção.
