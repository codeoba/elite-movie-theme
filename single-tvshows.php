<?php
/**
 * MovieElite Pro - Single TV Show Detail View (Unrestricted Player Iframe & Compact Poster)
 */

get_header();

while (have_posts()) : the_post();
    $post_id   = get_the_ID();
    $title     = get_the_title();
    $raw_imdb  = get_post_meta($post_id, 'imdb_id', true);
    $raw_tmdb  = get_post_meta($post_id, 'tmdb_id', true);

    $clean_tmdb = function_exists('movie_elite_clean_media_id') ? movie_elite_clean_media_id($raw_tmdb, 'tmdb') : preg_replace('/[^0-9]/', '', $raw_tmdb);
    $clean_imdb = function_exists('movie_elite_clean_media_id') ? movie_elite_clean_media_id($raw_imdb, 'imdb') : trim($raw_imdb);

    if (empty($clean_tmdb) && empty($clean_imdb)) {
        $clean_tmdb = '93405';
        $clean_imdb = 'tt1160419';
    } elseif (empty($clean_tmdb)) {
        $clean_tmdb = '93405';
    } elseif (empty($clean_imdb)) {
        $clean_imdb = 'tt1160419';
    }

    $rating    = get_post_meta($post_id, 'imdb_rating', true) ?: '8.8';
    $year      = get_post_meta($post_id, 'release_year', true) ?: '2026';
    $quality   = get_post_meta($post_id, 'movie_quality', true) ?: '4K UHD';
    $status    = get_post_meta($post_id, '_drama_status', true) ?: 'Ongoing';
    $poster    = get_post_meta($post_id, 'poster_url', true);
    $backdrop  = get_post_meta($post_id, 'backdrop_url', true) ?: $poster;

    // TV Show Seasons & Episodes
    $seasons   = intval(get_post_meta($post_id, 'total_seasons', true) ?: 1);
    $episodes  = intval(get_post_meta($post_id, 'total_episodes', true) ?: 12);

    // Fetch DramaCool scraped episodes data (if drama was imported via DramaCool importer)
    $dc_episodes_data = get_post_meta($post_id, 'dramacool_episodes_data', true);
    $has_dc_episodes  = (!empty($dc_episodes_data) && is_array($dc_episodes_data));

    // If DramaCool episodes exist, override episode count from real scraped data
    if ($has_dc_episodes) {
        $episodes = count($dc_episodes_data);
    }

    // Fetch VidVault.ru TV Episode Real Download Links
    $vv_links = array();
    if (function_exists('movie_elite_get_vidvault_links')) {
        $vv_links = movie_elite_get_vidvault_links($clean_tmdb ?: $clean_imdb, 'tv', 1, 1);
    }

    $vidvault_direct_url = "https://vidvault.ru/tv/{$clean_tmdb}/1/1";
    $dl_1080p  = get_post_meta($post_id, 'download_url_1080p', true) ?: ($vv_links['1080p'] ?? $vidvault_direct_url);
    $primary_download_url = !empty($dl_1080p) ? $dl_1080p : $vidvault_direct_url;

    if (empty($poster)) {
        $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80';
    }

    $genres = get_the_terms($post_id, 'genre');
    $genre_names = (!empty($genres) && !is_wp_error($genres)) ? wp_list_pluck($genres, 'name') : array('TV Series', 'Drama');

    // Dynamic TV Embed Player Sources:
    // - DramaCool dramas: use scraped servers for Episode 1
    // - Regular TV shows: use embed-manager (vidsrc, vsembed etc.)
    if ($has_dc_episodes) {
        // Get servers for episode 1 from DramaCool scraped data
        $first_ep_key = array_key_first($dc_episodes_data);
        $auto_embeds = $dc_episodes_data[$first_ep_key]['servers'] ?? array();
    } else {
        $auto_embeds = function_exists('movie_elite_generate_tv_embeds') ? movie_elite_generate_tv_embeds($clean_imdb, $clean_tmdb, 1, 1) : array();
    }

    // Manual Player Embeds (meta) - prepended FIRST ONLY IF filled by admin
    $manual_players = get_post_meta($post_id, 'manual_player_embeds', true);
    if (!is_array($manual_players)) {
        $manual_players = array();
    }

    // Merge: manual first, then auto/DramaCool
    $embeds = array();
    $srv_counter = 1;
    foreach ($manual_players as $mp) {
        if (!empty($mp['url'])) {
            $embeds[] = array('name' => $mp['label'] ?: 'Server ' . $srv_counter, 'url' => $mp['url']);
            $srv_counter++;
        }
    }
    foreach ($auto_embeds as $ae) {
        $embeds[] = array('name' => $ae['name'] ?? 'Server ' . $srv_counter, 'url' => $ae['url']);
        $srv_counter++;
    }

    // Force VidSrc ME to be Server 1 (Index 0) for TV Shows
    $vidsrc_me_url = "https://vidsrc.me/embed/tv/{$clean_imdb}/1/1";
    $vidsrc_found_key = null;

    foreach ($embeds as $k => $e) {
        if (strpos($e['url'], 'vidsrc.me') !== false || strpos(strtolower($e['name']), 'vidsrc me') !== false) {
            $vidsrc_found_key = $k;
            break;
        }
    }

    if ($vidsrc_found_key !== null) {
        $vidsrc_item = $embeds[$vidsrc_found_key];
        unset($embeds[$vidsrc_found_key]);
        array_unshift($embeds, $vidsrc_item);
    } else {
        array_unshift($embeds, array('name' => 'Server 1 (VidSrc ME)', 'url' => $vidsrc_me_url));
    }

    // Re-index array and format server labels cleanly
    $embeds = array_values($embeds);
    $renumber = 1;
    foreach ($embeds as &$eb) {
        if (preg_match('/Server \d+/i', $eb['name'])) {
            $eb['name'] = preg_replace('/Server \d+/i', 'Server ' . $renumber, $eb['name']);
        }
        $renumber++;
    }
    unset($eb);

    // Manual Download Links (meta)
    $manual_downloads = get_post_meta($post_id, 'manual_download_links', true);
    if (empty($manual_downloads) || !is_array($manual_downloads)) {
        $manual_downloads = array();
        $l720  = get_post_meta($post_id, 'download_url_720p', true);
        $l1080 = get_post_meta($post_id, 'download_url_1080p', true);
        $l4k   = get_post_meta($post_id, 'download_url_4k', true);
        if ($l720)  { $manual_downloads[] = array('label' => '720p HD',         'url' => $l720); }
        if ($l1080) { $manual_downloads[] = array('label' => '1080p Full HD',   'url' => $l1080); }
        if ($l4k)   { $manual_downloads[] = array('label' => '4K Ultra HD',     'url' => $l4k); }
    }
    $primary_download_url = !empty($manual_downloads[0]['url']) ? $manual_downloads[0]['url'] : $vidvault_direct_url;
