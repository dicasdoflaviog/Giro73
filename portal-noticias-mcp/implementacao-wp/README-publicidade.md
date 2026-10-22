# Parceiros G73 — campanhas nativas

**Atualização 22:** caixas removidas, imagens em tamanho natural e seis posições de comparação preenchidas. Consulte `../22-parceiros-dimensoes-comparacao.md` para o estado publicado atual.

Implementação de 22/10/2026, substitui a gestão antiga de dois slots. Usa o **snippet global 6** no Code Snippets existente, sem instalar outro plugin. Inserções editoriais permanecem no snippet 7. Não carregar uma segunda cópia do PHP no tema.

## Painel

- **Parceiros G73 → Nova campanha**: https://giro73.com.br/wp-admin/post-new.php?post_type=g73_partner
- **Campanhas**: https://giro73.com.br/wp-admin/edit.php?post_type=g73_partner
- **Configurações**: https://giro73.com.br/wp-admin/edit.php?post_type=g73_partner&page=g73-partners-settings

Use o título para o nome do anunciante/campanha. Selecione imagem desktop e, opcionalmente, uma arte mobile; escolha posição e formato, informe destino HTTP/HTTPS e status Ativo/Pausado. **Publicado + Ativo + agenda vigente** são necessários para veicular. Início e expiração são opcionais; o painel usa **America/Bahia**. Uma peça somente mobile também funciona como fallback desktop. Imagens aceitas: WebP, PNG e JPG; prefira WebP leve. Nenhuma mídia é excluída ao pausar ou remover uma campanha.

A migração inicial preservou os dois registros anteriores. Na atualização 22, a campanha **135** foi ajustada ao topo largo com mídia desktop **134** e mobile **131**, e a **136** ao primeiro miolo com mídia **132**. Quatro campanhas adicionais preenchem as outras posições. As mídias antigas permanecem na biblioteca. Os banners de comparação apontam para a página Contato do portal.

## Formatos e posições

| Valor do formato | Dimensões oficiais |
|---|---|
| `retangulo_medio` | 300×250 |
| `retangulo_grande` | 336×280 |
| `meia_pagina` | 300×600 |
| `billboard` | 970×250 |
| `leaderboard_grande` | 970×90 |
| `faixa_mobile` | 320×50 |
| `popup_modal` | 600×600 ou arte vertical 500×650 |

As peças se ajustam à largura disponível com largura máxima de 100% e altura automática, preservando a imagem completa. A largura e a altura intrínsecas da mídia definem a proporção. O formato é a classificação da campanha, sem forçar caixa artificial ao redor de uma arte de outra proporção. A posição é independente do formato: escolha uma combinação adequada à largura da coluna.

| Posição | Inserção |
|---|---|
| `topo_portal` | Abaixo do header na Home, single e arquivos/editorias/busca/autor |
| `sidebar_lateral` | Sidebar compartilhada, abaixo de Mais lidas e antes de Economia |
| `sidebar_base` | Sidebar compartilhada, abaixo de Economia |
| `home_entre_secoes` | Home, entre o hero e Últimas notícias, independente do segundo miolo |
| `in_article_1` | Após o terceiro parágrafo; no final quando houver menos de três |
| `in_article_2` | Após o sexto parágrafo da matéria, quando existir |
| `popup_global` | Sobreposição central, sujeita à chave geral, escopo e frequência |

Cada posição considera até **dez campanhas publicadas e ativas**, ainda válidas, em ordem de cadastro; peças futuras ficam reservadas para sua agenda. Excedentes não entram na seleção. Expiradas e pausadas não são entregues pelo endpoint público. Para substituir uma campanha, pause a anterior.

