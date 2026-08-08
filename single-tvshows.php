<?php
/**
 * MovieElite Pro - Single TV Show Detail View (With Dynamic Admin Embed Manager Filtering)
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
    $poster    = get_post_meta($post_id, 'poster_url', true);
    $backdrop  = get_post_meta($post_id, 'backdrop_url', true) ?: $poster;

    // TV Show Seasons & Episodes
    $seasons   = intval(get_post_meta($post_id, 'total_seasons', true) ?: 1);
    $episodes  = intval(get_post_meta($post_id, 'total_episodes', true) ?: 12);

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

    // Dynamic TV Embed Player Sources (Respects Admin Embed Manager Settings & Active Filter)
    $embeds = function_exists('movie_elite_generate_tv_embeds') ? movie_elite_generate_tv_embeds($clean_imdb, $clean_tmdb, 1, 1) : array();
?>

<!-- Lights Off Overlay -->
<div id="lights-off-overlay" style="display:none; position:fixed; inset:0; z-index:999; background:rgba(0,0,0,0.95); transition:all 0.3s;"></div>

<main class="main-content single-movie-wrapper">
    <div class="container">
        
        <!-- Breadcrumbs -->
        <div style="margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; 
            <a href="<?php echo esc_url(home_url('/tvshows/')); ?>">TV Shows</a> &nbsp;/&nbsp; 
            <span style="color:#fff;"><?php echo esc_html($title); ?></span>
        </div>

        <!-- TV Show Player Container -->
        <div class="movie-player-container" id="player-container-box" style="position:relative; z-index:1000; transition:all 0.3s; margin-bottom: 40px;">
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
            <div style="background:#111520; padding:14px 24px; border-bottom:1px solid var(--border-color); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:15px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-weight:800; font-size:0.85rem; color:var(--accent-gold);"><i class="fa-solid fa-layer-group"></i> SEASON:</span>
                    <select id="season-selector-select" style="background:#181e2d; color:#fff; border:1px solid var(--border-color); padding:6px 14px; border-radius:6px; font-weight:700; cursor:pointer;">
                        <?php for ($s = 1; $s <= $seasons; $s++) : ?>
                        <option value="<?php echo $s; ?>">Season <?php echo $s; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="font-weight:800; font-size:0.85rem; color:var(--accent-cyan);"><i class="fa-solid fa-list-ol"></i> EPISODES:</span>
                    <div id="episodes-grid-list" style="display:flex; gap:6px; flex-wrap:wrap;">
                        <?php for ($e = 1; $e <= min($episodes, 30); $e++) : ?>
                        <button type="button" class="alphabet-btn btn-episode-select <?php echo ($e === 1) ? 'active' : ''; ?>" data-episode="<?php echo $e; ?>" style="min-width:36px;">
                            Ep <?php echo $e; ?>
                        </button>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Embed Player Frame with referrer & autoplay attributes -->
            <div class="iframe-player-wrapper">
                <iframe id="main-movie-iframe" src="<?php echo esc_url($embeds[0]['url'] ?? "https://vidsrc.sbs/embed/tv/{$clean_tmdb}/1/1"); ?>" allow="autoplay; fullscreen; picture-in-picture; encrypted-media" referrerpolicy="origin-when-cross-origin" allowfullscreen></iframe>
            </div>

            <!-- Advanced Player Controls Sub-Bar -->
            <div style="padding: 14px 24px; display: flex; flex-wrap:wrap; align-items: center; justify-content: space-between; gap:15px; background: #0d1017; font-size: 0.85rem;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="color:var(--accent-green); font-weight:700;"><i class="fa-solid fa-circle-check"></i> TV Show Player Verified</span>
                </div>

                <!-- Advanced Action Buttons -->
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="button" id="btn-toggle-lights" class="alphabet-btn" style="background:rgba(255,183,3,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold);">
                        <i class="fa-solid fa-lightbulb"></i> <span id="lights-btn-text">Lights Off</span>
                    </button>

                    <button type="button" id="btn-toggle-expand" class="alphabet-btn" style="background:rgba(0,242,254,0.15); color:var(--accent-cyan); border:1px solid var(--accent-cyan);">
                        <i class="fa-solid fa-expand"></i> Theater Mode
                    </button>

                    <!-- Direct VidVault Download Link Button on Player Sub-Bar -->
                    <a href="<?php echo esc_url($primary_download_url); ?>" target="_blank" rel="noopener" class="alphabet-btn" style="background:rgba(0,255,136,0.15); color:var(--accent-green); border:1px solid var(--accent-green); text-decoration:none;">
                        <i class="fa-solid fa-download"></i> Download Episode
                    </a>

                    <button type="button" class="alphabet-btn" onclick="document.getElementById('main-movie-iframe').src=document.getElementById('main-movie-iframe').src;" style="background:rgba(255,255,255,0.08);">
                        <i class="fa-solid fa-rotate-right"></i> Reload
                    </button>
                </div>
            </div>
        </div>

        <!-- TV Show Details & Metadata -->
        <div style="display:grid; grid-template-columns: 280px 1fr; gap:35px;">
            <!-- Poster Sidebar -->
            <div>
                <div style="position:relative; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border-color); box-shadow:0 15px 30px rgba(0,0,0,0.5);">
                    <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" style="width:100%; display:block;" />
                </div>
            </div>

            <!-- Info Details -->
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <span class="slide-badge" style="margin:0;"><i class="fa-solid fa-tv"></i> <?php echo esc_html(implode(', ', $genre_names)); ?></span>
                    <span style="background:var(--accent-gold); color:#000; font-weight:900; padding:2px 8px; border-radius:4px; font-size:0.8rem;"><i class="fa-solid fa-star"></i> IMDb <?php echo esc_html($rating); ?></span>
                </div>

                <h1 style="font-size: 2.2rem; font-weight: 900; color: #fff; margin-bottom: 12px; line-height: 1.2;"><?php echo esc_html($title); ?></h1>
                
                <div style="display:flex; align-items:center; gap:20px; color:var(--text-muted); font-size:0.9rem; margin-bottom:20px;">
                    <span><i class="fa-solid fa-calendar-days" style="color:var(--accent-cyan);"></i> Release: <?php echo esc_html($year); ?></span>
                    <span><i class="fa-solid fa-layer-group" style="color:var(--accent-gold);"></i> Seasons: <?php echo esc_html($seasons); ?></span>
                    <span><i class="fa-solid fa-list-ol" style="color:var(--accent-green);"></i> Episodes: <?php echo esc_html($episodes); ?></span>
                    <span><i class="fa-solid fa-video" style="color:var(--accent-green);"></i> Quality: <?php echo esc_html($quality); ?></span>
                </div>

                <h3 style="color:#fff; font-size:1.2rem; margin-bottom:10px;">Storyline / Overview</h3>
                <div style="background:var(--bg-card); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color); color:var(--text-muted); font-size:0.95rem; line-height:1.7; margin-bottom:30px;">
                    <?php the_content(); ?>
                </div>

                <!-- Related TV Shows -->
                <h3 style="color:#fff; font-size:1.3rem; margin-bottom:15px;">Related TV Shows & Dramas</h3>
                <div class="movies-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
                    <?php
                    $related_query = new WP_Query(array(
                        'post_type'      => 'tvshows',
                        'post_status'    => 'publish',
                        'posts_per_page' => 4,
                        'post__not_in'   => array($post_id),
                        'orderby'        => 'rand'
                    ));

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

<?php
endwhile;
get_footer();
?>
