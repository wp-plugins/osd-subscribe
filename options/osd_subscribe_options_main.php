<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Instantiate class
new OSD_Subscribe_Main_Settings();

// Main Settings class
class OSD_Subscribe_Main_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_init', array($this, 'register_options'));
        add_action('admin_footer', array($this, 'insert_js'));
    }

    // Add options page to WP
    public function add_menu_item() {
        add_menu_page(
            'OSD Subscribe Settings', 
            'OSD Subscribe', 
            'manage_options',
            'osd-subscribe-options', 
            array($this, 'create_page'), 
            plugins_url('osd-subscribe/images/icon.png')
        ); 
    }

    // Create options page
    public function create_page() {
        global $osd_subscribe_instance;
        ?>
        <style>
            ul { padding-left: 20px; }
            #pre-div, #post-div { float: left; width: 50%; }
            .cont:after { content: ""; display: block; clear: both; }
            h3 { margin-top: 40px; margin-bottom: .25em; }
            label { display: inline-block; cursor: auto; font-weight: 700; font-size: 1.1em; margin-bottom: .2em; }
            label.radio-label {font-weight: normal; font-size: 1em; cursor: pointer; margin-right: 1em; }
            li:nth-child(n+2) { padding-top: 20px; }
            li.space { padding-top: 40px; }
            li:after { content: ""; display: block; clear: both; }
            .desc { margin-bottom: .5em; font-style: italic; }
            textarea { height: 250px; margin-top: .75em !important; font-size: 13px; }
            textarea.small { height: 27px; resize: none; font-size: 14px; overflow: hidden; margin-top: .5em !important; }
            .col { float: left; width: 48%; }
            .col:nth-child(1) { margin-right: 2%; }
            .col:nth-child(2) { margin-left: 2%; }
            .js-test-email { margin-left: 5px; text-align: center; cursor: pointer; background: #555555; border-radius: 4px; display: inline-block; padding: 3px 10px; color: white; }
            .js-test-email:hover { background: #777777; }
            .test-email-address { width: 250px; }
        </style>
        <div class="wrap">
            <h2>OSD Subscribe Settings</h2>   
            <?php
            // Display any messages
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') { ?>
                <div class="updated settings-error" id="setting-error-settings_updated"> 
                    <p><strong>Settings saved.</strong></p>
                </div>
            <?php } ?>
            <form class='osd-subscribe-options-form' method="post" action="options.php">
                <?php
                    settings_fields("osd_subscribe_option_group");
                    do_settings_sections("osd_subscribe_option_group");
                    $options = $osd_subscribe_instance->get_options();
                ?>
                <h3>General Settings</h3>
                <ul>
                    <li>
                        <label>Email From:</label><br>
                        <div class='desc'>
                            The from address of the email.&nbsp;&nbsp;(i.e.&nbsp; Name &lt;name@example.com&gt;)<br>
                            This field cannot contain HTML
                        </div>
                        <input class='widefat' type='text' name='osd_subscribe_options[from]' value="<?php echo $options['from']; ?>">
                    </li>
                    <li>
                        <div class="col">
                            <label>Send emails to the admin email address when users:</label><br>
                            <label class='radio-label'>
                                <input type='radio' name='osd_subscribe_options[admin_emails]' value='subscribe' <?php if ($options['admin_emails'] == "subscribe") { echo "checked='checked'"; } ?>/>
                                Subscribe
                            </label>
                            <label class='radio-label'>
                                <input type='radio' name='osd_subscribe_options[admin_emails]' value='unsubscribe' <?php if ($options['admin_emails'] == "unsubscribe") { echo "checked='checked'"; } ?>/>
                                Unsubscribe
                            </label>
                            <label class='radio-label'>
                                <input type='radio' name='osd_subscribe_options[admin_emails]' value='both' <?php if ($options['admin_emails'] == "both") { echo "checked='checked'"; } ?>/>
                                Both
                            </label>
                            <label class='radio-label'>
                                <input type='radio' name='osd_subscribe_options[admin_emails]' value='neither' <?php if ($options['admin_emails'] == "neither") { echo "checked='checked'"; } ?>/>
                                Neither
                            </label>                            
                        </div>
                        <div class="col">
                            <label>Allow Pages to have categories:</label><br>
                            <label class="radio-label">
                                <input type='radio' name='osd_subscribe_options[add_categories_to_pages]' value='true' <?php if ($options['add_categories_to_pages'] == 'true') { echo "checked='checked'"; } ?> />Yes
                            </label>
                            <label class="radio-label">
                                <input type='radio' name='osd_subscribe_options[add_categories_to_pages]' value='false' <?php if ($options['add_categories_to_pages'] == 'false') { echo "checked='checked'"; } ?> />No
                            </label>
                        </div>
                    </li>
                    <li>
                        <div class="col">
                            <label>Confirm Page:</label><br>
                            <div class='desc'>The page a user will see when confirming their subscription.</div>
                            <?php wp_dropdown_pages(array('name'=>'osd_subscribe_options[confirm_page]', 'selected'=>$options['confirm_page'], 'show_option_none'=>'None')); ?>
                        </div>
                        <div class="col">
                            <label>Unsubscribe Page:</label><br>
                            <div class='desc'>The page a user will see when unsubscribing.</div>
                            <?php wp_dropdown_pages(array('name'=>'osd_subscribe_options[unsubscribe_page]', 'selected'=>$options['unsubscribe_page'], 'show_option_none'=>'None')); ?>
                        </div>
                    </li>
                </ul>
                
                <h3>Front-end Javascript messages</h3>
                <div class="desc">Messages displayed when a user is subscribing. These fields may contain HTML.</div>
                <ul>
                    <li>
                        <div class='col'>
                            <label>Confirmation Email Sent Message:</label>
                            <textarea rows='1' name="osd_subscribe_options[confirm_message]" class='widefat small'><?php echo $options['confirm_message']; ?></textarea>
                        </div>
                        <div class='col'>
                            <label>General Error Message:</label>
                            <textarea name="osd_subscribe_options[error_general]" class='widefat small'><?php echo $options['error_general']; ?></textarea>
                        </div>
                    </li>
                    <li>
                        <div class="col">
                            <label>Invalid Email Error Message:</label>
                            <textarea name="osd_subscribe_options[error_invalid_email]" class='widefat small'><?php echo $options['error_invalid_email']; ?></textarea>
                        </div>
                        <div class="col">
                            <label>Already Subscribed Error Message:</label>
                            <textarea name="osd_subscribe_options[error_already_subscribed]" class='widefat small'><?php echo $options['error_already_subscribed']; ?></textarea>
                        </div>
                    </li>
                    <li>
                        <div class="col">
                            <label>Loading Message:</label>
                            <textarea name="osd_subscribe_options[loading_message]" class='widefat small'><?php echo $options['loading_message']; ?></textarea>
                        </div>
                        <div class="col">
                            <label>No Categories Message:</label>
                            <textarea name="osd_subscribe_options[error_no_categories]" class='widefat small'><?php echo $options['error_no_categories']; ?></textarea>
                        </div>
                    </li>
                </ul>

                <h3 class='space'>Email Templates</h3>
                <div class="desc">Email templates. The subject cannot contain HTML but the email bodies may contain HTML.</div>
                <ul>
                    <li>
                        <div class='col'>
                            <label>Send Test Subscription Email</label><br>
                            <input type='email' class='test-email-address' placeholder='Email' />
                            <span class='js-test-email' data-type='subscription'>Send</span>
                            <div class='test-email-message'></div>
                        </div>
                        <div class="col">
                            <label>Send Test Confirmation Email</label><br>
                            <input type='email' class='test-email-address' placeholder='Email' />
                            <span class='js-test-email' data-type='confirm'>Send</span>
                            <div class='test-email-message'></div>
                        </div>
                    </li>
                    <li>
                        <label>Subscription Email Template:</label>
                        <div class='desc'>
                            The template for email sent out to subscribers.<br>
                            According to the <a target="_blank" href='http://www.business.ftc.gov/documents/bus61-can-spam-act-compliance-guide-business'>CAN-SPAM Act</a> Subscription emails MUST contain an unsubscribe link and the address to your physical location.<br>
                            Available Placeholders: {SITENAME}, {UNSUBSCRIBEURL}, {TITLE}, {CONTENT}, {LINK}
                        </div>
                        <input class='widefat' type='text' placeholder='Subject' name='osd_subscribe_options[template_subject]' value="<?php echo $options['template_subject']; ?>" />
                        <textarea class='widefat' name='osd_subscribe_template'><?php echo $options['template']; ?></textarea>
                    </li>
                    <li>
                        <label>Confirmation Template:</label>
                        <div class='desc'>
                            The template for the email sent to users to confirm their subscription request.<br>
                            This template must use the {CONFIRMLINK} placeholders.<br>
                            Available Placeholders: {SITENAME}, {CONFIRMURL}
                        </div>
                        <input class='widefat' type='text' placeholder='Subject' name='osd_subscribe_options[confirm_template_subject]' value="<?php echo $options['confirm_template_subject']; ?>" />
                        <textarea class='widefat' name='osd_subscribe_confirm_template'><?php echo $options['confirm_template']; ?></textarea>
                    </li>
                </ul>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }


    // Insert JS
    function insert_js() {
        ?>
        <script>
            (function() {
                var buttons = document.querySelectorAll('.js-test-email');
                for (var i=0, l=buttons.length; i < l; i++) {
                    buttons[i].addEventListener('click', test_email);                    
                }

                // Send AJAX call to test email
                function test_email() {
                    var email = this.parentElement.querySelector('.test-email-address');
                    var message = this.parentElement.querySelector('.test-email-message');
                    var data = jQuery('.osd-subscribe-options-form').serialize();
                    data += "&action=osd_subscribe_test_email&widget=osd_subscribe&wp_nonce=<?php echo wp_create_nonce('osd_subscribe_test_email'); ?>&email="+email.value+"&type="+this.getAttribute("data-type");

                    if (HTMLInputElement.prototype.checkValidity !== undefined) {
                        email.setAttribute('required', 'required');
                        if (!email.checkValidity()) {
                            email.removeAttribute('required', 'required');
                            message.innerHTML = "The email address entered is not valid.";
                            return;
                        }
                    }

                    // Send request
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php");
                    xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (this.readyState === 4 && this.status === 200) {
                            if (this.responseText === "GOOD") {
                                message.innerHTML = "Email sent.";
                            } else {
                                message.innerHTML = "Sorry, there was an error";
                            }
                        }
                    }
                    xhr.send(data);
                    message.innerHTML = "Sending...";
                }
            })();
        </script>
        <?php
    }


    // Sanitize the input
    function sanitize($options) {
        foreach ($options as $key => $value) {
            if ($key == "from" || $key == "error_general" || $key == "error_invalid_email" || $key == "error_already_subscribed" || $key == "confirm_message" || $key == "loading_message") {
                continue;
            }
            $options[$key] = strip_tags($value);
        }
        return $options;
    }


    // Register/Add Options 
    public function register_options() {
        register_setting("osd_subscribe_option_group", "osd_subscribe_options", array($this, "sanitize"));
        register_setting("osd_subscribe_option_group", "osd_subscribe_template");
        register_setting("osd_subscribe_option_group", "osd_subscribe_confirm_template");
    }
}