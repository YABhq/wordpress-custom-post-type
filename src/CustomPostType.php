<?php

namespace Yab;

/**
 * Class CustomPostType
 *
 * @package CustomPostType
 * @subpackage WordPress
 */

class CustomPostType
{
    /**
     * Hold the custom fields from meta boxes
     * @var array
     */
    public $custom_fields = [];

    /**
     * The name of the custom post type
     * @var string
     */
    protected $post_type_name;

    /**
     * The arguments to be past to the custom post type
     * @var array
     */
    protected $args;

    /**
     * The labels of names to be used on the custom post type
     * @var array
     */
    protected $labels;

    /**
     * The textdomain to be used for translation
     * @var string
     */
    protected $textdomain;

    /**
     * The plural value of the name of the custom post type
     * @var string
     */
    protected $pluralname;

    /**
     * Create an instance of the CustomPostType class
     *
     * @param string $name   Name of the custom post type
     * @param string $pluralname The plural value of the given name
     * @param array  $args   Options to override the defaults
     * @param array  $labels Labels for the post type if you want to override defaults
     * @param string $textdomain Short name for the text domain
     */
    public function __construct($name, $pluralname = null, $args = [], $labels = [], $textdomain = null)
    {
        $this->post_type_name = strtolower(str_replace(' ', '_', $name));
        $this->pluralname = $pluralname;
        $this->args = $args;
        $this->labels = $labels;
        $this->textdomain = $textdomain;

        if (!post_type_exists($this->post_type_name)) {
            add_action('init', [$this, 'register_post_type']);
        }

        add_action('save_post', [$this, 'save'], 10, 3);
    }

    /**
     * Adds default options to the custom post type then registers the post type
     * @return void
     */
    public function register_post_type()
    {
        $post_type_name = ucwords(str_replace('_', ' ', $this->post_type_name));

        // Check if we have a plural name set
        if ($this->pluralname === null) {
            $post_type_name_plural = $post_type_name . 's';
        } else {
            $post_type_name_plural = $this->pluralname;
        }

        $labels = array_merge(
            [
                'name' => _x($post_type_name_plural, 'post type general name'),
                'singular_name' => _x($post_type_name, 'post type singular name'),
                'add_new' => _x('Add New', strtolower($post_type_name)),
                'add_new_item' => __('Add New ' . $post_type_name),
                'edit_item' => __('Edit ' . $post_type_name),
                'new_item' => __('New ' . $post_type_name),
                'all_items' => __('All ' . $post_type_name_plural),
                'view_item' => __('View ' . $post_type_name),
                'search_items' => __('Search ' . $post_type_name_plural),
                'not_found' => __('No ' . strtolower($post_type_name_plural) . ' found'),
                'not_found_in_trash' => __('No ' . strtolower($post_type_name_plural) . ' found in Trash'),
                'parent_item_colon' => '',
                'menu_name' => $post_type_name_plural
            ],
            $this->labels
        );

        $args = array_merge(
            [
                'label' => $post_type_name,
                'labels' => $labels,
                'public' => true,
                'show_ui' => true,
                'supports' => ['title', 'editor'],
                'show_in_nav_menus' => true,
                '_builtin' => false,
            ],
            $this->args
        );

        // Register the post type
        register_post_type($this->post_type_name, $args);
    }

    /**
     * Add a taxonomy to the given post type
     *
     * @param string $name   Name of the custom taxonomy
     * @param array  $args   Options to override the defaults
     * @param array  $labels Labels to override the default names
     */
    public function add_taxonomy($name, $plural = null, $args = [], $labels = [])
    {
        if (!empty($name)) {
            $post_type_name = $this->post_type_name;

            // Taxonomy properties
            $taxonomy_name = strtolower(str_replace(' ', '_', $name));
            $taxonomy_labels = $labels;
            $taxonomy_args = $args;
        }

        if (!taxonomy_exists($taxonomy_name)) {
            $name = ucwords(str_replace('_', ' ', $name));

            // Check if we have a plural name set
            if ($plural === null) {
                $plural = $name . 's';
            }

            $labels = array_merge(
                [
                    'name' => _x($plural, 'taxonomy general name'),
                    'singular_name' => _x($name, 'taxonomy singular name'),
                    'search_items' => __('Search ' . $plural),
                    'all_items' => __('All ' . $plural),
                    'parent_item' => __('Parent ' . $name),
                    'parent_item_colon' => __('Parent ' . $name . ':'),
                    'edit_item' => __('Edit ' . $name),
                    'update_item' => __('Update ' . $name),
                    'add_new_item' => __('Add New ' . $name),
                    'new_item_name' => __('New ' . $name . ' Name'),
                    'menu_name' => __($plural),
                ],
                $taxonomy_labels
            );

            $args = array_merge(
                [
                    'label' => $plural,
                    'labels' => $labels,
                    'public' => true,
                    'show_ui' => true,
                    'show_in_nav_menus' => true,
                    '_builtin' => false,
                ],

                $taxonomy_args
            );

            register_taxonomy($taxonomy_name, $post_type_name, $args);
        } else {
            // Taxonomy is already registered
            // just attach it to the post type
            register_taxonomy_for_object_type($taxonomy_name, $post_type_name);
        }
    }

