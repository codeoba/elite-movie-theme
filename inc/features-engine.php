<?php
/**
 * MovieElite Pro - Features Engine
 * Handles: Views Counter, Star Ratings, Watchlist, Continue Watching,
 *          Advanced Filter, Collections CPT, Coming Soon / New Releases
 */
if (!defined("ABSPATH")) { exit; }

// ═══════════════════════════════════════════════════════
// FEATURE 8: VIEWS COUNTER
// ═══════════════════════════════════════════════════════

function movie_elite_record_view() {
    if (!is_singular(array("movies","tvshows","collections"))) return;
    $post_id = get_queried_object_id();
    if (!$post_id) return;
    // Basic bot guard: only count if not a bot
    $ua = strtolower($_SERVER["HTTP_USER_AGENT"] ?? "");
    if (strpos($ua,"bot")!==false||strpos($ua,"crawler")!==false||strpos($ua,"spider")!==false) return;
    $views = (int) get_post_meta($post_id,"_movie_views",true);
    update_post_meta($post_id,"_movie_views", $views + 1);
}
add_action("wp_head","movie_elite_record_view");

function movie_elite_get_views($post_id) {
    $v = (int) get_post_meta($post_id,"_movie_views",true);
    if ($v >= 1000000) return round($v/1000000,1)."M";
    if ($v >= 1000)    return round($v/1000,1)."K";
    return $v ?: "New";
}

// ═══════════════════════════════════════════════════════
// FEATURE 1: STAR RATINGS & REVIEWS
// ═══════════════════════════════════════════════════════

// AJAX: submit a rating (1-5)
function movie_elite_ajax_rate() {
    check_ajax_referer("movie_elite_rate_nonce","nonce");
    $post_id = intval($_POST["post_id"] ?? 0);
    $score   = intval($_POST["score"] ?? 0);
    if (!$post_id || $score < 1 || $score > 5) { wp_send_json_error("Invalid"); }

    $ratings = get_post_meta($post_id,"_me_ratings",true);
    if (!is_array($ratings)) $ratings = [];
    $ratings[] = $score;
    update_post_meta($post_id,"_me_ratings",$ratings);
    $avg = round(array_sum($ratings)/count($ratings),1);
    update_post_meta($post_id,"_me_avg_rating",$avg);
    update_post_meta($post_id,"_me_rating_count",count($ratings));
    wp_send_json_success(["avg"=>$avg,"count"=>count($ratings)]);
}
add_action("wp_ajax_movie_elite_rate","movie_elite_ajax_rate");
add_action("wp_ajax_nopriv_movie_elite_rate","movie_elite_ajax_rate");

// AJAX: submit a review
function movie_elite_ajax_review() {
    check_ajax_referer("movie_elite_review_nonce","nonce");
    $post_id = intval($_POST["post_id"] ?? 0);
    $author  = sanitize_text_field($_POST["author"] ?? "Guest");
    $body    = sanitize_textarea_field($_POST["body"] ?? "");
    $score   = intval($_POST["score"] ?? 0);
    if (!$post_id || empty($body)) { wp_send_json_error("Missing fields"); }

    $reviews = get_post_meta($post_id,"_me_reviews",true);
    if (!is_array($reviews)) $reviews = [];
    $reviews[] = [
        "author" => $author ?: "Guest",
        "body"   => $body,
        "score"  => max(1,min(5,$score)),
        "date"   => date("Y-m-d H:i"),
    ];
    update_post_meta($post_id,"_me_reviews",$reviews);
    wp_send_json_success(["message"=>"Review posted!"]);
}
add_action("wp_ajax_movie_elite_review","movie_elite_ajax_review");
add_action("wp_ajax_nopriv_movie_elite_review","movie_elite_ajax_review");

