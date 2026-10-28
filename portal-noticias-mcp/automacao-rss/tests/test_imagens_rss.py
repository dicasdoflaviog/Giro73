import copy
from io import BytesIO
import json
from pathlib import Path
import sys
import tempfile
import time
import unittest
from unittest.mock import Mock, patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import coletor_rss as m


def photo(width=400, height=250):
    output = BytesIO()
    m.Image.new('RGB', (width, height), (70, 110, 170)).save(output, format='JPEG')
    return output.getvalue()


def generated(keyword='crédito regional'):
    return {'summary_points':['O serviço foi atualizado.','A informação é regional.','Consulte os detalhes na fonte.'], 'apto': True, 'observacao': '', 'title': 'Informação regional verificada',
            'excerpt': 'Resumo factual.', 'blocks': [
                {'type': 'p', 'text': 'Primeiro parágrafo factual.'},
                {'type': 'h2', 'text': 'Informações de serviço'},
                {'type': 'p', 'text': 'Segundo parágrafo factual.'}], 'tags': [],
            'meta': {'rank_math_focus_keyword': keyword, 'rank_math_title': 'Título SEO',
                     'rank_math_description': 'Descrição SEO factual.'}}


def entry():
    return {'url': 'https://fonte.example/noticia', 'guid': 'foto-1', 'publisher': 'Fonte regional',
            'source_text': 'Informação da fonte. '*20, 'source_title': 'Notícia',
            'date_basis':'published','published_at': time.time()-60, 'max_age_hours': 72}


