<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="header">
    <div class="container">
        <div class="header-content">
            <?php sportnza_logo(); ?>

            <nav class="main-nav">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-link<?php if ( is_front_page() ) echo ' active'; ?>">
                    <?php echo esc_html( sportnza_t( 'Home' ) ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/category/sports/' ) ); ?>" class="nav-link<?php if ( is_category( 'sports' ) ) echo ' active'; ?>">
                    <?php echo esc_html( sportnza_t( 'Sports' ) ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/category/casino/' ) ); ?>" class="nav-link<?php if ( is_category( 'casino' ) ) echo ' active'; ?>">
                    <?php echo esc_html( sportnza_t( 'Casino' ) ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/promotions/' ) ); ?>" class="nav-link<?php if ( is_page( 'promotions' ) ) echo ' active'; ?>">
                    <?php echo esc_html( sportnza_t( 'Promotions' ) ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="nav-link<?php if ( is_page( 'about' ) ) echo ' active'; ?>">
                    <?php echo esc_html( sportnza_t( 'More' ) ); ?>
                </a>
            </nav>

            <div class="header-actions">
                <?php
                $current_lang = sportnza_get_lang();
                $languages    = sportnza_get_languages();
                ?>
                <div class="lang-switcher">
                    <select class="lang-select" onchange="if(this.value)window.location=this.value" aria-label="Language">
                        <?php foreach ( $languages as $code => $label ) : ?>
                            <option value="<?php echo esc_url( add_query_arg( 'lang', $code, home_url( $_SERVER['REQUEST_URI'] ) ) ); ?>"<?php selected( $current_lang, $code ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a href="https://sportaza.com/gc/" class="btn btn-green btn-skew"><span><?php echo esc_html( sportnza_t( 'Join Now' ) ); ?></span></a>
            </div>

            <button class="mobile-menu-btn" aria-label="<?php echo esc_attr( sportnza_t( 'Toggle menu' ) ); ?>">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>