function movie_elite_render_ratings_reviews($post_id) {
    $avg   = (float) get_post_meta($post_id,"_me_avg_rating",true);
    $count = (int)   get_post_meta($post_id,"_me_rating_count",true);
    $reviews = get_post_meta($post_id,"_me_reviews",true);
    if (!is_array($reviews)) $reviews = [];
    $rate_nonce   = wp_create_nonce("movie_elite_rate_nonce");
    $review_nonce = wp_create_nonce("movie_elite_review_nonce");
    ?>
    <div class="me-ratings-section" id="me-ratings-<?php echo $post_id; ?>">

        <!-- Star Rating Widget -->
        <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-bottom:18px;">
            <div>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">YOUR RATING</div>
                <div class="me-stars-input" data-post="<?php echo $post_id;?>" data-nonce="<?php echo $rate_nonce;?>">
                    <?php for($i=1;$i<=5;$i++): ?>
                    <span class="me-star" data-val="<?php echo $i;?>">&#9733;</span>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="me-avg-block" id="me-avg-<?php echo $post_id;?>">
                <div style="font-size:2rem;font-weight:900;color:var(--accent-gold);line-height:1;"><?php echo $avg ?: "—"; ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?php echo $count; ?> ratings</div>
            </div>
        </div>

        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
        <div class="me-reviews-list" style="margin-bottom:20px;">
            <?php foreach(array_slice(array_reverse($reviews),0,5) as $rv): ?>
            <div class="me-review-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <strong style="font-size:0.9rem;color:#fff;"><?php echo esc_html($rv["author"]); ?></strong>
                    <span style="color:var(--accent-gold);font-size:0.85rem;">
                        <?php for($i=1;$i<=5;$i++) echo $i<=$rv["score"]?"★":"☆"; ?>
                    </span>
                </div>
                <p style="color:var(--text-muted);font-size:0.88rem;margin:0;"><?php echo esc_html($rv["body"]); ?></p>
                <span style="font-size:0.75rem;color:#555;margin-top:4px;display:block;"><?php echo esc_html($rv["date"]); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Add Review Form -->
        <div class="me-review-form" id="me-review-form-<?php echo $post_id;?>">
            <h4 style="color:#fff;font-size:1rem;margin-bottom:12px;"><i class="fa-solid fa-pen-to-square" style="color:var(--accent-cyan);"></i> Write a Review</h4>
            <input type="text" class="me-rv-author" placeholder="Your Name (optional)" style="width:100%;margin-bottom:8px;padding:8px 12px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-secondary);color:#fff;font-size:0.88rem;" />
            <div class="me-stars-input me-review-stars" data-post="<?php echo $post_id;?>" style="margin-bottom:8px;">
                <?php for($i=1;$i<=5;$i++): ?>
                <span class="me-star" data-val="<?php echo $i;?>">&#9733;</span>
                <?php endfor; ?>
            </div>
            <textarea class="me-rv-body" placeholder="Write your thoughts about this movie..." rows="4" style="width:100%;padding:10px 12px;border-radius:6px;border:1px solid var(--border-color);background:var(--bg-secondary);color:#fff;font-size:0.88rem;resize:vertical;"></textarea>
            <button type="button" class="me-btn-submit-review" data-post="<?php echo $post_id;?>" data-nonce="<?php echo $review_nonce;?>"
                style="margin-top:10px;background:linear-gradient(135deg,var(--accent-cyan),var(--accent-blue));color:#000;font-weight:800;border:none;border-radius:25px;padding:10px 24px;cursor:pointer;font-size:0.88rem;">
                <i class="fa-solid fa-paper-plane"></i> Post Review
            </button>
            <div class="me-review-msg" style="display:none;margin-top:8px;font-size:0.85rem;color:var(--accent-green);"></div>
        </div>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════
// FEATURE 9: COLLECTIONS CPT
// ═══════════════════════════════════════════════════════

function movie_elite_register_collections_cpt() {
    register_post_type("collections",[
        "labels" => [
            "name"          => "Collections",
            "singular_name" => "Collection",
            "menu_name"     => "🗂️ Collections",
            "add_new_item"  => "Add New Collection",
            "edit_item"     => "Edit Collection",
        ],
        "public"           => true,
        "has_archive"      => true,
        "publicly_queryable"=> true,
        "show_ui"          => true,
        "show_in_menu"     => true,
        "rewrite"          => ["slug"=>"collections"],
        "capability_type"  => "post",
        "hierarchical"     => false,
        "menu_position"    => 7,
        "menu_icon"        => "dashicons-portfolio",
        "supports"         => ["title","editor","thumbnail","excerpt"],
    ]);
}
add_action("init","movie_elite_register_collections_cpt");

// Meta box for assigning movies to a collection
function movie_elite_register_collection_meta() {
    add_meta_box(
        "me_collection_movies",
        "🎬 Movies & Shows in this Collection",
        "movie_elite_render_collection_meta",
        "collections","normal","high"
    );
}
add_action("add_meta_boxes","movie_elite_register_collection_meta");

function movie_elite_render_collection_meta($post) {
    wp_nonce_field("me_collection_save","me_collection_nonce");
    $movie_ids = get_post_meta($post->ID,"_collection_movie_ids",true);
    if (!is_array($movie_ids)) $movie_ids = [];

    // Get all movies & tvshows
    $all = get_posts(["post_type"=>["movies","tvshows"],"posts_per_page"=>-1,"orderby"=>"title","order"=>"ASC","post_status"=>"publish"]);
    ?>
    <p style="color:#555;font-size:0.85rem;margin-bottom:12px;">Select movies/shows that belong to this collection (e.g. a franchise, director series, etc.)</p>
    <div style="max-height:320px;overflow-y:auto;border:1px solid #ddd;border-radius:6px;padding:10px;">
    <?php foreach($all as $item): ?>
    <label style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f0f0f0;cursor:pointer;">
        <input type="checkbox" name="collection_movie_ids[]" value="<?php echo $item->ID;?>"
            <?php checked(in_array($item->ID,$movie_ids)); ?> />
        <span><?php echo esc_html($item->post_title); ?> <span style="color:#999;font-size:0.8rem;">(<?php echo $item->post_type;?>)</span></span>
    </label>
    <?php endforeach; ?>
    </div>
    <p style="margin-top:8px;font-size:0.8rem;color:#777;">Tip: You can also set the Poster and Backdrop URLs using the standard meta box below.</p>
    <?php
}

function movie_elite_save_collection_meta($post_id) {
    if (!isset($_POST["me_collection_nonce"])||!wp_verify_nonce($_POST["me_collection_nonce"],"me_collection_save")) return;
    if (defined("DOING_AUTOSAVE")&&DOING_AUTOSAVE) return;
    if (!current_user_can("edit_post",$post_id)) return;
    $ids = isset($_POST["collection_movie_ids"]) ? array_map("intval",$_POST["collection_movie_ids"]) : [];
    update_post_meta($post_id,"_collection_movie_ids",$ids);
    // Update reverse: each movie knows its collection
    foreach($ids as $mid) {
        $existing = get_post_meta($mid,"_in_collections",true);
        if (!is_array($existing)) $existing = [];
        if (!in_array($post_id,$existing)) { $existing[] = $post_id; update_post_meta($mid,"_in_collections",$existing); }
    }
}
add_action("save_post_collections","movie_elite_save_collection_meta");

// ═══════════════════════════════════════════════════════
// FEATURE 10: COMING SOON + NEW RELEASES BLOCKS
// ═══════════════════════════════════════════════════════

// Register block config entries for Coming Soon + New Releases
function movie_elite_add_default_blocks($defaults) {
    if (!isset($defaults["coming_soon"])) {
        $defaults["coming_soon"] = ["id"=>"coming_soon","name"=>"Coming Soon","status"=>"active","rule"=>"coming_soon","value"=>"","icon"=>"fa-clock"];
    }
    if (!isset($defaults["new_releases"])) {
        $defaults["new_releases"] = ["id"=>"new_releases","name"=>"New Releases","status"=>"active","rule"=>"new_releases","value"=>"7","icon"=>"fa-bolt"];
    }
    return $defaults;
}
add_filter("movie_elite_block_defaults","movie_elite_add_default_blocks");

// ═══════════════════════════════════════════════════════
// FEATURE 4: ADVANCED FILTER — AJAX HANDLER
// ═══════════════════════════════════════════════════════

function movie_elite_ajax_advanced_filter() {
    check_ajax_referer("movie_elite_nonce","nonce");

    $genre   = sanitize_text_field($_POST["genre"]   ?? "");
    $country = sanitize_text_field($_POST["country"] ?? "");
    $year    = sanitize_text_field($_POST["year"]    ?? "");
    $quality = sanitize_text_field($_POST["quality"] ?? "");
    $ptype   = sanitize_text_field($_POST["ptype"]   ?? "");
    $page    = max(1, intval($_POST["paged"] ?? 1));

    $args = [
        "post_type"      => $ptype === "tvshows" ? ["tvshows"] : ($ptype === "movies" ? ["movies"] : ["movies","tvshows"]),
        "post_status"    => "publish",
        "posts_per_page" => 20,
        "paged"          => $page,
        "orderby"        => "date",
        "order"          => "DESC",
    ];

    $tax_query = ["relation"=>"AND"];
    if (!empty($genre))   $tax_query[] = ["taxonomy"=>"genre",  "field"=>"slug","terms"=>$genre];
    if (!empty($country)) $tax_query[] = ["taxonomy"=>"country","field"=>"slug","terms"=>$country];
    if (count($tax_query) > 1) $args["tax_query"] = $tax_query;

    if (!empty($year))    $args["meta_query"][] = ["key"=>"release_year","value"=>$year,"compare"=>"="];
    if (!empty($quality)) $args["meta_query"][] = ["key"=>"movie_quality","value"=>$quality,"compare"=>"LIKE"];

    $q = new WP_Query($args);
    ob_start();
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post(); movie_elite_render_card_item(); }
        wp_reset_postdata();
    } else {
        echo "<div style=\"grid-column:1/-1;padding:40px;text-align:center;color:var(--text-muted);\"><i class=\"fa-solid fa-film\" style=\"font-size:2rem;opacity:0.3;\"></i><br><br>No movies found for selected filters. Try different options.</div>";
    }
    wp_send_json_success(["html"=>ob_get_clean(),"total"=>$q->found_posts,"pages"=>$q->max_num_pages]);
}
add_action("wp_ajax_movie_elite_advanced_filter","movie_elite_ajax_advanced_filter");
add_action("wp_ajax_nopriv_movie_elite_advanced_filter","movie_elite_ajax_advanced_filter");

