<?php
/*
Plugin Name: OOWP
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 0.2
*/

use Outlandish\Oowp\Oowp;

$_registeredConnections = array();

add_action('init', function() {
	$oowp = Oowp::getInstance();
	if ($oowp) {
		$oowp->init();
	}
});

function oowp_print_right_now_count($count, $postType, $singular, $plural, $status = null) {
	if (get_post_type_object($postType)->show_ui) {
		$num = number_format_i18n($count);
		$text = _n($singular, $plural, intval($count) );
		if ( current_user_can( 'edit_posts' )) {
			$link = 'edit.php?post_type=' . $postType;
			if ($status) {
				$link .= '&post_status='.$status;
			}
			$num = "<a href='$link'>$num</a>";
			$text = "<a href='$link'>$text</a>";
		}

		echo '<tr>';
		echo '<td class="first b b-' . $postType . '">' . $num . '</td>';
		echo '<td class="t ' . $postType . '">' . $text . '</td>';
		echo '</tr>';
	}
}

