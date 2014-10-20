<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Instantiate class
new OSD_Subscribe_Post();

// This class contains all ajax callbacks
class OSD_Subscribe_Post {
    function __construct() {
        if (!isset($_GET['action']) || $_GET['action'] == "") {
            return;
        }
        add_action('admin_post_'.$_GET['action'], array($this, $_GET['action']));
    }


    // Exports subscribers to CSV
    function osd_subscribe_export_subscribers() {
        global $osd_subscribe_instance;
        $osd_subscribe_instance->export_subscribers();
        exit;
    }


    // Imports subscribers
    function osd_subscribe_import_subscribers() {
        global $osd_subscribe_instance;
        if (isset($_FILES['subscribers_csv']) && is_array($_FILES['subscribers_csv'])) {
            $osd_subscribe_instance->import_subscribers($_FILES["subscribers_csv"]["tmp_name"], $_POST['origin_url']);
        }
        exit;
    }
}