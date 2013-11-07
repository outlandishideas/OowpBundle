<?php


namespace Outlandish\OowpBundle\Manager;


use Outlandish\OowpBundle\Query\OowpQuery;

class QueryManager extends \Outlandish\RoutemasterBundle\Manager\QueryManager {

	public function __construct(PostManager $postManager) {
		$this->postManager = $postManager;
	}

	public function query($queryArgs) {
		return new OowpQuery($this->processQueryArgs($queryArgs), $this->postManager);
	}

} 