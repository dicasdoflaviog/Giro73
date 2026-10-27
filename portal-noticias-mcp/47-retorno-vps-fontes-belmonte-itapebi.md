# Retorno da VPS — Belmonte e Itapebi

Log fornecido pelo responsável em 27/10/2026, após a instalação de `3dce26e` e repetição do lote de duas cidades.

| Fonte | Resultado registrado |
|---|---|
| Prefeitura de Belmonte | Caminhos retornaram 404 ou rejeição de DTD; nenhum RSS validado |
| Câmara de Belmonte | `https://camarabelmonte.ba.gov.br/feed/` reconhecido com zero entradas; cadastrado |
| Prefeitura de Itapebi | Caminhos HTTPS com/sem www retornaram 404, 403 ou rejeição de DTD; nenhum RSS validado |
| Câmara de Itapebi | Caminhos HTTPS com/sem www retornaram 404, 403 ou rejeição de DTD; nenhum RSS validado |

O resumo foi **uma fonte adicionada e 17 fontes cadastradas**, com três pendentes. Isso descreve a configuração, não uma validação de funcionamento de todos os 17 feeds existentes. O procedimento salvou backup e não gerou textos nem publicou notícias.

O antigo erro genérico de URL associado ao esquema HTTP desapareceu desse log: o código passou a seguir as tentativas em HTTPS. Os erros atuais são resultados dos caminhos consultados, não a mesma falha anterior de esquema. Não se conclui que os sites inteiros estejam indisponíveis nem que não exista outro RSS em caminho desconhecido.

Rejeição de DTD pode significar HTML de fallback ou XML com declaração de tipo. Não é evidência suficiente para remover a proteção de entidades ou aceitar a resposta como notícia. Zero entradas no feed da Câmara de Belmonte não significa zero notícias no site inteiro.

Foi preparado `automacao-rss/diagnosticar_fontes_html.py` para levantar os canais dessas três fontes na VPS, sem credenciais ou publicação. Ele consulta no máximo duas páginas por site, mostra metadados e links candidatos e salva JSON privado. Não há adaptador HTML ativo nesta etapa: sua implementação depende de observar estrutura, link de matéria, data, texto e origem da imagem, sem inventar seletores ou fatos.

A sintaxe, a ajuda e uma inspeção de HTML local de exemplo foram verificadas. As páginas municipais ainda não foram lidas daqui por impedimento do proxy; o diagnóstico real depende da execução na VPS. Nenhum cron, segredo, política editorial ou conteúdo WordPress foi modificado nesta etapa.
