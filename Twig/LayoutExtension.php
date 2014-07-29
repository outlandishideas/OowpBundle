<?php

namespace Outlandish\OowpBundle\Twig;

class LayoutExtension extends \Twig_Extension {
	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction('wp_head', array($this, 'getHead')),
            new \Twig_SimpleFunction('wp_footer', array($this, 'wpFooter') ),
            new \Twig_SimpleFunction('language_attributes', array($this, 'languageAttributes') ),
            new \Twig_SimpleFunction('get_bloginfo', array($this, 'getBlogInfo') ),
            new \Twig_SimpleFunction('site_url', array($this, 'siteUrl') ),
		);
	}

	public function getHead()	{
		return wp_head();
	}

    public function wpFooter(){
        return wp_footer();
    }

    public function languageAttributes(){
        return language_attributes();
    }

    public function siteUrl($arg = ""){
        return site_url($arg);
    }

    public function getBlogInfo($arg){
        return get_bloginfo($arg);
    }

	public function getName() {
		return 'layout_extension';
	}
}