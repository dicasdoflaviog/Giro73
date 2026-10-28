import copy
import json
from pathlib import Path
import tempfile
import time
import unittest
from unittest.mock import Mock,patch
import coletor_rss as m
import configurar_fontes_rss as c

URL='https://www.mucuri.ba.gov.br/noticias/atendimento-de-saude-na-comunidade'
TITLE='Atendimento de saúde para moradores da comunidade'
def article(date='<meta property="article:published_time" content="2026-10-28T08:00:00-03:00">'):
    return '<html><head>'+date+'</head><body><header>Última atualização: 28/10/2026</header><main><article><h1>'+TITLE+'</h1><div class="entry-content"><p>'+('Atendimento confirmado na comunidade, conforme os dados do comunicado. '*6)+'</p><img src="/foto.jpg" width="1200" height="675" alt="Fachada de unidade de saúde"></div></article></main><footer>Contato municipal e política de cookies.</footer></body></html>'

class ParseTests(unittest.TestCase):
    def test_link_discovery_rejects_navigation_external_lists_and_duplicates(self):
        markup=f'<nav><h2><a href="/noticias/fora">{TITLE}</a></h2></nav><h2><a href="{URL}">{TITLE}</a></h2><h2><a href="{URL}#x">{TITLE}</a></h2><h2><a href="https://evil.example/noticias/123">{TITLE}</a></h2><h2><a href="/noticias">Todas as notícias da prefeitura</a></h2><h2><a href="/documento.pdf">Documento institucional publicado</a></h2>'
        links=m.municipal_links(markup,URL)
        self.assertEqual([x['url'] for x in links],[URL])

    def test_article_has_original_date_text_image_and_safe_canonical(self):
        e=m.municipal_article(article(),URL,TITLE)
        self.assertEqual(e['title'],TITLE)
        self.assertEqual(e['published_parsed'],time.gmtime(m.municipal_date('2026-10-28T08:00:00-03:00')))
        self.assertNotIn('cookies',e['summary'])
        self.assertEqual(e['g73_html_images'][0]['alt'],'Fachada de unidade de saúde')

    def test_updated_time_footer_and_capture_date_never_become_publication(self):
        for markup in [article(''),article('<meta property="article:modified_time" content="2026-10-28T08:00:00-03:00">'),article('').replace('<h1>','<time class="updated" datetime="2026-10-28">Atualizado</time><h1>')]:
            with self.subTest(markup=markup[:80]):self.assertIsNone(m.municipal_article(markup,URL,TITLE))

    def test_sai_style_article_heading_and_explicit_portuguese_publication(self):
        markup=article('').replace('<h1>'+TITLE+'</h1>','<h1>Prefeitura Municipal</h1><h2>'+TITLE+'</h2><small>Publicado em 28/10/2026 às 08:00</small>')
        e=m.municipal_article(markup,URL,TITLE)
        self.assertEqual(e['title'],TITLE)
        self.assertEqual(e['published_parsed'],time.gmtime(m.municipal_date('2026-10-28T08:00:00-03:00')))

    def test_original_jsonld_date_is_used_and_unrelated_article_is_ignored(self):
        data=[{'@type':'NewsArticle','url':'https://www.mucuri.ba.gov.br/outra','datePublished':'2026-10-28'},
              {'@type':'NewsArticle','url':URL,'datePublished':'2020-02-20'}]
        e=m.municipal_article(article('<script type="application/ld+json">'+json.dumps(data)+'</script>'),URL,TITLE)
        self.assertEqual(e['published_parsed'].tm_year,2020)

    def test_complete_portuguese_dates_and_no_year_guess(self):
        self.assertEqual(m.municipal_date('Publicado em 28 de outubro de 2026'),m.municipal_date('28/10/2026'))
        self.assertIsNone(m.municipal_date('jan 01'))
        self.assertIsNone(m.municipal_date('31/02/2026'))
        self.assertEqual(time.gmtime(m.municipal_date('28/10/2026')).tm_hour,3)

    def test_listing_or_mismatched_title_is_not_an_article(self):
        self.assertIsNone(m.municipal_article(article(),URL,'Portal da prefeitura e canais de transparência'))
        self.assertIsNone(m.municipal_article(article().replace('Atendimento confirmado na comunidade, conforme os dados do comunicado. '*6,'Texto curto.'),URL,TITLE))

    def test_url_aliases_keep_old_rss_item_when_switching_to_html(self):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'state.sqlite');self.addCleanup(store.close)
            s={'id':'existente','category':'noticias-regiao'}
            self.assertTrue(store.discover(s,{'url':URL,'guid':'rss-1'}))
            self.assertFalse(store.discover(s,{'url':URL.replace('www.','')+'/','guid':'html-1'}))
            self.assertEqual(store.counts(),{'queued':1})

    def test_html_entries_pass_through_discovery_without_ai_or_publication(self):
        client=Mock();client.html_feed.return_value=[m.municipal_article(article(),URL,TITLE)]
        client.feed.side_effect=AssertionError('HTML source must not call feed')
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'state.sqlite');self.addCleanup(store.close)
            source={'id':'mucuri','name':'Prefeitura de Mucuri','url':'https://www.mucuri.ba.gov.br/noticias','transport':'html','category':'noticias-regiao','max_age_hours':72,'use_full_content':True}
            with patch.object(m.time,'time',return_value=m.municipal_date('2026-10-28T12:00:00-03:00')):
                self.assertEqual(m.discover(client,store,[source]),(1,0))
            row=store.db.execute('SELECT input_json FROM items').fetchone()
            self.assertEqual(json.loads(row[0])['image_candidates'][0]['alt'],'Fachada de unidade de saúde')
            client.generate.assert_not_called();client.submit.assert_not_called()

