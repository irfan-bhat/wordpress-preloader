<?php
/**
 * Plugin Name: WP Preloader
 * Plugin URI:  https://wordpress.org/plugins/wp-preloader/
 * Description: A customisable full-screen backdrop-blur preloader with your logo, spinner, and progress bar.
 * Version:     1.2.6
 * Author:      Irfan Bhat
 * Author URI:  https://irfanbhat.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-preloader
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'LP_VERSION',  '1.2.6' );
define( 'LP_DIR',      plugin_dir_path( __FILE__ ) );
define( 'LP_URL',      plugin_dir_url( __FILE__ ) );
define( 'LP_OPT',      'logo_preloader_options' );
define( 'LP_GITHUB_REPO', 'irfanbhat/wordpress-preloader' );

/* ---------------------------------------------------------------
   Plugin update checker (GitHub releases)
--------------------------------------------------------------- */
require_once LP_DIR . 'includes/plugin-update-checker.php';

add_action( 'plugins_loaded', function() {
    new LP_Github_Updater( LP_GITHUB_REPO, plugin_basename( __FILE__ ), 'wp-preloader' );
} );

/* ---------------------------------------------------------------
   Default options
--------------------------------------------------------------- */
function lp_defaults() {
    return [
        'overlay_color'  => '#ffffff',
        'overlay_opacity'=> 20,
        'blur_strength'  => 12,
        'accent_color'   => '#DC501E',
        'logo_url'       => '',
        'logo_width'     => 64,
        'show_bar'       => true,
        'show_ring'      => true,
        'fade_duration'  => 500,
        'min_display'    => 800,
        'enable_mobile'  => true,
    ];
}

function lp_options() {
    return wp_parse_args( get_option( LP_OPT, [] ), lp_defaults() );
}

/* ---------------------------------------------------------------
   Activation — save defaults
--------------------------------------------------------------- */
register_activation_hook( __FILE__, function() {
    if ( ! get_option( LP_OPT ) ) {
        update_option( LP_OPT, lp_defaults() );
    }
});

