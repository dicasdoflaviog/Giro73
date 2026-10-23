<?php
/** Giro73: colunas editoriais, vitrine regional e leitura nativa. Executar após engajamento-regional.php. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_reg_profiles')) {
function g73_reg_slugs() { return array('arthur-valente','helena-gusmao','lucas-vianna','beto-santana','malu-santoro','clara-mendes'); }
function g73_reg_profiles() {
 $rows=get_posts(array('post_type'=>'g73_columnist','post_status'=>'publish','posts_per_page'=>6,'no_found_rows'=>true,'orderby'=>'menu_order','order'=>'ASC'));
 $out=array(); foreach($rows as $p) {
  $slug=get_post_meta($p->ID,'g73_columnist_slug',true); if(!in_array($slug,g73_reg_slugs(),true)){continue;}
  $out[$slug]=array('id'=>$p->ID,'slug'=>$slug,'name'=>get_post_meta($p->ID,'g73_columnist_name',true)?:$p->post_title,
   'title'=>get_post_meta($p->ID,'g73_columnist_title',true),'bio'=>get_post_meta($p->ID,'g73_columnist_bio',true),
   'avatar'=>(int)get_post_meta($p->ID,'g73_columnist_avatar',true),'area'=>get_post_meta($p->ID,'g73_columnist_area',true),
   'color'=>get_post_meta($p->ID,'g73_columnist_color',true)?:'#2C1067','type'=>'coluna_editorial');
 } return $out;
}
function g73_reg_profile($id=0) {
 $id=$id?:get_the_ID();$slug=get_post_meta($id,'g73_columnist_slug',true);$rows=g73_reg_profiles();return isset($rows[$slug])?$rows[$slug]:null;
}
function g73_reg_avatar($p) {
 return $p['avatar']?'<figure class="g73-opinion-portrait">'.wp_get_attachment_image($p['avatar'],'full',false,array('class'=>'g73-opinion-avatar','width'=>64,'height'=>64,'loading'=>'lazy','decoding'=>'async','alt'=>'Imagem temática da coluna '.(isset($p['title'])?$p['title']:$p['name']))).'</figure>':'';
}
add_action('init',function(){
 register_post_type('g73_columnist',array('labels'=>array('name'=>'Colunistas G73','singular_name'=>'Coluna editorial','add_new_item'=>'Nova coluna editorial','edit_item'=>'Editar coluna editorial'),
  'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>true,'show_in_rest'=>true,'rest_base'=>'g73_columnist','menu_icon'=>'dashicons-id','supports'=>array('title','custom-fields','page-attributes'),'rewrite'=>false,'map_meta_cap'=>false,
  'capabilities'=>array_fill_keys(array('edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts'),'manage_options')));
 foreach(array('post','g73_columnist') as $type){foreach(array('slug','name','title','bio','avatar') as $field){
  $schema=array('type'=>'avatar'===$field?'integer':'string','default'=>'avatar'===$field?0:'');
  if('slug'===$field){$schema['enum']=array_merge(array(''),g73_reg_slugs());}if('avatar'===$field){$schema['minimum']=0;}
  register_post_meta($type,'g73_columnist_'.$field,array('single'=>true,'type'=>$schema['type'],'default'=>$schema['default'],'show_in_rest'=>array('schema'=>$schema),
   'sanitize_callback'=>function($v)use($field){return 'avatar'===$field?absint($v):('bio'===$field?sanitize_textarea_field($v):sanitize_text_field($v));},
   'auth_callback'=>function($a,$k,$id){return current_user_can('edit_post',$id);}));
 }}
 foreach(array('area','color') as $field){register_post_meta('g73_columnist','g73_columnist_'.$field,array('single'=>true,'type'=>'string','default'=>'','show_in_rest'=>true,'sanitize_callback'=>'color'===$field?'sanitize_hex_color':'sanitize_text_field','auth_callback'=>function(){return current_user_can('manage_options');}));}
 add_post_type_support('g73_event','thumbnail');
 register_setting('g73_regional','g73_agenda_demo_ids',array('type'=>'array','default'=>array(),'sanitize_callback'=>function($v){return array_values(array_unique(array_filter(array_map('absint',array_slice((array)$v,0,4)))));},'show_in_rest'=>array('schema'=>array('type'=>'array','maxItems'=>4,'items'=>array('type'=>'integer','minimum'=>1)))));
},20);
add_filter('rank_math/sitemap/exclude_post_type',function($v,$t){return 'g73_columnist'===$t?true:$v;},10,2);
add_filter('use_block_editor_for_post_type',function($v,$t){return 'g73_columnist'===$t?false:$v;},10,2);
add_filter('rest_pre_dispatch',function($v,$s,$r){if(preg_match('~^/wp/v2/g73_columnist(?:/|$)~',$r->get_route())&&!current_user_can('manage_options')){return new WP_Error('g73_profiles_private','Gestão reservada ao administrador.',array('status'=>is_user_logged_in()?403:401));}return $v;},10,3);
function g73_reg_sync($id) {
 if('post'!==get_post_type($id)){return;}$p=g73_reg_profile($id);
 foreach(array('name','title','bio','avatar') as $field){if($p){update_post_meta($id,'g73_columnist_'.$field,$p[$field]);}else{delete_post_meta($id,'g73_columnist_'.$field);}}
 if($p){update_post_meta($id,'g73_is_opinion',true);}
}
add_action('updated_post_meta',function($mid,$id,$key){if('g73_columnist_slug'===$key){g73_reg_sync($id);}},20,3);
add_action('added_post_meta',function($mid,$id,$key){if('g73_columnist_slug'===$key){g73_reg_sync($id);}},20,3);
add_action('rest_after_insert_post',function($p){g73_reg_sync($p->ID);},30);
function g73_reg_badge($id=0) {
 $p=g73_reg_profile($id);if(!$p){return '';}
 return '<div class="g73-columnist-badge" style="--g73-column-color:'.esc_attr($p['color']).'">'.g73_reg_avatar($p).'<div><div class="g73-opinion-pill">'.esc_html($p['title']).'</div><strong>'.esc_html($p['name']).'</strong><small>Opinião • Coluna da Redação</small></div></div>';
}
function g73_reg_byline($id=0) {
 $p=g73_reg_profile($id);return '<p class="g73-byline-text">Por <strong>Redação Giro73</strong>'.($p?' — '.esc_html($p['title']):' — Teixeira de Freitas, BA').'</p>';
}
function g73_reg_signature($id=0) {
 $id=$id?:get_the_ID();$p=g73_reg_profile($id);$owner=(int)get_post_field('post_author',$id);
 if(!$p){return '<section class="g73-author"><h2>Redação Giro73</h2><p><a href="'.esc_url(get_author_posts_url($owner)).'">'.esc_html(get_the_author_meta('display_name',$owner)).'</a></p><p>Equipe de jornalismo do Giro73 focada na cobertura diária de Teixeira de Freitas, Extremo Sul da Bahia e notícias nacionais com impacto no bolso do cidadão.</p></section>';}
 return '<section class="g73-author g73-column-signature">'.g73_reg_badge($id).'<p>'.esc_html($p['bio']).'</p><p class="g73-editorial-disclosure">Responsabilidade editorial: <a href="'.esc_url(home_url('/politica-editorial/')).'">Redação Giro73</a>, sob direção de Elder Mendonça Jr.</p></section>';
}
function g73_reg_opinions() {
 $profiles=g73_reg_profiles();if(!$profiles){return '';}
 $html='<section class="g73-opinions" aria-labelledby="g73-opinions-heading"><h2 id="g73-opinions-heading">Colunistas &amp; Opinião</h2><p class="g73-opinions-note">Análise, bastidores e opinião especializada sobre o Extremo Sul da Bahia e o Brasil.</p><div class="g73-opinions-grid">';
 foreach($profiles as $p){$last=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'no_found_rows'=>true,'meta_key'=>'g73_columnist_slug','meta_value'=>$p['slug']));
  $html.='<div class="g73-opinion-card" id="coluna-'.esc_attr($p['slug']).'" style="--g73-column-color:'.esc_attr($p['color']).'">'.g73_reg_avatar($p).'<div class="g73-opinion-copy"><div class="g73-opinion-pill">'.esc_html($p['title']).'</div><h3>'.esc_html($p['name']).'</h3><p>'.esc_html($p['area']).'</p>'.($last?'<a class="g73-opinion-latest" href="'.esc_url(get_permalink($last[0])).'">'.esc_html(get_the_title($last[0])).' →</a>':'<span class="g73-opinion-latest">Primeira coluna em preparação</span>').'<details><summary>Sobre a coluna</summary><p>'.esc_html($p['bio']).'</p></details></div></div>';
 } $html.='</div><details class="g73-opinion-photo-credit"><summary>Créditos das imagens ilustrativas</summary><ul>';foreach($profiles as $p){$caption=wp_get_attachment_caption($p['avatar']);if($caption){$html.='<li><strong>'.esc_html($p['title']).':</strong> '.wp_kses_post($caption).'</li>';}}return $html.'</ul></details></section>';
}
function g73_reg_agenda() {
 $ids=get_posts(array('post_type'=>'g73_event','post_status'=>'publish','posts_per_page'=>4,'fields'=>'ids','meta_key'=>'g73_event_date','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array(array('key'=>'g73_event_date','value'=>wp_date('Y-m-d'),'compare'=>'>=','type'=>'DATE'),array('relation'=>'OR',array('key'=>'g73_event_demo','compare'=>'NOT EXISTS'),array('key'=>'g73_event_demo','value'=>'1','compare'=>'!=')))));
 // Somente modelos selecionados explicitamente pelo administrador podem aparecer na vitrine pública.
 foreach((array)get_option('g73_agenda_demo_ids',array()) as $id){if(count($ids)>=4){break;}if('g73_event'===get_post_type($id)&&'draft'===get_post_status($id)&&get_post_meta($id,'g73_event_demo',true)&&!in_array((int)$id,$ids,true)){$ids[]=(int)$id;}}
 if(!$ids){return '';}$html='<section class="g73-agenda-showcase" aria-labelledby="g73-agenda-heading"><h2 id="g73-agenda-heading">Agenda 73 • Festas, Shows &amp; Eventos no Extremo Sul</h2><p class="g73-agenda-note">Cultura, turismo e encontros da região. Cards marcados como modelo são demonstrações: datas ilustrativas, sem evento ou programação confirmados. Imagem ilustrativa de demonstração.</p><div class="g73-agenda-grid">';
 foreach($ids as $id){$demo=(bool)get_post_meta($id,'g73_event_demo',true);$date=get_post_meta($id,'g73_event_date',true);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,wp_timezone());if(!$d){continue;}
  $title=preg_replace('/^MODELO\s*[—-]\s*/u','',get_the_title($id));$url=get_post_meta($id,'g73_event_url',true);
  $html.='<div class="g73-event-poster"><div class="g73-event-visual">'.get_the_post_thumbnail($id,'medium_large',array('loading'=>'lazy','decoding'=>'async','class'=>'g73-event-cover','alt'=>($demo?'Ilustração de demonstração — ':'').$title)).'<time class="g73-event-date" datetime="'.esc_attr($date).'" aria-label="'.esc_attr(($demo?'Data ilustrativa: ':'').$d->format('d/m/Y')).'"><strong>'.$d->format('d').'</strong><span>'.esc_html(wp_date('M',$d->getTimestamp(),wp_timezone())).'</span></time></div><div class="g73-event-info"><span class="g73-event-city-pill">'.esc_html(get_post_meta($id,'g73_event_city',true)).'</span>'.($demo?'<span class="g73-event-demo">Modelo • Não confirmado</span>':'').'<h3>'.esc_html($title).'</h3>';
  if($demo){$html.='<details class="g73-event-program"><summary>Ver programação →</summary><p>Modelo visual editável. Data ilustrativa: '.esc_html($d->format('d/m/Y')).'. Evento, local, horários e atrações ainda não confirmados. Não há ingressos à venda neste card.</p></details>';}
  elseif($url){$html.='<a class="g73-event-program-link" href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">Ver programação →</a>';}
  else{$html.='<span class="g73-event-program-link">Programação em atualização</span>';}$html.='</div></div>';
 }return $html.'</div></section>';
}
function g73_reg_voice() {return '<section class="g73-audio-player" aria-label="Leitura da notícia"><button type="button" data-g73-voice-play aria-label="Ouvir notícia">🔊 Ouvir notícia</button><button type="button" data-g73-voice-stop hidden>Parar</button><span data-g73-voice-status role="status" aria-live="polite">Voz do seu navegador • Português</span></section>';}
foreach(array('giro73_colunistas'=>'g73_reg_opinions','giro73_agenda_vitrine'=>'g73_reg_agenda','giro73_colunista_selo'=>'g73_reg_badge','giro73_autoria_editorial'=>'g73_reg_byline','giro73_assinatura_editorial'=>'g73_reg_signature','giro73_ouvir_noticia'=>'g73_reg_voice') as $tag=>$callback){add_shortcode($tag,$callback);}
add_filter('render_block_core/shortcode',function($html,$block){$raw=trim(isset($block['innerHTML'])?$block['innerHTML']:'');return preg_match('~^\[giro73_(?:agenda_vitrine|colunista_selo|autoria_editorial|assinatura_editorial|ouvir_noticia)\]$~D',$raw)?do_shortcode($raw):$html;},20,2);
add_action('wp_footer',function(){if(is_singular('post')){echo '<script id="g73-voice-runtime">'.g73_reg_voice_js().'</script>';}},36);
function g73_reg_voice_js(){return <<<'G73VOICE'
/* Giro73: leitura local sob comando do leitor; nenhum áudio ou serviço externo. */
(()=>{'use strict';const panel=document.querySelector('.g73-audio-player');if(!panel)return;
const play=panel.querySelector('[data-g73-voice-play]'),stop=panel.querySelector('[data-g73-voice-stop]'),status=panel.querySelector('[data-g73-voice-status]');
if(!('speechSynthesis'in window)||!('SpeechSynthesisUtterance'in window)){play.disabled=true;status.textContent='Leitura por voz indisponível neste navegador.';return;}
const engine=window.speechSynthesis;let state='idle',queue=[],index=0,generation=0,current=null;
const ignored='.g73-photo-credit,.g73-meta,.g73-partner-unit,.g73-read-also,.g73-related,.g73-whatsapp-card,.g73-reactions,.g73-poll,.g73-author,aside,figure,nav,[data-nosnippet]';
function parts(){const main=document.querySelector('.g73-single');if(!main)return[];const elements=[main.querySelector('h1'),main.querySelector('.g73-summary'),...main.querySelectorAll('.entry-content p')];return elements.filter(e=>e&&!e.closest(ignored)).flatMap(e=>{let text=e.textContent.replace(/\s+/g,' ').trim(),out=[];while(text.length>220){let cut=text.lastIndexOf(' ',220);if(cut<50)cut=220;out.push(text.slice(0,cut));text=text.slice(cut).trim();}if(text)out.push(text);return out;});}
function reset(message='Leitura encerrada.'){generation++;engine.cancel();current=null;state='idle';index=0;play.textContent='🔊 Ouvir notícia';play.setAttribute('aria-label','Ouvir notícia');play.setAttribute('aria-pressed','false');stop.hidden=true;status.textContent=message;}
function speak(token){if(token!==generation||state==='idle')return;if(index>=queue.length){reset('Leitura concluída.');return;}
current=new SpeechSynthesisUtterance(queue[index]);current.lang='pt-BR';current.rate=1.05;const voices=engine.getVoices();const voice=voices.find(v=>/^pt[-_]BR$/i.test(v.lang))||voices.find(v=>/^pt\b/i.test(v.lang));if(voice)current.voice=voice;
current.onstart=()=>{if(token===generation)status.textContent=`Lendo trecho ${index+1} de ${queue.length}`;};current.onend=()=>{if(token!==generation)return;index++;if(state==='playing')speak(token);};current.onerror=e=>{if(token===generation&&!['canceled','interrupted'].includes(e.error))reset('Não foi possível iniciar a voz. Tente novamente ou verifique as vozes do aparelho.');};engine.speak(current);}
play.addEventListener('click',()=>{if(state==='playing'){state='paused';engine.pause();play.textContent='▶ Continuar';play.setAttribute('aria-label','Continuar leitura');play.setAttribute('aria-pressed','false');status.textContent='Leitura pausada.';return;}
if(state==='paused'){state='playing';engine.resume();play.textContent='⏸ Pausar';play.setAttribute('aria-label','Pausar leitura');play.setAttribute('aria-pressed','true');status.textContent=`Lendo trecho ${index+1} de ${queue.length}`;if(!engine.speaking)speak(generation);return;}
queue=parts();if(!queue.length){status.textContent='Texto indisponível para leitura.';return;}engine.cancel();generation++;index=0;state='playing';play.textContent='⏸ Pausar';play.setAttribute('aria-label','Pausar leitura');play.setAttribute('aria-pressed','true');stop.hidden=false;status.textContent='Preparando a voz…';speak(generation);});
stop.addEventListener('click',()=>reset());window.addEventListener('pagehide',()=>reset());
})();
G73VOICE;
}
add_action('rest_api_init',function(){
 register_rest_field('post','g73_columnist_profile',array('get_callback'=>function($p){return g73_reg_profile($p['id']);},'schema'=>array('type'=>array('object','null'),'context'=>array('view','edit'),'readonly'=>true)));
 register_rest_route('g73/v1','/columnists',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){return array_values(g73_reg_profiles());}));
 register_rest_route('g73/v1','/editorial-preview/(?P<id>\d+)',array('methods'=>'GET','permission_callback'=>function($r){return 'post'===get_post_type((int)$r['id'])&&current_user_can('edit_post',(int)$r['id']);},'callback'=>function($r){$id=(int)$r['id'];$response=new WP_REST_Response(array('profile'=>g73_reg_profile($id),'badge'=>g73_reg_badge($id),'byline'=>g73_reg_byline($id),'signature'=>g73_reg_signature($id)));$response->header('Cache-Control','private, no-store');return $response;}));
});
add_action('add_meta_boxes',function(){
 add_meta_box('g73-columnist-assignment','Coluna editorial','g73_reg_post_box','post','side');
 add_meta_box('g73-columnist-fields','Perfil da coluna editorial','g73_reg_profile_box','g73_columnist','normal','high');
});
function g73_reg_post_box($post) {wp_nonce_field('g73_reg_post','g73_reg_post_nonce');echo '<p>Selecione apenas para artigos escritos para esta coluna. A autoria WordPress e a responsabilidade editorial permanecem identificadas.</p><select name="g73_columnist_slug" style="max-width:100%"><option value="">Redação / autor WordPress</option>';foreach(g73_reg_profiles() as $p){echo '<option value="'.esc_attr($p['slug']).'"'.selected(get_post_meta($post->ID,'g73_columnist_slug',true),$p['slug'],false).'>'.esc_html($p['name'].' — '.$p['title']).'</option>';}echo '</select>';}
add_action('save_post_post',function($id){if(wp_is_post_revision($id)||defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||!current_user_can('edit_post',$id)||!isset($_POST['g73_reg_post_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['g73_reg_post_nonce'])),'g73_reg_post')){return;}$slug=sanitize_key(isset($_POST['g73_columnist_slug'])?wp_unslash($_POST['g73_columnist_slug']):'');if(''===$slug||in_array($slug,g73_reg_slugs(),true)){update_post_meta($id,'g73_columnist_slug',$slug);g73_reg_sync($id);}});
function g73_reg_profile_box($post) {
 wp_nonce_field('g73_reg_profile','g73_reg_profile_nonce');echo '<p><strong>Coluna da Redação Giro73.</strong> Use autoria institucional enquanto não houver profissional real identificado e foto autorizada.</p>';
 foreach(array('slug'=>'Identificador (slug)','name'=>'Nome público','title'=>'Nome da coluna','area'=>'Editoria / área','color'=>'Cor (#hex)') as $k=>$label){echo '<p><label>'.esc_html($label).'<br><input class="regular-text" name="g73_columnist_'.$k.'" value="'.esc_attr(get_post_meta($post->ID,'g73_columnist_'.$k,true)).'"></label></p>';}
 echo '<p><label>Bio editorial<br><textarea class="large-text" rows="4" name="g73_columnist_bio">'.esc_textarea(get_post_meta($post->ID,'g73_columnist_bio',true)).'</textarea></label></p><p><label>ID do avatar WebP (Biblioteca de Mídia)<br><input type="number" min="0" id="g73-profile-avatar" name="g73_columnist_avatar" value="'.(int)get_post_meta($post->ID,'g73_columnist_avatar',true).'"></label></p><p><button class="button" type="button" id="g73-profile-select">Selecionar avatar WebP</button></p>'.g73_reg_avatar(array('avatar'=>(int)get_post_meta($post->ID,'g73_columnist_avatar',true),'name'=>$post->post_title));
}
add_action('admin_enqueue_scripts',function(){if(!get_current_screen()||'g73_columnist'!==get_current_screen()->post_type){return;}wp_enqueue_media();wp_add_inline_script('media-editor',<<<'G73PROFILE'
document.addEventListener('DOMContentLoaded',()=>{const b=document.getElementById('g73-profile-select');if(!b)return;b.addEventListener('click',()=>{const m=wp.media({title:'Imagem WebP da coluna',library:{type:'image/webp'},button:{text:'Usar avatar'},multiple:false});m.on('select',()=>{const image=m.state().get('selection').first().toJSON();if(image.mime==='image/webp')document.getElementById('g73-profile-avatar').value=image.id;});m.open();});});
G73PROFILE
);});
add_action('save_post_g73_columnist',function($id){if(wp_is_post_revision($id)||defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||!current_user_can('manage_options')||!isset($_POST['g73_reg_profile_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['g73_reg_profile_nonce'])),'g73_reg_profile')){return;}
 foreach(array('slug','name','title','area','color','bio','avatar') as $k){$v=isset($_POST['g73_columnist_'.$k])?wp_unslash($_POST['g73_columnist_'.$k]):'';$v='avatar'===$k?absint($v):('color'===$k?sanitize_hex_color($v):sanitize_textarea_field($v));if('slug'===$k&&!in_array($v,g73_reg_slugs(),true)){continue;}update_post_meta($id,'g73_columnist_'.$k,$v);}if(function_exists('g73_eng_purge_all')){g73_eng_purge_all();}
});
add_action('rest_after_insert_g73_columnist',function(){if(function_exists('g73_eng_purge_all')){g73_eng_purge_all();}});
add_action('admin_menu',function(){add_options_page('Autoridade Regional G73','Autoridade Regional G73','manage_options','g73-regional','g73_reg_settings');});
function g73_reg_settings() {
 if(!current_user_can('manage_options')){return;}$ids=(array)get_option('g73_agenda_demo_ids',array());
 echo '<div class="wrap"><h1>Autoridade Regional G73</h1><p><a href="'.esc_url(admin_url('edit.php?post_type=g73_columnist')).'">Gerenciar as seis colunas editoriais</a> • <a href="'.esc_url(admin_url('edit.php?post_type=g73_event')).'">Gerenciar Agenda 73 e imagens destacadas</a></p><form method="post" action="options.php">';settings_fields('g73_regional');
 echo '<h2>Vitrine de demonstração da Agenda 73</h2><p>Selecione até quatro IDs de eventos-modelo em rascunho. Os cards exibem “Modelo • Não confirmado” e não entram nos sitemaps. Deixe vazio para mostrar somente eventos realmente publicados.</p>';
 for($i=0;$i<4;$i++){echo '<p><label>ID do modelo '.($i+1).' <input type="number" min="0" name="g73_agenda_demo_ids[]" value="'.(isset($ids[$i])?(int)$ids[$i]:0).'"></label></p>';}submit_button();echo '</form></div>';
}
add_action('update_option_g73_agenda_demo_ids',function(){if(function_exists('g73_eng_purge_all')){g73_eng_purge_all();}});
}
