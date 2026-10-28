#!/usr/bin/env python3
"""Descobre/verifica RSS e adiciona fontes sem chamar a IA ou WordPress.

Executar na VPS com o Python do coletor, como administrador. Preserva a fila,
os IDs, as fontes existentes e salva backup antes de substituir a configuração.
authorized=True representa a declaração de autorização do administrador;
o teste técnico não verifica licenças de reutilização.
"""
from __future__ import annotations

import argparse
import calendar
from collections.abc import Mapping
import hashlib
from html.parser import HTMLParser
import json
import os
from pathlib import Path
import shutil
import sys
import tempfile
import time
import requests
from datetime import datetime
from urllib.parse import urljoin, urlsplit

sys.path.insert(0, "/opt/giro73-rss")
from coletor_rss import (Client, ConfigError, Deferred, ItemError, StopRun, MAX_SOURCES,
                        canonical_url, feed_redirect_url, https_feed_url,
                        load_sources, plain, public_feed_host)

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
    ("Agência Brasil — Esportes", "esportes", "https://agenciabrasil.ebc.com.br/rss/esportes/feed.xml", False),
    ("GE Bahia", "esportes", "https://ge.globo.com/rss/ge/futebol/times/bahia/", False),
    ("Canal Rural", "dinheiro-renda-extra", "https://www.canalrural.com.br/feed/", False),
    ("Notícias Agrícolas", "dinheiro-renda-extra", "https://www.noticiasagricolas.com.br/rss/noticias.xml", False),
    ("Canaltech", "tecnologia-inovacao", "https://canaltech.com.br/rss/", False),
    ("Olhar Digital", "tecnologia-inovacao", "https://olhardigital.com.br/feed/", False),
    ("Portal Leo Dias", "famosos-entretenimento", "https://portalleodias.com/feed", False),
    ("Sul Bahia News", "noticias-regiao", "https://www.sulbahianews.com.br/feed/", False),
    ("Radar News", "noticias-regiao", "https://radar.news/feed/", False),
]

CONVERTIDAS = [
    ("Regional via PolitePaul 1", "noticias-regiao", "https://politepaul.com/fd/GIKEXW1XNI1F.xml", False),
    ("Regional via PolitePaul 2", "noticias-regiao", "https://politepaul.com/fd/Fqe4CqOn1xIz.xml", False),
    ("Regional via PolitePaul 3", "noticias-regiao", "https://politepaul.com/fd/PjntToq6bO88.xml", False),
]

# Endereços indicados pelo administrador: candidatos, não RSS confirmados.
# Cidade, slug estável, Prefeitura, Câmara.
MUNICIPIOS = [
    ("Teixeira de Freitas", "teixeira-de-freitas", "teixeiradefreitas.ba.gov.br", "camaratf.ba.gov.br"),
    ("Prado", "prado", "prado.ba.gov.br", "camaraprado.ba.gov.br"),
    ("Itabela", "itabela", "itabela.ba.gov.br", "camaraitabela.ba.gov.br"),
    ("Porto Seguro", "porto-seguro", "portoseguro.ba.gov.br", "cmportoseguro.ba.gov.br"),
    ("Eunápolis", "eunapolis", "eunapolis.ba.gov.br", "cmeunapolis.ba.gov.br"),
    ("Itamaraju", "itamaraju", "itamaraju.ba.gov.br", "camaraitamaraju.ba.gov.br"),
    ("Alcobaça", "alcobaca", "alcobaca.ba.gov.br", "camaraalcobaca.ba.gov.br"),
    ("Caravelas", "caravelas", "caravelas.ba.gov.br", "camaracaravelas.ba.gov.br"),
    ("Nova Viçosa", "nova-vicosa", "novavicosa.ba.gov.br", "camaranovavicosa.ba.gov.br"),
    ("Mucuri", "mucuri", "mucuri.ba.gov.br", "camaramucuri.ba.gov.br"),
    ("Medeiros Neto", "medeiros-neto", "medeirosneto.ba.gov.br", "camaramedeirosneto.ba.gov.br"),
    ("Ibirapuã", "ibirapua", "ibirapua.ba.gov.br", "camaraibirapua.ba.gov.br"),
    ("Lajedão", "lajedao", "lajedao.ba.gov.br", "camaralajedao.ba.gov.br"),
    ("Vereda", "vereda", "vereda.ba.gov.br", "camaravereda.ba.gov.br"),
    ("Jucuruçu", "jucurucu", "jucurucu.ba.gov.br", "camarajucurucu.ba.gov.br"),
    ("Santa Cruz Cabrália", "santa-cruz-cabralia", "pmscc.ba.gov.br", "cmcabralia.ba.gov.br"),
    ("Belmonte", "belmonte", "belmonte.ba.gov.br", "camarabelmonte.ba.gov.br"),
    ("Itapebi", "itapebi", "itapebi.ba.gov.br", "camaraitapebi.ba.gov.br"),
    ("Guaratinga", "guaratinga", "guaratinga.ba.gov.br", "camaraguaratinga.ba.gov.br"),
]


