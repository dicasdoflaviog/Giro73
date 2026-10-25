#!/bin/sh
set -eu
umask 077
set -a
. /etc/giro73-rss.env
set +a
exec /opt/giro73-rss/.venv/bin/python \
  /opt/giro73-rss/coletor_rss.py \
  --feeds /etc/giro73-rss.json \
  --state /var/lib/giro73-rss/vistos_rss.sqlite3 "$@"
