<?php

namespace Outlandish\OowpBundle\PostType;

use Outlandish\OowpBundle\Util\ArrayHelper;
use Outlandish\OowpBundle\Query\OowpQuery;
use Outlandish\RoutemasterBundle\Manager\QueryManager;
use Outlandish\OowpBundle\Manager\PostManager;

/**
 * This class contains functions which are shared across all post types, and across all sites.
 *
 * These properties of WP_Post are proxied here.
 * @property array $ancestors
 * @property string $page_template
 */
abstract class Post
{

	/**
	 *
	 * @var int
	 */
	public $ID;

	/**
	 *
	 * @var int
	 */
	public $post_author = 0;

	/**
	 *
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 *
	 * @var string
	 */
	public $post_date_gmt = '0000-00-00 00:00:00';

	/**
	 *
	 * @var string
	 */
	public $post_content = '';

	/**
	 *
	 * @var string
	 */
	public $post_title = '';

	/**
	 *
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 *
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 *
	 * @var string
	 */
	public $comment_status = 'open';

	/**
	 *
	 * @var string
	 */
	public $ping_status = 'open';

	/**
	 *
	 * @var string
	 */
	public $post_password = '';

	/**
	 *
	 * @var string
	 */
	public $post_name = '';

	/**
	 *
	 * @var string
	 */
	public $to_ping = '';

	/**
	 *
	 * @var string
	 */
	public $pinged = '';

	/**
	 *
	 * @var string
	 */
	public $post_modified = '0000-00-00 00:00:00';

	/**
	 *
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 *
	 * @var string
	 */
	public $post_content_filtered = '';

	/**
	 *
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 *
	 * @var string
	 */
	public $guid = '';

	/**
	 *
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 *
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 *
	 * @var string
	 */
	public $post_mime_type = '';

	/**
	 *
	 * @var int
	 */
	public $comment_count = 0;

	/**
	 *
	 * @var string
	 */
	public $filter;

	/**
	 * Private variable used by post formats to cache parsed content.
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 * @access private
	 */
	public $format_content;


	/**
	 * This must be overridden in subclasses
	 */
	protected static $postType = 'post';

	/**
	 * @var PostManager
	 */
	protected static $postManager = null;
	/**
	 * @var QueryManager
	 */
	protected static $queryManager = null;

	protected $_cache = array();
	protected static $_staticCache = array();

	/**
	 * @var \WP_Post
	 */
	protected $post;


#region Getters, Setters, Construct, Init

	/**
	 * @param $data int | array | object
	 */
	public function __construct($data)
	{
		//Make sure it's an object
		$this->post = self::getPostObject($data);
		foreach (get_object_vars($this->post) as $key => $val) {
			$this->$key = $val;
		}
	}

	public static function setPostManager(PostManager $postManager) {
		self::$postManager = $postManager;
	}

	public static function setQueryManager(QueryManager $queryManager) {
		self::$queryManager = $queryManager;
	}

	/**
	 * Converts the data into a wordpress post object
	 * @static
	 * @param mixed $data
	 * @throws \RuntimeException
	 * @return \WP_Post
	 */
	public static function getPostObject($data)
	{
		// todo: rationalise this
		if (is_array($data)) {
			return new \WP_Post((object)$data);
		} else if (is_object($data)) {
			if ($data instanceof \WP_Post) {
				return $data;
			} else {
				return new \WP_Post($data);
			}
		} else if (is_numeric($data) && is_integer($data+0)) {
			return self::$queryManager->find($data);
		} else {
			throw new \RuntimeException('Unable to convert to post object');
		}
	}

	public static function onRegistrationComplete() {
		// do nothing
	}

	/**
	 * Override this to hook into the save event. This is called with low priority so
	 * all fields should be already saved
	 */
	public function onSave($postData) {
		// do nothing
	}

