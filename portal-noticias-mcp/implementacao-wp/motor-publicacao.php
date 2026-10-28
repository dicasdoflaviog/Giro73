<?php
/** Giro73: recepção editorial autenticada. Não recebe Updates nativos do Telegram. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_pub_error')) {
if (!function_exists('g73_pub_error')) {
function g73_pub_error($code,$message,$status=400) { return new WP_Error('g73_'.$code,$message,array('status'=>$status)); }
}
if (!function_exists('g73_pub_option')) {
function g73_pub_option($key,$default=false) { return get_option('g73_publisher_'.$key,$default); }
}
if (!function_exists('g73_pub_set')) {
function g73_pub_set($key,$value) { return update_option('g73_publisher_'.$key,$value,false); }
}
if (!function_exists('g73_pub_hosts')) {
function g73_pub_hosts() {
 // Compatibilidade com consultas antigas: não existe mais allowlist de imagens.
 return array();
}
}
if (!function_exists('g73_pub_permission')) {
function g73_pub_permission($request) {
 if(!is_ssl()){return g73_pub_error('https_required','Utilize HTTPS.',403);}
 $hash=(string)g73_pub_option('token_hash','');$token=(string)$request->get_header('x-g73-token');
 if(!$hash){return g73_pub_error('not_configured','Integração ainda não configurada.',503);}
 if(strlen($token)>256||!hash_equals($hash,hash('sha256',$token))){return g73_pub_error('unauthorized','Token inválido ou ausente.',401);}
 if(!g73_pub_option('enabled',true)){return g73_pub_error('disabled','Recepção pausada pelo administrador.',503);}
 return true;
}
}
/** Acrescentar logs com comparação atômica preserva requisições concorrentes. */
if (!function_exists('g73_pub_log')) {
function g73_pub_log($row) {
 global $wpdb;$name='g73_publisher_logs';
 for($attempt=0;$attempt<5;$attempt++){
  $old=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",$name));
  if(null===$old){$inserted=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')",$name,maybe_serialize(array($row))));if(1===$inserted){wp_cache_delete('notoptions','options');wp_cache_delete($name,'options');return;}continue;}
  $rows=maybe_unserialize($old);if(!is_array($rows)){$rows=array();}array_unshift($rows,$row);$rows=array_slice($rows,0,100);
  $changed=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND BINARY option_value=%s",maybe_serialize($rows),$name,$old));
  if(1===$changed){wp_cache_delete($name,'options');return;}
 }
 error_log('G73 publication log fallback: '.wp_json_encode($row));
}
}
if (!function_exists('g73_pub_rotate')) {
function g73_pub_rotate() {
 $token=bin2hex(random_bytes(32));g73_pub_set('token_hash',hash('sha256',$token));return $token;
}
}
if (!function_exists('g73_pub_encrypt')) {
function g73_pub_encrypt($value) {
 if(!function_exists('openssl_encrypt')){return g73_pub_error('encryption','Criptografia indisponível; chave não salva.',503);}
 $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($value,'aes-256-gcm',hash('sha256',wp_salt('auth').'g73-telegram',true),OPENSSL_RAW_DATA,$iv,$tag);
 return false===$cipher?g73_pub_error('encryption','Não foi possível proteger a chave.',500):base64_encode($iv.$tag.$cipher);
}
}
/** Exclusão condicional impede liberar a reserva adquirida por outra requisição. */
if (!function_exists('g73_pub_unlock')) {
function g73_pub_unlock($owner) {
 global $wpdb;$wpdb->delete($wpdb->options,array('option_name'=>'g73_publisher_lock','option_value'=>$owner),array('%s','%s'));wp_cache_delete('g73_publisher_lock','options');
}
}
if (!function_exists('g73_pub_lock')) {
function g73_pub_lock() {
 $old=get_option('g73_publisher_lock','');
 if($old&&(int)strtok($old,'|')<time()){g73_pub_unlock($old);}
 global $wpdb;$owner=(time()+120).'|'.wp_generate_uuid4();
 $inserted=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')",'g73_publisher_lock',$owner));
 if(1===$inserted){wp_cache_delete('notoptions','options');wp_cache_set('g73_publisher_lock',$owner,'options');return $owner;}return false;
}
}
if (!function_exists('g73_pub_html')) {
function g73_pub_html($value) {
 $allowed=array('p'=>array(),'h2'=>array(),'h3'=>array(),'h4'=>array(),'ul'=>array(),'ol'=>array('start'=>true),'li'=>array(),'strong'=>array(),'b'=>array(),'em'=>array(),'i'=>array(),'a'=>array('href'=>true,'title'=>true,'rel'=>true),'blockquote'=>array('cite'=>true),'br'=>array(),'hr'=>array(),'table'=>array(),'thead'=>array(),'tbody'=>array(),'tr'=>array(),'th'=>array('scope'=>true),'td'=>array(),'figure'=>array('class'=>true),'figcaption'=>array(),'img'=>array('src'=>true,'alt'=>true,'width'=>true,'height'=>true,'class'=>true,'loading'=>true,'decoding'=>true));
 $allowed['p']['class']=true;$allowed['p']['data-g73-faq']=true;$allowed['h2']['id']=true;$allowed['h3']['data-g73-faq']=true;$allowed['blockquote']['class']=true;
 $allowed['nav']=array('class'=>true,'aria-label'=>true);$allowed['span']=array('class'=>true,'data-g73-faq'=>true);$allowed['section']=array('class'=>true,'id'=>true);$allowed['div']=array('class'=>true);
 $value=preg_replace('~<(script|style)\b[^>]*>[\s\S]*?</\1\s*>|<!--[\s\S]*?-->~i','',$value);
 $value=wp_kses($value,$allowed,array('http','https','mailto'));
 // Conteúdo externo não pode executar shortcodes, blocos reutilizáveis ou componentes administrativos.
 $value=str_replace(array('[',']'),array('&#91;','&#93;'),$value);
 return wpautop($value);
}
}
if (!function_exists('g73_pub_validate')) {
function g73_pub_validate($request) {
 if(strlen($request->get_body())>524288){return g73_pub_error('payload_large','JSON limitado a 512 KB.',413);}
 if(!$request->is_json_content_type()){return g73_pub_error('json_required','Envie Content-Type: application/json.',415);}
 $p=$request->get_json_params();if(!is_array($p)||array_keys($p)===range(0,count($p)-1)){return g73_pub_error('json_required','Envie um objeto JSON.',400);}
 foreach(array('title','content') as $key){if(!isset($p[$key])||!is_string($p[$key])||!trim($p[$key])){return g73_pub_error('invalid_'.$key,'Título e conteúdo são obrigatórios e devem ser texto.');}}
 $title=sanitize_text_field($p['title']);$content=g73_pub_html($p['content']);
 if(!trim($title)||strlen($title)>600||strlen($p['content'])>450000||!trim(wp_strip_all_tags($content))){return g73_pub_error('content_invalid','Título ou conteúdo vazio ou acima do limite.');}
 foreach(array('excerpt','columnist_slug','image_alt','image_url','image_credit','image_credit_url','source_url','focus_keyword','status') as $key){if(isset($p[$key])&&(!is_string($p[$key])||strlen($p[$key])>('excerpt'===$key?2000:2048))){return g73_pub_error('invalid_field','Campo textual inválido: '.$key);}}
 $seo_meta=array();
 if(isset($p['meta'])){
  if(!is_array($p['meta'])){return g73_pub_error('meta_invalid','O campo meta deve ser um objeto JSON.');}
  foreach(array('rank_math_focus_keyword','rank_math_title','rank_math_description') as $field){
   if(!array_key_exists($field,$p['meta'])){continue;}
   if(!is_string($p['meta'][$field])||strlen($p['meta'][$field])>4000){return g73_pub_error('meta_invalid','Metadado SEO deve ser texto de até 4000 bytes: '.$field);}
   $seo_meta[$field]=sanitize_text_field($p['meta'][$field]);
  }
 }
 $category=isset($p['category'])?$p['category']:null;$term=null;
 if(is_int($category)||is_string($category)&&ctype_digit($category)){$term=get_term((int)$category,'category');}
 elseif(is_string($category)&&strlen($category)<=200){$term=get_term_by('slug',sanitize_title($category),'category');if(!$term){$term=get_term_by('name',$category,'category');}}
 if(!$term||is_wp_error($term)){return g73_pub_error('category_invalid','Informe uma categoria existente por ID, slug ou nome.');}
 $slug=isset($p['columnist_slug'])?$p['columnist_slug']:'';$profiles=function_exists('g73_reg_profiles')?g73_reg_profiles():array();
 if($slug&&!isset($profiles[$slug])){return g73_pub_error('columnist_invalid','Coluna editorial não cadastrada.');}
 $status=isset($p['status'])?$p['status']:'publish';
 if(!in_array($status,array('draft','publish'),true)){return g73_pub_error('status_invalid','Use draft ou publish.');}
 $author=(int)g73_pub_option('author_id',1);if(!get_user_by('id',$author)||!user_can($author,'edit_posts')){return g73_pub_error('author_invalid','Configure um responsável editorial válido no painel.',503);}
 $tags=isset($p['tags'])?$p['tags']:array();if(!is_array($tags)||count($tags)>20){return g73_pub_error('tags_invalid','Envie uma lista de até 20 nomes de tags.');}
 $clean_tags=array();foreach($tags as $tag){if(!is_string($tag)||!trim($tag)||strlen($tag)>150){return g73_pub_error('tags_invalid','Cada tag deve ser um nome não vazio, com até 150 bytes.');}$clean_tags[]=sanitize_text_field($tag);}$clean_tags=array_values(array_unique($clean_tags));sort($clean_tags);
 // A capa é opcional: sua ausência nunca impede criar a matéria no status solicitado.
 $image=isset($p['image_url'])?trim($p['image_url']):'';
 $excerpt=isset($p['excerpt'])?sanitize_text_field($p['excerpt']):wp_trim_words(wp_strip_all_tags($content),40,'');$excerpt=wp_html_excerpt($excerpt,320,'');
 $credit=isset($p['image_credit'])?sanitize_text_field($p['image_credit']):'';$credit_url=isset($p['image_credit_url'])?$p['image_credit_url']:'';
 if($credit_url&&(!g73_pub_url($credit_url,false)||!$credit)){return g73_pub_error('credit_invalid','Crédito com URL exige nome e endereço HTTPS válido.');}
 $key=(string)$request->get_header('idempotency-key');if($key&&!preg_match('/^[A-Za-z0-9._:-]{1,160}$/D',$key)){return g73_pub_error('idempotency_invalid','Idempotency-Key deve conter até 160 letras, números, ponto, hífen, dois-pontos ou sublinhado.');}
 return array('title'=>$title,'content'=>$content,'excerpt'=>$excerpt,'category'=>(int)$term->term_id,'columnist_slug'=>$slug,'image_url'=>$image,'image_alt'=>isset($p['image_alt'])?wp_html_excerpt(sanitize_text_field($p['image_alt']),300,''):null,'tags'=>$clean_tags,'meta'=>$seo_meta,'status'=>$status,'author'=>$author,'image_credit'=>$credit,'image_credit_url'=>$credit_url,'source_url'=>g73_pub_source_url(isset($p['source_url'])?$p['source_url']:g73_pub_content_source($content)),'focus_keyword'=>isset($p['focus_keyword'])?wp_html_excerpt(sanitize_text_field($p['focus_keyword']),100,''):'','key'=>$key);
}
}
if (!function_exists('g73_pub_content_source')) {
function g73_pub_content_source($content) {
 preg_match_all('~<p\b[^>]*>(?:(?!</p>)[\s\S])*?</p>~i',$content,$paragraphs);
 foreach(array_reverse($paragraphs[0]) as $paragraph){
  if(!preg_match('~^(?:Fonte|Refer[êe]ncia|Com informa[çc][õo]es de)\s*:~iu',trim(wp_strip_all_tags($paragraph)))){continue;}
  if(class_exists('WP_HTML_Tag_Processor')){$tags=new WP_HTML_Tag_Processor($paragraph);if($tags->next_tag('A')){return (string)$tags->get_attribute('href');}}
  if(preg_match('~\bhref\s*=\s*["\x27]([^"\x27]+)["\x27]~i',$paragraph,$link)){return html_entity_decode($link[1],ENT_QUOTES,'UTF-8');}
 }return '';
}
}
if (!function_exists('g73_pub_source_url')) {
function g73_pub_source_url($url) {
 if(!$url||!is_string($url)){return '';}$p=wp_parse_url(html_entity_decode($url,ENT_QUOTES,'UTF-8'));
 if(!is_array($p)||empty($p['host'])||empty($p['scheme'])||!in_array(strtolower($p['scheme']),array('http','https'),true)||!empty($p['user'])||!empty($p['pass'])){return '';}
 $path=isset($p['path'])?rtrim($p['path'],'/'):'';$query=array();if(!empty($p['query'])){parse_str($p['query'],$query);}
 foreach(array_keys($query) as $name){if(preg_match('/^(?:utm_|fbclid$|gclid$|dclid$|msclkid$)/i',$name)){unset($query[$name]);}}
 $article_query=false;foreach(array('p','id','ID','noticia','article') as $name){if(isset($query[$name])&&is_scalar($query[$name])&&strlen((string)$query[$name])){$article_query=true;}}
 // Índices/homepages são referências genéricas, não identificadores de uma notícia.
 if(!$article_query&&(''===$path||preg_match('~/(?:category|tag|feed|rss)(?:/|$)|/(?:noticias|news)(?:/index\.php)?$|/index\.php$~i',$path))){return '';}
 ksort($query);$host=strtolower($p['host']);$port=isset($p['port'])&&!in_array((int)$p['port'],array(80,443),true)?':'.(int)$p['port']:'';
 return 'https://'.$host.$port.$path.($query?'?'.http_build_query($query):'');
}
}
/** Datas explícitas no endereço são indícios; não são data de publicação inventada. */
if (!function_exists('g73_pub_source_review')) {
function g73_pub_source_review($url,$now=null) {
 $path=strtolower(rawurldecode((string)wp_parse_url($url,PHP_URL_PATH)));$parts=null;
 if(preg_match('~/(20[0-9]{2})/([0-9]{2})/([0-9]{2})(?:/|$)~',$path,$m)){$parts=array((int)$m[1],(int)$m[2],(int)$m[3]);}
 elseif(preg_match('~(?:/|-)([0-9]{2})-([0-9]{2})-(20[0-9]{2})(?:/|$)~',$path,$m)){$parts=array((int)$m[3],(int)$m[2],(int)$m[1]);}
 elseif(preg_match('~/(?:noticia|novidade)-([0-9]{2})([0-9]{2})(20[0-9]{2})[0-9]{6,10}-~',$path,$m)){$parts=array((int)$m[3],(int)$m[2],(int)$m[1]);}
 $result=array('date'=>'','reason'=>'','historical'=>false);
 if(!$parts){return $result;}
 if(!checkdate($parts[1],$parts[2],$parts[0])){$result['reason']='data_da_referencia_invalida';return $result;}
 $stamp=gmmktime(0,0,0,$parts[1],$parts[2],$parts[0]);$now=null===$now?time():(int)$now;
 $result['date']=gmdate('Y-m-d',$stamp);$result['historical']=$stamp<$now-7*DAY_IN_SECONDS;
 if($result['historical']||$stamp>$now+DAY_IN_SECONDS){$result['reason']='referencia_historica_ou_futura_requer_revisao';}
 return $result;
}
}
add_action('init',function(){
 foreach(array('g73_editorial_archive','g73_news_eligible') as $key){register_post_meta('post',$key,array('single'=>true,'type'=>'boolean','default'=>false,'show_in_rest'=>true,'sanitize_callback'=>'rest_sanitize_boolean','auth_callback'=>function($allowed,$meta_key,$post_id){return current_user_can('edit_post',(int)$post_id);}));}
});
if (!function_exists('g73_pub_story_tokens')) {
function g73_pub_story_tokens($value) {
 $value=preg_replace('~<figure\b[^>]*>[\s\S]*?</figure>~i','',$value);
 $value=strtolower(remove_accents(html_entity_decode(wp_strip_all_tags($value),ENT_QUOTES,'UTF-8')));
 $stop=array_flip(explode(' ','a ao aos as com como da das de do dos e em entre esta este no na nos nas o os para pela pelo por que se sua seu sobre um uma apos sao mais foi foram ser seus suas esse essa isso tem'));
 $out=array();foreach(preg_split('/[^a-z0-9]+/',$value) as $word){if(strlen($word)>2&&!isset($stop[$word])){$out[$word]=true;}}return $out;
}
}
if (!function_exists('g73_pub_story_lead')) {
function g73_pub_story_lead($content) {
 $content=preg_replace('~<figure\b[^>]*>[\s\S]*?</figure>~i','',$content);preg_match_all('~<p\b[^>]*>([\s\S]*?)</p>~i',$content,$p);
 $lead=array();foreach($p[1] as $paragraph){$text=trim(html_entity_decode(wp_strip_all_tags($paragraph),ENT_QUOTES,'UTF-8'));if(''===$text||preg_match('~^(?:Fonte|Refer[êe]ncia|Com informa[çc][õo]es de)\s*:~iu',$text)){continue;}$lead[]=$text;if(count($lead)>=2){break;}}return implode(' ',$lead);
}
}
if (!function_exists('g73_pub_story_dice')) {
function g73_pub_story_dice($a,$b) {return $a&&$b?2*count(array_intersect_key($a,$b))/(count($a)+count($b)):0;}
}
if (!function_exists('g73_pub_find_duplicate')) {
function g73_pub_find_duplicate($p) {
 if($p['source_url']){
  $same=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','pending','future'),'posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>true,'meta_key'=>'_g73_source_url','meta_value'=>$p['source_url']));
  if($same){return array('id'=>(int)$same[0],'reason'=>'same_source','review'=>false);}
 }
 $recent=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','pending','future'),'posts_per_page'=>50,'no_found_rows'=>true,'orderby'=>'date','order'=>'DESC'));
 $title=g73_pub_story_tokens($p['title']);$lead=g73_pub_story_tokens(g73_pub_story_lead($p['content']));
 foreach($recent as $post){
  $old_source=get_post_meta($post->ID,'_g73_source_url',true)?:g73_pub_source_url(g73_pub_content_source($post->post_content));
  if($p['source_url']&&$old_source===$p['source_url']){return array('id'=>(int)$post->ID,'reason'=>'same_source','review'=>false);}
  if(g73_pub_story_tokens($post->post_title)===g73_pub_story_tokens($p['title'])){return array('id'=>(int)$post->ID,'reason'=>'same_title','review'=>false);}
  $old_title=g73_pub_story_tokens($post->post_title);$old_lead=g73_pub_story_tokens(g73_pub_story_lead($post->post_content));
  if(count(array_intersect_key($title,$old_title))>=4&&g73_pub_story_dice($title,$old_title)>=.65&&g73_pub_story_dice($lead,$old_lead)>=.35){
   return array('id'=>(int)$post->ID,'reason'=>'similar_title_and_lead','review'=>true);
  }
 }return null;
}
}
if (!function_exists('g73_pub_url')) {
function g73_pub_url($url,$image=true) {
 $p=wp_parse_url($url);$schemes=$image?array('http','https'):array('https');
 if(!is_array($p)||!isset($p['scheme'],$p['host'])||!in_array(strtolower($p['scheme']),$schemes,true)||!empty($p['user'])||!empty($p['pass'])||isset($p['port'])&&!in_array((int)$p['port'],array(80,443),true)||!empty($p['fragment'])){return false;}
 $ip=trim($p['host'],'[]');if(filter_var($ip,FILTER_VALIDATE_IP)&&!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)){return false;}
 // Qualquer host público é aceito. wp_safe_remote_get verifica DNS e redirects.
 return true;
}
}
if (!function_exists('g73_pub_image')) {
function g73_pub_image($url,$title,$credit='',$credit_url='',$alt=null) {
 if(!g73_pub_url($url)){return g73_pub_error('image_url','Use uma URL pública HTTP(S), sem credenciais ou endereços privados.',422);}
 $alt=null===$alt?$title:$alt;$mimes=array('image/jpeg','image/png','image/webp','image/gif','image/avif');
 // ALT global de uma mídia compartilhada não deve mudar em matérias anteriores.
 $local=attachment_url_to_postid($url);if($local){$file=get_attached_file($local);$m=wp_get_attachment_metadata($local);if($file&&is_file($file)&&filesize($file)<=8388608&&in_array(get_post_mime_type($local),$mimes,true)&&isset($m['width'],$m['height'])&&$m['width']>=200&&$m['height']>=200&&$m['width']*$m['height']<=24000000&&(string)get_post_meta($local,'_wp_attachment_image_alt',true)===$alt){return array('id'=>$local,'created'=>false,'bytes'=>filesize($file),'warnings'=>array());}}
 if(!wp_http_validate_url($url)){return g73_pub_error('image_url','URL de imagem não permitida.',422);}
 require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/image.php';
 $temp=wp_tempnam('g73-source');$made=array();$attachment=0;$ok=false;$warnings=array();
 try {
  if(!$temp){return g73_pub_error('image_temp','Não foi possível preparar a imagem.',500);}
  // O transporte seguro do WordPress valida também os destinos dos redirecionamentos.
  $r=wp_safe_remote_get($url,array('timeout'=>20,'redirection'=>5,'sslverify'=>true,'stream'=>true,'filename'=>$temp,'limit_response_size'=>8388609,'reject_unsafe_urls'=>true));
  if(is_wp_error($r)||200!==wp_remote_retrieve_response_code($r)){return g73_pub_error('image_download','Não foi possível baixar a imagem após até cinco redirecionamentos seguros.',422);}
  clearstatcache(true,$temp);
  if(!is_file($temp)||!filesize($temp)||filesize($temp)>8388608){return g73_pub_error('image_large','Imagem vazia ou acima de 8 MB.',422);}
  $size=wp_getimagesize($temp);$mime=wp_get_image_mime($temp);
  if(!$size||!in_array($mime,$mimes,true)||$size[0]<200||$size[1]<200||$size[0]*$size[1]>24000000){return g73_pub_error('image_dimensions','Use imagem válida com pelo menos 200×200 pixels, até 24 megapixels.',422);}
  $upload=wp_upload_dir();if(!empty($upload['error'])){return g73_pub_error('image_storage','Diretório de mídia indisponível.',500);}
  $path='';$ready='image/webp'===$mime&&1200===$size[0]&&675===$size[1]&&filesize($temp)<60000;
  $optimize=(bool)g73_pub_option('optimize_images',true);
  if($optimize&&!$ready){
   try {
    $editor=wp_get_image_editor($temp);
    if(!is_wp_error($editor)&&!is_wp_error($editor->resize(1200,675,true))){
     $target=trailingslashit($upload['path']).wp_unique_filename($upload['path'],'g73-feed-'.wp_generate_uuid4().'.webp');$made[]=$target;
     foreach(array(70,50,32,18) as $quality){
      if(is_wp_error($editor->set_quality($quality))){break;}
      $saved=$editor->save($target,'image/webp');
      if(is_wp_error($saved)||empty($saved['path'])||!is_file($saved['path'])){break;}
      $made[]=$saved['path'];clearstatcache(true,$saved['path']);
      if(filesize($saved['path'])<60000&&'image/webp'===wp_get_image_mime($saved['path'])){$path=$saved['path'];$mime='image/webp';break;}
     }
    }
   } catch(Throwable $error) {
    // GD/Imagick é uma otimização opcional, não condição para receber a matéria.
    $warnings[]='O editor de imagem falhou; foi utilizada a imagem original.';
   }
   if(!$path){$warnings[]='Otimização 1200×675 WebP abaixo de 60 KB indisponível; imagem original preservada.';}
  }
  if(!$path){
   $extensions=array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif','image/avif'=>'avif');
   $path=trailingslashit($upload['path']).wp_unique_filename($upload['path'],'g73-feed-'.wp_generate_uuid4().'.'.$extensions[$mime]);$made[]=$path;
   if(!copy($temp,$path)){return g73_pub_error('image_storage','Não foi possível salvar a imagem original.',500);}
  }
  $caption=$credit;if($credit_url){$caption.=' — <a href="'.esc_url($credit_url).'" rel="nofollow noopener" target="_blank">Fonte e licença</a>';}
  $attachment=wp_insert_attachment(wp_slash(array('post_mime_type'=>$mime,'post_title'=>$title,'post_excerpt'=>$caption,'post_status'=>'inherit')),$path,0,true);
  if(is_wp_error($attachment)||!$attachment){$attachment=0;return g73_pub_error('image_register','Não foi possível registrar a imagem.',500);}
  update_post_meta($attachment,'_wp_attachment_image_alt',wp_slash($alt));
  try {$metadata=wp_generate_attachment_metadata($attachment,$path);}catch(Throwable $error){$metadata=array();}
  // Dimensões nativas permitem usar o original mesmo sem miniaturas GD/Imagick.
  $actual=get_attached_file($attachment);if(!$actual||!is_file($actual)){$actual=$path;update_attached_file($attachment,$path);}
  $actual_size=wp_getimagesize($actual);
  if(!is_array($metadata)||empty($metadata['width'])||empty($metadata['height'])){
   $metadata=array('width'=>(int)($actual_size?$actual_size[0]:$size[0]),'height'=>(int)($actual_size?$actual_size[1]:$size[1]),'file'=>_wp_relative_upload_path($actual),'filesize'=>filesize($actual),'sizes'=>array(),'image_meta'=>array());
   $warnings[]='Miniaturas indisponíveis; metadados básicos da imagem original registrados.';
  }
  wp_update_attachment_metadata($attachment,$metadata);
  $keep=array($path,$actual);if(!empty($metadata['original_image'])){$keep[]=trailingslashit(dirname($actual)).$metadata['original_image'];}
  foreach(array_unique($made) as $file){if(!in_array($file,$keep,true)&&is_file($file)){wp_delete_file($file);}}
  $ok=true;return array('id'=>$attachment,'created'=>true,'bytes'=>filesize($actual),'warnings'=>array_values(array_unique($warnings)));
 } catch(Throwable $error) {
  return g73_pub_error('image_processing','Não foi possível preparar a capa; a matéria pode continuar sem imagem.',422);
 } finally {
  if($temp&&is_file($temp)){wp_delete_file($temp);}if(!$ok){if($attachment){wp_delete_attachment($attachment,true);}foreach(array_unique($made) as $f){if(is_file($f)){wp_delete_file($f);}}}
 }
}
}
if (!function_exists('g73_pub_content_image')) {
function g73_pub_content_image($content,$image,$alt,$credit='') {
 // Substitui somente a figura gerenciada pelo coletor, sem duplicar fotos no replay.
 $content=preg_replace('~<figure\b[^>]*\bclass\s*=\s*["\'][^"\']*\bg73-imported-image\b[^"\']*["\'][^>]*>[\s\S]*?</figure\s*>~i','',$content);
 if(!$image){return $content;}
 $url=wp_get_attachment_url($image['id']);if(!$url){return $content;}
 $metadata=wp_get_attachment_metadata($image['id']);$dimensions='';
 if(!empty($metadata['width'])&&!empty($metadata['height'])){$dimensions=' width="'.(int)$metadata['width'].'" height="'.(int)$metadata['height'].'"';}
 $caption=$credit?(preg_match('/^(?:foto|imagem|fonte|cr[eé]dito)\s*:/iu',$credit)?$credit:'Foto: '.$credit):'Imagem da fonte; crédito não informado.';
 $figure='<figure class="g73-imported-image"><img class="wp-image-'.(int)$image['id'].'" src="'.esc_url($url).'" alt="'.esc_attr($alt).'"'.$dimensions.' loading="lazy" decoding="async" /><figcaption>'.esc_html($caption).'</figcaption></figure>';
 if(preg_match('~<section class="g73-em-resumo">[\s\S]*?</section>~i',$content,$summary_match,PREG_OFFSET_CAPTURE)){$offset=$summary_match[0][1]+strlen($summary_match[0][0]);return substr_replace($content,"\n".$figure,$offset,0);}
 if(preg_match('~</p\s*>~i',$content,$match,PREG_OFFSET_CAPTURE)){$offset=$match[0][1]+strlen($match[0][0]);return substr_replace($content,"\n".$figure,$offset,0);}
 return $figure."\n".$content;
}
}
if (!function_exists('g73_pub_response')) {
function g73_pub_response($id,$created,$request_id) {
 $warnings=(array)get_post_meta($id,'_g73_image_warnings',true);
 if('draft'===get_post_status($id)){$warnings[]='Rascunho: permalink ainda não está público.';}
 if(!get_post_thumbnail_id($id)){$warnings[]='Matéria sem imagem destacada.';}
 $image_id=get_post_thumbnail_id($id);$m=$image_id?wp_get_attachment_metadata($image_id):array();$response=new WP_REST_Response(array('id'=>$id,'status'=>get_post_status($id),'permalink'=>get_permalink($id),'created'=>$created,'request_id'=>$request_id,
  'featured_media'=>$image_id,'image'=>$image_id?array('url'=>wp_get_attachment_url($image_id),'width'=>isset($m['width'])?$m['width']:0,'height'=>isset($m['height'])?$m['height']:0):null,
  'seo'=>array('focus_keyword'=>get_post_meta($id,'rank_math_focus_keyword',true),'schema'=>get_post_meta($id,'g73_is_opinion',true)?'OpinionNewsArticle':'NewsArticle','title'=>get_post_meta($id,'rank_math_title',true),'description'=>get_post_meta($id,'rank_math_description',true)),
  'warnings'=>array_values(array_unique(array_filter($warnings)))), $created?201:200);
 return $response;
}
}
/** Validação de coerência; não calcula nem grava pontuação Rank Math. */
if (!function_exists('g73_pub_seo_issues')) {
function g73_pub_seo_issues($p) {
 $meta=isset($p['meta'])?$p['meta']:array();$keyword=trim(isset($meta['rank_math_focus_keyword'])?$meta['rank_math_focus_keyword']:$p['focus_keyword']);
 if(!$keyword){return array('palavra-chave ausente');}
 preg_match('~<p\b[^>]*>([\s\S]*?)</p>~i',$p['content'],$lead);
 $fields=array('título'=>$p['title'],'título SEO'=>isset($meta['rank_math_title'])?$meta['rank_math_title']:$p['title'],
 'descrição SEO'=>isset($meta['rank_math_description'])?$meta['rank_math_description']:$p['excerpt'],'abertura'=>isset($lead[1])?$lead[1]:'');$issues=array();
 foreach($fields as $field=>$text){$text=html_entity_decode(wp_strip_all_tags($text),ENT_QUOTES,'UTF-8');if(false===mb_stripos($text,$keyword,0,'UTF-8')){$issues[]='palavra-chave incoerente em '.$field;}}
 return $issues;
}
}
/* RSS policy 2026-10-28: applied before image processing or post creation. */
if (!function_exists('g73_rss_stamp')) {
function g73_rss_stamp($value) {
 if(is_int($value)||is_float($value)){return is_finite((float)$value)?(int)$value:0;}
 if(!is_string($value)||!preg_match('~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$~D',$value)){return 0;}
 try{$date=new DateTimeImmutable($value);$errors=DateTimeImmutable::getLastErrors();return $errors&&($errors['warning_count']||$errors['error_count'])?0:$date->getTimestamp();}catch(Exception $e){return 0;}
}
}
if (!function_exists('g73_rss_municipal')) {
function g73_rss_municipal($data) {
 foreach(array('source_url','source_feed_url') as $field){$host=strtolower((string)wp_parse_url(isset($data[$field])&&is_string($data[$field])?$data[$field]:'',PHP_URL_HOST));
  if(preg_match('~(?:^|\.)(?:gov|leg)\.br$|prefeitura|camara|camar[a-z]*municipal~i',remove_accents($host))){return true;}}
 $name=remove_accents(strtolower(implode(' ',array(isset($data['source_name'])?$data['source_name']:'',isset($data['source_type'])?$data['source_type']:''))));
 return (bool)preg_match('~prefeitura|camara|municipal|diario oficial~i',$name);
}
}
if (!function_exists('g73_rss_policy_decide')) {
function g73_rss_policy_decide($data,$now=null) {
 $now=null===$now?time():(int)$now;$stamp=g73_rss_stamp(isset($data['source_published_at'])?$data['source_published_at']:null);
 if(!$stamp||!isset($data['source_date_basis'])||'published'!==$data['source_date_basis']){return g73_pub_error('rss_date_required','RSS exige data original válida; updated/captura não substituem pubDate.',422);}
 if($stamp>$now){return g73_pub_error('rss_future','Data original do RSS está no futuro.',422);}
 if($now-$stamp>86400){return g73_pub_error('rss_stale','RSS com mais de 24 horas: item descartado, sem criação de post.',422);}
 if(empty($data['source_url'])||!g73_pub_source_url($data['source_url'])){return g73_pub_error('rss_source_required','Informe a URL original da matéria.',422);}
 $lead=isset($data['source_lead'])&&is_string($data['source_lead'])?wp_strip_all_tags($data['source_lead']):'';
 if(!trim($lead)){return g73_pub_error('rss_lead_required','RSS exige os dois primeiros parágrafos da fonte para revisão temporal.',422);}
 $event=g73_rss_stamp(isset($data['source_event_at'])?$data['source_event_at']:null);
 if($event&&$now-$event>172800){return g73_pub_error('rss_old_fact','Fato original anterior a 48 horas: descartado.',422);}
 if(preg_match('~h[aá]\s+(\d+)\s+dias?~iu',$lead,$m)&&(int)$m[1]>2){return g73_pub_error('rss_old_fact','A abertura informa fato ocorrido há mais de 48 horas.',422);}
 preg_match_all('~\b(\d{1,2})/(\d{1,2})/(20\d{2})\b~',$lead,$dates,PREG_SET_ORDER);
 foreach($dates as $d){if(checkdate((int)$d[2],(int)$d[1],(int)$d[3])){$date=new DateTimeImmutable(sprintf('%04d-%02d-%02d 23:59:59',$d[3],$d[2],$d[1]),new DateTimeZone('America/Bahia'));if($now-$date->getTimestamp()>172800){return g73_pub_error('rss_old_fact','A abertura contém referência factual anterior a 48 horas; descartada para não requentar.',422);}}}
 return array('status'=>g73_rss_municipal($data)?'draft':'publish','source_published_at'=>$stamp,'policy_version'=>'2026-10-28','max_age_seconds'=>86400);
}
}
if (!function_exists('g73_rss_request_data')) {
function g73_rss_request_data($request) {
 $raw=$request->get_json_params();$out=array();
 foreach(array('source_published_at','source_date_basis','source_feed_url','source_name','source_type','source_lead','source_title','source_event_at','summary_points','editorial_hold') as $key){if(isset($raw[$key])){$out[$key]=$raw[$key];}}
 foreach(array('source_date_basis','source_feed_url','source_name','source_type','source_lead','source_title') as $key){if(isset($out[$key])&&(!is_string($out[$key])||strlen($out[$key])>20000)){$out[$key]='';}}
 return $out;
}
}
if (!function_exists('g73_rss_apply')) {
function g73_rss_apply($p,$request) {
 $data=array_merge($p,g73_rss_request_data($request));$decision=g73_rss_policy_decide($data);if(is_wp_error($decision)){return $decision;}
 $points=isset($data['summary_points'])?$data['summary_points']:array();
 if(!is_array($points)||count($points)!==3){return g73_pub_error('rss_summary_required','Informe exatamente três itens factuais em summary_points.',422);}
 $summary='<section class="g73-em-resumo"><h2>Em resumo</h2><ul>';
 foreach($points as $point){if(!is_string($point)||!trim($point)||strlen($point)>1500){return g73_pub_error('rss_summary_invalid','Resumo factual inválido.',422);}$summary.='<li>'.esc_html(sanitize_text_field($point)).'</li>';}$summary.='</ul></section>';
 $p['content']=preg_replace_callback('~</p>~i',function($m)use($summary){return $m[0].$summary;},$p['content'],1);
 $p['meta']['rank_math_title']=!empty($p['meta']['rank_math_title'])?$p['meta']['rank_math_title']:$p['title'];
 $p['meta']['rank_math_description']=!empty($p['meta']['rank_math_description'])?$p['meta']['rank_math_description']:wp_html_excerpt($p['excerpt']?:wp_strip_all_tags($p['content']),155,'');
 $p['meta']['rank_math_focus_keyword']=!empty($p['meta']['rank_math_focus_keyword'])?$p['meta']['rank_math_focus_keyword']:($p['focus_keyword']?:wp_trim_words($p['title'],5,''));
 $p['status']=!empty($data['editorial_hold'])?'draft':$decision['status'];
 $p['_rss']=$decision;$p['_rss']['source_feed_url']=isset($data['source_feed_url'])?esc_url_raw($data['source_feed_url']):'';
 $p['_rss']['source_name']=isset($data['source_name'])?sanitize_text_field($data['source_name']):'';
 return $p;
}
}
/* Authenticated preflight uses exactly the live date/status and duplicate checks; creates nothing. */
add_action('rest_api_init',function(){register_rest_route('g73/v1','/rss-preflight',array('methods'=>'POST','permission_callback'=>function($r){return current_user_can('manage_options')?true:g73_pub_permission($r);},'callback'=>function($r){
 $d=$r->get_json_params();if(!is_array($d)){return g73_pub_error('rss_payload','Envie objeto JSON.',400);}
 $result=g73_rss_policy_decide($d);if(is_wp_error($result)){return $result;}
 $p=array('title'=>isset($d['source_title'])?$d['source_title']:'','source_url'=>g73_pub_source_url($d['source_url']),'content'=>'<p>'.esc_html($d['source_lead']).'</p>');
 $duplicate=g73_pub_find_duplicate($p);if($duplicate){return new WP_Error('g73_duplicate_article','Pauta já existe; descartada antes da redação.',array('status'=>409,'existing_id'=>$duplicate['id'],'reason'=>$duplicate['reason']));}
 return array_merge($result,array('dry_run'=>true,'posts_created'=>0));
}));});

