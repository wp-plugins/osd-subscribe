<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// Instantiate class
new OSD_Subscribe_Subscriber_Settings();

// Class for subscriber settings
class OSD_Subscribe_Subscriber_Settings {
    private $url;
    private $page;
    private $pages;
    private $users_num;
    private $active_num;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_footer', array($this, 'insert_js'));

        // Instantiate variables
        global $osd_subscribe_instance;
        if ($osd_subscribe_instance == null) {
            return;
        }
        
        $this->base_url = get_bloginfo('url')."/wp-admin/admin.php?page=osd-subscribe-options/subscribers";
        $this->url = get_bloginfo('url')."/wp-admin/admin.php?page=osd-subscribe-options/subscribers&page_num=";
        $this->page = (isset($_GET['page_num'])) ? $_GET['page_num'] : "1";
        $this->users = $osd_subscribe_instance->get_subscribers(false, true, $this->page - 1);
        $this->users_num = $osd_subscribe_instance->get_subscribers_count();
        $this->active_num = $osd_subscribe_instance->get_subscribers_count(true);
        $this->pages = ceil($this->users_num / $osd_subscribe_instance->subscribers_return_limit);

        // Redirect if page count is too high or too low
        if ($this->pages == 0 && isset($_GET['page_num'])) {
            header("Location: {$this->base_url}");
            exit;            
        } else if ($this->page > $this->pages && $this->pages > 0) {
            header("Location: {$this->url}{$this->pages}");
            exit;
        } else if ($this->page < 1) {
            header("Location: {$this->url}1");
            exit;
        }
    }

    // Add options page to WP
    public function add_menu_item() {
        add_submenu_page(
            'osd-subscribe-options', 
            'OSD Subscribe email subscribers', 
            'Subscribers',
            'manage_options',
            'osd-subscribe-options/subscribers', 
            array($this, 'create_page')
        ); 
    }

    // Creates the page
    public function create_page() {
        ?>
        <style>
            table { table-layout: auto !important; }
            table tr:nth-child(odd) { background-color: #f9f9f9; }
            .osd-subscribe-remove, .go-button { border: none; text-align: center; cursor: pointer; background: #555555; border-radius: 4px; display: inline-block; padding: 3px 6px; color: white; }
            .osd-subscribe-remove:hover, .go-button:hover { background: #777777; }
            .osd-subscribe-message { font-size: 1.25em; }
            .paginate { font-size: 1em; margin: 1em 0; }
            .paginate > a { display: inline-block; padding: 4px 10px; background: #555555; color: white; border-radius: 3px; margin: 0 4px; text-decoration: none; }
            .paginate > a:first-child { margin-left: 0; }
            .paginate > a.current, .paginate > a:hover { background: #AAAAAA; }
            .goto-page-cont { /*margin: 10px 0px;*/ display: inline-block; margin-left: 1em; }
            .goto-page-cont > .page-input { width: 100px; }
        </style>
        <div class='wrap'>
            <h2>Subscribers</h2>
            <h3>You have <?php echo $this->active_num; ?> active subscribers out of <?php echo $this->users_num; ?>.</h3>
            <div class='osd-subscribe-message'></div>
            <?php $this->get_pagination($this->page, $this->pages); ?>
            <table class='wp-list-table widefat fixed subscribers'>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Active</th>
                        <th>IP</th>
                        <th>Time</th>
                        <th>Confirm Time</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <?php foreach ($this->users as $user) { ?>
                    <tr>
                        <td><?php echo $user->email; ?></td>
                        <td><?php echo ($user->active) ? "Yes" : "No"; ?></td>
                        <td><?php echo $user->ip; ?></td>
                        <td><?php echo $user->time; ?></td>
                        <td><?php echo $user->confirm_time; ?></td>
                        <td><div class='osd-subscribe-remove' data-key="<?php echo $user->key; ?>">Remove</div></td>
                    </tr>
                <?php } ?>
            </table>
            <?php $this->get_pagination($this->page, $this->pages); ?>
        </div>
        <?php
    }


    // Get pagination
    function get_pagination($page, $pages) {
        ?>
        <div class='paginate'>
            <?php
            echo "<a class='page-link' href='{$this->url}".($page - 1)."'>&laquo;</a>";
            echo "&nbsp;Page {$page} of {$pages}&nbsp;";
            echo "<a class='page-link' href='{$this->url}".($page + 1)."'>&raquo;</a>";

            // Previous button
            // if ($page > 1) { echo "<a class='page-link' href='{$this->url}".($page - 1)."'>&laquo;</a>"; }

            // If less than 20 pages, just print them all
            // if ($pages < 20) {
            //     for ($i=1; $i <= $pages; $i++) {
            //         $class = ($i == $page) ? " current" : "";
            //         echo "<a class='page-link{$class}' href='{$this->url}{$i}'>{$i}</a>";
            //     }
            // } else {
            //     // Always show first page
            //     if ($page > 1) { echo "<a class='page-link' href='{$this->url}1'>1</a>"; }

            //     $increment = floor($pages / 10);
            //     for ($i = 2; $i < $page - 1; $i += $increment) {
            //         echo "<a class='page-link' href='{$this->url}{$i}'>{$i}</a>";
            //     }
            //     echo "<a class='page-link current' href='{$this->url}{$page}'>{$page}</a>";
            //     for ($i = $page + 1; $i < $pages; $i += $increment) {
            //         echo "<a class='page-link' href='{$this->url}{$i}'>{$i}</a>";
            //     }

            //     // Always show last page
            //     if ($page < $pages) { echo "<a class='page-link' href='{$this->url}{$pages}'>{$pages}</a>"; }
            // }

            // Next button
            // if ($page < $pages) { echo "<a class='page-link' href='{$this->url}".($page + 1)."'>&raquo;</a>"; }
            ?>
            <form class='goto-page-cont'>
                <input class='page-input' placeholder='Go to page' name='page_num' />
                <input class='go-button' type='submit' value='Go &raquo;' />
            </form>
        </div>
        <?php
    }


    // Insert JavaScript
    function insert_js() {
        ?>
        <script>
            (function() {
                var removes = document.querySelectorAll('.osd-subscribe-remove');
                var message = document.querySelector('.osd-subscribe-message');
                var goto_page_forms = document.querySelectorAll('.goto-page-cont');
                for (var i=0, l=goto_page_forms.length; i < l; i++) {
                    goto_page_forms[i].addEventListener('submit', gotoPage);
                }
                for (var i=0, l=removes.length; i < l; i++) {
                    removes[i].addEventListener('click', remove_subscriber);   
                }

                // Makes an AJAX call to remove a subscriber
                function remove_subscriber() {
                    if (!window.confirm("Are you sure?")) {
                        return;
                    }
                    var row = jQuery(this).parents('tr');
                    var data = "action=osd_subscribe_remove_subscriber&widget=osd_subscribe&wp_nonce=<?php echo wp_create_nonce('osd_subscribe_remove_subscriber'); ?>&key="+this.getAttribute('data-key');
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "<?php echo WP_SITEURL; ?>/wp-admin/admin-ajax.php");
                    xhr.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
                    xhr.onreadystatechange = function() {
                        if (this.readyState === 4 && this.status === 200) {
                            if (this.responseText == "GOOD") {
                                row[0].parentElement.removeChild(row[0]);
                                message.innerHTML = "User successfully removed.";
                            } else {
                                message.innerHTML = "Sorry, there was an error.";
                            }
                        }
                    }
                    xhr.send(data);
                    message.innerHTML = "Removing user..."
                }


                // Navigates to a specific page
                function gotoPage(ev) {
                    ev.preventDefault();
                    var page = this.querySelector('.page-input').value;
                    window.location = "<?php echo $this->url; ?>" + page;
                }
            })();
        </script>
        <?php
    }
}