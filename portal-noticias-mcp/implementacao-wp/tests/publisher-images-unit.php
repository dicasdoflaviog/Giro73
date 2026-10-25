<?php
/** Testes offline do snippet completo com as APIs WordPress simuladas. */
class WP_Error {
 public $code;public $message;public $data;
 function __construct($code,$message='',$data=array()){$this->code=$code;$this->message=$message;$this->data=$data;}
 function get_error_code(){return $this->code;}
 function get_error_message(){return $this->message;}
}
class WP_REST_Response {
 public $data;public $status;
 function __construct($data,$status=200){$this->data=$data;$this->status=$status;}
 function get_data(){return $this->data;}
 function get_status(){return $this->status;}
}
class FakeRequest {
 public $data;
 function __construct($overrides=array()){$this->data=array_merge(array('title'=>'Notícia regional','content'=>'<p>Informação factual da fonte.</p>','excerpt'=>'Resumo factual.','category'=>'noticias-regiao','status'=>'draft','image_url'=>'https://prado.ba.gov.br/foto.jpeg','tags'=>array(),'meta'=>array('rank_math_title'=>'Título SEO','rank_math_description'=>'Descrição SEO','rank_math_focus_keyword'=>'notícia regional')),$overrides);}
 function get_body(){return json_encode($this->data);}
 function is_json_content_type(){return true;}
 function get_json_params(){return $this->data;}
 function get_header($name){return 'idempotency-key'===$name?'unit-case': '';}
}
$root=sys_get_temp_dir().'/g73-php-test-'.bin2hex(random_bytes(5));mkdir($root.'/wp-admin/includes',0777,true);mkdir($root.'/uploads');
file_put_contents($root.'/wp-admin/includes/file.php','<?php');file_put_contents($root.'/wp-admin/includes/image.php','<?php');
define('ABSPATH',$root.'/');
function fixture_png($width=200,$height=200){
 $chunk=function($type,$data){return pack('N',strlen($data)).$type.$data.pack('N',crc32($type.$data));};
 $pixels=str_repeat("\x00".str_repeat("\xff\xff\xff",$width),$height);
 return "\x89PNG\r\n\x1a\n".$chunk('IHDR',pack('NNCCCCC',$width,$height,8,2,0,0,0)).$chunk('IDAT',gzcompress($pixels)).$chunk('IEND','');
}
$png=fixture_png();
$scenario=array();$posts=array();$media=array();$meta=array();$options=array();$http=array();$actions=array();$serial=0;$editor_calls=0;$inserted_statuses=array();
class FakeDB {
 public $options='options';
 function prepare($sql,...$args){return $sql;}
 function query($sql){return 1;}
 function delete($table,$values){return 1;}
}
$wpdb=new FakeDB();
function add_action(...$args){}function add_filter(...$args){}
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function update_option($key,$value,$autoload=false){$GLOBALS['options'][$key]=$value;return true;}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function home_url($path=''){return 'https://giro73.com.br'.$path;}
function is_wp_error($value){return $value instanceof WP_Error;}
function attachment_url_to_postid($url){return str_starts_with($url,'https://giro73.com.br/')?($GLOBALS['scenario']['local_id']??0):0;}
function wp_http_validate_url($url){return filter_var($url,FILTER_VALIDATE_URL);}
function wp_tempnam($name){return tempnam(ABSPATH,$name);}
function wp_safe_remote_get($url,$args){
 $GLOBALS['http'][]=$args;
 if(($GLOBALS['scenario']['download']??'')==='error'){return new WP_Error('http_failed','Falha simulada');}
 file_put_contents($args['filename'],($GLOBALS['scenario']['download']??'')==='html'?'<html>Sem imagem</html>':$GLOBALS['png']);
 return array('response'=>array('code'=>200));
}
function wp_remote_retrieve_response_code($response){return $response['response']['code'];}
function wp_getimagesize($path){return @getimagesize($path);}
function wp_get_image_mime($path){$info=@getimagesize($path);return $info?$info['mime']:false;}
class FakeEditor {
 function resize(...$args){return ($GLOBALS['scenario']['editor']??'')==='resize_error'?new WP_Error('resize_failed'):true;}
 function set_quality($quality){return true;}
 function save($path,$mime){
  if(($GLOBALS['scenario']['editor']??'')==='budget'){file_put_contents($path,str_repeat('x',60001));return array('path'=>$path);}
  return new WP_Error('save_failed');
 }
}
function wp_get_image_editor($path){
 $GLOBALS['editor_calls']++;
 $mode=$GLOBALS['scenario']['editor']??'error';
 if($mode==='throw'){throw new RuntimeException('GD/Imagick indisponível');}
 return $mode==='error'?new WP_Error('no_editor'):new FakeEditor();
}
function wp_upload_dir(){return array('path'=>ABSPATH.'uploads','error'=>false);}
function trailingslashit($path){return rtrim($path,'/').'/';}
function wp_unique_filename($path,$name){return $name;}
function wp_generate_uuid4(){return sprintf('00000000-0000-4000-8000-%012d',++$GLOBALS['serial']);}
function esc_url($url){return htmlspecialchars($url,ENT_QUOTES);}
function esc_attr($value){return htmlspecialchars($value,ENT_QUOTES);}
function esc_html($value){return htmlspecialchars($value,ENT_QUOTES);}
function wp_slash($value){return $value;}
function wp_insert_attachment($data,$file,$parent=0,$error=false){
 if(!empty($GLOBALS['scenario']['register_error'])){return new WP_Error('register_failed');}
 $id=500+count($GLOBALS['media']);$GLOBALS['media'][$id]=array('data'=>$data,'file'=>$file);return $id;
}
function update_post_meta($id,$key,$value){$GLOBALS['meta'][$id][$key]=$value;return true;}
function get_post_meta($id,$key,$single=false){return $GLOBALS['meta'][$id][$key]??'';}
function delete_post_meta($id,$key){unset($GLOBALS['meta'][$id][$key]);return true;}
function get_attached_file($id){return $GLOBALS['media'][$id]['file']??false;}
function update_attached_file($id,$path){$GLOBALS['media'][$id]['file']=$path;}
function _wp_relative_upload_path($path){return 'uploads/'.basename($path);}
function wp_generate_attachment_metadata($id,$path){
 $mode=$GLOBALS['scenario']['metadata']??'ok';
 if($mode==='throw'){throw new RuntimeException('Falha de miniaturas');}
 if($mode==='empty'){return false;}
 if($mode==='error'){return new WP_Error('metadata_failed');}
 $size=wp_getimagesize($path);return array('width'=>$size[0],'height'=>$size[1],'file'=>_wp_relative_upload_path($path),'sizes'=>array());
}
function wp_update_attachment_metadata($id,$data){update_post_meta($id,'_wp_attachment_metadata',$data);}
function wp_get_attachment_metadata($id){return get_post_meta($id,'_wp_attachment_metadata',true);}
function wp_delete_file($path){if(is_file($path)){unlink($path);}}
function wp_delete_attachment($id,$force=false){$path=get_attached_file($id);if($path){wp_delete_file($path);}unset($GLOBALS['media'][$id],$GLOBALS['meta'][$id]);}
function get_post_mime_type($id){return $GLOBALS['media'][$id]['data']['post_mime_type']??'';}
function wp_get_attachment_url($id){return 'https://giro73.com.br/uploads/'.basename(get_attached_file($id));}
function get_post_thumbnail_id($id){return !empty($GLOBALS['scenario']['thumbnail_error'])?0:(int)get_post_meta($id,'_thumbnail_id',true);}
function set_post_thumbnail($id,$attachment){if(!empty($GLOBALS['scenario']['thumbnail_error'])){return false;}update_post_meta($id,'_thumbnail_id',$attachment);return true;}
function get_post_status($id){return $GLOBALS['posts'][$id]['post_status'];}
function get_permalink($id){return 'https://giro73.com.br/?p='.$id;}
function sanitize_text_field($text){return trim(strip_tags($text));}
function wp_kses($text,$allowed,$protocols){return $text;}
function wpautop($text){return $text;}
function wp_strip_all_tags($text){return strip_tags($text);}
function wp_trim_words($text,$count,$more=''){return $text;}
function wp_html_excerpt($text,$count,$more=''){return substr($text,0,$count);}
function sanitize_title($text){return strtolower($text);}
function get_term_by($field,$value,$taxonomy){return (object)array('term_id'=>1);}
function get_term($id,$taxonomy){return (object)array('term_id'=>$id);}
function get_user_by($field,$id){return (object)array('ID'=>1);}
function user_can($user,$capability){return true;}
function wp_json_encode($value){return json_encode($value);}
function get_posts($args){$found=array();foreach($GLOBALS['posts'] as $id=>$post){if(get_post_meta($id,$args['meta_key'],true)===$args['meta_value']){$found[]=$id;}}return $found;}
function get_transient($key){return false;}
function set_transient($key,$value,$duration){return true;}
function wp_cache_delete(...$args){}function wp_cache_set(...$args){}
function wp_insert_post($post,$error=false){$GLOBALS['inserted_statuses'][]=$post['post_status'];$id=100+count($GLOBALS['posts']);$GLOBALS['posts'][$id]=$post;$GLOBALS['meta'][$id]=$post['meta_input'];return $id;}
function wp_set_post_terms(...$args){return array();}
function wp_update_post($post){if(isset($GLOBALS['posts'][$post['ID']])){$GLOBALS['posts'][$post['ID']]=array_merge($GLOBALS['posts'][$post['ID']],$post);}return $post['ID'];}
function wp_delete_post($id,$force=false){unset($GLOBALS['posts'][$id],$GLOBALS['meta'][$id]);}
function clean_post_cache($id){}
function do_action(...$args){$GLOBALS['actions'][]=$args;}

