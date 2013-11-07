<?php


namespace Outlandish\OowpBundle\Manager;


use Outlandish\OowpBundle\Helper\WordpressHelper;
use Outlandish\OowpBundle\PostType\Post;

class PostManager {

	protected $postTypeMapping = array();
	protected $hiddenAdminMenuPages = array(
		'edit.php',
		'link-manager.php'
	);

	/** @var WordpressHelper */
	protected $wpHelper;

	/**
	 * Initialises OOWP with the given (fully qualified) post type classes
	 * @param $classes
	 */
	public function init($classes = array()) {
		$this->registerPostTypes($classes);
		$this->addWordpressHooks();
		$this->postInit();
	}

	public function wpHelper() {
		if (!$this->wpHelper) {
			$this->wpHelper = new WordpressHelper();
		}
		return $this->wpHelper;
	}


	/**
	 * Registers all of the post types in the $classes array
	 * @throws \Exception
	 */
	protected function registerPostTypes($classes) {
		$defaultArgs = array(
			'labels'	  => array(),
			'public'	  => true,
			'has_archive' => true,
			'rewrite'	  => array(
				'slug'	  => '',
				'with_front'=> false
			),
			'show_ui'	  => true,
			'supports'	  => array(
				'title',
				'editor',
				'revisions',
			)
		);
		foreach ($classes as $class) {
			if (!is_subclass_of($class, 'Outlandish\OowpBundle\PostType\Post')) {
				throw new \Exception('Invalid post type class: ' . $class);
			}
			$postType = $class::postType();
			if ($postType == Post::postType()) {
				throw new \Exception('"post" is already registered. Have you forgotten to change the static "$postType" property of ' . $class . '?');
			}
			$args = $defaultArgs;
			$args['labels'] = self::generateLabels($class::friendlyName(), $class::friendlyNamePlural());
			$args['rewrite']['slug'] = $postType;
			$args = $class::getRegistrationArgs($args);
			register_post_type($postType, $args);
			$this->postTypeMapping[$postType] = $class;
			add_filter("manage_edit-{$postType}_columns", "$class::addCustomAdminColumns_internal");
			add_action("manage_{$postType}_posts_custom_column", "$class::printCustomAdminColumn_internal", 10, 2);
			add_action('right_now_content_table_end', "$class::addRightNowCount");

		}
	}

	/**
	 * Hooks into various wordpress events
	 */
	protected function addWordpressHooks() {
		// call the appropriate onSave function when a post is saved
		foreach ($this->postTypeMapping as $postType=>$class) {
			add_filter('save_post' , function($postId, $postData) use ($postType, $class) {
				if ($postData && $postData->post_type == $postType) {
					/** @var Post $post */
					$post = $class::fetchById($postId);
					if ($post) {
						$post->onSave($postData);
					}
				}
			}, '99', 2); // use large priority value to ensure this happens after ACF finishes saving its metadata
		}

		//override permalinks
		add_filter('post_type_link', function($post_link, $post, $leavename, $sample) {
			static $permalinkHookPostId;
			if ($post->post_name && $post->ID != $permalinkHookPostId) {
				// prevent infinite recursion by saving the ID before calling permalink() (which may come back here again)
				$permalinkHookPostId = $post->ID;
				$post_link = Post::createPostObject($post)->permalink($leavename);
				$permalinkHookPostId = null;
			}
			return $post_link;
		}, 10, 4);

		if (is_admin()) {
			//hide certain admin pages from menu
			$pages = $this->hiddenAdminMenuPages;
			add_action('admin_menu', function() use ($pages) {
				foreach ($pages as $page) {
					remove_menu_page($page);
				}
			});

			//make preview permalink resemble real permalink so that routing can work
			//todo: move this to RouteMasterBundle
			add_filter('preview_post_link', function ($link) {
				$qs = parse_url($link, PHP_URL_QUERY);
				$args = wp_parse_args($qs);

				//only modify link of unpublished posts (published posts use preview_id param)
				if (isset($args['p']) || isset($args['page_id'])) {
					$id = isset($args['p']) ? $args['p'] : $args['page_id'];
					$post = Post::createPostObject($id);
					if ($post) {
						$post->post_name = sanitize_title($post->post_name ? $post->post_name : $post->post_title, $post->ID);
						$link = $post->permalink();
						$link .= '?' . $qs;
					}
//					list($link, $slug) = get_sample_permalink($id);
//					$link = preg_replace('/%(page|post)name%/', $slug, $link);
				}

				return $link;
			});
		}
	}

