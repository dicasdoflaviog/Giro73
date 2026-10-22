# Publicidade de parceiros Giro73

Publicado pelo snippet PHP global **6**, no Code Snippets já instalado. Arquivo fonte: `publicidade-parceiros.php`. Não alterar a rotina de cotações, snippet 5.

## Gestão pelo WordPress

Acesse **Configurações → Publicidade Giro73**:

https://giro73.com.br/wp-admin/options-general.php?page=giro73-publicidade

1. Escolha a posição: **Sidebar — Home e artigos** ou **Miolo — após o terceiro parágrafo**.
2. Clique em **Selecionar na biblioteca** e envie/escolha um WebP ou PNG. Prefira arquivos até 100 KB.
3. Informe **URL de destino** e **Nome do anunciante**. A URL pode ser o site HTTPS do parceiro ou um link `https://wa.me/55DDDNUMERO?text=MENSAGEM_CODIFICADA`. Use somente número real fornecido pelo parceiro; o sistema não envia mensagens automaticamente.
4. Opcionalmente escolha uma imagem mobile. Clique em **Salvar publicidade**. O salvamento invalida o cache LiteSpeed.
5. Para liberar o espaço, remova as duas imagens e salve. Os arquivos permanecem na biblioteca.

Sem banner, o espaço mostra **Espaço disponível / Anuncie aqui / comercial@giro73.com.br**, com link mailto e assunto pronto. O usuário confirmou o uso do e-mail; nenhum número fictício foi publicado. O e-mail pode ser atualizado no mesmo painel.

Cada posição compartilha uma configuração global. O banner lateral é o mesmo na Home e nos artigos; o banner de miolo aparece automaticamente em todas as notícias. Alterar a campanha não exige editar cada post.

## Formatos e apresentação

| Posição | Área da imagem | Local |
|---|---|---|
| `.ad-slot-sidebar` | 300×250; opção 300×600 | Abaixo de Mais lidas na Home e no single |
| `.ad-slot-article` | Horizontal, recomendado 728×90; mobile 320×100 | Após o terceiro parágrafo editorial |

O rótulo **PUBLICIDADE** fica acima da imagem, com 28 px reservados; bordas acrescentam 2 px. A imagem lateral reduz a largura quando a coluna tem menos de 300 px, mantendo a altura reservada. O banner horizontal usa largura fluida e `object-fit: contain`, sem distorcer nem cortar a peça. Em mobile, a sidebar fica depois do artigo; o slot de miolo permanece no texto.

Fundo `#F4F5F7`, borda `#E2E8F0`, raio 8 px e rótulo de 10 px na cor `#9CA3AF`. Sem scripts de publicidade, iframe, rastreamento, animação, rotação ou plugin adicional. JavaScript da biblioteca de mídia carrega apenas na página de gestão; o visitante recebe HTML renderizado no servidor e CSS.

Imagens têm dimensões nativas do WordPress, lazy loading e decoding async. Links de anunciantes usam `rel="sponsored noopener noreferrer"`. A altura da área de anúncio existe mesmo sem imagem, sem expansão assíncrona para receber um banner. Uma mudança administrativa entre formatos 300×250 e 300×600 altera a altura na próxima navegação, não durante a leitura.

## Implementação

- Home e single usam o bloco nativo Shortcode: `[giro73_publicidade posicao="sidebar"]`.
- A rotina corrige o wpautop apenas desse shortcode para preservar o HTML dos slots.
- O filtro `render_block_core/post-content` insere o slot de miolo depois do terceiro parágrafo editorial no post principal. Ignora crédito da foto, parágrafos de metadados, citações, listas, figuras, tabelas e outros contextos de apoio. Não altera o banco de conteúdo.
- Se uma notícia tiver menos de três parágrafos, coloca o slot ao final do texto; nunca quebra um parágrafo.
- A presença de um slot de artigo existente evita duplicação automática.
- Página institucional, excerpt, feed RSS, resultado de busca e chamadas da Home não recebem anúncios de miolo.
- Artigos ganharam wrapper em grid de duas colunas no desktop, preservando largura máxima de leitura de 740 px. No mobile, coluna única.
- Campos dedicados são guardados na opção `g73_partner_ads`, também acessível a administradores pelo campo correspondente em `/wp-json/wp/v2/settings`. Settings API protege o formulário com nonce e `manage_options`; mídia aceita apenas WebP/PNG e destinos HTTP/HTTPS.

`slots-publicitarios.html` contém exemplos de blocos Shortcode. Não adicionar uma segunda cópia às posições que já são automáticas. Para HTML personalizado em outra área, reutilizar as classes e a estrutura retornada, preservando rótulo, dimensões e atributo sponsored; os campos do painel só gerenciam os slots renderizados pelo shortcode/rotina.

## Manutenção e rollback

Para retirar uma campanha, remover as imagens no painel, sem desativar o sistema. Para remover todos os slots, desativar apenas o snippet **6**, restaurar os templates anteriores do Git e limpar o LiteSpeed. O snippet de cotações **5** permanece ativo. Não carregar uma segunda cópia do PHP no tema enquanto o snippet estiver ativo.

Não há mensuração de impressões/cliques ou rodízio de campanhas nesta estrutura. Esses recursos só devem ser acrescentados quando houver necessidade e orçamento de performance definido.
