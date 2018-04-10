<?php
/**
 * Plugin Name: Jetpack Debug Helper
 * Plugin URI: https://jetpack.com/debugger
 * Description: Use Debbug helper plugin to figure out why something is not working as expected.
 * Version: 0.1
 * Author: Automattic
 * Author URI: https://jetpack.com/
 * License: GPLv2 or later
 *
 * @package Jetpack Debugger
 */


defined( 'JETPACK__API_BASE' )               or define( 'JETPACK__API_BASE', get_option( Jetpack_Debug_Helper::API_BASE, 'https://jetpack.wordpress.com/jetpack.' ) );


Jetpack_Debug_Helper::init();

register_deactivation_hook( __FILE__, array( 'Jetpack_Debug_Helper','deactivate' ) );

class Jetpack_Debug_Helper {
    const API_BASE = 'jetpack_debugger_api_base';
    const LAST_SYNC_ERROR = 'jetpack_debugger_last_sync_error';
    static $saved_error = false;

    static function init() {
        // allows us to set the value via the api.
        add_filter( 'jetpack_options_whitelist', array( 'Jetpack_Debug_Helper', 'whitelist_options' ) );
        add_filter( 'jetpack_sync_send_data', array( 'Jetpack_Debug_Helper', 'pre_send' ), 8, 4 );
        add_filter( 'jetpack_sync_send_data', array( 'Jetpack_Debug_Helper', 'store_sync_error' ), 11, 4 );
    }

    static function deactivate() {
        delete_option(Jetpack_Debug_Helper::API_BASE );
        delete_option(Jetpack_Debug_Helper::LAST_SYNC_ERROR );
    }

    static function pre_send( $data, $codec_name, $sent_timestamp, $queue_id  ) {
        if ( empty( $data ) ) {
            update_option( Jetpack_Debug_Helper::LAST_SYNC_ERROR, array(
                'error_code' => 'pre_empty',
                'queue' => $queue_id,
                'timestamp' => $sent_timestamp,
                'codec' => $codec_name,
            ) );

            self::$saved_error = true;
        }
        return $data;
    }

    static function store_sync_error( $data, $codec_name, $sent_timestamp, $queue_id ) {
        if ( isset( $data['error_code'] ) && ! self::$saved_error ) {
            update_option( Jetpack_Debug_Helper::LAST_SYNC_ERROR, array(
                'error_code' => $data['error_code'],
                'queue' => $queue_id,
                'timestamp' => $sent_timestamp,
                'codec' => $codec_name,
            ) );
        }
        if ( empty( $data ) && ! self::$saved_error ) {
            update_option( Jetpack_Debug_Helper::LAST_SYNC_ERROR, array(
                'error_code' => 'empty',
                'queue' => $queue_id,
                'timestamp' => $sent_timestamp,
                'codec' => $codec_name,
            ) );
        }
        return $data;
    }

    static function whitelist_options( $options ) {
        return array_merge( $options, array(
            Jetpack_Debug_Helper::API_BASE,
            Jetpack_Debug_Helper::LAST_SYNC_ERROR
        ) );
    }
    
}

