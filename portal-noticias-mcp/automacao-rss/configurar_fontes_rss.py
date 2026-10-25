#!/usr/bin/env python3
"""Descobre/verifica RSS e adiciona fontes sem chamar Gemini ou WordPress.

Executar na VPS com o Python do coletor, como administrador. Preserva a fila,
os IDs, as fontes existentes e salva backup antes de substituir a configuração.
authorized=True representa a declaração de autorização do administrador;
o teste técnico não verifica licenças de reutilização.
"""
from __future__ import annotations

import argparse
import hashlib
from html.parser import HTMLParser
import json
import os
from pathlib import Path
import shutil
import sys
import tempfile
import time
from datetime import datetime
from urllib.parse import urljoin, urlsplit

sys.path.insert(0, "/opt/giro73-rss")
from coletor_rss import Client, load_sources, public_feed_host

# Nome, categoria, endereço informado, procurar feed no site.
FONTES = [
    ("Portal TX", "noticias-regiao", "https://portaltx.site/", True),
    ("Vem Vê TX Bahia", "noticias-regiao", "https://vemvetxbahia.com.br/", True),
    ("Liberdade News", "noticias-regiao", "https://liberdadenews.com.br/?format=feed&type=rss", False),
    ("Bahia Notícias — Municípios", "noticias-regiao", "https://www.bahianoticias.com.br/municipios/rss.xml", False),
    ("G1 Bahia", "noticias-regiao", "https://g1.globo.com/rss/g1/bahia/", False),
    ("G1 Brasil", "noticias-regiao", "https://g1.globo.com/rss/g1/brasil/", False),
    ("Agência Brasil — Últimas", "noticias-regiao", "https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml", False),
    ("Bahia Extremo Sul", "noticias-regiao", "https://bahiaextremosul.com.br/feed", False),
    ("Extremo Sul Notícias", "noticias-regiao", "https://extremosulnoticias.com.br/feed/", False),
    ("Bahia Notícias — Esportes", "esportes", "https://www.bahianoticias.com.br/esportes/rss.xml", False),
    ("Agência Brasil — Economia", "dinheiro-renda-extra", "https://agenciabrasil.ebc.com.br/rss/economia/feed.xml", False),
    ("Olhar Digital", "tecnologia-inovacao", "https://olhardigital.com.br/feed/", False),
    ("Portal Leo Dias", "famosos-entretenimento", "https://portalleodias.com/feed", False),
    ("Sul Bahia News", "noticias-regiao", "https://www.sulbahianews.com.br/feed/", False),
    ("Radar News", "noticias-regiao", "https://radar.news/feed/", False),
]


def announce(*parts):
    print(*parts, flush=True)


def url_key(url):
    return url.rstrip("/")


def host_key(url):
    return (urlsplit(url).hostname or "").lower().removeprefix("www.")


