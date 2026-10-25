<?php
/** Giro73: recepção editorial autenticada. Não recebe Updates nativos do Telegram. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_pub_error')) {
function g73_pub_error($code,$message,$status=400) { return new WP_Error('g73_'.$code,$message,array('status'=>$status)); }
function g73_pub_option($key,$default=false) { return get_option('g73_publisher_'.$key,$default); }
function g73_pub_set($key,$value) { return update_option('g73_publisher_'.$key,$value,false); }
function g73_pub_hosts() {
 $hosts=(array)g73_pub_option('image_hosts',array(wp_parse_url(home_url(),PHP_URL_HOST),'upload.wikimedia.org','images.unsplash.com','images.pexels.com'));
 return array_values(array_unique(array_filter(array_map('strtolower',$hosts))));
}
function g73_pub_permission($request) {
 if(!is_ssl()){return g73_pub_error('https_required','Utilize HTTPS.',403);}
 $hash=(string)g73_pub_option('token_hash','');$token=(string)$request->get_header('x-g73-token');
 if(!$hash){return g73_pub_error('not_configured','Integração ainda não configurada.',503);}
 if(strlen($token)>256||!hash_equals($hash,hash('sha256',$token))){return g73_pub_error('unauthorized','Token inválido ou ausente.',401);}
 if(!g73_pub_option('enabled',true)){return g73_pub_error('disabled','Recepção pausada pelo administrador.',503);}
 return true;
}
/** Acrescentar logs com comparação atômica preserva requisições concorrentes. */
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
function g73_pub_rotate() {
 $token=bin2hex(random_bytes(32));g73_pub_set('token_hash',hash('sha256',$token));return $token;
}
function g73_pub_encrypt($value) {
 if(!function_exists('openssl_encrypt')){return g73_pub_error('encryption','Criptografia indisponível; chave não salva.',503);}
 $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($value,'aes-256-gcm',hash('sha256',wp_salt('auth').'g73-telegram',true),OPENSSL_RAW_DATA,$iv,$tag);
 return false===$cipher?g73_pub_error('encryption','Não foi possível proteger a chave.',500):base64_encode($iv.$tag.$cipher);
}
/** Exclusão condicional impede liberar a reserva adquirida por outra requisição. */
function g73_pub_unlock($owner) {
 global $wpdb;$wpdb->delete($wpdb->options,array('option_name'=>'g73_publisher_lock','option_value'=>$owner),array('%s','%s'));wp_cache_delete('g73_publisher_lock','options');
}
function g73_pub_lock() {
 $old=get_option('g73_publisher_lock','');
 if($old&&(int)strtok($old,'|')<time()){g73_pub_unlock($old);}
 global $wpdb;$owner=(time()+120).'|'.wp_generate_uuid4();
 $inserted=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,'no')",'g73_publisher_lock',$owner));
 if(1===$inserted){wp_cache_delete('notoptions','options');wp_cache_set('g73_publisher_lock',$owner,'options');return $owner;}return false;
}
function g73_pub_html($value) {
 $allowed=array('p'=>array(),'h2'=>array(),'h3'=>array(),'h4'=>array(),'ul'=>array(),'ol'=>array('start'=>true),'li'=>array(),'strong'=>array(),'b'=>array(),'em'=>array(),'i'=>array(),'a'=>array('href'=>true,'title'=>true,'rel'=>true),'blockquote'=>array('cite'=>true),'br'=>array(),'hr'=>array(),'table'=>array(),'thead'=>array(),'tbody'=>array(),'tr'=>array(),'th'=>array('scope'=>true),'td'=>array());
 $value=preg_replace('~<(script|style)\b[^>]*>[\s\S]*?</\1\s*>|<!--[\s\S]*?-->~i','',$value);
 $value=wp_kses($value,$allowed,array('http','https','mailto'));
 // Conteúdo externo não pode executar shortcodes, blocos reutilizáveis ou componentes administrativos.
 $value=str_replace(array('[',']'),array('&#91;','&#93;'),$value);
 return wpautop($value);
}
function g73_pub_validate($request) {
 if(strlen($request->get_body())>524288){return g73_pub_error('payload_large','JSON limitado a 512 KB.',413);}
 if(!$request->is_json_content_type()){return g73_pub_error('json_required','Envie Content-Type: application/json.',415);}
 $p=$request->get_json_params();if(!is_array($p)||array_keys($p)===range(0,count($p)-1)){return g73_pub_error('json_required','Envie um objeto JSON.',400);}
 foreach(array('title','content') as $key){if(!isset($p[$key])||!is_string($p[$key])||!trim($p[$key])){return g73_pub_error('invalid_'.$key,'Título e conteúdo são obrigatórios e devem ser texto.');}}
 $title=sanitize_text_field($p['title']);$content=g73_pub_html($p['content']);
 if(!trim($title)||strlen($title)>600||strlen($p['content'])>450000||!trim(wp_strip_all_tags($content))){return g73_pub_error('content_invalid','Título ou conteúdo vazio ou acima do limite.');}
 foreach(array('excerpt','columnist_slug','image_url','image_credit','image_credit_url','focus_keyword','status') as $key){if(isset($p[$key])&&(!is_string($p[$key])||strlen($p[$key])>('excerpt'===$key?2000:2048))){return g73_pub_error('invalid_field','Campo textual inválido: '.$key);}}
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
 return array('title'=>$title,'content'=>$content,'excerpt'=>$excerpt,'category'=>(int)$term->term_id,'columnist_slug'=>$slug,'image_url'=>$image,'tags'=>$clean_tags,'meta'=>$seo_meta,'status'=>$status,'author'=>$author,'image_credit'=>$credit,'image_credit_url'=>$credit_url,'focus_keyword'=>isset($p['focus_keyword'])?wp_html_excerpt(sanitize_text_field($p['focus_keyword']),100,''):'','key'=>$key);
}
function g73_pub_url($url,$image=true) {
 $p=wp_parse_url($url);if(!is_array($p)||!isset($p['scheme'],$p['host'])||'https'!==strtolower($p['scheme'])||!empty($p['user'])||!empty($p['pass'])||isset($p['port'])&&443!==(int)$p['port']||!empty($p['fragment'])){return false;}
 if(filter_var(trim($p['host'],'[]'),FILTER_VALIDATE_IP)){return false;}
 return !$image||in_array(strtolower($p['host']),g73_pub_hosts(),true);
}
function g73_pub_image($url,$title,$credit='',$credit_url='') {
 if(!g73_pub_url($url)){return g73_pub_error('image_host','Imagem deve usar HTTPS e um domínio autorizado no painel.',422);}
 // Reaproveitar mídia local pronta sem alterar créditos ou metadados de outros artigos.
 $local=attachment_url_to_postid($url);if($local){$file=get_attached_file($local);$m=wp_get_attachment_metadata($local);if($file&&is_file($file)&&filesize($file)<=8388608&&in_array(get_post_mime_type($local),array('image/jpeg','image/png','image/webp'),true)&&isset($m['width'],$m['height'])&&$m['width']>0&&$m['height']>0&&$m['width']*$m['height']<=24000000){return array('id'=>$local,'created'=>false,'bytes'=>filesize($file),'warnings'=>array());}}
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
  if(!$size||!in_array($mime,array('image/jpeg','image/png','image/webp'),true)||$size[0]<1||$size[1]<1||$size[0]*$size[1]>24000000){return g73_pub_error('image_dimensions','Use JPEG, PNG ou WebP válido, até 24 megapixels.',422);}
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
   $extensions=array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp');
   $path=trailingslashit($upload['path']).wp_unique_filename($upload['path'],'g73-feed-'.wp_generate_uuid4().'.'.$extensions[$mime]);$made[]=$path;
   if(!copy($temp,$path)){return g73_pub_error('image_storage','Não foi possível salvar a imagem original.',500);}
  }
  $caption=$credit;if($credit_url){$caption.=' — <a href="'.esc_url($credit_url).'" rel="nofollow noopener" target="_blank">Fonte e licença</a>';}
  $attachment=wp_insert_attachment(wp_slash(array('post_mime_type'=>$mime,'post_title'=>$title,'post_excerpt'=>$caption,'post_status'=>'inherit')),$path,0,true);
  if(is_wp_error($attachment)||!$attachment){$attachment=0;return g73_pub_error('image_register','Não foi possível registrar a imagem.',500);}
  update_post_meta($attachment,'_wp_attachment_image_alt',$title);
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
function g73_pub_publish($request) {
 // Permission callbacks podem ser consultados novamente ao gerar o header Allow.
 $GLOBALS['g73_pub_request']=array('request_id'=>wp_generate_uuid4(),'post_id'=>0,'started'=>microtime(true));
 $p=g73_pub_validate($request);if(is_wp_error($p)){return $p;}$owner=g73_pub_lock();if(!$owner){$e=g73_pub_error('busy','Outra publicação está em processamento. Repita em alguns segundos.',409);return $e;}
 $id=0;$image=null;$success=false;$warnings=array();
 try {
  $fingerprint=$p;unset($fingerprint['key'],$fingerprint['status']);if(empty($fingerprint['meta'])){unset($fingerprint['meta']);}$fingerprint=hash('sha256',wp_json_encode($fingerprint));$key=hash('sha256',$p['key']?:$fingerprint);
  $old=get_posts(array('post_type'=>'post','post_status'=>array('draft','publish','pending','future','private','trash'),'posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>true,'meta_key'=>'_g73_ingest_key','meta_value'=>$key));
  if($old){$old_id=(int)$old[0];$GLOBALS['g73_pub_request']['post_id']=$old_id;
   if(!hash_equals((string)get_post_meta($old_id,'_g73_ingest_fingerprint',true),$fingerprint)||'trash'===get_post_status($old_id)){return g73_pub_error('idempotency_conflict','Identificador já utilizado com outro conteúdo ou item na lixeira.',409);}
   return g73_pub_response($old_id,false,$GLOBALS['g73_pub_request']['request_id']);
  }
  $rate=(array)get_transient('g73_pub_rate');$now=time();$rate=array_values(array_filter($rate,function($v)use($now){return $v>$now-300;}));if(count($rate)>=20){return g73_pub_error('rate_limited','Limite de 20 tentativas de criação em cinco minutos.',429);}$rate[]=$now;set_transient('g73_pub_rate',$rate,300);
  if($p['image_url']){
   try {$image=g73_pub_image($p['image_url'],$p['title'],$p['image_credit'],$p['image_credit_url']);}
   catch(Throwable $error){$image=g73_pub_error('image_processing','Falha ao preparar a capa.',422);}
   if(is_wp_error($image)){$warnings[]=$image->get_error_code().': '.$image->get_error_message().' Matéria recebida sem capa.';$image=null;}
   elseif(!empty($image['warnings'])){$warnings=array_merge($warnings,$image['warnings']);}
  }
  $meta=array('_g73_ingest_key'=>$key,'_g73_ingest_fingerprint'=>$fingerprint,'_g73_automated'=>1,'rank_math_title'=>$p['title'],'rank_math_description'=>wp_html_excerpt($p['excerpt'],160,''),'rank_math_robots'=>array('index','follow'),'rank_math_advanced_robots'=>array('max-snippet'=>-1,'max-video-preview'=>-1,'max-image-preview'=>'large'));
  if($p['focus_keyword']){$meta['rank_math_focus_keyword']=$p['focus_keyword'];}
  // Somente os três campos SEO permitidos; meta recebido tem prioridade sobre os valores automáticos.
  $meta=array_merge($meta,$p['meta']);
  $meta['_g73_image_warnings']=$warnings;
  if($image){$meta['_thumbnail_id']=$image['id'];}
  if($p['columnist_slug']){$meta['g73_columnist_slug']=$p['columnist_slug'];$meta['g73_is_opinion']=true;}
  // SEO e a capa disponível entram antes dos hooks; falhas de imagem não rejeitam o texto.
  $id=wp_insert_post(wp_slash(array('post_type'=>'post','post_status'=>$p['status'],'post_author'=>$p['author'],'post_title'=>$p['title'],'post_content'=>$p['content'],'post_excerpt'=>$p['excerpt'],'post_category'=>array($p['category']),'tags_input'=>$p['tags'],'meta_input'=>$meta,'comment_status'=>'closed','ping_status'=>'closed')),true);
  if(is_wp_error($id)||!$id){$id=0;return g73_pub_error('post_create','Não foi possível criar a matéria.',500);}$GLOBALS['g73_pub_request']['post_id']=$id;
  // Persistência explícita após confirmar o ID, incluindo os campos recebidos em parameters.meta.
  foreach($meta as $k=>$v){update_post_meta($id,$k,is_string($v)?wp_slash($v):$v);}
  $tags=wp_set_post_terms($id,$p['tags'],'post_tag',false);if(is_wp_error($tags)){return g73_pub_error('post_tags','Não foi possível associar as tags.',500);}
  if($p['columnist_slug']){update_post_meta($id,'g73_columnist_slug',$p['columnist_slug']);if(function_exists('g73_reg_sync')){g73_reg_sync($id);}}
  if($image){
   try {
    if((int)get_post_thumbnail_id($id)!==(int)$image['id']&&!set_post_thumbnail($id,$image['id'])){delete_post_meta($id,'_thumbnail_id');$warnings[]='Não foi possível associar a capa; matéria recebida sem imagem.';}
    if($image['created']){wp_update_post(array('ID'=>$image['id'],'post_parent'=>$id));}
   }catch(Throwable $error){delete_post_meta($id,'_thumbnail_id');$warnings[]='Falha na associação da capa; matéria recebida sem imagem.';}
   update_post_meta($id,'_g73_image_warnings',array_values(array_unique($warnings)));
  }
  if($p['status']!==get_post_status($id)){return g73_pub_error('post_status','O WordPress não confirmou o status solicitado.',500);}
  clean_post_cache($id);do_action('litespeed_purge_post',$id);do_action('litespeed_purge_url',home_url('/'));$success=true;
  return g73_pub_response($id,true,$GLOBALS['g73_pub_request']['request_id']);
 } finally {if(!$success&&$id){wp_delete_post($id,true);}if(!$success&&is_array($image)&&!empty($image['created'])){wp_delete_attachment($image['id'],true);}g73_pub_unlock($owner);}
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
 register_rest_route('g73/v1','/publisher/status',array('methods'=>'GET','permission_callback'=>function(){return current_user_can('manage_options');},'callback'=>function(){$r=new WP_REST_Response(array('enabled'=>(bool)g73_pub_option('enabled',true),'default_status'=>'publish','token_configured'=>(bool)g73_pub_option('token_hash'),'telegram_configured'=>(bool)g73_pub_option('telegram_encrypted'),'telegram_encryption_available'=>function_exists('openssl_encrypt')&&in_array('aes-256-gcm',openssl_get_cipher_methods(),true),'image_hosts'=>g73_pub_hosts(),'author_id'=>(int)g73_pub_option('author_id',1),'endpoint'=>rest_url('g73/v1/publish')));$r->header('Cache-Control','private, no-store');return $r;}));
 register_rest_route('g73/v1','/publisher/logs',array('methods'=>'GET','permission_callback'=>function(){return current_user_can('manage_options');},'callback'=>function(){$r=new WP_REST_Response(g73_pub_option('logs',array()));$r->header('Cache-Control','private, no-store');return $r;}));
});
add_action('admin_menu',function(){add_options_page('Motor de Notícias G73','Motor de Notícias G73','manage_options','g73-publisher','g73_pub_screen');});
function g73_pub_screen() {
 if(!current_user_can('manage_options')){return;}$notice=isset($GLOBALS['g73_pub_notice'])?$GLOBALS['g73_pub_notice']:'';$token=isset($GLOBALS['g73_pub_one_time_token'])?$GLOBALS['g73_pub_one_time_token']:'';
 echo '<div class="wrap"><h1>Motor de Notícias G73</h1>'.($notice?'<div class="notice notice-info"><p>'.esc_html($notice).'</p></div>':'');
 if($token){echo '<div class="notice notice-warning"><p><strong>Copie o novo X-G73-Token agora. Ele não será exibido novamente; o anterior deixou de funcionar.</strong></p><p><code>'.esc_html($token).'</code></p></div>';}
 echo '<p>Endpoint: <code>'.esc_html(rest_url('g73/v1/publish')).'</code> • Header: <code>X-G73-Token</code>.</p><form method="post" action="'.esc_url(admin_url('options-general.php?page=g73-publisher')).'"><input type="hidden" name="g73_action" value="save">';wp_nonce_field('g73_publisher_save');
 echo '<p><label><input type="checkbox" name="enabled" value="1"'.checked((bool)g73_pub_option('enabled',true),true,false).'> Habilitar recepção externa</label></p><p>O JSON define o status: <code>draft</code> ou <code>publish</code>. Se omitido, o padrão é <strong>publish</strong>. Para revisão, envie explicitamente <code>draft</code>.</p><p><label>Responsável editorial <select name="author_id">';
 foreach(get_users(array('capability'=>'edit_posts','fields'=>array('ID','display_name'))) as $user){echo '<option value="'.(int)$user->ID.'"'.selected((int)g73_pub_option('author_id',1),(int)$user->ID,false).'>'.esc_html($user->display_name).'</option>';}
 echo '</select></label></p><p><label>Domínios HTTPS autorizados para imagens (um por linha)<br><textarea class="large-text code" name="image_hosts" rows="5">'.esc_textarea(implode("\n",g73_pub_hosts())).'</textarea></label></p><p>Downloads limitados a 8 MB e 24 megapixels, com até cinco redirecionamentos seguros. URLs inseguras e arquivos inválidos são ignorados sem rejeitar a matéria.</p><p><label><input type="checkbox" name="optimize_images" value="1"'.checked((bool)g73_pub_option('optimize_images',true),true,false).'> Tentar otimizar para 1200×675 WebP abaixo de 60 KB; preservar o original quando indisponível</label></p>';
 echo '<h2>Integração Telegram</h2><p>Estado: '.(g73_pub_option('telegram_encrypted')?'chave configurada':'chave ainda não informada').'. O bot/worker deve converter a mensagem em JSON editorial antes de chamar o endpoint; Updates nativos do Telegram não são artigos.</p><p><label>Nova chave de API do bot (vazio mantém a atual)<br><input class="large-text" type="password" autocomplete="new-password" name="telegram_token" value=""></label></p><p><label><input type="checkbox" name="clear_telegram" value="1"> Remover a chave Telegram salva</label></p>';submit_button('Salvar configurações');echo '</form><h2>Segurança do webhook</h2><p>Token: '.(g73_pub_option('token_hash')?'configurado (somente hash armazenado)':'não configurado').'. A rotação invalida imediatamente o token anterior.</p><form method="post" action="'.esc_url(admin_url('options-general.php?page=g73-publisher')).'"><input type="hidden" name="g73_action" value="rotate">';wp_nonce_field('g73_publisher_rotate');submit_button('Gerar / rotacionar token','secondary');echo '</form><h2>Últimas operações</h2><p>Até 100 registros. Sem token, chave Telegram, corpo da matéria ou URLs externas.</p><table class="widefat striped"><thead><tr><th>Data UTC</th><th>Post</th><th>HTTP</th><th>Resultado</th></tr></thead><tbody>';
 foreach(array_slice((array)g73_pub_option('logs',array()),0,20) as $row){echo '<tr><td>'.esc_html($row['time']).'</td><td>'.(int)$row['post_id'].'</td><td>'.(int)$row['http'].'</td><td>'.esc_html($row['result']).'</td></tr>';}
 echo '</tbody></table></div>';
}
function g73_pub_save_settings() {
 $author=isset($_POST['author_id'])?absint($_POST['author_id']):0;$raw=isset($_POST['image_hosts'])?wp_unslash($_POST['image_hosts']):'';$hosts=array();
 foreach(preg_split('/[\s,]+/',strtolower($raw),-1,PREG_SPLIT_NO_EMPTY) as $host){if(!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D',$host)||strlen($host)>253){return 'Domínio inválido. Não use protocolo, caminho, IP ou asterisco.';}$hosts[]=$host;}
 if(count($hosts)>30||!$author||!user_can($author,'edit_posts')){return 'Configurações inválidas; nada foi alterado.';}
 $telegram=isset($_POST['telegram_token'])?trim(wp_unslash($_POST['telegram_token'])):'';$encrypted=null;
 if($telegram){if(!preg_match('/^[0-9]{5,16}:[A-Za-z0-9_-]{30,100}$/D',$telegram)){return 'Formato da chave Telegram inválido; nada foi alterado.';}$encrypted=g73_pub_encrypt($telegram);if(is_wp_error($encrypted)){return $encrypted->get_error_message();}}
 g73_pub_set('enabled',!empty($_POST['enabled']));g73_pub_set('author_id',$author);g73_pub_set('image_hosts',array_values(array_unique($hosts)));g73_pub_set('optimize_images',!empty($_POST['optimize_images']));
 if(!empty($_POST['clear_telegram'])){delete_option('g73_publisher_telegram_encrypted');}elseif(null!==$encrypted){g73_pub_set('telegram_encrypted',$encrypted);}
 return 'Configurações salvas.';
}
add_action('admin_init',function(){
 if('POST'!==$_SERVER['REQUEST_METHOD']||!isset($_GET['page'])||'g73-publisher'!==$_GET['page']||!isset($_POST['g73_action'])||!current_user_can('manage_options')){return;}
 $action=sanitize_key(wp_unslash($_POST['g73_action']));if(!in_array($action,array('save','rotate'),true)){return;}check_admin_referer('g73_publisher_'.$action);
 if('rotate'===$action){$GLOBALS['g73_pub_one_time_token']=g73_pub_rotate();$GLOBALS['g73_pub_notice']='Token atualizado.';}else{$GLOBALS['g73_pub_notice']=g73_pub_save_settings();}
});
}