// ═══════════════════════════════════════════════════════
// WATCHLIST AJAX CARDS HANDLER
// ═══════════════════════════════════════════════════════

function movie_elite_ajax_watchlist_cards_handler() {
    check_ajax_referer('movie_elite_nonce', 'nonce');
    $ids_raw = $_POST['ids'] ?? '[]';
    $ids = json_decode(stripslashes($ids_raw), true);
    if (!is_array($ids) || empty($ids)) {
        wp_send_json_success(array('html' => ''));
    }
    $clean_ids = array_map('intval', $ids);

    $args = array(
        'post_type'      => array('movies', 'tvshows'),
        'post_status'    => 'publish',
        'post__in'       => $clean_ids,
        'posts_per_page' => -1,
        'orderby'        => 'post__in',
    );

    $query = new WP_Query($args);
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            movie_elite_render_card_item();
        }
        wp_reset_postdata();
    }
    wp_send_json_success(array('html' => ob_get_clean()));
}
add_action('wp_ajax_movie_elite_get_watchlist_cards', 'movie_elite_ajax_watchlist_cards_handler');
add_action('wp_ajax_nopriv_movie_elite_get_watchlist_cards', 'movie_elite_ajax_watchlist_cards_handler');

// ═══════════════════════════════════════════════════════
// FEATURE: REPORT BROKEN LINK / PLAYER SYSTEM
// ═══════════════════════════════════════════════════════

