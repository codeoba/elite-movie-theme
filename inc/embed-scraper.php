<?php
/**
 * MovieElite Pro - Multi-Source Video Embed Scraper & Draft Guard Engine
 * Generates verified 4+ embed server mirrors for movies and TV shows, ensuring
 * TMDb-only providers (VidSrc SBS, VSEmbed, AutoEmbed) get clean numeric TMDb IDs.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate Multi-Source Embed Player Server URLs
 *
 * @param string $imdb_id IMDb ID (e.g. tt1160419)
 * @param string $tmdb_id TMDb ID (e.g. 76600)
 * @return array Multi-source embed player array
 */
function movie_elite_generate_movie_embeds($imdb_id = '', $tmdb_id = '') {
    if (function_exists('movie_elite_get_embed_servers')) {
        $servers = movie_elite_get_embed_servers();
    } else {
        $servers = array();
    }

    // Clean IDs
    $tmdb_id = trim(preg_replace('/[^0-9]/', '', $tmdb_id));
    $imdb_id = trim($imdb_id);

    $embeds = array();

    foreach ($servers as $id => $srv) {
        if (($srv['status'] ?? 'active') !== 'active') {
            continue;
        }

        $pattern = $srv['pattern'] ?? '';
        $type    = $srv['type'] ?? 'imdb';
        $url     = '';

        if ($type === 'tmdb') {
            if (!empty($tmdb_id)) {
                $url = str_replace('{tmdb_id}', $tmdb_id, $pattern);
            }
        } else {
            if (!empty($imdb_id)) {
                $url = str_replace('{imdb_id}', $imdb_id, $pattern);
                $url = str_replace('{tmdb_id}', $tmdb_id, $url);
            } elseif (!empty($tmdb_id)) {
                $url = str_replace('{imdb_id}', $tmdb_id, $pattern);
                $url = str_replace('{tmdb_id}', $tmdb_id, $url);
            }
        }

        if (!empty($url) && strpos($url, '{') === false) {
            $embeds[] = array(
                'id'     => $id,
                'name'   => $srv['name'] ?? 'Server',
                'url'    => esc_url_raw($url),
                'type'   => $type
            );
        }
    }

    // Fallback if no embeds generated
    if (empty($embeds)) {
        if (!empty($tmdb_id)) {
            $embeds[] = array(
                'id' => 'server_fallback_1',
                'name' => 'Server 1 (AutoEmbed Net)',
                'url' => "https://autoembed.net/embed/movie/{$tmdb_id}",
                'type' => 'tmdb'
            );
            $embeds[] = array(
                'id' => 'server_fallback_2',
                'name' => 'Server 2 (VidSrc SBS)',
                'url' => "https://vidsrc.sbs/embed/movie/{$tmdb_id}",
                'type' => 'tmdb'
            );
        }
        if (!empty($imdb_id)) {
            $embeds[] = array(
                'id' => 'server_fallback_3',
                'name' => 'Server 3 (VidSrc Pro)',
                'url' => "https://vidsrc.to/embed/movie/{$imdb_id}",
                'type' => 'imdb'
            );
        }
    }

    return $embeds;
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
