# Espaços publicitários para parceiros locais

> Histórico: a implantação 17 removeu a sidebar dos artigos e manteve o espaço lateral apenas na Home. A inserção no terceiro parágrafo passou à rotina editorial do snippet 7.

Implantado em 22/10/2026 no Giro73, usando o Code Snippets existente, snippet global **6**, sem instalar outro plugin.

- Slot lateral na Home, imediatamente depois de Mais lidas e antes de Economia.
- Sidebar adicionada ao single, com a lista nativa de cinco matérias e o slot lateral logo abaixo.
- Slot no miolo após o terceiro parágrafo editorial, renderizado automaticamente no servidor. Crédito da foto não entra na contagem. O conteúdo armazenado dos artigos permanece intacto.
- Slots com fundo cinza sutil, borda leve, raio de 8 px, rótulo PUBLICIDADE e área reservada mesmo vazios.
- E-mail comercial confirmado pelo usuário como contato dos espaços disponíveis, sem telefone inventado.
- Gestão em Configurações → Publicidade Giro73: mídia WebP/PNG, versão mobile opcional, URL, nome do anunciante e formato lateral 300×250/300×600.
- Links de anunciantes marcados como sponsored; nenhuma chamada de rede publicitária ou script de rastreamento.

Arquivos em `implementacao-wp`: `publicidade-parceiros.php`, `slots-publicitarios.html`, `README-publicidade.md`, CSS global e templates Home/single atualizados. Guia de uso e detalhes dos formatos no README.

## Validação concluída

- Home e cinco artigos HTTP 200. Um slot lateral por página e um slot de miolo por artigo, sem duplicações.
- Nos cinco artigos, o slot está depois de exatamente três parágrafos editoriais, com um parágrafo completo imediatamente antes. Crédito da fotografia excluído da contagem.
- Chromium em 360, 390, 800, 1024 e 1440 px: sem rolagem horizontal. Sidebar com largura máxima de 300 px, área criativa de 250 px e opção de 600 px conferidas; miolo com área de 90 px no desktop e 100 px no mobile.
- Estilo computado: fundo, raio de 8 px, rótulo PUBLICIDADE e mailto comercial conferidos. Inspeção visual de artigo desktop/mobile e Home; nenhum erro JavaScript ou recurso com falha.
- Opção de gestão exposta na Settings API com estrutura e valores padrão corretos. Snippet 6 ativo, sem code_error, e código exportado igual ao arquivo local.
- Comparação REST antes/depois dos cinco posts: conteúdo, título, resumo, slug, categoria, datas, modified, status e featured_media intactos. A inserção acontece só na renderização do single.
- CSS e sintaxe PHP 7.4+ validados. Nenhuma campanha ou banner fictício foi publicado; os espaços permanecem disponíveis até o administrador selecionar peças reais.
- LiteSpeed Purge All confirmado; invalidação dirigida da Home e dos cinco artigos após preservar o HTML do shortcode sem wpautop. URLs canônicas verificadas depois da limpeza.

Os testes de geometria não equivalem a nota PageSpeed; o peso e as dimensões das peças comerciais futuras devem respeitar o guia para manter a performance.
