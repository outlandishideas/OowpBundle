<?php


namespace Outlandish\OowpBundle\Manager;


use Outlandish\OowpBundle\Helpers\OowpQuery;
use Outlandish\OowpBundle\Manager\PostManager;

class QueryManager extends \Outlandish\RoutemasterBundle\Manager\QueryManager {

	public function __construct(PostManager $postManager) {
		$this->postManager = $postManager;
	}

	public function query($queryArgs) {
		return new OowpQuery($queryArgs, $this->postManager, $this);
	}

} 