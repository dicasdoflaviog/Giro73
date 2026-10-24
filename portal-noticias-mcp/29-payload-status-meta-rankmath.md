# Endpoint publish — payload com status e meta Rank Math

Versão completa para substituir `implementacao-wp/motor-publicacao.php` no snippet 12 existente. Esta entrega altera o código no repositório; não instala a versão nova no WordPress nem publica matérias de teste.

## Novo contrato

- `status` enviado pelo bot controla diretamente `post_status` em `wp_insert_post`: `draft` ou `publish`.
- Se `status` estiver ausente ou nulo, o fallback é **publish**. O bloqueio anterior que dependia do modo administrativo foi removido. O painel passa a informar esse contrato; a chave geral de pausar a recepção continua disponível.
- `meta` é opcional. Quando informado, deve ser um objeto; somente `rank_math_focus_keyword`, `rank_math_title` e `rank_math_description` são aceitos. Valores devem ser strings de até 4000 bytes e são sanitizados. Campos ausentes não geram acessos a índices inexistentes.
- O SEO recebido tem prioridade sobre título/descrição automáticos e sobre o antigo campo `focus_keyword`. Campos desconhecidos em `meta` não são gravados.
- O ID de criação deve ser válido, maior que zero e não ser `WP_Error` antes das chamadas explícitas de `update_post_meta`.
- Os valores SEO e a capa já preparada também são enviados em `meta_input`, para estarem disponíveis quando o WordPress disparar os hooks de publicação. Não há uma segunda transição artificial de rascunho para publicação.
- A exigência existente de imagem para publicação direta, autenticação HTTPS por token, filtros de conteúdo, limites, logs e proteção contra duplicação continuam em vigor.

Exemplo do novo JSON em `implementacao-wp/publish-example.json`. Ele usa `draft` explicitamente para permitir revisão. Remover essa propriedade passa a solicitar publicação imediata.

O teste de integração `tests/publisher-live.py` foi atualizado para o novo contrato e verifica os três metadados recebidos. Continua criando apenas rascunhos; os testes de `publish` e fallback omitem a imagem para validar o status sem colocar fixtures no ar. A execução desse teste requer instalar esta versão no WordPress e fornecer os segredos localmente.

Verificação desta entrega: sintaxe do PHP conferida com parser PHP; sintaxe do teste Python e JSON conferidas. Não foi realizada validação de execução PHP/WordPress desta versão, nem alteração de cache em produção.

Ao substituir o snippet, use o arquivo completo e remova a abertura `<?php` se o editor Code Snippets já a fornecer. Substitua o conteúdo do snippet existente, mantendo prioridade 16; não ative uma segunda cópia com as mesmas funções.
