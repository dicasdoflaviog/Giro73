# Diagnóstico HTML — Belmonte e Itapebi

Resultado fornecido pelo responsável em 27/10/2026, após executar `diagnosticar_fontes_html.py` na VPS. O diagnóstico consultou a página inicial e a listagem de notícias de cada uma das três fontes; as seis páginas responderam HTTP 200. Não houve leitura integral das matérias individuais nem publicação nesta etapa.

| Fonte | O que o diagnóstico mostrou | Encaminhamento |
|---|---|---|
| Prefeitura de Belmonte | `/noticias` existe e lista links com datas em junho de 2026 e anteriores; sem RSS declarado nas duas páginas consultadas | Não inserir esse histórico na fila atual; manter como candidata, com eventual monitoramento de notícias novas |
| Prefeitura de Itapebi | `/site/noticias` existe, mas o texto recebido informa “Nenhuma Notícia Encontrada”; título indica portal SAI | Não há matéria disponível nessa listagem no momento da consulta |
| Câmara de Itapebi | `/site/noticias` lista URLs com referências a 2020 e 2018; sem RSS declarado nas páginas examinadas | Não importar essas referências antigas como notícias atuais |
| Câmara de Belmonte | No log RSS anterior: XML reconhecido, zero entradas e cadastro concluído | Manter o registro existente; o feed não fornecia matéria naquele teste |

As datas acima foram identificadas nos **endereços e títulos dos links**, não confirmadas no cabeçalho de cada matéria. A amostra examinada não comprova que todo o conteúdo desses domínios esteja desatualizado, nem que inexista um RSS em outro caminho. A lista recente de Belmonte mostrada no diagnóstico inclui 16/06/2026, 09/06/2026 e 04/06/2026, anteriores à janela padrão de 72 horas do coletor.

Os sites de Itapebi responderam em `https://www...`; portanto, neste teste não persistiu a falha de esquema HTTP do coletor. O obstáculo atual dessas amostras é a ausência de conteúdo recente/legível como RSS, e não uma autorização de imagens no WordPress.

## Decisão nesta etapa

- Registrar as observações no catálogo, sem remover fontes nem alterar configuração, fila, credenciais, cron ou Snippet 12.
- Não construir um adaptador HTML para alimentar a fila atual com o histórico identificado. Uma futura integração deve conferir data original, URL de matéria, texto e direitos da imagem antes de habilitar a fonte.
- Não mudar datas antigas para a data de coleta nem transformar anúncios de 2020 ou junho de 2026 em notícia atual.
- Prosseguir com o próximo lote já autorizado: Prefeituras e Câmaras de **Porto Seguro, Eunápolis e Itamaraju**, usando o configurador já instalado na VPS.

```bash
/opt/giro73-rss/.venv/bin/python -B /opt/giro73-rss/configurar_fontes_rss.py \
  --only-government \
  --city porto-seguro --city eunapolis --city itamaraju \
  --timeout 30
```

O comando verifica até seis órgãos, cadastra somente feeds reconhecidos e deixa novas fontes em rascunho. Pode concluir parcialmente e salvar os válidos. Ele não publica artigos nem chama a IA. As fontes existentes são preservadas; `JÁ CADASTRADA` não implica uma nova validação do RSS. A execução real desse próximo lote ainda não foi realizada por este agente, que não tem conexão SSH com a VPS.

O último resumo de cadastro fornecido foi de 17 fontes, não uma confirmação de que todas estejam fornecendo notícias. As observações no catálogo são documentação do diagnóstico e não uma cópia atual da configuração da VPS.
