<?php
/** Testes offline: G73_WP_HTML_API aponta para classes HTML API do WordPress 6.8.3. */
$api = getenv('G73_WP_HTML_API');
if (!$api || !is_file($api . '/class-wp-html-tag-processor.php')) {
    fwrite(STDERR, "Defina G73_WP_HTML_API com as classes HTML API reais do WordPress.\n"); exit(2);
}
foreach (array('class-wp-token-map.php','html5-named-character-references.php','class-wp-html-span.php',
    'class-wp-html-attribute-token.php','class-wp-html-text-replacement.php','class-wp-html-decoder.php',
    'class-wp-html-doctype-info.php','class-wp-html-tag-processor.php') as $file) { require_once $api . '/' . $file; }
define('ABSPATH', __DIR__ . '/');
$filters = array(); $cover_id = 410; $queried = 411; $admin = false; $feed = false; $singular = true;
$url = 'https://giro73.com.br/wp-content/uploads/2026/10/capa.webp';
$saved = ''; $attachment_caption = 'Crédito da biblioteca'; $checks = 0;
function add_filter($name, $callback, ...$args) { $GLOBALS['filters'][$name] = $callback; }
function add_shortcode(...$args) {}
function is_admin() { return $GLOBALS['admin']; }
function is_singular($type) { return $GLOBALS['singular']; }
function is_feed() { return $GLOBALS['feed']; }
function get_queried_object_id() { return $GLOBALS['queried']; }
function get_post_thumbnail_id($id) { return $GLOBALS['cover_id']; }
function wp_get_attachment_url($id) { return $GLOBALS['url']; }
function wp_get_attachment_metadata($id) { return array('sizes'=>array('large'=>array('file'=>'capa-1024x576.webp'))); }
function wp_get_attachment_caption($id) { return $GLOBALS['attachment_caption']; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function get_post_field($field, $id) { return $GLOBALS['saved']; }
function wp_kses_post($value) { return $value; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function wp_get_post_categories($id) { return array(); }
function home_url() { return 'https://giro73.com.br'; }
function url_to_postid($url) { return 0; }
function g73_ads_render($position) { return '<aside data-g73-position="in_article_1">Publicidade</aside>'; }
function g73_partner_unit($position) { return '<aside data-g73-position="in_article_2">Publicidade</aside>'; }
function __($text) { return $text; }
function _doing_it_wrong(...$args) { throw new RuntimeException('Uso inválido da HTML API'); }
require dirname(__DIR__) . '/single-editorial.php';
function check($condition, $label) {
    if (!$condition) { throw new RuntimeException($label); }
    ++$GLOBALS['checks'];
}
function figure($attributes = 'class="wp-image-410" src="https://giro73.com.br/wp-content/uploads/2026/10/capa.webp"', $class = 'g73-imported-image') {
    return '<figure class="'.$class.'"><img '.$attributes.'><figcaption>Foto: Fonte — crédito $1 &amp; equipe</figcaption></figure>';
}
function block($name, $html, $id = 411) {
    return $GLOBALS['filters'][$name]($html, array(), (object) array('context'=>array('postId'=>$id)));
}
$photo = figure();
$saved = '<p>Primeiro.</p>'.$photo.'<p>Segundo.</p><p>Terceiro.</p><p>Quarto.</p><p>Quinto.</p><p>Sexto.</p>';
$stored_hash = hash('sha256', $saved);
check(strpos(block('render_block_core/post-content',$saved),'g73-imported-image') !== false,'Sem capa renderizada, manter a foto do corpo');
$cover = block('render_block_core/post-featured-image','<figure class="g73-hero"><img src="capa.webp" alt="palavra-chave"></figure>');
check(strpos($cover,'crédito $1 &amp; equipe') !== false,'Crédito da figura preservado no topo sem interpolar $1');
check(substr_count($cover,'<figcaption') === 1,'Uma legenda no topo');
$rendered = block('render_block_core/post-content',$saved);
check(strpos($rendered,'g73-imported-image') === false,'Remover apenas a figura automática repetida');
check(substr_count($rendered,'in_article_1') === 1 && substr_count($rendered,'in_article_2') === 1,'Preservar inserções comerciais');
check(hash('sha256',$saved) === $stored_hash,'Conteúdo salvo permanece intacto para editor e Rank Math');
check(g73_single_unique_cover(figure('src="https://giro73.com.br/wp-content/uploads/2026/10/capa-1024x576.webp?v=2&amp;q=1"'),411)['content'] === '', 'Reconhecer variante registrada sem classe de mídia');
check(g73_single_unique_cover(figure('class="wp-image-410" src="https://cdn.example/foto.webp"'),411)['content'] === '', 'Reconhecer ID com CDN');
$other = figure('class="wp-image-999" src="https://giro73.com.br/wp-content/uploads/2026/10/outra.webp"');
check(g73_single_unique_cover($other,411)['content'] === $other,'Preservar outra foto e seu crédito');
$manual = figure('src="https://giro73.com.br/wp-content/uploads/2026/10/capa.webp"','foto-editorial');
check(g73_single_unique_cover($manual,411)['content'] === $manual,'Preservar figuras manuais sem marcador da importação');
$multiple = str_replace('</figure>','<img src="outra.webp"></figure>',$photo);
check(g73_single_unique_cover($multiple,411)['content'] === $multiple,'Preservar figura com mais de uma foto');
$cover_id = 0;
check(g73_single_unique_cover($photo,411)['content'] === $photo,'Sem mídia destacada, manter foto importada'); $cover_id = 410;
check(g73_single_unique_cover('<p>Texto sem figura.</p>',411)['content'] === '<p>Texto sem figura.</p>','Conteúdo sem marcador não é reescrito');
check(strpos(block('render_block_core/post-content',$saved,999),'g73-imported-image') !== false,'Não alterar outro post em bloco relacionado');
foreach (array('admin','feed') as $flag) {
    $GLOBALS[$flag] = true;
    check(block('render_block_core/post-content',$saved) === $saved,'Não alterar '.$flag);
    $GLOBALS[$flag] = false;
}
$singular = false;
check(block('render_block_core/post-content',$saved) === $saved,'Não alterar REST ou páginas não singulares'); $singular = true;
unset($GLOBALS['g73_single_cover_rendered']);
block('render_block_core/post-featured-image','');
check(strpos(block('render_block_core/post-content',$saved),'g73-imported-image') !== false,'Bloco de capa vazio não autoriza remoção');
$saved = '<p>Primeiro.</p>'.$photo;
block('render_block_core/post-featured-image','<figure><img src="capa.webp"></figure>');
check(strpos(block('render_block_core/post-content',$photo),'g73-imported-image') === false,'Remover duplicata mesmo sem parágrafos editoriais');
$url = 'https://giro73.com.br/wp-content/uploads/caf%C3%A9.webp';
check(g73_single_unique_cover(figure('src="https://giro73.com.br/wp-content/uploads/café.webp"'),411)['content'] === '', 'URLs codificadas e Unicode reconhecem a mesma mídia');
echo 'PASS '.$checks." verificações\n";
