<?php namespace Jarischaefer\HalApi\Representations;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\HalApiLink;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;

/**
 * Class HalApiPaginatedRepresentationImpl
 * @package Jarischaefer\HalApi\Representations
 */
class HalApiPaginatedRepresentationImpl extends HalApiRepresentationImpl implements HalApiPaginatedRepresentation
{

	/**
	 * @param LinkFactory $linkFactory
	 * @param RouteHelper $routeHelper
	 * @param HalApiLink $self
	 * @param HalApiLink $parent
	 * @param Paginator $paginator
	 * @param HalApiTransformerContract $transformer
	 * @param string $relation
	 */
	public function __construct(LinkFactory $linkFactory, RouteHelper $routeHelper, HalApiLink $self, HalApiLink $parent, Paginator $paginator, HalApiTransformerContract $transformer, string $relation)
	{
		parent::__construct($linkFactory, $routeHelper, $self, $parent);

		$route = $self->getRoute();
		$routeParameters = array_merge($self->getParameters(), ['per_page' => $paginator->perPage()]);

		$this->embedFromArray([
			$relation => $transformer->collection($paginator->items())
		])->meta('pagination', self::createPaginationMeta($paginator))
			->link('first', $linkFactory->create($route, array_merge($routeParameters, ['page' => 1])));

		$currentPage = $paginator->currentPage();

		if ($currentPage > 1) {
			$prev = $linkFactory->create($route, array_merge($routeParameters, ['page' => $currentPage - 1]));
			$this->link('prev', $prev);
		}

		if ($paginator->hasMorePages()) {
			$next = $linkFactory->create($route, array_merge($routeParameters, ['page' => $currentPage + 1]));
			$this->link('next', $next);
		}

		if ($paginator instanceof LengthAwarePaginator) {
			$this->link('last', $linkFactory->create($route, array_merge($routeParameters, ['page' => $paginator->lastPage() ?: 1])));
		}
	}

	/**
	 * @param Paginator $paginator
	 * @return array
	 */
	private static function createPaginationMeta(Paginator $paginator): array
	{
		$meta = [
			'page' => $paginator->currentPage(),
			'per_page' => $paginator->perPage(),
			'count' => count($paginator->items()),
		];

		if ($paginator instanceof LengthAwarePaginator) {
			$meta['total'] = $paginator->total();
			$meta['pages'] = $paginator->lastPage() ?: 1;
		}

		return $meta;
	}

}
