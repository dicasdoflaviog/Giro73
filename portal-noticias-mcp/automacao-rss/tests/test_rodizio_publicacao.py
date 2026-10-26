import contextlib
import copy
import io
import json
from pathlib import Path
import sqlite3
import sys
import tempfile
import time
import unittest
from unittest.mock import Mock, patch

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import coletor_rss as m
import configurar_publicacao_rss as policy


def generated():
    return {"apto":True,"observacao":"","title":"Informação regional",
            "excerpt":"Resumo factual.","blocks":[{"type":"p","text":"Informação confirmada pela fonte. "*25}],
            "tags":[],"meta":dict(zip(m.SEO_FIELDS,["informação regional","Informação regional","Descrição factual."]))}


def entry(number=1,age=60):
    return {"guid":str(number),"url":f"https://fonte.example/noticia-{number}",
            "publisher":"Fonte","source_title":"Notícia","source_text":"Informação factual da fonte. "*30,
            "published_at":time.time()-age,"max_age_hours":72}


class RotationPublicationTests(unittest.TestCase):
    def setUp(self):
        self.temp=tempfile.TemporaryDirectory(); self.addCleanup(self.temp.cleanup)
        self.path=Path(self.temp.name)/"state.sqlite3"
        self.store=m.Store(self.path); self.addCleanup(lambda:self.store.close())
        self.source={"id":"fonte-a","category":"noticias-regiao","publication_mode":"auto"}
        self.photo={"url":"https://fonte.example/capa.jpg","width":1200,"height":675}

    def add(self,number,source=None,age=60):
        self.store.discover(source or self.source,entry(number,age))

    def client(self):
        c=m.Client("router-test","wp-test","openai/gpt-4o-mini",
                   "https://giro73.com.br/wp-json/g73/v1/publish",time.monotonic()+600)
        self.addCleanup(c.session.close)
        c.generate=Mock(side_effect=lambda _:generated())
        c.resolve_image=Mock(return_value=self.photo)
        return c

    def response(self,status="publish",media=22):
        r=Mock(status_code=201,headers={})
        r.json.return_value={"id":21,"status":status,"featured_media":media,
                             "permalink":"https://giro73.com.br/?p=21"}
        return r

    def test_persistent_rotation_does_not_let_large_feed_dominate_limit_one(self):
        for n in range(1,5):self.add(n)
        b=dict(self.source,id="fonte-b");c=dict(self.source,id="fonte-c")
        self.add(5,b);self.add(6,c)
        first=list(self.store.ready(1))[0]
        self.store.close();self.store=m.Store(self.path)
        second=list(self.store.ready(1))[0]
        third=list(self.store.ready(1))[0]
        self.assertEqual({first['source'],second['source'],third['source']},{'fonte-a','fonte-b','fonte-c'})

    def test_batch_rotation_is_balanced_and_never_repeats_same_item(self):
        for n in range(1,5):self.add(n)
        for n in range(5,8):self.add(n,dict(self.source,id="fonte-b"))
        rows=list(self.store.ready(6))
        sequence=[r['source'] for r in rows]
        self.assertEqual(sequence.count('fonte-a'),3)
        self.assertEqual(sequence.count('fonte-b'),3)
        self.assertTrue(all(a!=b for a,b in zip(sequence,sequence[1:])))
        self.assertEqual(len({r['id'] for r in rows}),6)

    def test_disabled_or_removed_source_is_not_processed(self):
        self.add(1);self.add(2,dict(self.source,id='paused'))
        rows=list(self.store.ready(10,sources=['fonte-a']))
        self.assertEqual([r['source'] for r in rows],['fonte-a'])
        self.assertEqual(list(self.store.ready(1,sources=[])),[])

    def test_recent_news_is_selected_before_old_backlog_within_same_source(self):
        self.add(1,age=3600);self.add(2,age=30)
        self.assertTrue(list(self.store.ready(1))[0]['article'].endswith('noticia-2'))

    def test_prepared_uncertain_send_has_priority_and_delayed_item_does_not_block(self):
        self.add(1,age=3600);self.add(2,age=30)
        key=self.store.db.execute("SELECT id FROM items WHERE article LIKE '%noticia-1'").fetchone()[0]
        self.store.prepare(key,m.payload_from(generated(),entry(1),'noticias-regiao'))
        self.assertEqual(list(self.store.ready(1))[0]['id'],key)
        self.store.finish(key,'prepared',delay=900)
        self.assertNotEqual(list(self.store.ready(1))[0]['id'],key)

    def test_minimum_is_600_characters_not_600_words(self):
        g=generated();e=entry()
        self.assertLess(len(g['blocks'][0]['text'].split()),600)
        payload=m.payload_from(g,e,'noticias-regiao',self.photo)
        self.assertEqual(m.publication_decision(self.source,e,g,payload)[0],'publish')
        for target in ('source','generated','image','date'):
            with self.subTest(target=target):
                data=copy.deepcopy(g);material=copy.deepcopy(e);out=copy.deepcopy(payload)
                if target=='source':material['source_text']='curto'
                if target=='generated':data['blocks'][0]['text']='curto'
                if target=='image':out['image_url']=''
                if target=='date':material['published_at']=time.time()-25*3600
                self.assertEqual(m.publication_decision(self.source,material,data,out)[0],'draft')

    def test_default_policy_and_model_requested_publish_remain_draft(self):
        g=generated();g['status']='publish'
        payload=m.payload_from(g,entry(),'noticias-regiao',self.photo)
        self.assertEqual(payload['status'],'draft')
        self.assertEqual(m.publication_decision({},entry(),g,payload)[0],'draft')

    def test_historical_source_url_cannot_be_promoted_by_fresh_feed_date(self):
        e=entry();e['url']='https://fonte.example/2020/05/07/obra/'
        g=generated();payload=m.payload_from(g,e,'noticias-regiao',self.photo)
        self.assertEqual(m.publication_decision(self.source,e,g,payload),
                         ('draft','referencia_historica_ou_futura_requer_revisao'))

    def test_generic_reference_is_kept_for_manual_review(self):
        for url in ('https://fonte.example/','https://fonte.example/category/noticias/',
                    'https://fonte.example/noticias/index.php'):
            with self.subTest(url=url):
                e=entry();e['url']=url;g=generated()
                self.assertEqual(m.publication_decision(self.source,e,g,
                    m.payload_from(g,e,'noticias-regiao',self.photo)),
                    ('draft','referencia_generica_requer_revisao'))

    def test_sensitive_allegation_is_not_automatically_published(self):
        g=generated();g['title']='Vereadores denunciam exploração financeira'
        self.assertEqual(m.publication_decision(self.source,entry(),g,
            m.payload_from(g,entry(),'politica',self.photo)),
            ('draft','alegacao_sensivel_requer_revisao'))

    def test_promotional_padding_requires_review(self):
        g=generated();g['blocks'].append({'type':'p','text':'A obra é um marco significativo para todos.'})
        self.assertEqual(m.publication_decision(self.source,entry(),g,
            m.payload_from(g,entry(),'noticias-regiao',self.photo)),
            ('draft','linguagem_promocional_requer_revisao'))

    def test_small_image_is_attached_to_draft_without_auto_publishing(self):
        g=generated();e=entry();payload=m.payload_from(g,e,'noticias-regiao',self.photo)
        self.assertEqual(m.publication_decision(self.source,e,g,payload,image={'width':200,'height':200}),
                         ('draft','capa_pequena_requer_revisao'))
        self.assertEqual(m.publication_decision(self.source,e,g,payload,image=self.photo)[0],'publish')

    def test_source_url_date_is_parsed_as_utc_and_malformed_dates_fail_closed(self):
        e=entry();g=generated()
        e['url']='https://fonte.example/2026/02/31/obra/'
        self.assertEqual(m.editorial_review_reason(e,g),'data_da_referencia_invalida')
        e['url']='https://fonte.example/2026/10/26/obra/'
        with patch.object(m.time,'time',return_value=1793016000):
            self.assertIsNone(m.editorial_review_reason(e,g))

    def test_authorized_source_sends_publish_and_records_actual_status(self):
        self.add(1);c=self.client()
        c.session.request=Mock(return_value=self.response())
        self.assertEqual(m.process(c,self.store,1,sources=[self.source]),(1,False))
        self.assertEqual(c.session.request.call_args.kwargs['json']['status'],'publish')
        saved=self.store.db.execute('SELECT * FROM items').fetchone()
        self.assertEqual((saved['status'],saved['wp_id'],saved['wp_status']),('sent',21,'publish'))

    def test_backend_downgrade_to_draft_is_accepted_without_duplicate(self):
        self.add(1);c=self.client();c.session.request=Mock(return_value=self.response('draft',0))
        self.assertEqual(m.process(c,self.store,1,sources=[self.source]),(1,False))
        self.assertEqual(self.store.db.execute('SELECT wp_status FROM items').fetchone()[0],'draft')
        self.assertEqual(m.process(c,self.store,1,sources=[self.source]),(0,False))
        self.assertEqual(c.session.request.call_count,1)

    def test_publish_permission_is_explicit_and_draft_never_accepts_publish_response(self):
        c=self.client();c.session.request=Mock(return_value=self.response())
        with self.assertRaises(m.StopRun):c.submit('key',{'status':'publish'})
        c.session.request.assert_not_called()
        with self.assertRaises(m.StopRun):c.submit('key',{'status':'draft'})

    def test_existing_prepared_draft_is_not_promoted_when_source_becomes_auto(self):
        self.add(1);row=self.store.db.execute('SELECT * FROM items').fetchone()
        old=m.payload_from(generated(),entry(),'noticias-regiao',self.photo)
        self.store.prepare(row['id'],old)
        c=self.client();c.session.request=Mock(return_value=self.response('draft'))
        m.process(c,self.store,1,sources=[self.source])
        self.assertEqual(c.session.request.call_args.kwargs['json'],old)
        c.generate.assert_not_called()

    def test_lost_publish_response_resumes_same_payload_and_idempotency_key(self):
        self.add(1);c=self.client()
        c.session.request=Mock(side_effect=[m.requests.exceptions.Timeout(),self.response()]);c.attempts=1
        self.assertEqual(m.process(c,self.store,1,sources=[self.source]),(0,True))
        first=copy.deepcopy(c.session.request.call_args.kwargs)
        with self.store.db:self.store.db.execute('UPDATE items SET next_try=0')
        self.assertEqual(m.process(c,self.store,1,sources=[self.source]),(1,False))
        self.assertEqual(first,c.session.request.call_args.kwargs)
        self.assertEqual(c.generate.call_count,1)

    def test_revoked_auto_permission_does_not_send_prepared_publish(self):
        self.add(1);row=self.store.db.execute('SELECT * FROM items').fetchone()
        payload=m.payload_from(generated(),entry(),'noticias-regiao',self.photo);payload['status']='publish'
        self.store.prepare(row['id'],payload)
        c=self.client();c.session.request=Mock()
        self.assertEqual(m.process(c,self.store,1,sources=[dict(self.source,publication_mode='draft')]),(0,True))
        c.session.request.assert_not_called()
        self.assertEqual(self.store.counts(),{'blocked':1})

    def test_old_endpoint_image_error_retries_as_draft_not_publish(self):
        self.add(1);c=self.client();bad=self.response();bad.status_code=422
        bad.json.return_value={'code':'g73_image_editor'}
        sent=[]
        def remote(*args,**kwargs):
            sent.append(copy.deepcopy(kwargs))
            return bad if len(sent)==1 else self.response('draft',0)
        c.session.request=Mock(side_effect=remote)
        self.assertEqual(m.process(c,self.store,1,sources=[self.source]),(1,False))
        self.assertEqual([call['json']['status'] for call in sent],['publish','draft'])
        self.assertEqual(sent[0]['headers']['Idempotency-Key'],sent[1]['headers']['Idempotency-Key'])
        self.assertEqual(self.store.db.execute('SELECT wp_status FROM items').fetchone()[0],'draft')

    def test_policy_configuration_is_opt_in_preserves_sources_and_backups(self):
        feeds=[dict(self.source,id=f'fonte-{n}',url=url,authorized=True,name=str(n)) for n,url in enumerate([
            'https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml',
            'https://agenciabrasil.ebc.com.br/rss/economia/feed.xml',
            'https://g1.globo.com/rss/g1/bahia/',
            'https://portalleodias.com/feed/',
            'https://g1.globo.com/rss/g1/brasil/',
            'https://liberdadenews.com.br/?format=feed&type=rss'])]
        path=Path(self.temp.name)/'feeds.json';original=json.dumps({'feeds':feeds,'extra':'preservar'}).encode()
        path.write_bytes(original)
        with contextlib.redirect_stdout(io.StringIO()):
            policy.configure(path)
            self.assertEqual(path.read_bytes(),original)
            policy.configure(path,apply=True)
        saved=json.loads(path.read_bytes())
        self.assertEqual([f['publication_mode'] for f in saved['feeds']],['auto']*4+['draft']*2)
        self.assertEqual(saved['extra'],'preservar')
        self.assertEqual(next(path.parent.glob('feeds.json.bak-publicacao-*')).read_bytes(),original)

    def test_policy_does_not_match_unapproved_path_or_host_or_enable_paused_feed(self):
        approved='https://g1.globo.com/rss/g1/bahia/'
        self.assertNotIn(policy.identity('https://evil.example/rss/g1/bahia/'),policy.APPROVED)
        self.assertNotIn(policy.identity('https://g1.globo.com:8443/rss/g1/bahia/'),policy.APPROVED)
        path=Path(self.temp.name)/'feeds.json'
        path.write_text(json.dumps({'feeds':[dict(self.source,url=approved,authorized=False)]}))
        before=path.read_bytes()
        with contextlib.redirect_stdout(io.StringIO()),self.assertRaises(ValueError):policy.configure(path,apply=True)
        self.assertEqual(path.read_bytes(),before)

    def test_invalid_publication_configuration_is_rejected(self):
        base=dict(self.source,url='https://fonte.example/feed',authorized=True)
        for key,value in [('publication_mode','publish'),('auto_min_chars',599),('auto_min_chars',True),('auto_max_age_hours',0)]:
            path=Path(self.temp.name)/'feeds.json';path.write_text(json.dumps({'feeds':[dict(base,**{key:value})]}))
            with self.subTest(key=key,value=value),self.assertRaises(m.ConfigError):m.load_sources(path)

    def test_old_database_migration_preserves_pending_payload_and_source_dates(self):
        self.store.close();self.path.unlink()
        db=sqlite3.connect(self.path)
        db.execute('CREATE TABLE items (id TEXT PRIMARY KEY,source TEXT NOT NULL,article TEXT NOT NULL,category TEXT NOT NULL,input_json TEXT NOT NULL,status TEXT NOT NULL,payload TEXT,wp_id INTEGER,wp_url TEXT,attempts INTEGER DEFAULT 0,next_try REAL DEFAULT 0,last_error TEXT,created_at REAL,updated_at REAL)')
        e=entry();payload=json.dumps({'status':'draft'})
        db.execute('INSERT INTO items VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',('old','a',e['url'],'politica',json.dumps(e),'prepared',payload,None,None,1,0,None,1,1));db.commit();db.close()
        self.store=m.Store(self.path)
        row=self.store.db.execute('SELECT * FROM items').fetchone()
        self.assertEqual(row['payload'],payload)
        self.assertEqual(row['published_at'],e['published_at'])
        self.assertIsNone(row['wp_status'])


if __name__=='__main__':unittest.main()