?>

<!-- Lights Off Overlay -->
<div id="lights-off-overlay" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.95); transition:all 0.3s;"></div>

<main class="main-content single-movie-wrapper" data-post-id="<?php echo $post_id; ?>">
    <div class="container">
        
        <!-- Breadcrumbs -->
        <div style="margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; 
            <a href="<?php echo esc_url(home_url('/tvshows/')); ?>">TV Shows</a> &nbsp;/&nbsp; 
            <span style="color:#fff;"><?php echo esc_html($title); ?></span>
        </div>

        <!-- TV Show Player Container -->
        <div class="movie-player-container" id="player-container-box">
            <!-- Server Switcher Bar -->
            <div class="server-switcher-bar">
                <span class="server-label"><i class="fa-solid fa-server" style="color:var(--accent-cyan);"></i> SELECT SERVER:</span>
                <?php
                if (!empty($embeds) && is_array($embeds)) :
                    $server_num = 0;
                    foreach ($embeds as $srv) :
                        $server_num++;
                        $active = ($server_num === 1) ? 'active' : '';
                        $srv_url = esc_url($srv['url']);
                        $srv_name = esc_html($srv['name'] ?? "Server {$server_num}");
                ?>
                <button type="button" class="server-tab <?php echo $active; ?>" data-url="<?php echo $srv_url; ?>">
                    <i class="fa-solid fa-play"></i> <?php echo $srv_name; ?>
                </button>
                <?php
                    endforeach;
                else :
                ?>
                <button type="button" class="server-tab active" data-url="https://vidsrc.sbs/embed/tv/<?php echo esc_attr($clean_tmdb); ?>/1/1">
                    <i class="fa-solid fa-play"></i> Server 1 (VidSrc SBS)
                </button>
                <button type="button" class="server-tab" data-url="https://vsembed.ru/embed/tv/<?php echo esc_attr($clean_tmdb); ?>/1/1">
                    <i class="fa-solid fa-play"></i> Server 2 (VSEmbed Stream)
                </button>
                <button type="button" class="server-tab" data-url="https://autoembed.net/embed/tv/<?php echo esc_attr($clean_tmdb); ?>/1/1">
                    <i class="fa-solid fa-play"></i> Server 3 (AutoEmbed Net)
                </button>
                <?php endif; ?>
            </div>

            <!-- TV Show Season & Episode Selector Bar -->
            <div style="background:#111520; padding:12px 18px; border-bottom:1px solid var(--border-color); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-weight:800; font-size:0.82rem; color:var(--accent-gold);"><i class="fa-solid fa-layer-group"></i> SEASON:</span>
                    <select id="season-selector-select" style="background:#181e2d; color:#fff; border:1px solid var(--border-color); padding:5px 12px; border-radius:6px; font-weight:700; cursor:pointer; font-size:0.85rem;">
                        <?php for ($s = 1; $s <= $seasons; $s++) : ?>
                        <option value="<?php echo $s; ?>">Season <?php echo $s; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div style="display:flex; align-items:center; gap:8px; flex-wrap:nowrap; overflow-x:auto; max-width:100%; -webkit-overflow-scrolling:touch; padding-bottom:4px;">
                    <span style="font-weight:800; font-size:0.82rem; color:var(--accent-cyan); white-space:nowrap;"><i class="fa-solid fa-list-ol"></i> EPISODES:</span>
                    <div id="episodes-grid-list" style="display:flex; gap:5px; flex-wrap:nowrap;">
                        <?php
                        if ($has_dc_episodes) {
                            // DramaCool imported drama: render real episode buttons from scraped data
                            $ep_idx = 0;
                            foreach ($dc_episodes_data as $ep_num => $ep_data) {
                                $ep_idx++;
                                $ep_label = 'Ep ' . esc_html($ep_num);
                                $is_active = ($ep_idx === 1) ? 'active' : '';
                                // Encode servers JSON for this episode into data attribute
                                $ep_servers_json = esc_attr(json_encode($ep_data['servers'] ?? array()));
                                echo '<button type="button" class="alphabet-btn btn-episode-select ' . $is_active . '" data-episode="' . esc_attr($ep_num) . '" data-servers="' . $ep_servers_json . '" style="min-width:32px; padding:0 8px;">' . $ep_label . '</button>';
                            }
                        } else {
                            // Regular TV show: numeric episode buttons (update URL via season/episode pattern)
                            for ($e = 1; $e <= min($episodes, 30); $e++) : ?>
                            <button type="button" class="alphabet-btn btn-episode-select <?php echo ($e === 1) ? 'active' : ''; ?>" data-episode="<?php echo $e; ?>" style="min-width:32px; padding:0 8px;">
                                Ep <?php echo $e; ?>
                            </button>
                            <?php endfor;
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Embed Player Frame (Clean Unrestricted Player Iframe) -->
            <div class="iframe-player-wrapper" style="position:relative;">
                <iframe id="main-movie-iframe" src="<?php echo esc_url($embeds[0]['url'] ?? "https://vidsrc.me/embed/tv/{$clean_imdb}/1/1"); ?>" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" referrerpolicy="origin-when-cross-origin" allowfullscreen></iframe>
                
                <!-- Skip Intro Button Overlay -->
                <button type="button" id="btn-skip-intro" style="position:absolute; bottom:20px; right:20px; z-index:10; background:rgba(0,0,0,0.85); color:var(--accent-cyan); border:1px solid var(--accent-cyan); padding:8px 16px; border-radius:8px; font-weight:800; font-size:0.85rem; cursor:pointer; display:flex; align-items:center; gap:6px; backdrop-filter:blur(6px);">
                    <i class="fa-solid fa-forward-fast"></i> Skip Intro (85s)
                </button>
            </div>

            <!-- Advanced Player Controls Sub-Bar -->
            <div style="padding: 14px 20px; display: flex; flex-wrap:wrap; align-items: center; justify-content: space-between; gap:12px; background: #0d1017; font-size: 0.85rem;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="color:var(--accent-green); font-weight:700;"><i class="fa-solid fa-circle-check"></i> TV Show Player Verified</span>
                </div>

                <!-- Advanced Action Buttons -->
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    <button type="button" class="btn-next-episode alphabet-btn" style="background:linear-gradient(135deg, var(--accent-cyan), var(--accent-blue)); color:#000; font-weight:800; border:none;">
                        <i class="fa-solid fa-forward-step"></i> Next Episode
                    </button>

                    <button type="button" class="me-wl-btn alphabet-btn" data-id="<?php echo $post_id; ?>" style="background:rgba(255,45,107,0.15); color:var(--accent-magenta); border:1px solid var(--accent-magenta);">
                        <i class="fa-solid fa-heart"></i> Watchlist
                    </button>

                    <?php 
                    $trailer_url = get_post_meta($post_id, 'trailer_url', true);
                    if (!empty($trailer_url)) : 
                    ?>
                    <button type="button" class="btn-open-trailer alphabet-btn" data-trailer="<?php echo esc_attr($trailer_url); ?>" style="background:rgba(156,39,176,0.2); color:#e040fb; border:1px solid #e040fb;">
                        <i class="fa-solid fa-film"></i> Watch Trailer
                    </button>
                    <?php endif; ?>

                    <button type="button" class="btn-report-broken alphabet-btn" data-id="<?php echo $post_id; ?>" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid #ef4444;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Report Player
                    </button>

                    <button type="button" id="btn-toggle-lights" class="alphabet-btn" style="background:rgba(255,183,3,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold);">
                        <i class="fa-solid fa-lightbulb"></i> <span id="lights-btn-text">Lights Off</span>
                    </button>

                    <button type="button" id="btn-toggle-expand" class="alphabet-btn" style="background:rgba(0,242,254,0.15); color:var(--accent-cyan); border:1px solid var(--accent-cyan);">
                        <i class="fa-solid fa-expand"></i> Theater Mode
                    </button>

                    <!-- Download Buttons (Manual or Fallback) -->
                    <?php if (!empty($manual_downloads)) :
                        foreach ($manual_downloads as $dl) :
                            if (empty($dl['url'])) continue; ?>
                    <a href="<?php echo esc_url($dl['url']); ?>" target="_blank" rel="noopener" class="alphabet-btn" style="background:rgba(0,255,136,0.15); color:var(--accent-green); border:1px solid var(--accent-green); text-decoration:none;">
                        <i class="fa-solid fa-download"></i> <?php echo esc_html($dl['label'] ?: 'Download'); ?>
                    </a>
                    <?php endforeach; else : ?>
                    <a href="<?php echo esc_url($primary_download_url); ?>" target="_blank" rel="noopener" class="alphabet-btn" style="background:rgba(0,255,136,0.15); color:var(--accent-green); border:1px solid var(--accent-green); text-decoration:none;">
                        <i class="fa-solid fa-download"></i> Download Episode
                    </a>
                    <?php endif; ?>

                    <button type="button" class="alphabet-btn" onclick="document.getElementById('main-movie-iframe').src=document.getElementById('main-movie-iframe').src;" style="background:rgba(255,255,255,0.08);">
                        <i class="fa-solid fa-rotate-right"></i> Reload
                    </button>
                </div>
            </div>
        </div>

        <!-- TV Show Details & Metadata Grid -->
        <div class="single-details-grid">
            <!-- Compact Poster Sidebar -->
            <div>
                <div class="single-poster-wrap">
                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" />
                </div>
            </div>

            <!-- Info Details -->
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px; flex-wrap:wrap;">
                    <span class="slide-badge" style="margin:0;"><i class="fa-solid fa-tv"></i> <?php echo esc_html(implode(', ', $genre_names)); ?></span>
                    <span style="background:var(--accent-gold); color:#000; font-weight:900; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><i class="fa-solid fa-star"></i> IMDb <?php echo esc_html($rating); ?></span>
                </div>

                <h1 style="font-size: 2rem; font-weight: 900; color: #fff; margin-bottom: 12px; line-height: 1.2;"><?php echo esc_html($title); ?></h1>
                
                <div style="display:flex; align-items:center; gap:16px; color:var(--text-muted); font-size:0.88rem; margin-bottom:20px; flex-wrap:wrap;">
                    <span><i class="fa-solid fa-calendar-days" style="color:var(--accent-cyan);"></i> Release: <?php echo esc_html($year); ?></span>
                    <span><i class="fa-solid fa-layer-group" style="color:var(--accent-gold);"></i> Seasons: <?php echo esc_html($seasons); ?></span>
                    <span><i class="fa-solid fa-list-ol" style="color:var(--accent-green);"></i> Episodes: <?php echo esc_html($episodes); ?></span>
                    <span><i class="fa-solid fa-eye" style="color:var(--accent-cyan);"></i> Views: <?php echo function_exists('movie_elite_get_views') ? movie_elite_get_views($post_id) : '1'; ?></span>
                    <span><i class="fa-solid fa-video" style="color:var(--accent-green);"></i> Quality: <?php echo esc_html($quality); ?></span>
                </div>

                <!-- Storyline & Cast -->
                <h3 style="color:#fff; font-size:1.15rem; margin-bottom:10px;">Storyline / Overview</h3>
                <div style="background:var(--bg-card); padding:18px; border-radius:var(--radius-md); border:1px solid var(--border-color); color:var(--text-muted); font-size:0.92rem; line-height:1.7; margin-bottom:20px;">
                    <?php the_content(); ?>
                </div>

                <!-- Cast Avatars Carousel -->
                <?php if (function_exists('movie_elite_render_cast_avatars')) movie_elite_render_cast_avatars($post_id); ?>

                <!-- One-Tap Social Share Bar -->
                <div style="margin-bottom:28px; background:rgba(255,255,255,0.03); padding:16px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                    <h4 style="color:#fff; font-size:0.95rem; margin:0 0 12px 0;"><i class="fa-solid fa-share-nodes" style="color:var(--accent-cyan);"></i> Share This Drama With Friends</h4>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($title . ' - Watch Now: ' . get_permalink()); ?>" target="_blank" rel="noopener" style="background:#25D366; color:#fff; padding:7px 15px; border-radius:6px; font-weight:700; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-brands fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="https://t.me/share/url?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode($title); ?>" target="_blank" rel="noopener" style="background:#0088cc; color:#fff; padding:7px 15px; border-radius:6px; font-weight:700; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-brands fa-telegram"></i> Telegram
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" rel="noopener" style="background:#1877f2; color:#fff; padding:7px 15px; border-radius:6px; font-weight:700; font-size:0.82rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-brands fa-facebook"></i> Facebook
                        </a>
                        <button type="button" class="btn-copy-link" data-url="<?php echo esc_url(get_permalink()); ?>" style="background:rgba(255,255,255,0.1); color:#fff; border:1px solid var(--border-color); padding:7px 15px; border-radius:6px; font-weight:700; font-size:0.82rem; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-link"></i> <span class="copy-text">Copy Link</span>
                        </button>
                    </div>
                </div>

                <!-- Ongoing Drama Notify Me Button -->
                <?php if (!empty($status) && strcasecmp($status, 'ongoing') === 0) : ?>
                <div style="margin-bottom:28px; background:linear-gradient(135deg, rgba(245,158,11,0.12), rgba(16,185,129,0.12)); padding:18px; border-radius:var(--radius-md); border:1px solid #f59e0b; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                    <div>
                        <h4 style="color:#f59e0b; margin:0 0 4px 0; font-size:1rem; font-weight:800;"><i class="fa-solid fa-bell"></i> Ongoing Drama Alert</h4>
                        <p style="margin:0; color:var(--text-muted); font-size:0.85rem;">Get notified via email when next episodes are released!</p>
                    </div>
                    <button type="button" id="btn-open-notify-modal" style="background:#f59e0b; color:#000; font-weight:800; border:none; padding:9px 18px; border-radius:8px; cursor:pointer; font-size:0.88rem; display:inline-flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-envelope-open-text"></i> Notify Me
                    </button>
                </div>
                <?php endif; ?>

                <!-- Multi-Resolution Download Link Auto-Generator -->
                <div style="margin-bottom:28px; background:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                    <h3 style="color:#fff; font-size:1.1rem; margin:0 0 14px 0; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-cloud-arrow-down" style="color:var(--accent-green);"></i> Direct Episode Download Links (Multi-Resolution)
                    </h3>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:12px;">
                        <a href="javascript:void(0);" onclick="alert('Starting 4K Ultra HD Stream Download...');" style="background:rgba(0,255,136,0.12); color:var(--accent-green); border:1px solid var(--accent-green); border-radius:8px; padding:10px 14px; text-decoration:none; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span><i class="fa-solid fa-file-video"></i> 2160p 4K UHD</span>
                            <span style="font-size:0.7rem; background:var(--accent-green); color:#000; padding:2px 6px; border-radius:4px; font-weight:900;">2.4 GB</span>
                        </a>
                        <a href="javascript:void(0);" onclick="alert('Starting 1080p Full HD Stream Download...');" style="background:rgba(0,212,255,0.12); color:var(--accent-cyan); border:1px solid var(--accent-cyan); border-radius:8px; padding:10px 14px; text-decoration:none; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span><i class="fa-solid fa-file-video"></i> 1080p Full HD</span>
                            <span style="font-size:0.7rem; background:var(--accent-cyan); color:#000; padding:2px 6px; border-radius:4px; font-weight:900;">1.1 GB</span>
                        </a>
                        <a href="javascript:void(0);" onclick="alert('Starting 720p HD Stream Download...');" style="background:rgba(255,183,3,0.12); color:var(--accent-gold); border:1px solid var(--accent-gold); border-radius:8px; padding:10px 14px; text-decoration:none; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span><i class="fa-solid fa-file-video"></i> 720p Fast HD</span>
                            <span style="font-size:0.7rem; background:var(--accent-gold); color:#000; padding:2px 6px; border-radius:4px; font-weight:900;">550 MB</span>
                        </a>
                        <a href="javascript:void(0);" onclick="alert('Starting 480p Mobile Download...');" style="background:rgba(255,45,107,0.12); color:var(--accent-magenta); border:1px solid var(--accent-magenta); border-radius:8px; padding:10px 14px; text-decoration:none; font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:space-between;">
                            <span><i class="fa-solid fa-mobile-screen"></i> 480p Mobile</span>
                            <span style="font-size:0.7rem; background:var(--accent-magenta); color:#fff; padding:2px 6px; border-radius:4px; font-weight:900;">280 MB</span>
                        </a>
                    </div>
                </div>

                <!-- Quick Emoji Reactions -->
                <div style="margin-bottom:28px; background:var(--bg-card); padding:18px 22px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                    <h4 style="color:#fff; font-size:0.98rem; margin:0 0 12px 0; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-face-smile-beam" style="color:var(--accent-gold);"></i> Episode Quick Reactions
                    </h4>
                    <div style="display:flex; gap:12px; flex-wrap:wrap;" id="me-emoji-reactions-box">
                        <?php
                        $reactions = get_post_meta($post_id, '_emoji_reactions', true) ?: array('🔥' => 12, '😱' => 5, '😭' => 8, '💖' => 18, '👏' => 10);
                        foreach ($reactions as $em => $cnt) :
                        ?>
                        <button type="button" class="btn-emoji-react" data-emoji="<?php echo esc_attr($em); ?>" style="background:rgba(255,255,255,0.06); color:#fff; border:1px solid var(--border-color); padding:8px 16px; border-radius:20px; font-size:1.1rem; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:all 0.2s;">
                            <span><?php echo esc_html($em); ?></span>
                            <span class="emoji-count" style="font-size:0.82rem; color:var(--accent-cyan);"><?php echo intval($cnt); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Ratings & Reviews -->
                <div style="margin-bottom:30px; background:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                    <h3 style="color:#fff; font-size:1.15rem; margin-bottom:16px;"><i class="fa-solid fa-comments" style="color:var(--accent-cyan);"></i> User Ratings & Reviews</h3>
                    <?php if (function_exists('movie_elite_render_ratings_reviews')) movie_elite_render_ratings_reviews($post_id); ?>
                </div>

                <!-- Smart AI Recommendations -->
                <h3 style="color:#fff; font-size:1.2rem; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--accent-cyan);"></i> Related TV Shows & Dramas (AI Recommendations)
                </h3>
                <div class="movies-grid">
                    <?php
                    $related_query = function_exists('movie_elite_get_related_titles')
                        ? movie_elite_get_related_titles($post_id, 6)
                        : new WP_Query(array('post_type' => array('movies', 'tvshows'), 'post_status' => 'publish', 'posts_per_page' => 6, 'post__not_in' => array($post_id), 'orderby' => 'rand'));

                    if ($related_query->have_posts()) :
                        while ($related_query->have_posts()) : $related_query->the_post();
                            movie_elite_render_card_item();
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Trailer Modal Container -->
<div id="trailer-modal" class="me-modal-overlay" style="display:none;">
    <div class="me-modal-box">
        <button type="button" id="btn-close-trailer" class="me-modal-close">&times;</button>
        <h3 style="color:#fff; margin:0 0 15px 0;"><i class="fa-solid fa-film" style="color:var(--accent-cyan);"></i> Official Trailer</h3>
        <div class="iframe-player-wrapper" id="trailer-iframe-box"></div>
    </div>
</div>

<!-- Notification Subscription Modal -->
<div id="notify-modal" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:20px;">
    <div style="background:var(--bg-secondary); border-radius:16px; border:1px solid var(--border-color); max-width:450px; width:100%; padding:25px; box-shadow:0 20px 40px rgba(0,0,0,0.5);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="margin:0; color:#fff; font-size:1.1rem;"><i class="fa-solid fa-bell" style="color:#f59e0b;"></i> Episode Release Alerts</h3>
            <button type="button" id="btn-close-notify" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:18px;">Subscribing to <strong><?php echo esc_html($title); ?></strong>. We will send an instant alert email when new episodes drop!</p>
        <form id="form-notify-sub">
            <input type="email" id="notify-email-input" placeholder="Enter your email address..." style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-primary); color:#fff; margin-bottom:15px; box-sizing:border-box;" required />
            <button type="submit" style="width:100%; background:#f59e0b; color:#000; font-weight:900; padding:10px; border-radius:8px; border:none; cursor:pointer; font-size:0.95rem;">
                Subscribe Now
            </button>
        </form>
        <div id="notify-msg-box" style="margin-top:12px; display:none; font-size:0.85rem; font-weight:700;"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // 1. Continue Watching History Recorder
    (function() {
        var postId = <?php echo $post_id; ?>;
        var title = <?php echo json_encode(get_the_title()); ?>;
        var poster = <?php echo json_encode(get_post_meta($post_id, 'poster_url', true)); ?>;
        var permalink = <?php echo json_encode(get_permalink()); ?>;
        var currentEp = <?php echo isset($selected_ep) ? intval($selected_ep) : 1; ?>;
        var totalEps = <?php echo intval(get_post_meta($post_id, 'total_episodes', true) ?: 1); ?>;
        
        var history = [];
        try {
            history = JSON.parse(localStorage.getItem('movie_elite_continue_watching') || '[]');
        } catch(e) {}
        
        history = history.filter(function(item) { return item.id !== postId; });
        history.unshift({
            id: postId,
            title: title,
            poster: poster,
            permalink: permalink,
            ep: currentEp,
            totalEps: totalEps,
            pct: Math.round((currentEp / totalEps) * 100),
            time: Date.now()
        });
        
        if (history.length > 10) history = history.slice(0, 10);
        localStorage.setItem('movie_elite_continue_watching', JSON.stringify(history));
    })();

    // 2. Ongoing Drama Notify Modal Handlers
    $('#btn-open-notify-modal').on('click', function() {
        $('#notify-modal').css('display', 'flex');
    });

    $('#btn-close-notify').on('click', function() {
        $('#notify-modal').hide();
    });

    $('#form-notify-sub').on('submit', function(e) {
        e.preventDefault();
        var email = $('#notify-email-input').val().trim();
        if (!email) return;

        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Subscribing...');

        $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
            action: 'movie_elite_subscribe_episode_notify',
            email: email,
            post_id: <?php echo $post_id; ?>
        }, function(resp) {
            btn.prop('disabled', false).text('Subscribe Now');
            if (resp.success) {
                $('#notify-msg-box').css('color', '#10b981').text(resp.data.message).show();
                setTimeout(function() { $('#notify-modal').hide(); }, 2500);
            } else {
                $('#notify-msg-box').css('color', '#ef4444').text(resp.data.message).show();
            }
        });
    });

    // 3. Embed Server Health & Auto Fallback Engine
    var serverBtns = $('.server-btn');
    if (serverBtns.length > 0) {
        serverBtns.first().addClass('fastest-server');
        if (serverBtns.first().find('.fast-badge').length === 0) {
            serverBtns.first().append(' <span class="fast-badge" style="background:#10b981; color:#000; font-weight:900; font-size:0.65rem; padding:2px 6px; border-radius:4px; margin-left:4px;">⚡ FAST</span>');
        }
    }
});
</script>

