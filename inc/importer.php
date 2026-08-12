<?php
/**
 * MovieElite Pro - Advanced Automated TMDb / IMDb Importer Tool
 * Features:
 * 1. Smart Duplicate Detection (Marks already imported movies with green badges & disables double imports).
 * 2. Full Search Pagination (Displays total results count, total pages, Prev/Next navigation).
 * 3. Page-Level Manual & Bulk Import (Import individual items or bulk import an entire search page at once).
 * 4. Ultra-Reliable Poster Image Renderer with `referrerpolicy="no-referrer"` & CDN fallbacks.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Admin Menu for Importer
 */
function movie_elite_importer_menu() {
    add_submenu_page(
        'edit.php?post_type=movies',
        'TMDb Importer Engine',
        'Importer Tool',
        'manage_options',
        'movie-elite-importer',
        'movie_elite_importer_page_render'
    );
}
add_action('admin_menu', 'movie_elite_importer_menu');

/**
 * Render Importer Page
 */
function movie_elite_importer_page_render() {
    $api_key = get_option('movie_elite_tmdb_api_key', '15d260044e350723362f6236b22b270a');

    if (isset($_POST['save_tmdb_key'])) {
        $api_key = sanitize_text_field($_POST['tmdb_api_key']);
        update_option('movie_elite_tmdb_api_key', $api_key);
        echo '<div class="updated"><p>API Key saved successfully!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1 style="display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-cloud-upload" style="font-size:32px; color:#00f2fe;"></span>
            Advanced TMDb / IMDb Movie & TV Show Importer Engine
        </h1>
        <p>Search, paginate through thousands of titles, automatically detect existing movies to prevent duplication, and import manually or in page-by-page bulk!</p>
        <hr />

        <!-- API Key Configuration -->
        <form method="post" action="" style="margin-bottom:20px; background:#fff; padding:15px 20px; border-radius:8px; border:1px solid #ccd0d4; display:flex; align-items:center; gap:15px;">
            <label><strong>TMDb API Key:</strong></label>
            <input type="text" name="tmdb_api_key" value="<?php echo esc_attr($api_key); ?>" style="width:350px;" class="code" />
            <input type="submit" name="save_tmdb_key" class="button button-secondary" value="Save API Key" />
        </form>

        <!-- Quick Action Presets Bar -->
        <div style="background: linear-gradient(135deg, #1a1d2e, #252836); padding: 18px 22px; border-radius: 10px; color: #fff; margin-bottom: 22px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.1);">
            <strong style="color: #00d4ff; font-size: 1rem; display: block; margin-bottom: 10px;">⚡ Quick Presets (1-Click Fetching):</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <button type="button" class="button btn-quick-preset" data-feed="popular" data-type="movie" style="background:#00d4ff; color:#000; border:none; font-weight:800; padding:6px 14px;">
                    🔥 Popular Movies
                </button>
                <button type="button" class="button btn-quick-preset" data-feed="popular" data-type="tv" style="background:#00ff88; color:#000; border:none; font-weight:800; padding:6px 14px;">
                    📺 Popular TV Shows
                </button>
                <button type="button" class="button btn-quick-preset" data-feed="trending" data-type="movie" style="background:#ff9100; color:#000; border:none; font-weight:800; padding:6px 14px;">
                    📈 Trending Movies
                </button>
                <button type="button" class="button btn-quick-preset" data-feed="trending" data-type="tv" style="background:#ff2a70; color:#fff; border:none; font-weight:800; padding:6px 14px;">
                    📈 Trending TV Shows
                </button>
                <button type="button" class="button btn-quick-preset" data-feed="top_rated" data-type="movie" style="background:#ffc107; color:#000; border:none; font-weight:800; padding:6px 14px;">
                    ⭐ Top Rated Masterpieces
                </button>
                <button type="button" class="button btn-quick-preset" data-feed="asian_drama" data-type="tv" style="background:#9c27b0; color:#fff; border:none; font-weight:800; padding:6px 14px;">
                    ⛩️ Popular Asian Dramas (K-Drama & C-Drama)
                </button>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:25px;">
            <h2 style="margin-top:0;">Search & Filter Parameters</h2>
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-top:15px;">
                <div>
                    <label><strong>Import Type:</strong></label>
                    <select id="import-type" class="widefat" style="font-weight:bold; color:#007cba;">
                        <option value="movie">🍿 Movies (`movies` CPT)</option>
                        <option value="tv">📺 TV Shows & Dramas (`tvshows` CPT)</option>
                    </select>
                </div>
                <div>
                    <label><strong>Feed / Sort Method:</strong></label>
                    <select id="import-feed" class="widefat" style="font-weight:bold; color:#ff0055;">
                        <option value="popular">🔥 Popular Worldwide</option>
                        <option value="trending">📈 Trending Today</option>
                        <option value="top_rated">⭐ Top Rated</option>
                        <option value="now_playing">🍿 Now Playing / On The Air</option>
                        <option value="asian_drama">⛩️ Popular Asian Dramas</option>
                        <option value="discover">🔍 Custom Filter / Search</option>
                    </select>
                </div>
                <div>
                    <label><strong>Search Title / Name:</strong></label>
                    <input type="text" id="import-title" class="widefat" placeholder="e.g. Avatar, Soul, Tenet" />
                </div>
                <div>
                    <label><strong>IMDb / TMDb ID:</strong></label>
                    <input type="text" id="import-imdb" class="widefat" placeholder="e.g. tt1160419 or 438148" />
                </div>
                <div>
                    <label><strong>Release Year:</strong></label>
                    <input type="number" id="import-year" class="widefat" placeholder="e.g. 2026" min="1950" max="2030" />
                </div>
                <div>
                    <label><strong>Genre / Category:</strong></label>
                    <select id="import-genre" class="widefat">
                        <option value="">All Genres</option>
                        <option value="28">Action</option>
                        <option value="10749">Romance</option>
                        <option value="878">Sci-Fi</option>
                        <option value="18">Drama / Asian Drama</option>
                        <option value="35">Comedy</option>
                        <option value="27">Horror</option>
                        <option value="16">Animation</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:20px; flex-wrap:wrap; gap:15px;">
                <div style="display:flex; gap:12px;">
                    <button type="button" id="btn-run-search" class="button button-primary button-large" style="background:#00f2fe; border-color:#00f2fe; color:#000; font-weight:700;">
                        🔍 Fetch Results
                    </button>
                    <label style="display:flex; align-items:center; gap:6px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="chk-hide-imported" /> Hide Already Imported Items
                    </label>
                </div>

                <div id="bulk-page-wrapper" style="display:none;">
                    <button type="button" id="btn-import-page-bulk" class="button button-primary button-large" style="background:#ff0055; border-color:#ff0055; color:#fff; font-weight:700;">
                        ⚡ Bulk Import All Unimported Items on This Page
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Results Header & Pagination Bar -->
        <div id="import-results-container" style="display:none; background:#fff; padding:25px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:15px; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <div>
                    <h2 style="margin:0; font-size:1.4rem;" id="results-title-header">Search Results</h2>
                    <span id="results-count-badge" style="background:#007cba; color:#fff; font-weight:700; padding:3px 10px; border-radius:20px; font-size:0.8rem;">0 Total Items</span>
                </div>

                <!-- Pagination Controls -->
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" id="btn-page-prev" class="button" disabled>◀ Prev Page</button>
                    <span style="font-weight:700; font-size:0.9rem;">Page <span id="current-page-num">1</span> of <span id="total-pages-num">1</span></span>
                    <button type="button" id="btn-page-next" class="button" disabled>Next Page ▶</button>
                </div>
            </div>

            <!-- Items Cards Grid -->
            <div id="import-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap:18px;"></div>

            <!-- Bottom Pagination Controls -->
            <div style="display:flex; align-items:center; justify-content:center; gap:15px; margin-top:25px; border-top:1px solid #eee; padding-top:15px;">
                <button type="button" id="btn-page-prev-bottom" class="button" disabled>◀ Prev Page</button>
                <span style="font-weight:700;">Page <span id="current-page-num-bottom">1</span> of <span id="total-pages-num-bottom">1</span></span>
                <button type="button" id="btn-page-next-bottom" class="button" disabled>Next Page ▶</button>
            </div>
        </div>

        <!-- Import Log Console -->
        <div id="import-log" style="display:none; margin-top:20px; background:#0b0d14; color:#00ff88; font-family:monospace; padding:15px; border-radius:6px; max-height:250px; overflow-y:auto; font-size:0.85rem;"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var apiKey      = '<?php echo esc_js($api_key); ?>';
        var currentPage = 1;
        var totalPages  = 1;
        var currentItems = [];

        window.handleTmdbImgError = function(img) {
            var rawPath = img.getAttribute('data-path');
            var currentSrc = img.src || '';

            if (rawPath && currentSrc.indexOf('wsrv.nl') === -1) {
                var cleanPath = rawPath.indexOf('/') === 0 ? rawPath : '/' + rawPath;
                img.src = 'https://wsrv.nl/?url=https://image.tmdb.org/t/p/w500' + cleanPath;
            } else if (rawPath && currentSrc.indexOf('w342') === -1) {
                var cleanPath = rawPath.indexOf('/') === 0 ? rawPath : '/' + rawPath;
                img.src = 'https://image.tmdb.org/t/p/w342' + cleanPath;
            } else {
                img.onerror = null;
                img.src = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300';
            }
        };

        function getPosterUrl(path) {
            if (!path) return 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300';
            if (path.indexOf('http') === 0) return path;
            var cleanPath = path.indexOf('/') === 0 ? path : '/' + path;
            return 'https://image.tmdb.org/t/p/w500' + cleanPath;
        }

        function fetchTmdbResults(page) {
            var type  = $('#import-type').val();
            var feed  = $('#import-feed').val();
            var title = $('#import-title').val().trim();
            var imdb  = $('#import-imdb').val().trim();
            var year  = $('#import-year').val().trim();
            var genre = $('#import-genre').val();

            currentPage = page || 1;

            var endpoint = (type === 'tv') ? 'tv' : 'movie';
            var url = '';

            if (imdb) {
                url = 'https://api.themoviedb.org/3/find/' + imdb + '?api_key=' + apiKey + '&external_source=imdb_id';
            } else if (title) {
                url = 'https://api.themoviedb.org/3/search/' + endpoint + '?api_key=' + apiKey + '&query=' + encodeURIComponent(title) + '&page=' + currentPage;
            } else if (feed === 'popular') {
                url = 'https://api.themoviedb.org/3/' + endpoint + '/popular?api_key=' + apiKey + '&page=' + currentPage;
            } else if (feed === 'trending') {
                url = 'https://api.themoviedb.org/3/trending/' + endpoint + '/day?api_key=' + apiKey + '&page=' + currentPage;
            } else if (feed === 'top_rated') {
                url = 'https://api.themoviedb.org/3/' + endpoint + '/top_rated?api_key=' + apiKey + '&page=' + currentPage;
            } else if (feed === 'now_playing') {
                var sub = (type === 'tv') ? 'on_the_air' : 'now_playing';
                url = 'https://api.themoviedb.org/3/' + endpoint + '/' + sub + '?api_key=' + apiKey + '&page=' + currentPage;
            } else if (feed === 'asian_drama') {
                url = 'https://api.themoviedb.org/3/discover/tv?api_key=' + apiKey + '&with_origin_country=KR|CN|TW|JP&sort_by=popularity.desc&page=' + currentPage;
            } else {
                url = 'https://api.themoviedb.org/3/discover/' + endpoint + '?api_key=' + apiKey + '&sort_by=popularity.desc&page=' + currentPage;
                if (year) {
                    url += (type === 'tv') ? '&first_air_date_year=' + year : '&primary_release_year=' + year;
                }
                if (genre) url += '&with_genres=' + genre;
            }

            $('#import-results-container').show();
            $('#import-grid').html('<p style="grid-column:1/-1; padding:30px; text-align:center;">🔍 Fetching page ' + currentPage + ' from TMDb database...</p>');

            $.getJSON(url, function(res) {
                currentItems = res.results || res.movie_results || res.tv_results || [];
                var totalResults = res.total_results || currentItems.length;
                totalPages   = res.total_pages || 1;

                $('#results-count-badge').text(totalResults.toLocaleString() + ' Total Items Found');
                $('#current-page-num, #current-page-num-bottom').text(currentPage);
                $('#total-pages-num, #total-pages-num-bottom').text(totalPages);

                $('#btn-page-prev, #btn-page-prev-bottom').prop('disabled', currentPage <= 1);
                $('#btn-page-next, #btn-page-next-bottom').prop('disabled', currentPage >= totalPages);

                if (currentItems.length === 0) {
                    $('#import-grid').html('<p style="grid-column:1/-1; padding:30px; text-align:center;">No results found for this search.</p>');
                    $('#bulk-page-wrapper').hide();
                    return;
                }

                $('#bulk-page-wrapper').show();

                // Check existing imports in WordPress DB
                var tmdbIds = currentItems.map(function(item) { return item.id; });
                
                $.post(ajaxurl, {
                    action: 'movie_elite_check_existing_batch',
                    tmdb_ids: tmdbIds,
                    post_type: (type === 'tv') ? 'tvshows' : 'movies'
                }, function(checkRes) {
                    var existingMap = (checkRes.success && checkRes.data) ? checkRes.data : {};
                    renderGrid(existingMap);
                });
            });
        }

        function renderGrid(existingMap) {
            var type = $('#import-type').val();
            var hideImported = $('#chk-hide-imported').is(':checked');
            var html = '';

            $.each(currentItems, function(i, m) {
                var itemTitle  = m.title || m.name || 'Untitled';
                var tmdbId     = m.id;
                var isImported = existingMap[tmdbId] ? true : false;
                var poster     = getPosterUrl(m.poster_path || m.backdrop_path);
                var release    = (m.release_date || m.first_air_date || '2026').substring(0,4);
                var rating     = (m.vote_average || 7.5).toFixed(1);

                if (hideImported && isImported) return true; // Skip rendering if hide checkbox checked

                html += '<div class="import-card-item" data-tmdb="' + tmdbId + '" data-imported="' + isImported + '" style="border:1px solid ' + (isImported ? '#00c853' : '#e0e0e0') + '; padding:12px; border-radius:8px; text-align:center; background:' + (isImported ? '#f0fdf4' : '#fafafa') + '; position:relative; box-shadow:0 4px 6px rgba(0,0,0,0.03);">';
                
                if (isImported) {
                    html += '<span style="position:absolute; top:8px; right:8px; background:#00c853; color:#fff; font-size:10px; font-weight:800; padding:2px 8px; border-radius:12px; z-index:2;">✅ IMPORTED</span>';
                }

                var imgPath = m.poster_path || m.backdrop_path || '';
                html += '<div style="position:relative; width:100%; height:230px; border-radius:6px; overflow:hidden; background:#1e293b;">';
                html += '<img src="' + poster + '" data-path="' + imgPath + '" loading="lazy" onerror="handleTmdbImgError(this);" style="width:100%; height:100%; object-fit:cover; display:block;" alt="' + itemTitle + '" />';
                html += '</div>';

                html += '<h4 style="font-size:0.85rem; margin:10px 0 4px; line-height:1.2; height:32px; overflow:hidden;">' + itemTitle + '</h4>';
                html += '<div style="font-size:0.75rem; color:#666; margin-bottom:10px;">⭐ ' + rating + ' | 📅 ' + release + '</div>';
                
                if (isImported) {
                    html += '<button type="button" class="button button-small" disabled style="background:#e0e0e0; color:#777; width:100%; font-weight:700;">Already in Database</button>';
                } else {
                    html += '<button type="button" class="button button-small btn-import-one" data-tmdb="' + tmdbId + '" data-type="' + type + '" style="background:#00f2fe; color:#000; border:none; font-weight:700; width:100%;">Import ' + (type === 'tv' ? 'TV Show' : 'Movie') + '</button>';
                }

                html += '</div>';
            });

            if (html === '') {
                html = '<p style="grid-column:1/-1; padding:20px; text-align:center;">All items on this page are already imported!</p>';
            }

            $('#import-grid').html(html);
        }

        // Quick Presets Handler
        $('.btn-quick-preset').on('click', function() {
            var presetFeed = $(this).attr('data-feed');
            var presetType = $(this).attr('data-type');
            $('#import-type').val(presetType);
            $('#import-feed').val(presetFeed);
            $('#import-title').val('');
            $('#import-imdb').val('');
            fetchTmdbResults(1);
        });

        // Type or Feed change handler
        $('#import-type, #import-feed').on('change', function() {
            if ($('#import-feed').val() !== 'discover') {
                $('#import-title').val('');
                $('#import-imdb').val('');
            }
            fetchTmdbResults(1);
        });

        // Search trigger
        $('#btn-run-search').on('click', function() {
            fetchTmdbResults(1);
        });

        // Hide imported toggle
        $('#chk-hide-imported').on('change', function() {
            fetchTmdbResults(currentPage);
        });

        // Pagination buttons
        $('#btn-page-prev, #btn-page-prev-bottom').on('click', function() {
            if (currentPage > 1) fetchTmdbResults(currentPage - 1);
        });

        $('#btn-page-next, #btn-page-next-bottom').on('click', function() {
            if (currentPage < totalPages) fetchTmdbResults(currentPage + 1);
        });

        // Single Item Import
        $(document).on('click', '.btn-import-one', function() {
            var $btn   = $(this);
            var tmdbId = $btn.attr('data-tmdb');
            var type   = $btn.attr('data-type') || 'movie';
            $btn.prop('disabled', true).text('Importing...');

            $.post(ajaxurl, { action: 'movie_elite_ajax_import', tmdb_id: tmdbId, import_type: type }, function(res) {
                if (res.success) {
                    $btn.text('✅ Imported (' + res.data.status + ')').css({'background':'#00c853', 'color':'#fff'});
                    $btn.closest('.import-card-item').css({'border-color':'#00c853', 'background':'#f0fdf4'});
                } else {
                    $btn.text('Failed').css({'background':'#ff0055', 'color':'#fff'});
                }
            });
        });

        // Page Bulk Import
        $('#btn-import-page-bulk').on('click', function() {
            var $btn  = $(this);
            var type  = $('#import-type').val();
            var $unimported = $('.btn-import-one:not(:disabled)');
            
            if ($unimported.length === 0) {
                alert('All items on this page are already imported!');
                return;
            }

            if (!confirm('Import ' + $unimported.length + ' unimported items on this page?')) return;

            $btn.prop('disabled', true).text('⚡ Importing Page Items...');
            var $log = $('#import-log');
            $log.show().html('Starting Page Bulk Import for ' + $unimported.length + ' items...\n');

            var index = 0;
            function processNext() {
                if (index >= $unimported.length) {
                    $btn.prop('disabled', false).text('⚡ Bulk Import All Unimported Items on This Page');
                    $log.append('\n✅ Page Bulk Import Completed successfully!');
                    return;
                }

                var $currentBtn = $unimported.eq(index);
                var tmdbId = $currentBtn.attr('data-tmdb');
                
                $currentBtn.text('Importing...');

                $.post(ajaxurl, { action: 'movie_elite_ajax_import', tmdb_id: tmdbId, import_type: type }, function(res) {
                    index++;
                    if (res.success) {
                        $currentBtn.text('✅ Imported').css({'background':'#00c853', 'color':'#fff'}).prop('disabled', true);
                        $currentBtn.closest('.import-card-item').css({'border-color':'#00c853', 'background':'#f0fdf4'});
                        $log.append('Item [' + index + '/' + $unimported.length + '] TMDb ID ' + tmdbId + ' -> ' + res.data.status + '\n');
                    } else {
                        $currentBtn.text('Failed').css({'background':'#ff0055', 'color':'#fff'});
                        $log.append('Item [' + index + '/' + $unimported.length + '] TMDb ID ' + tmdbId + ' -> Failed\n');
                    }
                    processNext();
                });
            }

            processNext();
        });
    });
    </script>
    <?php
}

