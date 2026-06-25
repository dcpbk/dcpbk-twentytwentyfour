<?php

/**
 * DCPBK Twenty Twenty-Four functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package DCPBK Twenty Twenty-Four
 * @since DCPBK Twenty Twenty-Four 1.0
 */


if (!function_exists('dcpbktwentytwentyfour_return_custom_field')) {

    /**
     * Register shortcode to return custom field value
     * Usage: [metalookup field="custom_field_name" default="No data found"]
     *
     * @since DCPBK Twenty Twenty-Four 1.0
     * @param array $atts Shortcode attributes
     * @return string Custom field value or default value
     */
    function dcpbktwentytwentyfour_return_custom_field($atts)
    {
        $atts = shortcode_atts(array(
            'field' => '',
            'default' => 'No data found',
        ), $atts, 'metalookup');

        // Get the custom field value
        if ($custom_field = get_post_meta(get_the_ID(), $atts['field'], true)) {
            return $custom_field;
        } else {
            return $atts['default'];
        }
    }

    add_shortcode('metalookup', 'dcpbktwentytwentyfour_return_custom_field');
}

/**
 * Handle the blocks named "Forced Shortcode" (use this attribute inside query loops!)
 * Usage example:
 * <!-- wp:shortcode {"metadata":{"name":"Forced Shortcode"}} -->
 * <div>[metalookup field="position" default="Board Member"]</div>
 * <!-- /wp:shortcode -->
 * Source: https://gist.github.com/frzsombor/c53446050ee0bb5017e29b9afb039309
 */
add_filter('render_block', function ($block_content, $block, $instance) {
    if (isset($block['attrs']) && isset($block['attrs']['metadata']) && isset($block['attrs']['metadata']['name']) && $block['attrs']['metadata']['name'] === 'Forced Shortcode') {
        return do_shortcode($block_content);
    }
    return $block_content;
}, 10, 3);

// Disable Cloudflare Cache on nocache_headers (for MembershipWorks)
// (override if already set)
add_filter('nocache_headers', function ($headers) {
    return [
        'CDN-Cache-Control' =>  $headers['Cache-Control'] ?? 'no-cache, must-revalidate, max-age=0'
    ] + $headers;
});

// Set CDN-Cache-Control header same as Cache-Control if defined or default to 4h (+1Y stale-while-revalidate)
// (don't override if already set)
add_filter('wp_headers', function ($headers) {
    return $headers + [
        'CDN-Cache-Control' =>  $headers['Cache-Control'] ?? 'max-age=14400, stale-while-revalidate=31536000'
    ];
});

// Disable Quicklink on Membership pages (because these pages are not cached anyway)
add_filter('quicklink_options', function ($options) {
    $options['ignores'] = array_merge($options['ignores'], [
        '^' . preg_quote(site_url() . '/membership/', '/')
    ]);
    return $options;
});

/**
 * This function will connect wp_mail to your authenticated
 * SMTP server. This improves reliability of wp_mail, and 
 * avoids many potential problems.
 *
 * For instructions on the use of this script, see:
 * https://butlerblog.com/easy-smtp-email-wordpress-wp_mail/
 * 
 * Values for constants are set in wp-config.php
 */
add_action('phpmailer_init', 'send_smtp_email');
function send_smtp_email($phpmailer)
{
    $phpmailer->isSMTP();
    $phpmailer->Host       = SMTP_HOST;
    $phpmailer->SMTPAuth   = SMTP_AUTH;
    $phpmailer->Port       = SMTP_PORT;
    $phpmailer->Username   = SMTP_USER;
    $phpmailer->Password   = SMTP_PASS;
    $phpmailer->SMTPSecure = SMTP_SECURE;
    $phpmailer->From       = SMTP_FROM;
    $phpmailer->FromName   = SMTP_NAME;
}

/*
// WP Total Cache: Set Cache-Control and Expires headers for HTML files
// Set Cache-Control and Expires to 1 hour
add_filter('wpsc_htaccess_mod_headers', function ($headers) {
    $headers['Cache-Control'] = 'max-age=60, must-revalidate';
    $headers['CDN-Cache-Control'] = 'max-age=14400, stale-while-revalidate=31536000';
    return $headers;
}, 1);

add_filter('wpsc_htaccess_mod_expires', function ($expiry_rules) {
    $expiry_rules = array_filter($expiry_rules, function ($rule) {
        return strpos($rule, 'ExpiresByType') === false;
    });

    $expiry_rules[] = 'ExpiresByType text/html "access plus 1 minute"';
    return $expiry_rules;
}, 1);
*/

/****************************************
 * The below are snippets from WPCode
 ****************************************/
/**
 * Allow SVG uploads for administrator users.
 *
 * @param array $upload_mimes Allowed mime types.
 *
 * @return mixed
 */
add_filter(
    'upload_mimes',
    function ($upload_mimes) {
        // By default, only administrator users are allowed to add SVGs.
        // To enable more user types edit or comment the lines below but beware of
        // the security risks if you allow any user to upload SVG files.
        if (!current_user_can('administrator')) {
            return $upload_mimes;
        }

        $upload_mimes['svg']  = 'image/svg+xml';
        $upload_mimes['svgz'] = 'image/svg+xml';

        return $upload_mimes;
    }
);