/* ---------------------------------------------------------------
   Front-end: inject preloader HTML right after <body>
--------------------------------------------------------------- */
add_action( 'wp_body_open', 'lp_render_preloader' );
function lp_render_preloader() {
    $o        = lp_options();
    $logo_src = esc_url( $o['logo_url'] );
    $logo_w   = absint( $o['logo_width'] );
    $show_img = $logo_src ? "<img src=\"{$logo_src}\" alt=\"\" style=\"width:{$logo_w}px;\">" : '';
    ?>
    <div id="lp-preloader" role="status" aria-label="<?php esc_attr_e( 'Loading', 'wp-preloader' ); ?>">
        <div class="lp-inner">
            <?php if ( $o['show_ring'] ) : ?>
            <div class="lp-wrap">
                <div class="lp-ring"></div>
                <?php echo $show_img; ?>
            </div>
            <?php elseif ( $show_img ) : ?>
            <div class="lp-wrap"><?php echo $show_img; ?></div>
            <?php endif; ?>

            <?php if ( $o['show_bar'] ) : ?>
            <div class="lp-bar"><div class="lp-fill"></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/* ---------------------------------------------------------------
   Front-end: enqueue CSS + JS (with inline config)
--------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'lp_enqueue_frontend' );
function lp_enqueue_frontend() {
    $o = lp_options();

    // Skip on mobile if disabled
    if ( ! $o['enable_mobile'] && wp_is_mobile() ) return;

    wp_enqueue_style(
        'wp-preloader',
        LP_URL . 'css/preloader.css',
        [],
        LP_VERSION
    );

    // Inline dynamic CSS vars
    $overlay_color   = sanitize_hex_color( $o['overlay_color'] ) ?: '#ffffff';
    $overlay_opacity = max( 0, min( 100, absint( $o['overlay_opacity'] ) ) );
    $blur            = max( 0, min( 40,  absint( $o['blur_strength'] ) ) );
    $accent          = sanitize_hex_color( $o['accent_color'] );
    $logo_w          = absint( $o['logo_width'] );

    // Convert hex + opacity to rgba
    list( $r, $g, $b ) = sscanf( $overlay_color, '#%02x%02x%02x' );
    $alpha   = round( $overlay_opacity / 100, 2 );
    $rgba    = "rgba({$r},{$g},{$b},{$alpha})";

    $inline  = "#lp-preloader{--lp-overlay:{$rgba};--lp-blur:{$blur}px;--lp-accent:{$accent};--lp-logo-w:{$logo_w}px;}";
    wp_add_inline_style( 'wp-preloader', $inline );

    wp_enqueue_script(
        'wp-preloader',
        LP_URL . 'js/preloader.js',
        [],
        LP_VERSION,
        true   // footer
    );

    // Pass config to JS
    wp_localize_script( 'wp-preloader', 'lpConfig', [
        'fade'       => absint( $o['fade_duration'] ),
        'minDisplay' => absint( $o['min_display'] ),
    ]);
}

/* ---------------------------------------------------------------
   Admin: settings page
--------------------------------------------------------------- */
add_action( 'admin_menu', function() {
    add_options_page(
        __( 'WP Preloader', 'wp-preloader' ),
        __( 'WP Preloader', 'wp-preloader' ),
        'manage_options',
        'wp-preloader',
        'lp_settings_page'
    );
});

add_action( 'admin_init', 'lp_register_settings' );
function lp_register_settings() {
    register_setting( 'wp_preloader_group', LP_OPT, [
        'sanitize_callback' => 'lp_sanitize_options',
    ]);
}

function lp_sanitize_options( $input ) {
    $d   = lp_defaults();
    $out = [];
    $out['overlay_color']   = sanitize_hex_color( $input['overlay_color']   ?? $d['overlay_color'] )   ?: $d['overlay_color'];
    $out['overlay_opacity'] = max( 0,  min( 100,  absint( $input['overlay_opacity'] ?? $d['overlay_opacity'] ) ) );
    $out['blur_strength']   = max( 0,  min( 40,   absint( $input['blur_strength']   ?? $d['blur_strength'] ) ) );
    $out['accent_color']  = sanitize_hex_color( $input['accent_color']  ?? $d['accent_color'] )  ?: $d['accent_color'];
    $out['logo_url']      = esc_url_raw( $input['logo_url']             ?? '' );
    $out['logo_width']    = max( 20, min( 300, absint( $input['logo_width']   ?? $d['logo_width'] ) ) );
    $out['fade_duration'] = max( 0,  min( 3000, absint( $input['fade_duration'] ?? $d['fade_duration'] ) ) );
    $out['min_display']   = max( 0,  min( 5000, absint( $input['min_display']   ?? $d['min_display'] ) ) );
    $out['show_bar']      = ! empty( $input['show_bar'] );
    $out['show_ring']     = ! empty( $input['show_ring'] );
    $out['enable_mobile'] = ! empty( $input['enable_mobile'] );
    return $out;
}

/* ---------------------------------------------------------------
   Admin: enqueue media uploader
--------------------------------------------------------------- */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( $hook !== 'settings_page_wp-preloader' ) return;
    wp_enqueue_media();
    wp_enqueue_style( 'lp-admin', LP_URL . 'admin/admin.css', [], LP_VERSION );
    wp_enqueue_script( 'lp-admin', LP_URL . 'admin/admin.js', [ 'jquery' ], LP_VERSION, true );
});

