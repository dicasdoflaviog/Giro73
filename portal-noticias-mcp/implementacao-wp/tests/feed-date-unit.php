<?php
/** Datas editoriais: publicação original, contexto de Query Loop e fuso. */
define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60); define('HOUR_IN_SECONDS', 3600); define('DAY_IN_SECONDS', 86400);
function add_action(...$args) {} function add_filter(...$args) {} function add_shortcode(...$args) {}
function is_admin() { return $GLOBALS['admin'] ?? false; }
function get_the_ID() { return 99; }
function get_post_type($id) { return 'post'; }
function get_post_time($format, $gmt, $id) { $GLOBALS['time_request'] = array($format, $gmt, $id); return $GLOBALS['published'] ?? 1793029260; }
function get_post_meta($id, $key, $single) { return 18; }
function get_the_category($id) { return array((object) array('term_id' => 1, 'name' => 'Notícias & Região'), (object) array('term_id' => 18, 'name' => 'Política')); }
function get_category_link($id) { return 'https://giro73.com.br/category/' . $id . '/'; }
function is_wp_error($value) { return false; }
function wp_date($format, $stamp, $zone) { return (new DateTimeImmutable('@' . $stamp))->setTimezone($zone)->format($format); }
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
function esc_attr($s) { return esc_html($s); } function esc_url($s) { return esc_html($s); }
function wp_specialchars_decode($s, $flags) { return html_entity_decode($s, $flags); }
require dirname(__DIR__) . '/portal-estrutura.php';
$checks = 0;
function check($condition, $label) { if (!$condition) { throw new RuntimeException($label); } ++$GLOBALS['checks']; }
check(g73_portal_feed_age(1000, 1059) === 'Agora' && g73_portal_feed_age(1000, 1060) === 'Há 1 minuto', 'Limite de minuto');
check(g73_portal_feed_age(1000, 4600) === 'Há 1 hora' && g73_portal_feed_age(1000, 8200) === 'Há 2 horas', 'Horas em português');
check(g73_portal_feed_age(1000, 87400) === 'Há 1 dia' && g73_portal_feed_age(1000, 1000 + 30 * 86400) === 'Há 1 mês', 'Dias e meses');
check(g73_portal_feed_age(2000, 1000) === 'Agendada', 'Data futura não vira publicação passada');
$GLOBALS['published'] = (new DateTimeImmutable('2026-10-26T15:41:00Z'))->getTimestamp();
$parsed = array('attrs' => array('className' => 'extra g73-news-date'));
$instance = (object) array('context' => array('postId' => 409));
$html = g73_portal_feed_date('original', $parsed, $instance);
check(str_contains($html, '26/10/2026 às 12h41') && str_contains($html, '2026-10-26T12:41:00-03:00'), 'Horário exato da Bahia, com offset');
check($GLOBALS['time_request'] === array('U', true, 409), 'Publicação do post no Query Loop, sem usar atualização ou outro post global');
check(str_contains($html, '— Em <a href="https://giro73.com.br/category/18/">Política</a>'), 'Editoria principal atribuída');
check(g73_portal_feed_date('original', array('attrs' => array('className' => 'g73-news-date-extra')), $instance) === 'original', 'Outros blocos preservados');
$GLOBALS['admin'] = true; check(g73_portal_feed_date('original', $parsed, $instance) === 'original', 'Editor administrativo preservado');
if (getenv('G73_DATE_JS')) { file_put_contents(getenv('G73_DATE_JS'), g73_portal_feed_clock_js()); }
echo 'PASS ' . $checks . " verificações de datas\n";
