<?php

namespace Outlandish\OowpBundle\Helper;

use Outlandish\OowpBundle\Helpers\OowpQuery;
use Outlandish\OowpBundle\Manager\QueryManager;
use Symfony\Component\Templating\Helper\Helper;

class PaginatorHelper extends Helper
{

	public $blockRange = 2;
	public $gapSize = 2;
	public $pageInQuery = false;
	private $baseUrl;

	public function __construct(QueryManager $queryManager) {
		$this->queryManager = $queryManager;
	}

	public function getPaginationItems($baseUrl, $queryArgs, $totalPages, $currentPage) {
		if ($baseUrl[strlen($baseUrl) - 1] == '/') {
			$baseUrl = substr($baseUrl, 0, strlen($baseUrl) - 1);
		}
		$this->baseUrl = $baseUrl;
		$this->queryArgs = $queryArgs;

		$items = array();

		$ellipsis = '<li><span class="gap">...</span></li>';

		// Show the current page with $blockRange pages either side, in a block.
		// Also show the first and last pages, with ellipses in-between them 
		// and the block, if there is a gap of at least $gapSize
		$blockSize = $this->blockRange * 2 + 1;
		$blockMin = min($currentPage - $this->blockRange, $totalPages - $blockSize);
		$blockMax = max($currentPage + $this->blockRange, $blockSize);

		if ($blockMin - $this->gapSize > 1) {
			$items = $this->getItems(1, 1);
			$items[] = $ellipsis;
			$rightStart = $blockMin;
		} else {
			$rightStart = 1;
		}

		if ($blockMax + $this->gapSize < $totalPages) {
			$items = array_merge(
				$items,
				$this->getItems($rightStart, $blockMax, $currentPage),
				(array)$ellipsis,
				$this->getItems($totalPages, $totalPages)
			);
		} else {
			$items = array_merge(
				$items,
				$this->getItems($rightStart, $totalPages, $currentPage)
			);
		}

		// add prev/next, if needed
		if ($currentPage <= $totalPages && $currentPage > 1) {
			$prevPage = $currentPage - 1;
			array_unshift($items, "<li><a href='" . $this->buildUrl($prevPage) . "' class='prev' title='Page $prevPage'>&laquo; Previous page</a></li>");
		}
		if ($currentPage > 0 && $currentPage < $totalPages) {
			$nextPage = $currentPage + 1;
			array_push($items, "<li><a href='" . $this->buildUrl($nextPage) . "' class='next' title='Page $nextPage'>Next page &raquo;</a></li>");
		}

		return $items;
	}

	private function buildUrl($page) {
		if ($this->pageInQuery) {
			if ($page == 1) {
				unset($this->queryArgs['page']);
			} else {
				$this->queryArgs['page'] = $page;
			}
			if ($this->queryArgs) {
				return $this->baseUrl . '/?' . http_build_query($this->queryArgs);
			} else {
				return $this->baseUrl . '/';
			}
		} else {
			return $this->baseUrl . "/page/$page/?" . http_build_query($this->queryArgs);
		}
	}

	// constructs an array of elements to be used in a pagination list
	private function getItems($start, $max, $page = 0) {
		$items = array();
		for ($i = $start; $i <= $max; $i++) {
			$p = $this->buildUrl($i);
			if ($page == intval($i)) {
				$items[] = "<li class='page current'><a href='$p' title='Page $i'>$i</a></li>";
			} else {
				$items[] = "<li class='page'><a href='$p' title='Page $i'>$i</a></li>";
			}
		}
		return $items;
	}

	/**
	 * Gets the items that form the pagination for the supplied query.
	 * @param OowpQuery $items
	 * @param $baseUrl
	 * @return array
	 */
	public function render(OowpQuery $items, $baseUrl) {
		$paginationItems = array();
		$pageSize = $items->query_vars['posts_per_page'];
		if ($pageSize > 0) {
			$page = max(1, $items->query_vars['paged']);
			$totalItems = (int)$items->found_posts;
			if ($totalItems == 0) {
				// found_posts can be 0 if we ask for a page outside the valid range.
				// try getting just the first one to make sure
				$tmpArgs = $items->query;
				$tmpArgs['paged'] = 1;
				$tmpArgs['posts_per_page'] = 1;
				$tmpQuery = $this->queryManager->query($tmpArgs);
				$totalItems = $tmpQuery->found_posts;
			}
			$totalPages = ceil($totalItems / $pageSize);
			if ($totalPages > 1) {
				$this->pageInQuery = true;
				$paginationItems = $this->getPaginationItems($baseUrl, $_GET, $totalPages, $page);
			}
		}

		if ($paginationItems) {
			return '<ul class="pagination">'.implode('', $paginationItems).'</ul>';
		} else {
			return '';
		}
	}

	public function getName() {
		return 'paginator';
	}
}
