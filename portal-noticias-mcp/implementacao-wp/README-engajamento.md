# Engajamento e autoridade regional Giro73

Fonte: **engajamento-regional.php**, snippet global **9**, prioridade 13 no Code Snippets já instalado. Não instalar outro plugin nem carregar uma segunda cópia no tema. CSS e templates nesta pasta são as versões publicadas.

## Configuração do portal

**Configurações → Engajamento G73**:

https://giro73.com.br/wp-admin/options-general.php?page=g73-engagement

- **Grupo WhatsApp**: convite `https://chat.whatsapp.com/...`. O campo aceita somente o domínio oficial e HTTPS. Sem convite, os cards continuam visíveis, mas o botão fica desativado com aviso “Convite oficial disponível em breve”. O usuário confirmou deixar assim até fornecer o endereço.
- **Colunistas**: até quatro espaços, com autor WordPress, nome público opcional, área de atuação e avatar WebP autorizado. O nome em branco usa o perfil do autor selecionado. A exibição é circular de 56×56 px; sem imagem, monograma editorial G73. Sem pessoas configuradas, três espaços identificados como **Em breve**. Não foram inventadas biografias nem colunas.
- Cada colunista aponta para seu perfil e para seu último post publicado marcado como **opinião**. O autor da notícia é o selecionado no editor WordPress; o bloco não cria pessoas nem troca autoria de artigos existentes.

Campos em `GET/POST /wp-json/wp/v2/settings`, com autenticação administrativa:

```json
{
  "g73_whatsapp_group_url": "",
  "g73_columnists": [
    {"author_id": 0, "name": "", "area": "", "avatar_id": 0},
    {"author_id": 0, "name": "", "area": "", "avatar_id": 0},
    {"author_id": 0, "name": "", "area": "", "avatar_id": 0}
  ]
}
```

Ao preencher, use os IDs reais do usuário e da mídia. `g73_columnists` é um array JSON completo; preserve as linhas que deseja manter. O convite não foi substituído por link falso ou por um número de telefone.

## Reações e enquetes

No editor de cada notícia há o metabox **Enquete e opinião — Giro73**, com ativação, pergunta, duas a cinco opções (uma por linha), posição e marca de opinião. No editor de blocos, o metabox aparece na área de metadados nativa. Nenhum plugin de formulários é necessário.

- As três reações aparecem automaticamente no final da matéria. Contagens iniciais são zero; não há números de engajamento fabricados.
- Uma reação por matéria e um voto por versão de enquete ficam travados em `localStorage`.
- Há também um cookie funcional próprio `g73_reader`, assinado pelo servidor, Secure em HTTPS, HttpOnly e SameSite=Lax. Deduplicação adicional por leitor/matéria/tipo por trinta dias; com cache persistente usa operação atômica do cache, sem cache persistente usa opções transitórias exclusivas. O cookie pode durar até um ano. Não há identificação civil do leitor nem promessa de votação antifraude; limpar armazenamento ou mudar de navegador pode permitir nova participação.
- Limite de tentativas por leitor e hash do IP, sem guardar o IP puro no controle. Não há pixel, fonte, CAPTCHA ou biblioteca de terceiros.
- Gravação de contadores em `post_meta` usa compare-and-swap, evitando perder incrementos concorrentes. Votar não purga o site a cada clique.
- GET/POST de participação usam resposta privada sem cache. O frontend consulta os dados atuais somente quando o bloco se aproxima da tela, via IntersectionObserver. Uma requisição por matéria alimenta seus blocos na página; o JS carrega uma vez e apenas se houver reação/enquete.
- Resultados em barras CSS são exibidos após votar, com porcentagem e total. A enquete é identificada como participação de leitores, não pesquisa científica.
- Alterar pergunta ou opções zera os resultados e muda a versão da enquete. Uma página antiga não consegue votar na versão nova por engano.
- Comentários e pingbacks em posts são fechados por filtros nativos, sem apagar comentários antigos ou alterar o banco dos artigos. As reações substituem a participação aberta no template.

Metas de posts registradas com `show_in_rest=true` e escrita autorizada a quem pode editar a matéria:

| Meta | JSON |
|---|---|
| `g73_reactions` | objeto com `util`, `surpreendente`, `indignado`, inteiros não negativos |
| `g73_poll_enabled` | booleano, padrão false |
| `g73_poll_question` | texto, até 180 caracteres |
| `g73_poll_options` | array JSON de duas a cinco strings quando ativa; até 100 caracteres cada |
| `g73_poll_votes` | array JSON de inteiros, na ordem das opções |
| `g73_poll_placement` | `article`, `sidebar` ou `both` |
| `g73_is_opinion` | booleano para alimentar a última coluna do autor |

Exemplo de corpo para `POST /wp-json/wp/v2/posts/{ID_REAL}` (não é publicado automaticamente pela documentação):

```json
{
  "meta": {
    "g73_poll_enabled": true,
    "g73_poll_question": "Qual tema regional precisa de mais cobertura?",
    "g73_poll_options": ["Saúde e serviços", "Emprego e renda", "Cultura e eventos"],
    "g73_poll_placement": "both"
  }
}
```

