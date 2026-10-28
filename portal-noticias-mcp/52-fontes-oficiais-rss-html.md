# Monitoramento oficial: RSS primeiro, HTML como alternativa

Implementação de 28/10/2026 para os 22 órgãos dos 11 municípios reafirmados pelo responsável: Medeiros Neto, Ibirapuã, Lajedão, Vereda, Itamaraju, Alcobaça, Caravelas, Nova Viçosa, Mucuri, Porto Seguro e Eunápolis. Os endereços oficiais usam HTTPS e www; a identidade compara também o endereço sem www.

## Funcionamento

1. `configurar_fontes_rss.py --official-government` seleciona exatamente esses 22 órgãos. O catálogo anterior de 19 municípios permanece disponível com `--only-government` e `--city`.
2. Procura links RSS/Atom declarados e tenta `/feed/`, `/feed`, `/rss`, `/rss.xml`, `atom.xml` e outros caminhos existentes. Reserva parte do tempo para HTML quando os feeds não forem reconhecidos.
3. Sem RSS válido, verifica `/noticias`, `/site/noticias` e a Home. O BeautifulSoup extrai links de matérias da mesma origem e abre as páginas individuais.
4. Para cada matéria, exige título compatível, corpo com parágrafos e data original explícita. Usa `datePublished`, `article:published_time`, marcação equivalente ou data completa em elemento de publicação. Não usa `dateModified`, atualização global, rodapé, data parcial ou horário de captura.
5. Reaproveita o processamento existente: janela de frescor, filtro de referências históricas, fila SQLite, rodízio, revisão institucional, SEO, capas e idempotência.
6. A alternativa HTML também pode ser usada em um ciclo no qual o RSS de uma fonte municipal cadastrada falhar. Ela usa a mesma fonte/ID, sem criar outro cadastro.

## Sem duplicação

O cadastro compara domínio de `url` e `site_url`, município/órgão e nome existente. Preserva o ID quando altera o transporte. Se já encontrar dois cadastros concorrentes, avisa e não cria um terceiro nem apaga os anteriores. A fila consulta os aliases antigos de URL e acrescenta variantes com/sem www e barra final, preservando os itens anteriores.

Na seleção oficial, as fontes validadas são autorizadas para monitoramento, com modo automático condicionado às verificações por matéria, à revisão de atas/editais e à coerência SEO. Capas temáticas aprovadas são aproveitadas quando disponíveis. Uma listagem reconhecida mas vazia pode permanecer monitorada; isso não gera notícia artificialmente.

## Limites importantes

- HTML estático, sem executar JavaScript ou iframes externos. Portais que entreguem apenas iframe/JS são informados como pendentes; não são apresentados como funcionando.
- Sem redirecionamento HTML para outro domínio; www e ausência de www são equivalentes. RSS declarado pode continuar usando o leitor RSS existente.
- Até 2 MB por página, até oito páginas de matérias por consulta HTML e orçamento limitado por fonte. Sem paginação histórica indiscriminada.
- Não transforma notícia de 2020, janeiro ou agosto em notícia atual. A existência de conteúdo na Home não garante conteúdo dentro da janela de 72 horas.
- Falha de validação não apaga nem desautoriza automaticamente uma fonte já existente.

## O que foi consultado nesta sessão

Foram tentados `/feed`, `/rss` e `/rss.xml` nos 22 domínios pelo leitor web: os 66 resultados não permitiram validar XML. Isso não comprova ausência de RSS nem representa HTTP 404 do servidor municipal. O executor de desenvolvimento tem política de rede que não inclui esses municípios e não há SSH da VPS disponível.

As Homes também foram consultadas pelo leitor web. Houve conteúdo legível em portais SAI, na Câmara de Medeiros Neto e na Câmara de Alcobaça; outros retornaram erro/timeout ou conteúdo vazio. Exemplos:

| Órgão | Observação pública desta sessão | Consequência |
|---|---|---|
| Prefeitura de Medeiros Neto | Portal SAI, links de notícias na Home | Tentar leitura HTML de cada matéria e conferir data original |
| Câmara de Medeiros Neto | Home com notícias e datas completas | Tentar RSS e, se necessário, HTML |
| Câmara de Ibirapuã | Resposta contém iframe para outro domínio | Pendência; o parser não seguirá iframe externo |
| Câmara de Lajedão | Portal SAI com cartões de conteúdo | Não presumir ano a partir de “jan 01” |
| Prefeitura de Vereda | Página indica dependência de JavaScript | Pendência se as rotas de notícias não entregarem HTML estático |
| Câmara de Vereda | Portal SAI com cartões de conteúdo | Conferir data completa na matéria |
| Câmara de Itamaraju | Portal SAI com notícia na Home | Conferir data completa e frescor |
| Câmara de Alcobaça | Amostra com notícia datada de 2023 | Monitorar novos itens; não importar o histórico como atual |
| Prefeitura de Nova Viçosa | Portal SAI com listagem na Home | Conferir data original antes de enfileirar |
| Demais órgãos | Consulta não forneceu conteúdo conclusivo | Validação efetiva continua pendente na VPS |

Referências públicas: [Prefeitura de Medeiros Neto](https://www.medeirosneto.ba.gov.br/), [Câmara de Medeiros Neto](https://www.camaramedeirosneto.ba.gov.br/), [Câmara de Alcobaça](https://www.camaraalcobaca.ba.gov.br/). Essas observações não equivalem à execução do parser em produção.

## Instalação

O instalador `expandir-fontes-oficiais.sh COMMIT` atualiza coletor, configurador e catálogo de capas, faz backup e verifica por padrão exatamente a lista oficial de 22 órgãos. Preserva o `.env`, a fila e o cron. Ele não gera textos nem publica posts durante a instalação. Se receber `--city`, mantém o modo anterior de seleção por cidade.

A verificação pode concluir parcialmente: `FONTE VALIDADA` / `JÁ CADASTRADA / VALIDADA` identificam transporte e ID; `MONITORAMENTO SEM ENTRADAS` indica ausência de candidatos; `PENDENTE` indica que a fonte não foi validada. A lista real ativa depende desses logs e do JSON da VPS.

## Verificação local

137 testes Python passaram, com redes e APIs simuladas, incluindo os casos novos de extração, data, segurança de origem, fonte vazia, falha RSS com alternativa HTML, preservação de configuração e não duplicação. Sintaxe dos scripts Bash conferida. Nenhum post de teste foi criado e nenhum acesso à VPS foi realizado.
