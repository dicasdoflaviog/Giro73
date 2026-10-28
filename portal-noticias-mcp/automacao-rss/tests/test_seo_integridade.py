import copy
import unittest
from test_rodizio_publicacao import generated,entry
import coletor_rss as m

class SeoIntegrityTests(unittest.TestCase):
    def valid(self):
        g=generated();g['title']='Creches municipais: prazo para inscrições'
        g['blocks']=[{'type':'p','text':'As creches municipais recebem inscrições até o prazo informado pela prefeitura.'}]
        g['meta']={'rank_math_focus_keyword':'creches municipais','rank_math_title':g['title'],
                   'rank_math_description':'Creches municipais: confira a orientação da prefeitura.'}
        return g

    def test_short_factual_note_has_no_artificial_word_count_error(self):
        p=m.payload_from(self.valid(),entry(),'noticias-regiao')
        self.assertEqual(m.seo_issues(p),[])
        self.assertNotIn('rank_math_seo_score',p['meta'])

    def test_literal_keyword_mismatch_is_explained_and_preserved_as_draft(self):
        p=m.payload_from(self.valid(),entry(),'noticias-regiao')
        p['meta']['rank_math_focus_keyword']='vagas nas creches'
        before=copy.deepcopy(p)
        status,reason=m.publication_decision({'publication_mode':'auto','seo_required':True},entry(),self.valid(),p)
        self.assertEqual(status,'draft')
        self.assertIn('seo_requer_revisao',reason)
        self.assertEqual(p,before)

    def test_sports_story_from_technology_feed_is_classified_by_topic(self):
        g=self.valid();g['editorial_category']='esportes'
        p=m.payload_from(g,entry(),'tecnologia-inovacao')
        self.assertEqual(p['category'],'esportes')
        self.assertIn('/category/esportes/',p['content'])

    def test_invalid_editorial_category_is_not_silently_accepted(self):
        g=self.valid();g['editorial_category']='inventada'
        with self.assertRaises(m.ItemError):m.payload_from(g,entry(),'noticias-regiao')

    def test_source_image_alt_is_kept_without_injecting_focus_keyword(self):
        p=m.payload_from(self.valid(),entry(),'noticias-regiao',{'url':'https://fonte.example/foto.webp','alt':'Fachada de prédio com portão azul'})
        self.assertEqual(p['image_alt'],'Fachada de prédio com portão azul')
        self.assertIn('alt="Fachada de prédio com portão azul"',p['content'])
