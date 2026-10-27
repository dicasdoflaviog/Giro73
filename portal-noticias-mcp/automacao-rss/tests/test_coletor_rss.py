import importlib.util
import copy
import json
from pathlib import Path
import sys
import tempfile
import sqlite3
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
        c = m.Client('chave-openrouter-teste', 'token-wp-teste', 'openai/gpt-4o-mini',
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
    def test_openrouter_503_repete_e_recupera(self):
        c = self.client()
        c.session.request = Mock(side_effect=[response(503, headers={'Retry-After': '1'}), response(200, {'ok': True})])
        with patch.object(m.time, 'sleep') as sleep, patch.object(m.random, 'uniform', return_value=0):
            self.assertEqual(c.openrouter_json('models'), {'ok': True})
        self.assertEqual(c.session.request.call_count, 2)
        sleep.assert_called_once_with(3)
        headers = c.session.request.call_args.kwargs['headers']
        self.assertEqual(headers['Authorization'], 'Bearer chave-openrouter-teste')
        self.assertEqual(c.session.request.call_args.args[1], 'https://openrouter.ai/api/v1/models')
        self.assertNotIn('chave-openrouter-teste', c.session.request.call_args.args[1])
    def test_openrouter_503_persistente_tem_limite(self):
        c = self.client();c.session.request = Mock(return_value=response(503))
        with patch.object(m.time, 'sleep') as sleep:
            with self.assertRaises(m.Deferred):c.openrouter_json('models')
        self.assertEqual(c.session.request.call_count, 4)
        self.assertEqual(sleep.call_count, 3)
    def test_modelo_inexistente_nao_tem_fallback_silencioso(self):
        c = self.client();c.session.request = Mock(side_effect=[response(200,{'data':{}}),response(200,{'data':[]})])
        with self.assertRaises(m.StopRun):c.check_model()
        self.assertEqual(c.model, 'openai/gpt-4o-mini')
        self.assertEqual(c.session.request.call_count, 2)
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
    def test_fluxo_rss_openrouter_wordpress_e_segunda_coleta(self):
        from email.utils import formatdate
        xml = ('<rss version="2.0"><channel><title>Fonte</title><link>https://fonte.example/</link>'
               '<description>Fonte autorizada</description><item><title>Notícia regional</title>'
               '<link>https://fonte.example/noticia</link><guid>guid-integracao</guid>'
               '<pubDate>'+formatdate(time.time()-60, usegmt=True)+'</pubDate>'
               '<enclosure url="https://fonte.example/capa.jpg" type="image/jpeg" />'
               '<description><![CDATA['+'Conteúdo factual da fonte. '*20+']]></description></item></channel></rss>').encode()
        c = self.client();published = []
        def remote(method, url, **kwargs):
            if url == 'https://fonte.example/feed':
                r = response(200);r.iter_content.return_value = [xml];return r
            if url == 'https://fonte.example/capa.jpg':
                image=m.BytesIO();m.Image.new('RGB',(400,250),(80,100,140)).save(image,format='JPEG')
                r=response(200);r.iter_content.return_value=[image.getvalue()];return r
            if url == 'https://openrouter.ai/api/v1/key':
                return response(200, {'data': {'limit_remaining':100}})
            if url == 'https://openrouter.ai/api/v1/models':
                return response(200, {'data': [{'id':'openai/gpt-4o-mini','supported_parameters':['response_format']}]})
            if url == 'https://openrouter.ai/api/v1/chat/completions':
                self.assertEqual(kwargs['headers']['Authorization'], 'Bearer chave-openrouter-teste')
                self.assertEqual(kwargs['json']['model'], 'openai/gpt-4o-mini')
                self.assertEqual(kwargs['json']['response_format']['type'],'json_schema')
                return response(200, {'choices': [{'finish_reason': 'stop', 'message': {'content': json.dumps(generated())}}]})
            if url == c.endpoint:
                self.assertEqual(kwargs['json']['status'], 'draft')
                self.assertEqual(set(kwargs['json']['meta']), set(m.SEO_FIELDS))
                self.assertNotIn('Authorization', kwargs['headers'])
                self.assertEqual(kwargs['json']['image_url'],'https://fonte.example/capa.jpg')
                self.assertIn('<figure class="g73-imported-image">',kwargs['json']['content'])
                published.append(kwargs['json']);return response(201, {'id': 88, 'status': 'draft'})
            self.fail('Destino inesperado')
        c.session.request = Mock(side_effect=remote)
        source = dict(self.source, url='https://fonte.example/feed', name='Fonte autorizada', max_age_hours=72)
        with patch.object(m, 'public_feed_host'), patch.object(m, 'public_asset_host'):
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

    def test_config_aceita_cem_fontes_e_rejeita_excesso(self):
        f = Path(self.temp.name)/'many-feeds.json'
        def source(i):
            return {'id':f'fonte_{i}', 'url':f'https://example.org/feed/{i}',
                    'category':'noticias-regiao', 'authorized':True}
        feeds=[source(i) for i in range(m.MAX_SOURCES)]
        f.write_text(json.dumps({'feeds':feeds}))
        self.assertEqual(len(m.load_sources(f)),m.MAX_SOURCES)
        f.write_text(json.dumps({'feeds':feeds+[source(m.MAX_SOURCES)]}))
        with self.assertRaisesRegex(m.ConfigError,'100 fontes'):m.load_sources(f)

    def prepare_image_item(self, url='https://fonte.example/noticia', guid='imagem-1'):
        self.store.discover(self.source, entry(url, guid))
        key = self.store.db.execute('SELECT id FROM items WHERE article=?', (url,)).fetchone()[0]
        payload = m.payload_from(generated(), entry(url, guid), 'noticias-regiao')
        payload['image_url'] = 'https://prado.ba.gov.br/foto.jpeg'
        self.store.prepare(key, payload)
        return key, payload

    def test_422_de_imagem_reconhecido_com_e_sem_prefixo(self):
        c = self.client()
        for code in ('image_editor', 'image_download', 'image_dimensions', 'image_host'):
            for prefix in ('', 'g73_'):
                with self.subTest(code=prefix+code):
                    c.session.request = Mock(return_value=response(422, {'code': prefix+code}))
                    with self.assertRaises(m.ImageFallback):
                        c.submit('a'*64, {'status': 'draft', 'image_url': 'https://prado.ba.gov.br/foto.jpeg'})
                    self.assertEqual(c.session.request.call_count, 1)

    def test_fallback_persiste_antes_do_segundo_post_e_salva_permalink(self):
        key, original = self.prepare_image_item()
        c = self.client(); c.generate = Mock(); sends = []
        def remote(method, url, **kwargs):
            sends.append(copy.deepcopy(kwargs))
            if len(sends) == 1:
                self.assertTrue(kwargs['json']['image_url'])
                return response(422, {'code': 'g73_image_editor'})
            cached = json.loads(self.store.db.execute('SELECT payload FROM items WHERE id=?', (key,)).fetchone()[0])
            self.assertEqual(cached, kwargs['json'])
            self.assertEqual(cached['image_url'], '')
            return response(201, {'id': 901, 'status': 'draft', 'permalink': 'https://giro73.com.br/?p=901'})
        c.session.request = Mock(side_effect=remote)
        with patch.object(m.time, 'sleep') as sleep:
            self.assertEqual(m.process(c, self.store, 1), (1, False))
        sleep.assert_not_called(); c.generate.assert_not_called()
        self.assertEqual(len(sends), 2)
        self.assertEqual(sends[0]['headers']['Idempotency-Key'], sends[1]['headers']['Idempotency-Key'])
        self.assertEqual(sends[1]['json'], dict(original, image_url=''))
        saved = self.store.db.execute('SELECT * FROM items WHERE id=?', (key,)).fetchone()
        self.assertEqual((saved['status'], saved['wp_id'], saved['wp_url']), ('sent', 901, 'https://giro73.com.br/?p=901'))

    def test_fallback_com_resposta_perdida_reinicia_sem_imagem_e_sem_duplicar(self):
        key, original = self.prepare_image_item()
        c = self.client(); c.attempts = 1; c.generate = Mock(); sends = []; server = {}
        def remote(method, url, **kwargs):
            sends.append(copy.deepcopy(kwargs))
            if len(sends) == 1:
                return response(422, {'code': 'g73_image_download'})
            self.assertEqual(kwargs['json']['image_url'], '')
            idem = kwargs['headers']['Idempotency-Key']
            server.setdefault(idem, 902)
            if len(sends) == 2:
                raise m.requests.exceptions.Timeout('Resposta perdida')
            return response(200, {'id': server[idem], 'status': 'draft', 'permalink': 'https://giro73.com.br/?p=902'})
        c.session.request = Mock(side_effect=remote)
        self.assertEqual(m.process(c, self.store, 1), (0, True))
        row = self.store.db.execute('SELECT * FROM items WHERE id=?', (key,)).fetchone()
        self.assertEqual(row['status'], 'prepared')
        self.assertEqual(json.loads(row['payload'])['image_url'], '')
        self.store.close(); self.store = m.Store(self.path)
        with self.store.db:self.store.db.execute('UPDATE items SET next_try=0')
        self.assertEqual(m.process(c, self.store, 1), (1, False))
        self.assertEqual(sends[1]['json'], sends[2]['json'])
        self.assertEqual(sends[1]['headers'], sends[2]['headers'])
        self.assertEqual(len(server), 1); c.generate.assert_not_called()

    def test_outros_422_e_segundo_422_nao_entram_em_loop(self):
        c = self.client()
        for payload, code in [({'status': 'draft', 'image_url': ''}, 'g73_image_host'),
                              ({'status': 'draft', 'image_url': 'https://prado.ba.gov.br/foto.jpeg'}, 'g73_meta_invalid')]:
            c.session.request = Mock(return_value=response(422, {'code': code}))
            with self.assertRaises(m.ItemError) as error:c.submit('a'*64, payload)
            self.assertNotIsInstance(error.exception, m.ImageFallback)
            self.assertEqual(c.session.request.call_count, 1)
        self.prepare_image_item()
        c.session.request = Mock(return_value=response(422, {'code': 'g73_image_dimensions'}))
        self.assertEqual(m.process(c, self.store, 1), (0, True))
        self.assertEqual(c.session.request.call_count, 2)
        self.assertEqual(self.store.counts(), {'blocked': 1})

    def test_fallback_nao_impede_proximo_item_da_fila(self):
        self.prepare_image_item()
        self.prepare_image_item('https://fonte.example/noticia-2', 'imagem-2')
        c = self.client()
        c.session.request = Mock(side_effect=[
            response(422, {'code': 'g73_image_host'}),
            response(201, {'id': 903, 'status': 'draft'}),
            response(201, {'id': 904, 'status': 'draft'}),
        ])
        self.assertEqual(m.process(c, self.store, 2), (2, False))
        self.assertEqual(self.store.counts(), {'sent': 2})

    def test_migracao_aditiva_preserva_fila_antiga(self):
        self.store.close()
        self.path.unlink()
        db = sqlite3.connect(self.path)
        db.execute('CREATE TABLE items (id TEXT PRIMARY KEY, source TEXT NOT NULL, article TEXT NOT NULL, category TEXT NOT NULL, input_json TEXT NOT NULL, status TEXT NOT NULL DEFAULT "queued", payload TEXT, wp_id INTEGER, attempts INTEGER NOT NULL DEFAULT 0, next_try REAL NOT NULL DEFAULT 0, last_error TEXT, created_at REAL NOT NULL, updated_at REAL NOT NULL)')
        db.execute('INSERT INTO items (id,source,article,category,input_json,created_at,updated_at) VALUES (?,?,?,?,?,?,?)',
                   ('antigo','fonte','https://fonte.example/antigo','noticias-regiao','{}',time.time(),time.time()))
        db.commit(); db.close()
        self.store = m.Store(self.path)
        self.assertEqual(self.store.counts(), {'queued': 1})
        self.store.finish('antigo', 'sent', wp_id=905, wp_url='https://giro73.com.br/?p=905')
        saved = self.store.db.execute('SELECT * FROM items WHERE id="antigo"').fetchone()
        self.assertEqual(saved['wp_url'], 'https://giro73.com.br/?p=905')

    def test_recupera_apenas_bloqueados_por_422_de_imagem(self):
        image, payload = self.prepare_image_item()
        self.store.finish(image, 'blocked', error='WordPress HTTP 422: g73_image_download')
        other, _ = self.prepare_image_item('https://fonte.example/outro', 'outro')
        self.store.finish(other, 'blocked', error='WordPress HTTP 422: g73_meta_invalid')
        self.assertEqual(self.store.retry_image_errors(), 1)
        saved = self.store.db.execute('SELECT * FROM items WHERE id=?', (image,)).fetchone()
        self.assertEqual(saved['status'], 'prepared')
        self.assertEqual(json.loads(saved['payload']), dict(payload, image_url=''))
        self.assertEqual(self.store.counts(), {'prepared': 1, 'blocked': 1})
        self.assertEqual(self.store.retry_image_errors(), 0)

    def test_recuperacao_nao_reabre_item_ja_sem_imagem_ou_com_id(self):
        first, payload = self.prepare_image_item()
        payload['image_url'] = ''
        self.store.prepare(first, payload)
        self.store.finish(first, 'blocked', error='WordPress HTTP 422: g73_image_editor')
        second, _ = self.prepare_image_item('https://fonte.example/outro', 'outro')
        self.store.finish(second, 'blocked', wp_id=999, error='WordPress HTTP 422: g73_image_editor')
        self.assertEqual(self.store.retry_image_errors(), 0)

if __name__ == '__main__':
    unittest.main()
