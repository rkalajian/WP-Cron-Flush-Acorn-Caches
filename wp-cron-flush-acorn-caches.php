<?php
/**
 * Plugin Name: WP Cron Flush Acorn Caches
 * Description: Schedules WP Roots Acorn commands (optimize:clear and view:cache) to run via WP-Cron. Especially good for Sage 10 themes on WPEngine.
 * Author: Rob Kalajian
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Acorn_Cron_Scheduler {

    public static function activate() {
        if ( ! wp_next_scheduled( 'acorn_optimize_clear' ) ) {
            wp_schedule_event( time(), 'three_hours', 'acorn_optimize_clear' );
        }
        if ( ! wp_next_scheduled( 'acorn_view_cache' ) ) {
            wp_schedule_event( time(), 'three_hours', 'acorn_view_cache' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'acorn_optimize_clear' );
        wp_clear_scheduled_hook( 'acorn_view_cache' );
    }

    public static function run_optimize_clear() {
        self::run_acorn_command( 'optimize:clear' );
    }

    public static function run_view_cache() {
        self::run_acorn_command( 'view:cache' );
    }

    private static function run_acorn_command( $command ) {
        if ( class_exists( 'WP_CLI' ) ) {
            // Run via WP-CLI API
            \WP_CLI::runcommand( "acorn {$command}" );
        } else {
            // Fallback to shell_exec
            $output = shell_exec( 'wp acorn ' . escapeshellarg( $command ) . ' --path=' . ABSPATH . ' 2>&1' );
            if ( $output ) {
                error_log( "Acorn {$command}: " . $output );
            }
        }
    }
}

// Hooks
register_activation_hook( __FILE__, [ 'Acorn_Cron_Scheduler', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Acorn_Cron_Scheduler', 'deactivate' ] );

add_action( 'acorn_optimize_clear', [ 'Acorn_Cron_Scheduler', 'run_optimize_clear' ] );
add_action( 'acorn_view_cache', [ 'Acorn_Cron_Scheduler', 'run_view_cache' ] );


add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['three_hours'] = [
        'interval' => 3 * HOUR_IN_SECONDS,
        'display'  => __( 'Every 3 Hours' ),
    ];
    return $schedules;
});