	/**
	 * @param $name
	 * @param $args
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call($name, $args)
	{
		if (function_exists($name)) {
			global $post;
			$post = $this;
			setup_postdata($this);
			return call_user_func_array($name, $args);
		} elseif (function_exists("wp_" . $name)) {
			$name = "wp_" . $name;
			global $post;
			$post = $this;
			setup_postdata($this);
			return call_user_func_array($name, $args);
		} else {
			throw new \RuntimeException('Attempt to call non existenty method ' . $name . ' on class ' . get_class($this));
		}
	}

	/**
	 * Proxy magic properties to WP_Post
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->post->$name;
	}

	/**
	 * Proxy magic properties to WP_Post
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function __set($name, $value) {
		return $this->post->$name = $value;
	}

	/**
	 * Proxy magic properties to WP_Post
	 * @param string $name
	 * @return mixed
	 */
	public function __isset($name) {
		return isset($this->post->$name);
	}

	/**
	 * Returns the name of the function that called whatever called the caller :)
	 * e.g. if theFunction() called theOtherFunction(), theOtherFunction() could call getCaller(), which
	 * would return 'theFunction'
	 * @param null $function Don't supply this
	 * @param int $diff Don't supply this
	 * @return string
	 */
	protected function getCaller($function = null, $diff = 1) {
		if (!$function) {
			return $this->getCaller(__FUNCTION__, $diff+2);
		}

		$stack = debug_backtrace();
		$stackSize = count($stack);

		$caller = '';
		for ($i = 0; $i < $stackSize; $i++) {
			if ($stack[$i]['function'] == $function && ($i + $diff) < $stackSize) {
				$caller = $stack[$i + $diff]['function'];
				break;
			}
		}

		return $caller;
	}

	/**
	 * Gets the cached value for the function that called this
	 * @return mixed
	 */
	protected function getCacheValue() {
		$functionName = $this->getCaller();
		return (array_key_exists($functionName, $this->_cache) ? $this->_cache[$functionName] : null);
	}

	/**
	 * Sets and returns the cached value for the function that called this
	 * @param $value
	 * @return mixed
	 */
	protected function setCacheValue($value) {
		$this->_cache[$this->getCaller()] = $value;
		return $value;
	}

#endregion

#region Default getters

	/**
	 * @static
	 * @return string - the post name of this class
	 */
	public static function postType()
	{
		return static::$postType;
	}

	/**
	 * @static
	 * @return string - the human-friendly name of this class, derived from the post name
	 */
	public static function friendlyName() {
		return ucwords(str_replace('_', ' ', static::postType()));
	}

	/**
	 * @static
	 * @return string - the human-friendly name of this class, derived from the post name
	 */
	public static function friendlyNamePlural() {
		return static::friendlyName() . 's';
	}

	/**
	 * @deprecated Alias of connected
	 */
	public function getConnected($targetPostType, $single = false, $queryArgs = array(), $hierarchical = false){
		// todo: move to another class
		return $this->connected($targetPostType, $single, $queryArgs, $hierarchical);
	}

	public static function getConnectionName($targetType) {
		// todo: move to another class
		$types = array($targetType, self::postType());
		sort($types);
		return implode('_', $types);
	}

	/**
	 * @param $posts array Array of posts/post ids
	 */
	public function connectAll($posts) {
		// todo: move to another class
		foreach ($posts as $post) {
			$this->connect($post);
		}
	}

	/**
	 * @param $post int|object|Post
	 * @param array $meta
	 */
	public function connect($post, $meta = array()) {
		// todo: move to another class
		$post = Post::createPostObject($post);
		if ($post) {
			$connectionName = self::getConnectionName($post->post_type);
			/** @var \P2P_Directed_Connection_Type $connectionType */
			$connectionType = p2p_type($connectionName);
			if ($connectionType) {
				$p2pId = $connectionType->connect($this->ID, $post->ID);
				foreach ($meta as $key=>$value) {
					p2p_update_meta($p2pId, $key, $value);
				}
			}
		}
	}

