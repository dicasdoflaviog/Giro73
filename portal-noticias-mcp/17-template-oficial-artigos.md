# Template oficial de artigos: coluna central e retenção

Aplicado em **22/10/2026**. Esta especificação substitui a sidebar interna criada na implantação 16. A Home mantém sua estrutura e publicidade lateral.

## Publicado

- Leitura central de 740 px, margem 32 px auto, padding 0 16 px e fundo branco; sidebar removida da marcação do single.
- Chapéu colorido de 13 px, H1 de 36 px/800/1,15 e resumo de 19 px/1,45 com margem inferior de 24 px.
- Barra de assinatura com borda superior/inferior de 1 px, padding de 12 px, autor/local/data e botão WhatsApp nativo, renderizado no servidor.
- Imagem 16:9 com raio de 6 px, crédito factual preservado e espaçamento de 6/28 px.
- Corpo `.entry-content` de 19 px/1,7; parágrafos de 24 px de espaçamento; H2 de 24 px/800, margem 36/16 px e borda de 4 px em `--cor-editoria`. Links internos coloridos e sublinhados discretamente.
- Anúncio comercial depois do terceiro parágrafo; Leia também depois do sexto, sem alterações no conteúdo armazenado.
- Relacionados por mesma categoria, com alternativa pelos links editoriais já existentes quando há só um post na editoria. Nenhuma matéria aponta para si mesma. Os cinco artigos atuais receberam uma indicação publicada válida.
- Compartilhamento e inserções funcionam sem JavaScript. Nenhum plugin, fonte remota, script pesado ou consulta externa acrescentado ao single.

## Organização

`single-editorial.php` publicado no snippet global **7**. O snippet comercial **6** mantém campos/shortcode/renderer e passa a delegar a inserção ao snippet 7. O snippet financeiro **5** foi preservado. Guia técnico e rollback em `implementacao-wp/README-single.md`.

## Validação

- Home e cinco artigos HTTP 200; cinco artigos testados em **360, 390, 800, 1024 e 1440 px**.
- Sem sidebar ou anúncio lateral no single e sem overflow horizontal. Leitura central e todas as medidas principais conferidas por estilos computados.
- Nos cinco posts: um anúncio depois de exatamente três parágrafos e uma caixa depois de exatamente seis, excluindo o crédito da foto. Link relacionado publicado, interno e diferente do artigo atual.
- Título, tipografia, margens, bordas, proporção da foto, crédito e cores das cinco editorias conferidos. Inspeção visual de desktop e mobile concluída.
- Link WhatsApp contém título e URL correta no HTML inicial, com JavaScript desativado; nenhum compartilhamento enviado. Blocos de anúncio e indicação também presentes sem JS.
- Nenhum erro JavaScript. **CLS observado: 0**, no cenário de laboratório testado; não equivale a nota PageSpeed ou dados de campo.
- Sintaxe PHP 7.4+ e CSS validados. Snippets ativos sem code_error; arquivos publicados conferidos. Conteúdo e metadados dos cinco artigos comparados antes/depois e preservados.
- LiteSpeed Purge All confirmado e URLs canônicas verificadas após a limpeza.
