<?php

namespace Outlandish\OowpBundle\Helpers;

class WordpressHelper {

	private $acfFields = array();
	private $adminHelper;

	/** @see add_shortcode */
	function addShortcode($tag, $callable) {
		add_shortcode($tag, $callable);
	}

	public function siteInfo($info) {
		return get_bloginfo($info);
	}

	/** @see wp_enqueue_style */
	public function enqueueStyle($handle, $src = false, $dependencies = array(), $version = false, $media = 'all') {
		wp_enqueue_style($handle, $src, $dependencies, $version, $media);
	}

	/** @see wp_enqueue_script */
	public function enqueueScript($handle, $src = false, $dependencies = array(), $version = false, $inFooter = false) {
		wp_enqueue_script($handle, $src, $dependencies, $version, $inFooter);
	}

	/** @see wp_add_inline_style */
	public function addInlineStyle($handle, $css) {
		if (strpos($css, '<style') === false) {
			$css = '<style type="text/css">' . PHP_EOL . $css . '</style>';
		}
		wp_add_inline_style($handle, $css);
	}

	/** @see add_role */
	public function addRole($role, $displayName, $capabilities = array()) {
		add_role($role, $displayName, $capabilities);
	}

	/**
	 * No trailing slash as standard (http://www.example.com), if trailing slash is required, include as first argument, ($a = '/')
	 * second argument returns protocol for the url (http, https, etc)
	 * @see site_url
	 */
	public function siteURL($relativePath = '') {
		return site_url(null, null) . '/' . ltrim($relativePath, '/');
	}

	/**
	 * Gets the url for an asset in this theme.
	 * With no argument, this is just the root directory of this theme
	 * @param string $relativePath
	 * @return string
	 */
	public function assetUrl($relativePath = '') {
		$relativePath = '/' . ltrim($relativePath, '/');
		return get_template_directory_uri() . $relativePath;
	}

	public function imageUrl($fileName) {
		return $this->assetUrl('/images/' . $fileName);
	}

	public function jsUrl($fileName) {
		return $this->assetUrl('/js/' . $fileName);
	}

	public function cssUrl($fileName) {
		return $this->assetUrl('/css/' . $fileName);
	}

	/**
	 * @deprecated Use assetUrl() instead
	 * @return string
	 */
	public function siteThemeURL() {
		return $this->assetUrl();
	}

	public function directory($path = '') {
		return get_stylesheet_directory() . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
	}

	public function siteTitle() {
		return $this->siteInfo('name');
	}

	public function pageTitle($separator = '&laquo;', $display = false, $separatorLocation = 'right') {
		return wp_title($separator, $display, $separatorLocation);
	}

	/**
	 * Adds an image size to the theme, and adds the hook that ensures the thumbnails get resized when edited through the CMS
	 * @param $name
	 * @param $width
	 * @param $height
	 * @param bool $crop
	 */
	public function addImageSize($name, $width, $height, $crop = false){
		if ( function_exists( 'add_image_size' ) ) {
			add_image_size( $name, $width, $height, $crop);
			add_action('image_save_pre', function($data){
				global $_wp_additional_image_sizes;
				foreach($_wp_additional_image_sizes as $size => $properties){
					update_option($size."_size_w", $properties['width']);
					update_option($size."_size_h", $properties['height']);
					update_option($size."_crop", $properties['crop']);
				}
				return $data;
			});
		}
	}

	/**
	 * Get oowp class name for requested post type
	 * @param string $postType
	 * @return null|string
	 */
//	public function postClass($postType) {
//		global $_registeredPostClasses, $wp_post_types;
//		if (!isset($wp_post_types[$postType])) {
//			return null; //unregistered post type
//		} elseif (!isset($_registeredPostClasses[$postType])) {
//			return 'MiscPost'; //post type with no dedicated class
//		} else {
//			return $_registeredPostClasses[$postType];
//		}
//	}
//
//	public function postType($postClass) {
//		global $_registeredPostClasses;
//		foreach ($_registeredPostClasses as $type=>$class) {
//			if ($class == $postClass) {
//				return $type;
//			}
//		}
//		return null;
//	}
//
//	public function postTypeClasses() {
//		global $_registeredPostClasses;
//		return array_values($_registeredPostClasses);
//	}
//
//	public function postTypes() {
//		global $_registeredPostClasses;
//		return array_keys($_registeredPostClasses);
//	}

