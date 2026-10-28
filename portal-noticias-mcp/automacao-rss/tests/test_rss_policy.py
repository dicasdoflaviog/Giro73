import calendar
import copy
import json
from pathlib import Path
import sys
import tempfile
import time
import unittest
from unittest.mock import Mock
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import coletor_rss as m

class HardPolicyTests(unittest.TestCase):
    def test_clock_boundaries_and_original_date_required(self):
        now=1793220000
        for age,expected in [(0,None),(86400,None),(86401,'fonte_antiga_mais_de_24h'),(-1,'data_original_ausente_invalida_ou_futura')]:
            with self.subTest(age=age):
                self.assertEqual(m.rss_freshness_reason({'published_at':now-age,'date_basis':'published'},now),expected)
        for value in [None,True,float('nan'),float('inf'),'2026-10-28']:
            with self.subTest(value=value):
                self.assertIsNotNone(m.rss_freshness_reason({'published_at':value,'date_basis':'published'},now))
        self.assertIsNotNone(m.rss_freshness_reason({'published_at':now-1,'date_basis':'updated'},now))

    def test_municipal_feed_origin_name_and_nonmunicipal_news(self):
        for source in [{'url':'https://camaramucuri.ba.gov.br/feed/'},{'url':'https://site.leg.br/feed/'},{'name':'Câmara Municipal'},{'source_type':'prefeitura'}]:
            self.assertTrue(m.municipal_source(source))
        self.assertTrue(m.municipal_source({'url':'https://proxy.example/feed'},{'url':'https://prefeitura.example/nota'}))
        self.assertFalse(m.municipal_source({'url':'https://portal.example/feed','name':'Portal regional'}))

    def test_legacy_configuration_cannot_relax_24h_or_publish_municipal(self):
        with tempfile.TemporaryDirectory() as directory:
            p=Path(directory)/'feeds.json'
            p.write_text(json.dumps({'feeds':[
                {'id':'municipal','name':'Prefeitura Municipal','url':'https://prefeitura.example/feed','category':'noticias-regiao','authorized':True,'publication_mode':'auto','max_age_hours':720,'allow_undated':True},
                {'id':'news','url':'https://portal.example/feed','category':'noticias-regiao','authorized':True,'publication_mode':'draft','max_age_hours':72}]}))
            data=m.load_sources(p)
            self.assertEqual([x['publication_mode'] for x in data],['draft','auto'])
            self.assertTrue(all(x['max_age_hours']==24 and not x['allow_undated'] for x in data))

    def test_discovery_drops_old_missing_and_updated_even_if_config_requests_backfill(self):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'test.db')
            source={'id':'news','name':'News','url':'https://portal.example/feed','category':'noticias-regiao','max_age_hours':720,'allow_undated':True}
            c=Mock();stamp=time.time()-90000
            c.feed.return_value=[{'link':'https://portal.example/old','title':'Old','summary':'Fato. '*90,'published_parsed':time.gmtime(stamp)},
                                 {'link':'https://portal.example/undated','summary':'Fato. '*90},
                                 {'link':'https://portal.example/updated','summary':'Fato. '*90,'updated_parsed':time.gmtime()}]
            try:
                with self.assertLogs(m.LOG,level='INFO') as log:
                    self.assertEqual(m.discover(c,store,[source]),(0,0))
                self.assertEqual(store.counts(),{})
                self.assertIn('sem pautas novas nas últimas 24h',' '.join(log.output))
            finally:store.close()

    def test_prepared_old_backlog_is_skipped_before_network_or_paid_generation(self):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'test.db');source={'id':'news','category':'noticias-regiao'}
            entry={'url':'https://portal.example/old','published_at':time.time()-90000,'date_basis':'published','max_age_hours':720}
            store.discover(source,entry);key=store.db.execute('SELECT id FROM items').fetchone()[0]
            store.prepare(key,{'status':'publish','title':'Old'})
            c=Mock()
            try:
                self.assertEqual(m.process(c,store,1,sources=[source]),(0,False))
                c.generate.assert_not_called();c.submit.assert_not_called();c.preflight.assert_not_called();c.original_lead.assert_not_called()
                self.assertEqual(store.counts(),{'skipped':1})
            finally:store.close()

    def test_preflight_duplicate_prevents_generation(self):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'test.db');source={'id':'news','category':'noticias-regiao'}
            entry={'url':'https://portal.example/new','published_at':time.time()-60,'date_basis':'published','max_age_hours':24}
            store.discover(source,entry);c=Mock();c.original_lead.return_value='Fatos da fonte. '*20
            c.preflight.side_effect=m.DuplicateArticle('g73_duplicate_article',650,None)
            try:
                self.assertEqual(m.process(c,store,1,sources=[source]),(0,False))
                c.generate.assert_not_called();c.submit.assert_not_called()
                self.assertEqual(store.counts(),{'skipped':1})
            finally:store.close()

    def test_original_lead_requires_two_paragraphs_and_excludes_navigation(self):
        c=m.Client('test','test','openai/gpt-4o-mini','https://giro73.com.br/wp-json/g73/v1/publish',time.monotonic()+300)
        try:
            c.asset=Mock(return_value=(b'<nav><p>Old navigation date 01/01/2000</p></nav><article><p>First factual paragraph with enough words for extraction.</p><p>Second factual paragraph with enough words for extraction.</p></article>','https://portal.example/new'))
            lead=c.original_lead({'url':'https://portal.example/new'})
            self.assertIn('First factual',lead);self.assertIn('Second factual',lead);self.assertNotIn('2000',lead)
            c.asset.return_value=(b'<article><p>Only one factual paragraph with enough words for extraction.</p></article>','https://portal.example/new')
            with self.assertRaises(m.ItemError):c.original_lead({'url':'https://portal.example/new'})
        finally:c.session.close()

if __name__=='__main__':unittest.main()
