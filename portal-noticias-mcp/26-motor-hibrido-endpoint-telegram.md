# Fase 2 — recepção editorial REST e configuração Telegram

## Estado implantado

Endpoint **POST https://giro73.com.br/wp-json/g73/v1/publish**, autenticado exclusivamente pelo header `X-G73-Token`, via HTTPS. Token aleatório de 256 bits gerado; somente seu hash SHA-256 é armazenado. Recepção habilitada e modo padrão **draft**. A chave do bot Telegram ainda não foi fornecida.

Painel: **Configurações → Motor de Notícias G73** (`/wp-admin/options-general.php?page=g73-publisher`). Permite pausar a recepção, trocar responsável editorial, selecionar rascunho/publicação direta, autorizar domínios de imagens, salvar/remover a chave Telegram e gerar/rotacionar o token do webhook. A rotação mostra o novo token uma única vez e invalida imediatamente o anterior. Valores secretos não são expostos na REST Settings API nem no HTML público.

A chave Telegram, quando informada, é protegida com AES-256-GCM usando chave derivada do salt de autenticação WordPress. Alterar os salts exige recadastrar essa chave. Campo vazio preserva a chave existente; há uma opção explícita para removê-la. Criptografia disponível no servidor verificada.

O endpoint recebe **artigos em JSON**, não Updates nativos do Telegram. O bot/worker deve transformar a mensagem em conteúdo editorial e chamar `/publish`; cadastrar a chave não cria um bot, não configura `setWebhook` e não envia mensagens ao Telegram.

## Contrato

Headers:

```text
Content-Type: application/json
X-G73-Token: [TOKEN GUARDADO NO SEU SECRET MANAGER]
Idempotency-Key: telegram:canal:message-id
```

`Idempotency-Key` é recomendado para identificar uma mensagem ou item de feed. Aceita até 160 caracteres: letras, números, ponto, dois-pontos, hífen e sublinhado. Sem ele, usa-se uma impressão digital do conteúdo normalizado.

| Campo | Regra |
| --- | --- |
| `title` | Texto obrigatório, até 600 bytes |
| `content` | HTML editorial obrigatório, até 450 KB; sem scripts, estilos, blocos embutidos, imagens externas no corpo ou execução de shortcodes |
| `excerpt` | Opcional; gerado do conteúdo quando ausente, limitado a 320 caracteres |
| `category` | ID, slug ou nome exato de categoria existente; não cria categorias |
| `columnist_slug` | Opcional: `arthur-valente`, `helena-gusmao`, `lucas-vianna`, `beto-santana`, `malu-santoro`, `clara-mendes` |
| `image_url` | URL HTTPS de domínio autorizado; obrigatória para publicação direta |
| `tags` | Lista opcional com até 20 nomes |
| `status` | `draft` ou `publish`; `publish` só é aceito quando o administrador habilita publicação direta |
| `focus_keyword` | Palavra-chave opcional para Rank Math, até 100 caracteres |
| `image_credit` | Crédito/licença opcionais, informados pela redação; não se inventa autoria ou licença |
| `image_credit_url` | URL HTTPS opcional da fonte/licença, acompanhada de crédito textual |

O JSON completo não pode ultrapassar 512 KB. Use `implementacao-wp/publish-example.json` como modelo, substituindo dados e URL antes de enviar. Nomes e slugs das colunas preservam o vínculo editorial existente; a assinatura pública continua sendo da Redação Giro73, sob responsabilidade editorial cadastrada.

Exemplo de chamada, com segredo fornecido pelo ambiente do worker:

```bash
curl --request POST 'https://giro73.com.br/wp-json/g73/v1/publish' \
  --header 'Content-Type: application/json' \
  --header "X-G73-Token: ${G73_TOKEN}" \
  --header 'Idempotency-Key: telegram:canal:message-id' \
  --data-binary @artigo.json
```

Resposta de criação: HTTP **201**, `id`, `status`, `permalink`, `created`, `request_id`, `featured_media`, dimensões/URL da imagem, dados de SEO e avisos. Em rascunho, o permalink ainda não é público. Reenvio idêntico retorna **200**, o mesmo ID e `created: false`. Mesmo identificador com conteúdo diferente retorna **409**; o endpoint não sobrescreve artigos existentes nem transforma automaticamente um rascunho repetido em publicação.

