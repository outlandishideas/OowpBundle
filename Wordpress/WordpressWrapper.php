<?php

namespace Outlandish\OowpBundle\Wordpress;

use Outlandish\OowpBundle\PostType\Post as OowpPost;

/**
 * Wraps Wordpress functions to aid testing and cleaner code
 *
 * Class WordpressWrapper
 * @package Outlandish\AcadOowpBundle\Wordpress
 */
class WordpressWrapper
{
    /**
     * Wraps the is_wp_error() Wordpress function
     *
     * @param $thing
     * @return bool
     */
    public function isWPError($thing)
    {
        return is_wp_error($thing);
    }

    /**
     * Wraps the get_nav_menu_locations() Wordpress function
     *
     * @return array
     */
    public function getNavMenuLocations()
    {
        return get_nav_menu_locations();
    }

    /**
     * Wraps the get_term() Wordpress function
     *
     * @param $term
     * @param $taxonomy
     * @param string $output
     * @param string $filter
     * @return mixed|null|\WP_Error
     */
    public function getTerm($term, $taxonomy, $output = 'OBJECT', $filter = 'raw')
    {
        return get_term($term, $taxonomy, $output, $filter);
    }

    /**
     * Wraps the wp_get_nav_menu_items() Wordpress function
     *
     * @param $menu
     * @param array $args
     * @return mixed
     */
    public function getNavMenuItems($menu, $args = array())
    {
        return wp_get_nav_menu_items($menu, $args);
    }

    /**
     * Wraps the is_search() Wordpress function
     *
     * @return bool
     */
    public function isSearch()
    {
        return is_search();
    }

    /**
     * Wraps the is_404() Wordpress function
     *
     * @return bool
     */
    public function is404()
    {
        return is_404();
    }

    /**
     * Wraps the is_single() Wordpress function
     *
     * @return bool
     */
    public function isSingle()
    {
        return is_single();
    }

    /**
     * Wraps the get_post_ancestors() Wordpress function
     *
     * @param int|OowpPost|\WP_Post $post can either pass the post object or the post objects ids
     *
     * @return array
     */
    public function getPostAncestors($post)
    {
        return get_post_ancestors($post);
    }

    /**
     * Wraps the home_url() Wordpress function
     *
     * @return string|void
     */
    public function homeUrl()
    {
        return home_url();
    }

    /**
     * @return bool
     */
    public function isHome()
    {
        return is_home();
    }

    /**
     * Wraps the wp_insert_post() function for Wordpress Core
     *
     * @param $postArr
     * @param bool $wpError
     *
     * @return int|\WP_Error
     */
    public function wpInsertPost($postArr, $wpError = false)
    {
        return wp_insert_post($postArr, $wpError);
    }

    /**
     * Wraps the get_users() function for Wordpress Core
     *
     * @param array $args
     *
     * @return array
     */
    public function getUsers($args = array())
    {
        return get_users($args);
    }

    /**
     * Wraps the get_user_by() function for Wordpress Core
     *
     * @param $field
     * @param $value
     *
     * @return bool|\WP_User
     */
    public function getUserBy($field, $value)
    {
        return get_user_by($field, $value);
    }
}