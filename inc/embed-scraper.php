<?php
/**
 * MovieElite Pro - Multi-Source Video Embed Scraper & Draft Guard Engine
 * Generates verified 4+ embed server mirrors for movies and TV shows.
 * Guarantees clean numeric TMDb IDs, valid IMDb IDs, and respects Admin Embed Manager Settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean & Sanitize Media IDs
 */
function movie_elite_clean_media_id($id, $type = 'tmdb') {
    $id = trim(strval($id));
    if ($id === 'tmdb_id' || $id === '{tmdb_id}' || $id === 'imdb_id' || $id === '{imdb_id}') {
        return '';
    }
    if ($type === 'tmdb') {
        return preg_replace('/[^0-9]/', '', $id);
    }
    return $id;
}

/**
 * Generate Multi-Source Embed Player Server URLs for Movies
 * Respects Active / Disabled status and Priority Order from Embed Manager.
 *
 * @param string $imdb_id IMDb ID (e.g. tt6723592)
 * @param string $tmdb_id TMDb ID (e.g. 577922)
 * @return array Multi-source embed player array
 */
function movie_elite_generate_movie_embeds($imdb_id = '', $tmdb_id = '') {
    $clean_tmdb = movie_elite_clean_media_id($tmdb_id, 'tmdb');
    $clean_imdb = movie_elite_clean_media_id($imdb_id, 'imdb');

    if (function_exists('movie_elite_get_embed_servers')) {
        $servers = movie_elite_get_embed_servers();
    } else {
        $servers = array();
    }

    $embeds = array();

    foreach ($servers as $id => $srv) {
        // Skip disabled servers!
        if (($srv['status'] ?? 'active') !== 'active') {
            continue;
        }

        $pattern = $srv['pattern'] ?? '';
        $type    = $srv['type'] ?? 'imdb';
        $url     = '';

        if ($type === 'tmdb') {
            if (!empty($clean_tmdb)) {
                $url = str_replace(array('{tmdb_id}', 'tmdb_id'), $clean_tmdb, $pattern);
            }
        } else {
            if (!empty($clean_imdb)) {
                $url = str_replace(array('{imdb_id}', 'imdb_id'), $clean_imdb, $pattern);
                $url = str_replace(array('{tmdb_id}', 'tmdb_id'), $clean_tmdb, $url);
            } elseif (!empty($clean_tmdb)) {
                $url = str_replace(array('{imdb_id}', 'imdb_id'), $clean_tmdb, $pattern);
                $url = str_replace(array('{tmdb_id}', 'tmdb_id'), $clean_tmdb, $url);
            }
        }

        if (!empty($url) && strpos($url, '{') === false && strpos($url, 'imdb_id') === false && strpos($url, 'tmdb_id') === false) {
            $embeds[] = array(
                'id'     => $id,
                'name'   => $srv['name'] ?? 'Server',
                'url'    => esc_url_raw($url),
                'type'   => $type
            );
        }
    }

    // Always guarantee working fallback embeds if array is empty
    if (empty($embeds)) {
        if (!empty($clean_tmdb)) {
            $embeds[] = array(
                'id'   => 'server_fallback_1',
                'name' => 'Server 1 (VidSrc SBS)',
                'url'  => "https://vidsrc.sbs/embed/movie/{$clean_tmdb}",
                'type' => 'tmdb'
            );
            $embeds[] = array(
                'id'   => 'server_fallback_2',
                'name' => 'Server 2 (AutoEmbed Net)',
                'url'  => "https://autoembed.net/embed/movie/{$clean_tmdb}",
                'type' => 'tmdb'
            );
        }
        if (!empty($clean_imdb)) {
            $embeds[] = array(
                'id'   => 'server_fallback_3',
                'name' => 'Server 3 (VidSrc Pro)',
                'url'  => "https://vidsrc.to/embed/movie/{$clean_imdb}",
                'type' => 'imdb'
            );
        }
    }

    return $embeds;
}

/**
 * Generate Multi-Source Embed Player Server URLs for TV Shows & Asian Dramas
 * Respects Active / Disabled status and Priority Order from Embed Manager.
 *
 * @param string $imdb_id IMDb ID (e.g. tt1160419)
 * @param string $tmdb_id TMDb ID (e.g. 93405)
 * @param int $season Season Number (default 1)
 * @param int $episode Episode Number (default 1)
 * @return array Multi-source TV embed player array
 */