    /**
     * Register the existing taxonomy to the post type
     * 
     * @param string $taxonomy_name
     */
    public function add_existing_taxonomy($taxonomy_name)
    {
        add_action( 'init', function() use ($taxonomy_name) {
            register_taxonomy_for_object_type($taxonomy_name, $this->post_type_name);
        });
    }

    /**
     * API wrapper for add_meta_box so we can
     * call the function multiple times
     *
     * @param string $title
     * @param array  $fields
     * @param string $context
     * @param string $priority
     */
    public function add_meta_box($title, $fields = [], $context = 'normal', $priority = 'default')
    {
        if (!empty($title)) {
            $meta_box_id = strtolower(str_replace(' ', '_', $title));
            $this->custom_fields[$title] = $fields;

            // add the meta box
            add_action('admin_init', function () use ($meta_box_id, $title, $context, $priority, $fields) {
                add_meta_box(
                    $meta_box_id,
                    __($title, $this->textdomain),
                    [$this, 'display_meta_box'],
                    $this->post_type_name,
                    $context,
                    $priority,
                    $this->custom_fields[$title]
                );
            });
        }
    }

    /**
     * Display the meta box data
     *
     * @param  WP_Post $post
     * @param  array $data
     */
    public function display_meta_box($post, $data)
    {
        // Add an nonce field so we can check for it later.
        wp_nonce_field($data['id'] . '_custom_box', $data['id'] . '_inner_custom_box_nonce');

        /* Loop through $custom_fields */
        foreach ($data['args'] as $field) {
            $field_name = strtolower(str_replace(' ', '_', $data['id'])) . '_' . strtolower(str_replace(' ', '_', $field['name']));
            $field_type = empty($field['type']) ? 'text' : strtolower($field['type']);
            $field_size = empty($field['size']) ? '25' : $field['size'];
            $field_class = empty($field['class']) ? 'standard-field' : $field['class'];
            $field_value = get_post_meta($post->ID, $field_name, true);

            echo '<p class="' . $field_class . '">';
            echo '<label for="' . $field_name . '" style="font-weight: bold; display: block;">' . $field['name'] . '</label>';
            if ($field_type === 'select') {
                echo '<select name="' . $field_name . '" id="' . $field_name . '">';
                foreach ($field['values'] as $value) {
                    if ($value === $field_value) {
                        echo '<option selected>' . $value . '</option>';
                    } else {
                        echo '<option>' . $value . '</option>';
                    }
                }
                echo '</select>';
            } 
            elseif ($field_type === 'textarea')
             {
                 echo '<textarea rows="3" name="' . $field_name . '" id="' . $field_name . '" class="large-text code">' . esc_attr($field_value) . '</textarea>';
             }
            else {
                echo '<input type="' . $field_type . '" name="' . $field_name . '" id="' . $field_name . '" size="' . $field_size . '" value="' . esc_attr($field_value) . '" data-lpignore="true">';
            }
            echo '</p>';
        }
    }

    /**
     * Save the meta data from the custom meta box
     *
     * @param  integer $post_id
     * @param  WP_Post $post
     * @param  [type] $update
     * @return void
     */
    public function save($post_id, $post, $update)
    {
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions.
        if (isset($_POST['post_type']) && $this->post_type_name == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            return;
        }

        foreach ($this->custom_fields as $title => $fields) {
            // Loop through all fields
            foreach ($fields as $label => $type) {
                $field_id_name = strtolower(str_replace(' ', '_', $title)) . '_' . strtolower(str_replace(' ', '_', $type['name']));
                update_post_meta($post->ID, $field_id_name, $_POST[$field_id_name]);
            }
        }
    }
}
