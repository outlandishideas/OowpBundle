<li class="page_item page-item-<?php echo $post->ID;
	if ($post->isCurrentPage()) echo ' current_page_item';
	if ($post->isCurrentPageParent()) echo ' current_page_parent';
	if ($post->isCurrentPageAncestor()) echo ' current_page_ancestor'; ?>">

	<a href="<?php echo $post->permalink(); ?>"><?php echo $post->title(); ?></a>
	<?php

	if ($menuArgs->current_depth+1 < $menuArgs->max_depth || !$menuArgs->max_depth) :
		$children = $post->children($queryArgs);
		if ($children->post_count > 0) : ?>
			<ul class='children'>
				<?php
				$menuArgs->current_depth++;
				foreach($children as $child){
					$oowp->renderer()->printPost($child, 'menu_item', $args);
				}
				$menuArgs->current_depth--;
				?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
</li>
