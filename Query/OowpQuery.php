<?php

namespace Outlandish\OowpBundle\Query;

use Outlandish\OowpBundle\Manager\PostManager;
use Outlandish\OowpBundle\PostType\Post;

/**
 * Extend WP_Query to make it return Oowp Post objects instead of plain WP_Post objects.
 *
 * Also implements some array interfaces for convenience.
 */
class OowpQuery extends \WP_Query implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @var PostManager
	 */
	protected $postManager;

	/**
	 * @param string|array $query
	 * @param PostManager $postManager
	 * @throws \RuntimeException
	 */
	function __construct($query = '', $postManager) {
		$this->postManager = $postManager;

		parent::__construct($query);

		if ($this->query_vars['error']) {
			throw new \RuntimeException('Query error ' . $this->query_vars['error']);
		}
	}

	/* Interface methods */

	public function getIterator() {
		return new \ArrayIterator($this->posts);
	}

	public function offsetExists($offset) {
		return isset($this->posts[$offset]);
	}

	public function offsetGet($offset) {
		return $this->posts[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->posts[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->posts[$offset]);
	}

	public function count() {
		return count($this->posts);
	}

//	/**
//	 * Stores $this as the global $wp_query, executes the passed-in WP function, then reverts $wp_query
//	 * @return mixed
//	 */
//	protected function callGlobalQuery() {
//		global $wp_query;
//		$args     = func_get_args();
//		$function = array_shift($args);
//		$oldQuery = $wp_query;
//		$wp_query = $this;
//		$returnVal = call_user_func_array($function, $args);
//		$wp_query = $oldQuery;
//		return $returnVal;
//	}
//
//	/**
//	 * Returns the prev/next links for this query
//	 * @param string $sep
//	 * @param string $preLabel
//	 * @param string $nextLabel
//	 * @return mixed
//	 */
//	public function postsNavLink($sep = '', $preLabel = '', $nextLabel = '') {
//		return $this->callGlobalQuery('get_posts_nav_link', $sep, $preLabel, $nextLabel);
//	}
//
//	/**
//	 * @return QueryVars
//	 */
//	public function queryVars() {
//		return new QueryVars($this->query_vars);
//	}
//
//	public function sortByIds($ids) {
//		$indexes = array_flip($ids);
//		usort($this->posts, function($a, $b) use ($indexes) {
//			$aIndex = $indexes[$a->ID];
//			$bIndex = $indexes[$b->ID];
//			return $aIndex < $bIndex ? -1 : 1;
//		});
//	}

	/**
	 * Convert WP_Post objects to Oowp Post objects
	 * @return Post[]
	 */
	public function &get_posts() {
		parent::get_posts();

		foreach ($this->posts as $i => $post) {
			$classname = $this->postManager->postTypeClass($post->post_type);
			$this->posts[$i] = new $classname($post);
		}

		if (count($this->posts)) {
			$this->post = $this->posts[0];
			$this->queried_object = $this->post;
			$this->queried_object_id = $this->post->ID;
		}

		return $this->posts;
	}
}
