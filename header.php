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
                <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
                <input type="text" id="movie-search-input" placeholder="Search movies, tv shows, Asian dramas..." autocomplete="off" />
            </div>

            <!-- Navigation Links -->
            <nav class="site-nav">
                <ul class="main-nav">
                    <li class="<?php echo is_front_page() ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><i class="fa-solid fa-house"></i> Home</a>
                    </li>
                    <li>
                        <a href="#block-recommended"><i class="fa-solid fa-fire" style="color:var(--accent-gold);"></i> Recommended</a>
                    </li>
                    <li>
                        <a href="#block-action"><i class="fa-solid fa-gun"></i> Action</a>
                    </li>
                    <li>
                        <a href="#block-romance"><i class="fa-solid fa-heart" style="color:var(--accent-magenta);"></i> Romance</a>
                    </li>
                    <li>
                        <a href="#block-korean"><i class="fa-solid fa-film"></i> Korean</a>
                    </li>
                    <li>
                        <a href="#block-chinese"><i class="fa-solid fa-dragon"></i> Chinese</a>
                    </li>
                    <li>
                        <a href="#block-tvshows"><i class="fa-solid fa-tv"></i> TV Shows</a>
                    </li>
                    <li>
                        <a href="#block-asiandrama"><i class="fa-solid fa-masks-theater"></i> Dramas</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>
