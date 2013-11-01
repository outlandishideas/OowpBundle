<?php


namespace Outlandish\OowpBundle\EventListener;


use Outlandish\OowpBundle\Oowp;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RegisterPostTypes {

	/**
	 * @var Oowp
	 */
	private $postManager;

	public function __construct(Oowp $postManager) {
		$this->postManager = $postManager;
	}

	public function onKernelRequest() {
		$this->postManager->init();
	}

} 