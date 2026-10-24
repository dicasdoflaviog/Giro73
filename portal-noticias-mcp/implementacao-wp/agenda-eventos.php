<?php
/** Agenda 73: campos comerciais, guia paginado e filtros progressivos. Após engajamento/autoridade regional. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_agenda_categories')) {
function g73_agenda_categories() {
 return array('shows_festas'=>'Shows & Festas','gastronomia_feiras'=>'Gastronomia & Feiras','esportes'=>'Esportes','teatro_cultura'=>'Teatro & Cultura','negocios_cursos'=>'Negócios & Cursos','infantil_familia'=>'Infantil & Família');
}
function g73_agenda_fields() {
 return array('g73_event_category'=>'Categoria','g73_event_location'=>'Local físico','g73_event_price'=>'Preço / situação do ingresso','g73_event_ticket_url'=>'Link de ingressos (opcional)');
}
function g73_agenda_phone($value) {
 $value=trim((string)$value);if($value&&!preg_match('/^[+0-9().\s-]+$/D',$value)){return '';}$explicit=0===strpos($value,'+');
 $digits=preg_replace('/\D/','',$value);
 if (!$digits) { return ''; }
 if (!$explicit&&in_array(strlen($digits),array(10,11),true)) { $digits='55'.$digits; }
 return preg_match('/^[1-9][0-9]{7,14}$/D',$digits)?'+'.$digits:'';
}
function g73_agenda_url_valid($url) {
 if (''===$url) { return true; }
 $p=wp_parse_url($url);
 return is_array($p)&&!empty($p['host'])&&isset($p['scheme'])&&in_array(strtolower($p['scheme']),array('http','https'),true)&&empty($p['user'])&&empty($p['pass']);
}
function g73_agenda_validate($meta) {
 if (isset($meta['g73_event_category'])&&''!==$meta['g73_event_category']&&!array_key_exists($meta['g73_event_category'],g73_agenda_categories())) { return new WP_Error('g73_event_category','Selecione uma categoria válida.',array('status'=>400)); }
 if (isset($meta['g73_event_ticket_url'])&&!g73_agenda_url_valid($meta['g73_event_ticket_url'])) { return new WP_Error('g73_event_ticket_url','Use uma URL completa HTTP/HTTPS, sem credenciais.',array('status'=>400)); }
 if (!empty($meta['g73_event_whatsapp'])&&!g73_agenda_phone($meta['g73_event_whatsapp'])) { return new WP_Error('g73_event_whatsapp','Informe telefone com DDI e DDD; para o Brasil, DDD e número também são aceitos.',array('status'=>400)); }
 return true;
}
add_action('init',function(){
 foreach(array_merge(g73_agenda_fields(),array('g73_event_whatsapp'=>'Campo legado de integração')) as $key=>$label) {
  $schema=array('type'=>'string','default'=>'','maxLength'=>'g73_event_ticket_url'===$key?2048:240);
  if ('g73_event_category'===$key) { $schema['enum']=array_merge(array(''),array_keys(g73_agenda_categories())); }
  register_post_meta('g73_event',$key,array('single'=>true,'type'=>'string','default'=>'','show_in_rest'=>array('schema'=>$schema),
   'sanitize_callback'=>function($v)use($key){return 'g73_event_whatsapp'===$key?g73_agenda_phone($v):('g73_event_ticket_url'===$key?esc_url_raw($v,array('http','https')):sanitize_text_field($v));},
   'auth_callback'=>function(){return current_user_can('manage_options');}));
 }
},30);
add_filter('rest_pre_insert_g73_event',function($post,$request){
 if(is_wp_error($post)){return $post;}$valid=g73_agenda_validate((array)$request->get_param('meta'));return is_wp_error($valid)?$valid:$post;
},15,2);
add_action('add_meta_boxes_g73_event',function(){add_meta_box('g73-event-sales','Categoria, local e link do evento','g73_agenda_metabox','g73_event','normal','high');});
function g73_agenda_metabox($post) {
 wp_nonce_field('g73_agenda_save','g73_agenda_nonce');
 echo '<p>O botão Ver evento abre o Link oficial do evento. Se esse campo estiver vazio, utiliza o Link de ingressos. Sem URL, o botão fica desativado. Modelos não possuem links ativos.</p>';
 foreach(g73_agenda_fields() as $key=>$label){$v=get_post_meta($post->ID,$key,true);echo '<p><label>'.esc_html($label).'<br>';
  if('g73_event_category'===$key){echo '<select name="'.esc_attr($key).'"><option value="">Não informada</option>';foreach(g73_agenda_categories() as $slug=>$name){echo '<option value="'.esc_attr($slug).'"'.selected($v,$slug,false).'>'.esc_html($name).'</option>';}echo '</select>';}
  else{echo '<input class="large-text" type="'.('g73_event_ticket_url'===$key?'url':'text').'" maxlength="'.('g73_event_ticket_url'===$key?'2048':'240').'" name="'.esc_attr($key).'" value="'.esc_attr($v).'">';}
  echo '</label></p>';
 }echo '<p>Exemplos de preço: Gratuito; A partir de R$ 40; Esgotado. Use o Link oficial do evento para a página com programação e informações completas.</p>';
}
add_action('save_post_g73_event',function($id){
 if(wp_is_post_revision($id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||!current_user_can('manage_options')||!isset($_POST['g73_agenda_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['g73_agenda_nonce'])),'g73_agenda_save')){return;}
 $meta=array();foreach(g73_agenda_fields() as $key=>$label){$meta[$key]=sanitize_text_field(isset($_POST[$key])?wp_unslash($_POST[$key]):'');}
 $valid=g73_agenda_validate($meta);if(is_wp_error($valid)){set_transient('g73_agenda_error_'.get_current_user_id(),$valid->get_error_message(),60);return;}
 foreach($meta as $key=>$v){update_post_meta($id,$key,$v);}
},20);
add_action('admin_notices',function(){$key='g73_agenda_error_'.get_current_user_id();$message=get_transient($key);if($message){delete_transient($key);echo '<div class="notice notice-error"><p>'.esc_html($message).' Os campos de ingressos não foram alterados.</p></div>';}});
function g73_agenda_icon($type) {
 $path='location'===$type?'<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>':'<path d="M3 5h18v5a2 2 0 0 0 0 4v5H3v-5a2 2 0 0 0 0-4V5Z"/><path d="M15 5v3m0 3v2m0 3v3"/>';
 return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false">'.$path.'</svg>';
}
function g73_agenda_card($id) {
 $demo=(bool)get_post_meta($id,'g73_event_demo',true);$date=get_post_meta($id,'g73_event_date',true);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,wp_timezone());
 if(!$d){return '';}$title=preg_replace('/^MODELO\s*[—-]\s*/u','',get_the_title($id));$cats=g73_agenda_categories();$cat=get_post_meta($id,'g73_event_category',true);$city=get_post_meta($id,'g73_event_city',true);
 $location=get_post_meta($id,'g73_event_location',true);$price=get_post_meta($id,'g73_event_price',true);$url='';
 foreach(array('g73_event_url','g73_event_ticket_url') as $key){$candidate=get_post_meta($id,$key,true);if($candidate&&g73_agenda_url_valid($candidate)){$url=$candidate;break;}}
 $label='Ver evento →';$attrs=' href="'.esc_url($url).'" target="_blank" rel="nofollow noopener noreferrer"';
 $cover=get_the_post_thumbnail($id,'medium_large',array('loading'=>'lazy','decoding'=>'async','class'=>'g73-event-cover','alt'=>($demo?'Imagem ilustrativa de demonstração — ':'').$title));$heading=esc_html($title);
 if(!$demo&&$url){$cover='<a class="g73-event-cover-link"'.$attrs.' aria-label="'.esc_attr($label.' — '.$title).'">'.$cover.'</a>';$heading='<a class="g73-event-title-link"'.$attrs.'>'.$heading.'</a>';}
 $html='<article class="g73-event-poster" data-g73-event-category="'.esc_attr($cat).'"><div class="g73-event-visual">'.$cover.'<time class="g73-event-date" datetime="'.esc_attr($date).'" aria-label="'.esc_attr(($demo?'Data ilustrativa: ':'').$d->format('d/m/Y')).'"><strong>'.$d->format('d').'</strong><span>'.esc_html(wp_date('M',$d->getTimestamp(),wp_timezone())).'</span></time></div>';
 $html.='<div class="g73-event-info"><div class="g73-event-city-pill">'.esc_html((isset($cats[$cat])?$cats[$cat]:'Evento regional').' • '.($city?:'Cidade a confirmar')).'</div>'.($demo?'<div class="g73-event-demo">Modelo • Não confirmado</div>':'').'<h3>'.$heading.'</h3><div class="g73-event-facts"><p>'.g73_agenda_icon('location').'<span>'.esc_html($location?:'Local a confirmar').'</span></p><p>'.g73_agenda_icon('price').'<span>'.esc_html($price?:'Valor a confirmar').'</span></p></div><div class="g73-event-actions">';
 $html.=(!$demo&&$url)?'<a class="g73-event-cta g73-event-open"'.$attrs.' aria-label="'.esc_attr($label.' — '.$title).'">'.$label.'</a>':'<button type="button" class="g73-event-cta g73-event-open" disabled aria-label="'.esc_attr(($demo?'Link indisponível em modelo':'Link do evento não informado').' — '.$title).'">'.$label.'</button>';
 $html.='</div>';
 if($demo){$html.='<details class="g73-event-program"><summary>Sobre este modelo</summary><p>Data ilustrativa: '.esc_html($d->format('d/m/Y')).'. Evento, local, horários e atrações não confirmados. Não há ingressos à venda neste card.</p></details>';}
 elseif(!$url){$html.='<p class="g73-event-help">Link do evento ainda não informado.</p>';}
 return $html.'</div></article>';
}
function g73_agenda_query_args($limit,$page=1,$category='') {
 $meta=array('relation'=>'AND',array('key'=>'g73_event_date','value'=>wp_date('Y-m-d'),'compare'=>'>=','type'=>'DATE'),array('relation'=>'OR',array('key'=>'g73_event_demo','compare'=>'NOT EXISTS'),array('key'=>'g73_event_demo','value'=>'1','compare'=>'!=')));
 if($category){$meta[]=array('key'=>'g73_event_category','value'=>$category);}
 return array('post_type'=>'g73_event','post_status'=>'publish','posts_per_page'=>$limit,'paged'=>$page,'meta_key'=>'g73_event_date','orderby'=>array('meta_value'=>'ASC','ID'=>'ASC'),'meta_query'=>$meta);
}
function g73_agenda_render($atts=array()) {
 $atts=shortcode_atts(array('directory'=>'0'),$atts);$directory='1'===$atts['directory'];$base=home_url('/agenda-73/');$cats=g73_agenda_categories();
 $category=$directory&&isset($_GET['g73_categoria'])&&is_string($_GET['g73_categoria'])?sanitize_key(wp_unslash($_GET['g73_categoria'])):'';if(!isset($cats[$category])){$category='';}
 $page=$directory&&isset($_GET['g73_agenda_page'])?max(1,absint($_GET['g73_agenda_page'])):1;$limit=$directory?24:4;
 $q=new WP_Query(g73_agenda_query_args($limit,$page,$category));$ids=wp_list_pluck($q->posts,'ID');$total=(int)$q->found_posts;$pages=(int)$q->max_num_pages;$models=false;
 // Modelos somente quando não há eventos confirmados, e apenas os rascunhos expressamente selecionados.
 if(!$total){$confirmed=$category?get_posts(g73_agenda_query_args(1)):array();if(!$confirmed){
  $demo_ids=array();foreach((array)get_option('g73_agenda_demo_ids',array()) as $id){if('g73_event'===get_post_type($id)&&'draft'===get_post_status($id)&&get_post_meta($id,'g73_event_demo',true)&&(!$category||$category===get_post_meta($id,'g73_event_category',true))){$demo_ids[]=absint($id);}}
  $total=count($demo_ids);$pages=(int)ceil($total/$limit);$ids=array_slice($demo_ids,($page-1)*$limit,$limit);$models=(bool)$ids;
 }}
 $uid=wp_unique_id('g73-agenda-');$GLOBALS['g73_agenda_runtime']=true;$complete=''===$category&&$total<=$limit;
 $html='<section class="g73-agenda-showcase" data-g73-agenda data-g73-agenda-complete="'.($complete?'1':'0').'" aria-labelledby="'.esc_attr($uid).'"><h2 id="'.esc_attr($uid).'">Agenda 73 • Festas, Shows &amp; Eventos no Extremo Sul</h2><nav class="g73-agenda-filters" aria-label="Categorias de eventos">';
 foreach(array_merge(array(''=>'Todos'),$cats) as $slug=>$name){$href=$slug?add_query_arg('g73_categoria',$slug,$base):$base;$html.='<a href="'.esc_url($href).'" data-g73-agenda-filter="'.esc_attr($slug).'"'.($category===$slug?' aria-current="true"':'').'>'.esc_html($name).'</a>';}
 $html.='</nav><p class="g73-agenda-note">Encontre experiências no Extremo Sul da Bahia. Confira programação, valores e disponibilidade com a organização antes de sair de casa.'.($models?' Os cards de demonstração têm datas ilustrativas: nenhum evento ou ingresso está confirmado. Imagem ilustrativa.':'').'</p><p class="g73-agenda-count" role="status" aria-live="polite">'.esc_html($total.' '.($models?'modelos de demonstração':($total===1?'evento':'eventos'))).'</p><div class="g73-agenda-grid">';
 foreach($ids as $id){$html.=g73_agenda_card($id);}$html.='</div><p class="g73-agenda-empty"'.($ids?' hidden':'').'>Nenhum evento nesta seleção. Consulte outras categorias ou volte em breve.</p>';
 if($directory&&$pages>1){$links=paginate_links(array('base'=>add_query_arg('g73_agenda_page','%#%',$category?add_query_arg('g73_categoria',$category,$base):$base),'format'=>'','current'=>$page,'total'=>$pages,'type'=>'list','prev_text'=>'← Anterior','next_text'=>'Próxima →'));$html.='<nav class="g73-agenda-pagination" aria-label="Páginas da agenda">'.$links.'</nav>';}
 if(!$directory){$html.='<p class="g73-agenda-more"><a href="'.esc_url($base).'">Ver agenda completa →</a></p>';}
 return $html.'</section>';
}
add_filter('body_class',function($classes){if(is_page('agenda-73')){$classes[]='g73-agenda-page';}return $classes;});
add_shortcode('giro73_agenda_vitrine','g73_agenda_render');
add_shortcode('giro73_agenda_completa',function(){return g73_agenda_render(array('directory'=>'1'));});
add_filter('render_block_core/shortcode',function($html,$block){$raw=trim(isset($block['innerHTML'])?$block['innerHTML']:'');return '[giro73_agenda_completa]'===$raw?do_shortcode($raw):$html;},30,2);
add_action('wp_footer',function(){if(!empty($GLOBALS['g73_agenda_runtime'])){echo '<script id="g73-agenda-runtime">'.g73_agenda_js().'</script>';}},37);
function g73_agenda_js(){return <<<'G73AGENDA'
/* Filtro local quando todos os eventos estão no HTML; links nativos cobrem o catálogo paginado. */
(()=>{'use strict';document.querySelectorAll('[data-g73-agenda]').forEach(section=>{
if(section.dataset.g73AgendaComplete!=='1')return;
const cards=[...section.querySelectorAll('[data-g73-event-category]')],filters=[...section.querySelectorAll('[data-g73-agenda-filter]')],count=section.querySelector('.g73-agenda-count'),empty=section.querySelector('.g73-agenda-empty');
filters.forEach(link=>link.addEventListener('click',event=>{if(event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||event.button!==0)return;event.preventDefault();let n=0;const category=link.dataset.g73AgendaFilter;
cards.forEach(card=>{card.hidden=!!category&&card.dataset.g73EventCategory!==category;if(!card.hidden)n++;});
filters.forEach(item=>{if(item===link)item.setAttribute('aria-current','true');else item.removeAttribute('aria-current');});empty.hidden=n>0;count.textContent=n+' '+(n===1?'resultado':'resultados');
}));});})();
G73AGENDA;
}
// Prévia autenticada para gestão e validação de links sem publicar eventos fictícios.
add_action('rest_api_init',function(){register_rest_route('g73/v1','/events/preview/(?P<id>\d+)',array('methods'=>'GET','permission_callback'=>function($r){return current_user_can('manage_options')&&'g73_event'===get_post_type((int)$r['id']);},'callback'=>function($r){$response=new WP_REST_Response(array('html'=>g73_agenda_card((int)$r['id'])));$response->header('Cache-Control','private, no-store');return $response;}));});
}
