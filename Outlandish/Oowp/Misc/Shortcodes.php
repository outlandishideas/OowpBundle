<?php

namespace Outlandish\Oowp\Misc;

use Outlandish\Oowp\Oowp;

class Shortcodes {
	public function init() {
		Oowp::getInstance()->wpHelper()->addShortcode('listContent', array($this, 'oowp_fetchAll_shortcode'));
	}

	/**
	 * Shortcode that allows access to the basic  FetchAll functionality through the CMS
	 * Example: [listContent type='event' posts_per_page=3]
	 * @param $params
	 * @param $content
	 */
	public function fetchAll($params, $content) {
		$postType = $params['type']; //what kind of post are we querying
		unset($params['type']); //don't need this any more

		$className = Oowp::getInstance()->postTypeClass($postType);
		if (!$className) {
			if(WP_DEBUG) {
				die('OOWP shortcode error: unknown post-type ('.$postType.')');
			}
			return;
		}

		$query = $className::fetchAll($params);

		if($query){
			foreach($query as $post){
				$post->printItem();
			}
		}
	}
}