if (!function_exists('g73_pub_publish')) {
function g73_pub_publish($request) {
 // Permission callbacks podem ser consultados novamente ao gerar o header Allow.
 $GLOBALS['g73_pub_request']=array('request_id'=>wp_generate_uuid4(),'post_id'=>0,'started'=>microtime(true));
 $p=g73_pub_validate($request);if(is_wp_error($p)){return $p;}$owner=g73_pub_lock();if(!$owner){$e=g73_pub_error('busy','Outra publicação está em processamento. Repita em alguns segundos.',409);return $e;}
 $id=0;$image=null;$success=false;$warnings=array();
 try {
  $fingerprint=$p;unset($fingerprint['key'],$fingerprint['status'],$fingerprint['source_url']);if(empty($fingerprint['meta'])){unset($fingerprint['meta']);}$fingerprint=hash('sha256',wp_json_encode($fingerprint));$key=hash('sha256',$p['key']?:$fingerprint);
  $old=get_posts(array('post_type'=>'post','post_status'=>array('draft','publish','pending','future','private','trash'),'posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>true,'meta_key'=>'_g73_ingest_key','meta_value'=>$key));
  if($old){$old_id=(int)$old[0];$GLOBALS['g73_pub_request']['post_id']=$old_id;
   if(!hash_equals((string)get_post_meta($old_id,'_g73_ingest_fingerprint',true),$fingerprint)||'trash'===get_post_status($old_id)){return g73_pub_error('idempotency_conflict','Identificador já utilizado com outro conteúdo ou item na lixeira.',409);}
   return g73_pub_response($old_id,false,$GLOBALS['g73_pub_request']['request_id']);
  }
  $p=g73_rss_apply($p,$request);if(is_wp_error($p)){return $p;}
  $duplicate=g73_pub_find_duplicate($p);
  if($duplicate){return new WP_Error($duplicate['review']?'g73_duplicate_review':'g73_duplicate_article',$duplicate['review']?'Pauta semelhante já existe; compare os fatos antes de publicar.':'Esta notícia já foi recebida de sua fonte.',array('status'=>409,'existing_id'=>$duplicate['id'],'existing_url'=>get_permalink($duplicate['id']),'reason'=>$duplicate['reason']));}
  // Depois da resolução idempotente: não muda o fingerprint de payloads antigos.
  $source_review=g73_pub_source_review($p['source_url']);
  if($source_review['reason']){$p['status']='draft';$warnings[]='Referência temporal exige revisão: '.$source_review['reason'].($source_review['date']?' ('.$source_review['date'].')':'').'.';}
  $rate=(array)get_transient('g73_pub_rate');$now=time();$rate=array_values(array_filter($rate,function($v)use($now){return $v>$now-300;}));if(count($rate)>=20){return g73_pub_error('rate_limited','Limite de 20 tentativas de criação em cinco minutos.',429);}$rate[]=$now;set_transient('g73_pub_rate',$rate,300);
  $seo_issues=g73_pub_seo_issues($p);
  if($seo_issues){$p['status']='draft';$warnings[]='Revisão SEO: '.implode('; ',$seo_issues).'. Conteúdo preservado em rascunho.';}
  $image_alt=null!==$p['image_alt']?$p['image_alt']:'';
  if(!$image_alt&&class_exists('WP_HTML_Tag_Processor')){$image_tags=new WP_HTML_Tag_Processor($p['content']);while($image_tags->next_tag('IMG')){$existing_alt=trim((string)$image_tags->get_attribute('alt'));if($existing_alt){$image_alt=wp_html_excerpt(sanitize_text_field($existing_alt),300,'');break;}}}
  if(!$image_alt){$image_alt='Imagem que acompanha a matéria: '.$p['title'];}

  if($p['image_url']){
   try {$image=g73_pub_image($p['image_url'],$p['title'],$p['image_credit'],$p['image_credit_url'],$image_alt);}
   catch(Throwable $error){$image=g73_pub_error('image_processing','Falha ao preparar a capa.',422);}
   if(is_wp_error($image)){$warnings[]=$image->get_error_code().': '.$image->get_error_message().' Matéria recebida sem capa.';$image=null;}
   elseif(!empty($image['warnings'])){$warnings=array_merge($warnings,$image['warnings']);}
  }
  $p['content']=g73_pub_content_image($p['content'],$image,$image_alt,$p['image_credit']);
  if(!$image&&'publish'===$p['status']){$p['status']='draft';$warnings[]='Capa pendente: matéria preservada como rascunho para não publicar sem imagem.';}
  $meta=array('_g73_ingest_key'=>$key,'_g73_ingest_fingerprint'=>$fingerprint,'_g73_automated'=>1,'rank_math_title'=>$p['title'],'rank_math_description'=>wp_html_excerpt($p['excerpt'],160,''),'rank_math_robots'=>array('index','follow'),'rank_math_advanced_robots'=>array('max-snippet'=>-1,'max-video-preview'=>-1,'max-image-preview'=>'large'));
  if($p['source_url']){$meta['_g73_source_url']=$p['source_url'];}
  if($source_review['date']){$meta['_g73_source_reference_date']=$source_review['date'];}
  if($source_review['reason']){$meta['_g73_source_review_reason']=$source_review['reason'];$meta['g73_news_eligible']=false;}
  if($source_review['historical']){$meta['g73_editorial_archive']=true;}
  if($p['focus_keyword']){$meta['rank_math_focus_keyword']=$p['focus_keyword'];}
  // Somente os três campos SEO permitidos; meta recebido tem prioridade sobre os valores automáticos.
  $meta=array_merge($meta,$p['meta']);
  $meta['_g73_source_published_at']=$p['_rss']['source_published_at'];
  $meta['_g73_source_date_basis']='published';$meta['_g73_source_title']=sanitize_text_field((string)$request->get_param('source_title'));$meta['_g73_rss_policy_version']='2026-10-28';
  $meta['_g73_source_feed_url']=$p['_rss']['source_feed_url'];$meta['_g73_source_name']=$p['_rss']['source_name'];
  $meta['rank_math_facebook_title']=$meta['rank_math_title'];$meta['rank_math_facebook_description']=$meta['rank_math_description'];
  $meta['rank_math_twitter_title']=$meta['rank_math_title'];$meta['rank_math_twitter_description']=$meta['rank_math_description'];
  if($image){$meta['rank_math_facebook_image']=wp_get_attachment_url($image['id']);$meta['rank_math_twitter_image']=$meta['rank_math_facebook_image'];}
  $meta['rank_math_robots']=array('index');
  $meta['_g73_seo_review']=$seo_issues;
  $meta['_g73_image_warnings']=$warnings;
  if($image){$meta['_thumbnail_id']=$image['id'];}
  if($p['columnist_slug']){$meta['g73_columnist_slug']=$p['columnist_slug'];$meta['g73_is_opinion']=true;}
  // SEO e a capa disponível entram antes dos hooks; falhas de imagem não rejeitam o texto.
  $initial_status='publish'===$p['status']?'draft':$p['status'];
  $id=wp_insert_post(wp_slash(array('post_type'=>'post','post_status'=>$initial_status,'post_author'=>$p['author'],'post_title'=>$p['title'],'post_content'=>$p['content'],'post_excerpt'=>$p['excerpt'],'post_category'=>array($p['category']),'tags_input'=>$p['tags'],'meta_input'=>$meta,'comment_status'=>'closed','ping_status'=>'closed')),true);
  if(is_wp_error($id)||!$id){$id=0;return g73_pub_error('post_create','Não foi possível criar a matéria.',500);}$GLOBALS['g73_pub_request']['post_id']=$id;
  // Persistência explícita após confirmar o ID, incluindo os campos recebidos em parameters.meta.
  foreach($meta as $k=>$v){update_post_meta($id,$k,is_string($v)?wp_slash($v):$v);}
  $tags=wp_set_post_terms($id,$p['tags'],'post_tag',false);if(is_wp_error($tags)){return g73_pub_error('post_tags','Não foi possível associar as tags.',500);}
  if($p['columnist_slug']){update_post_meta($id,'g73_columnist_slug',$p['columnist_slug']);if(function_exists('g73_reg_sync')){g73_reg_sync($id);}}
  if($image){
   try {
    if((int)get_post_thumbnail_id($id)!==(int)$image['id']&&!set_post_thumbnail($id,$image['id'])){delete_post_meta($id,'_thumbnail_id');$warnings[]='Não foi possível associar a capa; matéria recebida sem imagem.';}
    else {update_post_meta($image['id'],'_wp_attachment_image_alt',wp_slash($image_alt));}
    if($image['created']){wp_update_post(array('ID'=>$image['id'],'post_parent'=>$id));}
   }catch(Throwable $error){delete_post_meta($id,'_thumbnail_id');$warnings[]='Falha na associação da capa; matéria recebida sem imagem.';}
   update_post_meta($id,'_g73_image_warnings',array_values(array_unique($warnings)));
   if(!get_post_thumbnail_id($id)){$p['status']='draft';}
  }
  // Falha fechada: confirmar imagem e persistência de todos os campos essenciais antes de publicar.
  if('publish'===$p['status']){
   $required=array('rank_math_title','rank_math_description','rank_math_focus_keyword','rank_math_facebook_title','rank_math_facebook_description','rank_math_facebook_image');
   foreach($required as $field){if(!trim((string)get_post_meta($id,$field,true))){$p['status']='draft';$warnings[]='SEO essencial ausente: '.$field;}}
   $size=wp_get_attachment_image_src(get_post_thumbnail_id($id),'full');
   if(!$size||$size[1]<1200){$p['status']='draft';$warnings[]='Capa com menos de 1200 pixels ou inválida: revisão manual.';}
   update_post_meta($id,'_g73_image_warnings',$warnings);
  }
  // Publicar somente depois de confirmar capa e metadados, sem janela pública incompleta.
  if('publish'===$p['status']){wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);}
  if($p['status']!==get_post_status($id)){return g73_pub_error('post_status','O WordPress não confirmou o status solicitado.',500);}
  clean_post_cache($id);do_action('litespeed_purge_post',$id);do_action('litespeed_purge_url',home_url('/'));$success=true;
  return g73_pub_response($id,true,$GLOBALS['g73_pub_request']['request_id']);
 } finally {if(!$success&&$id){wp_delete_post($id,true);}if(!$success&&is_array($image)&&!empty($image['created'])){wp_delete_attachment($image['id'],true);}g73_pub_unlock($owner);}
}
}
// NewsArticle permanece no único grafo Rank Math, com datas e capa reais; opinião é subtipo editorial.
add_filter('rank_math/json_ld',function($data){
 if(!is_singular('post')||!get_post_meta(get_queried_object_id(),'_g73_automated',true)){return $data;}
 $id=get_queried_object_id();$nodes=&$data;if(isset($data['@graph'])&&is_array($data['@graph'])){$nodes=&$data['@graph'];}
 foreach($nodes as &$node){if(!is_array($node)||!array_intersect((array)(isset($node['@type'])?$node['@type']:''),array('Article','BlogPosting','NewsArticle','OpinionNewsArticle'))){continue;}
  $node['@type']=get_post_meta($id,'g73_is_opinion',true)?'OpinionNewsArticle':'NewsArticle';$node['datePublished']=get_post_time(DATE_W3C,true,$id);$node['dateModified']=get_post_modified_time(DATE_W3C,true,$id);$node['mainEntityOfPage']=get_permalink($id);$node['inLanguage']='pt-BR';
  $image=wp_get_attachment_image_src(get_post_thumbnail_id($id),'full');if($image){$node['image']=array('@type'=>'ImageObject','url'=>$image[0],'width'=>$image[1],'height'=>$image[2]);}
 }unset($node);return $data;
},110);
add_filter('rest_post_dispatch',function($response,$server,$request){
 if('/g73/v1/publish'!==$request->get_route()){return $response;}$response=rest_ensure_response($response);$response->header('Cache-Control','private, no-store');$response->header('X-Content-Type-Options','nosniff');
 if(isset($GLOBALS['g73_pub_request'])){$c=$GLOBALS['g73_pub_request'];$d=$response->get_data();$code=isset($d['code'])?sanitize_key($d['code']):(isset($d['created'])&&$d['created']?'created':'replayed');
  g73_pub_log(array('time'=>gmdate('c'),'request_id'=>$c['request_id'],'post_id'=>(int)$c['post_id'],'http'=>$response->get_status(),'result'=>$code,'duration_ms'=>(int)((microtime(true)-$c['started'])*1000)));unset($GLOBALS['g73_pub_request']);
 }if(in_array($response->get_status(),array(409,429),true)){$response->header('Retry-After','15');}return $response;
},20,3);
add_action('rest_api_init',function(){
 register_rest_route('g73/v1','/publish',array('methods'=>'POST','permission_callback'=>'g73_pub_permission','callback'=>'g73_pub_publish'));
 register_rest_route('g73/v1','/publisher/token',array('methods'=>'POST','permission_callback'=>function(){return current_user_can('manage_options');},'callback'=>function(){$r=new WP_REST_Response(array('token'=>g73_pub_rotate(),'notice'=>'Copie agora. O valor não será exibido novamente.'),201);$r->header('Cache-Control','private, no-store');return $r;}));
 register_rest_route('g73/v1','/publisher/status',array('methods'=>'GET','permission_callback'=>function(){return current_user_can('manage_options');},'callback'=>function(){$r=new WP_REST_Response(array('enabled'=>(bool)g73_pub_option('enabled',true),'default_status'=>'publish','token_configured'=>(bool)g73_pub_option('token_hash'),'telegram_configured'=>(bool)g73_pub_option('telegram_encrypted'),'telegram_encryption_available'=>function_exists('openssl_encrypt')&&in_array('aes-256-gcm',openssl_get_cipher_methods(),true),'image_hosts'=>array(),'image_policy'=>'public_urls_no_allowlist','image_min_pixels'=>200,'author_id'=>(int)g73_pub_option('author_id',1),'endpoint'=>rest_url('g73/v1/publish')));$r->header('Cache-Control','private, no-store');return $r;}));
 register_rest_route('g73/v1','/publisher/logs',array('methods'=>'GET','permission_callback'=>function(){return current_user_can('manage_options');},'callback'=>function(){$r=new WP_REST_Response(g73_pub_option('logs',array()));$r->header('Cache-Control','private, no-store');return $r;}));
});
add_action('admin_menu',function(){add_options_page('Motor de Notícias G73','Motor de Notícias G73','manage_options','g73-publisher','g73_pub_screen');});
if (!function_exists('g73_pub_screen')) {
function g73_pub_screen() {
 if(!current_user_can('manage_options')){return;}$notice=isset($GLOBALS['g73_pub_notice'])?$GLOBALS['g73_pub_notice']:'';$token=isset($GLOBALS['g73_pub_one_time_token'])?$GLOBALS['g73_pub_one_time_token']:'';
 echo '<div class="wrap"><h1>Motor de Notícias G73</h1>'.($notice?'<div class="notice notice-info"><p>'.esc_html($notice).'</p></div>':'');
 if($token){echo '<div class="notice notice-warning"><p><strong>Copie o novo X-G73-Token agora. Ele não será exibido novamente; o anterior deixou de funcionar.</strong></p><p><code>'.esc_html($token).'</code></p></div>';}
 echo '<p>Endpoint: <code>'.esc_html(rest_url('g73/v1/publish')).'</code> • Header: <code>X-G73-Token</code>.</p><form method="post" action="'.esc_url(admin_url('options-general.php?page=g73-publisher')).'"><input type="hidden" name="g73_action" value="save">';wp_nonce_field('g73_publisher_save');
 echo '<p><label><input type="checkbox" name="enabled" value="1"'.checked((bool)g73_pub_option('enabled',true),true,false).'> Habilitar recepção externa</label></p><p>A política RSS impõe data original de até 24h e fontes institucionais em draft. O JSON solicita o status: <code>draft</code> ou <code>publish</code>. Se omitido, o padrão é <strong>publish</strong>. Para revisão, envie explicitamente <code>draft</code>.</p><p><label>Responsável editorial <select name="author_id">';
 foreach(get_users(array('capability'=>'edit_posts','fields'=>array('ID','display_name'))) as $user){echo '<option value="'.(int)$user->ID.'"'.selected((int)g73_pub_option('author_id',1),(int)$user->ID,false).'>'.esc_html($user->display_name).'</option>';}
 echo '</select></label></p><p>Imagens públicas HTTP(S) de qualquer domínio, sem whitelist. Mínimo 200×200 pixels, até 8 MB e 24 megapixels, com até cinco redirecionamentos seguros. Capas indisponíveis preservam a matéria em rascunho.</p><p><label><input type="checkbox" name="optimize_images" value="1"'.checked((bool)g73_pub_option('optimize_images',true),true,false).'> Tentar otimizar para 1200×675 WebP abaixo de 60 KB; preservar o original quando indisponível</label></p>';
 echo '<h2>Integração Telegram</h2><p>Estado: '.(g73_pub_option('telegram_encrypted')?'chave configurada':'chave ainda não informada').'. O bot/worker deve converter a mensagem em JSON editorial antes de chamar o endpoint; Updates nativos do Telegram não são artigos.</p><p><label>Nova chave de API do bot (vazio mantém a atual)<br><input class="large-text" type="password" autocomplete="new-password" name="telegram_token" value=""></label></p><p><label><input type="checkbox" name="clear_telegram" value="1"> Remover a chave Telegram salva</label></p>';submit_button('Salvar configurações');echo '</form><h2>Segurança do webhook</h2><p>Token: '.(g73_pub_option('token_hash')?'configurado (somente hash armazenado)':'não configurado').'. A rotação invalida imediatamente o token anterior.</p><form method="post" action="'.esc_url(admin_url('options-general.php?page=g73-publisher')).'"><input type="hidden" name="g73_action" value="rotate">';wp_nonce_field('g73_publisher_rotate');submit_button('Gerar / rotacionar token','secondary');echo '</form><h2>Últimas operações</h2><p>Até 100 registros. Sem token, chave Telegram, corpo da matéria ou URLs externas.</p><table class="widefat striped"><thead><tr><th>Data UTC</th><th>Post</th><th>HTTP</th><th>Resultado</th></tr></thead><tbody>';
 foreach(array_slice((array)g73_pub_option('logs',array()),0,20) as $row){echo '<tr><td>'.esc_html($row['time']).'</td><td>'.(int)$row['post_id'].'</td><td>'.(int)$row['http'].'</td><td>'.esc_html($row['result']).'</td></tr>';}
 echo '</tbody></table></div>';
}
}
if (!function_exists('g73_pub_save_settings')) {
function g73_pub_save_settings() {
 $author=isset($_POST['author_id'])?absint($_POST['author_id']):0;
 if(!$author||!user_can($author,'edit_posts')){return 'Configure um responsável editorial válido; nada foi alterado.';}
 $telegram=isset($_POST['telegram_token'])?trim(wp_unslash($_POST['telegram_token'])):'';$encrypted=null;
 if($telegram){if(!preg_match('/^[0-9]{5,16}:[A-Za-z0-9_-]{30,100}$/D',$telegram)){return 'Formato da chave Telegram inválido; nada foi alterado.';}$encrypted=g73_pub_encrypt($telegram);if(is_wp_error($encrypted)){return $encrypted->get_error_message();}}
 g73_pub_set('enabled',!empty($_POST['enabled']));g73_pub_set('author_id',$author);g73_pub_set('optimize_images',!empty($_POST['optimize_images']));
 if(!empty($_POST['clear_telegram'])){delete_option('g73_publisher_telegram_encrypted');}elseif(null!==$encrypted){g73_pub_set('telegram_encrypted',$encrypted);}
 return 'Configurações salvas.';
}
}
add_action('admin_init',function(){
 if('POST'!==$_SERVER['REQUEST_METHOD']||!isset($_GET['page'])||'g73-publisher'!==$_GET['page']||!isset($_POST['g73_action'])||!current_user_can('manage_options')){return;}
 $action=sanitize_key(wp_unslash($_POST['g73_action']));if(!in_array($action,array('save','rotate'),true)){return;}check_admin_referer('g73_publisher_'.$action);
 if('rotate'===$action){$GLOBALS['g73_pub_one_time_token']=g73_pub_rotate();$GLOBALS['g73_pub_notice']='Token atualizado.';}else{$GLOBALS['g73_pub_notice']=g73_pub_save_settings();}
});
}


