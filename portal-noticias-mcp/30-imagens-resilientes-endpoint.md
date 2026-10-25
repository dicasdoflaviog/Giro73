# Imagens opcionais no endpoint e fallback persistente no coletor

Registro da versão anterior instalada. Nova versão sem whitelist, com ALT e figura no corpo: [31-fotos-publicas-alt-rankmath.md](31-fotos-publicas-alt-rankmath.md), entregue para substituição e ainda não instalada nesta etapa.

PHP instalado no Snippet 12 do WordPress em **25/10/2026**, após autorização explícita do usuário. Código salvo e ativo, prioridade 16, conferido contra o arquivo do repositório sem a abertura `<?php`. A atualização Python continua entregue para integração: o agente não atualizou a VPS. O coletor da VPS já envia imagens, enquanto a versão de referência do repositório ainda monta `image_url: ""` por padrão; preserve a coleta de imagens personalizada ao integrar os trechos abaixo.

## Instalação confirmada no WordPress

- Backup privado da versão anterior e das configurações criado antes da substituição.
- Atualização feita pela REST API do Code Snippets através do Easy MCP (`wp_rest_write`). Nesta atualização o plugin salvou o código desativado; uma segunda chamada com somente `active: true` o reativou. Conferir `active` e `code_error` após cada atualização, sem supor que o envio de código e ativação em uma chamada garanta ambos.
- SHA-256 do código salvo, sem a abertura PHP: `141fd649761062c3b27ecc5f8b04466300ab1c19e406b04983279dbdc2d16da9`.
- A versão anterior tinha 11 domínios fixos no PHP. Eles foram preservados na opção `g73_publisher_image_hosts`, agora usada pelo código novo, incluindo `prado.ba.gov.br` e `camaraprado.ba.gov.br`. Um auxiliar temporário fez somente essa migração e foi desativado e enviado à lixeira.
- Endpoint habilitado, página inicial HTTP 200 e cache de páginas LiteSpeed limpo. Cache de objetos e CDN não foram limpos.
- Nenhum artigo ou mídia de teste foi criado. A importação real das fotos e o fallback do coletor na VPS não foram executados nesta instalação.
- Resultado sem credenciais: `implementacao-wp/validacao-snippet12-imagens.json`.

## PHP completo — Snippet 12

Arquivo: `implementacao-wp/motor-publicacao.php`. Substitua integralmente o PHP do Snippet 12, preservando o estado ativo. Se o editor já acrescenta a abertura PHP, omita somente a linha inicial `<?php`. Não crie outro snippet com as mesmas funções em paralelo.

- Download com `wp_safe_remote_get`, `redirection: 5`, validação de TLS, URLs seguras e limite de 8 MB. A allowlist de domínios de origem continua valendo; o transporte seguro verifica os redirecionamentos.
- JPEG/PNG/WebP válidos são aceitos mesmo abaixo de 1200×675; permanece o teto de 24 megapixels. HTML e outros arquivos não são anexados como imagens.
- Tenta otimizar para 1200×675 WebP <60 KB. Falhas do editor, resize, conversão ou orçamento preservam o original. A tentativa pode ser desativada em Configurações → Motor de Notícias G73.
- `wp_insert_attachment` registra a mídia. Se `wp_generate_attachment_metadata` falhar, registra os metadados básicos do original sem depender de miniaturas.
- Erros de download, host, formato, armazenamento, registro ou associação da capa não rejeitam o texto. A resposta de criação é 201 com `warnings`, persistidos em `_g73_image_warnings` e preservados em reenvios idempotentes.
- `draft` e `publish` respeitam o JSON; o padrão continua `publish`. A exigência de imagem para `publish` foi removida. O coletor de referência continua enviando exclusivamente `draft`.
- Originais podem ultrapassar 60 KB e ter outra proporção. O limite de performance é uma tentativa de otimização, não uma condição de recebimento. A indisponibilidade do processamento não garante WebP nem PageSpeed 95+.

Para importar as fotos citadas, mantenha na lista de domínios autorizados:

```text
prado.ba.gov.br
camaraprado.ba.gov.br
```

São adicionados no painel, sem apagar os domínios existentes. Se não autorizados, a matéria ainda é criada, mas sem essas imagens e com aviso. Créditos da imagem continuam vindo de `image_credit` e `image_credit_url`.