function movie_elite_ajax_report_broken_link() {
    check_ajax_referer('movie_elite_nonce', 'nonce');
    $post_id    = intval($_POST['post_id'] ?? 0);
    $server_name= sanitize_text_field($_POST['server_name'] ?? 'Server 1');
    $note       = sanitize_textarea_field($_POST['note'] ?? 'Player not loading');

    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid Post ID'));
    }

    $reports = get_post_meta($post_id, '_broken_link_reports', true);
    if (!is_array($reports)) $reports = array();

    $reports[] = array(
        'server'    => $server_name,
        'note'      => $note,
        'timestamp' => current_time('mysql'),
        'user_ip'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    );

    update_post_meta($post_id, '_broken_link_reports', $reports);
    update_post_meta($post_id, '_has_broken_link_report', 'yes');

    wp_send_json_success(array('message' => 'Thank you! The issue has been reported to site moderators.'));
}
add_action('wp_ajax_movie_elite_report_broken_link', 'movie_elite_ajax_report_broken_link');
add_action('wp_ajax_nopriv_movie_elite_report_broken_link', 'movie_elite_ajax_report_broken_link');

// ═══════════════════════════════════════════════════════
// FEATURE: WEEKLY AIRING SCHEDULE CALENDAR
// ═══════════════════════════════════════════════════════

