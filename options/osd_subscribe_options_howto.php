<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Instantiate class
new OSD_Subscribe_HowTo_Settings();

// Class for subscriber settings
class OSD_Subscribe_HowTo_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_item'));
    }

    // Add options page to WP
    public function add_menu_item() {
        add_submenu_page(
            'osd-subscribe-options', 
            'OSD Subscribe How-To', 
            'How-To',
            'manage_options',
            'osd-subscribe-options/how-to', 
            array($this, 'create_page')
        ); 
    }

    // Creates the page
    public function create_page() {
        ?>
        <style>
            ul { list-style-type: disc; padding-left: 2em; }
            h3 { margin: 2.5em 0 .5em 0; }
            pre { margin-left: -100px; }
        </style>
        <div class='wrap'>
            <h2>How to use OSD Subscribe</h2>
            <h3>General Use:</h3>
            <div>
                In order to use OSD Subscribe, first make sure all of the settings are where you would like them.
                Make sure that the Confirmation and Unsubscribe page settings are set, otherwise your users will
                be redirected to a undesirable page.<br>
                After all of your settings are in place, either use a widget or a shortcode to place a form on a page. 
                This form must have at least one category associated to it in order for it to work correctly 
                (see Widget Use or Shortcode Use for more information).
                <br><br>
                Categories work in an "or" fashion. If a user subscribes to a form with three categories, when a post
                containing one of those categories is published, the subscriber will receive an email.
                <br><br>
                OSD subscribe will only be able to email subscribers if the categories are associated with a post when it
                is published. Example, if a post is published without any categories being selected and then categories 
                are added after publishing, no subscribers will be emailed. Therefore, make sure to check all the 
                categories your wish your post to be associated with before publishing.
                <br><br>
                If you forget to add a category before publishing a post, you may delete the post, re-create it and publish it
                to email all subscribers correctly.
            </div>
            <h3>Widget Use:</h3>
            <div>
                In order to use the widget, simply navigate to the
                <a href='<?php echo get_bloginfo('url'); ?>/wp-admin/widgets.php'>widgets</a> page and place the 
                "OSD Subscribe" widget in the area you would like it to be.
                <br><br>
                Most of the widget fields are not required but have defaults. In order for the widget to work properly,
                you must select at least one category for subscribers to subscribe to. The category section represents
                the categories that a subscriber will be subscribing to for that particular widget instance. You may
                check as many values as you want. However, you should be careful to only select the categories you will 
                want subscribers to be able to subscribe to.
            </div>
            <h3>Shortcode Use:</h3>
            <div>
                The shortcode <strong>[osd_subscribe]</strong> may be used in any content box for a page or post.<br>
                This shortcode does not require a closing tag and has several attributes that may be used with it:<br>
                <ul>
                    <li>
                        <strong>title</strong>
                        <div class="desc">
                            A basic WordPress widget title
                        </div>
                    </li>
                    <li>
                        <strong>class</strong>
                        <div class="desc">
                            A CSS class placed on the form wrapper. This is useful for styling.
                        </div>
                    </li>
                    <li>
                        <strong>placeholder</strong>
                        <div class="desc">
                            The placeholder on the email input (the default is "Email").
                        </div>
                    </li>
                    <li>
                        <strong>button_text</strong>
                        <div class="desc">
                            The text on the submit button (the default is "Submit").
                        </div>
                    </li>
                    <li>
                        <strong>pre_content</strong>
                        <div class="desc">
                            The content before the form. On a widget this may contain HTML but HTML will not work 
                            in the shortcode.
                        </div>
                    </li>
                    <li>
                        <strong>post_content</strong>
                        <div class="desc">
                            The content after the form. On a widget this may contain HTML but HTML will not work 
                            in the shortcode.
                        </div>
                    </li>
                    <li>
                        <strong>categories</strong>
                        <div class="desc">
                            A comma-separated list (no spaces) of category "slugs." All category
                            slugs can be found 
                            <a href='<?php echo get_bloginfo('url'); ?>/wp-admin/edit-tags.php?taxonomy=category'>here</a> or below.
                            <br>
                            <strong>THERE MUST BE AT LEAST ONE CATEGORY FOR OSD SUBSCRIBE TO WORK CORRECTLY.</strong>
                            <br>
                            The list represents the categories this form is associated with. Users who subscribe 
                            to this form will be subscribed to all future posts containing one of these categories.
                            <br>
                            Example: <strong>[osd_subscribe categories='blog,news-articles' placeholder='Email Address' button_text='Subscribe Now!']</strong>
                        </div>
                    </li>
                </ul>
                <br>
                <div>
                    <Strong>Category Slugs:</Strong>
                    <ul>
                    <?php foreach(get_categories(array("hide_empty"=>0)) as $category) { ?>
                        <li><?php echo $category->slug; ?></li>
                    <?php } ?>
                    </ul>
                </div>
            </div>
            <h3>Filter Use:</h3>
            <div>
                OSD Subscribe provides two filters: <strong>osd_subscribe</strong> and <strong>osd_subscribe_email_statuses</strong>.
                <br><br>
                <strong>osd_subscribe</strong><br>
                This filter allows a user to completely customize the layout of the subscription form.<br>
                All of the important pieces are included as parameters in the filter.<br>
                Here is an example where the message div is placed after the submit button instead of before (this code can be placed in functions.php):
                <pre>
                    // Add OSD Subscribe filter
                    add_filter('osd_subscribe', 'filter_osd_subscribe', 10, 6);

                    // OSD Subscribe filter callback
                    function filter_osd_subscribe($default, $pre_content, $email_input, $message_div, $submit_input, $post_content) {
                        return $pre_content.$email_input.$submit_input.$message_div.$post_content;
                    }
                </pre>
                This filter at least requires $email_input, $message_div, and a submit button ($submit_input) to be used in order for OSD Subscribe
                to work correctly.<br>
                $pre_content and $post_content should also be used but are not necessary.<br>
                You may place these elements in any order and wrap them in as many container elements as you like.<br>
                You may also add an additional submit button if you like. The JavaScript is fired on form submission.
                <br><br><br>
                <strong>osd_subscribe_email_statuses</strong><br>
                This filter allows a user to specify what statuses to email subscribers when they become published.<br>
                The default statuses are: new, draft, auto-draft, pending, private, and future.<br>
                When a post moves from one of these status to "published" it will email all subscribers registered to one of the categories
                of that post.<br>
                Here is an example:
                <pre>
                    // Add OSD Subscribe statuses filter
                    add_filter('osd_subscribe_email_statuses', 'filter_osd_subscribe_statuses', 10, 1);

                    // OSD Subscribe email statuses filter callback
                    function filter_osd_subscribe_statuses($statuses) {
                        $statuses = array_push($statuses, "new-status")
                        return $statuses
                    }
                </pre>
            </div>
        </div>
        <?php
    }
}