## Python — integração no coletor existente

Arquivo de referência completo: `automacao-rss/coletor_rss.py`. A montagem de imagens personalizada da VPS não deve ser perdida ao copiar esta versão.

Acrescente a exceção junto às demais exceções:

```python
class ImageFallback(ItemError):
    pass
```

Use a constante `IMAGE_ERRORS` do arquivo completo. Dentro de `Client.submit`, substitua o tratamento de HTTP diferente de 200/201 por:

```python
if r.status_code not in {200, 201}:
    code = result.get("code", "erro_sem_codigo") if isinstance(result, dict) else "erro_sem_codigo"
    if (r.status_code == 422 and isinstance(code, str)
            and code.removeprefix("g73_") in IMAGE_ERRORS
            and payload.get("image_url")):
        raise ImageFallback(f"WordPress HTTP 422: {code}")
    raise ItemError(f"WordPress HTTP {r.status_code}: {code}")
```

Em `process`, o envio passa a ser:

```python
store.attempt(key)
try:
    post_id = client.submit(key, payload)
except ImageFallback as exc:
    LOG.warning("Item %s: %s; reenviando como rascunho sem imagem.", key[:12], exc)
    payload["image_url"] = ""
    payload["status"] = "draft"
    store.prepare(key, payload)  # COMMIT antes de enviar a variante sem imagem
    store.attempt(key)
    post_id = client.submit(key, payload)
store.finish(key, "sent", wp_id=post_id, wp_url=getattr(client, "last_permalink", None))
```

No arquivo completo, `Client.submit` também armazena o `permalink` válido em `last_permalink`; `Store` ganha a coluna `wp_url`, com migração aditiva, e `finish` recebe o argumento opcional `wp_url`. Copie esses ajustes juntos para guardar o link. Não apague o banco SQLite para atualizar o esquema. O permalink de um rascunho não representa uma matéria pública.

O primeiro erro 422 de imagem é uma rejeição confirmada antes da criação no endpoint antigo. O fallback reutiliza a mesma `Idempotency-Key`, altera apenas `image_url`/`status` e salva o novo payload antes do segundo POST. Se a resposta desse POST for perdida, o próximo cron repete a mesma variante, sem gerar outra matéria. Timeout, 503, 409 de conflito, erros de SEO/autenticação e 422 sem imagem não disparam esse fallback. O segundo POST permanece sujeito às retentativas de transporte já existentes; não há uma terceira variante de payload nem loop de 422.

Com o PHP atualizado, a maior parte desses casos passa a retornar 201 com aviso e não precisa de um segundo POST. O fallback Python mantém compatibilidade com a versão antiga do endpoint.

### Recuperar itens antigos bloqueados por imagem

Depois de integrar a versão Python, opcionalmente execute:

```bash
sudo -u giro73-rss /opt/giro73-rss/run --retry-image-errors
sudo -u giro73-rss /opt/giro73-rss/run --limit 1
```

O primeiro comando é local: recupera somente `blocked` com erro `WordPress HTTP 422` de imagem reconhecido, sem ID WordPress confirmado, com payload `draft` e `image_url` ainda preenchida. Limpa a imagem, preserva o ID da fila e prepara para reenvio; outros bloqueios permanecem para revisão. Ele não chama APIs nem cria posts. A execução seguinte envia rascunhos normalmente.

## Validação

- 39 testes Python offline, incluindo os 422 solicitados, persistência antes do fallback, resposta perdida/reinício, continuidade da fila, migração SQLite e recuperação seletiva de bloqueados.
- PHP 8.3: sintaxe validada e 17 verificações offline com APIs WordPress simuladas, cobrindo erros de GD/Imagick, metadata, download, associação, formato, replay e status.
- Nenhum artigo, mídia ou snippet do site real foi alterado para esses testes. `tests/publisher-live.py` foi adaptado, mas não executado. Os casos de `publish` são testados offline para não colocar fixtures no ar.

```bash
python -m unittest discover -s portal-noticias-mcp/automacao-rss/tests -q
php -l portal-noticias-mcp/implementacao-wp/motor-publicacao.php
php portal-noticias-mcp/implementacao-wp/tests/publisher-images-unit.php
```
