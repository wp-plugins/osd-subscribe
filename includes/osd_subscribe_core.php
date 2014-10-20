<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Core OSD Subscribe class
class OSD_Subscribe {
    // Variables
    const SUCCESS = 0;
    const ERROR_INVALID_EMAIL = 1;
    const ERROR_EMAIL_EXISTS = 2;
    const ERROR_FAILED_INSERT = 3;
    const ERROR_CONFIRM_MESSAGE_FAILURE = 4;
    const ERROR_CONFIRM_FAILURE = 5;
    const ERROR_NO_CATEGORIES = 6;

    // Additional Variables
    public $subscribers_return_limit = 50;
    private $table_name = "";
    private $categories_table_name = "";
    private $options = null;
    private $defaults = array();
    private $headers = array();


    // Constructor
    function __construct() {
        global $wpdb;

        // Set up defaults
        $this->defaults = array(
            "from" => get_bloginfo('name')." <".get_bloginfo('admin_email').">",
            "admin_emails" => "both",
            "add_categories_to_pages" => "false",
            "loading_message" => "Subscribing...",
            "confirm_message" => "A confirmation email has been sent to your email address.",
            "error_general" => "Sorry, there was an error. Please try again.",
            "error_invalid_email" => "Sorry, the email you provided is not valid.",
            "error_already_subscribed" => "Sorry, that email address is already subscribed.",
            "error_no_categories" => "Sorry, there are no categories associated with this subscription.",
            "template_subject" => "{SITENAME} has posted a new item",
            "confirm_template_subject" => "{SITENAME} - Please confirm your subscription request",
            "template" => "{SITENAME} has posted a new item, '{TITLE}'\n<br><br>\n{CONTENT}\n<br><br>\nYou may view the latest post <a target='_blank' href='{LINK}'>here</a>\n<br><br>\nYou received this e-mail because you asked to be notified when new items are posted.<br>\nIf you would not like to receive further notifications, <a target='_blank' href='{UNSUBSCRIBEURL}'>unsubscribe</a>\n<br><br>\nCompany Name<br>\n1234 Example Ln<br>\nCity, State 123456",
            "confirm_template" => "{SITENAME} has recieved a subscription request for this email address. To confirm your request please click on the link below:\n<br><br>\n<a target='_blank' href='{CONFIRMURL}'>Confirm subscription</a>\n<br><br>\nIf you did not request this, please disregard this message.\n<br><br>\nThank you,\n{SITENAME}.",
            "confirm_page" => "",
            "unsubscribe_page" => "",
        );
        $this->table_name = $wpdb->prefix . "osd_subscribe";
        $this->categories_table_name = $wpdb->prefix . "osd_subscribe_categories";
        $this->options = $this->get_options();
        $this->headers = array(
            "From: {$this->options['from']}",
            "Content-type: text/html"
        );

        // If the user is either unsubscribing or confirming a subscription
        if (isset($_GET['osd_subscribe'])) {
            add_filter('request', array(&$this, 'load_response_page'));
        }

        // Add hooks for publishing
        if (is_admin() && !defined('DOING_AJAX')) {
            $statuses = apply_filters('osd_subscribe_email_statuses', array('new', 'draft', 'auto-draft', 'pending', 'private', 'future'));
            foreach ($statuses as $status) {
                add_action("{$status}_to_publish", array(&$this, "publish"));
            }
        }

        // Add hooks for frontend users
        if (!is_admin()) {
            add_action('wp_footer', array(&$this, 'insert_frontend_js')); // Add JS
            add_shortcode('osd_subscribe', array(&$this, 'shortcode')); // Include shortcode
        }

        // Add settings link to plugin page
        if (is_admin()) {
            add_filter('plugin_action_links_osd-subscribe/osd-subscribe.php', array($this, "settings_link"));
        }

        // Allow pages to have categories
        if ($this->options["add_categories_to_pages"] == "true") {
            add_action('init', array($this, 'categories_for_pages'));
        }
    }


    // Allow pages to have categories
    function categories_for_pages() {
        register_taxonomy_for_object_type('category', 'page');
    }


