# Agenda 73 — filtros, ingressos e contato

Implementação publicada em `https://giro73.com.br/agenda-73/` (página 234), com a mesma vitrine na Home e link “Ver agenda completa”. Nenhum plugin adicional instalado.

## Gestão no WordPress e REST

No painel, **Agenda G73 → editar evento**: mantenha data, cidade, imagem destacada e confirmação; preencha a nova caixa **Categoria, local e link do evento**.

`/wp-json/wp/v2/g73_event/{id}` aceita os campos abaixo em `meta`, com autenticação de administrador. Todos estão registrados com `show_in_rest`; a gestão dos rascunhos permanece privada.

| Campo | Conteúdo |
| --- | --- |
| `g73_event_category` | `shows_festas`, `gastronomia_feiras`, `esportes`, `teatro_cultura`, `negocios_cursos` ou `infantil_familia` |
| `g73_event_city` | Cidade; campo existente preservado |
| `g73_event_location` | Local físico confirmado |
| `g73_event_price` | Texto: Gratuito, A partir de R$ 40, Esgotado etc. |
| `g73_event_url` | Link oficial do evento; destino principal da foto, título e botão |
| `g73_event_ticket_url` | Link de ingressos opcional, usado quando não houver link oficial do evento |
| `g73_event_whatsapp` | Campo legado preservado na REST; não aparece no card nem no formulário de edição |

`g73_event_date`, `g73_event_demo` e `featured_media` continuam disponíveis. O campo `g73_event_url` é o link principal. O card oferece uma única ação, **Ver evento →**, e foto/título levam ao mesmo destino.

Os quatro modelos selecionados (147, 148, 149 e 180) continuam em rascunho, identificados como demonstração. Receberam categorias, sem inventar locais, preços, ingressos ou números. Os modelos só preenchem a vitrine enquanto não houver eventos futuros confirmados. Para divulgar um evento real, confirmar informações, retirar a marca de modelo e publicar; a validação exige data e cidade.

## Interface e desempenho

- Sete pílulas com “Todos” ativo inicialmente; rolagem horizontal no celular, foco visível e respeito a movimento reduzido.
- Filtro local sem requisição quando a coleção inteira está carregada. Com mais eventos, os links filtram a consulta completa no servidor; diretório com paginação de 24 itens, Home com até quatro.
- Funciona sem JavaScript. Filtros usam `?g73_categoria=...`; paginação usa `g73_agenda_page`.
- Cards com foto, data, categoria/cidade, local e preço; SVGs inline e botões de pelo menos 46 px.
- Foto, título e botão **Ver evento →** abrem a URL do evento em nova aba com `nofollow noopener noreferrer`. Não há botão de contato com a produtora.
- Sem URL ou em modelos, o botão fica desativado. O status Esgotado/Encerrado não impede consultar o site do evento.
- JS inline condicional de 1.018 bytes; nenhuma biblioteca, fonte ou serviço externo novo. Imagens existentes carregadas sob demanda com proporção reservada.

## Fontes e implantação

- `implementacao-wp/agenda-eventos.php`: módulo nativo, executado depois de engajamento e autoridade regional; implantação no snippet 11, prioridade 15.
- `implementacao-wp/agenda-73-page.html`: conteúdo da página do guia.
- `implementacao-wp/css-g1-techtudo-ultra-leve.css`: estilos globais, incluindo o guia.
- `implementacao-wp/engajamento-regional.php`: campo principal identificado como Link oficial do evento.
- Prévia administrativa: `GET /wp-json/g73/v1/events/preview/{id}`, autenticada e com `Cache-Control: private, no-store`.

## Validação original

A simplificação posterior para link único está registrada em `27-agenda-link-unico.md`.

`tests/agenda-live.py` passou em 1440, 390 e 360 px para Home e guia: sete filtros, seleção vazia, recuperação de “Todos”, oito botões nos modelos, área de toque e ausência de transbordamento da página. Sem JavaScript, a categoria é filtrada no servidor.

Um evento de teste privado (235) validou as duas ações habilitadas, URL encoding, nova aba, atributos `rel`, normalização brasileira/internacional, esgotamento e bloqueio de ações em modelos. Destinos externos foram interceptados no navegador: nenhuma compra ou mensagem enviada. O rascunho de teste foi excluído ao final.

REST rejeitou categoria inválida, URL insegura, telefone inválido e publicação de modelo (HTTP 400), preservando os valores anteriores. Gestão e prévia sem autenticação responderam HTTP 401.

LiteSpeed Cache foi purgado no escopo completo do site, cobrindo variantes Desktop/Mobile, junto ao sitemap Rank Math. A validação usa Chromium responsivo; não representa teste em aparelho físico ou medição de nota PageSpeed.