class FeedLinks(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        kind = attrs.get("type", "").lower()
        href = attrs.get("href", "")
        if tag == "link" and href and ("rss" in kind or "atom" in kind):
            if "comment" not in href.lower():
                self.links.append(href)


def discover_candidates(client, site):
    """Links declarados primeiro; caminhos comuns são apenas candidatos."""
    candidates, current = [], site
    try:
        for _ in range(4):
            public_feed_host(current)
            response = client.request("GET", current, "rss", stream=True)
            try:
                if response.status_code in {301, 302, 303, 307, 308}:
                    current = urljoin(current, response.headers.get("Location", ""))
                    continue
                if response.status_code != 200:
                    announce("  Página inicial: HTTP", response.status_code)
                    break
                data = bytearray()
                for chunk in response.iter_content(65536):
                    client.remaining()
                    data.extend(chunk)
                    if len(data) > 2*1024*1024:
                        raise ValueError("Página acima de 2 MB.")
                parser = FeedLinks()
                parser.feed(data.decode(response.encoding or "utf-8", errors="replace"))
                candidates.extend(urljoin(current, href) for href in parser.links)
                break
            finally:
                response.close()
    except Exception as error:
        announce("  Descoberta no HTML indisponível:", type(error).__name__)
    for base in dict.fromkeys([site, current]):
        candidates.extend(urljoin(base, path) for path in (
            "feed/", "?feed=rss2", "rss/", "rss.xml", "?format=feed&type=rss"
        ))
    return list(dict.fromkeys(candidates))


def save_config(path, data):
    """Substituição atômica, com backup e preservação do proprietário/grupo."""
    previous = path.stat()
    fd, temporary = tempfile.mkstemp(prefix=".g73-rss-", dir=str(path.parent))
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as output:
            json.dump(data, output, ensure_ascii=False, indent=2)
            output.write("\n")
            output.flush()
            os.fsync(output.fileno())
        load_sources(Path(temporary))
        os.chown(temporary, previous.st_uid, previous.st_gid)
        os.chmod(temporary, 0o640)
        backup = str(path)+".bak-"+datetime.now().strftime("%Y%m%d-%H%M%S-%f")
        shutil.copy2(path, backup)
        os.replace(temporary, path)
        announce("Backup:", backup)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def configure(path, client, definitions=FONTES, seconds=60):
    load_sources(path)
    original = path.read_text(encoding="utf-8")
    data = json.loads(original)
    feeds = data["feeds"]
    added, failures = 0, []
    for name, category, address, discover in definitions:
        existing = next((feed for feed in feeds if
            url_key(feed.get("url", "")) == url_key(address) or
            (discover and host_key(feed.get("url", "")) == host_key(address))), None)
        if existing is not None:
            announce("JÁ CADASTRADA:", name, "|", existing["url"],
                     "| habilitada:", existing.get("authorized") is True)
            continue
        if len(feeds) >= 20:
            announce("NÃO ADICIONADA:", name, "| limite de 20 fontes.")
            failures.append(name)
            continue
        announce("VERIFICANDO:", name)
        client.deadline = time.monotonic()+seconds
        candidates = discover_candidates(client, address) if discover else [address]
        found = False
        for candidate in candidates:
            try:
                client.remaining()
                entries = client.feed(candidate)
            except Exception as error:
                # Somente erros locais/status; nenhuma chave é usada nesta rotina.
                announce("  Falhou:", candidate, "|", type(error).__name__, str(error))
                if time.monotonic() >= client.deadline:
                    break
                continue
            source_id = "fonte_"+hashlib.sha256(address.encode()).hexdigest()[:12]
            if any(feed.get("id") == source_id for feed in feeds):
                announce("  ID já utilizado; exige revisão manual.")
                break
            feeds.append({
                "id": source_id, "name": name, "url": candidate,
                "category": category, "authorized": True,
                "use_full_content": False, "allow_undated": False,
                "max_age_hours": 72,
            })
            added += 1
            found = True
            announce("  RSS VALIDADO:", candidate, "| entradas:", len(entries))
            break
        if not found:
            failures.append(name)
            announce("NÃO ADICIONADA:", name, "| nenhum RSS validado.")
    if added:
        if path.read_text(encoding="utf-8") != original:
            raise RuntimeError("A configuração foi editada durante a verificação; execute novamente.")
        save_config(path, data)
    announce("CONCLUÍDO | adicionadas:", added, "| cadastradas:", len(feeds))
    if failures:
        announce("EXIGEM REVISÃO:", ", ".join(failures))
    return 1 if failures else 0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--config", type=Path, default=Path("/etc/giro73-rss.json"))
    parser.add_argument("--timeout", type=int, default=60, choices=range(10, 121), metavar="10..120")
    args = parser.parse_args()
    os.umask(0o077)
    client = Client("", "", "rss-check", "https://giro73.com.br/wp-json/g73/v1/publish",
                    time.monotonic()+args.timeout, attempts=1)
    try:
        return configure(args.config, client, seconds=args.timeout)
    except json.JSONDecodeError as error:
        announce("JSON inválido na configuração | linha:", error.lineno, "| coluna:", error.colno)
        return 2
    except Exception as error:
        announce("ERRO:", type(error).__name__, str(error))
        return 2
    finally:
        client.session.close()


if __name__ == "__main__":
    sys.exit(main())
