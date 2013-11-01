<?php


namespace Outlandish\OowpBundle\EventListener;


use Symfony\Component\Templating\PhpEngine;

class AdminAssets {

	/**
	 * @var PhpEngine
	 */
	protected $templating;

	public function __construct(PhpEngine $templating = null) {
		$this->templating = $templating;
	}

	public function onInit() {
		$assetsHelper = $this->templating->get('assets');
		wp_enqueue_script('oowp_admin_js', $assetsHelper->getUrl('bundles/outlandishoowp/wp-admin.js'), array('jquery'), false, true);
	}

}