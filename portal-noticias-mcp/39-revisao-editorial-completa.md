# Revisão editorial do acervo — 26/10/2026

Foram revisadas 33 matérias: 31 existentes no início da execução e duas publicadas durante o trabalho (433 e 435). Ao final da verificação, 30 estavam publicadas e três em rascunho para apuração. A relação está em [39-materias-revisadas.csv](39-materias-revisadas.csv).

## Alterações aplicadas

- Vinte textos receberam revisão substancial para retirar linguagem promocional, resultados sem demonstração, aspas não conferidas e confusão entre anúncio, realização e situação atual.
- Os dez guias e colunas preservados receberam links relacionados e revisão das referências aos cinco artigos retirados anteriormente. A responsabilidade editorial institucional foi mantida.
- Cada uma das 30 matérias públicas tem duas sugestões de leitura escolhidas entre URLs publicadas e pertinentes. Nas matérias com capa, uma figura gerenciada permanece no conteúdo para o editor; o template continua exibindo a mesma capa apenas uma vez no site.
- Foram conferidos oito campos Rank Math por matéria: palavra-chave, título, descrição, categoria principal e títulos/descrições sociais. Não foi fabricada ou forçada pontuação SEO.
- Eleições, Legislativo, expediente e contas públicas foram organizados em Política. O Fies foi incluído em Dinheiro. As demais editorias e os vínculos das colunas foram preservados.
- Datas originais de publicação e os endereços das matérias públicas foram conservados. Mudanças de conteúdo geraram atualização real; correções relevantes têm nota datada no texto.
- A capa do artigo 435 foi retirada: mostrava um logotipo do CAPREMI, sem correspondência com o guia da Câmara de Itabela. O texto ficou sem capa até haver uma imagem pertinente e autorizada. Não foi criada uma foto de substituição com local ou identidade inventados.
- No artigo 319 foram retirados endereço completo e detalhes íntimos/hipóteses não confirmadas sobre a criança. Não foi antecipado resultado de perícia.

As três matérias em rascunho são **336, 341 e 346**, sobre áudio atribuído a vereadora, alegações de cobranças na Basevi e um relatório ambiental. As referências contêm datas ambíguas ou históricas e acusações que exigem conferência documental e tratamento das manifestações dos envolvidos. Os textos e os arquivos anteriores foram preservados; não houve exclusão definitiva. Seus slugs continuam disponíveis para eventual republicação após revisão.

## Limites da revisão

Esta é uma revisão editorial do material existente e de suas referências, não uma nova apuração independente. As tentativas de consultar fontes oficiais e Agência Brasil foram bloqueadas pelo proxy HTTP 403. Não foi afirmada leitura externa que não ocorreu. Textos históricos foram delimitados, informações de serviço sem sustentação específica foram retiradas e os casos sensíveis ficaram fora da publicação.

Crédito genérico não comprova licença de fotografia. Confirmar direitos e obter imagens maiores autorizadas continua pendente, assim como a configuração efetiva de medição/consentimento. As mudanças não constituem aprovação AdSense, garantia de Discover ou promessa de nota verde Rank Math.

## Proteções para as próximas entradas

O Snippet 12 foi ajustado para reconhecer o rótulo de atribuição “Referência:” e manter a comparação de fontes recentes mesmo quando uma matéria muda de editoria. A busca legada continua limitada aos 60 posts mais recentes dos últimos sete dias; metadados de origem existentes são consultados antes desse limite. Similaridade encaminha a revisão, sem apagar textos de mesmo tema automaticamente. Teste PHP cobre a mudança de rótulo e de categoria.

No coletor RSS, a geração recebe a data de referência e instruções adicionais de contexto temporal, atribuição, cuidado com crianças e retirada de enchimento promocional. Referência genérica, data completa antiga/futura na URL, datas inválidas, sinais de acusações sensíveis, algumas expressões promocionais e imagens verificadas com um lado menor que 400 px passam para rascunho, inclusive em fontes autorizadas para modo automático.

Esses sinais não são um verificador universal de fatos, direitos ou adequação visual. O upload PHP continua aceitando imagens pequenas válidas conforme sua regra existente; o novo critério decide apenas sobre publicação automática no coletor.

**Implantação:** o Snippet 12 foi atualizado no WordPress. As mudanças Python estão no GitHub e exigem a atualização da VPS pelo responsável. Não alteram payloads já preparados nem o bot Telegram e outros publicadores. O atualizador existente aceita o commit desta entrega e preserva `.env`, configuração local e banco; sua execução final pode processar um item real conforme a política autorizada.

## Liberar o GA4 no Easy MCP

1. Entre em [Google Analytics](https://analytics.google.com/) com uma conta que administre a propriedade do Giro73 e selecione essa propriedade.
2. Abra **Administrador → Gerenciamento de acesso à propriedade**.
3. No WordPress, abra **Easy MCP AI → Tools → External data → Google Analytics 4** e copie o email da conta de serviço exibida em **Service account key**.
4. No Analytics, use **+ → Adicionar usuários**, cole esse email e conceda a função **Leitor / Viewer**. Confirme a inclusão.
5. Confira o ID numérico em **Detalhes da propriedade** e use o mesmo ID no campo **Default property ID** do Easy MCP.
6. Salve no Easy MCP e clique em **Test connection**. Depois valide um relatório de sessões/páginas e o fluxo web relacionado ao domínio.

O erro atual do Google é de acesso negado à propriedade. Conceder leitura libera a consulta; o recebimento efetivo de eventos ainda deve ser conferido. O Search Console tem permissões próprias e já funciona.

## Validação e prévia

As verificações estão em [validacao-revisao-editorial.json](implementacao-wp/validacao-revisao-editorial.json): conteúdo e metadados salvos, datas e slugs, HTTP público, canonical, links relacionados, ausência da capa repetida e sitemaps. Passaram 93 testes Python e 33 verificações PHP offline. As chamadas simuladas desses testes não criaram notícias reais.

Ao concluir, o Purge All do LiteSpeed e a limpeza do sitemap Rank Math foram confirmados. O Snippet 12 permaneceu ativo, sem erro, com código correspondente ao arquivo versionado. O relatório HTML foi renderizado em PDF e conferido em largura mobile, sem rolagem horizontal da página.

O relatório também está em [HTML para abrir no navegador](relatorio-auditoria-giro73.html). A prévia foi gerada novamente a partir desse arquivo, sem depender da antiga visualização Markdown.
