<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a1d2e">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// Fetch Genres for dropdown
$nav_genres_terms = get_terms(array('taxonomy' => 'genre', 'hide_empty' => false));
$nav_genres = array();
if (!is_wp_error($nav_genres_terms) && !empty($nav_genres_terms)) {
    foreach ($nav_genres_terms as $gt) {
        $nav_genres[$gt->name] = $gt->slug;
    }
} else {
    $nav_genres = array(
        'Action' => 'action', 'Adventure' => 'adventure', 'Animation' => 'animation',
        'Comedy' => 'comedy', 'Crime' => 'crime', 'Documentary' => 'documentary',
        'Drama' => 'drama', 'Family' => 'family', 'Fantasy' => 'fantasy',
        'History' => 'history', 'Horror' => 'horror', 'Music' => 'music',
        'Mystery' => 'mystery', 'Romance' => 'romance', 'Sci-Fi' => 'sci-fi',
        'Thriller' => 'thriller', 'War' => 'war', 'Western' => 'western'
    );
}

// Fetch Countries for dropdown
$nav_countries_terms = get_terms(array('taxonomy' => 'country', 'hide_empty' => false));
$nav_countries = array();
if (!is_wp_error($nav_countries_terms) && !empty($nav_countries_terms)) {
    foreach ($nav_countries_terms as $ct) {
        $nav_countries[$ct->name] = $ct->slug;
    }
} else {
    $nav_countries = array(
        'South Korea' => 'korea', 'China' => 'china', 'Japan' => 'japan',
        'Thailand' => 'thailand', 'United States' => 'usa', 'United Kingdom' => 'uk',
        'India' => 'india', 'Philippines' => 'philippines', 'Indonesia' => 'indonesia',
        'Taiwan' => 'taiwan', 'France' => 'france', 'Germany' => 'germany'
    );
}

// Years list for dropdown
$nav_years = array('2026', '2025', '2024', '2023', '2022', '2021', '2020', '2019', '2018', '2017', '2016', '2015');
?>

<header class="site-header">
    <!-- ROW 1: TOP HEADER BAR (Logo on Left, Search Bar in Middle, Color Switcher on Right) -->
    <div class="header-top-row">
        <div class="container">
            <div class="header-top-inner">
                <!-- Site Logo -->
                <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
                    <i class="fa-solid fa-clapperboard movie-pop-icon"></i>
                    MOVIE<span>ELITE PRO</span>
                </a>

                <!-- Prominent Center Search Bar with Instant Live Preview -->
                <div class="nav-search-center">
                    <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
                        <input type="text" name="s" id="movie-search-input" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Search thousands of movies, tv shows, Asian dramas..." autocomplete="off" />
                    </form>
                    <div id="live-search-results" class="live-search-dropdown" style="display:none;"></div>
                </div>

                <!-- Right Controls: Accent Theme Color Switcher & Watchlist -->
                <div class="header-top-right">
                    <div class="accent-switcher-bar" title="Change Theme Accent Color">
                        <span class="accent-dot" data-color="#00d4ff" style="background:#00d4ff;" title="Cyan Glow"></span>
                        <span class="accent-dot" data-color="#ffc107" style="background:#ffc107;" title="Gold Star"></span>
                        <span class="accent-dot" data-color="#ff2a70" style="background:#ff2a70;" title="Neon Pink"></span>
                        <span class="accent-dot" data-color="#00ff88" style="background:#00ff88;" title="Emerald Green"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 2: DEDICATED NAVIGATION MENU BAR -->
    <div class="header-nav-row">
        <div class="container">
            <nav class="site-nav">
                <ul class="main-nav">
                    <li class="<?php echo (is_front_page() && !is_search()) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><i class="fa-solid fa-house"></i> Home</a>
                    </li>

                    <li class="<?php echo is_post_type_archive('movies') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movies/')); ?>"><i class="fa-solid fa-film" style="color:var(--accent-cyan);"></i> Movies</a>
                    </li>

                    <li class="<?php echo is_post_type_archive('tvshows') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/tvshows/')); ?>"><i class="fa-solid fa-tv" style="color:var(--accent-green);"></i> TV Shows</a>
                    </li>

                    <!-- GENRES MEGA DROPDOWN -->
                    <li class="has-dropdown <?php echo is_tax('genre') ? 'active' : ''; ?>">
                        <a href="#"><i class="fa-solid fa-masks-theater" style="color:var(--accent-gold);"></i> Genres</a>
                        <div class="nav-dropdown">
                            <div class="nav-dropdown-title"><i class="fa-solid fa-layer-group"></i> Browse by Genre</div>
                            <div class="nav-dropdown-grid-3">
                                <?php foreach ($nav_genres as $g_name => $g_slug) : ?>
                                <a href="<?php echo esc_url(home_url('/genre/' . sanitize_title($g_slug) . '/')); ?>" class="dropdown-item">
                                    <i class="fa-solid fa-tag"></i> <?php echo esc_html($g_name); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>

                    <!-- COUNTRIES MEGA DROPDOWN -->
                    <li class="has-dropdown <?php echo is_tax('country') ? 'active' : ''; ?>">
                        <a href="#"><i class="fa-solid fa-globe" style="color:var(--accent-cyan);"></i> Countries</a>
                        <div class="nav-dropdown">
                            <div class="nav-dropdown-title"><i class="fa-solid fa-earth-asia"></i> Browse by Country</div>
                            <div class="nav-dropdown-grid-2">
                                <?php foreach ($nav_countries as $c_name => $c_slug) : ?>
                                <a href="<?php echo esc_url(home_url('/country/' . sanitize_title($c_slug) . '/')); ?>" class="dropdown-item">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo esc_html($c_name); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>

                    <!-- YEARS DROPDOWN -->
                    <li class="has-dropdown dropdown-right <?php echo isset($_GET['release_year']) ? 'active' : ''; ?>">
                        <a href="#"><i class="fa-solid fa-calendar-days" style="color:var(--accent-green);"></i> Year</a>
                        <div class="nav-dropdown">
                            <div class="nav-dropdown-title"><i class="fa-solid fa-clock"></i> Release Year</div>
                            <div class="nav-dropdown-grid-years">
                                <?php foreach ($nav_years as $yr) : ?>
                                <a href="<?php echo esc_url(home_url('/?release_year=' . urlencode($yr))); ?>" class="dropdown-item year-item">
                                    <?php echo esc_html($yr); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </li>

                    <li class="<?php echo is_tax('movie_category', 'asian-drama') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movie_category/asian-drama/')); ?>"><i class="fa-solid fa-torii-gate" style="color:var(--accent-magenta);"></i> Asian Dramas</a>
                    </li>

                    <li class="<?php echo is_tax('movie_category', 'recommended') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movie_category/recommended/')); ?>"><i class="fa-solid fa-fire" style="color:var(--accent-gold);"></i> Recommended</a>
                    </li>

                    <li class="<?php echo is_post_type_archive('collections') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/collections/')); ?>"><i class="fa-solid fa-layer-group" style="color:var(--accent-cyan);"></i> Collections</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>