	/**
	 * @return \wpdb
	 */
	public function db() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Gets an ACF options value
	 * @param $optionName
	 * @return bool|mixed|string
	 */
	public function acfOption($optionName) {
		return get_field($optionName, 'option');
	}

	/**
	 * Gets the acf definitions, keyed by their hierarchical name (using hyphens).
	 * If $name is provided, a single acf definition is returned (if found)
	 * @param $acfPostName
	 * @param null $name
	 * @return array|null
	 */
	public function acf($acfPostName, $name = null) {
		if (!isset($this->acfFields[$acfPostName])) {
			$wpdb = $this->db();
			$acfData = $wpdb->get_col("SELECT pm.meta_value FROM $wpdb->posts AS p INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id WHERE p.post_name = 'acf_{$acfPostName}' AND pm.meta_key like 'field_%'");
			$acfFields = array();
			$this->populateAcf($acfFields, $acfData);
			$this->acfFields[$acfPostName] = $acfFields;
		}
		if ($name) {
			return array_key_exists($name, $this->acfFields[$acfPostName]) ? $this->acfFields[$acfPostName][$name] : null;
		} else {
			return $this->acfFields[$acfPostName];
		}
	}

	/**
	 * Recursively populates the acf definitions list
	 * @param $toPopulate
	 * @param $data array The ACF definition from the database
	 * @param string $prefix The prefix to use in the name. (only applicable to hierarchical fields, i.e. repeater fields)
	 */
	private function populateAcf(&$toPopulate, $data, $prefix = '') {
		foreach ($data as $acf) {
			$acf = maybe_unserialize($acf);
			$toPopulate[$prefix . $acf['name']] = $acf;
			if (!empty($acf['sub_fields'])) {
				$this->populateAcf($toPopulate, $acf['sub_fields'], $acf['name'] . '-');
			}
		}
	}

	/**
	 * @return \WP_User
	 */
	public static function currentUser() {
		global $current_user;
		get_currentuserinfo();
		return $current_user;
	}

	/** @see add_action */
	public function addAction($tag, $functionToAdd, $priority = 10, $acceptedArgs = 1) {
		add_action($tag, $functionToAdd, $priority, $acceptedArgs);
	}

	/** @see remove_action */
	function removeAction($tag, $functionToRemove, $priority = 10) {
		remove_action($tag, $functionToRemove, $priority);
	}

	/**
	 * Reverse the effects of register_taxonomy()
	 *
	 * @package WordPress
	 * @subpackage Taxonomy
	 * @since 3.0
	 * @uses $wp_taxonomies Modifies taxonomy object
	 *
	 * @param string $taxonomy Name of taxonomy object
	 * @param array|string $object_type Name of the object type
	 * @return bool True if successful, false if not
	 */
	public function unregisterTaxonomy($taxonomy, $object_type = '')
	{
		global $wp_taxonomies;

		if (!isset($wp_taxonomies[$taxonomy]))
			return false;

		if (!empty($object_type)) {
			$i = array_search($object_type, $wp_taxonomies[$taxonomy]->object_type);

			if (false !== $i)
				unset($wp_taxonomies[$taxonomy]->object_type[$i]);

			if (empty($wp_taxonomies[$taxonomy]->object_type))
				unset($wp_taxonomies[$taxonomy]);
		} else {
			unset($wp_taxonomies[$taxonomy]);
		}

		return true;
	}

	function unregisterPostType($postType) {
		if (function_exists('unregister_post_type')) {
			return unregister_post_type($postType);
		} else {
			global $wp_post_types;
			if (isset($wp_post_types[$postType])) {
				unset($wp_post_types[$postType]);

				add_action('admin_menu', function() use ($postType) {
					remove_menu_page('edit.php' . ($postType == 'post' ? "" : "?post_type=$postType"));
				}, $postType);
				return true;
			}
			return false;
		}
	}
}
