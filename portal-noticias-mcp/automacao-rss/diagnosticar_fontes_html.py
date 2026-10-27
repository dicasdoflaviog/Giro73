#!/usr/bin/env python3
"""Inspeciona fontes municipais, sem IA, publicação ou alteração de configurações."""
import argparse
import json
import os
from pathlib import Path
import re
import sys
import time
from urllib.parse import urlsplit

sys.path.insert(0, "/opt/giro73-rss")
from bs4 import BeautifulSoup
from coletor_rss import (Client, ConfigError, Deferred, ItemError, StopRun,
                        feed_redirect_url, https_feed_url, public_feed_host)

SITES = {
    "prefeitura-belmonte": "https://belmonte.ba.gov.br/",
    "prefeitura-itapebi": "https://itapebi.ba.gov.br/",
    "camara-itapebi": "https://camaraitapebi.ba.gov.br/",
}


def fetch(client, url):
    for _ in range(5):
        public_feed_host(url)
        response = client.request("GET", url, "rss", stream=True)
        try:
            if response.status_code in {301, 302, 303, 307, 308}:
                url = feed_redirect_url(url, response.headers.get("Location", ""))
                continue
            if response.status_code != 200:
                return {"url": url, "http": response.status_code}
            data = bytearray()
            for chunk in response.iter_content(65536):
                client.remaining()
                data.extend(chunk)
                if len(data) > 2*1024*1024:
                    raise ItemError("Página acima de 2 MB.")
            # Somente parsing de HTML; não executa scripts nem entidades XML.
            soup = BeautifulSoup(bytes(data), "html.parser", from_encoding=response.encoding)
            return inspect(soup, url, response.headers.get("Content-Type", ""))
        finally:
            response.close()
    raise ItemError("Excesso de redirecionamentos.")


def inspect(soup, url, content_type):
    def text(node, limit=300):
        return " ".join(node.get_text(" ", strip=True).split())[:limit] if node else ""
    generator = soup.find("meta", attrs={"name": re.compile("^generator$", re.I)})
    canonical = soup.find("link", rel="canonical")
    feeds, news, seen = [], [], set()
    host = (urlsplit(url).hostname or "").removeprefix("www.")
    for link in soup.find_all("link", href=True):
        if any(x in str(link.get("type", "")).lower() for x in ("rss", "atom")):
            try:
                candidate = https_feed_url(link["href"], url)
                if "comment" not in candidate.lower():
                    feeds.append(candidate)
            except (ConfigError, ValueError):
                pass
    for anchor in soup.find_all("a", href=True):
        try:
            candidate = https_feed_url(anchor["href"], url)
        except (ConfigError, ValueError):
            continue
        parsed = urlsplit(candidate)
        if (parsed.hostname or "").removeprefix("www.") != host or candidate in seen:
            continue
        if not re.search(r"noticia|not[ií]cia|news|/20\d\d/|imprensa|comunicacao", candidate+" "+text(anchor), re.I):
            continue
        seen.add(candidate)
        news.append({"url": candidate, "texto": text(anchor, 180)})
        if len(news) >= 15:
            break
    times = []
    for node in soup.find_all("meta", attrs={"property": re.compile(r"published_time|modified_time", re.I)}):
        times.append(str(node.get("content", ""))[:100])
    for node in soup.find_all("time")[:5]:
        times.append(str(node.get("datetime") or text(node, 100)))
    main = soup.find("article") or soup.find("main")
    paragraphs = [text(p, 350) for p in main.find_all("p")[:4]] if main else []
    return {"url": url, "http": 200, "content_type": content_type,
            "titulo": text(soup.title), "h1": text(soup.find("h1")),
            "generator": str(generator.get("content", ""))[:200] if generator else "",
            "canonical_declarado": str(canonical.get("href", ""))[:500] if canonical else "",
            "wordpress_markers": bool(soup.find("link", rel="https://api.w.org/") or
                                      soup.find("script", src=re.compile(r"/wp-(content|includes)/"))),
            "rss_declarados_nao_validados": list(dict.fromkeys(feeds)),
            "links_candidatos_nao_validados": news, "datas_encontradas": times,
            "amostra_paragrafos_nao_apurados": paragraphs,
            "observacao": "Metadados e links para diagnóstico; não comprovam RSS ou notícia elegível."}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=Path("/root/g73-diagnostico-fontes-html.json"))
    parser.add_argument("--site", choices=SITES, action="append")
    parser.add_argument("--timeout", type=int, default=30, choices=range(10, 91))
    args = parser.parse_args()
    os.umask(0o077)
    client = Client("", "", "html-check", "https://giro73.com.br/wp-json/g73/v1/publish",
                    time.monotonic()+args.timeout, attempts=1)
    report = {"somente_diagnostico": True, "fontes": []}
    try:
        for name in args.site or SITES:
            print("INSPECIONANDO:", name, flush=True)
            client.deadline = time.monotonic()+args.timeout
            item = {"fonte": name, "site_indicado": SITES[name], "paginas": []}
            try:
                home = fetch(client, SITES[name]); item["paginas"].append(home)
                # No máximo mais uma página, selecionada dos links da própria origem.
                links = home.get("links_candidatos_nao_validados", [])
                if links:
                    candidate = links[0]["url"]
                    if candidate != home["url"]:
                        item["paginas"].append(fetch(client, candidate))
            except (ConfigError, Deferred, ItemError, StopRun, OSError, ValueError) as error:
                item["erro"] = str(error) if isinstance(error, (ConfigError, Deferred, ItemError, StopRun)) else type(error).__name__
            report["fontes"].append(item)
        args.output.write_text(json.dumps(report, ensure_ascii=False, indent=2)+"\n", encoding="utf-8")
        os.chmod(args.output, 0o600)
        print(json.dumps(report, ensure_ascii=False, indent=2))
        print("Relatório salvo:", args.output)
        return 0
    finally:
        client.session.close()


if __name__ == "__main__":
    raise SystemExit(main())