require dirname(__DIR__).'/motor-publicacao.php';
function expect($condition,$message){if(!$condition){throw new RuntimeException($message);}}
function reset_case($changes=array()){
 global $scenario,$posts,$media,$meta,$options,$http,$actions,$editor_calls,$inserted_statuses;
 foreach(glob(ABSPATH.'uploads/*') as $path){unlink($path);}
 $scenario=$changes;$posts=array();$media=array();$meta=array();$http=array();$actions=array();$editor_calls=0;$inserted_statuses=array();
 $options=array('g73_publisher_image_hosts'=>array('prado.ba.gov.br','camaraprado.ba.gov.br','giro73.com.br'));
}
function received($request=null){
 $response=g73_pub_publish($request?:new FakeRequest());
 expect($response instanceof WP_REST_Response,'A imagem impediu criar a matéria.');
 expect($response->get_status()===201,'Criação não retornou 201.');
 expect($response->data['seo']['title']==='Título SEO','SEO recebido foi perdido.');
 expect($response->data['seo']['description']==='Descrição SEO','Descrição SEO foi perdida.');
 expect(count($GLOBALS['posts'])===1,'Matéria ausente ou duplicada.');
 return $response->data;
}
$tests=array(
 'GD ausente preserva imagem original pequena'=>function(){
  reset_case();$data=received();expect($data['featured_media']>0,'Original não anexado.');
  $id=$data['featured_media'];expect(get_post_mime_type($id)==='image/png','MIME original não preservado.');
  expect($data['image']['width']===200&&$data['image']['height']===200,'Dimensões pequenas rejeitadas.');
  expect($GLOBALS['http'][0]['redirection']===5&&$GLOBALS['http'][0]['reject_unsafe_urls']&&$GLOBALS['http'][0]['sslverify'],'Transporte seguro/redirect incorreto.');
 },
 'Exceção do editor não bloqueia o post'=>function(){reset_case(array('editor'=>'throw'));$data=received();expect($data['featured_media']>0,'Exceção perdeu original.');},
 'Erro de resize preserva original'=>function(){reset_case(array('editor'=>'resize_error'));$data=received();expect($data['featured_media']>0,'Resize abortou.');},
 'Erro de WebP preserva original'=>function(){reset_case(array('editor'=>'save_error'));$data=received();expect($data['featured_media']>0,'WebP abortou.');},
 'Orçamento de 60 KB não rejeita nem deixa sobras'=>function(){reset_case(array('editor'=>'budget'));$data=received();expect($data['featured_media']>0,'Orçamento abortou.');expect(count(glob(ABSPATH.'uploads/*.webp'))===0,'WebP incompleto ficou órfão.');},
 'Metadata vazia registra dimensões mínimas'=>function(){reset_case(array('metadata'=>'empty'));$data=received();expect($data['featured_media']>0&&$data['image']['width']===200,'Metadata vazia bloqueou original.');},
 'Exceção de metadata não bloqueia original'=>function(){reset_case(array('metadata'=>'throw'));$data=received();expect($data['featured_media']>0,'Exceção de metadata abortou.');},
 'WP_Error de metadata não bloqueia original'=>function(){reset_case(array('metadata'=>'error'));$data=received();expect($data['featured_media']>0,'WP_Error de metadata abortou.');},
 'Falha de download cria rascunho sem capa'=>function(){reset_case(array('download'=>'error'));$data=received();expect($data['featured_media']===0&&$data['status']==='draft','Download impediu draft.');expect(str_contains(implode(' ',$data['warnings']),'g73_image_download'),'Aviso de download ausente.');},
 'Host inseguro é ignorado sem download'=>function(){reset_case();$data=received(new FakeRequest(array('image_url'=>'http://127.0.0.1/imagem.jpg')));expect($data['featured_media']===0&&count($GLOBALS['http'])===0,'Host inseguro foi acessado.');},
 'HTML não é registrado como imagem'=>function(){reset_case(array('download'=>'html'));$data=received();expect($data['featured_media']===0&&count($GLOBALS['media'])===0,'HTML virou mídia.');},
 'Falha ao registrar mídia preserva texto e limpa arquivos'=>function(){reset_case(array('register_error'=>true));$data=received();expect($data['featured_media']===0&&count(glob(ABSPATH.'uploads/*'))===0,'Mídia com erro ficou órfã.');},
 'Publish sem capa permanece em rascunho'=>function(){reset_case();$data=received(new FakeRequest(array('status'=>'publish','image_url'=>'')));expect($data['status']==='draft','Matéria sem imagem foi publicada.');},
 'Replay mantém ID e avisos sem duplicar'=>function(){reset_case(array('download'=>'error'));$request=new FakeRequest();$data=received($request);$replay=g73_pub_publish($request);expect($replay->get_status()===200&&$replay->data['id']===$data['id']&&count($GLOBALS['posts'])===1,'Replay duplicou.');expect($replay->data['warnings']===$data['warnings'],'Avisos não persistiram.');},
 'Falha de associação da capa preserva matéria'=>function(){reset_case(array('thumbnail_error'=>true));$data=received();expect($data['featured_media']===0,'Thumbnail inválida não foi retirada.');},
 'Otimização pode ser desativada'=>function(){reset_case();$GLOBALS['options']['g73_publisher_optimize_images']=false;$data=received();expect($data['featured_media']>0&&$GLOBALS['editor_calls']===0,'Resize opcional ainda executou.');},
 'Validação do texto continua obrigatória'=>function(){reset_case();$result=g73_pub_publish(new FakeRequest(array('title'=>'')));expect(is_wp_error($result)&&count($GLOBALS['posts'])===0,'Texto inválido foi aceito.');},
 'Host público fora da antiga lista é aceito'=>function(){reset_case();$data=received(new FakeRequest(array('image_url'=>'https://outra-prefeitura.example/Handler.ashx?f=i&query=123')));expect($data['featured_media']>0&&count($GLOBALS['http'])===1,'Host público foi bloqueado por whitelist.');},
 'ALT usa exatamente a palavra-chave Rank Math'=>function(){reset_case();$keyword='crédito & renda "segura"';$request=new FakeRequest(array('meta'=>array('rank_math_title'=>'Título SEO','rank_math_description'=>'Descrição SEO','rank_math_focus_keyword'=>$keyword)));$data=received($request);expect(get_post_meta($data['featured_media'],'_wp_attachment_image_alt',true)===$keyword,'ALT divergiu da palavra-chave.');},
 'Figura local depois do primeiro parágrafo sem duplicação'=>function(){reset_case();$request=new FakeRequest(array('content'=>'<p>Primeiro parágrafo.</p><figure class="g73-imported-image"><img src="https://origem.example/foto.jpg" alt="antigo" /></figure><p>Segundo parágrafo.</p>'));$data=received($request);$content=$GLOBALS['posts'][$data['id']]['post_content'];expect(substr_count($content,'<figure')===1,'Figura foi duplicada.');expect(strpos($content,'</p>')<strpos($content,'<figure')&&strpos($content,'<figure')<strpos($content,'Segundo'),'Figura fora da posição.');expect(str_contains($content,'https://giro73.com.br/uploads/')&&str_contains($content,'alt="notícia regional"'),'Imagem local/ALT ausente.');},
 'Imagem menor que 200 pixels não vira capa'=>function(){reset_case();$normal=$GLOBALS['png'];$GLOBALS['png']=fixture_png(199,199);$data=received();$GLOBALS['png']=$normal;expect($data['featured_media']===0&&$data['status']==='draft','Ícone pequeno foi anexado.');},
 'Publish com foto válida mantém publicação'=>function(){reset_case();$data=received(new FakeRequest(array('status'=>'publish')));expect($data['status']==='publish'&&$data['featured_media']>0,'Foto válida não permitiu publicar.');expect($GLOBALS['inserted_statuses']===array('draft'),'Houve publicação antes da associação da capa.');},
 'ALT de mídia compartilhada não é alterado em outras matérias'=>function(){reset_case(array('local_id'=>500));$file=ABSPATH.'uploads/original.png';file_put_contents($file,$GLOBALS['png']);$GLOBALS['media'][500]=array('data'=>array('post_mime_type'=>'image/png'),'file'=>$file);update_post_meta(500,'_wp_attachment_metadata',array('width'=>200,'height'=>200));update_post_meta(500,'_wp_attachment_image_alt','ALT da matéria anterior');$data=received(new FakeRequest(array('image_url'=>'https://giro73.com.br/uploads/original.png')));expect($data['featured_media']!==500,'Mídia compartilhada foi reutilizada com ALT diferente.');expect(get_post_meta(500,'_wp_attachment_image_alt',true)==='ALT da matéria anterior','ALT antigo foi alterado.');expect(get_post_meta($data['featured_media'],'_wp_attachment_image_alt',true)==='notícia regional','Nova capa não recebeu a palavra-chave.');},
 'Publish com download indisponível preserva draft e retira figura remota'=>function(){reset_case(array('download'=>'error'));$data=received(new FakeRequest(array('status'=>'publish','content'=>'<p>Notícia.</p><figure class="g73-imported-image"><img src="https://origem.example/foto.jpg" /></figure>')));expect($data['status']==='draft'&&$data['featured_media']===0,'Publicou sem capa.');expect(!str_contains($GLOBALS['posts'][$data['id']]['post_content'],'<figure'),'Figura remota inválida permaneceu no texto.');},
);
foreach($tests as $name=>$test){try{$test();echo 'OK '.$name.PHP_EOL;}catch(Throwable $error){fwrite(STDERR,'FAIL '.$name.': '.$error->getMessage().PHP_EOL);exit(1);}}
echo 'PASS '.count($tests).' verificações PHP offline'.PHP_EOL;
