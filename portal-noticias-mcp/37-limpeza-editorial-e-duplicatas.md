# Limpeza editorial, grupo WhatsApp e proteção de duplicatas

Executado em 26/10/2026 por autorização do responsável. O escopo foi confirmado: retirar somente os cinco guias iniciais e preservar os seis artigos de colunas e quatro guias posteriores.

## Retirada de publicação

IDs 20, 21, 22, 23 e 24 foram movidos para a lixeira, com backup privado prévio. As fotografias da biblioteca não foram excluídas, evitando afetar referências compartilhadas e permitindo restauração.

A cobertura repetida do vendaval em Teixeira de Freitas, ID 416, também foi para a lixeira. A versão mais completa, ID 414, foi preservada. A URL antiga recebe HTTP 301 para a cobertura preservada, por regra nativa do snippet 8. Nenhum plugin foi instalado.

Os artigos 208, 210, 211, 212 e 215 mantiveram seus textos, com remoção das ligações para os cinco guias retirados. As âncoras viraram texto simples, sem links quebrados. Os demais dez textos da primeira expansão foram preservados.

## WhatsApp e acabamento

O convite oficial fornecido pelo responsável foi salvo em `g73_whatsapp_group_url`. A Home e os artigos exibem o botão com o endereço informado. Não houve ingresso automático no grupo nem envio de mensagens. O convite não fica embutido no código; pode ser atualizado pelo painel/API.

O snippet 9 omite o card quando não existe convite válido. O snippet 11 mantém modelos de eventos apenas em prévia autenticada e omite a vitrine vazia da Home pública; eventos confirmados continuam sendo exibidos normalmente. Os modelos continuam editáveis em rascunho no painel.

## Proteção no motor central — snippet 12

- Nova chave opcional `source_url`, normalizada e registrada em metadado interno. O coletor monta essa URL localmente; a IA não escolhe a origem.
- Reconhecimento da atribuição final de bots antigos; índices genéricos não contam como URL única de notícia.
- Origem já recebida: HTTP 409 `g73_duplicate_article`, sem novo post ou download de mídia.
- Título e abertura muito semelhantes a notícia recente da mesma categoria: HTTP 409 `g73_duplicate_review`, para revisão editorial, sem criar outra página pública.
- Similaridade limitada a 60 posts dos últimos sete dias; é uma heurística, não garantia de detectar todas as reformulações. Notícias diferentes não são apagadas automaticamente por compartilharem cidade ou assunto.
- O lock do motor e a idempotência continuam ativos; o novo campo de origem não muda fingerprints antigos. A verificação ocorre antes de importar foto e criar post.

A barreira do servidor já protege RSS e Telegram. A versão Python do repositório registra duplicatas exatas como `skipped` e suspeitas como `blocked`, com referência ao post existente. A VPS não foi acessada por SSH; seu coletor pode ser atualizado pelo atualizador existente, sem reaplicar a política das fontes.

## Verificação

- Seis IDs confirmados em `trash` e ausentes do sitemap de posts.
- Home, matéria do Fies e cobertura preservada: HTTP 200; convite correto; zero cartões de demonstração ou botões WhatsApp inativos.
- URL da duplicata: HTTP 301 para o artigo preservado.
- Sitemaps de posts, páginas e categorias: HTTP 200 e XML válido.
- Snippets 8, 9, 11 e 12 ativos após salvar/reativar. LiteSpeed e cache de sitemap Rank Math limpos.
- 87 testes Python, 32 verificações PHP do motor, 9 verificações PHP dos componentes e sintaxe conferida. Não foram criadas matérias públicas de teste.

Resultado sem credenciais: `implementacao-wp/validacao-limpeza-editorial.json`. O diagnóstico AdSense anterior continua válido para as pendências editoriais e de privacidade; esta limpeza não representa aprovação pelo Google.