def government_definitions(cities=None):
    """Preserva editorias: prefeitura em Região, câmara em Política, revisão manual."""
    selected = set(cities or [])
    definitions = []
    for city, slug, prefeitura, camara in MUNICIPIOS:
        if selected and slug not in selected:
            continue
        definitions.extend([
            ("Prefeitura de "+city, "noticias-regiao", "https://www."+prefeitura+"/", True),
            ("Câmara de "+city, "politica", "https://www."+camara+"/", True),
        ])
    return definitions


def government_metadata(address):
    for city, slug, prefeitura, camara in MUNICIPIOS:
        host = host_key(address)
        if host in {prefeitura, camara}:
            return {"city": city, "city_slug": slug, "site_url": address,
                    "source_type": "prefeitura" if host == prefeitura else "camara"}
    return {}


def announce(*parts):
    print(*parts, flush=True)


def url_key(url):
    return url.rstrip("/")


def host_key(url):
    return (urlsplit(url).hostname or "").lower().removeprefix("www.")


def converted_stats(entries):
    """Mesmo pré-filtro do coletor (resumo, data e URL), sem gerar textos."""
    links, dates, summaries, eligible = set(), 0, 0, 0
    now = time.time()
    for entry in entries[:50]:
        if not isinstance(entry, Mapping):
            continue
        link = entry.get("link") or entry.get("id", "")
        try:
            url = canonical_url(link)
            if host_key(url) == "politepaul.com":
                continue
        except (ConfigError, ValueError, TypeError):
            continue
        links.add(url)
        text_ok = len(plain(entry.get("summary", ""))[:20000]) >= 180
        stamp = entry.get("published_parsed") or entry.get("updated_parsed")
        published = calendar.timegm(stamp) if stamp else None
        dates += published is not None
        summaries += text_ok
        eligible += text_ok and published is not None and now-72*3600 <= published <= now+300
    return {"links": links, "dates": dates, "summaries": summaries, "eligible": eligible,
            "domains": sorted({host_key(url) for url in links})}


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
    """A tentativa de HTML não pode consumir todo o orçamento dos caminhos RSS."""
    candidates, current = [], site
    source_deadline = client.deadline
    # Com 20 s por fonte, reserva a maior parte para testar o XML diretamente.
    # O teto continua global; não aumenta o tempo de execução por órgão.
    html_seconds = max(1, min(6, (source_deadline-time.monotonic())/3))
    client.deadline = min(source_deadline, time.monotonic()+html_seconds)
    try:
        for _ in range(4):
            public_feed_host(current)
            response = client.request("GET", current, "rss", stream=True)
            try:
                if response.status_code in {301, 302, 303, 307, 308}:
                    current = feed_redirect_url(current, response.headers.get("Location", ""))
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
        # As exceções de ConfigError/Deferred/StopRun são mensagens locais do
        # coletor, sem URL com token, corpo de resposta ou chaves de API.
        reason = str(error) if isinstance(error, (ConfigError, Deferred, ItemError, StopRun)) else type(error).__name__
        announce("  AVISO: descoberta no HTML indisponível:", reason,
                 "| continuando a verificação direta dos caminhos RSS.")
    finally:
        client.deadline = source_deadline
    for base in dict.fromkeys([site, current]):
        candidates.extend(urljoin(base, path) for path in (
            "feed/", "feed", "rss", "rss.xml", "atom.xml", "?feed=rss2", "rss.php", "feed.xml", "rss2.xml",
            "index.php?format=feed&type=rss",
            "rss/", "rss.xml", "?format=feed&type=rss"
        ))
    normalized = []
    for candidate in candidates:
        try:
            normalized.append(https_feed_url(candidate))
        except (ConfigError, ValueError):
            continue
    return list(dict.fromkeys(normalized))


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


