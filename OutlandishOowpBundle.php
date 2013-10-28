<?php


namespace Outlandish\OowpBundle;


use Outlandish\OowpBundle\PostType\Post;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OutlandishOowpBundle extends Bundle {

	public function boot() {
		Post::setPostManager($this->container->get('outlandish_oowp.post_manager'));
		Post::setQueryManager($this->container->get('outlandish_oowp.query_manager'));
	}

} 