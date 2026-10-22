# WhatsApp e Open Graph

Atualização de **22/10/2026**, na rotina editorial do snippet **7**, sem JavaScript novo, plugin adicional ou alterações em posts/imagens.

## Mensagem e botão

URL gerada no servidor:

```text
https://api.whatsapp.com/send?text=RAWURLENCODE(Título da matéria + " - Leia em Giro73: " + URL permanente)
```

Usa `rawurlencode`, mantendo espaços como `%20`, caracteres acentuados em UTF-8 e a URL interna devidamente codificada no parâmetro `text`. O título é decodificado das entidades HTML antes da montagem. Nenhum asterisco de formatação foi acrescentado ao padrão literal pedido. O destaque visual do título do card é controlado pelo WhatsApp, não por esse texto simples.

O link funciona sem JavaScript e sem envio automático. A navegação é iniciada somente pelo leitor.

## Open Graph

O Rank Math continua sendo o único gerador das tags no head. As descrições já correspondiam aos excertos, as URLs aos permalinks e as imagens às fotografias WebP de 1200×675. Os títulos sociais eram versões abreviadas de SEO; o filtro específico **`rank_math/opengraph/facebook/og_title`** agora entrega o título editorial completo apenas nos artigos. O título SEO de busca permanece independente. Não foram injetadas tags duplicadas manualmente.

O nome do filtro foi conferido na implementação pública oficial do Rank Math: o método `tag` substitui `:` por `_` na propriedade; portanto `og:title` usa `og_title`.

## Limites da validação

A emulação mobile valida interface, mensagem, percent encoding e a navegação solicitada pelo botão. A navegação para WhatsApp é interceptada para não abrir sessão externa ou enviar mensagem. Isso não equivale a testar um telefone físico, o aplicativo real ou o card gerado nos servidores do WhatsApp.

O cache LiteSpeed dos cinco artigos foi invalidado após a atualização. Essa limpeza não apaga o cache do WhatsApp/Meta; não se garante renderização instantânea ou atualização imediata de uma prévia já armazenada pelo serviço.

## Conferência concluída

- Nos cinco artigos, exatamente uma tag de cada propriedade essencial: og:title igual ao título editorial, og:description igual ao excerto limpo, og:url igual ao permalink/canonical e og:image igual à URL original da mídia destacada atual.
- Páginas e imagens retornaram HTTP 200 em consulta HTTPS verificada com User-Agent de WhatsApp. Os cinco arquivos foram decodificados e conferidos: WebP, 1200×675, entre 27.626 e 59.260 bytes. Dimensões e MIME das tags também corretos.
- Emulação de iPhone 13 no Chromium: cinco links conferidos, texto e percent encoding exatos, sem overflow. Toque no botão de uma matéria capturado antes da rede externa, com URL esperada. Nenhuma mensagem enviada. Link também presente e funcional como href com JavaScript desativado.
- PHP válido e snippet ativo sem code_error. Conteúdo, metadados, imagens e datas dos posts preservados por comparação REST. Fontes publicadas iguais às do repositório.
- LiteSpeed dos cinco URLs canônicos limpo após a atualização e conferido após o intervalo indicado pelo servidor. Não foi testado WhatsApp real em telefone físico; os resultados acima não afirmam renderização efetiva do card pelo aplicativo.