/* ---------------------------------------------------------------
   Settings page HTML
--------------------------------------------------------------- */
function lp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $o = lp_options();
    ?>
    <div class="wrap lp-wrap-admin">
        <h1><?php esc_html_e( 'WP Preloader Settings', 'wp-preloader' ); ?></h1>

        <div class="lp-admin-layout">
            <!-- ── Form ── -->
            <div class="lp-form-col">
                <form method="post" action="options.php">
                    <?php settings_fields( 'wp_preloader_group' ); ?>

                    <div class="lp-card">
                        <h2><?php esc_html_e( 'Logo', 'wp-preloader' ); ?></h2>

                        <div class="lp-field">
                            <label><?php esc_html_e( 'Logo image', 'wp-preloader' ); ?></label>
                            <div class="lp-media-row">
                                <input type="text" id="lp-logo-url" name="<?php echo LP_OPT; ?>[logo_url]"
                                    value="<?php echo esc_attr( $o['logo_url'] ); ?>" class="regular-text">
                                <button type="button" id="lp-upload-btn" class="button">
                                    <?php esc_html_e( 'Choose image', 'wp-preloader' ); ?>
                                </button>
                                <button type="button" id="lp-remove-btn" class="button lp-remove"
                                    <?php echo $o['logo_url'] ? '' : 'style="display:none"'; ?>>
                                    <?php esc_html_e( 'Remove', 'wp-preloader' ); ?>
                                </button>
                            </div>
                            <?php if ( $o['logo_url'] ) : ?>
                            <div id="lp-preview-wrap">
                                <img id="lp-img-preview" src="<?php echo esc_url( $o['logo_url'] ); ?>" alt="">
                            </div>
                            <?php else : ?>
                            <div id="lp-preview-wrap" style="display:none">
                                <img id="lp-img-preview" src="" alt="">
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="lp-field lp-two-col">
                            <div>
                                <label for="lp-logo-width"><?php esc_html_e( 'Logo width (px)', 'wp-preloader' ); ?></label>
                                <input type="number" id="lp-logo-width" name="<?php echo LP_OPT; ?>[logo_width]"
                                    value="<?php echo esc_attr( $o['logo_width'] ); ?>" min="20" max="300" class="small-text"> px
                            </div>
                        </div>
                    </div>

                    <div class="lp-card">
                        <h2><?php esc_html_e( 'Backdrop blur', 'wp-preloader' ); ?></h2>

                        <div class="lp-field lp-two-col">
                            <div>
                                <label for="lp-overlay-color"><?php esc_html_e( 'Overlay tint', 'wp-preloader' ); ?></label>
                                <input type="color" id="lp-overlay-color" name="<?php echo LP_OPT; ?>[overlay_color]"
                                    value="<?php echo esc_attr( $o['overlay_color'] ); ?>">
                                <input type="text" class="lp-hex-text" data-for="lp-overlay-color"
                                    value="<?php echo esc_attr( $o['overlay_color'] ); ?>" maxlength="7">
                            </div>
                            <div>
                                <label for="lp-accent"><?php esc_html_e( 'Accent (ring & bar)', 'wp-preloader' ); ?></label>
                                <input type="color" id="lp-accent" name="<?php echo LP_OPT; ?>[accent_color]"
                                    value="<?php echo esc_attr( $o['accent_color'] ); ?>">
                                <input type="text" class="lp-hex-text" data-for="lp-accent"
                                    value="<?php echo esc_attr( $o['accent_color'] ); ?>" maxlength="7">
                            </div>
                        </div>

                        <div class="lp-field">
                            <label for="lp-overlay-opacity">
                                <?php esc_html_e( 'Overlay opacity', 'wp-preloader' ); ?>
                                <span id="lp-opacity-val" class="lp-range-val"><?php echo esc_html( $o['overlay_opacity'] ); ?>%</span>
                            </label>
                            <input type="range" id="lp-overlay-opacity" name="<?php echo LP_OPT; ?>[overlay_opacity]"
                                min="0" max="100" step="1" value="<?php echo esc_attr( $o['overlay_opacity'] ); ?>">
                        </div>

                        <div class="lp-field">
                            <label for="lp-blur">
                                <?php esc_html_e( 'Blur strength', 'wp-preloader' ); ?>
                                <span id="lp-blur-val" class="lp-range-val"><?php echo esc_html( $o['blur_strength'] ); ?>px</span>
                            </label>
                            <input type="range" id="lp-blur" name="<?php echo LP_OPT; ?>[blur_strength]"
                                min="0" max="40" step="1" value="<?php echo esc_attr( $o['blur_strength'] ); ?>">
                        </div>
                    </div>

                    <div class="lp-card">
                        <h2><?php esc_html_e( 'Elements', 'wp-preloader' ); ?></h2>
                        <div class="lp-field lp-checks">
                            <label>
                                <input type="checkbox" name="<?php echo LP_OPT; ?>[show_ring]" value="1"
                                    <?php checked( $o['show_ring'] ); ?>>
                                <?php esc_html_e( 'Show spinner ring', 'wp-preloader' ); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="<?php echo LP_OPT; ?>[show_bar]" value="1"
                                    <?php checked( $o['show_bar'] ); ?>>
                                <?php esc_html_e( 'Show progress bar', 'wp-preloader' ); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="<?php echo LP_OPT; ?>[enable_mobile]" value="1"
                                    <?php checked( $o['enable_mobile'] ); ?>>
                                <?php esc_html_e( 'Show on mobile devices', 'wp-preloader' ); ?>
                            </label>
                        </div>
                    </div>

                    <div class="lp-card">
                        <h2><?php esc_html_e( 'Timing', 'wp-preloader' ); ?></h2>
                        <div class="lp-field lp-two-col">
                            <div>
                                <label for="lp-fade"><?php esc_html_e( 'Fade-out duration (ms)', 'wp-preloader' ); ?></label>
                                <input type="number" id="lp-fade" name="<?php echo LP_OPT; ?>[fade_duration]"
                                    value="<?php echo esc_attr( $o['fade_duration'] ); ?>" min="0" max="3000" class="small-text"> ms
                            </div>
                            <div>
                                <label for="lp-min"><?php esc_html_e( 'Minimum display time (ms)', 'wp-preloader' ); ?></label>
                                <input type="number" id="lp-min" name="<?php echo LP_OPT; ?>[min_display]"
                                    value="<?php echo esc_attr( $o['min_display'] ); ?>" min="0" max="5000" class="small-text"> ms
                                <p class="description"><?php esc_html_e( 'Preloader stays visible for at least this long even if the page loads faster.', 'wp-preloader' ); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php submit_button( __( 'Save settings', 'wp-preloader' ) ); ?>
                </form>
            </div>

            <!-- ── Live preview ── -->
            <div class="lp-preview-col">
                <div class="lp-card lp-sticky">
                    <h2><?php esc_html_e( 'Preview', 'wp-preloader' ); ?></h2>
                    <div id="lp-live-preview">
                        <div class="lp-prev-bg-sim"></div>
                        <div class="lp-prev-overlay" id="lpv-overlay"></div>
                        <div class="lp-prev-inner">
                            <div class="lp-prev-wrap" id="lpv-wrap">
                                <div class="lp-prev-ring" id="lpv-ring"></div>
                                <?php if ( $o['logo_url'] ) : ?>
                                <img id="lpv-img" src="<?php echo esc_url( $o['logo_url'] ); ?>"
                                    style="width:<?php echo esc_attr( $o['logo_width'] ); ?>px;">
                                <?php else : ?>
                                <div id="lpv-placeholder" class="lp-prev-placeholder">LOGO</div>
                                <img id="lpv-img" src="" style="display:none;width:<?php echo esc_attr( $o['logo_width'] ); ?>px;">
                                <?php endif; ?>
                            </div>
                            <div class="lp-prev-bar" id="lpv-bar">
                                <div class="lp-prev-fill" id="lpv-fill"
                                    style="background:<?php echo esc_attr( $o['accent_color'] ); ?>"></div>
                            </div>
                        </div>
                    </div>
                    <p class="description" style="text-align:center;margin-top:.5rem">
                        <?php esc_html_e( 'Updates as you change settings', 'wp-preloader' ); ?>
                    </p>
                </div>

                <div class="lp-card lp-author-card">
                    <p class="lp-author-name">Irfan Bhat</p>
                    <p class="lp-author-meta">
                        <a href="https://irfanbhat.com" target="_blank" rel="noopener">irfanbhat.com</a>
                        &nbsp;·&nbsp;
                        <a href="mailto:info@irfanbhat.com">info@irfanbhat.com</a>
                    </p>
                    <p class="lp-author-ver">WP Preloader v1.2</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}
