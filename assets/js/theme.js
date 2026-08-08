/**
 * MovieElite Pro - Interactive Server Switcher, Hero Slider, Search & A-Z Filter
 */

jQuery(document).ready(function($) {
    
    /**
     * 1. Multi-Source Server Switcher
     */
    $(document).on('click', '.server-tab', function() {
        var $tab = $(this);
        var targetUrl = $tab.attr('data-url');
        
        if (!targetUrl) return;

        $('.server-tab').removeClass('active');
        $tab.addClass('active');

        var $iframe = $('#main-movie-iframe');
        if ($iframe.length) {
            $iframe.attr('src', targetUrl);
        }
    });

    /**
     * 2. Hero Slider Functionality
     */
    var $slides = $('.slide-item');
    var currentSlide = 0;
    var totalSlides = $slides.length;

    function showSlide(index) {
        if (totalSlides === 0) return;
        $slides.removeClass('active');
        currentSlide = (index + totalSlides) % totalSlides;
        $slides.eq(currentSlide).addClass('active');
    }

    $('#btn-hero-next').on('click', function() {
        showSlide(currentSlide + 1);
    });

    $('#btn-hero-prev').on('click', function() {
        showSlide(currentSlide - 1);
    });

    if (totalSlides > 1) {
        setInterval(function() {
            showSlide(currentSlide + 1);
        }, 6500);
    }

    /**
     * 3. A-Z Alphabetical Filter (AJAX & Local Grid Filter)
     */
    $('.alphabet-btn').on('click', function() {
        var $btn = $(this);
        var letter = $btn.attr('data-letter');

        $('.alphabet-btn').removeClass('active');
        $btn.addClass('active');

        if (letter === 'ALL') {
            $('.movie-card').show();
            return;
        }

        var foundCount = 0;
        $('.movie-card').each(function() {
            var title = ($(this).attr('data-title') || '').trim();
            var firstChar = title.charAt(0).toUpperCase();

            if (letter === '#') {
                if ($.isNumeric(firstChar)) {
                    $(this).show();
                    foundCount++;
                } else {
                    $(this).hide();
                }
            } else {
                if (firstChar === letter) {
                    $(this).show();
                    foundCount++;
                } else {
                    $(this).hide();
                }
            }
        });

        if (foundCount < 4 && typeof movie_elite_ajax !== 'undefined') {
            var $targetGrid = $('#block-recommended .movies-grid');
            $.post(movie_elite_ajax.ajax_url, {
                action: 'movie_elite_alphabet_filter',
                letter: letter,
                nonce: movie_elite_ajax.nonce
            }, function(response) {
                if (response.success && response.data) {
                    $targetGrid.html(response.data);
                }
            });
        }
    });

    /**
     * 4. Real-time Live Movie Search Filter
     */
    $('#movie-search-input').on('keyup', function() {
        var term = $(this).val().toLowerCase().trim();

        if (term === '') {
            $('.movie-card').show();
            return;
        }

        $('.movie-card').each(function() {
            var title = ($(this).attr('data-title') || '').toLowerCase();
            if (title.indexOf(term) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
