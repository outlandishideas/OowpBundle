<?php


namespace Outlandish\OowpBundle\EventListener;


use Outlandish\OowpBundle\Manager\PostManager;

class RegisterPostTypes {

	/**
	 * @var PostManager
	 */
	private $postManager;

	public function __construct(PostManager $postManager) {
		$this->postManager = $postManager;
	}

	public function onKernelRequest() {
		$this->postManager->init();
	}

} 