	/**
	 * @param $targetPostType string e.g. post, event - the type of post you want to connect to
	 * @param bool $single - just return the first/only post?
	 * @param array $queryArgs - augment or overwrite the default parameters for the WP_Query
	 * @param bool $hierarchical - if this is true the the function will return any post that is connected to this post *or any of its descendants*
	 * @return null|Post|OowpQuery
	 */
	public function connected($targetPostType, $single = false, $queryArgs = array(), $hierarchical = false)
	{
		// move to another class
		$toReturn = null;
		if (function_exists('p2p_register_connection_type')) {
			if(!is_array($targetPostType)) {
				$targetPostType = array($targetPostType);
			}
			$connection_name = array();
			foreach ($targetPostType as $targetType) {
				$connection_name[] = self::getConnectionName($targetType);
			}

			$defaults = array(
				'connected_type'  => $connection_name,
				'post_type'	   => $targetPostType,
			);

			#todo optimisation: check to see if this post type is hierarchical first
			if ($hierarchical) {
				$defaults['connected_items'] = array_merge($this->getDescendantIds(), array($this->ID));
			} else {
				$defaults['connected_items'] = $this->ID;
			}

			// use the menu order if $hierarchical is true, or any of the target post types are hierarchical
			$useMenuOrder = $hierarchical;
			if (!$useMenuOrder) {
				foreach ($targetPostType as $postType) {
					if (is_post_type_hierarchical($postType)) {
						$useMenuOrder = true;
						break;
					}
				}
			}
			if ($useMenuOrder) {
				$defaults['orderby'] = 'menu_order';
				$defaults['order'] = 'asc';
			}

			$queryArgs   = array_merge($defaults, $queryArgs);
			$result = self::$queryManager->query($queryArgs);

			if ($hierarchical) { //filter out any duplicate posts
				$post_ids = array();
				foreach($result->posts as $i => $post){
					if(in_array($post->ID, $post_ids))
						unset($result->posts[$i]);

					$post_ids[] = $post->ID;
				}
			}

			$toReturn = $single ? null : $result;
			if (!$single) {
				$toReturn = $result;
			} else if ($result && $result->posts) {
				$toReturn = $result->posts[0];
			}
		}

		return $toReturn;
	}

	static function walkTree($p, &$current_descendants = array())
	{
		$current_descendants = array_merge($p->children, $current_descendants);
		foreach ($p->children as $child) {
			self::walkTree($child, $current_descendants);
		}

		return $current_descendants;

	}

	function getDescendants()
	{
		//todo
		$posts = self::fetchAll();
		$keyed = array();
		foreach ($posts as $post) {
			$keyed[$post->ID]		   = $post;
			$keyed[$post->ID]->children = array();
		}
		unset($posts);
		foreach ($keyed as $post) { /* This is all a bit complicated but it works */
			if ($post->post_parent)
				$keyed[$post->post_parent]->children[] = $post;
		}

		$p		   = $keyed[$this->ID];
		$descendants = static::walkTree($p);
		return $descendants;
	}

	function getDescendantIds()
	{
		$ids = array();
		foreach ($this->getDescendants() as $d) {
			$ids[] = $d->ID;
		}
		return $ids;
	}

	public function allMetadata() {
		return get_metadata('post', $this->ID);
	}

	/**
	 * Gets the metadata (custom fields) for the post
	 * @param $name
	 * @param bool $single
	 * @return array|string
	 */
	public function metadata($name, $single = true) {
		$meta = null;
		if (function_exists('get_field')) {
			$meta = get_field($name, $this->ID);
		} else {
			$meta = get_post_meta($this->ID, $name, $single);
		}
		if (!$single && !$meta) {
			$meta = array(); // ensure return type is an array
		}
		return $meta;
	}

	/***************************************************************************************************************************************
	 *																																	   *
	 *																  TEMPLATE HELPERS													   *
	 *																																	   *
	 ***************************************************************************************************************************************/

	public function title()
	{
		return apply_filters('the_title', $this->post_title, $this->ID);
	}

	public function content()
	{
		return apply_filters('the_content', $this->post_content);
	}

	public function date($format = 'd M Y')
	{
		return date($format, $this->timestamp());
		//		return apply_filters('the_date', $this->post_date);
	}

	public function modifiedDate($format = 'd M Y') {
		return date($format, strtotime($this->post_modified));
	}

	/**
	 * @return Post|null Get parent of post (or post type)
	 */
	public function parent() {
		$parentId = !empty($this->post_parent) ? $this->post_parent : static::postTypeParentId();
		return $this->getCacheValue() ?: $this->setCacheValue(
			!empty($parentId) ? self::$queryManager->find($parentId) : null
		);
	}

	/**
	 * @static
	 * @return int returns the root parent type for posts.
	 * If parent of a hierarchical post type is a page, for example, this needs to be set to that ID
	 */
	public static function postTypeParentId(){
		return 0;
	}

