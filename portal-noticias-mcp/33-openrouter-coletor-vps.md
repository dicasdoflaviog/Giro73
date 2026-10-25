# Provedor oficial do coletor RSS: OpenRouter

O coletor foi padronizado para a API HTTP OpenAI-compatible do OpenRouter, sem chamada direta a outro provedor. `AI_MODEL` tem padrão `openai/gpt-4o-mini`; a chave é `OPENROUTER_API_KEY`. Ambas vêm de `/opt/giro73-rss/.env` ou de variáveis exportadas. `G73_TOKEN` e o endpoint WordPress permanecem separados da credencial de IA.

## Arquivos da atualização

- `automacao-rss/coletor_rss.py`: `/key`, `/models` e `/chat/completions`, JSON Schema estrito e retentativas de 429/503.
- `automacao-rss/requirements-rss.txt`: inclui `python-dotenv`; mantém requests, feedparser, BeautifulSoup e Pillow.
- `automacao-rss/run-coletor-rss.sh`: instalado como `/opt/giro73-rss/run`; o Python carrega o `.env` oficial.
- `automacao-rss/atualizar-vps.sh`: atualização com backup privado, lock, compilação, dependências e permissões.
- `automacao-rss/README.md` e `giro73-rss.env.example`: configuração e operação atuais.

## Contrato preservado

As matérias continuam com `status: draft`, `image_url` e os três campos `rank_math_focus_keyword`, `rank_math_title` e `rank_math_description` em `meta`. A imagem validada é inserida em uma figura após o primeiro parágrafo, com ALT igual à palavra-chave. O Snippet 12 registra a mídia e troca a URL externa pela local quando a importação tem sucesso.

A migração mantém fontes, SQLite, idempotência e payloads já preparados. Não recupera automaticamente capas de rascunhos antigos. A presença de imagem/ALT não garante nota 80 no Rank Math.

## Aplicação na VPS

Execute o atualizador como root, passando o commit que deseja instalar. Ele preserva o `.env` existente e o cron, substitui somente coletor, requirements e launcher e executa:

```bash
sudo -u giro73-rss /opt/giro73-rss/run --check-ai
sudo -u giro73-rss /opt/giro73-rss/run --limit 1
```

`--check-ai` não gera texto nem cria matérias. `--limit 1` depende de haver candidato novo apto e imagem acessível. O log de confirmação mostra ID WordPress, `capa` e link; uma capa com ID positivo confirma o attachment retornado pelo endpoint. Se houver coleta ativa, o instalador aguarda até 30 segundos e interrompe sem substituir os arquivos caso o lock continue ocupado.

## Validação da entrega

63 testes Python locais com APIs simuladas, incluindo contrato OpenRouter, autenticação, erros 429/503/402, `.env`, SEO, imagens e confirmação WordPress. Scripts shell verificados quanto à sintaxe. Não foi utilizada chave OpenRouter real, acessada a VPS nem criado rascunho real nesta validação.