class ImageTests(unittest.TestCase):
    def client(self):
        client = m.Client('openrouter-test', 'wp-test', 'openai/gpt-4o-mini',
                          'https://giro73.com.br/wp-json/g73/v1/publish', time.monotonic()+600)
        self.addCleanup(client.session.close)
        client.preflight=Mock(return_value={"status":"publish"})
        client.original_lead=Mock(side_effect=lambda e:e.get("source_text",""))
        return client

    def test_rejects_social_logo_icon_and_non_image_extensions(self):
        for path in ['facebook.jpg','LOGO.PNG','icon.webp','share.jpg','avatar.jpg','banner-default.png',
                     'documento.pdf','grafico.svg','pagina.html','foto.%70df']:
            with self.subTest(path=path):
                self.assertIsNone(m.clean_image_url('https://fonte.example/'+path))
        self.assertEqual(m.clean_image_url('/Handler.ashx?f=i&query=123','https://camara.example/'),
                         'https://camara.example/Handler.ashx?f=i&query=123')
        self.assertIsNone(m.clean_image_url('http://169.254.169.254/foto.jpg'))

    def test_camara_facebook_og_does_not_replace_article_photo(self):
        markup = ('<meta property="og:image" content="/facebook.jpg">'
                  '<header><img src="/identidade.jpg" width="500" height="500"></header>'
                  '<article><img class="share" src="/arquivo1.jpg">'
                  '<img src="/Handler.ashx?f=i&amp;query=123" width="800" height="450" alt="Obras na cidade"></article>')
        candidates = m.html_image_candidates(markup,'https://camara.example/noticia',article=True)
        self.assertEqual(candidates,[{'url':'https://camara.example/Handler.ashx?f=i&query=123'}])

    def test_srcset_prefers_larger_image_and_accepts_lazy_loading(self):
        markup = '<article><img data-src="/foto.jpg" srcset="/foto-400.jpg 400w, /foto-1200.jpg 1200w"></article>'
        result = m.html_image_candidates(markup,'https://fonte.example/',article=True)
        self.assertEqual(result[0]['url'],'https://fonte.example/foto-1200.jpg')

    def test_feed_media_enclosures_and_html_are_filtered(self):
        item = {'media_content':[{'medium':'video','url':'https://fonte.example/video.mp4'},
                                  {'type':None,'url':'https://fonte.example/original.jpg'}],
                'enclosures':[{'type':'image/jpeg','href':'https://fonte.example/logo.jpg'}],
                'summary':'<img src="/avatar.jpg"><img data-src="/segunda.jpg">',
                'media_thumbnail':[{'url':'https://fonte.example/miniatura.jpg'}]}
        urls = [candidate['url'] for candidate in m.feed_image_candidates(item,'https://fonte.example/')]
        self.assertEqual(urls,['https://fonte.example/original.jpg','https://fonte.example/segunda.jpg',
                               'https://fonte.example/miniatura.jpg'])

    def test_image_bytes_and_minimum_dimensions_are_verified(self):
        client = self.client()
        client.asset = Mock(return_value=(b'<html>Sem foto</html>','https://fonte.example/a.jpg'))
        with self.assertRaises(m.ItemError):client.image_info('https://fonte.example/a.jpg',time.monotonic()+10)
        with self.assertRaises(m.ItemError):client.image_info('https://fonte.example/a.jpg',time.monotonic()+10)
        self.assertEqual(client.asset.call_count,1)
        client.asset = Mock(return_value=(photo(199,200),'https://fonte.example/b.jpg'))
        with self.assertRaises(m.ItemError):client.image_info('https://fonte.example/b.jpg',time.monotonic()+10)
        client.asset = Mock(return_value=(photo(200,200),'https://fonte.example/c.jpg'))
        self.assertEqual(client.image_info('https://fonte.example/c.jpg',time.monotonic()+10)['width'],200)

    def test_invalid_first_candidate_falls_back_to_real_handler_photo(self):
        client = self.client(); item = entry()
        first='https://fonte.example/foto.jpg'; second='https://camara.example/Handler.ashx?f=i&query=123'
        item['image_candidates']=[{'url':first},{'url':second}]
        client.asset=Mock(side_effect=[(b'<html>404</html>',first),(photo(),second)])
        info=client.resolve_image(item)
        self.assertEqual(info['url'],second)
        self.assertEqual(info['width'],400)

    def test_article_og_photo_is_used_when_feed_has_no_image(self):
        client=self.client(); url='https://fonte.example/capa.jpg'
        client.asset=Mock(side_effect=[(f'<meta property="og:image" content="{url}">'.encode(),entry()['url']),
                                      (photo(),url)])
        self.assertEqual(client.resolve_image(entry())['url'],url)

    def test_dns_failure_of_photo_does_not_stop_queue(self):
        client=self.client(); client.asset=Mock(side_effect=m.Deferred('DNS indisponível'))
        item=dict(entry(),image_candidates=[{'url':'https://fonte.example/capa.jpg'}])
        self.assertIsNone(client.resolve_image(item))

    def test_private_redirect_is_not_requested_and_credentials_are_not_forwarded(self):
        client=self.client()
        response=Mock(status_code=302,headers={'Location':'http://169.254.169.254/foto.jpg'})
        client.session.request=Mock(return_value=response)
        with patch.object(m,'public_asset_host'):
            with self.assertRaises(m.ItemError):
                client.asset('https://fonte.example/foto.jpg',10000,time.monotonic()+10,image=True)
        self.assertEqual(client.session.request.call_count,1)
        headers=client.session.request.call_args.kwargs['headers']
        self.assertNotIn('Authorization',headers)
        self.assertNotIn('X-G73-Token',headers)

    def test_figure_after_first_paragraph_has_exact_keyword_and_same_url(self):
        keyword='crédito & renda "segura"'
        item=dict(entry(),image_credit='Divulgação / Fonte Oficial')
        url='https://fonte.example/Handler.ashx?f=i&query=123'
        payload=m.payload_from(generated(keyword),item,'noticias-regiao',image={'url':url,'width':400,'height':250})
        soup=m.BeautifulSoup(payload['content'],'html.parser')
        figure=soup.find('figure')
        self.assertEqual(soup.find('p').find_next_sibling().name,'figure')
        self.assertEqual(figure.img['alt'],keyword)
        self.assertEqual(figure.img['src'],payload['image_url'])
        self.assertEqual(figure.figcaption.get_text(),'Foto: Divulgação / Fonte Oficial')
        self.assertEqual(payload['status'],'draft')
        self.assertNotIn('rank_math_seo_score',payload['meta'])

    def test_missing_credit_does_not_invent_official_source(self):
        payload=m.payload_from(generated(),entry(),'noticias-regiao',image={'url':'https://fonte.example/capa.jpg'})
        self.assertIn('Imagem da fonte: Fonte regional',payload['content'])
        self.assertNotIn('Fonte Oficial',payload['content'])

    def test_image_422_removes_managed_figure_before_persisting_fallback(self):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'queue.sqlite3')
            try:
                item=entry(); source={'id':'fonte','category':'noticias-regiao'}
                store.discover(source,item)
                key=store.db.execute('SELECT id FROM items').fetchone()[0]
                payload=m.payload_from(generated(),item,'noticias-regiao',image={'url':'https://fonte.example/capa.jpg'})
                store.prepare(key,payload)
                client=self.client(); calls=[]
                def remote(method,url,**kwargs):
                    calls.append(copy.deepcopy(kwargs))
                    response=Mock(headers={})
                    if len(calls)==1:
                        response.status_code=422;response.json.return_value={'code':'g73_image_download'}
                    else:
                        response.status_code=201;response.json.return_value={'id':99,'status':'draft','permalink':'https://giro73.com.br/?p=99'}
                        cached=json.loads(store.db.execute('SELECT payload FROM items').fetchone()[0])
                        self.assertNotIn('<figure',cached['content'])
                        self.assertEqual(cached['image_url'],'')
                    return response
                client.session.request=Mock(side_effect=remote)
                self.assertEqual(m.process(client,store,1),(1,False))
                self.assertNotIn('<figure',calls[1]['json']['content'])
                self.assertEqual(calls[0]['headers'],calls[1]['headers'])
            finally:
                store.close()


if __name__=='__main__':
    unittest.main()