## Imagens e SEO

- Domínios iniciais: `giro73.com.br`, `upload.wikimedia.org`, `images.unsplash.com`, `images.pexels.com`. Lista exata, sem curingas; novos CDNs devem ser autorizados no painel.
- Downloads HTTPS com validação de URL segura, sem IPs literais, portas alternativas, credenciais na URL ou redirecionamentos. Timeout 12 segundos, até 8 MB e 24 megapixels.
- JPEG/PNG/WebP de pelo menos 1200×675 são recortados e convertidos no servidor para **WebP 1200×675 abaixo de 60.000 bytes**. Se a imagem não puder ser preparada, a criação é rejeitada; arquivos temporários são limpos.
- Mídia WordPress local já otimizada é reaproveitada, preservando seus créditos e sem reprocessamento.
- Post permanece em rascunho até completar metadados, tags e capa. Falhas removem apenas post/mídia novos pertencentes à requisição.
- Rank Math recebe título, descrição de até 160 caracteres e robots com `max-image-preview:large`; palavra-chave é gravada quando fornecida.
- O grafo Rank Math existente permanece único, com `NewsArticle`, Organization Giro73, autor responsável, datas reais e capa. Colunas de opinião usam `OpinionNewsArticle`, subtipo de `NewsArticle`.
- Ao concluir a criação, o código invalida cache do post e da Home por hooks LiteSpeed. O fluxo nativo WordPress/Rank Math preserva a atualização de sitemap.
- Nenhuma biblioteca ou script foi acrescentado ao frontend. Conversão ocorre apenas durante a recepção do conteúdo.

## Logs e proteção operacional

Últimos 100 registros disponíveis no painel: horário UTC, UUID da requisição, ID do post, HTTP, resultado e duração. Não guardam tokens, conteúdo, chave Telegram ou URL externa. Append com comparação atômica evita perda por concorrência; fallback usa o log PHP do servidor em caso de contenção excepcional.

Uma reserva atômica serializa a criação; chamadas concorrentes podem receber **409** com `Retry-After`. Limite: 20 tentativas de criação em cinco minutos; excesso retorna **429**. Duplicatas já concluídas não consomem a cota. Em HTTP 409/429, aguarde o intervalo informado e repita com o mesmo identificador. Erros de validação não devem ser reenviados sem corrigir os campos.

Rotas administrativas, com autenticação WordPress e capacidade `manage_options`:

- `GET /wp-json/g73/v1/publisher/status`: estado, modo, autor e domínios; somente indicadores de configuração dos segredos.
- `GET /wp-json/g73/v1/publisher/logs`: registros sem segredos.
- `POST /wp-json/g73/v1/publisher/token`: gera/rotaciona token; resposta privada sem cache, com valor exibido uma única vez.

A aplicação WordPress não recebe a senha administrativa pelo webhook; o worker utiliza somente o token dedicado. As credenciais de administração continuam restritas à configuração do portal.

## Implantação e verificação

Fonte: `implementacao-wp/motor-publicacao.php`, snippet global **12**, prioridade **16**, depois dos módulos editoriais existentes. Sem plugin adicional. Não modificar `config.toml`.

Teste `implementacao-wp/tests/publisher-live.py`: autenticação, validação, rejeição de URLs indevidas, criação em rascunho, sanitização, conversão WebP real, tags, metadados, reenvio, conflito de identificador, coluna, reaproveitamento de mídia, concorrência e privacidade dos logs. As fixtures finais (posts 251–253 e mídia 250) foram excluídas e o conjunto de notícias publicadas permaneceu intacto. Os oito grupos de verificações passaram; a rotação também rejeitou o token anterior (401) e aceitou a autenticação com o novo. A publicação direta continua desativada; não foram publicados artigos de teste.

O Schema e `max-image-preview:large` também foram conferidos em matéria pública já existente. O caminho de publicação direta compartilha a preparação validada em rascunhos; não houve publicação pública de fixture para testar essa transição.

Ao finalizar, LiteSpeed Cache foi purgado no escopo completo do site e Home/Agenda verificadas em URLs canônicas. Nenhuma nota PageSpeed foi presumida.