# Lista oficial reafirmada pelo responsável em 28/10/2026 (22 órgãos).
OFFICIAL_CITIES = ('medeiros-neto','ibirapua','lajedao','vereda','itamaraju','alcobaca',
                   'caravelas','nova-vicosa','mucuri','porto-seguro','eunapolis')


def configure_municipal(feeds,client,name,category,address,seconds):
    metadata=government_metadata(address)
    matches=[f for f in feeds if host_key(f.get('site_url',''))==host_key(address)
             or host_key(f.get('url',''))==host_key(address)
             or (f.get('city_slug')==metadata['city_slug'] and f.get('source_type')==metadata['source_type'])
             or f.get('name','').casefold()==name.casefold()]
    if len(matches)>1:
        announce('EXIGE REVISÃO:',name,'| múltiplos cadastros existentes; nenhum novo criado.')
        return False,False
    existing=matches[0] if matches else None
    if not existing and len(feeds)>=MAX_SOURCES:return False,False
    finish=time.monotonic()+seconds
    client.deadline=time.monotonic()+max(5,seconds*0.6)
    candidates=[]
    if existing and existing.get('transport','rss')=='rss':candidates.append(existing['url'])
    candidates+=discover_candidates(client,address)
    selected=None;transport='rss';entries=[]
    for candidate in dict.fromkeys(candidates):
        try:
            client.remaining();entries=client.feed(candidate);selected=candidate;break
        except (ItemError,ConfigError,Deferred,StopRun,ValueError,requests.exceptions.RequestException):
            if time.monotonic()>=client.deadline:break
    if selected is None:
        client.deadline=finish
        try:
            entries=client.html_feed({'name':name,'url':address,'site_url':address})
            selected=client.html_listing_url;transport='html'
        except (ItemError,ConfigError,Deferred,StopRun,ValueError,requests.exceptions.RequestException) as error:
            announce('PENDENTE:',name,'| RSS e HTML não validados |',type(error).__name__)
            return False,False
    source=dict(existing or {})
    source.update(metadata)
    source.update({'id':source.get('id') or 'fonte_'+hashlib.sha256(('https://'+host_key(address)+'/').encode()).hexdigest()[:12],
        'name':source.get('name') or name,'url':selected,'transport':transport,'category':source.get('category') or category,
        'authorized':True,'html_fallback':True,'use_full_content':True,'fetch_article_text':True,'allow_undated':False,
        'max_age_hours':source.get('max_age_hours',72),'publication_mode':'auto','auto_min_chars':600,
        'auto_max_age_hours':24,'seo_required':True})
    covers=Path(__file__).with_name('capas_editorias.json')
    if covers.is_file():
        cover=json.loads(covers.read_text()).get(source['category'])
        if cover and not source.get('fallback_image'):source['fallback_image']=cover
    changed=source!=existing
    if existing is not None:
        existing.clear();existing.update(source)
    else:feeds.append(source)
    announce('JÁ CADASTRADA / VALIDADA:' if existing is not None else 'FONTE VALIDADA:',
             name,'|',transport.upper(),'| entradas com texto/data:' if transport=='html' else '| entradas RSS:',len(entries),'| ID:',source['id'])
    if not entries:announce('  MONITORAMENTO SEM ENTRADAS: nenhuma notícia será criada até haver matéria elegível.')
    return changed,True


