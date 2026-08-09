<?php
/**
 * MovieElite Pro - Meta Box for Movies & TV Shows
 *
 * Sections:
 * 1. Basic Metadata & Identifiers
 * 2. Manual Player Embeds (Repeatable) - becomes Server 1, 2, 3... if filled
 * 3. TV Show Seasons & Episodes
 * 4. Poster & Backdrop Images
 * 5. Download Links (Repeatable) - label + URL pairs
 */

if (!defined('ABSPATH')) {
    exit;
}

function movie_elite_register_meta_boxes() {
    add_meta_box(
        'movie_elite_options_metabox',
        '🎬 Movie Details, Players & Downloads',
        'movie_elite_render_meta_box',
        array('movies', 'tvshows'),
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'movie_elite_register_meta_boxes');

function movie_elite_render_meta_box($post) {
    wp_nonce_field('movie_elite_save_meta_box', 'movie_elite_meta_box_nonce');

    $post_type = get_post_type($post);
    $is_tv     = ($post_type === 'tvshows');
    $pid       = $post->ID;

    $imdb_id        = get_post_meta($pid, 'imdb_id', true);
    $tmdb_id        = get_post_meta($pid, 'tmdb_id', true);
    $imdb_rating    = get_post_meta($pid, 'imdb_rating', true) ?: '8.5';
    $release_year   = get_post_meta($pid, 'release_year', true) ?: date('Y');
    $movie_quality  = get_post_meta($pid, 'movie_quality', true) ?: '4K UHD';
    $poster_url     = get_post_meta($pid, 'poster_url', true);
    $backdrop_url   = get_post_meta($pid, 'backdrop_url', true);
    $total_seasons  = get_post_meta($pid, 'total_seasons', true) ?: '1';
    $total_episodes = get_post_meta($pid, 'total_episodes', true) ?: '12';

    // Repeatable Manual Players (empty by default unless user added links)
    $manual_players = get_post_meta($pid, 'manual_player_embeds', true);
    if (!is_array($manual_players)) {
        $manual_players = array();
    }

    // Repeatable Download Links
    $download_links = get_post_meta($pid, 'manual_download_links', true);
    if (empty($download_links) || !is_array($download_links)) {
        $download_links = array();
        $l720  = get_post_meta($pid, 'download_url_720p', true);
        $l1080 = get_post_meta($pid, 'download_url_1080p', true);
        $l4k   = get_post_meta($pid, 'download_url_4k', true);
        if ($l720)  { $download_links[] = array('label' => '720p HD',         'url' => $l720); }
        if ($l1080) { $download_links[] = array('label' => '1080p Full HD',   'url' => $l1080); }
        if ($l4k)   { $download_links[] = array('label' => '4K Ultra HD MKV', 'url' => $l4k); }
    }
    ?>
    <style>
        .me-meta-wrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
        .me-section{background:#f8f9fa;padding:16px 18px;border-radius:8px;border:1px solid #e2e4e7;margin-bottom:16px}
        .me-section h4{margin:0 0 12px 0;color:#1a56a7;font-size:13.5px;display:flex;align-items:center;gap:7px}
        .me-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .me-field{display:flex;flex-direction:column;gap:4px}
        .me-field label{font-weight:700;color:#23282d;font-size:12.5px}
        .me-field input,.me-field select{padding:7px 10px;border-radius:4px;border:1px solid #ccd0d4;font-size:13px;width:100%;box-sizing:border-box}
        .me-repeatable-list{display:flex;flex-direction:column;gap:8px;margin-bottom:10px}
        .me-repeat-row{display:grid;grid-template-columns:180px 1fr 36px;gap:8px;align-items:center;background:#fff;border:1px solid #ddd;border-radius:6px;padding:9px 12px}
        .me-repeat-row input{padding:6px 10px;border-radius:4px;border:1px solid #ccd0d4;font-size:13px;width:100%;box-sizing:border-box}
        .me-repeat-row input.url-input{font-family:monospace;font-size:12px}
        .me-btn-remove{background:#dc2626;color:#fff;border:none;border-radius:4px;width:32px;height:32px;cursor:pointer;font-size:16px;line-height:1;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .me-btn-remove:hover{background:#b91c1c}
        .me-btn-add{background:#0ea5e9;color:#fff;border:none;border-radius:6px;padding:7px 14px;cursor:pointer;font-size:12.5px;font-weight:700;display:inline-flex;align-items:center;gap:5px}
        .me-btn-add:hover{background:#0284c7}
        .me-notice{background:#fffbeb;border:1px solid #fbbf24;border-radius:6px;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:8px}
        .me-player-section{border-left:4px solid #0ea5e9 !important}
        .me-download-section{border-left:4px solid #16a34a !important}
    </style>

    <div class="me-meta-wrap">

        <div class="me-section">
            <h4><span class="dashicons dashicons-video-alt3"></span> Basic Metadata &amp; Identifiers</h4>
            <div class="me-grid">
                <div class="me-field">
                    <label for="imdb_id">IMDb ID:</label>
                    <input type="text" id="imdb_id" name="imdb_id" value="<?php echo esc_attr($imdb_id); ?>" placeholder="e.g. tt1630029" />
                </div>
                <div class="me-field">
                    <label for="tmdb_id">TMDb ID:</label>
                    <input type="text" id="tmdb_id" name="tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" placeholder="e.g. 76600" />
                </div>
                <div class="me-field">
                    <label for="imdb_rating">IMDb Rating:</label>
                    <input type="text" id="imdb_rating" name="imdb_rating" value="<?php echo esc_attr($imdb_rating); ?>" placeholder="e.g. 8.5" />
                </div>
                <div class="me-field">
                    <label for="release_year">Release Year:</label>
                    <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" placeholder="e.g. 2026" />
                </div>
                <div class="me-field" style="grid-column:1/-1">
                    <label for="movie_quality">Video Quality Tag:</label>
                    <select id="movie_quality" name="movie_quality" style="max-width:240px">
                        <option value="4K UHD"       <?php selected($movie_quality,'4K UHD'); ?>>4K Ultra HD</option>
                        <option value="1080p Full HD" <?php selected($movie_quality,'1080p Full HD'); ?>>1080p Full HD</option>
                        <option value="720p HD"       <?php selected($movie_quality,'720p HD'); ?>>720p HD</option>
                        <option value="CAM Rip"       <?php selected($movie_quality,'CAM Rip'); ?>>CAM Rip</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="me-section me-player-section">
            <h4><span class="dashicons dashicons-controls-play"></span> Manual Player Servers
                <span style="font-weight:400;font-size:11.5px;color:#555;margin-left:6px">(Zinaweza kuwa embed URL au iframe src — zitakuwa servers wa kwanza kabla ya auto-embed)</span>
            </h4>
            <div class="me-notice">
                💡 <strong>Jinsi inavyofanya kazi:</strong> Kila row inakuwa "Server 1", "Server 2" n.k. Kama hujajaza, player itatumia automatic embed (vidsrc, vsembed n.k.) kama kawaida. Label ni jina la server (e.g. "Server 1 (FastPlay)").
            </div>
            <div class="me-repeatable-list" id="player-rows-list">
                <?php foreach ($manual_players as $idx => $player) :
                    $pl = esc_attr($player['label'] ?? 'Server '.($idx+1));
                    $pu = esc_attr($player['url']   ?? '');
                ?>
                <div class="me-repeat-row">
                    <input type="text" name="manual_player_embeds[<?php echo $idx;?>][label]" value="<?php echo $pl;?>" placeholder="e.g. Server 1 (VidSrc)" />
                    <input type="text" name="manual_player_embeds[<?php echo $idx;?>][url]"   value="<?php echo $pu;?>" class="url-input" placeholder="https://vidsrc.to/embed/movie/tt... au link nyingine" />
                    <button type="button" class="me-btn-remove" onclick="this.closest('.me-repeat-row').remove()">&#x2715;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="me-btn-add" id="btn-add-player">&#xff0b; Ongeza Server / Player</button>
            <template id="player-row-tpl">
                <div class="me-repeat-row">
                    <input type="text" name="manual_player_embeds[__I__][label]" placeholder="e.g. Server 2 (AutoEmbed)" />
                    <input type="text" name="manual_player_embeds[__I__][url]"   placeholder="https://..." class="url-input" />
                    <button type="button" class="me-btn-remove" onclick="this.closest('.me-repeat-row').remove()">&#x2715;</button>
                </div>
            </template>
        </div>

        <div class="me-section" <?php echo $is_tv ? 'style="border-left:4px solid #7c3aed"' : ''; ?>>
            <h4><span class="dashicons dashicons-slides"></span> TV Show &amp; Drama — Seasons / Episodes</h4>
            <div class="me-grid">
                <div class="me-field">
                    <label for="total_seasons">Total Seasons:</label>
                    <input type="number" id="total_seasons" name="total_seasons" value="<?php echo esc_attr($total_seasons);?>" min="1" max="50" placeholder="e.g. 1, 2, 3..." />
                </div>
                <div class="me-field">
                    <label for="total_episodes">Total Episodes per Season:</label>
                    <input type="number" id="total_episodes" name="total_episodes" value="<?php echo esc_attr($total_episodes);?>" min="1" max="200" placeholder="e.g. 12, 16, 24..." />
                </div>
            </div>
        </div>

        <div class="me-section">
            <h4><span class="dashicons dashicons-format-image"></span> Poster &amp; Backdrop Images</h4>
            <div class="me-grid">
                <div class="me-field">
                    <label for="poster_url">Poster Image URL:</label>
                    <input type="text" id="poster_url" name="poster_url" value="<?php echo esc_attr($poster_url);?>" placeholder="https://image.tmdb.org/t/p/w500/..." />
                </div>
                <div class="me-field">
                    <label for="backdrop_url">Backdrop Banner URL:</label>
                    <input type="text" id="backdrop_url" name="backdrop_url" value="<?php echo esc_attr($backdrop_url);?>" placeholder="https://image.tmdb.org/t/p/w1280/..." />
                </div>
            </div>
        </div>

        <div class="me-section me-download-section">
            <h4><span class="dashicons dashicons-download"></span> Download Links
                <span style="font-weight:400;font-size:11.5px;color:#555;margin-left:6px">(Weka label + link — ongeza nyingi unavyotaka)</span>
            </h4>
            <div class="me-notice" style="background:#f0fdf4;border-color:#86efac;color:#166534">
                📥 Kila row ni download link moja. Label ni jina (e.g. "720p HD", "1080p Full HD", "Episode 1 4K"). URL ni link ya download.
            </div>
            <div class="me-repeatable-list" id="download-rows-list">
                <?php foreach ($download_links as $idx => $dl) :
                    $dl_l = esc_attr($dl['label'] ?? '');
                    $dl_u = esc_attr($dl['url']   ?? '');
                ?>
                <div class="me-repeat-row">
                    <input type="text" name="manual_download_links[<?php echo $idx;?>][label]" value="<?php echo $dl_l;?>" placeholder="e.g. 1080p Full HD" />
                    <input type="text" name="manual_download_links[<?php echo $idx;?>][url]"   value="<?php echo $dl_u;?>" class="url-input" placeholder="https://nurhost.mdandu.com/..." />
                    <button type="button" class="me-btn-remove" onclick="this.closest('.me-repeat-row').remove()">&#x2715;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="me-btn-add" id="btn-add-download" style="background:#16a34a">&#xff0b; Ongeza Download Link</button>
            <template id="download-row-tpl">
                <div class="me-repeat-row">
                    <input type="text" name="manual_download_links[__I__][label]" placeholder="e.g. 4K Ultra HD MKV" />
                    <input type="text" name="manual_download_links[__I__][url]"   placeholder="https://..." class="url-input" />
                    <button type="button" class="me-btn-remove" onclick="this.closest('.me-repeat-row').remove()">&#x2715;</button>
                </div>
            </template>
        </div>

    </div>

    <script>
    (function(){
        function initRep(btnId, listId, tplId) {
            var btn  = document.getElementById(btnId);
            var list = document.getElementById(listId);
            var tpl  = document.getElementById(tplId);
            if (!btn||!list||!tpl) return;
            btn.addEventListener('click', function(){
                var idx  = list.querySelectorAll('.me-repeat-row').length;
                var html = tpl.innerHTML.replace(/__I__/g, idx);
                var wrap = document.createElement('div');
                wrap.innerHTML = html;
                var row  = wrap.firstElementChild;
                list.appendChild(row);
                var fi = row.querySelector('input');
                if(fi) fi.focus();
            });
        }
        initRep('btn-add-player',   'player-rows-list',   'player-row-tpl');
        initRep('btn-add-download', 'download-rows-list', 'download-row-tpl');
    })();
    </script>
    <?php
}

function movie_elite_save_meta_box_data($post_id) {
    if (!isset($_POST['movie_elite_meta_box_nonce']) || !wp_verify_nonce($_POST['movie_elite_meta_box_nonce'], 'movie_elite_save_meta_box')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }

    $scalar = array('imdb_id','tmdb_id','imdb_rating','release_year','movie_quality','poster_url','backdrop_url','total_seasons','total_episodes');
    foreach ($scalar as $f) {
        if (isset($_POST[$f])) { update_post_meta($post_id, $f, sanitize_text_field($_POST[$f])); }
    }

    // Repeatable: players
    $raw_p = isset($_POST['manual_player_embeds']) ? (array)$_POST['manual_player_embeds'] : array();
    $clean_p = array();
    foreach ($raw_p as $row) {
        $u = esc_url_raw(trim($row['url'] ?? ''));
        $l = sanitize_text_field($row['label'] ?? '');
        if (!empty($u)) { $clean_p[] = array('label' => $l ?: 'Server', 'url' => $u); }
    }
    update_post_meta($post_id, 'manual_player_embeds', $clean_p);
    if (!empty($clean_p)) {
        update_post_meta($post_id, 'primary_embed_url', $clean_p[0]['url']);
    } else {
        delete_post_meta($post_id, 'primary_embed_url');
    }

    // Repeatable: downloads
    $raw_d = isset($_POST['manual_download_links']) ? (array)$_POST['manual_download_links'] : array();
    $clean_d = array();
    foreach ($raw_d as $row) {
        $u = esc_url_raw(trim($row['url'] ?? ''));
        $l = sanitize_text_field($row['label'] ?? '');
        if (!empty($u)) { $clean_d[] = array('label' => $l ?: 'Download', 'url' => $u); }
    }
    update_post_meta($post_id, 'manual_download_links', $clean_d);
    update_post_meta($post_id, 'download_url_720p',  $clean_d[0]['url'] ?? '');
    update_post_meta($post_id, 'download_url_1080p', $clean_d[1]['url'] ?? '');
    update_post_meta($post_id, 'download_url_4k',    $clean_d[2]['url'] ?? '');

    // Refresh auto embeds
    $iid = sanitize_text_field($_POST['imdb_id'] ?? '');
    $tid = sanitize_text_field($_POST['tmdb_id'] ?? '');
    if (function_exists('movie_elite_generate_movie_embeds') && (!empty($iid)||!empty($tid))) {
        update_post_meta($post_id, 'movie_embed_sources', movie_elite_generate_movie_embeds($iid, $tid));
    }
}
add_action('save_post_movies',  'movie_elite_save_meta_box_data');
add_action('save_post_tvshows', 'movie_elite_save_meta_box_data');
