<?php
/**
 * MovieElite Pro - Dedicated Built-in Meta Box
 * Automatically renders clean input fields for IMDb ID, TMDb ID, Rating, Quality, Year, Poster,
 * Backdrop, Seasons, Episodes, and Download links (No manual custom field typing required!).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Meta Box for Movies & TV Shows
 */
function movie_elite_register_meta_boxes() {
    add_meta_box(
        'movie_elite_options_metabox',
        '🎬 Movie & TV Show Details, Player & Download Options',
        'movie_elite_render_meta_box',
        'movies',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'movie_elite_register_meta_boxes');

/**
 * Render Meta Box HTML
 */
function movie_elite_render_meta_box($post) {
    wp_nonce_field('movie_elite_save_meta_box', 'movie_elite_meta_box_nonce');

    // Retrieve saved meta values
    $imdb_id           = get_post_meta($post->ID, 'imdb_id', true);
    $tmdb_id           = get_post_meta($post->ID, 'tmdb_id', true);
    $imdb_rating       = get_post_meta($post->ID, 'imdb_rating', true) ?: '8.5';
    $release_year      = get_post_meta($post->ID, 'release_year', true) ?: date('Y');
    $movie_quality     = get_post_meta($post->ID, 'movie_quality', true) ?: '4K UHD';
    $poster_url        = get_post_meta($post->ID, 'poster_url', true);
    $backdrop_url      = get_post_meta($post->ID, 'backdrop_url', true);
    $primary_embed_url = get_post_meta($post->ID, 'primary_embed_url', true);
    $total_seasons     = get_post_meta($post->ID, 'total_seasons', true) ?: '1';
    $total_episodes    = get_post_meta($post->ID, 'total_episodes', true) ?: '12';
    $download_720p     = get_post_meta($post->ID, 'download_url_720p', true);
    $download_1080p    = get_post_meta($post->ID, 'download_url_1080p', true);
    $download_4k       = get_post_meta($post->ID, 'download_url_4k', true);
    ?>

    <style>
        .movie-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .movie-meta-field { display: flex; flex-direction: column; gap: 5px; }
        .movie-meta-field label { font-weight: 700; color: #23282d; font-size: 13px; }
        .movie-meta-field input, .movie-meta-field select { padding: 8px 12px; border-radius: 4px; border: 1px solid #ccd0d4; }
        .movie-meta-section { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e2e4e7; margin-bottom: 15px; }
        .movie-meta-section h4 { margin: 0 0 10px 0; color: #007cba; display: flex; align-items: center; gap: 8px; }
    </style>

    <!-- Section 1: Basic Identifiers & Ratings -->
    <div class="movie-meta-section">
        <h4><span class="dashicons dashicons-video-alt3"></span> Basic Metadata & Identifiers</h4>
        <div class="movie-meta-grid">
            <div class="movie-meta-field">
                <label for="imdb_id">IMDb ID:</label>
                <input type="text" id="imdb_id" name="imdb_id" value="<?php echo esc_attr($imdb_id); ?>" placeholder="e.g. tt1630029" />
            </div>
            <div class="movie-meta-field">
                <label for="tmdb_id">TMDb ID:</label>
                <input type="text" id="tmdb_id" name="tmdb_id" value="<?php echo esc_attr($tmdb_id); ?>" placeholder="e.g. 76600" />
            </div>
            <div class="movie-meta-field">
                <label for="imdb_rating">IMDb Score / Rating:</label>
                <input type="text" id="imdb_rating" name="imdb_rating" value="<?php echo esc_attr($imdb_rating); ?>" placeholder="e.g. 8.5" />
            </div>
            <div class="movie-meta-field">
                <label for="release_year">Release Year:</label>
                <input type="number" id="release_year" name="release_year" value="<?php echo esc_attr($release_year); ?>" placeholder="e.g. 2026" />
            </div>
            <div class="movie-meta-field">
                <label for="movie_quality">Video Quality Tag:</label>
                <select id="movie_quality" name="movie_quality">
                    <option value="4K UHD" <?php selected($movie_quality, '4K UHD'); ?>>4K Ultra HD</option>
                    <option value="1080p Full HD" <?php selected($movie_quality, '1080p Full HD'); ?>>1080p Full HD</option>
                    <option value="720p HD" <?php selected($movie_quality, '720p HD'); ?>>720p HD</option>
                    <option value="CAM Rip" <?php selected($movie_quality, 'CAM Rip'); ?>>CAM Rip</option>
                </select>
            </div>
            <div class="movie-meta-field">
                <label for="primary_embed_url">Custom Embed Player URL (Optional):</label>
                <input type="text" id="primary_embed_url" name="primary_embed_url" value="<?php echo esc_attr($primary_embed_url); ?>" placeholder="Auto-generated if left empty" />
            </div>
        </div>
    </div>

    <!-- Section 2: TV Shows & Asian Dramas (Seasons & Episodes) -->
    <div class="movie-meta-section">
        <h4><span class="dashicons dashicons-slides"></span> TV Shows & Asian Drama Details</h4>
        <div class="movie-meta-grid">
            <div class="movie-meta-field">
                <label for="total_seasons">Total Seasons:</label>
                <input type="number" id="total_seasons" name="total_seasons" value="<?php echo esc_attr($total_seasons); ?>" min="1" max="50" placeholder="e.g. 1, 2, 3..." />
            </div>
            <div class="movie-meta-field">
                <label for="total_episodes">Total Episodes per Season:</label>
                <input type="number" id="total_episodes" name="total_episodes" value="<?php echo esc_attr($total_episodes); ?>" min="1" max="100" placeholder="e.g. 12, 16, 24..." />
            </div>
        </div>
    </div>

    <!-- Section 3: Media Posters & Backdrops -->
    <div class="movie-meta-section">
        <h4><span class="dashicons dashicons-format-image"></span> Poster & Backdrop Images</h4>
        <div class="movie-meta-grid">
            <div class="movie-meta-field">
                <label for="poster_url">Poster Image URL:</label>
                <input type="text" id="poster_url" name="poster_url" value="<?php echo esc_attr($poster_url); ?>" placeholder="https://image.tmdb.org/t/p/w500/..." />
            </div>
            <div class="movie-meta-field">
                <label for="backdrop_url">Backdrop Banner URL:</label>
                <input type="text" id="backdrop_url" name="backdrop_url" value="<?php echo esc_attr($backdrop_url); ?>" placeholder="https://image.tmdb.org/t/p/w1280/..." />
            </div>
        </div>
    </div>

    <!-- Section 4: Download Links -->
    <div class="movie-meta-section">
        <h4><span class="dashicons dashicons-download"></span> Direct Download Links</h4>
        <div class="movie-meta-grid">
            <div class="movie-meta-field">
                <label for="download_url_720p">720p HD Download URL:</label>
                <input type="text" id="download_url_720p" name="download_url_720p" value="<?php echo esc_attr($download_720p); ?>" placeholder="https://download.site/movie-720p.mp4" />
            </div>
            <div class="movie-meta-field">
                <label for="download_url_1080p">1080p Full HD Download URL:</label>
                <input type="text" id="download_url_1080p" name="download_url_1080p" value="<?php echo esc_attr($download_1080p); ?>" placeholder="https://download.site/movie-1080p.mp4" />
            </div>
            <div class="movie-meta-field" style="grid-column: 1 / -1;">
                <label for="download_url_4k">4K Ultra HD Download URL:</label>
                <input type="text" id="download_url_4k" name="download_url_4k" value="<?php echo esc_attr($download_4k); ?>" placeholder="https://download.site/movie-4k.mkv" />
            </div>
        </div>
    </div>

    <?php
}

/**
 * Save Meta Box Values
 */
function movie_elite_save_meta_box_data($post_id) {
    if (!isset($_POST['movie_elite_meta_box_nonce']) || !wp_verify_nonce($_POST['movie_elite_meta_box_nonce'], 'movie_elite_save_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        'imdb_id', 'tmdb_id', 'imdb_rating', 'release_year', 'movie_quality',
        'poster_url', 'backdrop_url', 'primary_embed_url',
        'total_seasons', 'total_episodes',
        'download_url_720p', 'download_url_1080p', 'download_url_4k'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Refresh multi-embed array if IMDb or TMDb ID updated
    $imdb_id = sanitize_text_field($_POST['imdb_id'] ?? '');
    $tmdb_id = sanitize_text_field($_POST['tmdb_id'] ?? '');

    if (function_exists('movie_elite_generate_movie_embeds') && (!empty($imdb_id) || !empty($tmdb_id))) {
        $embeds = movie_elite_generate_movie_embeds($imdb_id, $tmdb_id);
        update_post_meta($post_id, 'movie_embed_sources', $embeds);
    }
}
add_action('save_post_movies', 'movie_elite_save_meta_box_data');
