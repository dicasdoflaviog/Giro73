<?php
/** Giro73: participação, convite WhatsApp, opinião, agenda e impressão. Snippet 9. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_eng_post')) {
function g73_eng_group_url($value) {
 $url=esc_url_raw(trim((string)$value),array('https'));
 if(''===$url||preg_match('~^https://chat\.whatsapp\.com/[A-Za-z0-9]+/?(?:\?[^\s]*)?$~D',$url)){return $url;}
 add_settings_error('g73_whatsapp_group_url','invalid_group','Use o convite oficial https://chat.whatsapp.com/...');return get_option('g73_whatsapp_group_url','');
}
function g73_eng_columnists($input=null){if(null===$input){$input=get_option('g73_columnists',array());}$rows=array();foreach(array_slice(is_array($input)?$input:array(),0,4) as $row){
 $id=isset($row['author_id'])?absint($row['author_id']):0;$avatar=isset($row['avatar_id'])?absint($row['avatar_id']):0;
 $rows[]=array('author_id'=>get_userdata($id)?$id:0,'name'=>sanitize_text_field(isset($row['name'])?$row['name']:''),'area'=>sanitize_text_field(isset($row['area'])?$row['area']:''),'avatar_id'=>'image/webp'===get_post_mime_type($avatar)?$avatar:0);
 }while(count($rows)<3){$rows[]=array('author_id'=>0,'name'=>'','area'=>'','avatar_id'=>0);}return $rows;
}
function g73_eng_post($id){$p=get_post(absint($id));if(!$p||'post'!==$p->post_type||$p->post_password){return false;}
 if('publish'===$p->post_status||in_array($p->post_status,array('draft','private','pending'),true)&&current_user_can('edit_post',$p->ID)){return $p;}return false;
}
function g73_eng_meta(){return array(
 'g73_reactions'=>array('type'=>'object','default'=>array('util'=>0,'surpreendente'=>0,'indignado'=>0),'additionalProperties'=>false,'properties'=>array_fill_keys(array('util','surpreendente','indignado'),array('type'=>'integer','minimum'=>0))),
 'g73_poll_enabled'=>array('type'=>'boolean','default'=>false),'g73_poll_question'=>array('type'=>'string','default'=>'','maxLength'=>180),
 'g73_poll_options'=>array('type'=>'array','default'=>array(),'maxItems'=>5,'items'=>array('type'=>'string','minLength'=>1,'maxLength'=>100)),
 'g73_poll_votes'=>array('type'=>'array','default'=>array(),'maxItems'=>5,'items'=>array('type'=>'integer','minimum'=>0)),
 'g73_poll_placement'=>array('type'=>'string','default'=>'article','enum'=>array('article','sidebar','both')),
 'g73_is_opinion'=>array('type'=>'boolean','default'=>false));}
function g73_eng_sanitize_meta($value,$key){
 if(in_array($key,array('g73_poll_enabled','g73_is_opinion'),true)){return (bool)$value;}
 if('g73_poll_question'===$key){return sanitize_text_field($value);}
 if('g73_poll_placement'===$key){return in_array($value,array('article','sidebar','both'),true)?$value:'article';}
 if('g73_poll_options'===$key){return array_values(array_slice(array_filter(array_map('sanitize_text_field',(array)$value),function($v){return ''!==$v;}),0,5));}
 if('g73_poll_votes'===$key){return array_values(array_map('absint',array_slice((array)$value,0,5)));}
 $out=array('util'=>0,'surpreendente'=>0,'indignado'=>0);foreach($out as $k=>$v){$out[$k]=isset($value[$k])?absint($value[$k]):0;}return $out;
}
add_action('init',function(){
 register_setting('g73_engagement','g73_whatsapp_group_url',array('type'=>'string','default'=>'','sanitize_callback'=>'g73_eng_group_url','show_in_rest'=>array('schema'=>array('type'=>'string','pattern'=>'^(|https://chat\\.whatsapp\\.com/[A-Za-z0-9]+/?(?:\\?[^\\s]*)?)$'))));
 register_setting('g73_engagement','g73_columnists',array('type'=>'array','default'=>g73_eng_columnists(array()),'sanitize_callback'=>'g73_eng_columnists','show_in_rest'=>array('schema'=>array('type'=>'array','maxItems'=>4,'items'=>array('type'=>'object','additionalProperties'=>false,'properties'=>array('author_id'=>array('type'=>'integer','minimum'=>0),'name'=>array('type'=>'string'),'area'=>array('type'=>'string'),'avatar_id'=>array('type'=>'integer','minimum'=>0)))))));
 foreach(g73_eng_meta() as $key=>$schema){register_post_meta('post',$key,array('type'=>$schema['type'],'single'=>true,'default'=>$schema['default'],'show_in_rest'=>array('schema'=>$schema),
 'sanitize_callback'=>function($v)use($key){return g73_eng_sanitize_meta($v,$key);},'auth_callback'=>function($allowed,$key,$id){return current_user_can('edit_post',$id);}));}
 register_post_type('g73_event',array('labels'=>array('name'=>'Agenda G73','singular_name'=>'Evento','add_new_item'=>'Novo evento','edit_item'=>'Editar evento'),
 'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>true,'rest_base'=>'g73_event','menu_icon'=>'dashicons-calendar-alt','supports'=>array('title','custom-fields'),'rewrite'=>false,'has_archive'=>false,'map_meta_cap'=>false,
 'capabilities'=>array_fill_keys(array('edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts'),'manage_options')));
 foreach(array('g73_event_date'=>array('type'=>'string','default'=>'','pattern'=>'^(|[0-9]{4}-[0-9]{2}-[0-9]{2})$'),'g73_event_city'=>array('type'=>'string','default'=>''),'g73_event_url'=>array('type'=>'string','default'=>''),'g73_event_demo'=>array('type'=>'boolean','default'=>false)) as $key=>$schema){register_post_meta('g73_event',$key,array('type'=>$schema['type'],'single'=>true,'default'=>$schema['default'],'show_in_rest'=>array('schema'=>$schema),'sanitize_callback'=>function($v)use($key){return 'g73_event_demo'===$key?(bool)$v:('g73_event_url'===$key?esc_url_raw($v,array('http','https')):sanitize_text_field($v));},'auth_callback'=>function(){return current_user_can('manage_options');}));}
});
add_filter('rank_math/sitemap/exclude_post_type',function($value,$type){return 'g73_event'===$type?true:$value;},10,2);
add_filter('use_block_editor_for_post_type',function($use,$type){return 'g73_event'===$type?false:$use;},10,2);
add_filter('comments_open',function($open,$id){return 'post'===get_post_type($id)?false:$open;},20,2);
add_filter('pings_open',function($open,$id){return 'post'===get_post_type($id)?false:$open;},20,2);
function g73_eng_poll($id){if(!get_post_meta($id,'g73_poll_enabled',true)){return null;}$q=get_post_meta($id,'g73_poll_question',true);$o=(array)get_post_meta($id,'g73_poll_options',true);if(!$q||count($o)<2){return null;}
 $raw=(array)get_post_meta($id,'g73_poll_votes',true);$votes=array();foreach($o as $i=>$label){$votes[]=isset($raw[$i])?absint($raw[$i]):0;}
 return array('question'=>$q,'options'=>$o,'votes'=>$votes,'total'=>array_sum($votes),'version'=>substr(hash('sha256',$q.wp_json_encode($o)),0,20));
}
function g73_eng_data($id){$raw=(array)get_post_meta($id,'g73_reactions',true);return array('id'=>(int)$id,'reactions'=>g73_eng_sanitize_meta($raw,'g73_reactions'),'poll'=>g73_eng_poll($id));}
function g73_eng_reader($create=false){$cookie=isset($_COOKIE['g73_reader'])?sanitize_text_field(wp_unslash($_COOKIE['g73_reader'])):'';$parts=explode('.',$cookie);
 if(2===count($parts)&&preg_match('/^[a-f0-9-]{36}$/D',$parts[0])&&hash_equals(hash_hmac('sha256',$parts[0],wp_salt('nonce')),$parts[1])){return $parts[0];}
 if(!$create){return false;}$id=wp_generate_uuid4();$value=$id.'.'.hash_hmac('sha256',$id,wp_salt('nonce'));
 setcookie('g73_reader',$value,array('expires'=>time()+YEAR_IN_SECONDS,'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Lax'));return $id;
}
function g73_eng_response($data){do_action('litespeed_control_set_nocache','Participação atual');$r=new WP_REST_Response($data);$r->header('Cache-Control','private, no-store, max-age=0');return $r;}
// Compare-and-swap mantém os contadores corretos mesmo com cliques simultâneos.
function g73_eng_increment($id,$key,$choice){global $wpdb;
 for($i=0;$i<8;$i++){wp_cache_delete($id,'post_meta');$mid=$wpdb->get_var($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id=%d AND meta_key=%s ORDER BY meta_id ASC LIMIT 1",$id,$key));
 if(!$mid){$default='g73_reactions'===$key?array('util'=>0,'surpreendente'=>0,'indignado'=>0):array_fill(0,count((array)get_post_meta($id,'g73_poll_options',true)),0);add_post_meta($id,$key,$default,true);continue;}
 $old=$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_id=%d",$mid));$next=maybe_unserialize($old);if(!is_array($next)){$next=array();}$next[$choice]=isset($next[$choice])?absint($next[$choice])+1:1;
 $changed=$wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value=%s WHERE meta_id=%d AND meta_value=%s",maybe_serialize($next),$mid,$old));
 if(1===$changed){wp_cache_delete($id,'post_meta');return true;}}
 return false;
}
function g73_eng_vote($request){$id=absint($request['id']);if(!g73_eng_post($id)){return new WP_Error('g73_not_found','Matéria indisponível.',array('status'=>404));}
 $origin=$request->get_header('origin');if($origin&&untrailingslashit($origin)!==untrailingslashit(home_url())){return new WP_Error('g73_origin','Origem não permitida.',array('status'=>403));}
 $reader=g73_eng_reader();if(!$reader){return new WP_Error('g73_reader_required','Recarregue a participação e permita o armazenamento funcional.',array('status'=>403));}
 $kind=$request->get_param('kind');$choice=$request->get_param('choice');$version='';if('reaction'===$kind){if(!in_array($choice,array('util','surpreendente','indignado'),true)){return new WP_Error('g73_choice','Reação inválida.',array('status'=>400));}$key='g73_reactions';}
 elseif('poll'===$kind){$poll=g73_eng_poll($id);if(!$poll||$request->get_param('version')!==$poll['version']){return new WP_Error('g73_poll_changed','A enquete mudou. Atualize a página.',array('status'=>409));}
 if(!is_int($choice)||$choice<0||$choice>=count($poll['options'])){return new WP_Error('g73_choice','Opção inválida.',array('status'=>400));}$key='g73_poll_votes';$version=$poll['version'];}
 else{return new WP_Error('g73_kind','Participação inválida.',array('status'=>400));}
 $ip=isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'';$rate='g73_rate_'.substr(hash_hmac('sha256',$ip.'|'.$reader,wp_salt('nonce')),0,32);$attempts=(int)get_transient($rate);if($attempts>=40){return new WP_Error('g73_rate_limited','Aguarde um minuto.',array('status'=>429));}set_transient($rate,$attempts+1,MINUTE_IN_SECONDS);
 $lock='g73_vote_'.substr(hash_hmac('sha256',$reader.'|'.$id.'|'.$kind.'|'.$version,wp_salt('nonce')),0,40);
 if(wp_using_ext_object_cache()){$locked=wp_cache_add($lock,1,'g73_votes',30*DAY_IN_SECONDS);}else{get_transient($lock);$locked=add_option('_transient_'.$lock,time(),'',false);if($locked){add_option('_transient_timeout_'.$lock,time()+30*DAY_IN_SECONDS,'',false);}}
 if(!$locked){return new WP_Error('g73_already_voted','Você já participou.',array('status'=>409));}
 if(!g73_eng_increment($id,$key,$choice)){if(wp_using_ext_object_cache()){wp_cache_delete($lock,'g73_votes');}else{delete_transient($lock);}return new WP_Error('g73_retry','Não foi possível registrar. Tente novamente.',array('status'=>503));}
 return g73_eng_response(g73_eng_data($id));
}
add_action('rest_api_init',function(){register_rest_route('g73/v1','/engagement/(?P<id>\d+)',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){$id=absint($r['id']);if(!g73_eng_post($id)){return new WP_Error('g73_not_found','Matéria indisponível.',array('status'=>404));}g73_eng_reader(true);return g73_eng_response(g73_eng_data($id));}));
 register_rest_route('g73/v1','/engagement/(?P<id>\d+)/vote',array('methods'=>'POST','permission_callback'=>'__return_true','callback'=>'g73_eng_vote','args'=>array('kind'=>array('type'=>'string','required'=>true,'enum'=>array('reaction','poll')),'version'=>array('type'=>'string','default'=>''))));
});
// Uma alteração editorial redefine a população de opções; nunca reaproveitar resultados antigos.
foreach(array('added_post_meta','updated_post_meta','deleted_post_meta') as $hook){add_action($hook,function($mid,$id,$key){if('post'===get_post_type($id)&&in_array($key,array('g73_poll_question','g73_poll_options'),true)){
 update_post_meta($id,'g73_poll_votes',array_fill(0,count((array)get_post_meta($id,'g73_poll_options',true)),0));do_action('litespeed_purge_post',$id);
 }},20,3);}
function g73_eng_wa(){ $url=get_option('g73_whatsapp_group_url','');
 if(!is_string($url)||!preg_match('~^https://chat\.whatsapp\.com/[A-Za-z0-9]+/?(?:\?[^\s]*)?$~D',$url)){return '';}
 $icon='<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" fill="currentColor"><path d="M20.52 3.48A11.91 11.91 0 0012.05 0C5.46 0 .1 5.35.1 11.94c0 2.1.55 4.15 1.6 5.96L0 24l6.27-1.64a11.98 11.98 0 005.77 1.47h.01C18.63 23.83 24 18.48 24 11.9c0-3.18-1.24-6.17-3.48-8.42zM12.05 21.8h-.01a9.9 9.9 0 01-5.05-1.38l-.36-.21-3.72.97.99-3.63-.24-.37a9.85 9.85 0 01-1.51-5.25c0-5.46 4.45-9.9 9.92-9.9a9.85 9.85 0 017.02 2.9 9.82 9.82 0 012.9 7.01c0 5.47-4.45 9.91-9.94 9.91zm5.44-7.42c-.3-.15-1.77-.87-2.04-.97-.28-.1-.48-.15-.68.15-.2.3-.77.97-.94 1.17-.18.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.21 5.09 4.5.71.3 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.08 1.77-.73 2.02-1.44.25-.7.25-1.3.17-1.44-.07-.12-.27-.2-.57-.35z"/></svg>';
 return '<section class="g73-whatsapp-card">'.$icon.'<h2>📱 Receba as notícias do Extremo Sul no seu WhatsApp</h2><p>Entre no grupo oficial do Giro73 e receba os destaques do dia em primeira mão.</p>'.($url?'<a class="g73-whatsapp-join" href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">Entrar no Grupo do Giro73 →</a>':'<button class="g73-whatsapp-join" type="button" disabled>Entrar no Grupo do Giro73 →</button><small>Convite oficial disponível em breve.</small>').'</section>';
}
function g73_eng_reactions(){ $id=get_queried_object_id();if(!g73_eng_post($id)){return '';}$GLOBALS['g73_eng_needed']=true;
 $html='<section class="g73-reactions" data-g73-engagement="'.(int)$id.'" data-g73-kind="reaction"><h2>O que você achou desta notícia?</h2><div class="g73-reaction-buttons">';
 foreach(array('util'=>'👍 Útil','surpreendente'=>'😮 Surpreendente','indignado'=>'😡 Indignado') as $key=>$text){$html.='<button type="button" data-g73-choice="'.$key.'" aria-pressed="false">'.$text.' <span>0</span></button>';}
 return $html.'</div><p class="g73-eng-status" data-g73-status role="status" aria-live="polite">Uma reação por matéria neste navegador.</p></section>';
}
function g73_eng_poll_block($placement='article'){
 $id=get_queried_object_id();if('sidebar'===$placement){$ids=get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>true,'meta_query'=>array(array('key'=>'g73_poll_enabled','value'=>'1'),array('key'=>'g73_poll_placement','value'=>array('sidebar','both'),'compare'=>'IN'))));$id=$ids?$ids[0]:0;}
 if(!$id||!g73_eng_post($id)){return '';}$where=get_post_meta($id,'g73_poll_placement',true)?:'article';if(!in_array($where,array($placement,'both'),true)){return '';}$poll=g73_eng_poll($id);if(!$poll){return '';}$GLOBALS['g73_eng_needed']=true;
 $html='<section class="g73-poll" data-g73-engagement="'.(int)$id.'" data-g73-kind="poll" data-g73-version="'.esc_attr($poll['version']).'"><h2 data-g73-question>'.esc_html($poll['question']).'</h2><div data-g73-options>';
 foreach($poll['options'] as $i=>$label){$html.='<button type="button" data-g73-choice="'.$i.'">'.esc_html($label).'</button>';}
 return $html.'</div><p class="g73-eng-status" data-g73-status role="status" aria-live="polite">Escolha uma opção para participar.</p><small>Enquete de leitores; não é pesquisa científica.</small></section>';
}
function g73_eng_columnists_block(){ $html='<section class="g73-columnists"><h2>Colunistas Giro73</h2><div class="g73-columnists-row">';
 foreach(g73_eng_columnists() as $row){$user=$row['author_id']?get_userdata($row['author_id']):false;$name=$row['name']?:($user?$user->display_name:'Em breve');$area=$row['area']?:'Análises e opinião regional';
 $last=$user?get_posts(array('post_type'=>'post','post_status'=>'publish','author'=>$user->ID,'posts_per_page'=>1,'has_password'=>false,'meta_key'=>'g73_is_opinion','meta_value'=>'1','no_found_rows'=>true)):array();
 $avatar=$row['avatar_id']?wp_get_attachment_image($row['avatar_id'],'thumbnail',false,array('class'=>'g73-columnist-avatar','width'=>56,'height'=>56,'loading'=>'lazy','decoding'=>'async','alt'=>$name)):'<span class="g73-columnist-placeholder" aria-hidden="true">G73</span>';
 $html.='<article class="g73-columnist">'.$avatar.'<div><h3>'.($user?'<a href="'.esc_url(get_author_posts_url($user->ID)).'">'.esc_html($name).'</a>':esc_html($name)).'</h3><p>'.esc_html($area).'</p>'.($last?'<a class="g73-columnist-latest" href="'.esc_url(get_permalink($last[0])).'">'.esc_html(get_the_title($last[0])).'</a>':'<span class="g73-columnist-latest">'.($user?'Primeira coluna em preparação':'Espaço para novos colunistas').'</span>').'</div></article>';
 }return $html.'</div></section>';
}
function g73_eng_agenda(){ $today=wp_date('Y-m-d');$posts=get_posts(array('post_type'=>'g73_event','post_status'=>'publish','posts_per_page'=>3,'no_found_rows'=>true,'meta_key'=>'g73_event_date','orderby'=>'meta_value','order'=>'ASC','meta_query'=>array(array('key'=>'g73_event_date','value'=>$today,'compare'=>'>=','type'=>'DATE'),array('relation'=>'OR',array('key'=>'g73_event_demo','compare'=>'NOT EXISTS'),array('key'=>'g73_event_demo','value'=>'1','compare'=>'!=')))));
 $html='<section class="g73-agenda"><h2>Agenda &amp; Eventos DDD 73</h2>';if(!$posts){return $html.'<p>Nenhum evento confirmado no momento.</p><a href="mailto:comercial@giro73.com.br?subject=Sugest%C3%A3o%20para%20a%20Agenda%20G73">Envie uma sugestão à redação →</a></section>';}
 $html.='<ul>';foreach($posts as $p){$date=get_post_meta($p->ID,'g73_event_date',true);
 $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,wp_timezone());if(!$d){continue;}$url=get_post_meta($p->ID,'g73_event_url',true);$title=esc_html(get_the_title($p));
 $html.='<li><time datetime="'.esc_attr($date).'" class="g73-calendar-badge"><strong>'.$d->format('d').'</strong><span>'.esc_html(wp_date('M',$d->getTimestamp(),wp_timezone())).'</span></time><div><span class="g73-event-city">'.esc_html(get_post_meta($p->ID,'g73_event_city',true)).'</span><h3>'.($url?'<a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">'.$title.'</a>':$title).'</h3></div></li>';
 }return $html.'</ul></section>';
}
function g73_eng_print_header(){return '<div class="g73-print-header"><img src="'.esc_url(home_url('/wp-content/uploads/2026/10/G73-PRETO-SELO.png')).'" width="125" height="58" alt="G73 — Giro73" loading="eager" decoding="async"><span>Jornalismo do Extremo Sul da Bahia</span></div>';}
function g73_eng_print_url(){
 $url=get_permalink(get_queried_object_id());$css=wp_json_encode('Fonte: '.$url,JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP);
 return '<style class="g73-print-page-style">@media print{@page{@bottom-left{content:'.$css.';font:8pt Georgia,serif;vertical-align:middle;text-align:left;white-space:normal;}}}</style><div class="g73-print-url">Fonte: '.esc_html($url).'</div>';
}
foreach(array('giro73_grupo_whatsapp'=>'g73_eng_wa','giro73_reacoes'=>'g73_eng_reactions','giro73_colunistas'=>'g73_eng_columnists_block','giro73_agenda'=>'g73_eng_agenda','giro73_impressao_cabecalho'=>'g73_eng_print_header','giro73_impressao_url'=>'g73_eng_print_url') as $tag=>$callback){add_shortcode($tag,$callback);}
add_shortcode('giro73_enquete',function($atts){$atts=shortcode_atts(array('posicao'=>'article'),$atts);return g73_eng_poll_block('sidebar'===$atts['posicao']?'sidebar':'article');});
add_filter('render_block_core/shortcode',function($html,$block){$raw=trim(isset($block['innerHTML'])?$block['innerHTML']:'');return preg_match('~^\[giro73_(?:grupo_whatsapp|reacoes|colunistas|agenda|impressao_cabecalho|impressao_url|enquete)(?:\s[^\]]*)?\]$~s',$raw)?do_shortcode($raw):$html;},10,2);
add_action('wp_footer',function(){if(empty($GLOBALS['g73_eng_needed'])){return;}echo '<script id="g73-engagement-boot" type="application/json">'.wp_json_encode(array('endpoint'=>trailingslashit(rest_url('g73/v1/engagement'))),JSON_HEX_TAG|JSON_HEX_AMP).'</script><script id="g73-engagement-runtime">'.g73_eng_js().'</script>';},35);
function g73_eng_js(){return <<<'G73ENG'
(()=>{'use strict';if('CSSMarginRule'in window)document.documentElement.classList.add('g73-print-margins');const boot=document.getElementById('g73-engagement-boot');if(!boot)return;const base=JSON.parse(boot.textContent).endpoint,groups=new Map(),pending=new Map();
const read=k=>{try{return localStorage.getItem(k);}catch(_){return null;}},save=(k,v)=>{try{localStorage.setItem(k,String(v));}catch(_){}};
const status=(el,msg)=>{el.querySelector('[data-g73-status]').textContent=msg;};
function draw(el,data){if(el.dataset.g73Kind==='reaction'){const key='g73-reaction-'+data.id,voted=read(key);el.querySelectorAll('[data-g73-choice]').forEach(b=>{const type=b.dataset.g73Choice;b.querySelector('span').textContent=String(data.reactions[type]||0);b.disabled=Boolean(voted);b.setAttribute('aria-pressed',String(voted===type));});if(voted)status(el,'Sua reação já foi registrada neste navegador.');return;}
const poll=data.poll;if(!poll){el.hidden=true;return;}el.hidden=false;el.dataset.g73Version=poll.version;el.querySelector('[data-g73-question]').textContent=poll.question;const key='g73-poll-'+data.id+'-'+poll.version,voted=read(key),area=el.querySelector('[data-g73-options]');area.replaceChildren();poll.options.forEach((label,index)=>{if(voted!==null){const row=document.createElement('div');row.className='g73-poll-result';const text=document.createElement('span'),value=document.createElement('strong'),bar=document.createElement('span');const percent=poll.total?Math.round(poll.votes[index]/poll.total*100):0;text.textContent=label;value.textContent=percent+'%';bar.className='g73-poll-track';const fill=document.createElement('span');fill.style.width=percent+'%';bar.append(fill);row.append(text,value,bar);area.append(row);}else{const button=document.createElement('button');button.type='button';button.dataset.g73Choice=String(index);button.textContent=label;area.append(button);}});status(el,voted!==null?'Obrigado! '+poll.total+' voto(s) nesta enquete.':'Escolha uma opção para participar.');}
const update=data=>(groups.get(String(data.id))||[]).forEach(el=>draw(el,data));
function load(id){if(!pending.has(id))pending.set(id,fetch(base+id,{credentials:'same-origin',cache:'no-store'}).then(r=>{if(!r.ok)throw Error('unavailable');return r.json();}).then(data=>{update(data);return data;}).catch(error=>{(groups.get(id)||[]).forEach(el=>status(el,'Participação indisponível no momento. Tente novamente.'));pending.delete(id);return null;}));return pending.get(id);}
const blocks=[...document.querySelectorAll('[data-g73-engagement]')];blocks.forEach(el=>{const id=el.dataset.g73Engagement;if(!groups.has(id))groups.set(id,[]);groups.get(id).push(el);el.addEventListener('click',async event=>{const button=event.target.closest('button[data-g73-choice]');if(!button||button.disabled||el.dataset.busy)return;el.dataset.busy='1';el.querySelectorAll('button').forEach(b=>b.disabled=true);status(el,'Registrando…');try{const current=await load(id);if(!current)throw Error('unavailable');const kind=el.dataset.g73Kind,version=kind==='poll'?el.dataset.g73Version:'';const response=await fetch(base+id+'/vote',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({kind,choice:kind==='poll'?Number(button.dataset.g73Choice):button.dataset.g73Choice,version})});const data=await response.json();if(!response.ok){if(data.code==='g73_already_voted'){save(kind==='poll'?'g73-poll-'+id+'-'+version:'g73-reaction-'+id,kind==='poll'?'voted':'registered');pending.delete(id);await load(id);status(el,'Você já participou desta votação.');return;}throw Error(response.status===429?'Muitas tentativas. Aguarde um minuto.':'Não foi possível registrar. Tente novamente.');}save(kind==='poll'?'g73-poll-'+id+'-'+version:'g73-reaction-'+id,button.dataset.g73Choice);pending.set(id,Promise.resolve(data));update(data);}catch(error){el.querySelectorAll('button').forEach(b=>b.disabled=false);status(el,error.message==='unavailable'?'Participação indisponível no momento.':error.message);}finally{delete el.dataset.busy;}});});
if('IntersectionObserver'in window){const observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){load(e.target.dataset.g73Engagement);observer.unobserve(e.target);}}),{rootMargin:'180px'});blocks.forEach(el=>observer.observe(el));}else groups.forEach((_,id)=>load(id));
})();

G73ENG;
}
function g73_eng_purge_all(){static $queued=false;if(!$queued){$queued=true;add_action('shutdown',function(){do_action('litespeed_purge_all');},20);}}
// Gestão nativa, sem assets administrativos no frontend.
add_action('admin_menu',function(){add_options_page('Engajamento Giro73','Engajamento G73','manage_options','g73-engagement','g73_eng_settings_page');});
function g73_eng_settings_page(){if(!current_user_can('manage_options')){return;}echo '<div class="wrap"><h1>Engajamento G73</h1>';settings_errors();echo '<form method="post" action="options.php">';settings_fields('g73_engagement');
 echo '<h2>Grupo oficial do WhatsApp</h2><p>Sem convite válido, o card fica oculto no portal. Ao cadastrar o link oficial, ele aparece automaticamente.</p><input class="large-text" type="url" name="g73_whatsapp_group_url" value="'.esc_attr(get_option('g73_whatsapp_group_url','')).'" placeholder="https://chat.whatsapp.com/...">';submit_button('Salvar engajamento');echo '</form><h2>Colunistas e Agenda 73</h2><p><a href="'.esc_url(admin_url('edit.php?post_type=g73_columnist')).'">Gerenciar colunas em Colunistas G73</a> • <a href="'.esc_url(admin_url('edit.php?post_type=g73_event')).'">Agenda G73</a> • <a href="'.esc_url(admin_url('options-general.php?page=g73-regional')).'">Configurar vitrine de modelos</a></p><p>Enquetes: editar notícia → Enquete e opinião. Eventos-modelo continuam em rascunho e disponíveis na prévia autenticada, sem exibição pública.</p></div>';
}
add_action('admin_enqueue_scripts',function($hook){if('settings_page_g73-engagement'!==$hook){return;}wp_enqueue_media();wp_add_inline_script('media-editor',<<<'G73ADMIN'
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-g73-eng-media]').forEach(b=>b.addEventListener('click',()=>{const f=document.getElementById(b.dataset.g73EngMedia),m=wp.media({title:'Avatar WebP autorizado',button:{text:'Usar avatar'},library:{type:'image/webp'},multiple:false});m.on('select',()=>{const image=m.state().get('selection').first().toJSON();if(image.mime!=='image/webp')return;f.value=image.id;document.getElementById(f.id+'-preview').textContent=image.filename;});m.open();}));document.querySelectorAll('[data-g73-eng-clear]').forEach(b=>b.addEventListener('click',()=>{document.getElementById(b.dataset.g73EngClear).value=0;document.getElementById(b.dataset.g73EngClear+'-preview').textContent='Sem foto — placeholder editorial';}));});
G73ADMIN
);});
add_action('add_meta_boxes_post',function(){add_meta_box('g73-poll-editor','Enquete e opinião — Giro73','g73_eng_poll_metabox','post','normal','default');});
function g73_eng_poll_metabox($post){wp_nonce_field('g73_poll_save','g73_poll_nonce');$id=$post->ID;$options=(array)get_post_meta($id,'g73_poll_options',true);$poll=g73_eng_poll($id);
 echo '<p><label><input type="checkbox" name="g73_poll_enabled" value="1"'.checked((bool)get_post_meta($id,'g73_poll_enabled',true),true,false).'> Ativar micro-enquete</label></p><p><label>Pergunta<br><input class="large-text" name="g73_poll_question" maxlength="180" value="'.esc_attr(get_post_meta($id,'g73_poll_question',true)).'"></label></p><p><label>Opções (uma por linha; de duas a cinco)<br><textarea name="g73_poll_options" rows="5" class="large-text">'.esc_textarea(implode("\n",$options)).'</textarea></label></p><p><label>Exibir em <select name="g73_poll_placement">';
 foreach(array('article'=>'Fim da matéria','sidebar'=>'Sidebar oficial','both'=>'Matéria e sidebar') as $key=>$label){echo '<option value="'.$key.'"'.selected(get_post_meta($id,'g73_poll_placement',true)?:'article',$key,false).'>'.$label.'</option>';}
 echo '</select></label></p><p>Alterar pergunta ou opções zera os votos. As contagens não são uma pesquisa científica.</p><p>Votos atuais: '.(int)($poll?$poll['total']:0).'</p><p><label><input type="checkbox" name="g73_is_opinion" value="1"'.checked((bool)get_post_meta($id,'g73_is_opinion',true),true,false).'> Este artigo é uma coluna de opinião do autor selecionado no WordPress.</label></p>';
}
add_action('save_post_post',function($id){if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||wp_is_post_revision($id)||!current_user_can('edit_post',$id)||!isset($_POST['g73_poll_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['g73_poll_nonce'])),'g73_poll_save')){return;}
 $question=sanitize_text_field(isset($_POST['g73_poll_question'])?wp_unslash($_POST['g73_poll_question']):'');$options=g73_eng_sanitize_meta(preg_split('/\r\n|\r|\n/',isset($_POST['g73_poll_options'])?wp_unslash($_POST['g73_poll_options']):''),'g73_poll_options');
 update_post_meta($id,'g73_poll_question',$question);update_post_meta($id,'g73_poll_options',$options);update_post_meta($id,'g73_poll_enabled',!empty($_POST['g73_poll_enabled'])&&$question&&count($options)>=2);
 update_post_meta($id,'g73_poll_placement',g73_eng_sanitize_meta(isset($_POST['g73_poll_placement'])?wp_unslash($_POST['g73_poll_placement']):'article','g73_poll_placement'));update_post_meta($id,'g73_is_opinion',!empty($_POST['g73_is_opinion']));
});
function g73_eng_validate_poll($post,$request){$meta=(array)$request->get_param('meta');$id=(int)$request->get_param('id');$get=function($key)use($meta,$id){return isset($meta[$key])?$meta[$key]:($id?get_post_meta($id,$key,true):null);};
 if($get('g73_poll_enabled')&&(!$get('g73_poll_question')||count((array)$get('g73_poll_options'))<2)){return new WP_Error('g73_invalid_poll','Enquete ativa exige pergunta e duas a cinco opções.',array('status'=>400));}return $post;
}
add_filter('rest_pre_insert_post','g73_eng_validate_poll',15,2);
function g73_eng_event_valid($meta,$id=0,$status='draft'){$get=function($key)use($meta,$id){return isset($meta[$key])?$meta[$key]:($id?get_post_meta($id,$key,true):'');};$date=$get('g73_event_date');
 if($date){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,wp_timezone());$err=DateTimeImmutable::getLastErrors();if(!$d||$err&&($err['warning_count']||$err['error_count'])){return new WP_Error('g73_event_date','Data inválida.',array('status'=>400));}}
 $url=$get('g73_event_url');if($url&&!preg_match('~^https?://~i',$url)){return new WP_Error('g73_event_url','Use URL HTTP/HTTPS.',array('status'=>400));}
 if('publish'===$status&&(!$date||!$get('g73_event_city')||$get('g73_event_demo'))){return new WP_Error('g73_event_unconfirmed','Preencha data/cidade e remova a marca de demonstração somente após confirmar o evento.',array('status'=>400));}return true;
}
add_filter('rest_pre_insert_g73_event',function($p,$r){$id=(int)$r->get_param('id');$status=isset($p->post_status)?$p->post_status:($id?get_post_status($id):'draft');$valid=g73_eng_event_valid((array)$r->get_param('meta'),$id,$status);return is_wp_error($valid)?$valid:$p;},10,2);
add_filter('rest_pre_dispatch',function($result,$server,$request){if(preg_match('~^/wp/v2/g73_event(?:/|$)~',$request->get_route())&&!current_user_can('manage_options')){return new WP_Error('g73_event_forbidden','A gestão da agenda exige administrador.',array('status'=>is_user_logged_in()?403:401));}return $result;},10,3);
add_action('add_meta_boxes_g73_event',function(){remove_meta_box('postcustom','g73_event','normal');add_meta_box('g73-event-details','Data, cidade e confirmação','g73_eng_event_metabox','g73_event','normal','high');});
function g73_eng_event_metabox($post){wp_nonce_field('g73_event_save','g73_event_nonce');foreach(array('g73_event_date'=>array('Data','date'),'g73_event_city'=>array('Cidade','text'),'g73_event_url'=>array('Link oficial do evento','url')) as $key=>$field){echo '<p><label>'.esc_html($field[0]).'<br><input class="regular-text" type="'.$field[1].'" name="'.esc_attr($key).'" value="'.esc_attr(get_post_meta($post->ID,$key,true)).'"></label></p>';}
 echo '<p><label><input type="checkbox" name="g73_event_demo" value="1"'.checked((bool)get_post_meta($post->ID,'g73_event_demo',true),true,false).'> Modelo de demonstração — não veicular</label></p><p>Publique somente eventos confirmados com data e cidade. Horário local: '.esc_html(wp_timezone_string()).'.</p>';
}
add_action('save_post_g73_event',function($id,$post){if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||wp_is_post_revision($id)||!current_user_can('manage_options')||!isset($_POST['g73_event_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['g73_event_nonce'])),'g73_event_save')){return;}
 $meta=array();foreach(array('g73_event_date','g73_event_city','g73_event_url') as $key){$meta[$key]=sanitize_text_field(isset($_POST[$key])?wp_unslash($_POST[$key]):'');}$meta['g73_event_url']=esc_url_raw($meta['g73_event_url'],array('http','https'));$meta['g73_event_demo']=!empty($_POST['g73_event_demo']);
 $valid=g73_eng_event_valid($meta,$id,$post->post_status);if(is_wp_error($valid)){
 set_transient('g73_event_error_'.get_current_user_id(),$valid->get_error_message(),60);if('publish'===$post->post_status){wp_update_post(array('ID'=>$id,'post_status'=>'draft'));}return;}
 foreach($meta as $key=>$v){update_post_meta($id,$key,$v);}g73_eng_purge_all();
},10,2);
add_action('admin_notices',function(){$k='g73_event_error_'.get_current_user_id();$message=get_transient($k);if($message){delete_transient($k);echo '<div class="notice notice-error"><p>'.esc_html($message).' O evento não foi publicado.</p></div>';}});
add_action('save_post_g73_event',function(){g73_eng_purge_all();},30);
add_action('rest_after_insert_g73_event',function(){g73_eng_purge_all();},30);
add_action('before_delete_post',function($id){if('g73_event'===get_post_type($id)){g73_eng_purge_all();}});
foreach(array('g73_whatsapp_group_url','g73_columnists') as $option){add_action('update_option_'.$option,function(){g73_eng_purge_all();});add_action('add_option_'.$option,function(){g73_eng_purge_all();});}
// Exemplos são rascunhos identificados, nunca anúncios de eventos reais não confirmados.
add_action('init',function(){if(get_option('g73_events_examples_v1')){return;}$ok=true;foreach(array(array('teixeira','Feira de produtores e economia criativa','Teixeira de Freitas',7),array('porto','Encontro cultural regional','Porto Seguro',14),array('prado','Programação comunitária e lazer','Prado',21)) as $example){
 $exists=get_posts(array('post_type'=>'g73_event','post_status'=>array('draft','publish','private','pending','trash'),'posts_per_page'=>1,'fields'=>'ids','meta_key'=>'g73_event_example_key','meta_value'=>$example[0]));if($exists){continue;}
 $id=wp_insert_post(array('post_type'=>'g73_event','post_status'=>'draft','post_title'=>'MODELO — '.$example[1],'meta_input'=>array('g73_event_date'=>wp_date('Y-m-d',time()+$example[3]*DAY_IN_SECONDS),'g73_event_city'=>$example[2],'g73_event_url'=>'','g73_event_demo'=>true,'g73_event_example_key'=>$example[0])),true);if(!$id||is_wp_error($id)){$ok=false;}}
 if($ok){update_option('g73_events_examples_v1',1,false);}
},25);

}
