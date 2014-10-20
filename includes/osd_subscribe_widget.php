<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

// OSD Subscribe widget
class OSD_Subscribe_Widget extends WP_Widget {
    // Widget Constructor
    public function __construct() {
        parent::__construct(
            'osd_subscribe',
            'OSD Subscribe',
            array('description' => __('Add a customizable email subscription form.'))
        );
    }

    // Outputs the content of the widget
    public function widget($args, $instance) {
        echo OSD_Subscribe::get_form_html($instance);
    }


    // Outputs the options form on admin
    public function form($instance) {
        ?>
        <style>
            .osd-subscribe-indent { padding-left: 1em; -webkit-user-select: none; -moz-user-select: none; user-select: none; }
            .desc { font-style: italic; }
        </style>
        <p>
            <label>Widget Title:</label><br>
            <input class='widefat' placeholder="" name='<?php echo $this->get_field_name('title'); ?>' type='text' value="<?php echo $instance['title']; ?>"/>
        </p>
        <p>
            <label>Class:</label><br>
            <input class='widefat' placeholder="" name='<?php echo $this->get_field_name('class'); ?>' type='text' value="<?php echo $instance['class']; ?>"/>
        </p>
        <p>
            <label>Placeholder:</label><br>
            <input class='widefat' placeholder="" name='<?php echo $this->get_field_name('placeholder'); ?>' type='text' value="<?php echo $instance['placeholder']; ?>"/>
        </p>
        <p>
            <label>Button Text:</label><br>
            <input class='widefat' placeholder="" name='<?php echo $this->get_field_name('button_text'); ?>' type='text' value="<?php echo $instance['button_text']; ?>"/>
        </p>
        <p>
            <label>Pre-Content:</label><br>
            <input class='widefat' placeholder="" name='<?php echo $this->get_field_name('pre_content'); ?>' type='text' value="<?php echo $instance['pre_content']; ?>"/>
        </p>
        <p>
            <label>Post-Content:</label><br>
            <input class='widefat' placeholder="" name='<?php echo $this->get_field_name('post_content'); ?>' type='text' value="<?php echo $instance['post_content']; ?>"/>
        </p>
        <p>
            <label>Categories:</label><br>
            <span class="desc">You must select at least one</span><br>
            <ul class='osd-subscribe-indent'>
                <?php
                foreach (get_categories(array("hide_empty"=>0)) as $category) {
                    echo "
                        <li>
                            <label>
                                <input type='checkbox' name='".$this->get_field_name('categories')."[]' value='{$category->slug}' ".$this->get_checked($category->slug, $instance['categories'])." />
                                {$category->name}
                            </label>
                        </li>";
                }
                ?>
            </ul>
        </p>
        <?php
    }


    // Processing widget options on save
    public function update($new_instance, $old_instance) {
        foreach ($new_instance as $key => $value) {
            if ($key == "class") {
                $new_instance[$key] = preg_replace("/'|\"/", "", $new_instance[$key]);
                $new_instance[$key] = strip_tags($new_instance[$key]);
            }
        }
        return $new_instance;
    }


    // Gets selected value
    private function get_checked($value, $array) {
        if (!is_array($array)) {
            return;
        } else if (in_array($value, $array)) {
            return "checked='checked'";
        }
    }
}


// Register the widget
function register_osd_subscribe_widget() {
    register_widget('OSD_Subscribe_Widget');    
}
add_action('widgets_init', 'register_osd_subscribe_widget');