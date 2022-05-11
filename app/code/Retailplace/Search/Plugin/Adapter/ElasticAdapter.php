<?php
/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Plugin\Adapter;

use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\AdapterInterface;
use Mirasvit\SearchElastic\Adapter\Mapper;
use Magento\Framework\Search\Adapter\Mysql\ResponseFactory;
use Mirasvit\SearchElastic\Adapter\Aggregation\Builder as AggregationBuilder;
use Mirasvit\SearchElastic\Model\Engine;
use Mirasvit\SearchElastic\Model\Config;
use Magento\Framework\Search\Adapter\Mysql\Adapter as MysqlAdapter;
use Retailplace\Search\Model\SearchFilter;
use Exception;
use Magento\Framework\Search\Response\QueryResponse;
use Mirasvit\SearchElastic\Adapter\ElasticAdapter as AdapterElasticAdapter;
use Closure;

/**
 * Class ElasticAdapter
 */
class ElasticAdapter implements AdapterInterface
{
    /**
     * @var int
     */
    const SELLER_PRODUCT_COUNT = 5;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var AggregationBuilder
     */
    private $aggregationBuilder;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var MysqlAdapter
     */
    private $mysqlAdapter;

    /**
     * @var SearchFilter
     */
    private $searchFilter;

    /**
     * ElasticAdapter constructor.
     *
     * @param Mapper $mapper
     * @param ResponseFactory $responseFactory
     * @param AggregationBuilder $aggregationBuilder
     * @param Engine $engine
     * @param Config $config
     * @param MysqlAdapter $mysqlAdapter
     * @param SearchFilter $searchFilter
     */
    public function __construct(
        Mapper $mapper,
        ResponseFactory $responseFactory,
        AggregationBuilder $aggregationBuilder,
        Engine $engine,
        Config $config,
        MysqlAdapter $mysqlAdapter,
        SearchFilter $searchFilter
    ) {
        $this->mapper = $mapper;
        $this->responseFactory = $responseFactory;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->engine = $engine;
        $this->config = $config;
        $this->mysqlAdapter = $mysqlAdapter;
        $this->searchFilter = $searchFilter;
    }

    /**
     * Around plugin  for Query
     *
     * @param AdapterElasticAdapter $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @return QueryResponse|mixed
     * @throws Exception
     */
    public function aroundQuery(
        AdapterElasticAdapter $subject,
        Closure $proceed,
        RequestInterface $request
    ) {
        if ($this->searchFilter->getSellerView()
            || $this->searchFilter->getNewSellerViewIds()
        ) {
            return $this->query($request);
        }

        return $proceed($request);
    }

    /**
     * Original code for preparing query
     *
     * @param RequestInterface $request
     *
     * @return array|QueryResponse
     * @throws Exception
     */
    private function getBaseQuery(RequestInterface $request)
    {
        $query = $this->mapper->buildQuery($request);

        if (!$this->engine->isAvailable()) {
            return $this->mysqlAdapter->query($request);
        }

        if ($request->getName() == 'quick_search_container'
            || $request->getName() == 'catalog_view_container'
            || $request->getName() == 'catalogsearch_fulltext'
        ) {
            $query = $this->filterByStockStatus($query);
        }

        $query = $this->setDocumentType($query);

        return  $query;
    }

    /**
     * Request query
     *
     * @param RequestInterface $request
     * @param array $query
     * @param bool $isSellerView
     *
     * @return QueryResponse
     * @throws Exception
     */
    private function requestQuery(RequestInterface $request, array $query, bool $isSellerView): QueryResponse
    {
        $client = $this->engine->getClient();
        $attempt   = 0;
        $response  = false;
        $exception = false;

        while ($attempt < 5 && $response === false) {
            $attempt++;

            try {
                $response = $client->search($query);
            } catch (Exception $e) {
                $exception = $e;
            }
        }

        if (!$response && $exception) {
            throw $exception;
        }

        if (filter_input(INPUT_GET, 'debug') === 'search') {
            var_dump($response);
        }
        if ($isSellerView) {
            $hits = $this->getHits($response);
        } else {
            $hits = $response['hits']['hits'] ?? [];
        }
        $hits = array_slice($hits, 0, $this->config->getResultsLimit());

        $documents = [];
        foreach ($hits as $idx => $doc) {
            $documents[] = [
                'id'        => $doc['_id'],
                'entity_id' => $doc['_id'],
                'score'     => $doc['_score'] + (count($hits) - $idx), #prevent randomize, if _score are same
                'data'      => $doc['_source'] ?? [],
            ];
        }

        return $this->responseFactory->create([
            'documents'    => $documents,
            'aggregations' => $this->aggregationBuilder->extract($request, $response),
            'total'        => count($documents),
        ]);
    }

