import contextlib
import io
import json
from pathlib import Path
import sys
import tempfile
import time
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import configurar_fontes_rss as config
from coletor_rss import ItemError


class Response:
    def __init__(self, text="", status=200, headers=None):
        self.content = text.encode()
        self.status_code = status
        self.headers = headers or {}
        self.encoding = "utf-8"
        self.closed = False

    def iter_content(self, size):
        yield self.content

    def close(self):
        self.closed = True


class Client:
    def __init__(self, feeds=None, pages=None):
        self.feeds = feeds or {}
        self.pages = pages or {}
        self.calls = []

    def remaining(self):
        return 30

    def feed(self, url):
        self.calls.append(url)
        if url not in self.feeds:
            raise ItemError("Feed HTTP 404")
        return self.feeds[url]

    def request(self, method, url, service, **kwargs):
        assert method == "GET" and service == "rss"
        self.calls.append(url)
        return self.pages.get(url, Response(status=403))


class ConfigureTests(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.path = Path(self.directory.name)/"feeds.json"
        self.original = {"feeds": [{
            "id": "fonte_regional_autorizada", "name": "Liberdade News",
            "url": "https://liberdadenews.com.br/?format=feed&type=rss",
            "category": "noticias-regiao", "authorized": True,
            "max_age_hours": 72,
        }]}
        self.path.write_text(json.dumps(self.original))

    def run_config(self, client, definitions):
        with contextlib.redirect_stdout(io.StringIO()), patch.object(config, "public_feed_host"):
            return config.configure(self.path, client, definitions)

    def test_discovers_declared_feed_preserves_existing_and_repeats_safely(self):
        definition = [("Portal TX", "noticias-regiao", "https://portaltx.site/", True)]
        page = Response('<link rel="alternate" type="application/rss+xml" href="/noticias.xml">')
        client = Client(feeds={"https://portaltx.site/noticias.xml": [1]},
                        pages={"https://portaltx.site/": page})
        self.assertEqual(self.run_config(client, definition), 0)
        result = json.loads(self.path.read_text())
        self.assertEqual(result["feeds"][0], self.original["feeds"][0])
        self.assertEqual(result["feeds"][1]["url"], "https://portaltx.site/noticias.xml")
        self.assertTrue(page.closed)
        self.assertEqual(self.path.stat().st_mode & 0o777, 0o640)
        backup = list(Path(self.directory.name).glob("*.bak-*"))
        self.assertEqual(len(backup), 1)
        self.assertEqual(json.loads(backup[0].read_text()), self.original)
        client.calls.clear()
        self.assertEqual(self.run_config(client, definition), 0)
        self.assertEqual(client.calls, [])
        self.assertEqual(json.loads(self.path.read_text()), result)

    def test_home_blocked_but_fallback_feed_valid(self):
        definition = [("Vem Vê TX Bahia", "noticias-regiao", "https://vemvetxbahia.com.br/", True)]
        client = Client(feeds={"https://vemvetxbahia.com.br/?feed=rss2": []})
        self.assertEqual(self.run_config(client, definition), 0)
        self.assertEqual(json.loads(self.path.read_text())["feeds"][-1]["url"],
                         "https://vemvetxbahia.com.br/?feed=rss2")

    def test_itapebi_http_redirect_tries_https_www_and_keeps_https_in_config(self):
        site="https://itapebi.ba.gov.br/"
        secure="https://www.itapebi.ba.gov.br/"
        client=Client(feeds={secure+"feed/":[]},pages={
            site:Response(status=301,headers={"Location":"http://www.itapebi.ba.gov.br/"}),
            secure:Response('<link type="application/rss+xml" href="http://www.itapebi.ba.gov.br/feed/">')})
        self.assertEqual(self.run_config(client,[("Prefeitura de Itapebi","noticias-regiao",site,True)]),0)
        self.assertEqual(json.loads(self.path.read_text())["feeds"][-1]["url"],secure+"feed/")
        self.assertTrue(all(url.startswith("https://") for url in client.calls))

    def test_html_timeout_preserves_source_time_for_direct_feed(self):
        clock={"now":100.0}
        site="https://itapebi.ba.gov.br/"
        client=Client(feeds={site+"feed/":[1]})
        def slow_html(*args,**kwargs):
            self.assertLessEqual(client.deadline,106.1)
            clock["now"]=106.0
            raise config.Deferred("Limite de duração da execução atingido.")
        client.request=slow_html
        original_feed=client.feed
        def fallback(url):
            self.assertEqual(client.deadline,120.0)
            self.assertGreater(client.deadline,clock["now"])
            return original_feed(url)
        client.feed=fallback
        with patch.object(config.time,"monotonic",side_effect=lambda:clock["now"]):
            with contextlib.redirect_stdout(io.StringIO()),patch.object(config,"public_feed_host"):
                self.assertEqual(config.configure(self.path,client,
                    [("Prefeitura de Itapebi","noticias-regiao",site,True)],seconds=20),0)

    def test_html_dns_warning_is_explicit_and_fallback_is_still_tried(self):
        site="https://itapebi.ba.gov.br/"
        client=Client(feeds={site+"feed/":[1]})
        client.request=lambda *a,**k:(_ for _ in ()).throw(config.Deferred("Falha de DNS da fonte."))
        output=io.StringIO()
        with contextlib.redirect_stdout(output),patch.object(config,"public_feed_host"):
            result=config.configure(self.path,client,[("Itapebi","noticias-regiao",site,True)])
        self.assertEqual(result,0)
        self.assertIn("Falha de DNS da fonte",output.getvalue())
        self.assertIn("continuando",output.getvalue())

    def test_legacy_php_and_rss_without_trailing_slash(self):
        definitions = [
            ("Portal TX", "noticias-regiao", "https://portaltx.site/", True),
            ("Vem Vê TX Bahia", "noticias-regiao", "https://vemvetxbahia.com.br/", True),
        ]
        client = Client(feeds={
            "https://portaltx.site/rss.php": [1],
            "https://vemvetxbahia.com.br/rss": [1],
        })
        self.assertEqual(self.run_config(client, definitions), 0)
        urls = [feed["url"] for feed in json.loads(self.path.read_text())["feeds"]]
        self.assertIn("https://portaltx.site/rss.php", urls)
        self.assertIn("https://vemvetxbahia.com.br/rss", urls)

    def test_invalid_feeds_do_not_change_config(self):
        before = self.path.read_bytes()
        definitions = [("Sem RSS", "noticias-regiao", "https://example.org/", True)]
        self.assertEqual(self.run_config(Client(), definitions), 1)
        self.assertEqual(self.path.read_bytes(), before)
        self.assertEqual(list(Path(self.directory.name).glob("*.bak-*")), [])

    def test_partial_failure_saves_only_valid_feed(self):
        definitions = [("Válida", "esportes", "https://example.org/esportes.xml", False),
                       ("Inválida", "noticias-regiao", "https://example.org/erro.xml", False)]
        client = Client(feeds={"https://example.org/esportes.xml": [1, 2]})
        self.assertEqual(self.run_config(client, definitions), 1)
        feeds = json.loads(self.path.read_text())["feeds"]
        self.assertEqual(len(feeds), 2)
        self.assertEqual(feeds[-1]["category"], "esportes")

    def test_all_fifteen_definitions_without_duplicate_liberdade(self):
        feeds = {address if not discover else address+"feed/": [1]
                 for _, _, address, discover in config.FONTES}
        client = Client(feeds=feeds)
        self.assertEqual(self.run_config(client, config.FONTES), 0)
        self.assertEqual(len(json.loads(self.path.read_text())["feeds"]), 15)

    def test_government_registry_contains_requested_cities_and_roles(self):
        definitions = config.government_definitions()
        self.assertEqual(len(config.MUNICIPIOS), 19)
        self.assertEqual(len(definitions), 38)
        self.assertEqual(len({x[2] for x in definitions}), 38)
        selected = config.government_definitions(["porto-seguro", "eunapolis"])
        self.assertEqual(len(selected), 4)
        self.assertEqual([x[1] for x in selected],
                         ["noticias-regiao", "politica", "noticias-regiao", "politica"])

    def test_adds_more_than_twenty_valid_feeds_in_draft_and_preserves_old_policy(self):
        self.original["feeds"][0]["publication_mode"] = "auto"
        self.path.write_text(json.dumps(self.original))
        definitions = config.government_definitions()
        client = Client(feeds={address+"feed/": [1] for _, _, address, _ in definitions})
        self.assertEqual(self.run_config(client, definitions), 0)
        result = json.loads(self.path.read_text())["feeds"]
        self.assertEqual(len(result), 39)
        self.assertEqual(result[0], self.original["feeds"][0])
        for source in result[1:]:
            self.assertEqual(source["publication_mode"], "draft")
            self.assertTrue(source["city"])
            self.assertEqual(source["category"],
                             "politica" if source["source_type"] == "camara" else "noticias-regiao")

    def test_redirected_government_feed_is_not_added_again(self):
        definitions = config.government_definitions(["prado"])[:1]
        client = Client(feeds={"https://noticias.prado.ba.gov.br/feed/": [1]},
                        pages={"https://prado.ba.gov.br/": Response(
                            '<link type="application/rss+xml" href="https://noticias.prado.ba.gov.br/feed/">')})
        self.assertEqual(self.run_config(client, definitions), 0)
        client.calls.clear()
        self.assertEqual(self.run_config(client, definitions), 0)
        self.assertEqual(client.calls, [])
        self.assertEqual(len(json.loads(self.path.read_text())["feeds"]), 2)

    def test_capacity_does_not_silently_drop_an_existing_source(self):
        sources=[]
        for i in range(config.MAX_SOURCES-1):
            sources.append(dict(self.original["feeds"][0],id=f"existing_{i}",url=f"https://example.org/feed/{i}"))
        self.path.write_text(json.dumps({"feeds":sources}))
        definitions=config.government_definitions(["porto-seguro"])
        client=Client(feeds={address+"feed/": [1] for _,_,address,_ in definitions})
        self.assertEqual(self.run_config(client,definitions),1)
        result=json.loads(self.path.read_text())["feeds"]
        self.assertEqual(len(result),config.MAX_SOURCES)
        self.assertEqual(result[:config.MAX_SOURCES-1],sources)

    def test_declared_comment_feed_is_ignored(self):
        parser = config.FeedLinks()
        parser.feed('<link type="application/rss+xml" href="/comments/feed/">'
                    '<link type="application/atom+xml" href="/atom.xml">')
        self.assertEqual(parser.links, ["/atom.xml"])

    def test_converted_feeds_use_original_source_and_preserve_dedup_options(self):
        entries = [{"link": "https://portaltx.site/noticia/123",
                    "summary": "Informação regional confirmada. "*10,
                    "published_parsed": time.gmtime()}]
        client = Client(feeds={source[2]: entries for source in config.CONVERTIDAS})
        self.assertEqual(self.run_config(client, config.CONVERTIDAS), 0)
        feeds = json.loads(self.path.read_text())["feeds"]
        self.assertEqual(len(feeds), 4)
        for feed in feeds[1:]:
            self.assertEqual(feed["name"], "Portal TX")
            self.assertEqual(feed["category"], "noticias-regiao")
            self.assertFalse(feed["allow_undated"])
            self.assertFalse(feed["use_full_content"])
        stats = config.converted_stats(entries)
        self.assertEqual(stats["eligible"], 1)
        tracking = config.converted_stats([dict(entries[0], link="https://portaltx.site/noticia/123?utm_source=rss")])
        self.assertEqual(stats["links"], tracking["links"])

    def test_converter_without_original_links_is_not_added(self):
        url = config.CONVERTIDAS[0][2]
        client = Client(feeds={url: [{"link": url, "summary": "Texto suficiente. "*30}]})
        before = self.path.read_bytes()
        self.assertEqual(self.run_config(client, [config.CONVERTIDAS[0]]), 1)
        self.assertEqual(self.path.read_bytes(), before)

    def test_converter_title_only_does_not_pass_prefilter(self):
        entry = {"link": "https://portaltx.site/noticia/1", "title": "Uma notícia",
                 "summary": "Título curto", "content": [{"value": "Texto completo. "*40}]}
        stats = config.converted_stats([entry])
        self.assertEqual(stats["eligible"], 0)
        self.assertEqual(stats["summaries"], 0)
        client = Client(feeds={config.CONVERTIDAS[0][2]: [entry]})
        self.assertEqual(self.run_config(client, [config.CONVERTIDAS[0]]), 0)
        feed = json.loads(self.path.read_text())["feeds"][-1]
        self.assertFalse(feed["allow_undated"])
        self.assertFalse(feed["use_full_content"])

    def test_converter_prefilter_rejects_old_and_future_dates(self):
        entry = {"link": "https://portaltx.site/noticia/1", "summary": "Texto regional. "*30}
        old = dict(entry, published_parsed=time.gmtime(time.time()-73*3600))
        future = dict(entry, published_parsed=time.gmtime(time.time()+3600))
        self.assertEqual(config.converted_stats([old, future])["eligible"], 0)

    def test_simultaneous_config_edit_is_preserved(self):
        definitions = [("Válida", "esportes", "https://example.org/feed/", False)]
        client = Client(feeds={"https://example.org/feed/": [1]})
        real_feed = client.feed
        def edit_config(url):
            data = dict(self.original, edited_elsewhere=True)
            self.path.write_text(json.dumps(data))
            return real_feed(url)
        client.feed = edit_config
        with self.assertRaisesRegex(RuntimeError, "editada"):
            self.run_config(client, definitions)
        self.assertTrue(json.loads(self.path.read_text())["edited_elsewhere"])


if __name__ == "__main__":
    unittest.main()
