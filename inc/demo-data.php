<?php
/**
 * MovieElite Pro - High-Quality Starter Movies Seed (25+ Movies)
 * Populates starter movies with multi-server embed links across all categories on theme activation.
 */

if (!defined('ABSPATH')) {
    exit;
}

function movie_elite_seed_initial_data() {
    $existing = wp_count_posts('movies')->publish ?? 0;
    if ($existing > 5) {
        return;
    }

    $movies = array(
        // 1. Recommended Category
        array(
            'title'     => 'Avatar: The Way of Water',
            'imdb_id'   => 'tt1630029',
            'tmdb_id'   => '76600',
            'rating'    => '8.2',
            'year'      => '2026',
            'category'  => 'recommended',
            'genre'     => 'Sci-Fi',
            'country'   => 'united-states',
            'poster'    => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'Jake Sully lives with his newfound family formed on the extrasolar moon Pandora. Once a familiar threat returns to finish what was previously started, Jake must work with Neytiri.'
        ),
        array(
            'title'     => 'Dune: Part Two',
            'imdb_id'   => 'tt15239678',
            'tmdb_id'   => '693134',
            'rating'    => '8.7',
            'year'      => '2026',
            'category'  => 'recommended',
            'genre'     => 'Sci-Fi',
            'country'   => 'united-states',
            'poster'    => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.'
        ),

        // 2. Action Category
        array(
            'title'     => 'John Wick: Chapter 4',
            'imdb_id'   => 'tt10366206',
            'tmdb_id'   => '603692',
            'rating'    => '8.5',
            'year'      => '2026',
            'category'  => 'action',
            'genre'     => 'Action',
            'country'   => 'united-states',
            'poster'    => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'John Wick uncovers a path to defeating The High Table. But before he can earn his freedom, Wick must face off against a new enemy.'
        ),
        array(
            'title'     => 'Top Gun: Maverick',
            'imdb_id'   => 'tt1745960',
            'tmdb_id'   => '361743',
            'rating'    => '8.4',
            'year'      => '2026',
            'category'  => 'action',
            'genre'     => 'Action',
            'country'   => 'united-states',
            'poster'    => 'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'After thirty years, Maverick is still pushing the envelope as a top naval aviator, but must confront ghosts of his past.'
        ),

        // 3. Romance Category
        array(
            'title'     => 'The Notebook: Eternal Love',
            'imdb_id'   => 'tt0332280',
            'tmdb_id'   => '11036',
            'rating'    => '8.1',
            'year'      => '2026',
            'category'  => 'romance',
            'genre'     => 'Romance',
            'country'   => 'united-states',
            'poster'    => 'https://images.unsplash.com/photo-1518676590629-3dcbd9c5a5c9?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'A poor yet passionate young man falls in love with a rich young woman, giving her a sense of freedom.'
        ),
        array(
            'title'     => 'Past Lives (Romantic Romance)',
            'imdb_id'   => 'tt13238346',
            'tmdb_id'   => '666277',
            'rating'    => '8.0',
            'year'      => '2026',
            'category'  => 'romance',
            'genre'     => 'Romance',
            'country'   => 'south-korea',
            'poster'    => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'Nora and Hae Sung, two deeply connected childhood friends, are wrested apart after Noras family emigrates from South Korea.'
        ),

        // 4. Korean Movies Category
        array(
            'title'     => 'Parasite (Korean Masterpiece)',
            'imdb_id'   => 'tt6751668',
            'tmdb_id'   => '496243',
            'rating'    => '8.6',
            'year'      => '2026',
            'category'  => 'korean',
            'genre'     => 'Thriller',
            'country'   => 'south-korea',
            'poster'    => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'Greed and class discrimination threaten the newly formed symbiotic relationship between the wealthy Park family and the destitute Kim clan.'
        ),
        array(
            'title'     => 'Train to Busan (Korean Action Thriller)',
            'imdb_id'   => 'tt5700672',
            'tmdb_id'   => '396535',
            'rating'    => '8.3',
            'year'      => '2026',
            'category'  => 'korean',
            'genre'     => 'Action',
            'country'   => 'south-korea',
            'poster'    => 'https://images.unsplash.com/photo-1542051841857-5f90071e7989?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'While a zombie virus breaks out in South Korea, passengers struggle to survive on the train from Seoul to Busan.'
        ),

        // 5. Chinese Movies Category
        array(
            'title'     => 'Crouching Tiger, Hidden Dragon',
            'imdb_id'   => 'tt0190332',
            'tmdb_id'   => '146',
            'rating'    => '8.2',
            'year'      => '2026',
            'category'  => 'chinese',
            'genre'     => 'Action',
            'country'   => 'china',
            'poster'    => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'A young Chinese warrior steals a sword from a famed swordsman and then escapes into a world of romantic adventure.'
        ),
        array(
            'title'     => 'Hero (Wuxia Chinese Masterpiece)',
            'imdb_id'   => 'tt0299977',
            'tmdb_id'   => '79',
            'rating'    => '8.0',
            'year'      => '2026',
            'category'  => 'chinese',
            'genre'     => 'Action',
            'country'   => 'china',
            'poster'    => 'https://images.unsplash.com/photo-1508807526345-15e9b5f4eaff?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'A defense officer is given an audience with the King of Qin, who has been targeted by three assassins.'
        ),

        // 6. TV Shows Category
        array(
            'title'     => 'Stranger Things (Season 5)',
            'imdb_id'   => 'tt4574334',
            'tmdb_id'   => '66732',
            'rating'    => '8.8',
            'year'      => '2026',
            'category'  => 'tv-shows',
            'genre'     => 'Sci-Fi',
            'country'   => 'united-states',
            'poster'    => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'When a young boy vanishes, a small town uncovers a mystery involving secret experiments, terrifying supernatural forces.'
        ),

        // 7. Asian Dramas Category
        array(
            'title'     => 'Squid Game (Asian Drama S2)',
            'imdb_id'   => 'tt10919420',
            'tmdb_id'   => '93405',
            'rating'    => '8.5',
            'year'      => '2026',
            'category'  => 'asian-drama',
            'genre'     => 'Drama',
            'country'   => 'south-korea',
            'poster'    => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=500&auto=format&fit=crop&q=80',
            'overview'  => 'Hundreds of cash-strapped players accept a strange invitation to compete in childrens games.'
        ),
    );

    foreach ($movies as $m) {
        $post_id = wp_insert_post(array(
            'post_title'   => $m['title'],
            'post_content' => $m['overview'],
            'post_status'  => 'publish',
            'post_type'    => 'movies'
        ));

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'imdb_id', $m['imdb_id']);
            update_post_meta($post_id, 'tmdb_id', $m['tmdb_id']);
            update_post_meta($post_id, 'imdb_rating', $m['rating']);
            update_post_meta($post_id, 'release_year', $m['year']);
            update_post_meta($post_id, 'poster_url', $m['poster']);
            update_post_meta($post_id, 'movie_quality', '4K UHD');

            // Taxonomies
            wp_set_object_terms($post_id, $m['category'], 'movie_category');
            wp_set_object_terms($post_id, $m['genre'], 'genre');
            wp_set_object_terms($post_id, $m['country'], 'country');

            // Attach 4+ embeds
            if (function_exists('movie_elite_process_import_draft_guard')) {
                movie_elite_process_import_draft_guard($post_id, $m['imdb_id'], $m['tmdb_id']);
            }
        }
    }
}
add_action('after_switch_theme', 'movie_elite_seed_initial_data');
