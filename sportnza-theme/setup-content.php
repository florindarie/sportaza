<?php
/**
 * Sportnza Content Setup Script
 *
 * Creates categories, sample posts, pages, and configures theme settings.
 * Access via: http://localhost:8881/?sportnza_setup=1
 *
 * @package Sportnza
 */

add_action( 'init', function() {
    if ( ! isset( $_GET['sportnza_setup'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }

    // Reset mode: delete the flag so setup can run again
    if ( $_GET['sportnza_setup'] === 'reset' ) {
        delete_option( 'sportnza_content_setup_done' );
        wp_die( 'Setup flag reset. You can now run <a href="?sportnza_setup=1">setup</a> again.' );
    }

    // Debug mode: show path info
    if ( $_GET['sportnza_setup'] === 'debug' ) {
        $dir = SPORTNZA_DIR . '/assets/images/articles/';
        $info = 'SPORTNZA_DIR: ' . SPORTNZA_DIR . '<br>';
        $info .= 'Articles dir: ' . $dir . '<br>';
        $info .= 'Dir exists: ' . ( is_dir( $dir ) ? 'YES' : 'NO' ) . '<br>';
        $info .= 'nba.jpg exists: ' . ( file_exists( $dir . 'nba.jpg' ) ? 'YES' : 'NO' ) . '<br>';
        if ( is_dir( $dir ) ) {
            $files = scandir( $dir );
            $info .= 'Files in dir: ' . implode( ', ', $files ) . '<br>';
        }
        $info .= 'Upload dir: ' . print_r( wp_upload_dir(), true );
        wp_die( $info );
    }

    // Cleanup mode: delete duplicate posts, keep only clean-slug originals
    if ( $_GET['sportnza_setup'] === 'cleanup' ) {
        $keep_ids = array( 22, 28, 29, 30, 31, 32, 34, 35, 36 );
        $all_posts = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        $deleted = 0;
        foreach ( $all_posts as $pid ) {
            if ( ! in_array( $pid, $keep_ids, true ) ) {
                wp_delete_post( $pid, true );
                $deleted++;
            }
        }
        // Also delete old categories
        $old_cats = array( 'game-insights', 'local-hubs', 'fantasy-advice', 'the-outliers' );
        foreach ( $old_cats as $slug ) {
            $term = get_term_by( 'slug', $slug, 'category' );
            if ( $term ) {
                wp_delete_term( $term->term_id, 'category' );
            }
        }
        wp_die( "Cleanup done. Deleted $deleted duplicate posts. Kept IDs: " . implode( ', ', $keep_ids ) . '. <a href="/">Visit homepage</a>' );
    }

    if ( $_GET['sportnza_setup'] !== '1' ) {
        return;
    }

    if ( get_option( 'sportnza_content_setup_done' ) ) {
        wp_die( 'Setup already completed. <a href="?sportnza_setup=reset">Reset</a> to re-run.' );
    }

    // ─── Update site settings ───────────────────────────────────────
    update_option( 'blogname', 'Sportaza' );
    update_option( 'blogdescription', 'Shape the game your way.' );
    update_option( 'permalink_structure', '/%postname%/' );

    // ─── Create pages ───────────────────────────────────────────────
    $home_page = wp_insert_post( array(
        'post_title'  => 'Home',
        'post_status' => 'publish',
        'post_type'   => 'page',
    ) );

    $about_page = wp_insert_post( array(
        'post_title'    => 'About Us',
        'post_status'   => 'publish',
        'post_type'     => 'page',
        'post_name'     => 'about',
        'page_template' => 'page-about.php',
        'post_content'  => '<p>Here at Sportaza, where gaming goes to play fair sport, we\'re built for the player experience. From the moment you log in, we want you to feel welcomed, energized, and ready to compete. This isn\'t just our gear — player satisfaction is what matters most to us and is at the heart of our satisfaction.</p>

<p>Sportaza gives you the best betting tools you need to shape any game your way. Whether you\'re a seasoned pro or just getting started, Sportaza gives you the edge to always be one step ahead.</p>

<p>With our advanced Sportaza Prematch tools like Partial Cashout, Sports Statistics, Live Match Trackers, and Bet Builder — you\'re not just placing bets, you\'re shaping outcomes. And with our expansive market coverage, you\'ll never miss a moment of the action.</p>

<p>Bet across leagues worldwide with access to millions of events, from pre-game action to live and virtual sports. Dive into thousands of high-energy slots, a vast betting catalogue, over a hundred table games, or the thrill of live casino from top studios.</p>

<p>Looking for even more competition? Experience a fantasy league vibe with Challenges, Tournaments, and everyday opportunities to win prize pools, climb leaderboards, and unlock exclusive VIP rewards.</p>

<p>We\'re not just about play, we\'re about building a legacy of winners, champions, and legends in the making.</p>',
    ) );

    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $home_page );

    // ─── Create categories ──────────────────────────────────────────
       // ─── Create categories + subcategories ─────────────────────────
    $category_structure = array(
        'sports' => array(
            'name'     => 'Sports',
            'children' => array(
                'soccer'            => 'Soccer',
                'hockey'            => 'Hockey',
                'basketball'        => 'Basketball',
                'american-football' => 'American Football',
                'tennis'            => 'Tennis',
                'other'             => 'Other',
            ),
        ),
        'casino' => array(
            'name'     => 'Casino',
            'children' => array(
                'slots'       => 'Slots',
                'blackjack'   => 'Blackjack',
                'roulette'    => 'Roulette',
                'poker'       => 'Poker',
                'table-games' => 'Table Games',
            ),
        ),
        'promotions' => array(
            'name'     => 'Promotions',
            'children' => array(),
        ),
    );

    $cat_ids = array();

    foreach ( $category_structure as $parent_slug => $parent_data ) {
        $term = term_exists( $parent_slug, 'category' );

        if ( ! $term ) {
            $term = wp_insert_term(
                $parent_data['name'],
                'category',
                array(
                    'slug' => $parent_slug,
                )
            );
        }

        if ( ! is_wp_error( $term ) ) {
            $parent_id = is_array( $term ) ? $term['term_id'] : $term;
            $cat_ids[ $parent_slug ] = (int) $parent_id;

            foreach ( $parent_data['children'] as $child_slug => $child_name ) {
                $child_term = term_exists( $child_slug, 'category' );

                if ( ! $child_term ) {
                    $child_term = wp_insert_term(
                        $child_name,
                        'category',
                        array(
                            'slug'   => $child_slug,
                            'parent' => $parent_id,
                        )
                    );
                } else {
                    $child_term_id = is_array( $child_term ) ? $child_term['term_id'] : $child_term;
                    wp_update_term(
                        $child_term_id,
                        'category',
                        array(
                            'parent' => $parent_id,
                        )
                    );
                }

                if ( ! is_wp_error( $child_term ) ) {
                    $cat_ids[ $child_slug ] = is_array( $child_term ) ? $child_term['term_id'] : $child_term;
                }
            }
        }
    }

    // ─── Create tags ────────────────────────────────────────────────
    $tags = array( 'NBA', 'NFL', 'NHL', 'Hockey', 'Fantasy', 'Strategy', 'Golf', 'Betting', 'Casino', 'Slots' );
    $tag_ids = array();
    foreach ( $tags as $tag_name ) {
        $tag = term_exists( $tag_name, 'post_tag' );
        if ( ! $tag ) {
            $tag = wp_insert_term( $tag_name, 'post_tag' );
        }
        if ( ! is_wp_error( $tag ) ) {
            $tag_ids[ $tag_name ] = is_array( $tag ) ? $tag['term_id'] : $tag;
        }
    }

    // ─── Upload images from theme's articles directory ─────────────
    $images_dir = SPORTNZA_DIR . '/assets/images/articles/';

    function sportnza_upload_image( $images_dir, $filename ) {
        $filepath = $images_dir . $filename;
        if ( ! file_exists( $filepath ) ) {
            return 0;
        }

        $file_data = file_get_contents( $filepath );
        if ( ! $file_data ) {
            return 0;
        }

        $upload = wp_upload_bits( $filename, null, $file_data );
        if ( ! empty( $upload['error'] ) ) {
            return 0;
        }

        $filetype   = wp_check_filetype( $filename );
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
        if ( ! is_wp_error( $attach_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $metadata = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
            wp_update_attachment_metadata( $attach_id, $metadata );
        }

        return $attach_id;
    }

    $images = array(
        'nba'               => sportnza_upload_image( $images_dir, 'nba.jpg' ),
        'nfl'               => sportnza_upload_image( $images_dir, 'nfl.jpg' ),
        'stanley_cup'       => sportnza_upload_image( $images_dir, 'stanley-cup.jpg' ),
        'local_hubs_1'      => sportnza_upload_image( $images_dir, 'local-hubs.jpg' ),
        'local_hubs_2'      => sportnza_upload_image( $images_dir, 'rivalry.jpg' ),
        'fantasy_nhl'       => sportnza_upload_image( $images_dir, 'fantasy-nhl.jpg' ),
        'fantasy_sell_high' => sportnza_upload_image( $images_dir, 'fantasy-sell-high.jpg' ),
        'saturday_hockey'   => sportnza_upload_image( $images_dir, 'saturday-hockey.jpg' ),
        'pga'               => sportnza_upload_image( $images_dir, 'pga.jpg' ),
    );

    // ─── Create posts in new categories ────────────────────────────

    // SPORTS
    $post1 = wp_insert_post( array(
        'post_title'    => 'NBA Analytics: Breaking Down the Numbers That Matter',
        'post_excerpt'  => 'Dive deep into advanced statistics and probability models that can give you the edge in NBA betting.',
        'post_content'  => '<p>In the world of sports betting, data is king. Advanced analytics have transformed how we understand basketball, and savvy bettors are leveraging these insights to make more informed decisions.</p><p>From player efficiency ratings to pace-adjusted statistics, the numbers tell a story that the eye test alone cannot capture. Understanding these metrics can be the difference between a winning and losing strategy.</p><p>In this deep dive, we break down the key statistical models that professional analysts use to evaluate NBA games, including expected value calculations, regression models, and real-time probability assessments.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-06 10:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['basketball'] ),
        'tags_input'    => array( 'NBA', 'Betting' ),
    ) );
    if ( $images['nba'] ) set_post_thumbnail( $post1, $images['nba'] );

    $post2 = wp_insert_post( array(
        'post_title'    => 'NFL Conference Championships: Complete Betting Guide',
        'post_excerpt'  => 'Everything you need to know for the conference championship matchups this season.',
        'post_content'  => '<p>The conference championships represent some of the most exciting betting opportunities of the NFL season. With only four teams remaining, every detail matters.</p><p>Our comprehensive guide covers point spreads, over/under totals, player props, and live betting strategies for both the AFC and NFC championship games.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-05 10:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['american-football'] ),
        'tags_input'    => array( 'NFL', 'Betting' ),
    ) );
    if ( $images['nfl'] ) set_post_thumbnail( $post2, $images['nfl'] );

    $post3 = wp_insert_post( array(
        'post_title'    => 'NHL Stanley Cup Futures: Early Value Picks',
        'post_excerpt'  => 'Finding the best value in Stanley Cup futures markets before the playoffs begin.',
        'post_content'  => '<p>With the NHL regular season in full swing, futures markets are constantly shifting. Smart bettors know that finding value early can lead to significant payoffs.</p><p>We analyze the top contenders, sleeper picks, and the metrics that matter most when evaluating Stanley Cup odds.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-04 10:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['hockey'] ),
        'tags_input'    => array( 'NHL', 'Hockey' ),
    ) );
    if ( $images['stanley_cup'] ) set_post_thumbnail( $post3, $images['stanley_cup'] );

    $post4 = wp_insert_post( array(
        'post_title'    => 'Fantasy Hockey: Finding Sleeper Picks for Value',
        'post_excerpt'  => 'Uncovering hidden gems that can win your fantasy hockey league this season.',
        'post_content'  => '<p>Fantasy hockey success often hinges on finding undervalued players. Whether you are drafting or working the waiver wire, identifying sleepers before they break out is key.</p><p>We highlight the players flying under the radar who could deliver elite production at a fraction of the cost.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-06 08:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['hockey'] ),
        'tags_input'    => array( 'Fantasy', 'Hockey' ),
    ) );
    if ( $images['fantasy_nhl'] ) set_post_thumbnail( $post4, $images['fantasy_nhl'] );

    $post5 = wp_insert_post( array(
        'post_title'    => "Identifying 'Sell High' Candidates in Your Fantasy Lineup",
        'post_excerpt'  => 'Maximize your roster value by knowing when to trade at peak performance.',
        'post_content'  => '<p>One of the most important skills in fantasy sports management is recognizing when a player\'s current production is unsustainable. Selling high at the right time can transform your roster.</p><p>We analyze the key indicators that suggest a player is due for regression and the optimal trade strategies to capitalize.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-05 08:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['other'] ),
        'tags_input'    => array( 'Strategy', 'Fantasy' ),
    ) );
    if ( $images['fantasy_sell_high'] ) set_post_thumbnail( $post5, $images['fantasy_sell_high'] );

    $post6 = wp_insert_post( array(
        'post_title'    => 'PGA Tour Analytics: Data-Driven Approach to Golf Betting',
        'post_excerpt'  => 'How strokes gained metrics and course history can predict tournament outcomes with surprising accuracy.',
        'post_content'  => '<p>Golf betting has been revolutionized by advanced analytics, particularly the strokes gained framework. By breaking down performance into specific categories we can build predictive models with impressive accuracy.</p><p>Course history is another critical factor. Some players consistently perform well at certain venues due to course design, grass types, altitude, and weather patterns that suit their game.</p><p>In this comprehensive analysis, we combine strokes gained data with course-specific metrics to identify the best value picks for upcoming PGA Tour events.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-03 07:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['other'] ),
        'tags_input'    => array( 'Golf' ),
    ) );
    if ( $images['pga'] ) set_post_thumbnail( $post6, $images['pga'] );

    $post7 = wp_insert_post( array(
        'post_title'    => 'Regional Betting Insights: Know Your Local Teams',
        'post_excerpt'  => 'Why local knowledge gives you the edge in sports betting and how to leverage it.',
        'post_content'  => '<p>When it comes to sports betting, local knowledge can be your greatest asset. Understanding team dynamics, fan culture, travel schedules, and venue-specific factors gives you insights that national analysts often miss.</p><p>In this guide, we explore how regional expertise translates into better betting decisions.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-02 09:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['other'] ),
        'tags_input'    => array( 'Betting' ),
    ) );
    if ( $images['local_hubs_1'] ) set_post_thumbnail( $post7, $images['local_hubs_1'] );

    $post8 = wp_insert_post( array(
        'post_title'    => 'The Rivalry That Defined a Nation: Senators vs Maple Leafs',
        'post_excerpt'  => 'Exploring the intense history between two Canadian hockey giants and what it means for betting.',
        'post_content'  => '<p>Few rivalries in hockey carry the weight and passion of Senators vs Maple Leafs. Rooted in Canadian identity and decades of competition, this matchup transcends the sport.</p><p>From regular season battles to playoff wars, we trace the history and intensity that makes this one of hockey\'s greatest rivalries.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-06 09:00:00',
        'post_category' => array( $cat_ids['sports'], $cat_ids['hockey'] ),
        'tags_input'    => array( 'Hockey' ),
    ) );
    if ( $images['local_hubs_2'] ) set_post_thumbnail( $post8, $images['local_hubs_2'] );

    // CASINO
    $post9 = wp_insert_post( array(
        'post_title'    => 'Top Slots Features Players Look for in 2026',
        'post_excerpt'  => 'From bonus rounds to volatility, here is what makes modern slot games stand out.',
        'post_content'  => '<p>Slots continue to dominate the casino experience thanks to their variety, pace, and rewarding mechanics. Players are paying closer attention to RTP, volatility, and feature depth than ever before.</p><p>In this guide, we break down the most important elements that shape today\'s slot experience.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-06 11:00:00',
        'post_category' => array( $cat_ids['casino'], $cat_ids['slots'] ),
        'tags_input'    => array( 'Casino', 'Slots' ),
    ) );

    $post10 = wp_insert_post( array(
        'post_title'    => 'Blackjack Basics: Smart Decisions at the Table',
        'post_excerpt'  => 'A simple look at core blackjack principles every player should understand.',
        'post_content'  => '<p>Blackjack remains one of the most strategy-driven casino games. While luck always matters, making better decisions on hits, stands, doubles, and splits can improve your long-term results.</p><p>This article covers the essentials in a beginner-friendly way.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-05 11:00:00',
        'post_category' => array( $cat_ids['casino'], $cat_ids['blackjack'] ),
        'tags_input'    => array( 'Casino' ),
    ) );

    $post11 = wp_insert_post( array(
        'post_title'    => 'Live Roulette: Why Real-Time Tables Keep Growing',
        'post_excerpt'  => 'Live roulette combines classic gameplay with the energy of a real casino environment.',
        'post_content'  => '<p>Live roulette brings players closer to the casino floor by combining real dealers, live streams, and immersive table action.</p><p>We look at why the format continues to attract both experienced and casual players.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-04 11:00:00',
        'post_category' => array( $cat_ids['casino'], $cat_ids['roulette'] ),
        'tags_input'    => array( 'Casino' ),
    ) );

    $post12 = wp_insert_post( array(
        'post_title'    => 'Poker Room Trends: What Online Players Want Most',
        'post_excerpt'  => 'Faster formats, cleaner UX, and better rewards are shaping online poker.',
        'post_content'  => '<p>Online poker keeps evolving with new tournament formats, quicker gameplay, and player-focused features. The modern poker audience expects both flexibility and value.</p><p>These are the trends shaping the next wave of poker experiences.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-03 11:00:00',
        'post_category' => array( $cat_ids['casino'], $cat_ids['poker'] ),
        'tags_input'    => array( 'Casino' ),
    ) );

    $post13 = wp_insert_post( array(
        'post_title'    => 'Classic Table Games That Never Go Out of Style',
        'post_excerpt'  => 'From baccarat to blackjack, table games remain a cornerstone of online casino play.',
        'post_content'  => '<p>Table games continue to define the classic casino experience. Their simple rules, elegant presentation, and strategic depth make them timeless.</p><p>This piece explores why players keep coming back to traditional table action.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-02 11:00:00',
        'post_category' => array( $cat_ids['casino'], $cat_ids['table-games'] ),
        'tags_input'    => array( 'Casino' ),
    ) );

    // PROMOTIONS
    $post14 = wp_insert_post( array(
        'post_title'    => 'Saturday Night Hockey Special: Best Bets & Bonuses',
        'post_excerpt'  => 'Our top picks for the best Saturday night hockey betting opportunities plus exclusive bonuses.',
        'post_content'  => '<p>Saturday night hockey is a tradition, and it also presents some of the best betting opportunities of the week. With a full slate of games, the options are endless.</p><p>Our experts break down the top bets, including moneylines, puck lines, and prop bets for the featured matchups. Plus, check out our exclusive Saturday night bonuses.</p>',
        'post_status'   => 'publish',
        'post_date'     => '2026-02-04 08:00:00',
        'post_category' => array( $cat_ids['promotions'] ),
        'tags_input'    => array( 'NHL', 'Betting' ),
    ) );
    if ( $images['saturday_hockey'] ) set_post_thumbnail( $post14, $images['saturday_hockey'] );

    // ─── Set up Customizer (hero uses theme asset directly) ────────
    set_theme_mod( 'sportnza_hero_bg', SPORTNZA_URI . '/assets/images/hero-banner.jpg' );
    set_theme_mod( 'sportnza_hero_title', 'Claim Your Welcome Bonus' );
    set_theme_mod( 'sportnza_hero_subtitle', 'Join the Fun at Sportaza' );
    set_theme_mod( 'sportnza_hero_cta_primary_text', 'Join Now' );

    // ─── Delete default content ─────────────────────────────────────
    wp_delete_post( 1, true );
    wp_delete_post( 2, true );

    // ─── Mark setup as done ─────────────────────────────────────────
    update_option( 'sportnza_content_setup_done', true );
    flush_rewrite_rules();

    wp_die( '<h1>Sportaza Setup Complete!</h1><p>All categories, posts, pages, and settings have been configured.</p><p><a href="' . home_url( '/' ) . '">Visit Homepage</a> | <a href="' . admin_url() . '">Go to Admin</a></p>' );
} );
