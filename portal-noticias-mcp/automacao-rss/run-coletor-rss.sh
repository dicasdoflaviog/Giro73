#!/bin/sh
set -eu
umask 077
# O Python carrega /opt/giro73-rss/.env com python-dotenv, sem executar conteúdo shell.
exec /opt/giro73-rss/.venv/bin/python \
  /opt/giro73-rss/coletor_rss.py \
  --feeds /etc/giro73-rss.json \
  --state /var/lib/giro73-rss/vistos_rss.sqlite3 "$@"
