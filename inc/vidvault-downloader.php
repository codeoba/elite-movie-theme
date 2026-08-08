<?php
/**
 * MovieElite Pro - VidVault.ru Download Engine Integration
 * Fetches exact 100% matching movie and TV show download links via official VidVault API.
 * Official VidVault Endpoints:
 * - Token API: https://vidvault.ru/api/get-token
 * - Download Proxy: https://vidvault.ru/api/download-proxy
 * - Direct Web Portal: https://vidvault.ru/movie/{tmdb_id} & https://vidvault.ru/tv/{tmdb_id}/{season}/{episode}
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch Download Links from VidVault API with Transient Caching
 *
 * @param string $tmdb_id TMDb or IMDb ID
 * @param string $type 'movie' or 'tv'
 * @param int $season Season number (for TV)
 * @param int $episode Episode number (for TV)
 * @return array Array of download links formatted by resolution/quality
 */
function movie_elite_get_vidvault_links($tmdb_id, $type = 'movie', $season = 1, $episode = 1) {
    if (empty($tmdb_id)) {
        return array();
    }

    $cache_key = 'movie_elite_vv_' . md5("{$type}_{$tmdb_id}_{$season}_{$episode}");
    $cached_links = get_transient($cache_key);

    if ($cached_links !== false && is_array($cached_links)) {
        return $cached_links;
    }

    $links = array(
        '720p'  => '',
        '1080p' => '',
        '4k'    => '',
        'mkv'   => '',
        'direct_page' => ($type === 'tv') ? "https://vidvault.ru/tv/{$tmdb_id}/{$season}/{$episode}" : "https://vidvault.ru/movie/{$tmdb_id}"
    );

    try {
        // Step 1: Get Token
        $token_response = wp_remote_get('https://vidvault.ru/api/get-token', array(
            'timeout' => 8,
            'headers' => array('User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
        ));

        if (!is_wp_error($token_response)) {
            $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
            $token = $token_data['t'] ?? '';

            if (!empty($token)) {
                // Step 2: Query Proxy
                $body_data = array(
                    'type'   => $type,
                    'tmdbId' => $tmdb_id
                );
                if ($type === 'tv') {
                    $body_data['season']  = intval($season);
                    $body_data['episode'] = intval($episode);
                }

                $proxy_response = wp_remote_post('https://vidvault.ru/api/download-proxy', array(
                    'timeout' => 10,
                    'headers' => array(
                        'Content-Type'    => 'application/json',
                        'x-request-token' => $token,
                        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                    ),
                    'body' => json_encode($body_data)
                ));

                if (!is_wp_error($proxy_response)) {
                    $res = json_decode(wp_remote_retrieve_body($proxy_response), true);

                    // Extract MP4 streams
                    $mp4 = $res['mp4Data']['downloadInfo']['data']['streams'] ?? $res['mp4Data']['data']['streams'] ?? array();
                    foreach ($mp4 as $st) {
                        $res_val = strtolower(strval($st['resolution'] ?? $st['resolutions'] ?? '720'));
                        $url     = $st['url'] ?? '';
                        if (!empty($url)) {
                            $dl_url = "https://dl.gemlelispe.workers.dev/" . urlencode($url);
                            if (strpos($res_val, '1080') !== false) {
                                $links['1080p'] = $dl_url;
                            } elseif (strpos($res_val, '2160') !== false || strpos($res_val, '4k') !== false) {
                                $links['4k'] = $dl_url;
                            } else {
                                $links['720p'] = $dl_url;
                            }
                        }
                    }

                    // Extract MKV files
                    $mkv = $res['mkvData']['files'][0]['url'] ?? '';
                    if (!empty($mkv)) {
                        $links['mkv'] = $mkv;
                    }
                }
            }
        }
    } catch (\Exception $e) {
        // Fallback to direct page link
    }

    // Fallbacks if API returns empty for specific resolutions
    if (empty($links['720p'])) {
        $links['720p'] = $links['direct_page'];
    }
    if (empty($links['1080p'])) {
        $links['1080p'] = $links['direct_page'];
    }
    if (empty($links['4k'])) {
        $links['4k'] = $links['direct_page'];
    }

    // Cache results for 12 hours
    set_transient($cache_key, $links, 12 * HOUR_IN_SECONDS);

    return $links;
}