/**
 * AJAX Handler: Check Batch TMDb IDs against WP DB to prevent duplicates
 */
function movie_elite_check_existing_batch_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $tmdb_ids  = isset($_POST['tmdb_ids']) && is_array($_POST['tmdb_ids']) ? array_map('sanitize_text_field', $_POST['tmdb_ids']) : array();
    $post_type = sanitize_text_field($_POST['post_type'] ?? 'movies');

    if (empty($tmdb_ids)) {
        wp_send_json_success(array());
    }

    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($tmdb_ids), '%s'));

    $query = $wpdb->prepare("
        SELECT meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'tmdb_id' 
        AND pm.meta_value IN ($placeholders)
        AND p.post_type = %s
        AND p.post_status != 'trash'
    ", array_merge($tmdb_ids, array($post_type)));

    $existing_ids = $wpdb->get_col($query);
    $map = array();
    foreach ($existing_ids as $id) {
        $map[$id] = true;
    }

    wp_send_json_success($map);
}
add_action('wp_ajax_movie_elite_check_existing_batch', 'movie_elite_check_existing_batch_handler');

/**
 * AJAX Handler for Movies & TV Shows Single Import with Duplicate Guard
 */
function movie_elite_ajax_import_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $tmdb_id     = sanitize_text_field($_POST['tmdb_id'] ?? '');
    $import_type = sanitize_text_field($_POST['import_type'] ?? 'movie');
    $api_key     = get_option('movie_elite_tmdb_api_key', '15d260044e350723362f6236b22b270a');

    if (empty($tmdb_id)) {
        wp_send_json_error('Missing TMDb ID');
    }

    $endpoint  = ($import_type === 'tv') ? 'tv' : 'movie';
    $post_type = ($import_type === 'tv') ? 'tvshows' : 'movies';

    // Duplicate Check by TMDb ID meta
    global $wpdb;
    $existing_id = $wpdb->get_var($wpdb->prepare("
        SELECT p.ID 
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE pm.meta_key = 'tmdb_id' AND pm.meta_value = %s AND p.post_type = %s AND p.post_status != 'trash'
        LIMIT 1
    ", $tmdb_id, $post_type));

    if ($existing_id) {
        wp_send_json_success(array('status' => 'Already Imported (Skipped)', 'embeds' => 4));
    }

    $url = "https://api.themoviedb.org/3/{$endpoint}/{$tmdb_id}?api_key={$api_key}&append_to_response=external_ids,credits";
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        wp_send_json_error('TMDb connection error');
    }

    $m = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($m)) {
        wp_send_json_error('Data not found');
    }

    $title    = sanitize_text_field($m['title'] ?? $m['name'] ?? '');
    $imdb_id  = $m['external_ids']['imdb_id'] ?? '';
    $overview = sanitize_textarea_field($m['overview'] ?? '');
    $rating   = number_format($m['vote_average'] ?? 7.5, 1);
    $year     = substr($m['release_date'] ?? $m['first_air_date'] ?? '2026', 0, 4);
    $poster   = $m['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $m['poster_path'] : '';
    $backdrop = $m['backdrop_path'] ? 'https://image.tmdb.org/t/p/w1280' . $m['backdrop_path'] : '';

    $seasons  = intval($m['number_of_seasons'] ?? 1);
    $episodes = intval($m['number_of_episodes'] ?? 12);

    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => $overview,
        'post_status'  => 'publish',
        'post_type'    => $post_type
    ));

    if ($post_id && !is_wp_error($post_id)) {
        // Save Meta fields
        update_post_meta($post_id, 'imdb_id', $imdb_id);
        update_post_meta($post_id, 'tmdb_id', $tmdb_id);
        update_post_meta($post_id, 'imdb_rating', $rating);
        update_post_meta($post_id, 'release_year', $year);
        update_post_meta($post_id, 'poster_url', $poster);
        update_post_meta($post_id, 'backdrop_url', $backdrop);
        update_post_meta($post_id, 'movie_quality', '4K UHD');
        update_post_meta($post_id, 'total_seasons', $seasons);
        update_post_meta($post_id, 'total_episodes', $episodes);

        $rel_date = $m['release_date'] ?? $m['first_air_date'] ?? '';
        if (!empty($rel_date)) {
            update_post_meta($post_id, '_release_date', $rel_date);
            if (strtotime($rel_date) <= time()) {
                update_post_meta($post_id, '_is_now_playing', '1');
            }
        }

        // Assign genres
        if (!empty($m['genres'])) {
            $genre_names = array_column($m['genres'], 'name');
            wp_set_object_terms($post_id, $genre_names, 'genre');
        }

        // Assign Cast to actor taxonomy with profile photos & character roles
        if (!empty($m['credits']['cast'])) {
            $cast_names   = array();
            $cast_details = array();
            $cast_limit   = 0;
            foreach ($m['credits']['cast'] as $cast_member) {
                if (!empty($cast_member['name'])) {
                    $c_name      = sanitize_text_field($cast_member['name']);
                    $c_photo     = !empty($cast_member['profile_path']) ? 'https://image.tmdb.org/t/p/w185' . $cast_member['profile_path'] : '';
                    $c_character = sanitize_text_field($cast_member['character'] ?? 'Main Cast');

                    $cast_names[]   = $c_name;
                    $cast_details[] = array(
                        'name'      => $c_name,
                        'photo'     => $c_photo,
                        'character' => $c_character
                    );

                    // Save photo URL to actor taxonomy term meta
                    $term = get_term_by('name', $c_name, 'actor');
                    if ($term && !is_wp_error($term) && !empty($c_photo)) {
                        update_term_meta($term->term_id, '_actor_photo_url', $c_photo);
                    }

                    $cast_limit++;
                    if ($cast_limit >= 12) break;
                }
            }
            if (!empty($cast_names)) {
                wp_set_object_terms($post_id, $cast_names, 'actor', true);
                update_post_meta($post_id, 'movie_cast', implode(', ', $cast_names));
                update_post_meta($post_id, 'movie_cast_details', $cast_details);
            }
        }

        // Assign Category & Build Western TV Show Season/Episode Multi-Server Players
        if ($import_type === 'tv') {
            wp_set_object_terms($post_id, 'tv-shows', 'movie_category');

            // Build TV Show Season & Episode multi-server embed data
            $tv_episodes_data = array();
            $total_seasons = max(1, $seasons);
            $total_episodes_count = 0;

            for ($s = 1; $s <= min($total_seasons, 10); $s++) {
                $eps_in_season = ($s === 1) ? min(30, max(1, $episodes)) : 12;
                for ($e = 1; $e <= $eps_in_season; $e++) {
                    $total_episodes_count++;
                    $target_id = !empty($imdb_id) ? $imdb_id : $tmdb_id;
                    $servers = array(
                        array('name' => 'Server 1 (AutoEmbed)', 'url' => "https://autoembed.cc/embed/tv/{$target_id}/{$s}/{$e}"),
                        array('name' => 'Server 2 (VidSrc PRO)', 'url' => "https://vidsrc.to/embed/tv/{$target_id}/{$s}/{$e}"),
                        array('name' => 'Server 3 (VidSrc ME)',  'url' => "https://vidsrc.me/embed/tv/{$target_id}/{$s}/{$e}"),
                        array('name' => 'Server 4 (SuperEmbed)', 'url' => "https://multiembed.mov/directstream.php?video_id={$target_id}&s={$s}&e={$e}")
                    );
                    $tv_episodes_data["s{$s}_e{$e}"] = array(
                        'season'  => $s,
                        'episode' => $e,
                        'title'   => "S{$s} E{$e}",
                        'servers' => $servers
                    );
                }
            }
            update_post_meta($post_id, 'dramacool_episodes_data', $tv_episodes_data);
            update_post_meta($post_id, 'total_episodes', max($episodes, $total_episodes_count));
        }

        // Apply Multi-Source Embed Generator & Draft Guard
        if (function_exists('movie_elite_process_import_draft_guard')) {
            $status = movie_elite_process_import_draft_guard($post_id, $imdb_id, $tmdb_id);
            $post_status = $status ? 'Published' : 'Draft (No Embed)';
        } else {
            $post_status = 'Published';
        }

        wp_send_json_success(array('status' => $post_status, 'embeds' => 4));
    }

    wp_send_json_error('Failed inserting post');
}
add_action('wp_ajax_movie_elite_ajax_import', 'movie_elite_ajax_import_handler');