	/**
	 * Traverses up the getParent() hierarchy until finding one with no parent, which is returned
	 */
	public function getRoot() {
		$parent = $this->parent();
		if ($parent) {
			return $parent->getRoot();
		}
		return $this;
	}

	public function timestamp()
	{
		return strtotime($this->post_date);
	}

	function excerpt($chars = 400, $stuff = null) {
		if (empty($stuff)) {
			$stuff = $this->content();
		}
		$content = str_replace("<!--more-->", '<span id="more-1"></span>', $stuff);
		//try to split on more link
		$parts = preg_split('|<span id="more-\d+"></span>|i', $content);
		$content = $parts[0];
		$content = strip_tags($content);
		$content = str_replace('&nbsp;', ' ',$content);
		$excerpt = '';

		// try to split to the nearest paragraph
		$paragraphs = array_filter(preg_split('/(\n)/', $content), function($a) { return trim($a) != ''; });
		if($paragraphs){
			foreach ($paragraphs as $paragraph) {
				if((strlen($excerpt) + strlen($paragraph)) < $chars){
					$excerpt .= $paragraph." ";
				}else{
					break;
				}
			}
		}

		// fall back on using individual words
		if(!$excerpt){
			$words = array_filter(explode(" ", $content));
			if($words){
				foreach($words as $word){
					if((strlen($excerpt) + strlen($word)) < $chars && $word){
						$excerpt .= $word." ";
					}else{
						break;
					}
				}
			}
		}

		$excerpt = trim($excerpt);

		// add an ellipsis if the excerpt ends with a letter, comma or colon
		if(preg_match('%\w|,|:%i', substr($excerpt, -1))) {
			$excerpt = $excerpt."...";
		}

		return ($excerpt);
	}

	public function permalink() {
		if ($this->isHomepage()) {
			return rtrim(get_bloginfo('url'), '/') . '/';
		}
		return get_permalink($this->ID);
	}

	/**
	 * Fetches all posts (of any post_type) whose post_parent is this post, as well as
	 * the root posts of any post_types whose declared postTypeParentId is this post.
	 * add 'post_type' to query args to only return certain post types for children.
	 * add 'post__not_in to query args to exclude certain pages based on id.
	 * @param array $queryArgs
	 * @return OowpQuery|Post[]
	 */
	public function children($queryArgs = array())
	{
		// fetch the default children
		$defaults = array('post_parent' => $this->ID, 'post_type'=>'any');
		$queryArgs = wp_parse_args($queryArgs, $defaults);
		$children = self::$queryManager->query($queryArgs);

		$postTypes = $queryArgs['post_type'];

		// merge in the roots of any post type whose parent is this post
		if (!is_array($postTypes)) {
			$postTypes = array($postTypes);
		}
		$posts = array();
		foreach($this->childPostTypes() as $postType=>$class){
			foreach ($postTypes as $p) {
				if ($p == 'any' || ($p != 'none' && $p == $postType)) {
					$rootsQuery = self::$queryManager->query(array('post_parent' => self::postTypeParentId()));
					$posts = array_merge($posts, $rootsQuery->posts);
				}
			}
		}
		$children->posts = array_merge($children->posts, $posts);
		$children->post_count = count($children->posts);
		return $children;
	}

	/**
	 * @return array Class names of Post types having this object as their parent
	 */
	public function childPostTypes()
	{
		$names = array();
		foreach (self::$postManager->postTypeMapping() as $postType=>$class) {
			if ($class::postTypeParentId() == $this->ID) {
				$names[$postType] = $class;
			}
		}
		return $names;
	}

	/**
	 * @return array Post types of Post types that are connected to this post type
	 */
	public static function connectedPostTypes()
	{
		// todo: move to another class
		global $_registeredConnections;
		return isset($_registeredConnections[self::postType()]) ? $_registeredConnections[self::postType()] : array();
	}

	/**
	 * @return array ClassNames of Post types that are connected to this post type
	 */
	public static function connectedClassNames()
	{
		// todo: move to another class
		$names = array();
		foreach(self::connectedPostTypes() as $post_type){
			$names[] = self::$postManager->postTypeClass($post_type);
		}
		return $names;
	}

