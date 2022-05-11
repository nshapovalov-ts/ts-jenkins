<?php

namespace Retailplace\Search\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\Search\Dynamic\EntityStorage;
use Magento\Framework\Search\Request\BucketInterface;
use Mirasvit\SearchElastic\Adapter\DataProvider;
use Mirasvit\SearchElastic\Model\Config;
use Mirasvit\SearchElastic\Model\Engine;

class ElasticSearchDataProvider
{
    const ATTR_CODE_MIN_ORDER_AMOUNT = 'min_order_amount';
    const NO_MIN_ORDER_AMOUNT_MAX_LIMIT = 1;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var IndexScopeResolver
     */
    private $resolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var string
     */
    private $indexerId;

    /**
     * @param ResourceConnection $resource
     * @param IndexScopeResolver $resolver
     * @param Config $config
     * @param Engine $engine
     * @param string $indexerId
     */
    public function __construct(
        ResourceConnection $resource,
        IndexScopeResolver $resolver,
        Config $config,
        Engine $engine,
        $indexerId = 'catalogsearch_fulltext'
    ) {
        $this->resource = $resource;
        $this->resolver = $resolver;
        $this->config = $config;
        $this->engine = $engine;
        $this->indexerId = $indexerId;
    }

    /**
     * @param DataProvider $subject
     * @param array $result
     * @param BucketInterface $bucket
     * @param array $dimensions
     * @param int $range
     * @param EntityStorage $entityStorage
     * @return array
     */
    public function afterGetAggregation(
        DataProvider $subject,
        $result,
        BucketInterface $bucket,
        array $dimensions,
        $range,
        EntityStorage $entityStorage
    ) {
        $fieldName = $bucket->getField();
        if ($fieldName != self::ATTR_CODE_MIN_ORDER_AMOUNT) {
            return $result;
        }
        $indexName = $this->resolver->resolve($this->indexerId, $dimensions);

        $entityIds = $entityStorage->getSource();
        if (is_object($entityIds) && $entityIds instanceof \Magento\Framework\DB\Ddl\Table) {
            $select = $this->resource->getConnection()->select()
                ->from($entityIds->getName(), ['entity_id']);
            $entityIds = $this->resource->getConnection()->fetchCol($select);
        }

        $requestQuery = [
            'index' => $this->config->getIndexName($indexName),
            'body'  => [
                'stored_fields' => [
                    '_id',
                    '_score',
                ],
                'query'         => [
                    'bool' => [
                        'filter' => [
                            [
                                'terms' => [
                                    '_id' => $entityIds,
                                ],
                            ],
                        ],
                    ],
                ],
                'aggregations'  => [
                    'price_ranges' => [
                        'range' => [
                            'field'  => $fieldName . '_raw',
                            'ranges' => [
                                ['to' => self::NO_MIN_ORDER_AMOUNT_MAX_LIMIT],
                                ['from' => self::NO_MIN_ORDER_AMOUNT_MAX_LIMIT, 'to' => $range],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($this->engine->getDocumentType()) {
            $requestQuery['type'] = $this->engine->getDocumentType();
        }

        try {
            $queryResult = $this->engine->getClient()
                ->search($requestQuery);
            $key = 0;
            foreach ($queryResult['aggregations']['price_ranges']['buckets'] as $bucket) {
                $result[$key] = $bucket['doc_count'];
                $key++;
            }
        } catch (\Exception $e) {
        }

        foreach ($result as $key => $value) {
            if ($value == 0) {
                unset($result[$key]);
            }
        }
        ksort($result);
        return $result;
    }

    /**
     * @param DataProvider $subject
     * @param array $result
     * @param int $range
     * @param array $dbRanges
     * @return array
     */
    public function afterPrepareData(
        DataProvider $subject,
        $result,
        $range,
        array $dbRanges
    ) {
        if (isset($dbRanges[0])) {
            /** Update item ranges for no minimum order amount */
            if (isset($result[0])) {
                $result[0]['from'] = '';
                $result[0]['to'] = self::NO_MIN_ORDER_AMOUNT_MAX_LIMIT;
            }
            if (isset($result[1])) {
                $result[1]['from'] = self::NO_MIN_ORDER_AMOUNT_MAX_LIMIT;
                $result[1]['to'] = $range;
            }
        }
        return $result;
    }
}
