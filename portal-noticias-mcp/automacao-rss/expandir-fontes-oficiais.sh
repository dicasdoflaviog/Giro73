#!/usr/bin/env bash
# Atualiza apenas os dois scripts necessários e verifica RSS governamentais.
# Não gera texto, não chama o endpoint WordPress e não muda a política existente.
set -euo pipefail
umask 077

if [[ "$(id -u)" -ne 0 ]]; then
  echo 'Execute como root ou com sudo.' >&2
  exit 2
fi
G73_REF="${1:-main}"
if (( $# )); then shift; fi
if [[ ! "$G73_REF" =~ ^[A-Za-z0-9._-]{1,80}$ ]]; then
  echo 'Referência Git inválida.' >&2
  exit 2
fi
G73_ROOT=/opt/giro73-rss
G73_STATE=/var/lib/giro73-rss/vistos_rss.sqlite3
G73_BASE="https://raw.githubusercontent.com/dicasdoflaviog/Giro73/${G73_REF}/portal-noticias-mcp/automacao-rss"
id giro73-rss >/dev/null
for G73_TOOL in curl flock install; do command -v "$G73_TOOL" >/dev/null; done
if [[ ! -x "$G73_ROOT/.venv/bin/python" || ! -f "$G73_ROOT/coletor_rss.py" || ! -f /etc/giro73-rss.json ]]; then
  echo 'Instalação existente do coletor e /etc/giro73-rss.json são necessários.' >&2
  exit 2
fi
G73_STAGE=$(mktemp -d "$G73_ROOT/.fontes.XXXXXX")
trap 'rm -rf -- "$G73_STAGE"' EXIT
for G73_FILE in coletor_rss.py configurar_fontes_rss.py; do
  curl --fail --silent --show-error --location --retry 3 --max-time 60 \
    "$G73_BASE/$G73_FILE" --output "$G73_STAGE/$G73_FILE"
done
"$G73_ROOT/.venv/bin/python" -m py_compile "$G73_STAGE/coletor_rss.py" "$G73_STAGE/configurar_fontes_rss.py"
"$G73_ROOT/.venv/bin/python" -c 'import requests, feedparser; from bs4 import BeautifulSoup; from PIL import Image; from dotenv import dotenv_values, load_dotenv'

install -d -o giro73-rss -g giro73-rss -m 0700 /var/lib/giro73-rss
touch "$G73_STATE.lock"
chown giro73-rss:giro73-rss "$G73_STATE.lock"
chmod 0600 "$G73_STATE.lock"
exec 9>>"$G73_STATE.lock"
if ! flock -w 30 9; then
  echo 'Coleta em andamento. Execute novamente quando terminar; nenhum arquivo substituído.' >&2
  exit 2
fi
G73_BACKUP=$(mktemp -d /root/giro73-fontes-backup.XXXXXX)
cp -a /etc/giro73-rss.json "$G73_BACKUP/feeds.json"
for G73_FILE in coletor_rss.py configurar_fontes_rss.py; do
  if [[ -f "$G73_ROOT/$G73_FILE" ]]; then cp -a "$G73_ROOT/$G73_FILE" "$G73_BACKUP/"; fi
  install -o root -g giro73-rss -m 0640 "$G73_STAGE/$G73_FILE" "$G73_ROOT/$G73_FILE.new"
done
for G73_FILE in coletor_rss.py configurar_fontes_rss.py; do
  mv -f "$G73_ROOT/$G73_FILE.new" "$G73_ROOT/$G73_FILE"
done
echo "Scripts atualizados. Backup privado: $G73_BACKUP"
echo 'Verificando RSS; somente feeds válidos serão cadastrados como draft.'
cd "$G73_ROOT"
G73_RESULT=0
"$G73_ROOT/.venv/bin/python" -B "$G73_ROOT/configurar_fontes_rss.py" \
  --only-government --timeout 20 "$@" || G73_RESULT=$?
if [[ "$G73_RESULT" == 1 ]]; then
  echo 'Verificação parcial: confira as fontes em EXIGEM REVISÃO. As válidas foram salvas com backup.'
elif [[ "$G73_RESULT" == 0 ]]; then
  echo 'Verificação concluída. Confira os cadastros e os feeds já existentes no relatório.'
fi
echo 'Esta rotina não gerou textos, não criou posts e não alterou o cron nem o arquivo .env.'
exit "$G73_RESULT"
