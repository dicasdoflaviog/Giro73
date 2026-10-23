# Fechamento — autoridade regional, engajamento e parceiros

Aplicado em 22/10/2026 no horário da Bahia (23/10 UTC), com código WordPress nativo no mecanismo de snippets existente. Nenhum plugin adicional foi instalado.

## Colunistas & Opinião

Seis perfis no menu **Colunistas G73**, CPT `g73_columnist`, administrável por REST autenticada. Avatares de iniciais em WebP de **64×64 px**, entre **1.358 e 1.424 bytes**. A Home exibe os seis perfis em uma faixa de seis colunas no desktop; passa para três e duas colunas nas telas menores. Não há textos ou colunas publicados ficticiamente: até a primeira publicação vinculada, cada perfil informa “Primeira coluna em preparação”.

| Perfil | Coluna | Slug | Registro / avatar |
|---|---|---|---|
| Arthur Valente | Bússola do Bolso | `arthur-valente` | 174 / 167 |
| Helena Gusmão | Radar Político & Gestão | `helena-gusmao` | 175 / 168 |
| Lucas Vianna | Futuro Prático | `lucas-vianna` | 176 / 169 |
| Beto Santana | Arquibancada & Resenha | `beto-santana` | 177 / 170 |
| Malu Santoro | Veneno & VIP | `malu-santoro` | 178 / 171 |
| Dra. Clara Mendes | Raiz & Vitalidade | `clara-mendes` | 179 / 172 |

Todos são identificados como **personas editoriais**. O nome de Clara não é apresentado como comprovação de formação médica. As bios não inventam currículos, diplomas ou registros profissionais. Os cinco artigos-base conservam sua autoria atual, assim como o responsável real no Schema. Não foi criada uma sexta categoria de notícias para preencher a vitrine.

Metadados registrados em posts e perfis, com `show_in_rest`:

- `g73_columnist_slug`, `g73_columnist_name`, `g73_columnist_title`, `g73_columnist_bio`: strings.
- `g73_columnist_avatar`: ID inteiro de mídia.
- No cadastro de perfil: `g73_columnist_area` e `g73_columnist_color`.

Consultar o diretório público aprovado:

```http
GET /wp-json/g73/v1/columnists
```

Vincular um post existente ou novo, com autenticação e permissão de edição:

```http
POST /wp-json/wp/v2/posts/ID
Content-Type: application/json

{"meta":{"g73_columnist_slug":"arthur-valente"}}
```

Nome, coluna, bio e avatar são preenchidos a partir do perfil oficial. O post recebe a marca de opinião. O selo aparece acima do H1; a assinatura final informa a persona e a responsabilidade da Redação. O nome real do autor WordPress não é trocado por uma conta de pessoa fictícia. Remover o vínculo: enviar slug vazio. O campo de leitura `g73_columnist_profile` entrega o perfil resolvido na resposta REST.

No editor de notícias, o seletor **Coluna / persona editorial** faz o mesmo vínculo. Os perfis têm bio, editoria, cor e seletor nativo da Biblioteca de Mídia para trocar o avatar. Alterações de apresentação são resolvidas pelo cadastro central. A prévia autenticada `GET /wp-json/g73/v1/editorial-preview/ID` permite conferir selo, assinatura e linha de autoria, inclusive em rascunhos editáveis, sem publicá-los.

## Agenda 73

A Home apresenta quatro cards com imagem **16:10**, data, cidade e “Ver programação →”. A imagem compartilhada entre modelos é uma ilustração gerada por IA, identificada no texto da seção e nos metadados, com **600×375 px / 56.660 bytes WebP**. Compartilhar o mesmo placeholder reduz transferências; cada evento pode receber sua própria imagem destacada pelo painel ou `featured_media` na REST.

Os quatro cadastros continuam em **rascunho** e são explicitamente selecionados para demonstração na Home. Cada card informa **“Modelo • Não confirmado”**. O botão abre os detalhes da demonstração, sem simular horários, atrações ou venda de ingressos.

| ID | Modelo | Cidade | Data ilustrativa |
|---|---|---|---|
| 147 | Feira de produtores e economia criativa | Teixeira de Freitas | 29/10/2026 |
| 148 | Encontro cultural regional | Porto Seguro | 05/11/2026 |
| 149 | Programação comunitária e lazer | Prado | 12/11/2026 |
| 180 | Mostra de cultura e turismo | Alcobaça | 19/11/2026 |