<?php
endwhile;
get_footer();
?>

<script>
(function() {
    'use strict';

    var hasDcEpisodes = <?php echo $has_dc_episodes ? 'true' : 'false'; ?>;
    var cleanTmdb     = '<?php echo esc_js($clean_tmdb); ?>';

    // ---------- Server tab switcher (shared for all TV shows) ----------
    document.querySelectorAll('.server-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.server-tab').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var iframe = document.getElementById('main-movie-iframe');
            if (iframe && this.dataset.url) {
                iframe.src = this.dataset.url;
            }
        });
    });

    // ---------- Episode selector ----------
    document.querySelectorAll('.btn-episode-select').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Active state
            document.querySelectorAll('.btn-episode-select').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');

            var iframe = document.getElementById('main-movie-iframe');

            if (hasDcEpisodes) {
                // DramaCool drama: swap server buttons to this episode's real servers
                var serversRaw = this.getAttribute('data-servers');
                var servers = [];
                try { servers = JSON.parse(serversRaw || '[]'); } catch(e) {}

                var bar = document.querySelector('.server-switcher-bar');
                if (bar && servers.length) {
                    // Remove old server buttons (keep the label span)
                    bar.querySelectorAll('.server-tab').forEach(function(b) { b.remove(); });

                    servers.forEach(function(srv, idx) {
                        var newBtn = document.createElement('button');
                        newBtn.type = 'button';
                        newBtn.className = 'server-tab' + (idx === 0 ? ' active' : '');
                        newBtn.setAttribute('data-url', srv.url || '');
                        newBtn.innerHTML = '<i class="fa-solid fa-play"></i> ' + (srv.name || ('Server ' + (idx + 1)));
                        newBtn.addEventListener('click', function() {
                            bar.querySelectorAll('.server-tab').forEach(function(b) { b.classList.remove('active'); });
                            this.classList.add('active');
                            if (iframe && this.dataset.url) { iframe.src = this.dataset.url; }
                        });
                        bar.appendChild(newBtn);
                    });

                    // Load first server
                    if (iframe && servers[0] && servers[0].url) {
                        iframe.src = servers[0].url;
                    }
                }
            } else {
                // Regular TV show: update episode number in all server URLs
                var epNum  = this.getAttribute('data-episode') || '1';
                var selSeason = document.getElementById('season-selector-select');
                var seasonNum = selSeason ? selSeason.value : '1';

                document.querySelectorAll('.server-tab').forEach(function(b) {
                    var url = b.getAttribute('data-url') || '';
                    // Replace /S/E pattern in URL  e.g. /1/1 -> /1/2
                    url = url.replace(/\/\d+\/\d+$/, '/' + seasonNum + '/' + epNum);
                    b.setAttribute('data-url', url);
                    if (b.classList.contains('active') && iframe) {
                        iframe.src = url;
                    }
                });
            }
        });
    });

    // Season change for regular TV shows
    var seasonSelect = document.getElementById('season-selector-select');
    if (seasonSelect) {
        seasonSelect.addEventListener('change', function() {
            if (hasDcEpisodes) return; // DramaCool dramas are single-season
            var seasonNum = this.value;
            var epNum = '1';
            var activeEp = document.querySelector('.btn-episode-select.active');
            if (activeEp) { epNum = activeEp.getAttribute('data-episode') || '1'; }

            var iframe = document.getElementById('main-movie-iframe');
            document.querySelectorAll('.server-tab').forEach(function(b) {
                var url = b.getAttribute('data-url') || '';
                url = url.replace(/\/\d+\/\d+$/, '/' + seasonNum + '/' + epNum);
                b.setAttribute('data-url', url);
                if (b.classList.contains('active') && iframe) { iframe.src = url; }
            });
        });
    }

    // Lights Off toggle
    var lightsBtn = document.getElementById('btn-toggle-lights');
    var lightsOverlay = document.getElementById('lights-off-overlay');
    var lightsBtnText = document.getElementById('lights-btn-text');
    if (lightsBtn && lightsOverlay) {
        lightsBtn.addEventListener('click', function() {
            var isOff = lightsOverlay.style.display !== 'none';
            lightsOverlay.style.display = isOff ? 'none' : 'block';
            if (lightsBtnText) { lightsBtnText.textContent = isOff ? 'Lights Off' : 'Lights On'; }
        });
    }

    // Theater Mode toggle
    var expandBtn = document.getElementById('btn-toggle-expand');
    var playerBox = document.getElementById('player-container-box');
    if (expandBtn && playerBox) {
        expandBtn.addEventListener('click', function() {
            playerBox.classList.toggle('theater-mode-active');
        });
    }
})();
</script>

<?php
get_footer();
?>
