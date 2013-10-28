<?php


namespace Outlandish\OowpBundle\Helper;


use Outlandish\OowpBundle\Manager\QueryManager;
use Outlandish\OowpBundle\Oowp;
use Outlandish\OowpBundle\PostType\Post;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine;
use Symfony\Component\Templating\Helper\Helper;

class MenuHelper extends Helper {

	public function __construct(QueryManager $queryManager, Oowp $postManager, PhpEngine $template) {
		$this->queryManager = $queryManager;
		$this->postManager = $postManager;
		$this->templating = $template;
	}

	/**
	 * Prints the children of the given post (or the $postType roots if not given)
	 * @param Post $rootPost
	 * @param string $postType
	 * @param int $maxDepth
	 * @return string
	 */
	public function render(Post $rootPost = null, $postType = 'page', $maxDepth = 1) {
		$queryArgs = array(
			'post_type' => $postType,
			'orderby' => 'menu_order',
			'order' => 'asc'
		);
		if ($rootPost) {
			$posts = $rootPost->children($queryArgs);
		} else {
			$class = $this->postManager->postTypeClass($postType);
			$queryArgs['post_parent'] = $class::postTypeParentId();
			$posts = $this->queryManager->query($queryArgs);
		}

		$menuArgs = array(
			'max_depth' => $maxDepth,
			'current_depth' => 1
		);
		$html = '';
		foreach ($posts as $post) {
			$html .= $this->templating->render('OutlandishOowpBundle:Helper:menuItem.html.php', array(
				'post'=> $post,
				'queryArgs' => $queryArgs,
				'menuArgs' => (object)$menuArgs
			));
		}
		return $html;
	}

	public function getName() {
		return 'menu';
	}
}