<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Instantiate class
new OSD_Subscribe_Ajax();

// This class contains all ajax callbacks
class OSD_Subscribe_Ajax {
    function __construct() {
        add_action('wp_loaded', array($this, 'osd_subscribe_validate_ajax'));
    }


    // Check verify and reject if it fails
    function osd_subscribe_validate_ajax() {
        if (isset($_POST['widget']) && $_POST['widget'] == "osd_subscribe") {
            if (!wp_verify_nonce($_POST['wp_nonce'], $_POST['action'])) {
                die('Invalid AJAX Request');
                exit;
            }
            add_action('wp_ajax_nopriv_'.$_POST['action'], array($this, $_POST['action']));
            add_action('wp_ajax_'.$_POST['action'], array($this, $_POST['action']));
        }
    }


    // Add a subscriber
    function osd_subscribe_add_subscriber() {
        if (isset($_POST['email'])) {
            global $osd_subscribe_instance;
            $osd_subscribe_instance->add_subscriber($_POST['email'], $_POST['categories']);
        }
        exit;
    }


    // Remove a subscriber (called from osd_subscribe_options_subscribers)
    function osd_subscribe_remove_subscriber() {
        if (isset($_POST['key'])) {
            global $osd_subscribe_instance;
            if ($osd_subscribe_instance->remove_subscriber($_POST['key'], true)) {
                echo "GOOD";
            }
        }
        exit;
    }


    // Sends test emails
    function osd_subscribe_test_email() {
        if (isset($_POST['email']) && isset($_POST['type'])) {
            global $osd_subscribe_instance;
            $osd_subscribe_instance->test_email($_POST['email'], $_POST['type']);
        }
        exit;
    }
}