	/**
	 * Run at the end of the init function, after all post types have been registered
	 */
	protected function postInit() {
		foreach ($this->postTypeMapping as $class) {
			$class::onRegistrationComplete();
		}
	}

	/**
	 * Attempts to style each post type menu item and posts page with its own icon, as found in the given directory.
	 * In order to be automatically styled, icon names should have the following forms:
	 * - icon-{post_type}-page (for posts pages, next to header)
	 * - icon-{post_type}-menu-inactive (for menu items)
	 * - icon-{post_type}-menu-active (for menu items when active/hovered)
	 */
	function generateAdminCss($resourcesDir, $resourcesUrl) {
		//todo include from somewhere
		$dir = $resourcesDir . 'images/';
		$selectors = array();
		$patterns = array(
			'menu-inactive' => '#adminmenu #menu-posts-{post_type} .wp-menu-image',
			'menu-active' => '#adminmenu #menu-posts-{post_type}:hover .wp-menu-image, #adminmenu #menu-posts-{post_type}.wp-has-current-submenu .wp-menu-image',
			'page' => '#wpcontent #icon-edit.icon32-posts-{post_type}'
		);
		$patternMatch = implode('|', array_keys($patterns));
		if (is_dir($dir)) {
			$postTypes = array_keys($this->postTypeMapping);
			$handle = opendir($dir);
			while (false !== ($file = readdir($handle))) {
				$fullFile = $dir . DIRECTORY_SEPARATOR . $file;
				if (!is_dir($fullFile) && filesize($fullFile)) {
					$imageSize = @getimagesize($fullFile);
					if ($imageSize && $imageSize[0] && $imageSize[1]) {
						foreach ($postTypes as $postType) {
							if (preg_match('/icon-' . $postType . '-(' . $patternMatch .')\.\w+$/', $file, $matches)) {
								$type = $matches[1];
								$selectors[preg_replace('/{post_type}/', $postType, $patterns[$type])] = array(
									'background-image' => "url(" . $resourcesUrl . 'images/' . $file . ')',
									'background-repeat' => 'no-repeat',
									'background-position' => 'center center'
								);
							}
						}
					}
				}
			}
		}
		$css = '';
		if ($selectors) {
			foreach ($selectors as $selector=>$properties) {
				$css .= $selector . ' {' . PHP_EOL;
				foreach ($properties as $key=>$value) {
					$css .= "  {$key}: {$value} !important;" . PHP_EOL;
				}
				$css .= '}' . PHP_EOL;
			}
		}
		return $css;
	}

	protected function generateLabels($singular, $plural = null) {
		if (!$plural) {
			$plural = $singular . 's';
		}
		return array(
			'name' => $plural,
			'singular_name' => $singular,
			'add_new' => 'Add New',
			'add_new_item' => 'Add New ' . $singular,
			'edit_item' => 'Edit ' . $singular,
			'new_item' => 'New ' . $singular,
			'all_items' => 'All ' . $plural,
			'view_item' => 'View ' . $singular,
			'search_items' => 'Search ' . $plural,
			'not_found' =>  'No ' . $plural . ' found',
			'not_found_in_trash' => 'No ' . $plural . ' found in Trash',
			'parent_item_colon' => 'Parent ' . $singular . ':',
			'menu_name' => $plural
		);
	}

	public function postTypeClass($postType) {
		global $wp_post_types;
		if (!isset($wp_post_types[$postType])) {
			return null; //unregistered post type
		} elseif (array_key_exists($postType, $this->postTypeMapping)) {
			return $this->postTypeMapping[$postType];
		} else {
			return 'Outlandish\OowpBundle\PostType\MiscPost'; //post type with no dedicated class
		}
	}

	public function postTypeMapping() {
		return $this->postTypeMapping;
	}
}