class MunicipalClientTests(unittest.TestCase):
    def client(self):
        client=m.Client('','','none','https://giro73.com.br/wp-json/g73/v1/publish',time.monotonic()+60,attempts=1)
        self.addCleanup(client.session.close);return client

    def test_html_reader_reads_article_but_does_not_call_ai(self):
        client=self.client()
        listing='<h1>Notícias</h1><h2><a href="'+URL+'">'+TITLE+'</a></h2>'
        client.municipal_page=Mock(side_effect=[(listing.encode(),'https://www.mucuri.ba.gov.br/noticias'),(article().encode(),URL)])
        client.generate=Mock();client.submit=Mock()
        result=client.html_feed({'url':'https://www.mucuri.ba.gov.br/','name':'Prefeitura'})
        self.assertEqual(len(result),1);client.generate.assert_not_called();client.submit.assert_not_called()

    def test_only_iframe_or_js_shell_is_not_silently_activated(self):
        client=self.client();client.municipal_page=Mock(return_value=(b'<iframe src="https://saiconecta.imap.org.br/tenant"></iframe>',URL))
        with self.assertRaises(m.ItemError):client.html_feed({'url':'https://www.mucuri.ba.gov.br/'})

    def test_empty_news_listing_can_be_monitored_without_inventing_entries(self):
        client=self.client();client.municipal_page=Mock(return_value=(b'<h1>Noticias</h1><p>Nenhuma noticia encontrada.</p>',URL))
        self.assertEqual(client.html_feed({'url':'https://www.mucuri.ba.gov.br/'}),[])

    def test_https_redirect_does_not_follow_external_host(self):
        client=self.client();response=Mock(status_code=302,headers={'Location':'https://evil.example/'})
        client.request=Mock(return_value=response)
        with patch.object(m,'public_feed_host'),self.assertRaises(m.ItemError):client.municipal_page('https://www.mucuri.ba.gov.br/',time.monotonic()+10)
        self.assertEqual(client.request.call_count,1);response.close.assert_called_once()

