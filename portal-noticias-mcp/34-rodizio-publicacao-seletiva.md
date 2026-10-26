# Rodízio de fontes e publicação seletiva — 26/10/2026

## Política aprovada

| Fonte | Modo |
| --- | --- |
| Agência Brasil — Últimas | Automático elegível |
| Agência Brasil — Economia | Automático elegível |
| G1 Bahia | Automático elegível |
| Portal Leo Dias | Automático elegível |
| Demais fontes, inclusive G1 Brasil | Rascunho |

A seleção corresponde a quatro URLs de feeds e é aplicada pelo administrador na VPS. Não altera categorias, IDs nem habilita fontes pausadas. O arquivo `configurar_publicacao_rss.py` apenas simula sem `--apply`; com essa opção, faz backup e substituição atômica de `/etc/giro73-rss.json`. A seleção considera URLs exatas, incluindo caminho, sem liberar outros feeds do mesmo domínio.

O atualizador aplica essa seleção somente com a opção explícita:

```bash
bash /root/atualizar-giro73-rss.sh COMMIT_OU_MAIN --apply-publication-policy
```

Atualizações sem a opção preservam os modos locais. `.env`, banco, IDs e histórico são mantidos. O instalador mantém o lock da coleta durante a aplicação. Não há implantação remota automática pelo agente, que não tem acesso SSH à VPS.

## Critérios da publicação automática

O padrão, inclusive em configurações antigas sem campos novos, continua sendo rascunho. Campos por fonte:

```json
{
  "publication_mode": "auto",
  "auto_min_chars": 600,
  "auto_max_age_hours": 24
}
```

- `publication_mode`: `draft` ou `auto`; o status não é escolhido pela IA.
- `auto_min_chars`: inteiro 600–20000. Tanto o texto recebido da fonte quanto os parágrafos gerados precisam atingir o mínimo. Títulos, legendas e link de atribuição não contam.
- `auto_max_age_hours`: inteiro 1–72. Padrão 24 horas; ausência de data ou notícia fora da janela fica para revisão.
- Também exige material considerado apto pelo modelo, foto validada e os três campos SEO estruturalmente válidos. Não faz avaliação visual humana da foto nem comprova fidelidade factual.
- Falta de requisito vira rascunho com motivo no log; não aumenta texto artificialmente nem inventa fatos. O Snippet 12 já instalado confirma a capa antes de publicar e pode manter `draft` se a importação falhar.

Os critérios são avaliados ao preparar cada novo payload. Retentativas de payloads já enviados/tentados mantêm o conteúdo e status originais para resolver respostas perdidas sem duplicar matérias.

São **600 caracteres**, não 600 palavras. Esse filtro não garante nota verde no Rank Math, aprovação AdSense, direitos de reprodução ou qualidade jornalística. A seleção não concede licença de textos/fotos; confira as condições de uso de cada origem e mantenha atribuição. Fonte grande que só ofereça resumo curto pode continuar gerando rascunhos.

## Rodízio e prioridade

O SQLite guarda a última vez em que cada fonte foi atendida. A próxima seleção favorece a fonte há mais tempo sem atendimento, inclusive entre execuções com limite 1. Dentro da vez da fonte, resolve payloads já preparados e depois prioriza notícias de data mais recente. Uma fonte com fila grande não ocupa sozinha todas as vagas enquanto houver outras aptas. Fontes desabilitadas/removidas deixam de ter itens enviados, sem apagar a fila.

O limite continua sendo o total de candidatos por execução, não por fonte. O cron existente permanece de hora em hora: a publicação acontece quando o coletor executa e encontra item apto, sem aprovação no painel; não é detecção instantânea de atualizações na origem.

## Confirmação e migração

`sent` significa recebido pelo WordPress, incluindo rascunhos. O novo campo `wp_status` registra `draft` ou `publish`, e o log mostra status, capa e permalink. Uma publicação solicitada que o servidor preserve como rascunho não é repetida como outro artigo.

A migração adiciona `wp_status`, `published_at` e `source_rotation`, preservando dados existentes. Recupera datas de itens pendentes a partir de `input_json`. Payloads preparados continuam imutáveis e com a mesma chave de idempotência: rascunhos preparados antes da mudança não são promovidos. Revogar o modo automático bloqueia envios de payloads `publish` preparados até revisão. Um 422 de imagem confirmado de um endpoint antigo gera a variante de rascunho sem foto, como antes.

`--dry-run` não envia naquele momento, mas pode preparar payloads `publish` que serão enviados pelo cron depois; não é uma pausa permanente da publicação. Para pausar uma fonte, use `authorized: false`; para mantê-la coletando com revisão manual, use `publication_mode: draft`.

## Validação e limites

81 testes Python com APIs simuladas: 63 anteriores e 18 novos. Os casos novos cobrem persistência do rodízio, limite 1, equilíbrio em lote, notícias recentes, fontes pausadas, limite em caracteres, políticas, status real, resposta perdida, revogação, fallback e migração. A sintaxe dos scripts também é conferida. Nenhuma matéria foi publicada nem configuração da VPS alterada pelo agente durante os testes.

A classificação automática por assunto/cidade, links internos automáticos e conferência avançada da palavra-chave discutidos no planejamento continuam como próximas etapas; não fazem parte desta alteração.
