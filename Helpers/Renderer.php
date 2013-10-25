<?php


namespace Outlandish\Oowp\Helpers;


use Outlandish\Oowp\Oowp;
use Outlandish\Oowp\PostType\Post;

class Renderer {
	protected $paths = array();
	protected $postTypeSeparators = array(',', '|', '\+', '\s');
	protected $partialNameSeparator = '-';

	function defaultPath() {
		return __DIR__ . '/../Resources/partials';
	}

	function addPath($path, $priority) {
		$this->paths[] = array(realpath($path), $priority);
	}

	/**
	 * Recurses through the path structure, looking for files that match the partial type
	 * @param $path
	 * @param $partialType
	 * @param $postType
	 * @return array
	 */
	protected function findPartialMatches($path, $partialType, $postType = null) {
		$matches = array();

		if (!file_exists($path) || !is_dir($path)) {
			return $matches;
		}
		if (in_array($this->partialNameSeparator, $this->postTypeSeparators)) {
			// partial name separator must not be one of the post type separators
			return $matches;
		}

		$entries = array();
		$fh = opendir($path);
		while (false !== ($entry = readdir($fh))) {
			if (!in_array($entry, array('.', '..'))) {
				$entries[] = $entry;
			}
		}
		closedir($fh);

		if ($postType) {
			foreach ($entries as $entry) {
				if ($entry == $partialType . '.php') {
					$matches[] = $path . DIRECTORY_SEPARATOR . $entry;
				}
			}
		} else {
			$separatorRegex = '/[' . implode('', $this->postTypeSeparators) . ']+/';
			sort($entries);
			foreach ($entries as $entry) {
				$fullPath = $path . DIRECTORY_SEPARATOR . $entry;
				$filename = basename($fullPath);
				if (strpos($filename, $partialType) === 0) {
					// if there is a file that contains the post name too, make that a priority for selection
					$postTypeStart = strpos($filename, $this->partialNameSeparator);
					$postTypeEnd = strpos($filename, '.php');
					if ($postTypeStart > 0) {
						// split everything after the '-' by the valid separators
						$postTypes = preg_split($separatorRegex, substr($filename, $postTypeStart+1, $postTypeEnd - $postTypeStart - 1));
						if (in_array($postType, $postTypes)) {
							$matches[] = array($fullPath, 0 + count($matches));
						}
					} else {
						$matches[] = array($fullPath, 0 - count($matches));
					}
				}
			}
			$matches = $this->getSortedByRank($matches);
		}
		return $matches;
	}

	function getSortedByRank($data) {
		usort($data, function($a, $b) {
			$a = $a[1];
			$b = $b[1];
			if ($a == $b) {
				return 0;
			}
			return $a > $b ? -1 : 1;
		});
		return array_map(function($a) { return $a[0]; }, $data);
	}

	function printPartial($partialType, $args = array()) {
	}

	/**
	 * looks for $partialType-$post_type.php, then $partialType.php in the partials directory of
	 * the theme, then the plugin
	 * @param Post $post
	 * @param string $partialType e.g. main,  item, promo, etc
	 * @param array $args To be used by the partial file
	 */
	function printPost(Post $post, $partialType, $args = array()) {
		extract($args);

		$postType = $post->post_type;
		$oowp = Oowp::getInstance();

		$paths = $this->getSortedByRank($this->paths);
		foreach ($paths as $path) {
			$matches = $this->findPartialMatches($path, $partialType, $postType);
			if ($matches) {
				$match = $matches[0];
				if (WP_DEBUG) print "\n\n<!--start $match start-->\n";
				include($match);
				if (WP_DEBUG) print "\n<!--end $match end-->\n\n";
				return;
			}
		}

		// show an error message
		?>
		<div class="oowp-error" style="background: #fcc;border: 1px dashed #c33;margin: 2px;padding: 2px;float: left;clear: both;">
			<span class="oowp-post-type"><?php echo $postType; ?></span>: <span class="oowp-post-id"><?php echo $post->ID; ?></span>
			<div class="oowp-error-message">Partial '<?php echo $partialType; ?>' not found</div>
		</div>
<?php
// 		throw new Exception(sprintf("Partial $partialType not found", $paths, get_class($this)));
	}

	/**
	 * Prints the children of the given post (or the $postType roots if not given)
	 * @param Post $rootPost
	 * @param string $postType
	 * @param int $maxDepth
	 * @return string
	 */
	public function renderMenuItems(Post $rootPost = null, $postType = 'page', $maxDepth = 3){
		ob_start();
		$queryArgs = array(
			'post_type' => $postType,
			'orderby' => 'menu_order',
			'order' => 'asc'
		);
		if ($rootPost) {
			$posts = $rootPost->children($queryArgs);
		} else {
			$class = Oowp::getInstance()->postTypeClass($postType);
			$posts = $class::fetchRoots($queryArgs);
		}

		$menuArgs = array(
			'max_depth' => $maxDepth,
			'current_depth' => 1
		);
		foreach($posts as $post){
			$this->printPost($post, 'menu_item', array('queryArgs'=>$queryArgs, 'menuArgs'=>(object)$menuArgs));
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

}