	/**
	 * Executes a wordpress function, setting $this as the global $post first, then resets the global post data.
	 * Expects the first argument to be the function, followed by any arguments
	 * @return mixed
	 */
	protected function callGlobalPost()
	{
		$args = func_get_args();
		$callback = array_shift($args);
		global $post;
		$post = $this;
		setup_postdata($this);
		$returnVal = call_user_func_array($callback, $args);
		wp_reset_postdata();
		return $returnVal;
	}

	/**
	 * @return mixed
	 * @deprecated use wp_author() instead
	 */
	public function author()
	{
		$authordata = get_userdata($this->post_author);
		return apply_filters('the_author', is_object($authordata) ? $authordata->display_name : null);
	}

	/**
	 * @return string the Robots meta tag, should be NOINDEX, NOFOLLOW for some post types
	 */
	public function robots(){
		return "";
	}


#endregion

#region HTML Template helpers

	/**
	 * TODO: Move all of these print functions to a printer class
	 */
	public function htmlLink($attrs = array())
	{
		$attributeString = '';
		foreach($attrs as $key => $value){
			$attributeString .= " $key=\"$value\" ";
		}
		return "<a href=\"" . $this->permalink() . "\" $attributeString>" . $this->title() . "</a>";
	}

	protected static function htmlList($items)
	{
		$links = array();
		foreach ($items as $term) {
			$links[] = $term->htmlLink();
		}
		return implode(', ', $links);
	}

	/**
	 * @return bool true if this is an ancestor of the page currently being viewed
	 */
	public function isCurrentPage() {
		$x = $this->getQueriedObject();
		if (isset($x) && $x->ID == $this->ID) return true;

		return false;
	}
	/**
	 * @return bool true if this is an ancestor of the page currently being viewed
	 */
	public function isCurrentPageParent() {
		$x = $this->getQueriedObject();
		if (isset($x) && ($x->post_parent == $this->ID || $x->postTypeParentId() == $this->ID)) return true;

		return false;
	}
	/**
	 * @return bool true if this is an ancestor of the page currently being viewed
	 */
	public function isCurrentPageAncestor() {
		$x = $this->getQueriedObject();
		while (isset($x) && $x) {
			if ($x->ID == $this->ID) return true;
			$x = $x->parent();
		}
		return false;
	}


	protected function featuredImageAttachmentId() {
		return $this->metadata('featured_image', true) ?: $this->metadata('image', true);
	}

	public function featuredImageUrl($image_size = 'thumbnail'){
		$image = wp_get_attachment_image_src($this->featuredImageAttachmentId(), $image_size);
		return $image[0];
	}

	public function featuredImage($size = 'thumbnail', $attrs = array()){
		return wp_get_attachment_image($this->featuredImageAttachmentId(), $size, 0, $attrs);
	}

	/**
	 * Gets the list of elements that comprise a breadcrumb trail
	 */
	function breadcrumbs(){
		$ancestors = array($this->title());
		$current = $this;
		while($parent = $current->parent()){
			$ancestors[] = $parent->htmlLink();
			$current = $parent;
		}
		$home = self::fetchHomepage();
		if ($home && $this->ID != $home->ID) {
			$ancestors[] = $home->htmlLink();
		}
		return array_reverse($ancestors);
	}

	/**
	 * Prints the breadcrumb trail using the given delimiter
	 * @param string $delimiter The text to separate the breadcrumb elements. Defaults to ' &raquo; ' ( » )
	 * @return string
	 */
	function printBreadcrumbs($delimiter = ' &raquo; ') {
		echo implode($delimiter, $this->breadcrumbs());
	}

	/**
	 * @deprecated
	 * @return OowpQuery
	 */
	public function attachments(){
		$queryArgs = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => 'inherit', 'post_parent' => $this->ID );
		return self::$queryManager->query($queryArgs);
	}

	protected function listToString($posts, $without_links = false){
		$links = array();
		foreach ( $posts as $item ) {
			$links[] = $without_links ? $item->title() : "<a href='". $item->permalink() ."'>".$item->title()."</a>";
		}

		if(count($links) > 1){
			$a1 = array_pop($links);
			$a2 = array_pop($links);
			$links[] = "$a1 & $a2";
			return implode(', ', $links);
		}elseif($links){
			return $links[0];
		}
	}

#endregion

