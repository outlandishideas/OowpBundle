<?php


namespace Outlandish\Oowp\Helpers;

class WordpressAdminHelper {
	/** @see remove_menu_page */
	function removeMenuPage($menuSlug) {
		remove_menu_page($menuSlug);
	}
}