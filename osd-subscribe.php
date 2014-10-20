<?php
/*
Plugin Name: OSD Subscribe
Plugin URI: http://outsidesource.com
Description: A plugin that adds a customizable and filterable email subscription widget and shortcode for posts.
Version: 1.2
Author: OSD Web Development Team
Author URI: http://outsidesource.com
License: GPL2v2
*/

// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Include core
require_once('includes/osd_subscribe_core.php');
$osd_subscribe_instance = new OSD_Subscribe();

// Include additional files
require_once('includes/osd_subscribe_widget.php');

// Include all files only needed for admin
if (is_admin() && !defined('DOING_AJAX')) {
    require_once('options/osd_subscribe_options_main.php');
    require_once('options/osd_subscribe_options_subscribers.php');
    require_once('options/osd_subscribe_options_howto.php');
    require_once('includes/osd_subscribe_post.php');
}

// Include all fields for AJAX
if (is_admin() && defined('DOING_AJAX')) {
    require_once('includes/osd_subscribe_ajax.php');
}

// Install OSD Subscribe on activation
register_activation_hook(__FILE__, array($osd_subscribe_instance, 'install'));