<?php
/** Giro73: leitura editorial, link de compartilhamento e inserções no servidor. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_single_paragraph_ends')) {
// Posições dos parágrafos editoriais completos, preservando o HTML original.
function g73_single_paragraph_ends($content) {
    $pattern = '~<!--[\s\S]*?-->|<(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~';
    preg_match_all($pattern, $content, $tokens, PREG_OFFSET_CAPTURE);
    $stack = array(); $start = null; $ends = array();
    $blocked = array('blockquote', 'figure', 'ul', 'ol', 'table', 'aside', 'script', 'style', 'pre', 'nav', 'footer');
    $void = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr');
    foreach ($tokens[0] as $token) {
        if (!preg_match('~^<\s*(/?)\s*([a-z][a-z0-9:-]*)\b~i', $token[0], $tag)) { continue; }
        $name = strtolower($tag[2]); $end = $token[1] + strlen($token[0]);
        if ($tag[1]) {
            if ('p' === $name && null !== $start) {
                $text = trim(str_replace("\xc2\xa0", ' ', html_entity_decode(wp_strip_all_tags(substr($content, $start, $token[1] - $start)), ENT_QUOTES, 'UTF-8')));
                if ('' !== $text) { $ends[] = $end; }
                $start = null;
            }
            for ($i = count($stack) - 1; $i >= 0; --$i) {
                if ($stack[$i] === $name) { $stack = array_slice($stack, 0, $i); break; }
            }
        } else {
            if ('p' === $name) {
                $start = !array_intersect($stack, $blocked) && !preg_match('~g73-photo-credit|g73-meta~i', $token[0]) ? $end : null;
            }
            if (!in_array($name, $void, true) && !preg_match('~/\s*>$~', $token[0])) { $stack[] = $name; }
        }
    }
    return $ends;
}
function g73_single_related_id($post_id) {
    $categories = wp_get_post_categories($post_id);
    if ($categories) {
        $related = get_posts(array('post_type' => 'post', 'post_status' => 'publish',
            'has_password' => false, 'posts_per_page' => 1, 'post__not_in' => array($post_id),
            'category__in' => $categories, 'fields' => 'ids', 'no_found_rows' => true,
            'update_post_meta_cache' => false, 'update_post_term_cache' => false));
        if ($related) { return (int) $related[0]; }
    }
    // Em editorias com um único post, aproveitar uma ligação editorial já existente.
    $content = get_post_field('post_content', $post_id);
    preg_match_all('~<a\b[^>]*\bhref\s*=\s*["\x27]([^"\x27]+)["\x27]~i', $content, $links);
    $host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
    foreach ($links[1] as $url) {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
        if ('/' === substr($url, 0, 1) && '//' !== substr($url, 0, 2)) { $url = home_url($url); }
        if (strtolower((string) wp_parse_url($url, PHP_URL_HOST)) !== $host) { continue; }
        $id = url_to_postid($url);
        if ($id && $id !== $post_id && 'post' === get_post_type($id)
            && 'publish' === get_post_status($id) && '' === (string) get_post_field('post_password', $id)) { return $id; }
    }
    return 0;
}
function g73_single_read_also($post_id) {
    $id = g73_single_related_id($post_id);
    if (!$id) { return ''; }
    return '<aside class="g73-read-also" data-g73-read-also aria-label="Leia também"><span class="g73-read-also-label">Leia também</span><a href="'
        . esc_url(get_permalink($id)) . '">' . esc_html(get_the_title($id)) . '</a></aside>';
}
add_filter('render_block_core/post-content', function ($content, $parsed, $instance) {
    if (is_admin() || !is_singular('post') || is_feed() || !isset($instance->context['postId'])
        || (int) $instance->context['postId'] !== (int) get_queried_object_id()) { return $content; }
    $ends = g73_single_paragraph_ends($content);
    if (!$ends) { return $content; }
    $insertions = array();
    if (false === strpos($content, 'data-g73-position="in_article_1"') && function_exists('g73_ads_render')) {
        $offset = $ends[min(2, count($ends) - 1)];
        $insertions[$offset] = g73_ads_render('article');
    }
    if (count($ends) >= 6 && false === strpos($content, 'data-g73-position="in_article_2"') && function_exists('g73_partner_unit')) {
        $offset = $ends[5];
        $insertions[$offset] = (isset($insertions[$offset]) ? $insertions[$offset] : '') . g73_partner_unit('in_article_2');
    }
    if (false === strpos($content, 'data-g73-read-also')) {
        $related = g73_single_read_also((int) $instance->context['postId']);
        $offset = count($ends) >= 6 ? $ends[5] : $ends[max(0, (int) ceil(count($ends) / 2) - 1)];
        $insertions[$offset] = (isset($insertions[$offset]) ? $insertions[$offset] : '') . $related;
    }
    krsort($insertions, SORT_NUMERIC);
    foreach ($insertions as $offset => $html) {
        $content = substr($content, 0, $offset) . $html . substr($content, $offset);
    }
    return $content;
}, 25, 3);
add_shortcode('giro73_compartilhar_whatsapp', function () {
    $id = get_queried_object_id();
    if (!$id || 'post' !== get_post_type($id)) { return ''; }
    $text = wp_specialchars_decode(get_the_title($id), ENT_QUOTES) . ' - Leia em Giro73: ' . get_permalink($id);
    return '<a class="g73-share-whatsapp" data-g73-whatsapp href="' . esc_url('https://api.whatsapp.com/send?text=' . rawurlencode($text))
        . '" target="_blank" rel="noopener noreferrer">Compartilhar no WhatsApp</a>';
});
// WhatsApp usa Open Graph: título editorial completo, sem alterar o título SEO.
add_filter('rank_math/opengraph/facebook/og_title', function ($title) {
    return is_singular('post')
        ? wp_specialchars_decode(wp_strip_all_tags(get_the_title(get_queried_object_id())), ENT_QUOTES) : $title;
});
add_filter('render_block_core/shortcode', function ($html, $block) {
    return '[giro73_compartilhar_whatsapp]' === trim(isset($block['innerHTML']) ? $block['innerHTML'] : '')
        ? do_shortcode('[giro73_compartilhar_whatsapp]') : $html;
}, 10, 2);
}
