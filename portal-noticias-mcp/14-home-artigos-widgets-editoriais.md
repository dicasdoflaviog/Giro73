# Home, topo e artigos: pacote editorial

Aplicado em **22/10/2026**, no WordPress existente. Não foram recriados artigos, páginas ou mídias. Os cinco posts mantiveram integralmente conteúdo, título, resumo, slug, categoria, status, datas e imagem destacada, conferidos pela API antes/depois.

## Publicado

- Topbar branca de 36 px, cinco atalhos coloridos em lowercase e data/hora em Brasília com região. Em telas abaixo de 1024 px, data/região são ocultadas para manter a navegação compacta.
- Header ouro de 58 px, grid `1fr auto 1fr`, logo oficial **G73-BRANCO-SELO**, mídia 60, altura de 34 px, no centro absoluto. Menu lateral branco com as cinco editorias e cinco páginas institucionais; busca nativa WordPress por formulário GET.
- Rodapé claro `#F8F9FA`, borda ouro de 3 px, logo compacta preta oficial, mídia 61, altura de 28 px; cinco links institucionais e expediente.
- Home preserva consultas automáticas: manchete, dois destaques e feed com offset 3. Fotos reais licenciadas mantidas; sem sombras ou cartões em volta de cada notícia.
- Sidebar com exatamente três cards: previsão, cinco matérias numeradas e economia. A ordenação de “Mais lidas” continua identificada como **Seleção editorial**, pois não há fonte de visualizações configurada.
- Artigo com container de 740 px, H1 de 38 px, resumo de 19 px, assinatura local, data/hora nativa e link de compartilhamento WhatsApp com título e URL canônica. Imagem 16:9 e crédito factual; corpo de 19 px/1,68 e H2 com borda de 4 px na cor da editoria.

## Fontes e comportamento dos widgets

| Informação | Fonte e estado verificado |
|---|---|
| Clima | Open-Meteo HTTP 200; dados de hoje renderizados para Teixeira de Freitas, Porto Seguro, Eunápolis e Itamaraju. Máxima/mínima de cada período calculada a partir das horas 06–11, 12–17 e 18–23. Ícone representa a condição predominante. |
| Link da previsão | https://www.climatempo.com.br/, endereço confirmado pelo usuário; não foi criada nova página. |
| Dólar e euro | AwesomeAPI HTTP 200, CORS `*`, bid em reais com quatro casas, variação percentual e horário das moedas informado pela fonte. |
| Ibovespa | HG Brasil HTTP 200 com pontos e variação, mas sem `Access-Control-Allow-Origin` para a origem Giro73 na consulta sem chave. Um navegador normal bloqueia essa leitura. A linha conserva traços e estado de indisponibilidade; precisa de fonte com CORS autorizado ou integração de servidor para atualização efetiva. Não publicar o índice como reais nem inventar cotação. |

Os bloqueios de rede iniciais do ambiente foram superados durante a execução: as três APIs passaram a responder HTTP 200. A configuração de domínios foi salva no rascunho do ambiente; salvar rascunho não equivale a publicar configuração. Não atribuir a conectividade observada à aplicação automática do rascunho.

Cache público local no navegador: clima por 15 minutos, moedas/índice por 5 minutos. Requisições depois do load e perto da viewport, timeout de 6,5 segundos, sem cookies/credenciais, localização pessoal ou biblioteca externa. Respostas ausentes, inválidas ou de outro dia não viram dados de previsão. Campos e linhas têm espaço reservado. Cotações não recebem números estáticos de demonstração.

## Verificação

- Home e cinco artigos públicos HTTP 200; Home e artigo regional com cache LiteSpeed `hit` na conferência final.
- Chromium em **360, 390, 800, 1024 e 1440 px**: sem overflow horizontal; header 36/58 px, logo central de 34 px e logo do footer de 28 px conferidos por medidas computadas.
- Menu: dez links, abertura em dialog nativo, foco contido, Escape e retorno do foco. Busca abre/foca/fecha e encontra o artigo de inteligência artificial no WordPress.
- Artigo regional: largura, tipografia, borda colorida dos H2, crédito da fotografia, data em português e URL canônica no botão WhatsApp conferidos. Nenhuma mensagem enviada.
- Clima e moedas renderizados com respostas reais obtidas por HTTPS verificado. A ponte HTTPS de teste do Chromium foi ajustada para preservar a restrição CORS da HG Brasil; o resultado econômico é parcial, com moedas e fallback seguro do índice.
- Testes com fixtures separados das respostas reais verificaram as quatro coordenadas, períodos, moeda positiva/negativa, índice em pontos, respostas inválidas e ausência de valores fictícios. Os números de fixtures não foram publicados nem salvos nos templates.
- Sem erros JavaScript. O WordPress convertia um operador de um ramo do script em entidade HTML; a expressão foi reescrita e o HTML público foi retestado.
- CLS observado no cenário com APIs indisponíveis: **0**. Com dados reais e seletores de cidades: aproximadamente **0,0000122**, em laboratório. Não é medição de campo nem nota PageSpeed. Nenhuma promessa de 95+ decorre deste teste.
- CSS validado, scripts próprios sem dependências, fontes de sistema, fotos anteriores preservadas. JS próprio legível: cerca de 11,5 KB no total; o script de compartilhamento só está no artigo e os widgets só na Home.
- LiteSpeed **Purge All** confirmado; Home invalidada novamente após a correção do script. Requisições canônicas verificadas sem parâmetros para burlar cache.

Fontes publicadas: `implementacao-wp/header-editorial.html`, `footer-editorial.html`, `home-grade-automatica.html`, `single-editorial.html` e `css-g1-techtudo-ultra-leve.css`. Os scripts separados são a referência legível dos trechos inline. Exportação REST de single usa escape JSON `\u005c`, equivalente à barra da máscara de data do WordPress.
