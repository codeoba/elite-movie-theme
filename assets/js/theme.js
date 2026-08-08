/**
 * MovieElite Pro - Interactive Player Switcher, Lights Off, Theater Expand & Episode Selector
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
     * 2. Advanced Player Controls: Lights Off Toggle
     */
    $('#btn-toggle-lights').on('click', function() {
        var $overlay = $('#lights-off-overlay');
        var $btnText = $('#lights-btn-text');

        if ($overlay.is(':visible')) {
            $overlay.fadeOut(300);
            $btnText.text('Lights Off');
        } else {
            $overlay.fadeIn(300);
            $btnText.text('Lights On');
        }
    });

    $('#lights-off-overlay').on('click', function() {
        $(this).fadeOut(300);
        $('#lights-btn-text').text('Lights Off');
    });

    /**
     * 3. Advanced Player Controls: Expand / Theater Mode
     */
    $('#btn-toggle-expand').on('click', function() {
        var $box = $('#player-container-box');
        $box.toggleClass('expanded-theater-mode');

        if ($box.hasClass('expanded-theater-mode')) {
            $box.css({
                'max-width': '100vw',
                'margin-left': 'calc(-50vw + 50%)',
                'margin-right': 'calc(-50vw + 50%)',
                'border-radius': '0'
            });
        } else {
            $box.css({
                'max-width': '',
                'margin-left': '',
                'margin-right': '',
                'border-radius': 'var(--radius-lg)'
            });
        }
    });

    /**
     * 4. TV Show & Asian Drama Episode Selector
     */
    $(document).on('click', '.btn-episode-select', function() {
        var $epBtn = $(this);
        var ep = $epBtn.attr('data-episode');
        var season = $('#season-selector-select').val() || 1;

        $('.btn-episode-select').removeClass('active');
        $epBtn.addClass('active');

        var $activeServerTab = $('.server-tab.active');
        var currentUrl = $activeServerTab.attr('data-url') || '';

        // If URL has TV pattern, replace season & episode parameters
        if (currentUrl.indexOf('/embed/tv/') !== -1 || currentUrl.indexOf('video_id=') !== -1) {
            // Replace or append season & episode
            var newUrl = currentUrl.replace(/\/tv\/([^\/]+)\/(\d+)\/(\d+)/, '/tv/$1/' + season + '/' + ep);
            $('#main-movie-iframe').attr('src', newUrl);
        }
    });

    /**
     * 5. Hero Slider Functionality
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
     * 6. A-Z Alphabetical Filter
     */
    $('.alphabet-btn').on('click', function() {
        var $btn = $(this);
        var letter = $btn.attr('data-letter');

        if (!letter) return;

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
     * 7. Real-time Live Movie Search Filter
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