    // Install OSD Subscribe 
    function install() {
        global $wpdb;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name)) == $this->table_name) {
            return;
        }
        $query = "CREATE TABLE `$this->table_name` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(100) NOT NULL UNIQUE DEFAULT '',
            `active` tinyint(1) DEFAULT 0,
            `key` VARCHAR(64) NOT NULL UNIQUE DEFAULT '',
            `time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
            `ip` char(64) NOT NULL DEFAULT '',
            `confirm_time` TIMESTAMP,
            `confirm_ip` char(64) NOT NULL DEFAULT '',
            PRIMARY KEY (id))";
        $wpdb->query($query);

        $query = "CREATE TABLE `$this->categories_table_name` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `subscriber_id` int(11) NOT NULL,
            `category` VARCHAR(64) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            INDEX `subscriber_id` USING BTREE (`subscriber_id`))";
        $wpdb->query($query);
    }


    // Returns the number of subscribers
    function get_subscribers_count($get_active = false) {
        global $wpdb;
        $where = ($get_active == true) ? " WHERE `active` = 1" : "";
        $query = "SELECT COUNT(*) as `count` FROM `{$this->table_name}`{$where}";
        $results = $wpdb->get_results($query);
        return $results[0]->count;
    }


    // Return all users
    function get_subscribers($get_active = false, $limit = false, $page = 0, $with_categories = false) {
        global $wpdb;
        $limit_offset = $page * $this->subscribers_return_limit;
        $where = ($get_active == true) ? " WHERE `active` = 1" : "";
        $limit = ($limit == true) ? " LIMIT {$limit_offset},{$this->subscribers_return_limit}" : "";
        $query = "SELECT * FROM `{$this->table_name}`{$where}{$limit}";
        $subscribers = $wpdb->get_results($query);

        // Get categories if requested
        if ($with_categories == true) {
            foreach ($subscribers as $subscriber) {
                $query = $wpdb->prepare("SELECT `category` FROM `{$this->categories_table_name}` WHERE `subscriber_id` = %d", $subscriber->id);
                $categories = $wpdb->get_results($query);
                $subscriber->categories = array();
                foreach ($categories as $category) {
                    $subscriber->categories[] = $category->category;
                }
            }
        }
        return $subscribers;
    }


    // Output the form given the attributes (called by shortcode and widget)
    static function get_form_html($attrs) {
        $title = (isset($attrs['title']) && $attrs['title'] != "") ? "<h1 class='widgettitle'>".strip_tags($attrs['title'])."</h2>" : "";
        $class = (isset($attrs['class']) && $attrs['class'] != "") ? " ".strip_tags($attrs['class']) : "";
        $placeholder = (isset($attrs['placeholder']) && $attrs['placeholder'] != "") ? $attrs['placeholder'] : "Email";
        $button_text = (isset($attrs['button_text']) && $attrs['button_text'] != "") ? $attrs['button_text'] : "Subscribe";
        $pre_content = (isset($attrs['pre_content']) && $attrs['pre_content'] != "") ? "<div class='osd-subscribe-pre-content'>{$attrs['pre_content']}</div>" : "";
        $post_content = (isset($attrs['post_content']) && $attrs['post_content'] != "") ? "<div class='osd-subscribe-post-content'>{$attrs['post_content']}</div>" : "";
        $categories = (isset($attrs['categories']) && $attrs['categories'] != "") ? $attrs['categories'] : "";
        $categories = (is_array($categories)) ? implode(",", $categories) : $categories;

        // Set up filterable elements
        $email_input = "<input class='osd-subscribe-email' type='email' name='email' placeholder='{$placeholder}' />";
        $submit_input = "<input class='osd-subscribe-submit' type='submit' value='{$button_text}' />";
        $message_div = "<div class='osd-subscribe-message'></div>";
        $filterable = "
            {$pre_content}
            <div class='osd-subscribe-email-cont'>
                {$email_input}
            </div>
            {$message_div}
            {$submit_input}
            {$post_content}";
        
        $output = "
            <div class='widget osd-subscribe{$class}'>
                {$title}
                <form class='osd-subscribe-form'>
                    ".apply_filters("osd_subscribe", $filterable, $pre_content, $email_input, $message_div, $submit_input, $post_content)."
                    <input type='hidden' class='osd-subscribe-categories' name='osd-subscribe-categories' value='{$categories}' />
                </form>
            </div>";
        return $output;
    }


    // Replaces placeholders in email template
    function replace_placeholders($options, $args) {
        $args["key"] = (isset($args["key"])) ? $args["key"] : "";
        $content = (isset($args["content"])) ? $args["content"] : "";
        $title = (isset($args["title"])) ? $args["title"] : "";
        $link = (isset($args["link"])) ? $args["link"] : "";
        $site = get_bloginfo('name');
        $url = get_bloginfo('url');

        foreach ($options as $key => $value) {
            $options[$key] = str_replace("{SITENAME}", $site, $options[$key]);
            $options[$key] = str_replace("{CONFIRMURL}", $url."?osd_subscribe&confirm={$args["key"]}", $options[$key]);
            $options[$key] = str_replace("{UNSUBSCRIBEURL}", $url."?osd_subscribe&unsubscribe={$args["key"]}", $options[$key]);
            $options[$key] = str_replace("{CONTENT}", $content, $options[$key]);
            $options[$key] = str_replace("{TITLE}", $title, $options[$key]);
            $options[$key] = str_replace("{LINK}", $link, $options[$key]);
        }
        return $options;
    }


    // Get all options
    function get_options() {
        if ($this->options != null) {
            return $this->options;
        }

        $options = get_option('osd_subscribe_options');
        if ($options == null || !is_array($options)) {
            $options = array();
        }
        $options['template'] = get_option('osd_subscribe_template');
        $options['confirm_template'] = get_option('osd_subscribe_confirm_template');

        foreach ($this->defaults as $key => $value) {
            if (!isset($options[$key]) || $options[$key] == "") {
                $options[$key] = $this->defaults[$key];
            }
        }
        
        return $options;
    }


    // Add a subscriber
    function add_subscriber($email, $categories = "", $importing = false) {
        global $wpdb;
        // Check if valid email
        if (!is_email($email)) {
            return $this->return_message(true, ERROR_INVALID_EMAIL, $this->options['error_invalid_email'], $importing);
        }

        // Check categories
        $categories = ($categories == "") ? array() : explode(",", $categories);

        // If no categories return
        if (count($categories) == 0) {
            return $this->return_message(true, ERROR_NO_CATEGORIES, $this->options['error_no_categories'], $importing);
        }

        // Check if the user already exists
        $query = $wpdb->prepare("SELECT `id`, `email`, `active`, `key` FROM `$this->table_name` WHERE `email` = %s", $email);
        $results = $wpdb->get_results($query);

        // If user exists already, just add categories and resend confirmation email
        if (count($results) > 0) {
            // Add categories to user if there are new categories
            $query = $wpdb->prepare("SELECT `category` FROM `$this->categories_table_name` WHERE `subscriber_id` = %d", $results[0]->id);
            $category_rows = $wpdb->get_results($query);
            $existing_categories = array();
            $new_categories = array();
            
            // If categories are already associated to the subscriber find which ones are new
            foreach ($category_rows as $rw_category) {
                array_push($existing_categories, $rw_category->category);
            }
            foreach ($categories as $category) {
                if (!in_array($category, $existing_categories)) {
                    array_push($new_categories, $category);
                }
            }

            // Insert new categories if they exist
            if (count($new_categories) > 0) {
                if (!$this->insert_categories($results[0]->id, $new_categories)) {
                    return $this->return_message(true, ERROR_FAILED_INSERT, "Unable to add new categories to subscriber", $importing);
                }
            }

            // If user is active and no new categories have been added, error out
            if ($results[0]->active == 1 && count($new_categories) == 0) {
                return $this->return_message(true, ERROR_EMAIL_EXISTS, $this->options['error_already_subscribed'], $importing);
            }
            $key = $results[0]->key;
        } else {
            // If user doesn't exist, insert into DB
            $key = substr(hash("SHA256", openssl_random_pseudo_bytes(64, $crypt_strong)), 0, 32);
            if ($importing == true) {
                $ip = "Imported";
            } else {
                $ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            }
            $active = ($importing == true) ? 1 : 0;

            // Insert new user and return on failure
            $query = $wpdb->prepare("INSERT INTO `$this->table_name` (`email`, `key`, `ip`, `active`) VALUES(%s, %s, %s, %d)", $email, $key, $ip, $active);
            $results = $wpdb->query($query);

            if ($results == false || $results == 0) {
                return $this->return_message(true, ERROR_FAILED_INSERT, "Unable to add subscriber", $importing);
            }

            // Insert categories
            if (!$this->insert_categories($wpdb->insert_id, $categories)) {
                return $this->return_message(true, ERROR_FAILED_INSERT, "Unable to insert categories", $importing);
            }
        }

        // Get confirmation template and mail confirmation message to subscriber
        if ($importing == false) {
            $replaced_options = $this->replace_placeholders(
                array(
                    "confirm_template_subject" => $this->options['confirm_template_subject'],
                    "confirm_template" => $this->options['confirm_template']), 
                array("key" => $key));
            
            $mail_success = wp_mail($email, $replaced_options['confirm_template_subject'], $replaced_options['confirm_template'], $this->headers, "");
            
            if (!$mail_success) {
                return $this->return_message(true, ERROR_CONFIRM_MESSAGE_FAILURE, "Failed to send the confirmation message");
            }
        }

        // Return success
        if ($importing == true) {
            return $this->return_message(false, SUCCESS, "Users successfully imported", $importing);            
        } else {
            return $this->return_message(false, SUCCESS, $this->options['confirm_message'], $importing);
        }
    }


    // Runs when a post is published. Sends the email to all subscribers
    function publish($post) {
        // Setup variables
        global $wpdb;
        setup_postdata($post);
        $excerpt = get_the_excerpt();
        $categories = wp_get_post_categories($post->ID, array("fields"=>"slugs"));

        // No categories associated with post (probably a page or a different post type)
        if (count($categories) == 0) {
            return;
        }

        // Build query for getting subscribers associated with the post's categories
        $query = "SELECT DISTINCT a.`email`, a.`key`
            FROM `$this->table_name` a 
            LEFT JOIN `$this->categories_table_name` b ON a.`id` = b.`subscriber_id`
            WHERE a.`active` = 1 AND b.`category` IN (";
        $placeholders = array();
        
        foreach ($categories as $category) {
            $query .= "'%s',";
            array_push($placeholders, $category);
        }
        $query = rtrim($query, ",");
        $query .= ")";

        // Get subscribers
        $subscribers = $wpdb->get_results($wpdb->prepare($query, $placeholders));

        // Mail each subscriber
        foreach ($subscribers as $subscriber) {
            $replaced_options = $this->replace_placeholders(
                array(
                    "template_subject" => $this->options['template_subject'], 
                    "template" => $this->options['template']), 
                array(
                    "key"=>$subscriber->key,
                    "content"=>$excerpt,
                    "title"=>$post->post_title,
                    "link"=>$post->guid));
            wp_mail($subscriber->email, $replaced_options['template_subject'], $replaced_options['template'], $this->headers, "");
        }
    }


    // Confirm a subscriber
    function confirm_subscriber($key) {
        global $wpdb;
        $headers = array(
            "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">",
            "Content-type: text/html"
        );

        // Confirm User
        $ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $query = $wpdb->prepare("SELECT `id`, `email` FROM `$this->table_name` WHERE `key` = %s AND `active` = 0 LIMIT 1", $key);
        $results = $wpdb->get_results($query);

        // User is already active
        if (count($results) == 0) {
            return;
        }

        // Update user row
        $query = $wpdb->prepare("UPDATE `$this->table_name` SET `active` = 1, `confirm_time` = CURRENT_TIMESTAMP, `confirm_ip` = %s WHERE `key` = %s LIMIT 1", $ip, $key);
        $update_result = $wpdb->query($query);

        // Send email to admin showing new subscription
        if ($this->options['admin_emails'] == "both" || $this->options['admin_emails'] == "subscribe") {
            wp_mail(get_bloginfo('admin_email'), get_bloginfo('name')." - Someone has subscribed to get updates!", $results[0]->email." has just subscribed!", $headers, "");
        }
    }


    // Remove a subscriber
    function remove_subscriber($key, $remove = false) {
        global $wpdb;
        $headers = array(
            "From: ".get_bloginfo('name')." <".get_bloginfo('admin_email').">",
            "Content-type: text/html"
        );

        $id_result = $wpdb->get_results($wpdb->prepare("SELECT `id` FROM `$this->table_name` WHERE `key` = %s LIMIT 1", $key));
        if (count($id_result) == 0) {
            return;
        }

        // Remove categories associated with subscriber
        $query = $wpdb->prepare("DELETE FROM `$this->categories_table_name` WHERE `subscriber_id` = %d", $id_result[0]->id);
        $results = $wpdb->query($query);

        // Remove user if in settings section, otherwise set as inactive
        if ($remove == true) {
            $query = $wpdb->prepare("DELETE FROM `$this->table_name` WHERE `key` = %s LIMIT 1", $key);
            $result = $wpdb->query($query);
            if ($result == 1) {
                return true;
            }
            return false;
        } else {
            // Check if user is already unsubscribed
            $check_query = $wpdb->prepare("SELECT `id`, `email` FROM `{$this->table_name}` WHERE `key` = %s AND `active` = 1 LIMIT 1", $key);
            $results = $wpdb->get_results($check_query);

            // User already unsubscribed
            if (count($results) == 0) {
                return;
            }
            $query = $wpdb->prepare("UPDATE `$this->table_name` SET `active` = 0 WHERE `key` = %s LIMIT 1", $key);
            $result = $wpdb->query($query);
        }        

        // Send email to admin showing unsubscription
        if ($this->options['admin_emails'] == "both" || $this->options['admin_emails'] == "unsubscribe") {
            wp_mail(get_bloginfo('admin_email'), get_bloginfo('name')." - Someone has unsubscribed from updates.", $results[0]->email." has just unsubscribed.", $headers, "");
        }
    }


    // Insert categories
    private function insert_categories($subscriber_id, $categories) {
        // Build category query
        global $wpdb;
        $categories = (!is_array($categories)) ? explode(",", $categories) : $categories;
        $wp_categories = get_categories(array("hide_empty"=>0));
        $placeholders = array();
        $run_once = false;
        $query = "INSERT INTO `$this->categories_table_name` (`subscriber_id`, `category`) VALUES";

        foreach ($categories as $category) {
            $category = trim($category);
            if ($category == "" || $category == " ") {
                continue;
            } else if ($this->category_is_real($wp_categories, $category)) {
                if ($run_once) {
                    $query .= ",";
                }
                $query .= "(%d, %s)";
                array_push($placeholders, $subscriber_id);
                array_push($placeholders, $category);
                $run_once = true;
            }
        }

        // If all categories were bogus, just return
        if (count($placeholders) == 0) {
            return false;
        }

        // Insert categories
        $query = $wpdb->prepare($query, $placeholders);
        $results = $wpdb->query($query);

        if ($results == false || $results == 0) {
            return false;
        }
        return true;
    }


    // Send a test email
    function test_email($email, $type) {
        $mail_success = false;
        $headers = array(
            "From: {$_POST['osd_subscribe_options']['from']}",
            "Content-type: text/html"
        );
        if ($type == "subscription") {
            $replaced_options = $this->replace_placeholders(
                array(
                    "template_subject" => stripslashes($_POST['osd_subscribe_options']['template_subject']), 
                    "template" => stripslashes($_POST['osd_subscribe_template'])), 
                array(
                    "key" => "",
                    "content" => "This is a test subscription email. This is fake content to verify that your template is set up correctly.\n<br>Links will not work correctly in this email.",
                    "title" => "Test Post Title",
                    "link" => get_bloginfo('url')));
            $mail_success = wp_mail($email, $replaced_options['template_subject'], $replaced_options['template'], $headers, "");
        } else if ($type == "confirm") {
            $replaced_options = $this->replace_placeholders(
                array(
                    "confirm_template_subject" => stripslashes($_POST['osd_subscribe_options']['confirm_template_subject']),
                    "confirm_template" => stripslashes($_POST['osd_subscribe_confirm_template'])), 
                array("key" => ""));
            $mail_success = wp_mail($email, $replaced_options['confirm_template_subject'], $replaced_options['confirm_template'], $headers, "");
        }

        if ($mail_success == true) {
            echo "GOOD";
        }
    }


    // Export Subscribers
    function export_subscribers() {
        // Set CSV headers
        header("Content-type: text/csv; charset=utf-8");
        header('Content-Disposition: attachment; filename=subscribers.csv');

        // Open standard output for csv
        $subscribers = $this->get_subscribers(false, false, 0, true);

        $csv = fopen("php://output", "w");
        fputcsv($csv, array("Email", "Category"));
        foreach ($subscribers as $subscriber) {
            $fields = array($subscriber->email);
            foreach ($subscriber->categories as $category) {
                $fields[] = $category;
            }
            fputcsv($csv, $fields);
        }
    }


    // Export Subscribers
    function import_subscribers($filename) {
        // Get and clean redirect url
        $redirect_url = rawurldecode($_POST['origin_url']);
        $redirect_url = preg_replace("/&error=[0-1]&osd_subscribe_message=[^&]*/", "", $redirect_url);

        // Get uploaded CSV
        $csv = fopen($filename, "r");

        // Get first line of headers
        fgetcsv($csv);

        // Read CSV
        while (($subscriber = fgetcsv($csv)) !== false) {
            $email = $subscriber[0];
            $categories = implode(",", array_slice($subscriber, 1));
            $result = json_decode($this->add_subscriber($email, $categories, true));
          
            if ($result->success == false) {
                break;
            }
        }
        $error = ($result->success == false) ? "1" : "0";
        $redirect_url .= "&error={$error}&osd_subscribe_message=".base64_encode($result->message);
        header("Location: {$redirect_url}");
    }


    // Return JSON AJAX message (boolean, string)
    function return_message($error, $code, $message, $return_error = false) {
        $return = (object) array(
            "success"=>!$error, 
            "return_code"=>constant("OSD_Subscribe::".$code), 
            "message"=>$message);
        if ($return_error == true) {
            return json_encode($return);
        } else {
            echo json_encode($return);
        }
    }


    // Loads the OSD Subscribe page
    function load_response_page($request) {
        global $wpdb;
        if (isset($_GET['confirm']) && $_GET['confirm'] != '') {
            // Confirm the user
            $this->confirm_subscriber($_GET['confirm']);
            $request = array('page_id' => $this->options['confirm_page']);
        } else if (isset($_GET['unsubscribe']) && $_GET['unsubscribe'] != '') {
            // Remove the user
            $this->remove_subscriber($_GET['unsubscribe']);
            $request = array('page_id' => $this->options['unsubscribe_page']);
        }
        return $request;
    }


    // Insert JS for the widget and shortcode
    function insert_frontend_js() {
        ?>
        <script>
            (function() {
                // Base64 decoder
                if (window.atob !== undefined) {
                    var Base64 = {decode: function(data) { return window.atob(data); }};
                } else {
                    var Base64 = {_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}};
                }

                var osd_subscribes = document.querySelectorAll('.osd-subscribe');
                for (var i=0, l=osd_subscribes.length; i < l; i++) {
                    osd_subscribes[i].addEventListener('submit', subscribe);
                }


                // Send AJAX request
                function subscribe(ev) {
                    ev.preventDefault();
                    var email = this.querySelector('input[name=email]');
                    var categories = this.querySelector('.osd-subscribe-categories').value;
                    var data = "action=osd_subscribe_add_subscriber&widget=osd_subscribe&wp_nonce=<?php echo wp_create_nonce('osd_subscribe_add_subscriber'); ?>&email="+email.value+"&categories="+categories;
                    var message = this.querySelector('.osd-subscribe-message');
                    var invalid_email_message = "<?php echo base64_encode($this->options['error_invalid_email']); ?>";

                    if (email.value.replace(/\s/gi, "") === "") {
                        message.innerHTML = Base64.decode(invalid_email_message);
                        return;
                    } else if (HTMLInputElement.prototype.checkValidity !== undefined) {
                        if (!email.checkValidity()) {
                            message.innerHTML = Base64.decode(invalid_email_message);
                            return;
                        }
                    }

                    // Send request
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php");
                    xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (this.readyState === 4 && this.status === 200) {
                            var response = null;
                            try {
                                response = JSON.parse(this.responseText);
                                if (response.return_code === 0) {
                                    email.value = "";
                                }
                                message.innerHTML = response.message;
                            } catch (error) {
                                message.innerHTML = Base64.decode("<?php echo base64_encode($this->options['error_general']); ?>");
                            }
                        }
                    }
                    xhr.send(data);
                    message.innerHTML = Base64.decode("<?php echo base64_encode($this->options['loading_message']); ?>");
                }
            })();
        </script>
        <?php
    }


    // OSD Subscribe shortcode
    function shortcode($attrs, $content = "") {
        return OSD_Subscribe::get_form_html($attrs);
    }


    // Settings link
    function settings_link($links) {
        array_push($links, "<a href='admin.php?page=osd-subscribe-options'>Settings</a>");
        array_push($links, "<a href='admin.php?page=osd-subscribe-options/how-to'>How-To</a>");
        return $links;
    }


    // Check if a category exists
    private function category_is_real($categories, $slug) {
        foreach ($categories as $category) {
            if ($category->slug == $slug) {
                return true;
            }
        }
        return false;
    }
}