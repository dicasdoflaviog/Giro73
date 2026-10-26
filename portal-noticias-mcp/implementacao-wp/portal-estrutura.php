<?php
/** Giro73: cabeçalhos de arquivo, Schema e ads.txt nativos, sem assets externos. */
if (!defined('ABSPATH')) { return; }
// URL de cópia removida: encaminhar para a cobertura preservada do mesmo acontecimento.
add_action('template_redirect', function () {
    if (!is_404() || !in_array(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET', array('GET', 'HEAD'), true)) { return; }
    $path = wp_parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
    $redirects = array(
        '/prefeito-marcelo-belitardo-acompanha-recuperacao-apos-vendaval-em-teixeira-de-freitas' => '/prefeito-de-teixeira-de-freitas-mobiliza-equipes-para-recuperacao-apos-vendaval/'
    );
    $path = is_string($path) ? rtrim($path, '/') : '';
    if (isset($redirects[$path])) { wp_safe_redirect(home_url($redirects[$path]), 301, 'Giro73'); exit; }
}, 1);
if (!function_exists('g73_portal_archive_heading')) {
function g73_portal_editorial_bio() {
    return 'Equipe de jornalismo do Giro73 focada na cobertura diária de Teixeira de Freitas, Extremo Sul da Bahia e notícias nacionais com impacto no bolso do cidadão.';
}
function g73_portal_archive_heading() {
    $color = '#2C1067'; $title = 'Notícias do Giro73'; $description = '';
    if (is_category()) {
        $term = get_queried_object();
        $colors = array('noticias-regiao' => '#FF006E', 'politica' => '#B91C1C', 'dinheiro-renda-extra' => '#3A86FF',
            'tecnologia-inovacao' => '#2C1067', 'esportes' => '#D97706', 'famosos-entretenimento' => '#FB5607');
        $title = 'politica' === $term->slug ? 'Política & Poder' : wp_specialchars_decode($term->name, ENT_QUOTES);
        $description = wp_strip_all_tags($term->description);
        $color = isset($colors[$term->slug]) ? $colors[$term->slug] : $color;
    } elseif (is_search()) {
        global $wp_query;
        $title = 'Resultados para: ' . get_search_query(false);
        $count = (int) $wp_query->found_posts;
        $description = $count . (1 === $count ? ' notícia encontrada.' : ' notícias encontradas.');
    } elseif (is_author()) {
        $author = get_queried_object();
        $title = 1 === (int) $author->ID ? 'Redação Giro73' : $author->display_name;
        $description = $author->description;
    } else {
        $title = wp_strip_all_tags(get_the_archive_title());
        $description = wp_strip_all_tags(get_the_archive_description());
    }
    $html = '<header class="g73-archive-heading" style="--g73-section:' . esc_attr($color) . '"><h1>' . esc_html($title) . '</h1>';
    if ($description) { $html .= '<p>' . esc_html(wp_specialchars_decode($description, ENT_QUOTES)) . '</p>'; }
    if (is_author() && 1 === (int) get_queried_object_id()) {
        $html .= '<p class="g73-archive-expediente">Responsável editorial: ' . esc_html(get_the_author_meta('display_name', 1))
            . ' · Teixeira de Freitas, BA · <a href="' . esc_url(home_url('/politica-editorial/')) . '">Política Editorial</a></p>';
    }
    return $html . '</header>';
}
add_shortcode('giro73_cabecalho_arquivo', 'g73_portal_archive_heading');
add_filter('render_block_core/shortcode', function ($html, $block) {
    return '[giro73_cabecalho_arquivo]' === trim(isset($block['innerHTML']) ? $block['innerHTML'] : '')
        ? do_shortcode('[giro73_cabecalho_arquivo]') : $html;
}, 10, 2);
// A busca do portal lista notícias, sem misturar páginas institucionais.
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search()) { $query->set('post_type', 'post'); }
});
// Complementa o único grafo do Rank Math, sem duplicar JSON-LD no head.
add_filter('rank_math/json_ld', function ($data) {
    $nodes =& $data;
    if (isset($data['@graph']) && is_array($data['@graph'])) { $nodes =& $data['@graph']; }
    $org_id = home_url('/#organization'); $found_org = false;
    $logo = wp_get_attachment_image_src((int) get_option('site_icon'), 'full');
    foreach ($nodes as &$node) {
        if (!is_array($node)) { continue; }
        $types = isset($node['@type']) ? (array) $node['@type'] : array();
        if (isset($node['@id']) && $org_id === $node['@id']) {
            $found_org = true; $node['@type'] = 'Organization'; $node['name'] = 'Giro73'; $node['url'] = home_url();
            $node['description'] = g73_portal_editorial_bio();
            if ($logo) { $node['logo'] = array('@type' => 'ImageObject', '@id' => home_url('/#logo'),
                'url' => $logo[0], 'contentUrl' => $logo[0], 'width' => $logo[1], 'height' => $logo[2], 'caption' => 'Giro73'); }
        }
    }
    unset($node);
    if (!$found_org) {
        $organization = array('@type' => 'Organization', '@id' => $org_id, 'name' => 'Giro73', 'url' => home_url());
        if ($logo) { $organization['logo'] = array('@type' => 'ImageObject', 'url' => $logo[0], 'width' => $logo[1], 'height' => $logo[2]); }
        $nodes[] = $organization;
    }
    if (!is_singular('post')) { return $data; }
    $post_id = get_queried_object_id(); $post = get_post($post_id); $author_id = (int) $post->post_author;
    $author_url = get_author_posts_url($author_id);
    $title = wp_specialchars_decode(wp_strip_all_tags(get_the_title($post_id)), ENT_QUOTES);
    $description = wp_specialchars_decode(wp_strip_all_tags(get_the_excerpt($post_id)), ENT_QUOTES);
    $sections = wp_get_post_categories($post_id, array('fields' => 'names'));
    $section = is_wp_error($sections) ? '' : wp_specialchars_decode(implode(', ', $sections), ENT_QUOTES);
    $found_article = false;
    $g73_article_type = get_post_meta($post_id, 'g73_is_opinion', true) ? 'OpinionNewsArticle' : 'NewsArticle';
    foreach ($nodes as &$node) {
        if (!is_array($node)) { continue; }
        $types = isset($node['@type']) ? (array) $node['@type'] : array();
        if (in_array('Person', $types, true) && isset($node['@id']) && $author_url === $node['@id']) {
            $node['name'] = get_the_author_meta('display_name', $author_id);
            $node['url'] = $author_url; $node['description'] = get_the_author_meta('description', $author_id);
            $node['sameAs'] = array(home_url());
        }
        if (array_intersect($types, array('BlogPosting', 'Article', 'NewsArticle', 'OpinionNewsArticle'))) {
            $found_article = true; $node['@type'] = $g73_article_type; $node['headline'] = $title; $node['name'] = $title;
            $node['description'] = $description; $node['articleSection'] = $section;
            $node['publisher'] = array('@id' => $org_id);
            $node['author'] = array('@type' => 'Person', '@id' => $author_url,
                'name' => get_the_author_meta('display_name', $author_id), 'url' => $author_url);
        }
        if (in_array('WebPage', $types, true) && isset($node['url']) && get_permalink($post_id) === $node['url']) { $node['name'] = $title; }
    }
    unset($node);
    if (!$found_article) {
        $article = array('@type' => $g73_article_type, '@id' => get_permalink($post_id) . '#richSnippet',
            'headline' => $title, 'description' => $description, 'articleSection' => $section, 'inLanguage' => 'pt-BR',
            'datePublished' => get_post_time(DATE_W3C, false, $post_id), 'dateModified' => get_post_modified_time(DATE_W3C, false, $post_id),
            'publisher' => array('@id' => $org_id), 'author' => array('@type' => 'Person', 'name' => get_the_author_meta('display_name', $author_id), 'url' => $author_url),
            'mainEntityOfPage' => get_permalink($post_id));
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');
        if ($image) { $article['image'] = array('@type' => 'ImageObject', 'url' => $image[0], 'width' => $image[1], 'height' => $image[2]); }
        $nodes[] = $article;
    }
    return $data;
}, 99);
// O expediente principal tem bio própria; a busca mantém a política do Rank Math.
add_filter('rank_math/frontend/robots', function ($robots) {
    if (is_author() && 1 === (int) get_queried_object_id()) {
        unset($robots['noindex'], $robots['nofollow']);
        $robots['index'] = 'index'; $robots['follow'] = 'follow';
        $robots['max-image-preview'] = 'max-image-preview:large';
    }
    return $robots;
}, 99);
// Publisher é opcional até a conta AdSense fornecer um ID real.
add_action('init', function () {
    register_setting('reading', 'g73_adsense_publisher_id', array('type' => 'string', 'default' => '',
        'sanitize_callback' => function ($value) { return is_string($value) && preg_match('/^pub-[0-9]{16}$/D', $value) ? $value : ''; },
        'show_in_rest' => array('schema' => array('type' => 'string', 'pattern' => '^(pub-[0-9]{16})?$'))));
});
add_action('admin_init', function () {
    add_settings_field('g73_adsense_publisher_id', 'AdSense — ID do publisher', function () {
        echo '<input class="regular-text" type="text" name="g73_adsense_publisher_id" value="' . esc_attr(get_option('g73_adsense_publisher_id', ''))
            . '" placeholder="pub-XXXXXXXXXXXXXXXX" pattern="pub-[0-9]{16}"><p class="description">Preencha somente após receber o ID oficial. Enquanto estiver vazio, ads.txt não declara vendedores autorizados.</p>';
    }, 'reading', 'default');
});
// Interceptação nativa antes da consulta principal, sem flush ou regra extra.
add_action('parse_request', function ($wp) {
    if ('ads.txt' !== trim($wp->request, '/')) { return; }
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    if (!in_array($method, array('GET', 'HEAD'), true)) { status_header(405); header('Allow: GET, HEAD'); exit; }
    status_header(200); header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff'); header('Cache-Control: public, max-age=300');
    if ('HEAD' !== $method) {
        $publisher = get_option('g73_adsense_publisher_id', '');
        echo "# Giro73 - publicidade digital\n";
        echo preg_match('/^pub-[0-9]{16}$/D', $publisher) ? 'google.com, ' . $publisher . ", DIRECT, f08c47fec0942fa0\n"
            : "# Aguardando ID oficial do publisher AdSense. Nenhum vendedor autorizado cadastrado.\n";
    }
    exit;
}, 0);
add_action('update_option_g73_adsense_publisher_id', function () { do_action('litespeed_purge_url', home_url('/ads.txt')); });
add_action('add_option_g73_adsense_publisher_id', function () { do_action('litespeed_purge_url', home_url('/ads.txt')); });
}