/* G73 blocos editoriais v1: sem bibliotecas externas. */
add_filter('rank_math/json_ld',function($data){
 $GLOBALS['g73_has_faq_schema']=false;
 $walk=function($node)use(&$walk){if(!is_array($node)){return;}if(isset($node['@type'])&&in_array('FAQPage',(array)$node['@type'],true)){$GLOBALS['g73_has_faq_schema']=true;}foreach($node as $value){if(is_array($value)){$walk($value);}}};$walk($data);return $data;
},999);
add_filter('the_content',function($content){
 if(is_admin()||is_feed()||!is_singular('post')||!in_the_loop()||!is_main_query()||post_password_required()){return $content;}
 if(class_exists('WP_HTML_Tag_Processor')&&strpos($content,'g73-faq')!==false&&empty($GLOBALS['g73_has_faq_schema'])&&strpos($content,'schema.org/FAQPage')===false&&!get_post_meta(get_the_ID(),'rank_math_schema_FAQPage',true)){
  $tags=new WP_HTML_Tag_Processor($content);
  while($tags->next_tag()){
   if($tags->has_class('g73-faq')){$tags->set_attribute('itemscope','');$tags->set_attribute('itemtype','https://schema.org/FAQPage');}
   elseif($tags->has_class('g73-faq-item')){$tags->set_attribute('itemscope','');$tags->set_attribute('itemtype','https://schema.org/Question');$tags->set_attribute('itemprop','mainEntity');}
   elseif('H3'===$tags->get_tag()&&$tags->get_attribute('data-g73-faq')==='question'){$tags->set_attribute('itemprop','name');}
   elseif('P'===$tags->get_tag()&&$tags->get_attribute('data-g73-faq')==='answer'){$tags->set_attribute('itemscope','');$tags->set_attribute('itemtype','https://schema.org/Answer');$tags->set_attribute('itemprop','acceptedAnswer');}
   elseif($tags->get_attribute('data-g73-faq')==='text'){$tags->set_attribute('itemprop','text');}
  }$content=$tags->get_updated_html();
 }
 if(!empty($GLOBALS['g73_rendered_lead'][get_the_ID()])){$content=preg_replace('~^\s*<p class="g73-lead">[\s\S]*?</p>\s*~','',$content,1);}
 if(strpos($content,'g73-post-tools')!==false){return $content;}
 $url=get_permalink();$title=wp_strip_all_tags(get_the_title());$q=rawurlencode('Explique esta notícia, consultando a fonte e indicando eventuais limitações: '.$title.' — '.$url);
 $g73_icons=array('ChatGPT'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#10a37f" d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.142.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364 15.1192 7.2a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6067-1.4997Z"/></svg>','Perplexity'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#20b8cd" d="M22.3977 7.0896h-2.3106V.0676l-7.5094 6.3542V.1577h-1.1554v6.1966L4.4904 0v7.0896H1.6023v10.3976h2.8882V24l6.932-6.3591v6.2005h1.1554v-6.0469l6.9318 6.1807v-6.4879h2.8882V7.0896zm-3.4657-4.531v4.531h-5.355l5.355-4.531zm-13.2862.0676 4.8691 4.4634H5.6458V2.6262zM2.7576 16.332V8.245h7.8476l-6.1149 6.1147v1.9723H2.7576zm2.8882 5.0404v-3.8852h.0001v-2.6488l5.7763-5.7764v7.0111l-5.7764 5.2993zm12.7086.0248-5.7766-5.1509V9.0618l5.7766 5.7766v6.5588zm2.8882-5.0652h-1.733v-1.9723L13.3948 8.245h7.8478v8.087z"/></svg>','Google'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.98 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/></svg>','WhatsApp'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2zm0 18.06c-1.49 0-2.94-.4-4.22-1.16l-.3-.18-3.12.82.83-3.04-.2-.31a8.12 8.12 0 0 1-1.25-4.28c0-4.5 3.66-8.16 8.16-8.16 2.18 0 4.23.85 5.77 2.39 1.54 1.54 2.39 3.59 2.39 5.77.01 4.51-3.65 8.15-8.16 8.15z"/><path d="M8.6 7.3c-.3 0-.6.1-.8.4-.4.4-.7 1-.7 1.8 0 1.1.8 2.3 1 2.5.1.2 1.7 2.6 4.1 3.5 2 .8 2.4.6 2.8.5.4-.1 1.3-.6 1.5-1.2.2-.6.2-1.1.1-1.2-.1-.1-.3-.2-.6-.4l-1.6-.7c-.2-.1-.4-.2-.6.1l-.7.8c-.1.2-.3.2-.5.1-.3-.1-1-.4-1.8-1.1-.7-.6-1.1-1.3-1.3-1.6-.1-.2 0-.4.1-.5l.4-.4.3-.4c.1-.1.1-.3 0-.5l-.7-1.7c-.2-.4-.4-.4-.6-.4h-.4z"/></svg>','Telegram'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>','Facebook'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>','X'=>'<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>');
 $out='<aside class="g73-post-tools" aria-label="Ferramentas da notícia"><section class="g73-geo-block"><span class="g73-geo-title">Pergunte à IA sobre esta notícia</span><div class="g73-geo-buttons">';
 foreach(array('ChatGPT'=>'https://chatgpt.com/?q='.$q,'Perplexity'=>'https://www.perplexity.ai/search?q='.$q,'Google'=>'https://www.google.com/search?q='.rawurlencode($title.' '.$url)) as $name=>$link){$out.='<a class="g73-geo-btn" href="'.esc_url($link).'" target="_blank" rel="noopener noreferrer">'.$g73_icons[$name].esc_html($name).'</a>';}
 $out.='</div><p class="g73-tools-note">Confira as fontes: respostas de IA podem conter erros.</p></section><section class="g73-share-block"><p class="g73-share-title">Compartilhe esta notícia</p><div class="g73-share-buttons">';
 $text=rawurlencode($title.' '.$url);$u=rawurlencode($url);
 foreach(array('WhatsApp'=>'https://api.whatsapp.com/send?text='.$text,'Telegram'=>'https://t.me/share/url?url='.$u.'&text='.rawurlencode($title),'Facebook'=>'https://www.facebook.com/sharer/sharer.php?u='.$u,'X'=>'https://twitter.com/intent/tweet?url='.$u.'&text='.rawurlencode($title)) as $name=>$link){$out.='<a class="g73-share-btn" href="'.esc_url($link).'" target="_blank" rel="noopener noreferrer">'.$g73_icons[$name].esc_html($name).'</a>';}
 return $content.$out.'</div></section></aside>';
},30);
add_filter('do_shortcode_tag',function($output,$tag){
 if('giro73_assinatura_editorial'!==$tag||!is_singular('post')||strpos($output,'g73-author-policies')!==false){return $output;}
 // A assinatura é institucional: usa a marca real da Redação, não retrato ilustrativo.
 $photo=wp_get_attachment_image(56,array(80,80),false,array('alt'=>'Redação Giro73 — marca oficial','loading'=>'lazy','decoding'=>'async'));
 return '<div class="g73-author-box">'.($photo?'<div class="g73-author-avatar">'.$photo.'</div>':'').'<div class="g73-author-info">'.$output.'<p class="g73-author-links"><a href="'.esc_url(home_url('/sobre-nos/')).'">Quem Somos</a><a href="'.esc_url(home_url('/politica-editorial/')).'">Política Editorial</a></p></div></div>';
},30,2);

add_action('wp_head',function(){if(is_singular('post')){?>
<style id="g73-editorial-blocks">
/* Giro73 — referência visual Notícias Clique, CSS nativo. */
.g73-single{--g73-accent:#facc15;--g73-panel:#f8fafc;--g73-ink:#1e293b;--g73-line:#e2e8f0}
.g73-portal .g73-single .g73-lead{font-size:1.15rem;line-height:1.5;color:#4a5568;margin:0 0 20px;font-weight:400}
.g73-portal .g73-content .g73-toc{padding:20px;border:1px solid #e2e8f0;border-left:4px solid #facc15;border-radius:8px;background:#f8fafc;color:#1e293b;margin:24px 0}
.g73-toc-title{font-size:.8rem;font-weight:800;letter-spacing:.07em}.g73-toc ul{margin:12px 0 0;padding-left:20px}.g73-toc li{margin:6px 0}.g73-toc a{color:inherit;text-decoration:underline;text-underline-offset:3px}
.g73-content h2[id],.g73-content #faq{scroll-margin-top:6rem}
.g73-portal .g73-content blockquote.g73-destaque{background:#f8fafc;border:0;border-inline-start:4px solid #facc15;padding:16px 20px;margin:28px 0;font-style:italic;font-weight:400;color:#1e293b;border-radius:0 8px 8px 0}.g73-portal .g73-content .g73-destaque p{margin:0}
.g73-portal .g73-content .g73-post-tools{border:0;padding:0;margin:35px 0;background:transparent;color:#1e293b;border-radius:0}
.g73-portal .g73-content .g73-geo-block{text-align:center;padding:24px 16px;margin:0 0 24px;background:#fdfdfd;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0}
.g73-geo-title{display:block;font-size:.9rem;font-weight:700;color:#0f172a;margin-bottom:14px}
.g73-geo-buttons,.g73-share-buttons{display:flex;flex-wrap:wrap;justify-content:center;gap:10px}
.g73-portal .g73-content .g73-geo-btn{display:inline-flex;align-items:center;justify-content:center;padding:8px 18px;min-height:44px;box-sizing:border-box;border-radius:9999px;background:#fff;border:1px solid #cbd5e1;color:#1e293b;font-size:.85rem;font-weight:600;text-decoration:none;transition:background-color .2s ease,border-color .2s ease}
.g73-portal .g73-content .g73-geo-btn:hover{border-color:#0f172a;background:#f8fafc}
.g73-portal .g73-content .g73-tools-note{font-size:.8rem;line-height:1.5;margin:14px 0 0;color:#64748b}
.g73-share-block{text-align:center}.g73-portal .g73-content .g73-share-title{font-size:.85rem;margin:0 0 12px;color:#475569;font-weight:600}.g73-share-buttons a{display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:9999px;background:#111;color:#fff;min-height:44px;padding:8px 14px;box-sizing:border-box;font-size:.8rem;text-decoration:none}.g73-share-buttons a:hover{background:#334155}.g73-share-buttons svg{flex-shrink:0}
.g73-portal .g73-single .g73-author-box{background:#111;color:#fff;border:0;border-left:6px solid #facc15;border-radius:12px;padding:24px;display:flex;align-items:center;gap:20px;margin:40px 0;box-sizing:border-box}
.g73-author-avatar{flex:0 0 80px}.g73-portal .g73-author-avatar img{display:block;width:80px;height:80px;border-radius:50%;border:2px solid #facc15;object-fit:cover;background:#facc15;box-sizing:border-box}
.g73-author-info{flex:1;min-width:0}.g73-portal .g73-author-info .g73-author{padding:0;margin:0;background:transparent;border:0;color:#fff;border-radius:0}
.g73-portal .g73-author-info .g73-author h2,.g73-portal .g73-author-info .g73-author h4{margin:0 0 4px;color:#fff;font-size:1.15rem;line-height:1.35;border:0;padding:0}
.g73-portal .g73-author-info .g73-author p,.g73-portal .g73-author-info .g73-author-bio{font-size:.85rem;color:#cbd5e1;line-height:1.5;margin:6px 0}
.g73-portal .g73-author-info .g73-author a,.g73-portal .g73-author-links a{color:#facc15;text-decoration:underline;text-underline-offset:3px;font-size:.8rem;font-weight:600}
.g73-portal .g73-author-links{display:flex;flex-wrap:wrap;gap:6px 12px;margin:10px 0 0}
.g73-portal .g73-content .g73-faq{margin:35px 0;border:0;padding:0}.g73-portal .g73-content .g73-faq>h2{font-size:1.3rem;margin:0 0 16px;padding:0;border:0}.g73-portal .g73-content .g73-faq-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;margin:0 0 12px}.g73-portal .g73-content .g73-faq-item h3{margin:0 0 8px;font-size:1rem;line-height:1.45;color:#0f172a}.g73-portal .g73-content .g73-faq-item p{margin:0;font-size:.9rem;line-height:1.6;color:#475569}
.g73-portal .g73-related{margin:40px 0;padding:24px 0 0;border-top:1px solid #e2e8f0}.g73-portal .g73-related>h2{font-size:1.3rem;margin:0 0 20px;color:#0f172a}.g73-related-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.g73-related-card{overflow:hidden;border:1px solid #e2e8f0;border-radius:10px;background:#fff;min-width:0}.g73-related-image{display:block;aspect-ratio:16/9;overflow:hidden;background:#f1f5f9}.g73-related-image img{display:block;width:100%;height:100%;object-fit:cover}.g73-related-copy{padding:14px}.g73-related-badge{display:inline-block;border-radius:4px;padding:4px 7px;background:#facc15;color:#111;font-size:.65rem;font-weight:800;line-height:1.3;text-decoration:none;text-transform:uppercase}.g73-portal .g73-related-card h3{font-size:1rem;line-height:1.4;margin:10px 0;color:#0f172a}.g73-related-card h3 a{color:inherit;text-decoration:none}.g73-related-card h3 a:hover{text-decoration:underline}.g73-related-card time{font-size:.75rem;color:#64748b}
.g73-geo-btn:focus-visible,.g73-share-buttons a:focus-visible,.g73-author-box a:focus-visible,.g73-toc a:focus-visible,.g73-related a:focus-visible{outline:3px solid #a17b00;outline-offset:3px}
@media(max-width:600px){.g73-portal .g73-single .g73-author-box{align-items:flex-start;padding:20px;gap:14px}.g73-author-avatar{flex-basis:64px}.g73-portal .g73-author-avatar img{width:64px;height:64px}.g73-related-grid{grid-template-columns:1fr;gap:16px}.g73-related-image{aspect-ratio:16/9}.g73-geo-buttons{gap:8px}.g73-portal .g73-content .g73-geo-btn{padding:8px 16px}}
@media(max-width:360px){.g73-portal .g73-single .g73-author-box{flex-direction:column}}
@media(prefers-reduced-motion:reduce){.g73-portal .g73-content .g73-geo-btn{transition:none}}
@media print{.g73-post-tools,.g73-related{display:none}.g73-portal .g73-single .g73-author-box{background:#fff;color:#111;border:1px solid #ccc}.g73-portal .g73-author-info .g73-author h2,.g73-portal .g73-author-info .g73-author p{color:#111}}

/* G73: botões com ícones inline e compartilhamento amarelo. */
.g73-portal .g73-content .g73-geo-buttons{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin:16px 0 8px}
.g73-portal .g73-content .g73-geo-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:8px 18px;border-radius:9999px;background:#fff;border:1px solid #cbd5e1;color:#0f172a;font-size:.9rem;font-weight:600;text-decoration:none;min-height:44px;transition:background-color .2s ease,border-color .2s ease,transform .2s ease}
.g73-portal .g73-content .g73-geo-btn svg{width:18px;height:18px;flex-shrink:0}
.g73-portal .g73-content .g73-geo-btn:hover{border-color:#000;background:#f8fafc;transform:translateY(-1px)}
.g73-portal .g73-content .g73-geo-title,.g73-portal .g73-content .g73-share-title{font-size:1.05rem;font-weight:700;color:#0f172a}
.g73-portal .g73-content .g73-share-buttons{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;margin-top:14px}
.g73-portal .g73-content .g73-share-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 20px;border-radius:9999px;background:#ffd000;color:#000;font-size:.88rem;font-weight:700;text-decoration:none;border:0;box-shadow:0 2px 4px rgb(0 0 0 / 8%);min-height:44px;transition:background-color .2s ease,transform .2s ease,box-shadow .2s ease}
.g73-portal .g73-content .g73-share-btn svg{width:17px;height:17px;fill:#000;flex-shrink:0}
.g73-portal .g73-content .g73-share-btn:hover{background:#e6bc00;transform:translateY(-2px);box-shadow:0 4px 8px rgb(0 0 0 / 15%);color:#000}
@media(prefers-reduced-motion:reduce){.g73-portal .g73-content .g73-geo-btn,.g73-portal .g73-content .g73-share-btn{transition:none}.g73-portal .g73-content .g73-geo-btn:hover,.g73-portal .g73-content .g73-share-btn:hover{transform:none}}
</style>
<?php }} ,30);

add_filter('render_block_core/post-excerpt',function($html){
 if(is_singular('post')&&get_the_ID()===get_queried_object_id()&&preg_match('~^\s*(<p class="g73-lead">[\s\S]*?</p>)~',(string)get_post_field('post_content',get_queried_object_id()),$lead)){
  $GLOBALS['g73_rendered_lead'][get_queried_object_id()]=true;return wp_kses_post($lead[1]);
 }
 return $html;
},30);
// Inserções do template não devem separar perguntas de suas respostas.
add_filter('render_block_core/post-content',function($content){
 if(!is_singular('post')||strpos($content,'g73-faq')===false){return $content;}
 return preg_replace_callback('~<section\b[^>]*class="g73-faq"[^>]*>[\s\S]*?</section>~i',function($match){
  $after='';$faq=preg_replace_callback('~<aside\b(?=[^>]*\b(?:data-g73-read-also|data-g73-position))[^>]*>[\s\S]*?</aside>~i',function($aside)use(&$after){$after.=$aside[0];return '';},$match[0]);return $faq.$after;
 },$content);
},35);


// Três notícias da mesma categoria, renderizadas no servidor.
add_filter('render_block_core/group',function($html,$block){
 if(is_admin()||!is_singular('post')||is_feed()||post_password_required()){return $html;}
 $classes=isset($block['attrs']['className'])?preg_split('/\s+/',$block['attrs']['className']):array();
 if(!in_array('g73-related',$classes,true)){return $html;}
 $id=get_queried_object_id();$categories=wp_get_post_categories($id);if(!$categories){return '';}
 $posts=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>3,'post__not_in'=>array($id),'category__in'=>$categories,'has_password'=>false,'no_found_rows'=>true,'ignore_sticky_posts'=>true,'meta_query'=>array(array('key'=>'_thumbnail_id','value'=>0,'compare'=>'>','type'=>'NUMERIC'))));
 if(!$posts){return '';}
 $out='<section class="g73-related" aria-labelledby="g73-related-heading"><h2 id="g73-related-heading">Continue nesta editoria</h2><div class="g73-related-grid">';
 foreach($posts as $post){
  $terms=get_the_category($post->ID);$category=null;foreach($terms as $term){if(in_array((int)$term->term_id,$categories,true)){$category=$term;break;}}if(!$category&&$terms){$category=$terms[0];}
  $link=get_permalink($post->ID);$title=get_the_title($post->ID);
  $image=get_the_post_thumbnail($post->ID,'medium_large',array('loading'=>'lazy','decoding'=>'async','sizes'=>'(max-width: 600px) calc(100vw - 40px), 240px'));
  $out.='<article class="g73-related-card"><a class="g73-related-image" href="'.esc_url($link).'" aria-label="'.esc_attr($title).'">'.$image.'</a><div class="g73-related-copy">';
  if($category){$out.='<a class="g73-related-badge" href="'.esc_url(get_category_link($category->term_id)).'">'.esc_html($category->name).'</a>';}
  $out.='<h3><a href="'.esc_url($link).'">'.esc_html($title).'</a></h3><time datetime="'.esc_attr(get_post_time('c',true,$post->ID)).'">'.esc_html(get_the_date('d/m/Y',$post->ID)).'</time></div></article>';
 }
 return $out.'</div></section>';
},40,2);




// A revisão aparece apenas no editor; nenhuma alegação ou pontuação é fabricada.
add_action('admin_notices',function(){
 $screen=get_current_screen();if(!$screen||'post'!==$screen->base){return;}$id=isset($_GET['post'])?(int)$_GET['post']:0;
 if(!$id||!current_user_can('edit_post',$id)){return;}$issues=get_post_meta($id,'_g73_seo_review',true);
 if($issues&&is_array($issues)){echo '<div class="notice notice-warning"><p><strong>Giro73 — revisão SEO:</strong> '.esc_html(implode('; ',$issues)).'. Confira título, palavra-chave, descrição e abertura. Não acrescente fatos ou texto apenas para pontuação.</p></div>';}
});


