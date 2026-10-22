# Parceiros G73 — gerenciador nativo

Implantado em **22/10/2026**, sem instalar plugins. Substitui o painel antigo do snippet 6; o snippet 7 continua responsável pelas inserções editoriais. Guia completo e exemplos REST/MCP: [README-publicidade.md](implementacao-wp/README-publicidade.md).

## Entrega

- CPT privado `g73_partner`, menu **Parceiros G73**, formulário com biblioteca de mídia nativa, título, imagens desktop/mobile, destino, status, agenda, sete formatos e cinco posições. Metadados registrados para REST. Administração restrita a administradores.
- Rotação por visita e a cada oito segundos; modo empilhado na sidebar. Até dez peças candidatas por posição. Links patrocinados, imagens inteiras e estruturas responsivas com reserva de dimensões.
- Modal nativo com chave geral, tempo/rolagem, frequência diária/sessão/sempre, escopo, auto-fechar, fechamento por botão/backdrop/ESC, gerenciamento de foco e rodízio por visita. Inicialmente **desligado**, sem campanha real de modal.
- Migração idempotente de dois banners para campanhas **135** e **136**. Mídias desktop/mobile, destinos e contato originais preservados. Formatos identificados pelas dimensões reais para não encaixar uma faixa horizontal num espaço vertical de 600 px.
- Classes neutras em todo HTML/CSS ativo: `g73-partner-unit`, `g73-vitrine-box`, `g73-media-frame`, `g73-overlay-card`. PUBLICIDADE continua explícito. Não se promete imunidade a bloqueadores.
- Topo nos seis templates editoriais; sidebar compartilhada; inserções após parágrafos 3 e 6; posição adicional no feed da Home. Conteúdo dos posts permanece intacto; single continua sem sidebar.
- Uma cópia de JavaScript puro, **5.927 bytes / 2.422 bytes gzip**, e uma chamada local pequena por navegação. Endpoint sem cache de resposta e elegibilidade verificada por datas no navegador. Expiração independe de WP-Cron. Alterações de campanha/configuração disparam purga LiteSpeed uma vez ao final da requisição.

## Validação

- PHP analisado sintaticamente; JavaScript verificado e executado em Chromium real.
- Teste isolado reproduzível em `implementacao-wp/tests/parceiros-browser.py`: rotação em 390/1440 px sem mudança de altura, links patrocinados, empilhamento, início/expiração, frequência diária e por sessão, escopo, rolagem, rodízio do modal, auto-fechar, ESC, botão, clique no fundo e foco.
- API nativa: metadados persistidos, schema dos sete formatos/cinco posições, período invertido e mídia inválida recusados com HTTP 400. Campanha futura entregue como candidata; campanha expirada ou pausada excluída do endpoint público. Administração anônima recusada com HTTP 401.
- Easy MCP: criação, edição/pausa e exclusão de campanhas conferidas com `wp_*_cpt_item`. Registros temporários de teste excluídos; permaneceram apenas as duas campanhas migradas.
- Home e single abertos em 390/1440 px: banners carregaram, arte mobile correta, sem rolagem horizontal, uma cópia do runtime por página e nenhum erro de JavaScript. Um filtro cosmético de teste para as antigas classes não ocultou as novas unidades. Não equivale a teste de todas as listas uBlock/Brave.
- Fonte do snippet publicado comparada com a versão local, sem erros registrados. Cinco posts conferidos antes/depois, sem mudanças no conteúdo, títulos, datas, imagens, metadados ou status.
- Capturas mobile/desktop revisadas. Sem nova medição PageSpeed ou promessa de nota 95; sem promessa de que um intersticial seja neutro para SEO.

- Configuração global salva pela REST com chave geral desligada e valores padrão preservados. Purga final confirmada pelo Easy MCP: LiteSpeed `cleared`, escopo `site`, cabeçalho `X-LiteSpeed-Purge`, sem camadas pendentes. Isso inclui o cache das variantes desktop/mobile; Rank Math/Elementor não foram selecionados.
- Após a purga, Home canônica conferida sem parâmetros com user agents desktop/mobile, HTTP 200 e somente as classes novas. Sitemap index e três mapas permanecem HTTP 200 e sem URLs de campanhas; categorias, busca e ads.txt continuam acessíveis.

## Manutenção

Campanhas: `/wp-admin/edit.php?post_type=g73_partner`. Configurações: `/wp-admin/edit.php?post_type=g73_partner&page=g73-partners-settings`. REST de gestão: `/wp-json/wp/v2/g73_partner`; configuração global: `g73_partners_config` em `/wp-json/wp/v2/settings`. Nenhuma credencial foi versionada.
