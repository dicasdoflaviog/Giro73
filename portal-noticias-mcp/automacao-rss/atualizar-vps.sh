#!/usr/bin/env bash
# Uso: sudo bash atualizar-vps.sh COMMIT_OU_MAIN [--apply-publication-policy]
set -euo pipefail
umask 077

if [[ "$(id -u)" -ne 0 ]]; then
  echo 'Execute como root ou com sudo.' >&2
  exit 1
fi
RSS_REF="${1:-main}"
RSS_POLICY="${2:-}"
if [[ -n "$RSS_POLICY" && "$RSS_POLICY" != '--apply-publication-policy' ]]; then
  echo 'Opção inválida. Use --apply-publication-policy para aplicar a seleção aprovada de fontes.' >&2
  exit 1
fi
if [[ ! "$RSS_REF" =~ ^[A-Za-z0-9._-]{1,80}$ ]]; then
  echo 'Referência Git inválida.' >&2
  exit 1
fi
RSS_ROOT=/opt/giro73-rss
RSS_STATE=/var/lib/giro73-rss/vistos_rss.sqlite3
RSS_BASE="https://raw.githubusercontent.com/dicasdoflaviog/Giro73/${RSS_REF}/portal-noticias-mcp/automacao-rss"
id giro73-rss >/dev/null
command -v curl >/dev/null
command -v flock >/dev/null
command -v sudo >/dev/null
if [[ ! -x "$RSS_ROOT/.venv/bin/python" || ! -f "$RSS_ROOT/.env" ]]; then
  echo 'É necessário o ambiente virtual existente e /opt/giro73-rss/.env com OPENROUTER_API_KEY.' >&2
  exit 1
fi

RSS_STAGE=$(mktemp -d "$RSS_ROOT/.update.XXXXXX")
trap 'rm -rf -- "$RSS_STAGE"' EXIT
for RSS_FILE in coletor_rss.py requirements-rss.txt run-coletor-rss.sh configurar_publicacao_rss.py configurar_fontes_rss.py capas_editorias.json giro73-rss.cron; do
  curl --fail --silent --show-error --location --retry 3 --max-time 60 \
    "$RSS_BASE/$RSS_FILE" --output "$RSS_STAGE/$RSS_FILE"
done
sh -n "$RSS_STAGE/run-coletor-rss.sh"
"$RSS_ROOT/.venv/bin/python" -m py_compile "$RSS_STAGE/coletor_rss.py" "$RSS_STAGE/configurar_publicacao_rss.py"

install -d -o giro73-rss -g giro73-rss -m 0700 /var/lib/giro73-rss
# Mesmo inode e lock do coletor; não remover nem substituir o arquivo .lock.
touch "$RSS_STATE.lock"
chown giro73-rss:giro73-rss "$RSS_STATE.lock"
chmod 0600 "$RSS_STATE.lock"
exec 9>>"$RSS_STATE.lock"
if ! flock -w 30 9; then
  echo 'Há uma coleta em andamento. Aguarde terminar e execute a atualização novamente; os arquivos não foram substituídos.' >&2
  exit 1
fi

RSS_BACKUP=$(mktemp -d /root/giro73-rss-backup.XXXXXX)
for RSS_FILE in coletor_rss.py requirements-rss.txt run .env configurar_publicacao_rss.py configurar_fontes_rss.py capas_editorias.json; do
  if [[ -f "$RSS_ROOT/$RSS_FILE" ]]; then cp -a "$RSS_ROOT/$RSS_FILE" "$RSS_BACKUP/"; fi
done
if [[ -f /etc/giro73-rss.env ]]; then cp -a /etc/giro73-rss.env "$RSS_BACKUP/legacy.env"; fi
if [[ -f /etc/giro73-rss.json ]]; then cp -a /etc/giro73-rss.json "$RSS_BACKUP/feeds.json"; fi
if [[ -f "$RSS_STATE" ]]; then
  "$RSS_ROOT/.venv/bin/python" - "$RSS_STATE" "$RSS_BACKUP/vistos_rss.sqlite3" <<'PY'
import sqlite3, sys
from pathlib import Path
with sqlite3.connect(Path(sys.argv[1]).resolve().as_uri()+"?mode=ro", uri=True) as source:
    with sqlite3.connect(sys.argv[2]) as destination:
        source.backup(destination)
PY
fi