def configure(path, client, definitions=FONTES, seconds=60):
    load_sources(path)
    original = path.read_text(encoding="utf-8")
    data = json.loads(original)
    feeds = data["feeds"]
    added, failures, converted_links = 0, [], set()
    for name, category, address, discover in definitions:
        if government_metadata(address) and hasattr(client,'html_feed'):
            changed,success=configure_municipal(feeds,client,name,category,address,seconds)
            added+=int(changed)
            if not success:failures.append(name)
            continue
        existing = next((feed for feed in feeds if
            url_key(feed.get("url", "")) == url_key(address) or
            (discover and (host_key(feed.get("url", "")) == host_key(address) or
                           host_key(feed.get("site_url", "")) == host_key(address)))), None)
        if existing is not None:
            if existing.get("authorized") is not True:
                client.deadline = time.monotonic()+seconds
                try:
                    client.feed(existing["url"])
                except (ItemError, ConfigError, Deferred, StopRun, requests.exceptions.RequestException) as error:
                    failures.append(name)
                    announce("NÃO REATIVADA:", name, "|", type(error).__name__)
                    continue
                existing["authorized"] = True
                added += 1
            announce("JÁ CADASTRADA:", name, "|", existing["url"],
                     "| habilitada:", existing.get("authorized") is True)
            continue
        if len(feeds) >= MAX_SOURCES:
            announce("NÃO ADICIONADA:", name, "| limite de", MAX_SOURCES, "fontes.")
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
            publisher = name
            if host_key(address) == "politepaul.com":
                stats = converted_stats(entries)
                announce("  RESUMOS >=180:", stats["summaries"], "| COM DATA:", stats["dates"],
                         "| PASSAM NO PRÉ-FILTRO:", stats["eligible"], "(até 50 entradas)")
                announce("  DOMÍNIOS ORIGINAIS:", ", ".join(stats["domains"]) or "nenhum")
                announce("  LINKS REPETIDOS ENTRE CONVERTIDOS:", len(stats["links"] & converted_links))
                converted_links.update(stats["links"])
                if not stats["links"]:
                    announce("  Sem links HTTP(S) para matérias originais; exige revisão.")
                    continue
                if not stats["eligible"]:
                    announce("  AVISO: RSS válido, mas nenhum item passa no pré-filtro atual.")
                publisher = "Portal TX" if stats["domains"] == ["portaltx.site"] else ", ".join(stats["domains"][:2])
            source_id = "fonte_"+hashlib.sha256(address.encode()).hexdigest()[:12]
            if any(feed.get("id") == source_id for feed in feeds):
                announce("  ID já utilizado; exige revisão manual.")
                break
            source = {
                "id": source_id, "name": publisher, "url": candidate,
                "category": category, "authorized": True,
                "use_full_content": False, "allow_undated": False,
                "max_age_hours": 72,
                "publication_mode": "draft",
            }
            source.update(government_metadata(address))
            feeds.append(source)
            added += 1
            found = True
            announce("  RSS VALIDADO:", candidate, "| entradas:", len(entries))
            if not entries:
                announce("  AVISO: XML válido, mas sem entradas; nenhuma matéria poderá ser coletada deste feed agora.")
            if publisher != name:
                announce("  FONTE NO CADASTRO:", publisher)
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
    selection = parser.add_mutually_exclusive_group()
    selection.add_argument("--only-local", action="store_true",
                           help="Verifica apenas Portal TX e Vem Vê TX Bahia.")
    selection.add_argument("--only-converted", action="store_true",
                           help="Verifica apenas os três XMLs informados via PolitePaul.")
    selection.add_argument("--official-government", action="store_true",
                           help="Verifica os 22 órgãos da lista oficial: RSS primeiro, HTML como alternativa; sem duplicar cadastros.")
    selection.add_argument("--only-government", action="store_true",
                           help="Descobre RSS das prefeituras e câmaras indicadas; novas fontes ficam em draft.")
    parser.add_argument("--city", action="append", choices=[x[1] for x in MUNICIPIOS],
                        help="Com --only-government, verifica somente esta cidade; pode repetir.")
    args = parser.parse_args()
    if args.city and not args.only_government:
        parser.error("--city exige --only-government.")
    os.umask(0o077)
    client = Client("", "", "rss-check", "https://giro73.com.br/wp-json/g73/v1/publish",
                    time.monotonic()+args.timeout, attempts=1)
    try:
        definitions = (government_definitions(OFFICIAL_CITIES) if args.official_government else
                       government_definitions(args.city) if args.only_government else
                       CONVERTIDAS if args.only_converted else
                       [source for source in FONTES if source[3]] if args.only_local else FONTES+CONVERTIDAS)
        return configure(args.config, client, definitions=definitions, seconds=args.timeout)
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
