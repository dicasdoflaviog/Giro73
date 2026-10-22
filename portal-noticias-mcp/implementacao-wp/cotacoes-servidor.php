<?php
/** Giro73: endpoint público de cotações, sem chamadas financeiras no frontend. */
if (!defined('ABSPATH')) {
    return;
}

if (!function_exists('g73_market_v1_get')) {
function g73_market_v1_json($url) {
    $response = wp_remote_get($url, array(
        'timeout' => 4, 'redirection' => 0, 'limit_response_size' => 65536,
        'headers' => array('Accept' => 'application/json'),
    ));
    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        return array();
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return is_array($body) ? $body : array();
}

function g73_market_v1_quote($value, $variation, $unit, $source, $as_of = null) {
    if (!is_numeric($value) || !is_numeric($variation)
        || !is_finite((float) $value) || !is_finite((float) $variation) || (float) $value <= 0) {
        return null;
    }
    return array('value' => (float) $value, 'variation' => (float) $variation,
        'unit' => $unit, 'source' => $source, 'as_of' => $as_of);
}

function g73_market_v1_get() {
    $cached = get_transient('g73_market_v2');
    if (is_array($cached)) {
        return $cached;
    }
    // Trava atômica: apenas uma consulta fria por vez, inclusive sem Redis.
    $lock = 'g73_market_v1_refresh_lock';
    $lock_time = (int) get_option($lock, 0);
    if ($lock_time && time() - $lock_time > 15) {
        delete_option($lock);
    }
    if (!add_option($lock, time(), '', false)) {
        return new WP_Error('g73_market_refreshing', 'Cotações em atualização.', array('status' => 503));
    }
    try {
        $fx = g73_market_v1_json('https://economia.awesomeapi.com.br/json/last/USD-BRL,EUR-BRL');
        $quotes = array();
        foreach (array('usd' => 'USDBRL', 'eur' => 'EURBRL') as $key => $code) {
            $q = isset($fx[$code]) && is_array($fx[$code]) ? $fx[$code] : array();
            $as_of = isset($q['timestamp']) && is_numeric($q['timestamp']) && $q['timestamp'] > 0
                ? gmdate('c', (int) $q['timestamp']) : null;
            $quotes[$key] = g73_market_v1_quote(isset($q['bid']) ? $q['bid'] : null,
                isset($q['pctChange']) ? $q['pctChange'] : null, 'currency', 'AwesomeAPI', $as_of);
        }
        $hg = g73_market_v1_json('https://api.hgbrasil.com/finance?format=json');
        // A HG Brasil também fornece moedas caso a AwesomeAPI não responda.
        foreach (array('usd' => 'USD', 'eur' => 'EUR') as $key => $code) {
            if (!$quotes[$key]) {
                $currency = isset($hg['results']['currencies'][$code]) ? $hg['results']['currencies'][$code] : array();
                $quotes[$key] = g73_market_v1_quote(isset($currency['buy']) ? $currency['buy'] : null,
                    isset($currency['variation']) ? $currency['variation'] : null, 'currency', 'HG Brasil');
            }
        }
        $q = isset($hg['results']['stocks']['IBOVESPA']) ? $hg['results']['stocks']['IBOVESPA'] : array();
        $quotes['ibov'] = g73_market_v1_quote(isset($q['points']) ? $q['points'] : null,
            isset($q['variation']) ? $q['variation'] : null, 'points', 'HG Brasil');
        $count = count(array_filter($quotes));
        $ttl = 3 === $count ? 15 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS;
        $now = time();
        $data = array('status' => 3 === $count ? 'ready' : ($count ? 'partial' : 'unavailable'),
            'quotes' => $quotes, 'fetched_at' => gmdate('c', $now),
            'expires_at' => gmdate('c', $now + $ttl), 'cache_ttl' => $ttl);
        // Falhas têm retry em um minuto; respostas completas ficam 15 minutos.
        set_transient('g73_market_v2', $data, $ttl);
        return $data;
    } finally {
        delete_option($lock);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('giro73/v1', '/cotacoes', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => function () {
            $data = g73_market_v1_get();
            if (is_wp_error($data)) {
                $response = new WP_REST_Response(array('code' => $data->get_error_code(),
                    'message' => $data->get_error_message()), 503);
                $response->header('Retry-After', '3');
                $response->header('Cache-Control', 'no-store');
                return $response;
            }
            $response = new WP_REST_Response($data, 200);
            $response->header('Cache-Control', 'no-store');
            return $response;
        },
    ));
});
}