Gerenciamento: **Agenda G73**, CPT `g73_event`, REST `/wp-json/wp/v2/g73_event`. Campos: `g73_event_date`, `g73_event_city`, `g73_event_url`, `g73_event_demo`, além de `featured_media`. Somente administradores gerenciam estes registros. Modelos não entram no sitemap e não recebem Schema de evento confirmado.

Em **Configurações → Autoridade Regional G73**, a opção REST `g73_agenda_demo_ids` seleciona até quatro modelos. Enviar `[]` em `/wp-json/wp/v2/settings` remove as demonstrações. Eventos realmente publicados e futuros têm prioridade. O guardião anterior continua impedindo publicar um registro ainda marcado como demonstração.

## Ouvir notícia

O template `single` tem o controle abaixo da barra de autor/WhatsApp. Usa exclusivamente `speechSynthesis` e `SpeechSynthesisUtterance`, `pt-BR`, velocidade **1.05**, com ouvir/pausar/continuar/parar e indicador por trecho. A voz é escolhida entre as vozes de português instaladas no dispositivo. A disponibilidade depende do navegador e das vozes do aparelho.

A leitura só começa após clique. O código divide textos longos em trechos, lê título, linha fina e parágrafos, excluindo anúncios, Leia também, créditos de imagens e componentes de interface. A navegação encerra a leitura. Não há MP3, serviço de TTS, fonte remota ou biblioteca de áudio. O JavaScript é inserido somente nas matérias; fica desativado com mensagem explicativa em navegadores incompatíveis.

## Parceiros e componentes preservados

- As seis campanhas 135, 136, 163–166 continuam ativas nas posições de topo, lateral superior/inferior, miolos 1/2 e entre seções da Home.
- Containers transparentes, sem borda/padding/altura artificial; micro-aba PUBLICIDADE colada à imagem e centralização conferida. Topo: 970 px desktop, 320 px mobile. Laterais: 300×250 e 300×600. Imagens dos miolos alinhadas à coluna de leitura.
- Módulo `popup_modal` / `popup_global` acessível no painel e nos schemas REST, com seus controles anteriores. A chave geral permanece **desligada**, conforme a configuração existente; o recurso está pronto para campanha.
- Grupo WhatsApp na sidebar e no final das matérias, com opção/API `g73_whatsapp_group_url`. Convite ainda vazio por escolha do proprietário; botão preparado e desativado até informar o link.
- Reações e micro-enquetes preservadas, com gravação em meta, proteção contra repetição e barras em CSS. Nenhum voto de teste foi somado aos artigos publicados.
- Impressão mantém marca, título, resumo, autoria/data, foto, texto e URL original; oculta player, banners, navegação, enquetes e botões.

## Implantação e verificação

Fontes: `autoridade-regional.php` (snippet **10**, prioridade **14**, após snippet 9), `leitura-noticia.js`, templates Home/Single e CSS global. O painel legado de quatro espaços de colunistas foi substituído pelo acesso ao novo gerenciador; a opção antiga permanece apenas para compatibilidade.

- Sintaxe PHP e JavaScript conferidas. Fonte implantada comparada com o repositório.
- REST confirmou os seis perfis e todos os metadados. Um rascunho privado validou vínculo de Arthur e Clara, preenchimento dos campos e HTML do selo/byline/assinatura; slug desconhecido retornou **400**. O rascunho de teste foi removido.
- Testes de reações, resultados de enquetes, bloqueio após recarga, rotação, empilhamento e controles de frequência/fechamento do modal passaram.
- Testes de voz com engine simulado confirmaram ausência de autoplay, idioma/velocidade, exclusão de publicidade/Leia também e pausa/retomada/parada. Não equivalem a teste em aparelho físico.
- Navegação real da Home e de uma matéria em **390 e 1440 px**: HTTP 200, imagens carregadas, seis personas, quatro modelos, banners justos, sem overflow e sem erros JavaScript. Conteúdo principal e vitrines também renderizados sem JavaScript.
- PDF A4 gerado e inspecionado, com fonte original no rodapé e sem controles comerciais/de interação.

Os avatares e a imagem da vitrine carregam abaixo da dobra com dimensões reservadas e lazy loading. Não houve nova medição de PageSpeed; nenhuma nota ou prazo de indexação é garantido por esta implantação.

Purga final do LiteSpeed confirmada via Easy MCP, escopo `site`, camada `litespeed-cache`, status `cleared`. A validação final usa as URLs canônicas sem parâmetro de bypass após a purga.