    /**
     * Query
     *
     * @param RequestInterface $request
     * @return QueryResponse
     * @SuppressWarnings(PHPMD)
     * @throws Exception
     */
    public function query(RequestInterface $request): QueryResponse
    {
        $query = $this->getBaseQuery($request);

        $isSellerView = (bool) $this->searchFilter->getSellerView();
        if ($isSellerView) {
            $query = $this->setAggregations($query);
        }

        $shopIds = $this->searchFilter->getNewSellerViewIds();
        if ($shopIds) {
            $query = $this->addNewSellersFilter($query, $shopIds);
        }

        return $this->requestQuery($request, $query, $isSellerView);
    }

    /**
     * @param array $query
     *
     * @return array
     */
    private function filterByStockStatus(array $query): array
    {
        if ($this->config->isShowOutOfStock() == false) {
            $query['body']['query']['bool']['must'][] = [
                'term' => [
                    'is_in_stock_raw' => 1,
                ],
            ];
        }

        return $query;
    }

    /**
     * @param array $query
     *
     * @return array
     */
    private function setDocumentType(array $query): array
    {
        if ($this->engine->getDocumentType()) {
            $query['type'] = $this->engine->getDocumentType();
        }

        return $query;
    }

    /**
     * Set Aggregations
     *
     * @param array $query
     * @return array
     */
    private function setAggregations(array $query): array
    {
        $isSellerView = $this->searchFilter->getSellerView();
        if ($isSellerView) {
            $query['body']['aggregations']['sellers_items'] = [
                "terms"        => [
                    "field"        => "mirakl_shop_ids_raw",
                    "size"         => 999999
                ],
                "aggregations" => [
                    "products" => [
                        "terms" => [
                            "field" => "id",
                            "size"  => self::SELLER_PRODUCT_COUNT
                        ]
                    ]
                ]
            ];
        }

        return $query;
    }

    /**
     * Get Hits
     *
     * @param array $response
     * @return array
     */
    private function getHits(array $response): array
    {
        $hits = [];

        $allProductsIds = [];

        if (!empty($response['aggregations']['sellers_items'])) {
            $sellersItems = $response['aggregations']['sellers_items'];
            $buckets = !empty($sellersItems['buckets']) ? $sellersItems['buckets'] : [];
            $score = 10.0;

            foreach ($buckets as $item) {
                if (empty($item['products']['buckets'])) {
                    continue;
                }

                $products = $item['products']['buckets'];
                foreach ($products as $product) {
                    if (empty($product['key'])) {
                        continue;
                    }

                    if (!empty($allProductsIds[$product['key']])) {
                        continue;
                    }

                    $allProductsIds[$product['key']] = $product['key'];

                    $score -= 0.01;

                    $hits[] = [
                        "_type"  => "_doc",
                        "_id"    => $product['key'],
                        "_score" => $score
                    ];
                }
            }
        }

        return $hits;
    }

    /**
     * Add filtering by new seller IDs. Uses for top menu filters.
     *
     * @param array $query
     * @param array $shopIds
     *
     * @return array
     */
    private function addNewSellersFilter(array $query, array $shopIds): array
    {
        $mustConditions = $query['body']['query']['bool']['must'] ?? [];
        if ($mustConditions) {
            $query['body']['query']['bool']['must'][] = [
                'terms' => [
                    'mirakl_shop_ids_raw' => $shopIds,
                ],
            ];
        }

        return $query;
    }
}
