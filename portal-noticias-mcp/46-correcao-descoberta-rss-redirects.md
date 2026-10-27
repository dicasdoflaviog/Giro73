# Correção da descoberta RSS após o log de Itapebi

Data: 27/10/2026. O responsável enviou um log parcial da VPS com falha genérica na descoberta HTML, múltiplos `ConfigError URL deve ser pública, completa e sem credenciais embutidas`, um destino `http://www.itapebi.ba.gov.br/...` e RSS da Câmara de Belmonte reconhecido com zero entradas.

## Diagnóstico demonstrado pelo código e pelo log

O leitor de RSS exige HTTPS. Antes desta correção, o redirecionamento de HTTPS para HTTP era passado diretamente à validação seguinte, produzindo uma mensagem genérica de URL em vez de tentar a versão HTTPS do destino. As URLs iniciais mostradas no log não contêm credenciais; portanto, o erro não prova presença de usuário/senha. A incompatibilidade de esquema do redirecionamento é o problema reproduzido nos testes, não uma confirmação de RSS disponível em Itapebi.

O mesmo log comprova uma descoberta técnica bem-sucedida: `https://camarabelmonte.ba.gov.br/feed/` foi reconhecido como RSS, mas com zero entradas. Isso não equivale a zero notícias no site inteiro nem permite produzir matérias a partir desse feed naquele momento. O motivo de falha da Prefeitura de Belmonte não foi mostrado no trecho e permanece indeterminado.

Também foi identificado um problema independente de orçamento: abrir a página inicial podia consumir todo o deadline compartilhado com a procura dos caminhos RSS.

## Alterações

- Links e redirecionamentos de RSS em HTTP passam a ser normalizados para o mesmo endereço em HTTPS, inclusive o host `www`, sem efetuar a requisição HTTP.
- A normalização só se aplica à leitura de fontes; os clientes autenticados WordPress/OpenRouter e as imagens não foram alterados.
- Certificado, endereço público, ausência de credenciais e limite de redirecionamentos continuam validados. Não há `verify=False`, liberação de IP privado ou fallback silencioso para HTTP.
- Redirecionamento sem Location e loop para a mesma URL HTTPS têm erros específicos, sem gastar todas as tentativas no mesmo endereço.
- A descoberta do HTML recebe um orçamento menor no deadline do cliente (até um terço da fonte, limitado a seis segundos) e restaura o prazo original antes dos testes diretos de RSS. Não é um limite rígido do resolvedor DNS do sistema.
- Aviso de HTML informa o motivo local conhecido e esclarece que a procura direta continua.
- Feed vazio recebe um aviso explícito. RSS confirmado ainda não significa item elegível, licença de reprodução ou apuração jornalística concluída.

## Validação e aplicação

Passaram **104 testes Python**, incluindo redirecionamento como o de Itapebi, link RSS em HTTP no HTML, tentativa apenas de HTTPS, rejeição de IP privado e credenciais, loop/Location vazio e reserva de tempo para RSS depois de uma falha do HTML. A sintaxe Bash do instalador foi conferida.

A disponibilidade real do RSS de Itapebi continua pendente de teste na VPS; o proxy deste ambiente impede a consulta externa. Não foi acessada a VPS por SSH nem executada publicação de teste no WordPress. A alteração está no Git e deve ser instalada pelo `expandir-fontes-oficiais.sh` com a referência atualizada, repetindo primeiro `--city itapebi --city belmonte --timeout 60`.

Não foram alterados os limites editoriais, o cron ou a política de publicação: padrão de cinco candidatos globais por execução, novas fontes institucionais em rascunho e preservação dos cadastros existentes.
