<a href='<?php print $post->permalink(); ?>' class='item item-<?php print $post->post_type; ?>'>
	<h3><?php print $post->title(); ?></h3>
	<p><?php print $post->excerpt(); ?></p>
</a>
