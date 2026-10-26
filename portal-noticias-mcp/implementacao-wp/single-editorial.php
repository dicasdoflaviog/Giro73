<?php
/** Giro73: leitura editorial, link de compartilhamento e inserções no servidor. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_single_paragraph_ends')) {
// Identidade da mídia sem parâmetros de versão; aceita variantes reais registradas no WP.
function g73_single_image_key($url) {
    if (!is_string($url) || '' === $url) { return ''; }
    $parts = wp_parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    if (!is_array($parts) || empty($parts['path'])) { return ''; }
    return strtolower(isset($parts['host']) ? $parts['host'] : '') . rawurldecode($parts['path']);
}
// Só retira figuras automáticas que repetem a capa; nunca altera o post_content salvo.
function g73_single_unique_cover($content, $post_id) {
    $result = array('content' => $content, 'caption' => '');
    if (false === strpos($content, 'g73-imported-image') || !class_exists('WP_HTML_Tag_Processor')) { return $result; }
    $cover_id = (int) get_post_thumbnail_id($post_id);
    if (!$cover_id) { return $result; }
    $url = (string) wp_get_attachment_url($cover_id);
    $urls = array(g73_single_image_key($url));
    $base = substr($url, 0, strrpos($url, '/') + 1);
    $meta = wp_get_attachment_metadata($cover_id);
    if (is_array($meta)) {
        foreach (isset($meta['sizes']) ? $meta['sizes'] : array() as $size) {
            if (!empty($size['file'])) { $urls[] = g73_single_image_key($base . $size['file']); }
        }
        if (!empty($meta['original_image'])) { $urls[] = g73_single_image_key($base . $meta['original_image']); }
    }
    $urls = array_filter($urls);
    $result['content'] = preg_replace_callback('~<figure\b[^>]*>[\s\S]*?</figure\s*>~i', function ($match) use ($cover_id, $urls, &$result) {
        $tags = new WP_HTML_Tag_Processor($match[0]);
        if (!$tags->next_tag('FIGURE') || !$tags->has_class('g73-imported-image')) { return $match[0]; }
        $images = 0; $same = false;
        while ($tags->next_tag('IMG')) {
            ++$images;
            if ($tags->has_class('wp-image-' . $cover_id)
                || in_array(g73_single_image_key($tags->get_attribute('src')), $urls, true)) { $same = true; }
        }
        // Uma figura com várias fotos deve ser preservada integralmente.
        if (1 !== $images || !$same) { return $match[0]; }
        if ('' === $result['caption'] && preg_match('~<figcaption\b[^>]*>([\s\S]*?)</figcaption\s*>~i', $match[0], $caption)) {
            $result['caption'] = $caption[1];
        }
        return '';
    }, $content);
    return $result;
}
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
    $post_id = (int) $instance->context['postId'];
    if (!empty($GLOBALS['g73_single_cover_rendered'][$post_id])) {
        $unique = g73_single_unique_cover($content, $post_id);
        $content = $unique['content'];
    }
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
// Crédito da foto no servidor; matérias anteriores já possuem o crédito no início do conteúdo.
add_filter('render_block_core/post-featured-image', function ($html, $block, $instance) {
    if (is_admin() || !is_singular('post') || !isset($instance->context['postId'])
        || (int)$instance->context['postId'] !== (int)get_queried_object_id()) { return $html; }
    $id = (int)$instance->context['postId'];
    if (false === stripos($html, '<img')) { return $html; }
    $GLOBALS['g73_single_cover_rendered'][$id] = true;
    $unique = g73_single_unique_cover(get_post_field('post_content', $id), $id);
    if (false !== strpos($unique['content'], 'g73-photo-credit')) { return $html; }
    $caption = '' !== $unique['caption'] ? $unique['caption'] : wp_get_attachment_caption(get_post_thumbnail_id($id));
    if (!$caption || false !== strpos($html, '<figcaption')) { return $html; }
    return preg_replace_callback('~</figure>\s*$~', function () use ($caption) {
        return '<figcaption class="g73-photo-credit">' . wp_kses_post($caption) . '</figcaption></figure>';
    }, $html, 1);
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
