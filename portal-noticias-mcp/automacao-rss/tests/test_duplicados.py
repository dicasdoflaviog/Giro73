import json
from pathlib import Path
import sys,tempfile,time,unittest
from unittest.mock import Mock
sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import coletor_rss as m

class DuplicateTests(unittest.TestCase):
    def client(self):
        client=m.Client('test-router','test-wp','openai/gpt-4o-mini','https://giro73.com.br/wp-json/g73/v1/publish',time.monotonic()+300)
        self.addCleanup(client.session.close)
        return client
    def reply(self,code,url='https://giro73.com.br/existente/'):
        response=Mock(status_code=409,headers={})
        response.json.return_value={'code':code,'data':{'status':409,'existing_id':414,'existing_url':url}}
        return response
    def test_duplicate_response_is_not_retried(self):
        c=self.client();c.session.request=Mock(return_value=self.reply('g73_duplicate_article'))
        with self.assertRaises(m.DuplicateArticle) as error:c.submit('key',{'status':'draft'})
        self.assertEqual(error.exception.existing_id,414)
        self.assertEqual(error.exception.existing_url,'https://giro73.com.br/existente/')
        self.assertEqual(c.session.request.call_count,1)
    def test_untrusted_existing_permalink_is_not_stored(self):
        c=self.client();c.session.request=Mock(return_value=self.reply('g73_duplicate_article','https://outro.example/'))
        with self.assertRaises(m.DuplicateArticle) as error:c.submit('key',{'status':'draft'})
        self.assertIsNone(error.exception.existing_url)
    def process_case(self,code):
        with tempfile.TemporaryDirectory() as directory:
            store=m.Store(Path(directory)/'state.sqlite3')
            try:
                source={'id':'source','category':'noticias-regiao'}
                entry={'url':'https://origem.example/artigo','guid':'1','published_at':time.time(),'max_age_hours':72}
                store.discover(source,entry)
                row=store.db.execute('SELECT id FROM items').fetchone()
                store.prepare(row['id'],{'title':'Notícia','status':'draft','image_url':''})
                c=self.client();c.session.request=Mock(return_value=self.reply(code))
                result=m.process(c,store,1)
                saved=dict(store.db.execute('SELECT * FROM items').fetchone())
                self.assertEqual(c.session.request.call_count,1)
                self.assertEqual(saved['wp_id'],414)
                self.assertEqual(saved['wp_url'],'https://giro73.com.br/existente/')
                self.assertEqual(m.process(c,store,1),(0,False))
                self.assertEqual(c.session.request.call_count,1)
                return result,saved
            finally:store.close()
    def test_confirmed_duplicate_is_skipped_without_failing_run(self):
        result,saved=self.process_case('g73_duplicate_article')
        self.assertEqual(result,(0,False));self.assertEqual(saved['status'],'skipped')
    def test_possible_duplicate_is_held_for_review(self):
        result,saved=self.process_case('g73_duplicate_review')
        self.assertEqual(result,(0,True));self.assertEqual(saved['status'],'blocked')
        self.assertEqual(saved['last_error'],'g73_duplicate_review')
    def test_other_409_still_requires_review(self):
        c=self.client();c.session.request=Mock(return_value=self.reply('g73_idempotency_conflict'))
        with self.assertRaises(m.ItemError) as error:c.submit('key',{'status':'draft'})
        self.assertNotIsInstance(error.exception,m.DuplicateArticle)
    def test_source_url_is_assembled_locally_not_chosen_by_model(self):
        g={'apto':True,'title':'Notícia','excerpt':'Resumo','blocks':[{'type':'p','text':'Fatos da fonte.'}],'tags':[],
           'meta':dict(zip(m.SEO_FIELDS,['notícia','Título','Descrição'])),'source_url':'https://inventado.example/'}
        result=m.payload_from(g,{'url':'https://origem.example/artigo','publisher':'Origem'},'noticias-regiao')
        self.assertEqual(result['source_url'],'https://origem.example/artigo')

if __name__=='__main__':unittest.main()
