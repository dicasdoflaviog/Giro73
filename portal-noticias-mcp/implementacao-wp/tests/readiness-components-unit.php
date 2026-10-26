<?php
/** Regressão offline: componentes públicos completos e prévia privada dos modelos. */
define('ABSPATH', __DIR__.'/');
$options=array('g73_whatsapp_group_url'=>'','g73_agenda_demo_ids'=>array(147));
$preview=false;$admin=false;$confirmed=false;$checks=0;
function add_action(...$args){}function add_filter(...$args){}function add_shortcode(...$args){}
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function is_preview(){return $GLOBALS['preview'];}
function current_user_can($cap){return $GLOBALS['admin'];}
function shortcode_atts($defaults,$atts){return array_merge($defaults,$atts);}
function home_url($path=''){return 'https://giro73.com.br'.$path;}
function wp_date($format,$stamp=null){return 'Y-m-d'===$format?'2026-10-26':'nov';}
function wp_timezone(){return new DateTimeZone('America/Bahia');}
function wp_list_pluck($rows,$field){return array_map(fn($r)=>$r->$field,$rows);}
function wp_unique_id($prefix){static $n=0;return $prefix.(++$n);}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_attr($s){return esc_html($s);}function esc_url($s){return esc_html($s);}
function wp_parse_url($u){return parse_url($u);}function absint($v){return abs((int)$v);}
function add_query_arg($key,$value,$url){return $url.'?'.$key.'='.urlencode($value);}
function get_post_type($id){return 'g73_event';}function get_post_status($id){return 147===$id?'draft':'publish';}
function get_post_meta($id,$key,$single=true){
 $row=array('g73_event_demo'=>147===$id,'g73_event_date'=>'2026-11-02','g73_event_city'=>'Teixeira de Freitas',
 'g73_event_category'=>'teatro_cultura','g73_event_location'=>'Local confirmado','g73_event_price'=>'Gratuito',
 'g73_event_url'=>147===$id?'':'https://example.org/evento','g73_event_ticket_url'=>'');return $row[$key]??'';
}
function get_the_title($id){return 147===$id?'MODELO — Demonstração':'Evento confirmado';}
function get_the_post_thumbnail(...$args){return '<img src="foto.webp" alt="Evento">';}
function get_posts($args){return $GLOBALS['confirmed']?array((object)array('ID'=>201)):array();}
class WP_Query {
 public $posts,$found_posts,$max_num_pages;
 function __construct($args){$this->posts=get_posts($args);$this->found_posts=count($this->posts);$this->max_num_pages=$this->found_posts?1:0;}
}
function check($condition,$label){if(!$condition){throw new RuntimeException($label);}++$GLOBALS['checks'];}
require dirname(__DIR__).'/engajamento-regional.php';
require dirname(__DIR__).'/agenda-eventos.php';
check(''===g73_eng_wa(),'Sem convite, não exibir botão inativo');
$options['g73_whatsapp_group_url']='https://example.org/convite';
check(''===g73_eng_wa(),'URL externa inválida não ativa o card');
$options['g73_whatsapp_group_url']='https://chat.whatsapp.com/GrupoTeste123';
$card=g73_eng_wa();check(strpos($card,'href="https://chat.whatsapp.com/GrupoTeste123"')!==false&&strpos($card,' disabled')===false,'Convite válido ativa o card');
check(''===g73_agenda_render(),'Sem evento confirmado, omitir vitrine vazia da Home');
$directory=g73_agenda_render(array('directory'=>'1'));
check(strpos($directory,'Nenhum evento nesta seleção')!==false&&strpos($directory,'g73-event-demo')===false,'Diretório vazio não anuncia eventos fictícios');
$admin=true;check(''===g73_agenda_render(),'Administrador também não vê modelos na Home pública');
$preview=true;$sample=g73_agenda_render();
check(strpos($sample,'Modelo • Não confirmado')!==false&&strpos($sample,' disabled')!==false,'Prévia administrativa mantém modelo explícito sem CTA ativo');
$admin=false;check(''===g73_agenda_render(),'Prévia sem permissão não divulga rascunhos');
$preview=false;$confirmed=true;
$real=g73_agenda_render();check(strpos($real,'Evento confirmado')!==false&&strpos($real,'href="https://example.org/evento"')!==false&&strpos($real,'g73-event-demo')===false,'Evento publicado e confirmado continua público');
echo 'PASS '.$checks." verificações\n";
