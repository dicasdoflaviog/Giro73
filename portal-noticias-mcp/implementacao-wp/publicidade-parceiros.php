<?php
/** Parceiros G73: CPT, administração, REST, agenda e posições nativas. Snippet global 6. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_partner_formats')) {
function g73_partner_formats() { return array(
 'retangulo_medio'=>array('Retângulo médio — 300×250',300,250),'retangulo_grande'=>array('Retângulo grande — 336×280',336,280),
 'meia_pagina'=>array('Meia página — 300×600',300,600),'billboard'=>array('Billboard — 970×250',970,250),
 'leaderboard_grande'=>array('Leaderboard grande — 970×90',970,90),'faixa_mobile'=>array('Faixa mobile — 320×50',320,50),
 'popup_modal'=>array('Modal — 600×600 / 500×650',600,600)); }
function g73_partner_positions() { return array('topo_portal'=>'Topo do portal','sidebar_lateral'=>'Sidebar lateral','in_article_1'=>'Matéria — após o 3º parágrafo','in_article_2'=>'Matéria — após o 6º parágrafo / feed','popup_global'=>'Destaque sobreposto'); }
function g73_partner_defaults() { return array('contact_email'=>'comercial@giro73.com.br',
 'modes'=>array('topo_portal'=>'rotacao','sidebar_lateral'=>'rotacao','in_article_1'=>'rotacao','in_article_2'=>'rotacao','popup_global'=>'rotacao'),
 'overlay'=>array('enabled'=>false,'trigger'=>'tempo','delay'=>3,'scroll'=>30,'frequency'=>'1x_por_dia','scope'=>'todo_portal','auto_close'=>15)); }
function g73_partner_config($input=null) {
 $out=g73_partner_defaults(); if (null===$input) { $input=get_option('g73_partners_config',array()); } if (!is_array($input)) { return $out; }
 $out['contact_email']=sanitize_email(isset($input['contact_email'])?$input['contact_email']:'')?:$out['contact_email'];
 if (isset($input['modes']['sidebar_lateral']) && 'empilhado'===$input['modes']['sidebar_lateral']) { $out['modes']['sidebar_lateral']='empilhado'; }
 $p=isset($input['overlay'])&&is_array($input['overlay'])?$input['overlay']:array();
 $out['overlay']['enabled']=!empty($p['enabled']);
 foreach(array('trigger'=>array('tempo','rolagem'),'frequency'=>array('1x_por_dia','1x_por_sessao','sempre'),'scope'=>array('todo_portal','home','materias')) as $key=>$values) { if(isset($p[$key])&&in_array($p[$key],$values,true)){$out['overlay'][$key]=$p[$key];} }
 foreach(array('delay'=>array(3,120),'scroll'=>array(1,95),'auto_close'=>array(0,120)) as $key=>$range) { if(isset($p[$key])){$out['overlay'][$key]=max($range[0],min($range[1],absint($p[$key])));} }
 return $out;
}
function g73_partner_image($id) { $id=absint($id); return in_array(get_post_mime_type($id),array('image/webp','image/png','image/jpeg'),true)?$id:0; }
function g73_partner_meta_schema() { return array(
 'g73_desktop_image'=>array('type'=>'integer','default'=>0,'minimum'=>0), 'g73_mobile_image'=>array('type'=>'integer','default'=>0,'minimum'=>0),
 'g73_url'=>array('type'=>'string','default'=>''), 'g73_status'=>array('type'=>'string','default'=>'pausado','enum'=>array('ativo','pausado')),
 'g73_start_at'=>array('type'=>'string','default'=>'','pattern'=>'^(|[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(Z|[+-][0-9]{2}:[0-9]{2}))$'),
 'g73_end_at'=>array('type'=>'string','default'=>'','pattern'=>'^(|[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(Z|[+-][0-9]{2}:[0-9]{2}))$'),
 'g73_format'=>array('type'=>'string','default'=>'retangulo_medio','enum'=>array_keys(g73_partner_formats())),
 'g73_position'=>array('type'=>'string','default'=>'sidebar_lateral','enum'=>array_keys(g73_partner_positions()))); }
function g73_partner_clean_meta($value,$key) {
 if(in_array($key,array('g73_desktop_image','g73_mobile_image'),true)){return g73_partner_image($value);}
 if('g73_url'===$key){return esc_url_raw($value,array('http','https'));}
 return sanitize_text_field($value);
}
add_action('init',function(){
 register_post_type('g73_partner',array('labels'=>array('name'=>'Parceiros G73','singular_name'=>'Campanha','add_new_item'=>'Nova campanha','edit_item'=>'Editar campanha','menu_name'=>'Parceiros G73'),
 'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>true,'rest_base'=>'g73_partner',
 'menu_icon'=>'dashicons-store','supports'=>array('title','custom-fields'),'has_archive'=>false,'rewrite'=>false,'map_meta_cap'=>false,
 'capabilities'=>array_fill_keys(array('edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts'),'manage_options')));
 foreach(g73_partner_meta_schema() as $key=>$schema){register_post_meta('g73_partner',$key,array('type'=>$schema['type'],'single'=>true,'default'=>$schema['default'],
 'show_in_rest'=>array('schema'=>$schema),'auth_callback'=>function(){return current_user_can('manage_options');},
 'sanitize_callback'=>function($value)use($key){return g73_partner_clean_meta($value,$key);}));}
 register_setting('g73_partners','g73_partners_config',array('type'=>'object','default'=>g73_partner_defaults(),'sanitize_callback'=>'g73_partner_config','show_in_rest'=>array('schema'=>array(
 'type'=>'object','additionalProperties'=>false,'properties'=>array('contact_email'=>array('type'=>'string'),'modes'=>array('type'=>'object','additionalProperties'=>false,'properties'=>array_fill_keys(array_keys(g73_partner_positions()),array('type'=>'string','enum'=>array('rotacao','empilhado')))),
 'overlay'=>array('type'=>'object','additionalProperties'=>false,'properties'=>array('enabled'=>array('type'=>'boolean'),'trigger'=>array('type'=>'string','enum'=>array('tempo','rolagem')),'delay'=>array('type'=>'integer','minimum'=>3,'maximum'=>120),'scroll'=>array('type'=>'integer','minimum'=>1,'maximum'=>95),'frequency'=>array('type'=>'string','enum'=>array('1x_por_dia','1x_por_sessao','sempre')),'scope'=>array('type'=>'string','enum'=>array('todo_portal','home','materias')),'auto_close'=>array('type'=>'integer','minimum'=>0,'maximum'=>120))))))));
});
// O CPT permanece fora dos mapas públicos e dos grafos de notícias.
add_filter('rank_math/sitemap/exclude_post_type',function($excluded,$type){return 'g73_partner'===$type?true:$excluded;},10,2);
add_filter('use_block_editor_for_post_type',function($use,$type){return 'g73_partner'===$type?false:$use;},10,2);
function g73_partner_validate($meta,$id=0) {
 foreach(array('g73_desktop_image','g73_mobile_image') as $key){if(!empty($meta[$key])&&!g73_partner_image($meta[$key])){return new WP_Error('g73_invalid_image','Escolha WebP, PNG ou JPG da biblioteca.',array('status'=>400));}}
 foreach(array('g73_start_at','g73_end_at') as $key){if(!empty($meta[$key])){$d=date_create_immutable($meta[$key]);$errors=DateTimeImmutable::getLastErrors();if(!$d||($errors&&($errors['warning_count']||$errors['error_count']))){return new WP_Error('g73_invalid_date','Data inválida. Use ISO 8601 com fuso horário.',array('status'=>400));}}}
 $get=function($key)use($meta,$id){return isset($meta[$key])?$meta[$key]:($id?get_post_meta($id,$key,true):'');};
 $start=$get('g73_start_at');$end=$get('g73_end_at'); if($start&&$end&&strtotime($end)<=strtotime($start)){return new WP_Error('g73_invalid_range','A expiração precisa ser posterior ao início.',array('status'=>400));}
 $url=$get('g73_url');if($url&&!preg_match('~^https?://~i',$url)){return new WP_Error('g73_invalid_url','Use URL HTTP ou HTTPS.',array('status'=>400));}
 if('ativo'===$get('g73_status')&&!g73_partner_image($get('g73_desktop_image'))&&!g73_partner_image($get('g73_mobile_image'))){return new WP_Error('g73_missing_image','Escolha uma imagem antes de ativar.',array('status'=>400));}
 return true;
}
// Cadastro completo e metadados são administrativos; o visitante recebe só peças elegíveis.
add_filter('rest_pre_dispatch',function($result,$server,$request){
 if(preg_match('~^/wp/v2/g73_partner(?:/|$)~',$request->get_route())&&!current_user_can('manage_options')){return new WP_Error('g73_partner_forbidden','Gerencie campanhas com uma conta administradora.',array('status'=>is_user_logged_in()?403:401));}return $result;
},10,3);
add_filter('rest_pre_insert_g73_partner',function($prepared,$request){$valid=g73_partner_validate((array)$request->get_param('meta'),(int)$request->get_param('id'));return is_wp_error($valid)?$valid:$prepared;},10,2);
function g73_partner_media_data($id){$src=wp_get_attachment_image_src($id,'full');return $src?array('url'=>$src[0],'width'=>$src[1],'height'=>$src[2]):null;}
function g73_partner_payload(){
 $ids=get_posts(array('post_type'=>'g73_partner','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','no_found_rows'=>true,
 'meta_key'=>'g73_status','meta_value'=>'ativo','update_post_term_cache'=>false));$rows=array();$counts=array();
 foreach($ids as $id){$pos=get_post_meta($id,'g73_position',true);$format=get_post_meta($id,'g73_format',true);$formats=g73_partner_formats();if(!isset($formats[$format])||!isset(g73_partner_positions()[$pos])){continue;}
 $start=get_post_meta($id,'g73_start_at',true);$end=get_post_meta($id,'g73_end_at',true);$start=$start?strtotime($start)*1000:0;$end=$end?strtotime($end)*1000:0;if($end&&$end<=time()*1000){continue;}
 if(isset($counts[$pos])&&$counts[$pos]>=10){continue;}$desktop=g73_partner_media_data(g73_partner_image(get_post_meta($id,'g73_desktop_image',true)));$mobile=g73_partner_media_data(g73_partner_image(get_post_meta($id,'g73_mobile_image',true)));if(!$desktop){$desktop=$mobile;}if(!$desktop){continue;}
 $counts[$pos]=isset($counts[$pos])?$counts[$pos]+1:1;
 $rows[]=array('id'=>$id,'name'=>wp_specialchars_decode(get_the_title($id),ENT_QUOTES),'desktop'=>$desktop,'mobile'=>$mobile,'url'=>esc_url_raw(get_post_meta($id,'g73_url',true),array('http','https')),'position'=>$pos,'format'=>$format,'width'=>$formats[$format][1],'height'=>$formats[$format][2],'start'=>$start,'end'=>$end);
 }
 return array('server_time'=>time()*1000,'settings'=>g73_partner_config(),'campaigns'=>$rows);
}
add_action('rest_api_init',function(){register_rest_route('g73/v1','/partners',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){do_action('litespeed_control_set_nocache','Campanhas atuais');$response=new WP_REST_Response(g73_partner_payload());$response->header('Cache-Control','no-store, max-age=0');return $response;}));});
// Migração idempotente: mantém os arquivos antigos intactos e não reativa campanhas depois.
add_action('init',function(){if(get_option('g73_partners_migrated_v1')){return;}$old=get_option('g73_partner_ads',array());$ok=true;
 foreach(array('sidebar'=>'sidebar_lateral','article'=>'in_article_1') as $key=>$pos){if(empty($old[$key]['image_id'])&&empty($old[$key]['mobile_image_id'])){continue;}$slot=$old[$key];
 $existing=get_posts(array('post_type'=>'g73_partner','post_status'=>'any','posts_per_page'=>1,'meta_key'=>'g73_legacy_key','meta_value'=>$key,'fields'=>'ids'));if($existing){continue;}
 $format='sidebar'===$key&&'300x600'===$slot['format']?'meia_pagina':('article'===$key?'billboard':'retangulo_medio');
 $size=wp_get_attachment_image_src(g73_partner_image($slot['image_id']),'full');
 if($size){foreach(g73_partner_formats() as $candidate=>$dimensions){if($size[1]===$dimensions[1]&&$size[2]===$dimensions[2]){$format=$candidate;break;}}}
 $id=wp_insert_post(array('post_type'=>'g73_partner','post_status'=>'publish','post_title'=>!empty($slot['advertiser'])?$slot['advertiser']:'Giro73 — banner existente '.('sidebar'===$key?'lateral':'de matéria'),'meta_input'=>array(
 'g73_desktop_image'=>g73_partner_image($slot['image_id']),'g73_mobile_image'=>g73_partner_image($slot['mobile_image_id']),'g73_url'=>esc_url_raw($slot['url']),
 'g73_status'=>'ativo','g73_start_at'=>'','g73_end_at'=>'','g73_position'=>$pos,'g73_format'=>$format,'g73_legacy_key'=>$key)),true);if(is_wp_error($id)||!$id){$ok=false;}
 }
 if($ok){if(!get_option('g73_partners_config')){$defaults=g73_partner_defaults();if(!empty($old['contact_email'])){$defaults['contact_email']=sanitize_email($old['contact_email']);}add_option('g73_partners_config',$defaults);}update_option('g73_partners_migrated_v1',1,false);}
},20);
function g73_partner_unit($position){
 if(!isset(g73_partner_positions()[$position])||'popup_global'===$position){return '';}
 $GLOBALS['g73_partners_needed']=true;$payload=g73_partner_payload();$items=array_values(array_filter($payload['campaigns'],function($c)use($position){return $c['position']===$position;}));
 $empty=in_array($position,array('sidebar_lateral','in_article_1'),true);$width='sidebar_lateral'===$position?300:970;$ratio='sidebar_lateral'===$position?1.2:970/90;$mobile_ratio='sidebar_lateral'===$position?1.2:3.2;
 if($items){$width=max(array_column($items,'width'));$ratio=min(array_map(function($c){return $c['width']/$c['height'];},$items));$mobile_ratio=min(array_map(function($c){return $c['mobile']?$c['mobile']['width']/$c['mobile']['height']:$c['width']/$c['height'];},$items));}
 $stack='sidebar_lateral'===$position&&'empilhado'===$payload['settings']['modes'][$position];$count=$stack?max(1,count($items)):1;$frames='';
 for($i=0;$i<$count;$i++){$frames.='<div class="g73-media-frame"></div>';}
 return '<aside class="g73-partner-unit'.($stack?' g73-partner-stack':'').' g73-partner-unit--'.esc_attr($position).'" data-g73-position="'.esc_attr($position).'"'.($empty?' data-g73-empty="1"':'').(!$items&&!$empty?' hidden':'').' style="--g73-partner-width:'.(int)$width.'px;--g73-partner-ratio:'.esc_attr((string)$ratio).';--g73-partner-mobile-ratio:'.esc_attr((string)$mobile_ratio).'" aria-label="Publicidade"><span class="g73-partner-label">PUBLICIDADE</span><div class="g73-vitrine-box">'.$frames.'</div></aside>';
}
// Compatibilidade com todos os shortcodes já publicados.
function g73_ads_render($position){return g73_partner_unit('sidebar'===$position?'sidebar_lateral':('article'===$position?'in_article_1':$position));}
add_shortcode('giro73_publicidade',function($atts){$atts=shortcode_atts(array('posicao'=>'sidebar'),$atts);return g73_ads_render($atts['posicao']);});
add_shortcode('giro73_parceiros',function($atts){$atts=shortcode_atts(array('posicao'=>'sidebar_lateral'),$atts);return g73_partner_unit($atts['posicao']);});
add_filter('render_block_core/shortcode',function($html,$block){$raw=trim(isset($block['innerHTML'])?$block['innerHTML']:'');return preg_match('~^\[giro73_(?:publicidade|parceiros)(?:\s[^\]]*)?\]$~s',$raw)?do_shortcode($raw):$html;},10,2);
add_action('wp_footer',function(){
 $payload=g73_partner_payload();$enabled=$payload['settings']['overlay']['enabled'];if(empty($GLOBALS['g73_partners_needed'])&&!$enabled){return;}
 $payload['endpoint']=rest_url('g73/v1/partners');$payload['page']=is_front_page()?'home':(is_singular('post')?'materias':'outros');
 echo '<dialog class="g73-overlay-card" aria-label="Publicidade de parceiro Giro73"><div class="g73-overlay-toolbar"><span class="g73-partner-label">PUBLICIDADE</span><button type="button" data-g73-close>✕ Fechar</button></div><div class="g73-overlay-content"></div></dialog>';
 echo '<script type="application/json" id="g73-partners-boot">'.wp_json_encode($payload,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script><script id="g73-partners-runtime">';
 echo g73_partner_frontend_js();echo '</script>';
},30);
function g73_partner_purge(){static $queued=false;if(!$queued){$queued=true;add_action('shutdown',function(){do_action('litespeed_purge_all');},20);}}
add_action('save_post_g73_partner','g73_partner_purge',30);
add_action('rest_after_insert_g73_partner','g73_partner_purge',30);
foreach(array('added_post_meta','updated_post_meta','deleted_post_meta') as $hook){add_action($hook,function($mid,$id,$key){if('g73_partner'===get_post_type($id)&&0===strpos($key,'g73_')){g73_partner_purge();}},30,3);}
add_action('before_delete_post',function($id){if('g73_partner'===get_post_type($id)){g73_partner_purge();}});
add_action('update_option_g73_partners_config','g73_partner_purge');
add_action('add_option_g73_partners_config','g73_partner_purge');
// Gestão visual com os controles e a biblioteca de mídia nativos do WordPress.
add_action('admin_menu',function(){add_submenu_page('edit.php?post_type=g73_partner','Configurações Parceiros G73','Configurações','manage_options','g73-partners-settings','g73_partner_settings_page');});
function g73_partner_select($name,$value,$choices){echo '<select name="'.esc_attr($name).'">';foreach($choices as $key=>$label){echo '<option value="'.esc_attr($key).'"'.selected($value,$key,false).'>'.esc_html($label).'</option>';}echo '</select>';}
function g73_partner_settings_page(){if(!current_user_can('manage_options')){return;}$c=g73_partner_config();$p=$c['overlay'];
 echo '<div class="wrap"><h1>Parceiros G73 — Configurações</h1><p>Campanhas em Parceiros G73 → Nova campanha. Até dez peças por posição; somente campanhas publicadas, ativas e dentro da agenda são exibidas. Horário: '.esc_html(wp_timezone_string()).'.</p>';settings_errors();echo '<form action="options.php" method="post">';settings_fields('g73_partners');
 echo '<table class="form-table"><tr><th>E-mail comercial</th><td><input type="email" name="g73_partners_config[contact_email]" value="'.esc_attr($c['contact_email']).'" required></td></tr><tr><th>Modo da sidebar</th><td>';g73_partner_select('g73_partners_config[modes][sidebar_lateral]',$c['modes']['sidebar_lateral'],array('rotacao'=>'Rotação — visita e a cada 8 segundos','empilhado'=>'Empilhado — até dez peças'));echo '</td></tr><tr><th>Chave geral da sobreposição</th><td><input type="hidden" name="g73_partners_config[overlay][enabled]" value="0"><label><input type="checkbox" name="g73_partners_config[overlay][enabled]" value="1"'.checked($p['enabled'],true,false).'> Ligado</label><p class="description">Desligado até configurar uma campanha. A frequência diária reduz repetição; sobreposições podem afetar a experiência e o SEO, sobretudo no celular.</p></td></tr>';
 foreach(array('trigger'=>array('Gatilho',array('tempo'=>'Por tempo após carregar','rolagem'=>'Por rolagem')),'frequency'=>array('Frequência',array('1x_por_dia'=>'Uma vez por dia — recomendado','1x_por_sessao'=>'Uma vez por sessão','sempre'=>'Sempre — testes')),'scope'=>array('Onde exibir',array('todo_portal'=>'Todo o portal','home'=>'Apenas na Home','materias'=>'Apenas nas matérias'))) as $key=>$field){echo '<tr><th>'.esc_html($field[0]).'</th><td>';g73_partner_select('g73_partners_config[overlay]['.$key.']',$p[$key],$field[1]);echo '</td></tr>';}
 foreach(array('delay'=>array('Espera após carregar (segundos)',3,120),'scroll'=>array('Rolagem mínima (%)',1,95),'auto_close'=>array('Auto-fechar (segundos; 0 desativa)',0,120)) as $key=>$field){echo '<tr><th>'.esc_html($field[0]).'</th><td><input type="number" min="'.$field[1].'" max="'.$field[2].'" name="g73_partners_config[overlay]['.esc_attr($key).']" value="'.(int)$p[$key].'"></td></tr>';}
 echo '</table>';submit_button('Salvar configurações');echo '</form><p>Demais posições usam rotação. Pausa ao passar o mouse, focar um link ou ocultar a aba. Nenhum clique ou mensagem é enviado automaticamente.</p></div>';
}
add_action('add_meta_boxes_g73_partner',function(){remove_meta_box('postcustom','g73_partner','normal');add_meta_box('g73-partner-campaign','Peça, posição e contrato','g73_partner_metabox','g73_partner','normal','high');});
function g73_partner_metabox($post){wp_nonce_field('g73_partner_save','g73_partner_nonce');echo '<p>Nome do anunciante/campanha: use o título acima. Publique e escolha status Ativo para exibir.</p><table class="form-table">';
 foreach(array('g73_desktop_image'=>'Imagem desktop','g73_mobile_image'=>'Imagem mobile (opcional)') as $key=>$label){$id=(int)get_post_meta($post->ID,$key,true);echo '<tr><th>'.esc_html($label).'</th><td><input type="hidden" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.$id.'"><button type="button" class="button" data-g73-pick="'.esc_attr($key).'">Selecionar imagem</button> <button type="button" class="button" data-g73-remove="'.esc_attr($key).'">Remover</button><div id="'.esc_attr($key).'-preview">'.($id?wp_get_attachment_image($id,'thumbnail'):'Nenhuma imagem').'</div><p class="description">WebP, PNG ou JPG. Prefira peças leves. Mobile vazio reutiliza desktop.</p></td></tr>';}
 $value=get_post_meta($post->ID,'g73_url',true);echo '<tr><th>URL de destino</th><td><input type="url" class="large-text" name="g73_url" value="'.esc_attr($value).'" placeholder="https://"></td></tr>';
 foreach(array('g73_status'=>array('Status',array('pausado'=>'Pausado','ativo'=>'Ativo')),'g73_format'=>array('Formato',array_map(function($v){return $v[0];},g73_partner_formats())),'g73_position'=>array('Posição',g73_partner_positions())) as $key=>$field){$value=get_post_meta($post->ID,$key,true);if(!$value){$value=g73_partner_meta_schema()[$key]['default'];}echo '<tr><th>'.esc_html($field[0]).'</th><td>';g73_partner_select($key,$value,$field[1]);echo '</td></tr>';}
 foreach(array('g73_start_at'=>'Início (opcional)','g73_end_at'=>'Expiração (opcional)') as $key=>$label){$value=get_post_meta($post->ID,$key,true);$local=$value?wp_date('Y-m-d\TH:i',strtotime($value),wp_timezone()):'';echo '<tr><th>'.esc_html($label).'</th><td><input type="datetime-local" name="'.esc_attr($key).'" value="'.esc_attr($local).'"><span> '.esc_html(wp_timezone_string()).'</span></td></tr>';}
 echo '</table><p>A expiração interrompe a exibição automaticamente, sem depender do WP-Cron. Máximo dez campanhas elegíveis por posição, em ordem de cadastro. Use o formato Modal na posição Destaque sobreposto.</p>';
}
add_action('save_post_g73_partner',function($id){if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE||wp_is_post_revision($id)||!current_user_can('manage_options')||!isset($_POST['g73_partner_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['g73_partner_nonce'])),'g73_partner_save')){return;}
 $meta=array();foreach(g73_partner_meta_schema() as $key=>$schema){if(!isset($_POST[$key])){continue;}$v=wp_unslash($_POST[$key]);if(in_array($key,array('g73_start_at','g73_end_at'),true)&&$v){try{$v=(new DateTimeImmutable($v,wp_timezone()))->format(DATE_ATOM);}catch(Exception $e){$v='invalid';}}
 if(isset($schema['enum'])&&!in_array($v,$schema['enum'],true)){$v=$schema['default'];}$meta[$key]=g73_partner_clean_meta($v,$key);}
 $valid=g73_partner_validate($meta,$id);if(is_wp_error($valid)){set_transient('g73_partner_error_'.get_current_user_id(),$valid->get_error_message(),60);return;}
 foreach($meta as $key=>$value){update_post_meta($id,$key,$value);}
},10);
add_action('admin_notices',function(){$key='g73_partner_error_'.get_current_user_id();$message=get_transient($key);if($message){delete_transient($key);echo '<div class="notice notice-error"><p>'.esc_html($message).' Os dados anteriores da campanha foram preservados.</p></div>';}});
add_filter('manage_g73_partner_posts_columns',function($cols){$cols['g73_campaign']='Status / Posição / Agenda';return $cols;});
add_action('manage_g73_partner_posts_custom_column',function($column,$id){if('g73_campaign'!==$column){return;}$status=get_post_meta($id,'g73_status',true);$end=get_post_meta($id,'g73_end_at',true);$start=get_post_meta($id,'g73_start_at',true);if('ativo'===$status&&$end&&strtotime($end)<=time()){$status='Expirado';}elseif('ativo'===$status&&$start&&strtotime($start)>time()){$status='Agendado';}echo esc_html($status.' · '.get_post_meta($id,'g73_position',true));},10,2);
add_action('admin_enqueue_scripts',function(){ $screen=get_current_screen();if(!$screen||'g73_partner'!==$screen->post_type){return;}wp_enqueue_media();wp_add_inline_script('media-editor',g73_partner_admin_js());});
function g73_partner_admin_js(){return <<<'G73JS'
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-g73-pick]').forEach(b=>b.addEventListener('click',()=>{const f=document.getElementById(b.dataset.g73Pick),m=wp.media({title:'Selecionar peça — WebP, PNG ou JPG',button:{text:'Usar imagem'},library:{type:['image/webp','image/png','image/jpeg']},multiple:false});m.on('select',()=>{const x=m.state().get('selection').first().toJSON();if(!['image/webp','image/png','image/jpeg'].includes(x.mime))return;f.value=x.id;const p=document.getElementById(f.id+'-preview');p.textContent=x.filename;});m.open();}));document.querySelectorAll('[data-g73-remove]').forEach(b=>b.addEventListener('click',()=>{document.getElementById(b.dataset.g73Remove).value=0;document.getElementById(b.dataset.g73Remove+'-preview').textContent='Nenhuma imagem';}));});
G73JS;
}
function g73_partner_frontend_js(){return <<<'G73JS'
(()=>{'use strict';
const boot=document.getElementById('g73-partners-boot');if(!boot)return;
const initial=JSON.parse(boot.textContent),units=[...document.querySelectorAll('[data-g73-position]')];
let config=initial,clockOffset=0,opened=false,lastFocus=null,closeTimer=null;
const store=(type,key,value)=>{try{const s=type==='session'?sessionStorage:localStorage;if(value===undefined)return s.getItem(key);s.setItem(key,String(value));}catch(_){}return null;};
const now=()=>Date.now()+clockOffset,eligible=c=>(!c.start||now()>=c.start)&&(!c.end||now()<c.end);
const image=(c,eager=false)=>{const p=document.createElement('picture');if(c.mobile&&c.mobile.url!==c.desktop.url){const s=document.createElement('source');s.media='(max-width:767px)';s.srcset=c.mobile.url;p.append(s);}const i=document.createElement('img');i.src=c.desktop.url;i.alt=c.name;i.width=c.desktop.width;i.height=c.desktop.height;i.loading=eager?'eager':'lazy';i.decoding='async';p.append(i);if(!c.url)return p;const a=document.createElement('a');a.href=c.url;a.target='_blank';a.rel='sponsored noopener';a.append(p);return a;};
const frame=document.querySelector('.g73-overlay-card'),panel=frame?.querySelector('.g73-overlay-content');
const close=()=>{if(!frame||!frame.open)return;frame.close();clearTimeout(closeTimer);document.documentElement.classList.remove('g73-overlay-open');lastFocus?.focus({preventScroll:true});};
if(frame){frame.querySelector('[data-g73-close]').addEventListener('click',close);frame.addEventListener('click',e=>{if(e.target===frame){const r=frame.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)close();}});frame.addEventListener('cancel',e=>{e.preventDefault();close();});}
const allowed=()=>{const p=config.settings.overlay;if(p.frequency==='sempre')return true;if(p.frequency==='1x_por_sessao')return !store('session','g73-partner-seen');return store('local','g73-partner-day')!==new Intl.DateTimeFormat('en-CA',{timeZone:'America/Bahia',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(now()));};
function open(){const p=config.settings.overlay;if(opened||!p.enabled||!frame||!allowed())return;const items=config.campaigns.filter(c=>c.position==='popup_global'&&eligible(c));if(!items.length)return;
const index=Number(store('local','g73-partner-overlay-turn')||0)%items.length;const c=items[index];panel.replaceChildren(image(c,true));frame.dataset.campaign=String(c.id);lastFocus=document.activeElement;frame.showModal();document.documentElement.classList.add('g73-overlay-open');frame.querySelector('[data-g73-close]').focus();opened=true;store('local','g73-partner-overlay-turn',index+1);store('session','g73-partner-seen','1');store('local','g73-partner-day',new Intl.DateTimeFormat('en-CA',{timeZone:'America/Bahia',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(now())));if(p.auto_close>0)closeTimer=setTimeout(close,p.auto_close*1000);if(c.end)setTimeout(close,Math.min(2147483647,Math.max(1,c.end-now())));
}
const scopes=()=>{const p=config.settings.overlay;return p.scope==='todo_portal'||p.scope==='home'&&initial.page==='home'||p.scope==='materias'&&initial.page==='materias';};
function trigger(){if(!config.settings.overlay.enabled||!scopes())return;const p=config.settings.overlay;const run=()=>{if(p.trigger==='tempo')setTimeout(open,Math.max(3000,p.delay*1000));else{const watch=()=>{const range=document.documentElement.scrollHeight-innerHeight;if(range>0&&scrollY/range*100>=p.scroll){open();if(opened)removeEventListener('scroll',watch);}};addEventListener('scroll',watch,{passive:true});watch();}};if(document.readyState==='complete')run();else addEventListener('load',run,{once:true});}
const state=new Map();function render(){units.forEach(unit=>{const position=unit.dataset.g73Position,all=config.campaigns.filter(c=>c.position===position),items=all.filter(eligible),mode=position==='sidebar_lateral'?config.settings.modes[position]:'rotacao';let s=state.get(unit);if(!s){const key='g73-partner-turn-'+position,n=Number(store('local',key)||0);s={turn:n};state.set(unit,s);store('local',key,n+1);}
const signature=items.map(c=>c.id).join(',')+':'+(mode==='empilhado'?'all':items.length?s.turn%items.length:0);if(signature===s.signature)return;s.signature=signature;
const area=unit.querySelector('.g73-vitrine-box');area.replaceChildren();unit.hidden=!all.length&&!unit.dataset.g73Empty;
if(!items.length){if(unit.dataset.g73Empty){const a=document.createElement('a');a.className='g73-partner-contact';a.href='mailto:'+config.settings.contact_email+'?subject=Quero%20anunciar%20no%20Giro73';a.textContent='Espaço disponível — Anuncie aqui: '+config.settings.contact_email;area.append(a);}return;}
const chosen=mode==='empilhado'?items:[items[s.turn%items.length]];unit.classList.toggle('g73-partner-stack',mode==='empilhado');chosen.forEach(c=>{const f=document.createElement('div');f.className='g73-media-frame';f.dataset.campaign=String(c.id);f.append(image(c));area.append(f);});

});if(frame?.open&&!config.campaigns.some(c=>String(c.id)===frame.dataset.campaign&&eligible(c)))close();}
const start=()=>{render();trigger();setInterval(()=>{if(document.hidden||frame?.open)return;for(const [unit,s]of state){if(!unit.matches(':hover')&&!unit.contains(document.activeElement))s.turn++;}render();},8000);const edges=config.campaigns.flatMap(c=>[c.start,c.end]).filter(t=>t>now());edges.forEach(t=>setTimeout(render,Math.min(2147483647,t-now()+50)));let resize;addEventListener('resize',()=>{clearTimeout(resize);resize=setTimeout(()=>{state.forEach(s=>{s.signature='';});render();},120);});};
// Uma chamada local pequena evita campanhas vencidas em HTML do cache.
fetch(initial.endpoint,{credentials:'omit',cache:'no-store'}).then(r=>{if(!r.ok)throw Error('unavailable');return r.json();}).then(data=>{config=data;clockOffset=data.server_time-Date.now();start();}).catch(()=>{clockOffset=0;start();});
})();

G73JS;
}
}
