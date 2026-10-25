import contextlib
import io
import json
from pathlib import Path
import sys
import tempfile
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

    def test_declared_comment_feed_is_ignored(self):
        parser = config.FeedLinks()
        parser.feed('<link type="application/rss+xml" href="/comments/feed/">'
                    '<link type="application/atom+xml" href="/atom.xml">')
        self.assertEqual(parser.links, ["/atom.xml"])

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