# Bibliotecas não contêm credenciais e precisam ser legíveis pelo usuário de serviço.
# O umask 077 permanece no restante do script (backup, .env e banco).
(
  umask 022
  "$RSS_ROOT/.venv/bin/python" -m pip install --no-cache-dir --timeout 30 --retries 2 \
    -r "$RSS_STAGE/requirements-rss.txt"
)
# Repara instalações anteriores em que python-dotenv já existe, mas ficou privado
# para root: pip normalmente responderia "Requirement already satisfied".
if ! (cd "$RSS_ROOT" && sudo -u giro73-rss "$RSS_ROOT/.venv/bin/python" \
  -c 'from dotenv import dotenv_values, load_dotenv') >/dev/null 2>&1; then
  echo 'Reinstalando python-dotenv com permissões de leitura para o usuário de serviço...'
  (
    umask 022
    "$RSS_ROOT/.venv/bin/python" -m pip install --force-reinstall --no-deps \
      --no-cache-dir --timeout 30 --retries 2 'python-dotenv>=1.1,<2'
  )
fi
if ! (cd "$RSS_ROOT" && sudo -u giro73-rss "$RSS_ROOT/.venv/bin/python" \
  -c 'from dotenv import dotenv_values, load_dotenv; import requests, feedparser; from bs4 import BeautifulSoup; from PIL import Image'); then
  echo 'O usuário giro73-rss não consegue importar as bibliotecas. Confira permissões do venv e arquivos locais com nomes de módulos; o coletor não foi substituído.' >&2
  exit 1
fi
for RSS_FILE in configurar_fontes_rss.py capas_editorias.json; do
  install -o root -g giro73-rss -m 0640 "$RSS_STAGE/$RSS_FILE" "$RSS_ROOT/$RSS_FILE"
done
install -o root -g giro73-rss -m 0640 "$RSS_STAGE/coletor_rss.py" "$RSS_ROOT/coletor_rss.py.new"
install -o root -g giro73-rss -m 0640 "$RSS_STAGE/requirements-rss.txt" "$RSS_ROOT/requirements-rss.txt.new"
install -o root -g giro73-rss -m 0750 "$RSS_STAGE/run-coletor-rss.sh" "$RSS_ROOT/run.new"
install -o root -g giro73-rss -m 0640 "$RSS_STAGE/configurar_publicacao_rss.py" "$RSS_ROOT/configurar_publicacao_rss.py.new"
chown root:giro73-rss "$RSS_ROOT/.env"
chmod 0640 "$RSS_ROOT/.env"
for RSS_DB in "$RSS_STATE" "$RSS_STATE-wal" "$RSS_STATE-shm" "$RSS_STATE-journal"; do
  if [[ -f "$RSS_DB" ]]; then chown giro73-rss:giro73-rss "$RSS_DB"; chmod 0600 "$RSS_DB"; fi
done
mv -f "$RSS_ROOT/coletor_rss.py.new" "$RSS_ROOT/coletor_rss.py"
mv -f "$RSS_ROOT/requirements-rss.txt.new" "$RSS_ROOT/requirements-rss.txt"
mv -f "$RSS_ROOT/run.new" "$RSS_ROOT/run"
mv -f "$RSS_ROOT/configurar_publicacao_rss.py.new" "$RSS_ROOT/configurar_publicacao_rss.py"
if [[ "$RSS_POLICY" == '--apply-publication-policy' ]]; then
  # Feed verification failures are reported individually; do not invent RSS endpoints.
  "$RSS_ROOT/.venv/bin/python" "$RSS_ROOT/configurar_fontes_rss.py" --timeout 20 || [[ $? == 1 ]]
  "$RSS_ROOT/.venv/bin/python" "$RSS_ROOT/configurar_fontes_rss.py" --official-government --timeout 60 || [[ $? == 1 ]]
  "$RSS_ROOT/.venv/bin/python" "$RSS_ROOT/configurar_publicacao_rss.py" --apply
fi
# Replace the canonical cron, and stop the optional timer to avoid duplicate schedules.
if command -v systemctl >/dev/null && systemctl cat giro73-rss.timer >/dev/null 2>&1; then
  systemctl disable --now giro73-rss.timer
fi
if [[ -f /etc/cron.d/giro73-rss ]]; then cp -a /etc/cron.d/giro73-rss "$RSS_BACKUP/cron"; fi
install -o root -g root -m 0644 "$RSS_STAGE/giro73-rss.cron" /etc/cron.d/giro73-rss
flock -u 9
exec 9>&-

echo "Atualização instalada. Backup privado: $RSS_BACKUP"
echo 'Validando OpenRouter sem gerar texto...'
sudo -u giro73-rss /opt/giro73-rss/run --check-ai
echo 'Processando até dez candidatos; confira editoria, status, capa e link no log...'
sudo -u giro73-rss /opt/giro73-rss/run --limit 10
