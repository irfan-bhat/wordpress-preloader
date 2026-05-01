<?php
/**
 * Plugin Update Checker Library
 * GitHub integration for WordPress plugin updates
 * 
 * @link https://github.com/YahnisElsts/plugin-update-checker
 */

if ( ! class_exists( 'LP_Github_Updater' ) ) {
    class LP_Github_Updater {
        private $github_repo;
        private $plugin_file;
        private $plugin_slug;
        private $transient_name;
        private $cache_time = 12 * HOUR_IN_SECONDS;

        public function __construct( $github_repo, $plugin_file, $plugin_slug ) {
            $this->github_repo = $github_repo;
            $this->plugin_file = $plugin_file;
            $this->plugin_slug = $plugin_slug;
            $this->transient_name = 'lp_github_update_' . $this->plugin_slug;

            add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_updates' ] );
            add_filter( 'plugins_api', [ $this, 'plugin_api_info' ], 10, 3 );
        }

        public function check_for_updates( $transient ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            $remote = $this->get_remote_info();

            if ( ! $remote || is_wp_error( $remote ) ) {
                return $transient;
            }

            if ( version_compare( $transient->checked[ $this->plugin_file ], $remote->version, '<' ) ) {
                $transient->response[ $this->plugin_file ] = $remote;
            }

            return $transient;
        }

        public function plugin_api_info( $result, $action, $args ) {
            if ( $action !== 'plugin_information' ) {
                return $result;
            }

            if ( isset( $args->slug ) && $args->slug === $this->plugin_slug ) {
                $remote = $this->get_remote_info();

                if ( ! $remote || is_wp_error( $remote ) ) {
                    return $result;
                }

                return $remote;
            }

            return $result;
        }

        private function get_remote_info() {
            $cached = get_transient( $this->transient_name );

            if ( ! empty( $cached ) ) {
                return $cached;
            }

            $response = wp_remote_get(
                'https://api.github.com/repos/' . $this->github_repo . '/releases/latest',
                [
                    'timeout'    => 5,
                    'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
                ]
            );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $body = json_decode( wp_remote_retrieve_body( $response ) );

            if ( empty( $body ) || ! isset( $body->tag_name ) ) {
                return null;
            }

            $version = ltrim( $body->tag_name, 'v' );

            // Find the ZIP asset
            $download_url = null;
            if ( ! empty( $body->assets ) ) {
                foreach ( $body->assets as $asset ) {
                    if ( strpos( $asset->name, '.zip' ) !== false ) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            // Fallback to archive download
            if ( ! $download_url ) {
                $download_url = 'https://github.com/' . $this->github_repo . '/archive/' . $body->tag_name . '.zip';
            }

            $remote = new stdClass();
            $remote->name = isset( $body->name ) ? $body->name : 'WP Preloader';
            $remote->slug = $this->plugin_slug;
            $remote->version = $version;
            $remote->tested = get_bloginfo( 'version' );
            $remote->requires = '5.0';
            $remote->requires_php = '7.2';
            $remote->download_link = $download_url;
            $remote->package = $download_url;
            $remote->url = $body->html_url;
            $remote->homepage = 'https://github.com/' . $this->github_repo;
            $remote->sections = [
                'description'  => isset( $body->body ) ? $body->body : '',
                'installation' => 'Upload the plugin folder to your /wp-content/plugins/ directory.',
                'changelog'    => isset( $body->body ) ? $body->body : 'See GitHub releases for details.',
            ];
            $remote->banners = [];
            $remote->icons = [];

            set_transient( $this->transient_name, $remote, $this->cache_time );

            return $remote;
        }
    }
}
