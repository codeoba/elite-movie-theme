<?php
/**
 * MovieElite Pro - Multi-Source Video Embed Scraper & Draft Guard Engine
 * Generates 4+ embed server mirrors for movies and verifies embed health before publishing.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate 4+ Embed Player Server URLs for a Movie
 *
 * @param string $imdb_id IMDb ID (e.g. tt1160419)
 * @param string $tmdb_id TMDb ID (e.g. 438148)
 * @return array Multi-source embed player array
 */
function movie_elite_generate_movie_embeds($imdb_id = '', $tmdb_id = '') {
    if (function_exists('movie_elite_get_embed_servers')) {
        $servers = movie_elite_get_embed_servers();
    } else {
        $servers = array();
    }

    $embeds = array();

    foreach ($servers as $id => $srv) {
        if (($srv['status'] ?? 'active') !== 'active') {
            continue;
        }

        $pattern = $srv['pattern'] ?? '';
        $type    = $srv['type'] ?? 'imdb';
        $url     = '';

        if ($type === 'tmdb' && !empty($tmdb_id)) {
            $url = str_replace('{tmdb_id}', $tmdb_id, $pattern);
        } elseif (!empty($imdb_id)) {
            $url = str_replace('{imdb_id}', $imdb_id, $pattern);
            $url = str_replace('{tmdb_id}', $tmdb_id, $url);
        }

        if (!empty($url)) {
            $embeds[] = array(
                'id'     => $id,
                'name'   => $srv['name'] ?? 'Server',
                'url'    => esc_url_raw($url),
                'type'   => $type
            );
        }
    }

    return $embeds;
}

/**
 * Test Embed Server Responsiveness (Health Checker)
 *
 * @param string $url Embed Iframe URL
 * @return bool True if valid/responsive, False if dead
 */
function movie_elite_verify_embed_health($url) {
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $response = wp_remote_request($url, array(
        'method'      => 'HEAD',
        'timeout'     => 3,
        'redirection' => 3,
        'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'sslverify'   => false
    ));

    if (is_wp_error($response)) {
        // Retry with fast GET request
        $response = wp_remote_get($url, array(
            'timeout'     => 3,
            'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'sslverify'   => false
        ));
    }

    if (is_wp_error($response)) {
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    return ($code >= 200 && $code < 404);
}

/**
 * Draft Protection Guard: If no embed player source is available, keep post in Draft
 *
 * @param int $post_id Post ID
 * @param string $imdb_id
 * @param string $tmdb_id
 * @return bool True if published, False if moved to draft
 */
function movie_elite_process_import_draft_guard($post_id, $imdb_id = '', $tmdb_id = '') {
    $embeds = movie_elite_generate_movie_embeds($imdb_id, $tmdb_id);

    if (empty($embeds)) {
        // Move to Draft
        wp_update_post(array(
            'ID'          => $post_id,
            'post_status' => 'draft'
        ));
        update_post_meta($post_id, '_import_draft_reason', 'No active embed player sources found');
        return false;
    }

    // Save embeds array to postmeta
    update_post_meta($post_id, 'movie_embed_sources', $embeds);
    update_post_meta($post_id, 'primary_embed_url', $embeds[0]['url'] ?? '');
    
    return true;
}