/**
 * Add SVG files mime check.
 *
 * @param array        $wp_check_filetype_and_ext Values for the extension, mime type, and corrected filename.
 * @param string       $file Full path to the file.
 * @param string       $filename The name of the file (may differ from $file due to $file being in a tmp directory).
 * @param string[]     $mimes Array of mime types keyed by their file extension regex.
 * @param string|false $real_mime The actual mime type or false if the type cannot be determined.
 */
add_filter(
    'wp_check_filetype_and_ext',
    function ($wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime) {

        if (!$wp_check_filetype_and_ext['type']) {

            $check_filetype  = wp_check_filetype($filename, $mimes);
            $ext             = $check_filetype['ext'];
            $type            = $check_filetype['type'];
            $proper_filename = $filename;

            if ($type && 0 === strpos($type, 'image/') && 'svg' !== $ext) {
                $ext  = false;
                $type = false;
            }

            $wp_check_filetype_and_ext = compact('ext', 'type', 'proper_filename');
        }

        return $wp_check_filetype_and_ext;
    },
    10,
    5
);

/** 
 * Add custom post types for DCPBK.
 * - A Leadership post type with the board and volunteer leaders of DCPBK.
 * - A Jobs post type for job listings.
 */
add_action('init', 'dcpbk_custom_post_types');
function dcpbk_custom_post_types()
{
    $leadership_args = [
        'label'  => esc_html__('Leadership', 'dcpbk'),
        'labels' => [
            'menu_name'          => esc_html__('Leadership', 'dcpbk'),
            'name_admin_bar'     => esc_html__('Leader', 'dcpbk'),
            'add_new'            => esc_html__('Add Leader', 'dcpbk'),
            'add_new_item'       => esc_html__('Add new Leader', 'dcpbk'),
            'new_item'           => esc_html__('New Leader', 'dcpbk'),
            'edit_item'          => esc_html__('Edit Leader', 'dcpbk'),
            'view_item'          => esc_html__('View Leader', 'dcpbk'),
            'update_item'        => esc_html__('Update Leader', 'dcpbk'),
            'all_items'          => esc_html__('All Leaders', 'dcpbk'),
            'search_items'       => esc_html__('Search Leaders', 'dcpbk'),
            'parent_item_colon'  => esc_html__('Parent Leader', 'dcpbk'),
            'not_found'          => esc_html__('No Leaders found', 'dcpbk'),
            'not_found_in_trash' => esc_html__('No Leaders found in Trash', 'dcpbk'),
            'name'               => esc_html__('Leadership', 'dcpbk'),
            'singular_name'      => esc_html__('Leader', 'dcpbk'),
        ],
        'description'         => esc_html__('DCPBK Leadership', 'dcpbk'),
        'public'              => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'capability_type'     => 'page',
        'hierarchical'        => false,
        'has_archive'         => true,
        'query_var'           => false,
        'can_export'          => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-groups',
        'delete_with_user'    => false,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'revisions',
            'excerpt',
            'custom-fields',
            'page-attributes',
        ],
        'rewrite' => [
            'slug'       => 'about/leadership',
            'with_front' => true,
            'pages'      => true,
            'feeds'      => true,
        ],
    ];

    $volunteer_args = [
        'label'  => esc_html__('Position', 'dcpbk'),
        'labels' => [
            'menu_name'          => esc_html__('Positions', 'dcpbk'),
            'name_admin_bar'     => esc_html__('Position', 'dcpbk'),
            'add_new'            => esc_html__('Add Position', 'dcpbk'),
            'add_new_item'       => esc_html__('Add new Position', 'dcpbk'),
            'new_item'           => esc_html__('New Position', 'dcpbk'),
            'edit_item'          => esc_html__('Edit Position', 'dcpbk'),
            'view_item'          => esc_html__('View Position', 'dcpbk'),
            'update_item'        => esc_html__('Update Position', 'dcpbk'),
            'all_items'          => esc_html__('All Positions', 'dcpbk'),
            'search_items'       => esc_html__('Search Positions', 'dcpbk'),
            'parent_item_colon'  => esc_html__('Parent Position', 'dcpbk'),
            'not_found'          => esc_html__('No Positions found', 'dcpbk'),
            'not_found_in_trash' => esc_html__('No Positions found in Trash', 'dcpbk'),
            'name'               => esc_html__('Positions', 'dcpbk'),
            'singular_name'      => esc_html__('Position', 'dcpbk'),
        ],
        'description'         => esc_html__('DCPBK Job Postings', 'dcpbk'),
        'public'              => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'show_in_rest'        => true,
        'capability_type'     => 'page',
        'hierarchical'        => false,
        'has_archive'         => true,
        'query_var'           => false,
        'can_export'          => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-portfolio',
        'delete_with_user'    => false,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'revisions',
            'excerpt',
            'custom-fields',
            'page-attributes',
        ],
        'rewrite' => [
            'slug'       => 'volunteer',
            'with_front' => true,
            'pages'      => true,
            'feeds'      => true,
        ],
    ];

    register_post_type('leadership', $leadership_args);
    register_post_type('volunteer', $volunteer_args);
}
// Fix rewrite rules after theme activation
add_action('after_switch_theme', 'my_rewrite_flush');
function my_rewrite_flush()
{
    dcpbk_custom_post_types();
    flush_rewrite_rules();
}
