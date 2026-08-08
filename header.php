<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b0d14">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <!-- Site Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
                <i class="fa-solid fa-clapperboard movie-pop-icon"></i>
                MOVIE<span>ELITE PRO</span>
            </a>

            <!-- Search Bar -->
            <div class="nav-search">
                <form method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
                    <input type="text" name="s" id="movie-search-input" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Search movies, tv shows, Asian dramas..." autocomplete="off" />
                </form>
            </div>

            <!-- Navigation Links -->
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
                    <li class="<?php echo is_tax('movie_category', 'recommended') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movie_category/recommended/')); ?>"><i class="fa-solid fa-fire" style="color:var(--accent-gold);"></i> Recommended</a>
                    </li>
                    <li class="<?php echo is_tax('genre', 'action') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/genre/action/')); ?>"><i class="fa-solid fa-gun"></i> Action</a>
                    </li>
                    <li class="<?php echo is_tax('genre', 'romance') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/genre/romance/')); ?>"><i class="fa-solid fa-heart" style="color:var(--accent-magenta);"></i> Romance</a>
                    </li>
                    <li class="<?php echo is_tax('movie_category', 'korean') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movie_category/korean/')); ?>"><i class="fa-solid fa-film"></i> Korean</a>
                    </li>
                    <li class="<?php echo is_tax('movie_category', 'chinese') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movie_category/chinese/')); ?>"><i class="fa-solid fa-dragon"></i> Chinese</a>
                    </li>
                    <li class="<?php echo is_tax('movie_category', 'asian-drama') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/movie_category/asian-drama/')); ?>"><i class="fa-solid fa-masks-theater"></i> Asian Dramas</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>