**Rotação**: posição inicial alterna a cada visita via localStorage e muda a cada oito segundos. Pausa em aba oculta, durante a sobreposição, ao passar o mouse ou focar a peça. **Empilhado**, disponível separadamente para `sidebar_lateral` e `sidebar_base`, mostra as peças uma abaixo da outra. As outras posições mantêm rotação. Cada arte usa sua altura natural, com a micro-aba PUBLICIDADE colada no topo; não há fundo cinza, borda, padding ou altura fixa nos containers. Para rodízio contínuo sem mudança de altura, mantenha a mesma proporção nas campanhas de cada posição. No modo de comparação atual há uma peça por posição.

Sem campanha elegível, a posição fica oculta; não aparece caixa vazia nem texto de espaço disponível. O contato de gestão continua `comercial@giro73.com.br`. Nenhum telefone fictício é usado. Links de parceiros abrem em outra aba com `rel="sponsored noopener"`; o rótulo PUBLICIDADE permanece visível.

## Sobreposição

Em **Parceiros G73 → Configurações**:

- Chave geral, inicialmente **desligada**, sem campanha real de sobreposição cadastrada.
- Gatilho por tempo, padrão três segundos **após load**, mínimo três; ou por rolagem, padrão 30%.
- Frequência diária, por sessão ou sempre para testes. Dia calculado no fuso da Bahia; sessão usa sessionStorage. Dependem da disponibilidade do armazenamento no navegador.
- Escopo: todo o portal, somente Home ou somente matérias.
- Auto-fechar: padrão quinze segundos; zero desativa.
- Fechar por botão visível de 44 px, clique no fundo ou ESC. Usa `<dialog>` nativo, foco no botão e retorno ao elemento anterior; fundo com blur de 3 px. Próxima visita elegível escolhe outra campanha, sem troca de peça enquanto o leitor interage com o modal.

Frequência diária e atraso reduzem repetição e interferência no carregamento; **não garantem ausência de impacto de intersticiais no SEO**, especialmente no celular. Prefira escopo Home e frequência diária. Não há bibliotecas de slider, iframe, pixel, mensuração de cliques ou envio automático de mensagens.

## REST API e Easy MCP AI

Gestão requer administrador (`manage_options`) e autenticação WordPress existente. Nenhuma credencial deve ser colocada neste repositório. CPT `g73_partner`, `show_in_rest=true`, com título e custom-fields registrados.

- `GET/POST /wp-json/wp/v2/g73_partner`
- `GET/POST/DELETE /wp-json/wp/v2/g73_partner/{id}`
- `GET/POST /wp-json/wp/v2/settings` → `g73_partners_config`
- Público: `GET /wp-json/g73/v1/partners` — apenas dados para exibir campanhas candidatas e configurações de apresentação; `Cache-Control: no-store`.

| Meta | Tipo / valores |
|---|---|
| `g73_desktop_image` | ID inteiro da mídia, 0 se vazio |
| `g73_mobile_image` | ID inteiro, opcional |
| `g73_url` | URL HTTP/HTTPS ou vazio |
| `g73_status` | `ativo` / `pausado` |
| `g73_start_at` | ISO 8601 com fuso, ou vazio |
| `g73_end_at` | ISO 8601 com fuso, ou vazio; posterior ao início |
| `g73_format` | Um dos sete formatos |
| `g73_position` | Uma das sete posições (seis no fluxo + sobreposição) |

Exemplo de corpo de criação, **rascunho pausado**, usando imagem já existente:

```json
{
  "title": "Nome real do parceiro — campanha",
  "status": "draft",
  "meta": {
    "g73_desktop_image": 133,
    "g73_mobile_image": 0,
    "g73_url": "https://giro73.com.br/contato/",
    "g73_status": "pausado",
    "g73_start_at": "",
    "g73_end_at": "",
    "g73_format": "retangulo_medio",
    "g73_position": "sidebar_lateral"
  }
}
```

Ativar: POST no ID com `{"status":"publish","meta":{"g73_status":"ativo"}}`. Pausar: `{"meta":{"g73_status":"pausado"}}`. Datas: por exemplo `2026-11-01T08:00:00-03:00`. Não envie data local sem offset. Imagem inexistente/incompatível, período invertido ou status/formato inválido são recusados. Ativar exige ao menos uma imagem.