#region Static functions

	/**
	 * @static
	 * Called by register(), for registering this post type
	 * @param $defaults
	 * @return mixed array of arguments used by register_post
	 */
	static function getRegistrationArgs($defaults) {
		return $defaults;
	}

	/**
	 * @static
	 * append the count(s) to the end of the 'right now' box on the dashboard
	 */
	public static function addRightNowCount() {
		$postType = static::postType();
		if ($postType != 'post' && $postType != 'page') {
			$singular = static::friendlyName();
			$plural = static::friendlyNamePlural();

			$numPosts = wp_count_posts($postType);

			self::printRightNowCount($numPosts->publish, $postType, $singular, $plural);
			if ($numPosts->pending > 0) {
				self::printRightNowCount($numPosts->pending, $postType, $singular . ' Pending', $plural . ' Pending', 'pending');
			}
		}
	}

	public static function printRightNowCount($count, $postType, $singular, $plural, $status = null) {
		if (get_post_type_object($postType)->show_ui) {
			$num = number_format_i18n($count);
			$text = _n($singular, $plural, intval($count));
			if (current_user_can('edit_posts')) {
				$link = 'edit.php?post_type=' . $postType;
				if ($status) {
					$link .= '&post_status=' . $status;
				}
				$num = "<a href='$link'>$num</a>";
				$text = "<a href='$link'>$text</a>";
			}

			echo '<tr>';
			echo '<td class="first b b-' . $postType . '">' . $num . '</td>';
			echo '<td class="t ' . $postType . '">' . $text . '</td>';
			echo '</tr>';
		}
	}

	/**
	 * This wraps the given array in a helper object, and calls addCustomAdminColumns with it
	 * @static
	 * @param $defaults
	 * @return array
	 */
	static final function addCustomAdminColumns_internal($defaults) {
		if (isset($_GET['post_status']) && $_GET['post_status'] == 'trash') {
			return $defaults;
		} else {
			$helper = new ArrayHelper($defaults);
			static::addCustomAdminColumns($helper);
			return $helper->array;
		}
	}

	/**
	 * @static
	 * This simply calls the non-internal version, after creating an object from the id
	 * @param $column
	 * @param $post_id
	 */
	static final function printCustomAdminColumn_internal($column, $post_id) {
		$key = 'adminColumnPost';
		// try to get the post from the cache, to minimise re-fetching
		if (!isset(Post::$_staticCache[$key]) || Post::$_staticCache[$key]->ID != $post_id) {
			$status = empty($_GET['post_status']) ? 'publish' : $_GET['post_status'];
			$query = self::$queryManager->query(array('p'=>$post_id, 'posts_per_page'=>1, 'post_status'=>$status));
			Post::$_staticCache[$key] = ($query->post_count ? $query->post : null);
		}
		if (Post::$_staticCache[$key]) {
			static::printCustomAdminColumn($column, Post::$_staticCache[$key]);
		}
	}

	/**
	 * @static
	 * Use this in combination with printCustomAdminColumn to add custom columns to the wp admin interface for the post.
	 * Argument should end up with an array of [column name]=>[column header]
	 * Ordering is respected, so use the helper functions insertBefore and insertAfter
	 * @param $helper ArrayHelper Contains the default columns
	 */
	static function addCustomAdminColumns(ArrayHelper $helper) { /* do nothing */ }

	/**
	 * @static
	 * Use this in combination with addCustomAdminColumns to render the column value for a post
	 * @param $column string The name of the column, as given in addCustomAdminColumns
	 * @param $post Post The post (subclass) object
	 */
	static function printCustomAdminColumn($column, $post) { /* do nothing */ }

	/**
	 * Gets the queried object (i.e. the post/page currently being viewed)
	 * @return null|Post
	 */
	public function getQueriedObject() {
		global $ooQueriedObject;
		if (!isset($ooQueriedObject)) {
			global $wp_the_query;
			$id = $wp_the_query->get_queried_object_id();
			$ooQueriedObject = $id ? self::$queryManager->find($id) : null;
		}
		return $ooQueriedObject;
	}

	/**
	 * @static Creates a p2p connection to another post type
	 * @param $targetPostType - the post_type of the post type you want to connect to
	 * @param array $parameters - these can overwrite the defaults. though if you change the name of the connection you'll need a custom getConnected aswell
	 * @return mixed
	 */
	static function registerConnection($targetPostType, $parameters = array())
	{
		if (!function_exists('p2p_register_connection_type'))
			return;
		$postType = (string)self::postType();

		//register this connection globally so that we can find out about it later
		global $_registeredConnections;
		if(empty($_registeredConnections[$postType])) {
			$_registeredConnections[$postType] = array();
		}
		if(empty($_registeredConnections[$targetPostType])) {
			$_registeredConnections[$targetPostType] = array();
		}
		if (in_array($targetPostType, $_registeredConnections[$postType])) {
			return; //this connection has already been registered
		}
		$_registeredConnections[$targetPostType][] = $postType;
		$_registeredConnections[$postType][] = $targetPostType;

		$types = array($targetPostType, self::postType());
		sort($types);

		$connection_name = self::getConnectionName($targetPostType);
		$defaults		= array(
			'name'		=> $connection_name,
			'from'		=> $types[0],
			'to'		  => $types[1],
			'cardinality' => 'many-to-many',
			'reciprocal'  => true
		);

		$parameters = wp_parse_args($parameters, $defaults);
		p2p_register_connection_type($parameters);
	}

	/**
	 * Factory method for creating a post of the appropriate Post subclass, for the given data
	 * @static
	 * @param object $data
	 * @return Post|null
	 */
	public static function createPostObject($data = null) {
		if ($data) {
			$postData = self::getPostObject($data);
			if ($postData) {
				$className = self::$postManager->postTypeClass($postData->post_type);
				if ($className == get_class($postData)) {
					return $postData;
				} else {
					return new $className($postData);
				}
			}
		}
		return null;
	}

	/**
	 * Factory method for creating a post of the appropriate Post subclass, for the given post ID
	 * @static
	 * @param $ids int|int[]
	 * @throws \Exception
	 * @return Post|OowpQuery|null
	 */
	public static function fetchById($ids) {
		if (is_array($ids) && $ids){
			return self::$queryManager->query(array('post__in' => $ids));
		}elseif($ids){
			return static::fetchOne(array('p' => $ids));
		}else{
			throw new \Exception("no Ids supplied to Post::fetchById()");
		}
	}

	public static function fetchBySlug($slug){
		return static::fetchOne(array(
			'name' => $slug,
			'post_type' => static::postType(),
			'numberposts' => 1
		));
	}

	/**
	 * @static
	 * @param array $queryArgs - accepts a wp_query $queryArgs array which overwrites the defaults
	 * @return OowpQuery
	 */
	public static function fetchAll($queryArgs = array())
	{
		$defaults = array(
			'post_type' => static::postType()
		);
		if (is_post_type_hierarchical($defaults['post_type'])) {
			$defaults['orderby'] = 'menu_order';
			$defaults['order'] = 'asc';
		}

		$queryArgs = wp_parse_args($queryArgs, $defaults);
		$query	= self::$queryManager->query($queryArgs);

		return $query;
	}

	/**
	 * @static
	 * @return null|Post
	 */
	static function fetchHomepage() {
		$key = 'homepage';
		if (!array_key_exists($key, Post::$_staticCache)) {
			$id = get_option('page_on_front');
			Post::$_staticCache[$key] = $id ? self::fetchById($id) : null;
		}
		return Post::$_staticCache[$key];
	}

	/**
	 * @return bool true if this is the site homepage
	 */
	public function isHomepage() {
		return $this->ID == get_option('page_on_front');
	}

	/**
	 * Return the first post matching the arguments
	 * @static
	 * @param $queryArgs
	 * @return null|Post
	 */
	static function fetchOne($queryArgs)
	{
		$queryArgs['posts_per_page'] = 1;
		$query = self::$queryManager->query($queryArgs);
		return $query->posts ? $query->post : null;
	}

	/**
	 * @static Returns the roots of this post type (i.e those whose post_parent is self::postTypeParentId)
	 * @param array $queryArgs
	 * @return OowpQuery
	 */
	static function fetchRoots($queryArgs = array())
	{
		#todo perhaps the post_parent should be set properly in the database
//		$queryArgs['post_parent'] = static::postTypeParentId();
		$queryArgs['post_parent'] = self::postTypeParentId();
		return static::fetchAll($queryArgs);
	}




#endregion


}


