# Sidebar: onze cidades e cotações no servidor

Aplicado em **22/10/2026** no WordPress existente. Home atualizada; artigos, páginas, fotografias, header, footer e CSS preservados.

## Clima

Onze opções na ordem pedida, com Teixeira de Freitas como padrão. A seleção muda os parâmetros latitude/longitude enviados ao Open-Meteo. Coordenadas são pontos de referência das sedes, não limites municipais ou geolocalização do visitante.

| Cidade | Latitude | Longitude |
|---|---:|---:|
| Teixeira de Freitas | -17.535 | -39.7419 |
| Porto Seguro | -16.4497 | -39.0647 |
| Eunápolis | -16.377 | -39.5807 |
| Itamaraju | -17.0392 | -39.5312 |
| Prado | -17.3364 | -39.2227 |
| Mucuri | -18.0754 | -39.5565 |
| Caravelas | -17.7268 | -39.2597 |
| Medeiros Neto | -17.3707 | -40.2238 |
| Nova Viçosa | -17.8926 | -39.3743 |
| Itanhém | -17.1642 | -40.3321 |
| Alcobaça | -17.5195 | -39.2036 |

Os quatro pontos existentes foram preservados; os sete novos foram conferidos no dataset público de municípios brasileiros, filtrando UF 29 e DDD 73. Referência e lista reutilizável em `implementacao-wp/cidades-clima-extremo-sul.json`. O geocoder adicional estava bloqueado na rede; a consulta meteorológica usa coordenadas fixas e não depende dele.

## Cotações locais

Rotina em `implementacao-wp/cotacoes-servidor.php`, publicada pelo **Code Snippets 4.0.0**, snippet global **5**. Instalação pela REST nativa do WordPress. As ferramentas atuais não escrevem PHP do tema; o snippet evita alterar o tema principal e não carrega assets extras no frontend.

O navegador consulta somente **`https://giro73.com.br/wp-json/giro73/v1/cotacoes`**. O servidor usa `wp_remote_get` para buscar moedas na AwesomeAPI e Ibovespa na HG Brasil. Se moedas não vierem da primeira fonte, usa valores reais de compra e variação da HG Brasil, identificados no JSON. CORS de APIs financeiras deixa de participar da consulta do widget.

Resultado completo em transient **`g73_market_v2` por 900 segundos**, com fetched_at e expires_at. Falhas/parciais usam cache de 60 segundos para retentar. Trava de atualização evita consultas frias concorrentes; URLs fixas, TLS padrão, timeout de 4 segundos por fonte e limite de 64 KB por resposta. Endpoint apenas de leitura, sem URL arbitrária de usuário. Nenhum segredo publicado.

Campos recebidos são validados antes de mostrar valor/variação. Ibovespa em **pontos**, dólar/euro em **reais**; variações positivas/negativas/zero preservadas. O horário de consulta do servidor é identificado como tal, sem inventar horário de atualização do índice. Não foram inseridos valores estáticos na Home.

O widget continua carregado depois do load e perto da viewport. Cache financeiro anterior do navegador foi removido do fluxo; o servidor controla o vencimento. A interface trata falha/503 com tentativa curta adicional e mantém indicação de indisponibilidade quando necessário.

## Validação e operação

Duas consultas consecutivas ao endpoint retornaram HTTP 200 com os três indicadores, status ready, cache_ttl **900**, fetched_at idêntico e vencimento exatamente quinze minutos depois. A AwesomeAPI falhou na consulta do servidor; a alternativa HG Brasil forneceu os três indicadores. Ver relatório de validação pública e conferência final desta implantação no histórico do commit.

Código PHP analisado para sintaxe PHP 7.4+, snippet ativo sem code_error, JavaScript validado. LiteSpeed limpo após salvar a Home. Para rollback, desativar apenas o snippet 5; instruções de migração e verificação em `implementacao-wp/README-interface.md`.

### Conferência pública final

- As onze opções foram selecionadas no Chromium, cada uma gerando requisição com a latitude/longitude do manifesto. Todas retornaram HTTP 200, dados de hoje e estado ready; a grade retornada pelo Open-Meteo ficou próxima do ponto municipal informado.
- O endpoint anônimo local foi chamado pelo widget e os três valores foram renderizados. Nenhuma requisição do navegador para os hosts financeiros foi observada.
- Larguras de 360, 390, 1024 e 1440 px sem overflow horizontal da página ou da tabela. Inspeção visual desktop/mobile concluída; nenhum erro JavaScript.
- Home salva na API, rotina PHP ativa, cache LiteSpeed limpo e HTML canônico conferido depois da limpeza. Nenhuma nota PageSpeed foi inferida desses testes.
