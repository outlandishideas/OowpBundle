<?php


namespace Outlandish\OowpBundle\EventListener;


use Outlandish\OowpBundle\Oowp;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RegisterPostTypes {

	/**
	 * @var Oowp
	 */
	private $postManager;

	public function onKernelRequest(GetResponseEvent $event) {
		$this->postManager->init();
	}

	public function setPostManager(Oowp $postManager) {
		$this->postManager = $postManager;
	}

} 