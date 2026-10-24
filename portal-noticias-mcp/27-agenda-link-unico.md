# Agenda 73 — link único por evento

Atualização vigente de textos e cores dos botões: `28-agenda-ctas-por-categoria.md`.

Removido o botão **Falar com Produtora**, suas mensagens de contato ausente e o campo de telefone do formulário administrativo.

Cada card oferece **Ver evento →**. Foto, título e botão abrem o mesmo destino em nova aba, com `nofollow noopener noreferrer`:

1. `g73_event_url`: **Link oficial do evento**.
2. Se estiver vazio, `g73_event_ticket_url`: link de ingressos opcional.

Sem URL válida, o botão permanece desativado. Eventos-modelo continuam identificados e sem links ativos. O status “Esgotado” não impede consultar a página oficial.

O metadado antigo `g73_event_whatsapp` permanece disponível somente para compatibilidade REST; dados existentes não foram apagados e não são usados no card.

Aplicado aos snippets 9/11 e ao CSS global do WordPress. Os filtros da Agenda permanecem iguais. Verificados prioridade da URL oficial, alternativa de ingressos, ausência de URL e proteção de modelos, usando apenas um rascunho de teste privado, excluído ao final.

Validação responsiva em 1440, 390 e 360 px na Home e no guia, incluindo os três pontos de clique do card. Destinos externos foram interceptados no teste; nenhuma mensagem enviada ou compra realizada. LiteSpeed Cache limpo após implantação.

O hash `9fd4546` informado não foi localizado no remoto disponível. A alteração partiu de `8b21043`, que era o HEAD remoto de `main`, preservando a implementação existente.