class MunicipalConfigTests(unittest.TestCase):
    def client(self):
        client=Mock();client.deadline=time.monotonic()+60;client.feed.side_effect=m.ItemError('Feed HTTP 404')
        client.html_feed.return_value=[];client.html_listing_url='https://www.mucuri.ba.gov.br/noticias'
        return client

    def test_runtime_rss_failure_uses_html_for_same_official_source(self):
        client=self.client();client.html_feed.return_value=['article']
        source={'id':'mesmo-id','url':'https://www.mucuri.ba.gov.br/feed/','site_url':'https://www.mucuri.ba.gov.br/','source_type':'prefeitura','html_fallback':True}
        self.assertEqual(m.read_source(client,source),(['article'],'html'))
        self.assertEqual(source['id'],'mesmo-id')

    def test_press_feed_does_not_implicitly_gain_html_access(self):
        client=self.client()
        with self.assertRaises(m.ItemError):m.read_source(client,{'id':'press','url':'https://press.example/feed','html_fallback':True})
        client.html_feed.assert_not_called()

    def test_exact_official_list_has_22_unique_domains(self):
        definitions=c.government_definitions(c.OFFICIAL_CITIES)
        self.assertEqual(len(definitions),22)
        self.assertEqual(len({c.host_key(x[2]) for x in definitions}),22)
        self.assertTrue(all(x[2].startswith('https://www.') for x in definitions))

    def test_fallback_updates_existing_source_and_repeated_run_keeps_id(self):
        feeds=[{'id':'manter-id','name':'Prefeitura de Mucuri','url':'https://mucuri.ba.gov.br/feed/','category':'noticias-regiao','authorized':True}]
        client=self.client()
        with patch.object(c,'discover_candidates',return_value=['https://www.mucuri.ba.gov.br/feed/']):
            self.assertEqual(c.configure_municipal(feeds,client,'Prefeitura de Mucuri','noticias-regiao','https://www.mucuri.ba.gov.br/',60),(True,True))
            self.assertEqual(c.configure_municipal(feeds,client,'Prefeitura de Mucuri','noticias-regiao','https://www.mucuri.ba.gov.br/',60),(False,True))
        self.assertEqual(len(feeds),1);self.assertEqual(feeds[0]['id'],'manter-id');self.assertEqual(feeds[0]['transport'],'html')

    def test_native_rss_wins_without_running_html(self):
        client=self.client();client.feed.side_effect=None;client.feed.return_value=[]
        feeds=[]
        with patch.object(c,'discover_candidates',return_value=['https://www.mucuri.ba.gov.br/feed/']):
            self.assertEqual(c.configure_municipal(feeds,client,'Prefeitura de Mucuri','noticias-regiao','https://www.mucuri.ba.gov.br/',60),(True,True))
        self.assertEqual(feeds[0]['transport'],'rss');client.html_feed.assert_not_called()

    def test_failure_preserves_existing_configuration(self):
        client=self.client();client.html_feed.side_effect=m.ItemError('Portal indisponível')
        feeds=[{'id':'existente','name':'Prefeitura de Mucuri','url':'https://mucuri.ba.gov.br/feed/','authorized':True}];before=copy.deepcopy(feeds)
        with patch.object(c,'discover_candidates',return_value=[]):
            self.assertEqual(c.configure_municipal(feeds,client,'Prefeitura de Mucuri','noticias-regiao','https://www.mucuri.ba.gov.br/',60),(False,False))
        self.assertEqual(feeds,before)

    def test_duplicate_existing_records_do_not_create_a_third(self):
        feeds=[{'id':str(i),'url':'https://mucuri.ba.gov.br/feed/'} for i in range(2)];client=self.client()
        self.assertEqual(c.configure_municipal(feeds,client,'Prefeitura de Mucuri','noticias-regiao','https://www.mucuri.ba.gov.br/',60),(False,False))
        self.assertEqual(len(feeds),2);client.feed.assert_not_called()
