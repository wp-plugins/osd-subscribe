<?php
// If uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option("osd_subscribe_options");
delete_option("osd_subscribe_template");
delete_option("osd_subscribe_confirm_template");

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}osd_subscribe");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}osd_subscribe_categories");