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

class ProductionPreservationTests(unittest.TestCase):
    def article(self,date=True):
        meta='<meta property="article:published_time" content="2026-10-28T10:00:00-03:00">' if date else ''
        return meta+'''<article><h1>Câmara apresenta medidas para os serviços públicos municipais</h1><div class="entry-content"><p>A Câmara apresentou informações confirmadas sobre o funcionamento dos serviços públicos e informou os canais oficiais de atendimento ao cidadão.</p><p>A população deve consultar os documentos e os horários divulgados pela instituição antes de solicitar atendimento e acompanhar o andamento das propostas.</p></div></article>'''
    def test_municipal_date_keeps_bahia_timezone(self):
        self.assertEqual(m.municipal_date('28/10/2026 10:00'),m.municipal_date('2026-10-28T13:00:00Z'))
        self.assertIsNone(m.municipal_date('31/02/2026'))
    def test_municipal_html_extracts_original_date_title_and_text(self):
        item=m.municipal_article(self.article(),'https://camara.example/medidas-servicos')
        self.assertIsNotNone(item)
        self.assertEqual(item['title'],'Câmara apresenta medidas para os serviços públicos municipais')
        self.assertEqual(item['published_parsed'],time.gmtime(m.municipal_date('2026-10-28T13:00:00Z')))
        self.assertIn('canais oficiais',m.plain(item['summary']))
    def test_updated_or_footer_date_is_not_original_publication(self):
        markup='<meta property="article:modified_time" content="2026-10-28T10:00:00-03:00">'+self.article(False)+'<footer>28/10/2026</footer>'
        self.assertIsNone(m.municipal_article(markup,'https://camara.example/medidas-servicos'))
    def test_municipal_links_restrict_origin_and_ignore_navigation(self):
        markup='<nav><a href="/menu">Notícias de todos os serviços públicos</a></nav><article><h2><a href="/noticias/servico">Câmara informa os novos horários dos serviços</a></h2><h2><a href="https://outro.example/noticias/servico">Outro portal informa horários dos serviços</a></h2></article>'
        links=m.municipal_links(markup,'https://camara.example/')
        self.assertEqual(len(links),1);self.assertEqual(links[0]['url'],'https://camara.example/noticias/servico')
    def test_rss_failure_uses_only_configured_municipal_html_fallback(self):
        c=Mock();c.feed.side_effect=m.ItemError('feed indisponível');c.html_feed.return_value=['municipal-item']
        source={'id':'camara','source_type':'camara','site_url':'https://camara.example/','url':'https://camara.example/feed/','html_fallback':True}
        self.assertEqual(m.read_source(c,source),(['municipal-item'],'html'))
        with self.assertRaises(m.ItemError):m.read_source(c,dict(source,html_fallback=False))
    def test_source_load_preserves_transport_fallback_and_fulltext_flags(self):
        with tempfile.TemporaryDirectory() as directory:
            p=Path(directory)/'config.json';source={'id':'camara','name':'Câmara Municipal','url':'https://camara.example/noticias/','site_url':'https://camara.example/','source_type':'camara','transport':'html','html_fallback':True,'fetch_article_text':True,'authorized':True,'category':'politica','publication_mode':'auto','fallback_image':{'url':'https://giro73.com.br/foto.webp','credit':'Arquivo autorizado','credit_url':'https://giro73.com.br/'}}
            p.write_text(json.dumps({'feeds':[source]}));loaded=m.load_sources(p)[0]
            self.assertEqual(loaded['transport'],'html');self.assertTrue(loaded['html_fallback']);self.assertTrue(loaded['fetch_article_text'])
            self.assertEqual(loaded['fallback_image'],source['fallback_image']);self.assertEqual(loaded['publication_mode'],'draft');self.assertEqual(loaded['max_age_hours'],24)
    def test_store_keeps_www_and_trailing_slash_alias_dedup(self):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'queue.db')
            try:
                source={'id':'a','category':'politica'};e={'url':'https://www.camara.example/noticia/','published_at':time.time(),'date_basis':'published'}
                self.assertTrue(store.discover(source,e))
                self.assertFalse(store.discover(dict(source,id='b'),dict(e,url='https://camara.example/noticia')))
                self.assertEqual(store.counts(),{'queued':1})
            finally:store.close()
    def test_category_selection_and_image_alt_credits_are_preserved(self):
        g={'apto':True,'title':'Informação esportiva confirmada','excerpt':'Resumo de informação esportiva confirmada.','blocks':[{'type':'p','text':'Informação esportiva confirmada pela fonte.'}],'tags':[],'editorial_category':'esportes','summary_points':['Fato confirmado.','Local confirmado.','Impacto informado.'],'meta':dict(zip(m.SEO_FIELDS,['informação esportiva','Informação esportiva confirmada','Informação esportiva confirmada pela fonte.']))}
        e={'url':'https://fonte.example/futebol','publisher':'Fonte','published_at':time.time()-60,'date_basis':'published','source_text':'Dados da fonte.'}
        image={'url':'https://fonte.example/foto.jpg','alt':'Torcedores no estádio','credit':'Foto: Fotógrafo identificado','credit_url':'https://fonte.example/credito','width':1200,'height':675}
        out=m.payload_from(g,e,'tecnologia-inovacao',image)
        self.assertEqual(out['category'],'esportes');self.assertEqual(out['image_alt'],image['alt']);self.assertEqual(out['image_credit'],image['credit'])
        self.assertIn('/category/esportes/',out['content']);self.assertNotIn('/category/tecnologia-inovacao/',out['content'])

if __name__=='__main__':unittest.main()