function movie_elite_render_airing_schedule() {
    $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    $current_day = date('D');

    // Query active TV shows & Asian Dramas
    $shows = get_posts(array(
        'post_type'      => 'tvshows',
        'posts_per_page' => 14,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC'
    ));

    ?>
    <div class="airing-schedule-container">
        <div class="airing-schedule-header">
            <h3><i class="fa-solid fa-calendar-week" style="color:var(--accent-cyan);"></i> Weekly Airing Schedule (TV Shows & Dramas)</h3>
            <span class="schedule-badge"><i class="fa-solid fa-clock"></i> New Episodes Daily</span>
        </div>
        <div class="airing-days-tabs">
            <?php foreach ($days as $idx => $d) : 
                $active = ($d === substr($current_day, 0, 3)) ? 'active' : '';
            ?>
                <button type="button" class="airing-day-tab <?php echo $active; ?>" data-day="<?php echo esc_attr($d); ?>">
                    <?php echo esc_html($d); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="airing-shows-list">
            <?php if (!empty($shows)) : ?>
                <?php foreach ($shows as $s_idx => $show) : 
                    $show_id    = $show->ID;
                    $title      = get_the_title($show_id);
                    $permalink  = get_permalink($show_id);
                    $poster     = get_post_meta($show_id, 'poster_url', true) ?: get_the_post_thumbnail_url($show_id, 'thumbnail');
                    $assigned_day = $days[$s_idx % 7];
                    if (empty($poster)) $poster = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=150';
                ?>
                <div class="airing-show-card" data-day="<?php echo esc_attr($assigned_day); ?>" style="<?php echo ($assigned_day === substr($current_day, 0, 3) || $s_idx < 4) ? '' : 'display:none;'; ?>">
                    <div class="airing-thumb">
                        <img src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($title); ?>" />
                    </div>
                    <div class="airing-info">
                        <span class="airing-day-tag"><?php echo esc_html($assigned_day); ?> Airing</span>
                        <h4><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h4>
                        <span class="airing-time"><i class="fa-solid fa-play-circle"></i> New Episode</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="color:var(--text-muted); padding:15px;">No scheduled dramas for today.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// ═══════════════════════════════════════════════════════
// ENQUEUE FEATURES JS + CSS
// ═══════════════════════════════════════════════════════

function movie_elite_enqueue_features_assets() {
    // Get all genres and countries for filter dropdowns
    $genres    = get_terms(["taxonomy"=>"genre",  "hide_empty"=>true]);
    $countries = get_terms(["taxonomy"=>"country","hide_empty"=>true]);
    $years = [];
    for($y=date("Y")+1;$y>=2000;$y--) $years[] = $y;

    wp_localize_script("movie-elite-main","meFeatures",[
        "ajaxurl"       => admin_url("admin-ajax.php"),
        "nonce"         => wp_create_nonce("movie_elite_nonce"),
        "rateNonce"     => wp_create_nonce("movie_elite_rate_nonce"),
        "reviewNonce"   => wp_create_nonce("movie_elite_review_nonce"),
        "watchlistNonce"=> wp_create_nonce("movie_elite_watchlist"),
        "genres"        => !is_wp_error($genres)    ? array_map(fn($t)=>["slug"=>$t->slug,"name"=>$t->name],$genres) : [],
        "countries"     => !is_wp_error($countries) ? array_map(fn($t)=>["slug"=>$t->slug,"name"=>$t->name],$countries) : [],
        "years"         => $years,
    ]);
}
add_action("wp_enqueue_scripts","movie_elite_enqueue_features_assets",20);
