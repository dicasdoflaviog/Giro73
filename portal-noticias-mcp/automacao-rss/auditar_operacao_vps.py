#!/usr/bin/env python3
"""Auditoria local de agendamento/fila RSS. Não lê .env nem publica ou altera o cron."""
import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import sqlite3
import subprocess
import time
from urllib.parse import urlsplit


def command(args):
    try:
        result = subprocess.run(args, capture_output=True, text=True, timeout=8, check=False)
        states = {'inactive', 'failed', 'activating', 'deactivating'}
        if result.returncode == 0 or (args[:2] == ['systemctl', 'is-active'] and result.stdout.strip() in states):
            return result.stdout
        return ""
    except (OSError, subprocess.TimeoutExpired):
        return ""


def cron_entries(text, source):
    entries = []
    for raw in text.splitlines():
        line = raw.strip()
        if not line or line.startswith('#') or not re.search(r'giro73|coletor_rss\.py', line, re.I):
            continue
        if re.match(r'^[A-Za-z_][A-Za-z_0-9]*\s*=', line):
            continue
        parts = line.split()
        size = 1 if line.startswith('@') else 5
        if len(parts) <= size:
            continue
        limit = re.search(r'--limit(?:=|\s+)(\d+)', line)
        entries.append({'origem': source, 'expressao_cron': ' '.join(parts[:size]),
                        'coletor_rss_identificado': bool(re.search(r'/opt/giro73-rss/run|coletor_rss\.py', line)),
                        'limit_explicito': int(limit.group(1)) if limit else None,
                        'observacao': 'Comando e variáveis omitidos para não revelar credenciais.'})
    return entries


def collect():
    now = time.time()
    report = {'momento_utc': datetime.now(timezone.utc).isoformat(), 'somente_leitura': True,
              'fuso_sistema': command(['timedatectl', 'show', '--property=Timezone', '--value']).strip(),
              'servico_cron': command(['systemctl', 'is-active', 'cron']).strip() or 'nao_confirmado',
              'cron_encontrado': [], 'timers_candidatos': []}
    paths = [Path('/etc/crontab')]
    directory = Path('/etc/cron.d')
    if directory.is_dir():
        paths += [p for p in directory.iterdir() if re.fullmatch(r'[A-Za-z0-9_-]+', p.name) and p.is_file()]
    for path in paths:
        try:
            report['cron_encontrado'] += cron_entries(path.read_text(errors='replace'), str(path))
        except OSError:
            pass
    for user in ['root', 'giro73-rss']:
        report['cron_encontrado'] += cron_entries(command(['crontab', '-u', user, '-l']), 'crontab:'+user)
    listing = command(['systemctl', 'list-unit-files', '--type=timer', '--no-legend', '--no-pager'])
    for line in listing.splitlines():
        unit = line.split()[0] if line.split() else ''
        if not re.fullmatch(r'[A-Za-z0-9_.@-]+\.timer', unit) or not re.search(r'giro|rss|telegram', unit, re.I):
            continue
        properties = command(['systemctl', 'show', unit, '--property=TimersCalendar',
                              '--property=LastTriggerUSec', '--property=NextElapseUSecRealtime'])
        report['timers_candidatos'].append({'unidade': unit, 'propriedades': properties.strip()})
    config = Path('/etc/giro73-rss.json')
    try:
        feeds = json.loads(config.read_text(encoding='utf-8-sig')).get('feeds', [])
        if not isinstance(feeds, list):
            raise ValueError('feeds deve ser uma lista')
        report['fontes'] = [{'id': f.get('id'), 'nome': f.get('name'),
                             'host': urlsplit(f.get('url', '')).hostname,
                             'categoria': f.get('category'), 'autorizada': f.get('authorized'),
                             'publication_mode': f.get('publication_mode', 'draft'),
                             'janela_horas': f.get('max_age_hours', 72),
                             'auto_min_chars': f.get('auto_min_chars', 600),
                             'auto_max_age_hours': f.get('auto_max_age_hours', 24)}
                            for f in feeds if isinstance(f, dict)]
    except (OSError, ValueError, TypeError, AttributeError):
        report['fontes_erro'] = 'Configuracao ausente, invalida ou sem permissao; valores secretos nao foram lidos.'
    database = Path('/var/lib/giro73-rss/vistos_rss.sqlite3')
    if database.is_file():
        try:
            with sqlite3.connect(database.resolve().as_uri()+'?mode=ro', uri=True, timeout=2) as db:
                report['fila'] = dict(db.execute('SELECT status,COUNT(*) FROM items GROUP BY status').fetchall())
                columns = {row[1] for row in db.execute('PRAGMA table_info(items)')}
                report['confirmados_ultimas_24h'] = db.execute("SELECT COUNT(*) FROM items WHERE status='sent' AND updated_at>=?", (now-86400,)).fetchone()[0]
                report['confirmados_ultimos_7dias'] = db.execute("SELECT COUNT(*) FROM items WHERE status='sent' AND updated_at>=?", (now-7*86400,)).fetchone()[0]
                if 'wp_status' in columns:
                    report['status_wp_confirmados_24h'] = dict(db.execute("SELECT COALESCE(wp_status,'desconhecido'),COUNT(*) FROM items WHERE status='sent' AND updated_at>=? GROUP BY wp_status", (now-86400,)).fetchall())
                report['nota_contagem'] = 'Confirmacoes locais de envio, nao todas as publicacoes do portal; status pode ter sido alterado depois no WordPress.'
        except sqlite3.Error:
            report['fila_erro'] = 'Banco indisponivel ou esquema diferente; nenhuma migracao executada.'
    else:
        report['fila_erro'] = 'Banco esperado nao encontrado; pode existir outro worker/caminho.'
    log = Path('/var/log/giro73-rss/coletor.log')
    if log.is_file():
        try:
            with log.open('rb') as handle:
                handle.seek(max(0, log.stat().st_size-1048576))
                text = handle.read(1048576).decode('utf-8', errors='replace')
            finishes = []
            for line in text.splitlines():
                match = re.search(r'Fim:\s*(\d+)\s+(?:itens|rascunhos)\s+confirmados', line)
                if match:
                    stamp = re.match(r'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}', line)
                    finishes.append({'hora_como_no_log': stamp.group(0) if stamp else None,
                                     'confirmados': int(match.group(1))})
            report['ultimas_conclusoes_no_log'] = finishes[-12:]
            report['log_nota'] = 'Amostra de ate 1 MB; horarios sem conversao e sem publicar linhas brutas.'
        except OSError:
            report['log_erro'] = 'Sem permissao de leitura.'
    root = Path('/opt/giro73-rss')
    report['versoes_sha256'] = {}
    for name in ['coletor_rss.py', 'run', 'configurar_publicacao_rss.py']:
        try:
            report['versoes_sha256'][name] = hashlib.sha256((root/name).read_bytes()).hexdigest()
        except OSError:
            pass
    report['limite_da_auditoria'] = 'Nao encontra automaticamente workers com nomes/caminhos desconhecidos; nao le .env, tokens, corpos de artigos ou comandos completos.'
    return report


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=Path('/root/g73-auditoria-operacao.json'))
    args = parser.parse_args()
    os.umask(0o077)
    report = collect()
    result = json.dumps(report, ensure_ascii=False, indent=2)
    args.output.write_text(result+'\n', encoding='utf-8')
    os.chmod(args.output, 0o600)
    print(result)
    print('Relatorio salvo:', args.output)


if __name__ == '__main__':
    main()
