<?php
/** Giro73: publicidade local, gestão nativa e renderização sem JS no frontend. */
if (!defined('ABSPATH')) { return; }
if (!function_exists('g73_ads_render')) {
function g73_ads_defaults() {
    $slot = array('image_id' => 0, 'mobile_image_id' => 0, 'url' => '', 'advertiser' => '', 'format' => '300x250');
    return array('contact_email' => 'comercial@giro73.com.br', 'sidebar' => $slot, 'article' => $slot);
}
function g73_ads_image_id($id) {
    $id = absint($id);
    return in_array(get_post_mime_type($id), array('image/webp', 'image/png'), true) ? $id : 0;
}
function g73_ads_sanitize($input) {
    $out = g73_ads_defaults();
    if (!is_array($input)) { return $out; }
    $out['contact_email'] = sanitize_email(isset($input['contact_email']) ? $input['contact_email'] : '') ?: $out['contact_email'];
    foreach (array('sidebar', 'article') as $position) {
        $slot = isset($input[$position]) && is_array($input[$position]) ? $input[$position] : array();
        foreach (array('image_id', 'mobile_image_id') as $key) {
            $out[$position][$key] = g73_ads_image_id(isset($slot[$key]) ? $slot[$key] : 0);
        }
        $out[$position]['url'] = esc_url_raw(isset($slot['url']) ? $slot['url'] : '', array('http', 'https'));
        $out[$position]['advertiser'] = sanitize_text_field(isset($slot['advertiser']) ? $slot['advertiser'] : '');
        $out[$position]['format'] = isset($slot['format']) && '300x600' === $slot['format'] ? '300x600' : '300x250';
    }
    return $out;
}
add_action('init', function () {
    $slot_schema = array('type' => 'object', 'additionalProperties' => false, 'properties' => array(
        'image_id' => array('type' => 'integer', 'minimum' => 0),
        'mobile_image_id' => array('type' => 'integer', 'minimum' => 0),
        'url' => array('type' => 'string'), 'advertiser' => array('type' => 'string'),
        'format' => array('type' => 'string', 'enum' => array('300x250', '300x600')),
    ));
    register_setting('g73_partner_ads', 'g73_partner_ads', array(
        'type' => 'object', 'default' => g73_ads_defaults(), 'sanitize_callback' => 'g73_ads_sanitize',
        'show_in_rest' => array('schema' => array('type' => 'object', 'additionalProperties' => false,
            'properties' => array('contact_email' => array('type' => 'string'), 'sidebar' => $slot_schema, 'article' => $slot_schema))),
    ));
});
function g73_ads_render($position) {
    if (!in_array($position, array('sidebar', 'article'), true)) { return ''; }
    $config = g73_ads_sanitize(get_option('g73_partner_ads', g73_ads_defaults()));
    $slot = $config[$position];
    $id = $slot['image_id'];
    $mobile = $slot['mobile_image_id'];
    // Uma imagem mobile também pode funcionar sozinha, sem esconder o anúncio no desktop.
    if (!$id) { $id = $mobile; }
    $tall = 'sidebar' === $position && '300x600' === $slot['format'];
    $class = 'g73-partner-ad ad-slot-' . $position . ($tall ? ' ad-slot-sidebar--tall' : '');
    $html = '<div class="' . esc_attr($class) . '" data-g73-ad-slot="' . esc_attr($position) . '" role="complementary" aria-label="Publicidade">';
    $html .= '<div class="g73-ad-label">PUBLICIDADE</div><div class="g73-ad-creative">';
    if ($id) {
        $alt = $slot['advertiser'] ? 'Publicidade de ' . $slot['advertiser'] : get_post_meta($id, '_wp_attachment_image_alt', true);
        $alt = $alt ?: 'Publicidade de parceiro do Giro73';
        $image = wp_get_attachment_image($id, 'full', false, array('class' => 'g73-ad-image',
            'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async',
            'sizes' => 'sidebar' === $position ? '(max-width: 320px) 100vw, 300px' : '(max-width: 767px) calc(100vw - 32px), 728px'));
        if ($mobile && $mobile !== $id) {
            $image = '<picture><source media="(max-width: 767px)" srcset="' . esc_url(wp_get_attachment_url($mobile)) . '">' . $image . '</picture>';
        }
        if ($slot['url']) {
            $image = '<a class="g73-ad-link" href="' . esc_url($slot['url']) . '" target="_blank" rel="sponsored noopener noreferrer">' . $image . '</a>';
        }
        $html .= $image;
    } else {
        $email = $config['contact_email'];
        $href = 'mailto:' . $email . '?subject=' . rawurlencode('Quero anunciar no Giro73');
        $html .= '<a class="g73-ad-availability" href="' . esc_url($href) . '"><strong>Espaço disponível</strong><span>Anuncie aqui</span><span class="g73-ad-contact">' . esc_html($email) . '</span></a>';
    }
    return $html . '</div></div>';
}
add_shortcode('giro73_publicidade', function ($atts) {
    $atts = shortcode_atts(array('posicao' => 'sidebar'), $atts, 'giro73_publicidade');
    return g73_ads_render($atts['posicao']);
});
// O bloco Shortcode nativo aplica wpautop; preservar somente este HTML de slot.
add_filter('render_block_core/shortcode', function ($html, $block) {
    $raw = trim(isset($block['innerHTML']) ? $block['innerHTML'] : '');
    return preg_match('~^\[giro73_publicidade(?:\s[^\]]*)?\]$~s', $raw) ? do_shortcode($raw) : $html;
}, 10, 2);
// A inserção no terceiro parágrafo é feita pela rotina single-editorial.php.
add_action('admin_menu', function () {
    add_options_page('Publicidade Giro73', 'Publicidade Giro73', 'manage_options', 'giro73-publicidade', 'g73_ads_settings_page');
});
function g73_ads_settings_page() {
    if (!current_user_can('manage_options')) { return; }
    $config = g73_ads_sanitize(get_option('g73_partner_ads', g73_ads_defaults()));
    echo '<div class="wrap"><h1>Publicidade Giro73</h1><p>Banners estáticos de parceiros locais. Use WebP/PNG leve, preferencialmente até 100 KB. Links podem apontar para o site do parceiro ou WhatsApp com mensagem pronta.</p>';
    settings_errors('g73_partner_ads');
    echo '<form method="post" action="options.php">'; settings_fields('g73_partner_ads');
    echo '<p><label>E-mail de contato dos espaços disponíveis<br><input type="email" class="regular-text" name="g73_partner_ads[contact_email]" value="' . esc_attr($config['contact_email']) . '" required></label></p>';
    foreach (array('sidebar' => 'Sidebar — Home', 'article' => 'Miolo — após o terceiro parágrafo') as $position => $title) {
        $slot = $config[$position];
        echo '<h2>' . esc_html($title) . '</h2><table class="form-table" role="presentation"><tbody>';
        foreach (array('image_id' => 'Imagem do banner', 'mobile_image_id' => 'Imagem mobile (opcional)') as $key => $label) {
            $field = 'g73-ad-' . $position . '-' . $key;
            echo '<tr><th scope="row">' . esc_html($label) . '</th><td><input type="hidden" id="' . esc_attr($field) . '" name="g73_partner_ads[' . esc_attr($position) . '][' . esc_attr($key) . ']" value="' . absint($slot[$key]) . '">';
            echo '<button type="button" class="button" data-g73-media="' . esc_attr($field) . '">Selecionar na biblioteca</button> <button type="button" class="button" data-g73-clear="' . esc_attr($field) . '">Remover</button>';
            echo '<p id="' . esc_attr($field) . '-preview">' . ($slot[$key] ? wp_get_attachment_image($slot[$key], 'thumbnail', false, array('style' => 'max-width:150px;height:auto')) : 'Nenhuma imagem selecionada. O espaço exibirá o contato comercial.') . '</p></td></tr>';
        }
        foreach (array('url' => 'URL de destino', 'advertiser' => 'Nome do anunciante') as $key => $label) {
            echo '<tr><th scope="row"><label for="g73-' . esc_attr($position . '-' . $key) . '">' . esc_html($label) . '</label></th><td><input class="regular-text" type="' . ('url' === $key ? 'url' : 'text') . '" id="g73-' . esc_attr($position . '-' . $key) . '" name="g73_partner_ads[' . esc_attr($position) . '][' . esc_attr($key) . ']" value="' . esc_attr($slot[$key]) . '"></td></tr>';
        }
        if ('sidebar' === $position) {
            echo '<tr><th scope="row">Formato lateral</th><td><select name="g73_partner_ads[sidebar][format]">';
            foreach (array('300x250', '300x600') as $format) {
                echo '<option value="' . esc_attr($format) . '"' . selected($slot['format'], $format, false) . '>' . esc_html($format) . '</option>';
            }
            echo '</select></td></tr>';
        } else {
            echo '<tr><th scope="row">Formatos recomendados</th><td>Desktop 728×90; mobile 320×100. A imagem se ajusta sem cortes, dentro da altura reservada.</td></tr>';
        }
        echo '</tbody></table>';
    }
    submit_button('Salvar publicidade');
    echo '</form><p>Sem imagem, o contato aparece automaticamente. Remover a imagem não apaga o arquivo da biblioteca. PUBLICIDADE permanece visível nos dois formatos.</p></div>';
}
add_action('admin_enqueue_scripts', function ($hook) {
    if ('settings_page_giro73-publicidade' !== $hook) { return; }
    wp_enqueue_media();
    wp_add_inline_script('media-editor', <<<'JS'
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-g73-media]').forEach(button=>button.addEventListener('click',()=>{
    const field=document.getElementById(button.dataset.g73Media),preview=document.getElementById(field.id+'-preview');
    const frame=wp.media({title:'Banner Giro73 — WebP ou PNG',button:{text:'Usar banner'},library:{type:['image/webp','image/png']},multiple:false});
    frame.on('select',()=>{const image=frame.state().get('selection').first().toJSON();if(!['image/webp','image/png'].includes(image.mime)){alert('Selecione uma imagem WebP ou PNG.');return;}field.value=image.id;preview.textContent=image.filename;});frame.open();
  }));
  document.querySelectorAll('[data-g73-clear]').forEach(button=>button.addEventListener('click',()=>{const field=document.getElementById(button.dataset.g73Clear);field.value=0;document.getElementById(field.id+'-preview').textContent='Nenhuma imagem selecionada. O espaço exibirá o contato comercial.';}));
});
JS
    );
});
// Aplicar alterações comerciais também às páginas já armazenadas pelo LiteSpeed.
add_action('update_option_g73_partner_ads', function () { do_action('litespeed_purge_all'); });
add_action('add_option_g73_partner_ads', function () { do_action('litespeed_purge_all'); });
}