Não preencher votos artificiais. Os votos reais são escritos pelo endpoint e as contagens podem ser lidas pela API. Desativar mantém os resultados; mudar pergunta/opções cria outra votação e zera contagens. Na sidebar aparece a enquete mais recente publicada e configurada para `sidebar`/`both`; no final da matéria aparece apenas sua enquete se configurada para `article`/`both`.

Endpoints leves:

- `GET /wp-json/g73/v1/engagement/{id}`: reações e enquete atuais; emite o cookie funcional se necessário.
- `POST /wp-json/g73/v1/engagement/{id}/vote`: corpo `{"kind":"reaction","choice":"util"}` ou `{"kind":"poll","choice":1,"version":"VERSAO_DO_GET"}`. Índice da opção começa em zero. Deve usar o mesmo cookie do GET. Erros: opção inválida 400, repetição/versão antiga 409, excesso 429. Origem externa não autorizada é recusada.
- Anônimos só acessam notícias públicas sem senha. Rascunhos/privados só podem ser consultados e testados por quem pode editar a matéria; isso permite prévia editorial sem criar engajamento público de teste.

## Agenda regional

**Agenda G73**: https://giro73.com.br/wp-admin/edit.php?post_type=g73_event

CPT privado `g73_event`, REST base `g73_event`, título para o nome e metas:

| Meta | Valor |
|---|---|
| `g73_event_date` | data `AAAA-MM-DD` no fuso do portal |
| `g73_event_city` | cidade da cobertura |
| `g73_event_url` | informações/ingressos HTTP/HTTPS ou vazio |
| `g73_event_demo` | booleano; demonstrações não são veiculadas |

Três exemplos foram criados como **rascunhos de demonstração**, editáveis, IDs **147**, **148**, **149**: Teixeira de Freitas, Porto Seguro e Prado. Os nomes começam com MODELO e as datas são de exercício, não confirmação de eventos. O usuário confirmou deixar tudo pronto para preencher depois. Para veicular: trocar por evento confirmado, conferir data/cidade/link, desmarcar demonstração e publicar. O sistema recusa a publicação de demonstrações ou de eventos sem data/cidade. Datas impossíveis são recusadas.

A agenda pública mostra até três próximos eventos confirmados, ordenados por data, com selo dia/mês, cidade, nome e link. Abrange qualquer cidade regional informada, incluindo Eunápolis. Eventos anteriores à data local não aparecem. Sem confirmação, mostra mensagem honesta e contato da redação. O CPT fica fora de notícias, busca pública e sitemap. Os exemplos são idempotentes, protegidos pela opção `g73_events_examples_v1`; não apagar essa flag para evitar recriação.

REST administrativa: `GET/POST /wp-json/wp/v2/g73_event` e `GET/POST/DELETE /wp-json/wp/v2/g73_event/{id}`. A API de gestão exige administrador. No **Easy MCP AI**, usar `wp_*_cpt_item` com `rest_base: "g73_event"`. Para posts usar `wp_update_post`/`wp_update_post_meta`; para configurações usar `wp_rest_read`/`wp_rest_write` em `/wp/v2/settings`.

## Templates e impressão

- Sidebar compartilhada: grupo WhatsApp, enquete opcional e agenda abaixo de Economia.
- Single: reações, enquete e grupo WhatsApp após o texto e antes do autor. A leitura permanece sem sidebar.
- Home: faixa de colunistas abaixo da seção principal, sem inventar especialistas.
- Print: `@media print` esconde header de navegação/topbar, sidebar, botões, enquetes, reações, CTA, autor de apoio, rodapé do site e parceiros/modais. Mantém marca G73 limpa, editoria, título, resumo, assinatura/data, foto/crédito e texto em Georgia/serif.
- A URL canônica original aparece na margem inferior das folhas via regra nativa `@page`/`@bottom-left`, sem cobrir o texto. Em navegadores sem suporte a caixas de margem, há uma fonte em fluxo normal ao final do artigo. Uma verificação leve de `CSSMarginRule` evita duplicar a fonte nos navegadores compatíveis. Navegador/impressora podem acrescentar seus cabeçalhos próprios se o leitor os habilitar.

Shortcodes nativos: `[giro73_grupo_whatsapp]`, `[giro73_reacoes]`, `[giro73_enquete]`, `[giro73_enquete posicao="sidebar"]`, `[giro73_colunistas]`, `[giro73_agenda]`, `[giro73_impressao_cabecalho]`, `[giro73_impressao_url]`. Não duplicar os blocos automáticos.

Alterações de convite/colunistas/agenda invalidam LiteSpeed. Manter os snippets 5–8 ativos. Para rollback, restaurar os três templates e CSS anteriores, desativar apenas snippet 9 e limpar cache; não apagar posts, mídias, eventos ou resultados. Nenhuma credencial foi colocada no código.

Teste isolado: `python portal-noticias-mcp/implementacao-wp/tests/engajamento-browser.py`, com Playwright e Chromium. Não grava no WordPress. Registro: [implantação 21](../21-engajamento-retencao-autoridade-regional.md).
