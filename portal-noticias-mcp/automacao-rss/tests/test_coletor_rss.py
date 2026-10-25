import importlib.util
import json
from pathlib import Path
import sys
import tempfile
import time
import unittest
from unittest.mock import Mock, patch

FILE = Path(__file__).resolve().parents[1]/'coletor_rss.py'
spec = importlib.util.spec_from_file_location('coletor_rss', FILE)
m = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = m
spec.loader.exec_module(m)

def response(status, data=None, headers=None):
    r = Mock(status_code=status, headers=headers or {})
    r.json.return_value = data or {}
    return r

def generated():
    return {'apto': True, 'observacao': '', 'title': 'Informação regional verificada',
            'excerpt': 'Resumo factual para revisão editorial.', 'blocks': [
                {'type': 'p', 'text': 'Conteúdo factual fornecido pela fonte, sem eventos adicionais.'}],
            'tags': ['Bahia'], 'meta': dict(zip(m.SEO_FIELDS, ['notícia regional', 'Título SEO regional', 'Descrição factual para o Rank Math.'])),
            'status': 'publish'}  # Campo inesperado da IA nunca pode alterar o status.

def entry(url='https://fonte.example/noticia?utm_source=rss', guid='id-1'):
    return {'url': url, 'guid': guid, 'publisher': 'Fonte autorizada', 'source_title': 'Notícia',
            'source_text': 'Informação factual. '*20, 'published_at': time.time()-100,
            'max_age_hours': 72}

class CollectorTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.path = Path(self.temp.name)/'vistos.sqlite3'
        self.store = m.Store(self.path)
        self.source = {'id': 'fonte-1', 'category': 'noticias-regiao'}
    def tearDown(self):
        self.store.close()
        self.temp.cleanup()
    def client(self):
        c = m.Client('chave-google-teste', 'token-wp-teste', 'gemini-3.8-flash',
                     'https://giro73.com.br/wp-json/g73/v1/publish', time.monotonic()+600)
        self.addCleanup(c.session.close)
        return c
    def test_guid_e_url_sem_tracking_nao_duplicam(self):
        self.assertTrue(self.store.discover(self.source, entry()))
        self.assertFalse(self.store.discover(self.source, entry('https://fonte.example/outro-endereco', 'id-1')))
        self.assertFalse(self.store.discover({'id': 'outro-feed', 'category': 'politica'},
            entry('https://fonte.example/noticia?fbclid=abc', 'outro-guid')))
        self.assertEqual(self.store.counts(), {'queued': 1})
    def test_status_e_seo_sao_controlados(self):
        out = m.payload_from(generated(), entry(), 'politica')
        self.assertEqual(out['status'], 'draft')
        self.assertEqual(set(out['meta']), set(m.SEO_FIELDS))
        self.assertEqual(out['category'], 'politica')
        self.assertEqual(out['image_url'], '')
        self.assertIn('Fonte autorizada', out['content'])
    def test_html_da_ia_nao_executa_scripts(self):
        g = generated();g['blocks'][0]['text'] = '<script>executar()</script><b>Informação factual</b>'
        out = m.payload_from(g, entry(), 'politica')
        self.assertNotIn('<script>', out['content'])
        self.assertNotIn('executar()', out['content'])
        self.assertIn('Informação factual', out['content'])
    def test_meta_incompleto_bloqueia_item(self):
        g = generated();del g['meta']['rank_math_title']
        with self.assertRaises(m.ItemError):m.payload_from(g, entry(), 'politica')
    def test_google_503_repete_e_recupera(self):
        c = self.client()
        c.session.request = Mock(side_effect=[response(503, headers={'Retry-After': '1'}), response(200, {'ok': True})])
        with patch.object(m.time, 'sleep') as sleep, patch.object(m.random, 'uniform', return_value=0):
            self.assertEqual(c.google_json('models/gemini-3.8-flash'), {'ok': True})
        self.assertEqual(c.session.request.call_count, 2)
        sleep.assert_called_once_with(3)
        headers = c.session.request.call_args.kwargs['headers']
        self.assertEqual(headers['x-goog-api-key'], 'chave-google-teste')
        self.assertNotIn('chave-google-teste', c.session.request.call_args.args[1])
    def test_google_503_persistente_tem_limite(self):
        c = self.client();c.session.request = Mock(return_value=response(503))
        with patch.object(m.time, 'sleep') as sleep:
            with self.assertRaises(m.Deferred):c.google_json('models/gemini-3.8-flash')
        self.assertEqual(c.session.request.call_count, 4)
        self.assertEqual(sleep.call_count, 3)
    def test_modelo_inexistente_nao_tem_fallback_silencioso(self):
        c = self.client();c.session.request = Mock(return_value=response(404))
        with self.assertRaises(m.StopRun):c.check_model()
        self.assertEqual(c.model, 'gemini-3.8-flash')
        self.assertEqual(c.session.request.call_count, 1)
    def test_503_de_configuracao_wordpress_nao_repete(self):
        c = self.client();c.session.request = Mock(return_value=response(503, {'code': 'g73_disabled'}))
        with patch.object(m.time, 'sleep') as sleep:
            with self.assertRaises(m.StopRun):c.submit('a'*64, {'status': 'draft'})
        self.assertEqual(c.session.request.call_count, 1)
        sleep.assert_not_called()
    def test_409_busy_repete_mas_conflito_nao(self):
        c = self.client();c.session.request = Mock(side_effect=[response(409, {'code': 'g73_busy'}), response(201, {'id': 321, 'status': 'draft'})])
        with patch.object(m.time, 'sleep'):
            self.assertEqual(c.submit('a'*64, {'status': 'draft'}), 321)
        self.assertEqual(c.session.request.call_args.kwargs['headers']['Idempotency-Key'], 'rss-'+'a'*64)
        c.session.request = Mock(return_value=response(409, {'code': 'g73_idempotency_conflict'}))
        with self.assertRaises(m.ItemError):c.submit('a'*64, {'status': 'draft'})
        self.assertEqual(c.session.request.call_count, 1)
    def test_redirect_de_sessao_preserva_post_e_destino(self):
        c = self.client();c.session.request = Mock(side_effect=[response(307, headers={'Location': c.endpoint}), response(201, {'id': 3, 'status': 'draft'})])
        self.assertEqual(c.submit('a'*64, {'status': 'draft'}), 3)
        self.assertEqual(c.session.request.call_args_list[0].kwargs['json'], c.session.request.call_args_list[1].kwargs['json'])
    def test_resposta_perdida_preserva_payload_entre_execucoes(self):
        self.store.discover(self.source, entry())
        server = {}
        class Fake:
            generates = 0
            sends = []
            lost = False
            def remaining(self):return 600
            def generate(self, source):self.generates += 1;return generated()
            def submit(self, key, payload):
                self.sends.append((key, json.dumps(payload, sort_keys=True)))
                server.setdefault(key, 777)
                if not self.lost:self.lost = True;raise m.Deferred('Resposta perdida após criação')
                return server[key]
        c = Fake();m.process(c, self.store, 5)
        row = self.store.db.execute('SELECT * FROM items').fetchone()
        self.assertEqual(row['status'], 'prepared')
        self.store.close();self.store = m.Store(self.path)
        with self.store.db:self.store.db.execute('UPDATE items SET next_try=0')
        self.assertEqual(m.process(c, self.store, 5), (1, False))
        self.assertEqual(c.generates, 1)
        self.assertEqual(c.sends[0], c.sends[1])
        self.assertEqual(len(server), 1)
        self.assertEqual(self.store.counts(), {'sent': 1})
        self.assertFalse(self.store.discover(self.source, entry()))
    def test_dry_run_prepara_sem_enviar(self):
        self.store.discover(self.source, entry())
        c = Mock();c.generate.return_value = generated()
        m.process(c, self.store, 5, dry_run=True)
        c.submit.assert_not_called()
        self.assertEqual(self.store.counts(), {'prepared': 1})
    def test_fonte_insuficiente_nao_vira_artigo(self):
        self.store.discover(self.source, entry())
        c = Mock();c.generate.return_value = {'apto': False}
        m.process(c, self.store, 5)
        c.submit.assert_not_called()
        self.assertEqual(self.store.counts(), {'skipped': 1})
    def test_status_publish_local_e_recusado(self):
        c = self.client();c.session.request = Mock()
        with self.assertRaises(m.StopRun):c.submit('a'*64, {'status': 'publish'})
        c.session.request.assert_not_called()
    def test_endpoint_retornando_publicado_interrompe(self):
        c = self.client();c.session.request = Mock(return_value=response(201, {'id': 55, 'status': 'publish'}))
        with self.assertRaises(m.StopRun):c.submit('a'*64, {'status': 'draft'})
    def test_fluxo_rss_gemini_wordpress_e_segunda_coleta(self):
        from email.utils import formatdate
        xml = ('<rss version="2.0"><channel><title>Fonte</title><link>https://fonte.example/</link>'
               '<description>Fonte autorizada</description><item><title>Notícia regional</title>'
               '<link>https://fonte.example/noticia</link><guid>guid-integracao</guid>'
               '<pubDate>'+formatdate(time.time()-60, usegmt=True)+'</pubDate>'
               '<description><![CDATA['+'Conteúdo factual da fonte. '*20+']]></description></item></channel></rss>').encode()
        c = self.client();published = []
        def remote(method, url, **kwargs):
            if url == 'https://fonte.example/feed':
                r = response(200);r.iter_content.return_value = [xml];return r
            if url.endswith(':generateContent'):
                self.assertEqual(kwargs['headers'], {'x-goog-api-key': 'chave-google-teste'})
                return response(200, {'candidates': [{'finishReason': 'STOP', 'content': {'parts': [{'text': json.dumps(generated())}]}}]})
            if url.endswith('/models/gemini-3.8-flash'):
                return response(200, {'supportedGenerationMethods': ['generateContent']})
            if url == c.endpoint:
                self.assertEqual(kwargs['json']['status'], 'draft')
                self.assertEqual(set(kwargs['json']['meta']), set(m.SEO_FIELDS))
                self.assertNotIn('x-goog-api-key', kwargs['headers'])
                published.append(kwargs['json']);return response(201, {'id': 88, 'status': 'draft'})
            self.fail('Destino inesperado')
        c.session.request = Mock(side_effect=remote)
        source = dict(self.source, url='https://fonte.example/feed', name='Fonte autorizada', max_age_hours=72)
        with patch.object(m, 'public_feed_host'):
            self.assertEqual(m.discover(c, self.store, [source]), (1, 0))
            self.assertEqual(m.process(c, self.store, 5), (1, False))
            self.assertEqual(m.discover(c, self.store, [source]), (0, 0))
            self.assertEqual(m.process(c, self.store, 5), (0, False))
        self.assertEqual(len(published), 1)
        self.assertEqual(self.store.counts(), {'sent': 1})
    def test_feed_com_erro_nao_impede_demais_fontes(self):
        c = Mock();c.feed.side_effect = [m.StopRun('Certificado inválido'), []]
        sources = [dict(self.source, url='https://fonte.example/feed'),
                   {'id': 'outra', 'url': 'https://outra.example/feed', 'category': 'politica'}]
        self.assertEqual(m.discover(c, self.store, sources), (0, 1))
        self.assertEqual(c.feed.call_count, 2)
    def test_lock_impede_segunda_execucao(self):
        with open(str(self.path)+'.lock', 'a+') as lock:
            m.fcntl.flock(lock, m.fcntl.LOCK_EX | m.fcntl.LOCK_NB)
            with patch.object(m, 'Store') as constructor:
                self.assertEqual(m.main(['--state', str(self.path), '--status']), 0)
                constructor.assert_not_called()
    def test_config_sem_autorizacao_nao_e_aceita(self):
        f = Path(self.temp.name)/'feeds.json';f.write_text(json.dumps({'feeds': [{'authorized': False}]}))
        with self.assertRaises(m.ConfigError):m.load_sources(f)

if __name__ == '__main__':
    unittest.main()