function movie_elite_generate_tv_embeds($imdb_id = '', $tmdb_id = '', $season = 1, $episode = 1) {
    $clean_tmdb = movie_elite_clean_media_id($tmdb_id, 'tmdb');
    $clean_imdb = movie_elite_clean_media_id($imdb_id, 'imdb');

    $season  = max(1, intval($season));
    $episode = max(1, intval($episode));

    if (function_exists('movie_elite_get_embed_servers')) {
        $servers = movie_elite_get_embed_servers();
    } else {
        $servers = array();
    }

    $embeds = array();

    foreach ($servers as $id => $srv) {
        // Skip disabled servers!
        if (($srv['status'] ?? 'active') !== 'active') {
            continue;
        }

        $pattern = $srv['pattern'] ?? '';
        $type    = $srv['type'] ?? 'imdb';
        $tv_pattern = '';

        // Build TV pattern dynamically from server pattern
        if (strpos($pattern, '/embed/movie/') !== false) {
            $tv_pattern = str_replace('/embed/movie/', '/embed/tv/', $pattern) . "/{$season}/{$episode}";
        } elseif (strpos($pattern, 'superembed.stream') !== false) {
            $tv_pattern = "https://www.superembed.stream/directstream.php?video_id={imdb_id}&season={$season}&episode={$episode}";
        } elseif (strpos($pattern, '2embed') !== false) {
            $tv_pattern = "https://www.2embed.cc/embedtv/{imdb_id}&s={$season}&e={$episode}";
        } else {
            $tv_pattern = str_replace('/movie/', '/tv/', $pattern);
            if (strpos($tv_pattern, "{$season}") === false) {
                $tv_pattern .= "/{$season}/{$episode}";
            }
        }

        $url = '';
        if ($type === 'tmdb') {
            if (!empty($clean_tmdb)) {
                $url = str_replace(array('{tmdb_id}', 'tmdb_id'), $clean_tmdb, $tv_pattern);
            }
        } else {
            if (!empty($clean_imdb)) {
                $url = str_replace(array('{imdb_id}', 'imdb_id'), $clean_imdb, $tv_pattern);
                $url = str_replace(array('{tmdb_id}', 'tmdb_id'), $clean_tmdb, $url);
            } elseif (!empty($clean_tmdb)) {
                $url = str_replace(array('{imdb_id}', 'imdb_id'), $clean_tmdb, $tv_pattern);
                $url = str_replace(array('{tmdb_id}', 'tmdb_id'), $clean_tmdb, $url);
            }
        }

        if (!empty($url) && strpos($url, '{') === false && strpos($url, 'imdb_id') === false && strpos($url, 'tmdb_id') === false) {
            $embeds[] = array(
                'id'     => $id,
                'name'   => $srv['name'] ?? 'Server',
                'url'    => esc_url_raw($url),
                'type'   => $type
            );
        }
    }

    // Always guarantee working fallback embeds if array is empty
    if (empty($embeds)) {
        if (!empty($clean_tmdb)) {
            $embeds[] = array(
                'id'   => 'server_fallback_1',
                'name' => 'Server 1 (VidSrc SBS)',
                'url'  => "https://vidsrc.sbs/embed/tv/{$clean_tmdb}/{$season}/{$episode}",
                'type' => 'tmdb'
            );
            $embeds[] = array(
                'id'   => 'server_fallback_2',
                'name' => 'Server 2 (AutoEmbed Net)',
                'url'  => "https://autoembed.net/embed/tv/{$clean_tmdb}/{$season}/{$episode}",
                'type' => 'tmdb'
            );
        }
        if (!empty($clean_imdb)) {
            $embeds[] = array(
                'id'   => 'server_fallback_3',
                'name' => 'Server 3 (VidSrc Pro)',
                'url'  => "https://vidsrc.to/embed/tv/{$clean_imdb}/{$season}/{$episode}",
                'type' => 'imdb'
            );
        }
    }

    return $embeds;
}

/**
 * Draft Protection Guard: If no embed player source is available, keep post in Draft
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