No **Easy MCP AI**, usar `wp_create_cpt_item`, `wp_get_cpt_item`, `wp_update_cpt_item`, `wp_list_cpt_items` e `wp_delete_cpt_item`, com **`rest_base: "g73_partner"`**. Os campos vão no objeto `meta`. Para configurações usar `wp_rest_read`/`wp_rest_write`, `route: "/wp/v2/settings"`. O ciclo de criação/edição/exclusão foi conferido via MCP; os registros de teste foram excluídos.

Exemplo de configuração completa para salvar em `g73_partners_config` (ao editar, leia primeiro e preserve os outros campos):

```json
{
  "contact_email": "comercial@giro73.com.br",
  "modes": {
    "topo_portal": "rotacao",
    "sidebar_lateral": "rotacao",
    "sidebar_base": "rotacao",
    "home_entre_secoes": "rotacao",
    "in_article_1": "rotacao",
    "in_article_2": "rotacao",
    "popup_global": "rotacao"
  },
  "overlay": {
    "enabled": false,
    "trigger": "tempo",
    "delay": 3,
    "scroll": 30,
    "frequency": "1x_por_dia",
    "scope": "todo_portal",
    "auto_close": 15
  }
}
```

O objeto de configurações é substituído e sanitizado; envie os campos que deseja preservar. Os metadados da campanha podem ser atualizados parcialmente.

## Engenharia, cache e manutenção

- PHP: `publicidade-parceiros.php` (snippet 6). Shortcodes novo `[giro73_parceiros posicao="..."]` e legado `[giro73_publicidade posicao="sidebar|article"]` compatível.
- PHP editorial: `single-editorial.php` (snippet 7), preserva Leia também e compartilhamento, insere unidades sem reescrever o conteúdo dos posts.
- Templates e CSS versionados nesta pasta. Micro-aba por peça, largura natural e classes neutras `.g73-partner-unit`, `.g73-vitrine-box`, `.g73-media-frame` e `.g73-overlay-card`; não há `ad-slot` no HTML/CSS ativo. Isso reduz conflitos com filtros cosméticos por classe, sem prometer contornar todos os bloqueadores.
- As imagens e suas dimensões são renderizadas no servidor, inclusive os atributos width/height da versão mobile em picture. Uma cópia de JS inline por página, nenhuma biblioteca no frontend. Uma requisição local leve no navegador sincroniza campanhas e horário, independentemente do cache de HTML. Imagens são carregadas apenas nas peças exibidas, com lazy loading no fluxo e prioridade normal no modal.
- Datas são verificadas no endpoint e no navegador; expiração não depende do WP-Cron. Mudanças de campanha/configuração invalidam LiteSpeed no shutdown, uma vez por requisição, incluindo as variantes desktop/mobile. Navegações seguintes recebem a nova configuração; leitores que já abriram a página mantêm sua seleção até navegar, com a agenda local ainda respeitada.
- O CPT é privado, não aparece no feed de notícias, busca pública, arquivos jornalísticos ou sitemap Rank Math. Administração REST anônima é recusada; endpoint público não permite escrita.
- Opção antiga `g73_partner_ads` permanece intacta como backup, não é mais o painel de gestão. A flag `g73_partners_migrated_v1` impede recriação/religamento de campanhas. Não apagar essa flag em produção.
- Rollback: restaurar snippets 6/7, estilos e templates do commit anterior, limpar LiteSpeed. Não excluir mídias/CPT para fazer rollback. Cotações (snippet 5), Schema/ads.txt (snippet 8), páginas e artigos permanecem preservados.

Teste de comportamento isolado: `python portal-noticias-mcp/implementacao-wp/tests/parceiros-browser.py`, com Python Playwright e Chromium instalados. Não altera o WordPress. Registros de validação: `../20-gerenciador-parceiros-g73.md`.
