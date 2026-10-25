import json
import os
from pathlib import Path
import sys
import tempfile
import time
import unittest
from unittest.mock import Mock, patch

sys.path.insert(0,str(Path(__file__).resolve().parents[1]))
import coletor_rss as m


def response(status,data=None,headers=None):
    result=Mock(status_code=status,headers=headers or {})
    result.json.return_value=data or {}
    return result


def draft():
    return {'apto':True,'observacao':'','title':'Notícia regional','excerpt':'Resumo factual.',
            'blocks':[{'type':'p','text':'Informação factual da fonte.'}],'tags':[],
            'meta':{'rank_math_focus_keyword':'notícia regional','rank_math_title':'Título SEO',
                    'rank_math_description':'Descrição SEO.'}}


def completion(data=None):
    return {'choices':[{'finish_reason':'stop','message':{'content':json.dumps(data or draft())}}]}


class OpenRouterTests(unittest.TestCase):
    def client(self,model='openai/gpt-4o-mini'):
        client=m.Client('router-key-test','wp-token-test',model,
                        'https://giro73.com.br/wp-json/g73/v1/publish',time.monotonic()+600)
        self.addCleanup(client.session.close)
        return client

    def test_generation_uses_only_openrouter_and_keeps_structured_seo(self):
        client=self.client()
        client.session.request=Mock(side_effect=[response(200,{'data':{}}),
            response(200,{'data':[{'id':client.model,'supported_parameters':['response_format']}]}),
            response(200,completion())])
        source={'source_title':'Notícia','source_text':'Material factual. '*20,'publisher':'Fonte',
                'url':'https://fonte.example/noticia','image_candidates':[{'url':'https://fonte.example/capa.jpg'}]}
        self.assertEqual(client.generate(source),draft())
        calls=client.session.request.call_args_list
        self.assertEqual([c.args[1] for c in calls],[m.OPENROUTER_BASE_URL+'/key',
                         m.OPENROUTER_BASE_URL+'/models',m.OPENROUTER_BASE_URL+'/chat/completions'])
        body=calls[-1].kwargs['json']
        self.assertEqual(body['model'],'openai/gpt-4o-mini')
        self.assertFalse(body['stream'])
        self.assertEqual(body['response_format']['type'],'json_schema')
        self.assertTrue(body['response_format']['json_schema']['strict'])
        self.assertTrue(body['provider']['require_parameters'])
        self.assertEqual(calls[-1].kwargs['headers']['Authorization'],'Bearer router-key-test')
        self.assertNotIn('X-G73-Token',calls[-1].kwargs['headers'])
        self.assertNotIn('image_candidates',json.loads(body['messages'][1]['content']))

    def test_configured_model_is_not_silently_changed(self):
        client=self.client('vendor/model:free')
        client.session.request=Mock(side_effect=[response(200,{'data':{}}),response(200,{'data':[]})])
        with self.assertRaises(m.StopRun):client.check_model()
        self.assertEqual(client.model,'vendor/model:free')

    def test_429_respects_retry_after(self):
        client=self.client()
        client.session.request=Mock(side_effect=[response(429,headers={'Retry-After':'5'}),
                                                response(200,{'data':[]})])
        with patch.object(m.time,'sleep') as sleep,patch.object(m.random,'uniform',return_value=0):
            self.assertEqual(client.openrouter_json('models'),{'data':[]})
        sleep.assert_called_once_with(5)
        self.assertEqual(client.session.request.call_count,2)

    def test_402_is_not_retried_and_does_not_expose_response_or_key(self):
        client=self.client()
        client.session.request=Mock(return_value=response(402,{'error':{'message':'private provider detail router-key-test'}}))
        with self.assertRaises(m.StopRun) as error:client.openrouter_json('chat/completions',{})
        self.assertIn('402',str(error.exception))
        self.assertNotIn('router-key-test',str(error.exception))
        self.assertNotIn('private provider detail',str(error.exception))
        self.assertEqual(client.session.request.call_count,1)

    def test_http_200_with_provider_error_preserves_work_for_later(self):
        client=self.client()
        client.session.request=Mock(return_value=response(200,{'error':{'code':503,'message':'temporary'}}))
        with self.assertRaises(m.Deferred):client.openrouter_json('chat/completions',{})

    def test_incomplete_refused_and_invalid_content_do_not_become_articles(self):
        client=self.client();client.model_checked=True
        for result in [
            {'choices':[{'finish_reason':'length','message':{'content':'{}'}}]},
            {'choices':[{'finish_reason':'stop','message':{'refusal':'Refused','content':None}}]},
            {'choices':[{'finish_reason':'stop','message':{'content':'not-json'}}]},
        ]:
            with self.subTest(result=result):
                client.session.request=Mock(return_value=response(200,result))
                with self.assertRaises(m.ItemError):client.generate({'source_text':'Material factual.'})

    def test_schema_matches_strict_openai_contract(self):
        def check(node):
            self.assertEqual(node['type'],node['type'].lower())
            if node['type']=='object':
                self.assertFalse(node['additionalProperties'])
                self.assertEqual(set(node['required']),set(node['properties']))
                for child in node['properties'].values():check(child)
            if node['type']=='array':check(node['items'])
        check(m.SCHEMA)
        self.assertEqual(set(m.SCHEMA['properties']['meta']['required']),set(m.SEO_FIELDS))

    def test_dotenv_exported_values_win_and_legacy_only_supplies_wordpress(self):
        with tempfile.TemporaryDirectory() as directory:
            primary=Path(directory)/'.env';legacy=Path(directory)/'legacy.env'
            primary.write_text('OPENROUTER_API_KEY = "file-router-key"\nAI_MODEL="openai/gpt-4o-mini"\n',encoding='utf-8-sig')
            legacy.write_text('OPENROUTER_API_KEY=ignored-legacy-key\nAI_MODEL=ignored/model\nG73_TOKEN=existing-token\n')
            with patch.dict(os.environ,{'OPENROUTER_API_KEY':'exported-key'},clear=True),patch.object(m,'LEGACY_ENV_FILE',legacy):
                m.load_environment(primary)
                self.assertEqual(os.environ['OPENROUTER_API_KEY'],'exported-key')
                self.assertEqual(os.environ['AI_MODEL'],'openai/gpt-4o-mini')
                self.assertEqual(os.environ['G73_TOKEN'],'existing-token')

    def test_legacy_ai_values_are_not_loaded(self):
        with tempfile.TemporaryDirectory() as directory:
            primary=Path(directory)/'.env';legacy=Path(directory)/'legacy.env'
            primary.write_text('AI_MODEL=openai/gpt-4o-mini\n')
            legacy.write_text('OPENROUTER_API_KEY=must-not-be-used\nG73_TOKEN=existing-token\n')
            with patch.dict(os.environ,{},clear=True),patch.object(m,'LEGACY_ENV_FILE',legacy):
                m.load_environment(primary)
                self.assertNotIn('OPENROUTER_API_KEY',os.environ)
                self.assertEqual(os.environ['G73_TOKEN'],'existing-token')

    def test_check_ai_reads_official_dotenv_and_does_not_need_wordpress_token(self):
        with tempfile.TemporaryDirectory() as directory:
            primary=Path(directory)/'.env';primary.write_text('OPENROUTER_API_KEY=router-key-test\n')
            client=self.client()
            client.session.request=Mock(side_effect=[response(200,{'data':{}}),
                response(200,{'data':[{'id':client.model,'supported_parameters':['response_format']}]})])
            with patch.dict(os.environ,{},clear=True),patch.object(m,'DEFAULT_ENV_FILE',primary),\
                    patch.object(m,'LEGACY_ENV_FILE',Path(directory)/'missing.env'),patch.object(m,'Client',return_value=client) as constructor:
                self.assertEqual(m.main(['--state',str(Path(directory)/'state.sqlite3'),'--check-ai']),0)
                self.assertEqual(constructor.call_args.args[0],'router-key-test')
                self.assertEqual(constructor.call_args.args[1],'')
                self.assertEqual(constructor.call_args.args[2],'openai/gpt-4o-mini')
                self.assertEqual(client.session.request.call_count,2)

    def test_local_status_does_not_load_credentials(self):
        with tempfile.TemporaryDirectory() as directory,patch.object(m,'load_environment') as load:
            self.assertEqual(m.main(['--state',str(Path(directory)/'state.sqlite3'),'--status']),0)
            load.assert_not_called()

    def test_wordpress_confirmation_records_cover_and_link(self):
        client=self.client()
        client.session.request=Mock(return_value=response(201,{'id':1001,'status':'draft',
            'featured_media':1002,'permalink':'https://giro73.com.br/?p=1001'}))
        self.assertEqual(client.submit('a'*64,{'status':'draft','image_url':'https://fonte.example/foto.jpg'}),1001)
        self.assertEqual(client.last_featured_media,1002)
        self.assertEqual(client.last_permalink,'https://giro73.com.br/?p=1001')


if __name__=='__main__':unittest.main()
