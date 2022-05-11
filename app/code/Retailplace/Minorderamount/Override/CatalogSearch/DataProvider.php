<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Retailplace\Minorderamount\Override\CatalogSearch;

use Magento\Catalog\Model\Layer\Filter\Price\Range;
use Magento\Customer\Model\Indexer\CustomerGroupDimensionProvider;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Framework\Search\Adapter\Mysql\Aggregation\DataProviderInterface as MysqlDataProviderInterface;
use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Framework\Search\Dynamic\IntervalFactory;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;
use Magento\Store\Model\StoreManager;
use \Magento\Framework\Search\Request\IndexScopeResolverInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @deprecated 101.0.0
 * @see \Magento\CatalogSearch
 */
class DataProvider extends \Magento\CatalogSearch\Model\Adapter\Mysql\Dynamic\DataProvider
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var Range
     */
    private $range;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var MysqlDataProviderInterface
     */
    private $dataProvider;

    /**
     * @var IntervalFactory
     */
    private $intervalFactory;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var IndexScopeResolverInterface
     */
    private $priceTableResolver;

    /**
     * @var DimensionFactory|null
     */
    private $dimensionFactory;

    /**
     * @param ResourceConnection $resource
     * @param Range $range
     * @param Session $customerSession
     * @param MysqlDataProviderInterface $dataProvider
     * @param IntervalFactory $intervalFactory
     * @param StoreManager $storeManager
     * @param IndexScopeResolverInterface|null $priceTableResolver
     * @param DimensionFactory|null $dimensionFactory
     */
    public function __construct(
        ResourceConnection $resource,
        Range $range,
        Session $customerSession,
        MysqlDataProviderInterface $dataProvider,
        IntervalFactory $intervalFactory,
        StoreManager $storeManager = null,
        IndexScopeResolverInterface $priceTableResolver = null,
        DimensionFactory $dimensionFactory = null
    )
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->range = $range;
        $this->customerSession = $customerSession;
        $this->dataProvider = $dataProvider;
        $this->intervalFactory = $intervalFactory;
        parent::__construct(
            $resource,
            $range,
            $customerSession,
            $dataProvider,
            $intervalFactory,
            $storeManager,
            $priceTableResolver,
            $dimensionFactory
        );
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManager::class);
        $this->priceTableResolver = $priceTableResolver ?: ObjectManager::getInstance()->get(
            IndexScopeResolverInterface::class
        );
        $this->dimensionFactory = $dimensionFactory ?: ObjectManager::getInstance()->get(DimensionFactory::class);
    }


    /**
     * {@inheritdoc}
     */
    public function getAggregation(
        BucketInterface $bucket,
        array $dimensions,
        $range,
        \Magento\Framework\Search\Dynamic\EntityStorage $entityStorage
    )
    {
        if ($bucket->getField() == "min_order_amount") {
            $select = $this->dataProvider->getDataSet($bucket, $dimensions, $entityStorage->getSource());
            $mainTable = $select->getPart('from')?? "";
            $firstresult = [];
            if(isset($mainTable['main_table']['tableName']) ){
                $subSelect = clone $mainTable['main_table']['tableName'];
                $subSelect->where("main_table.value = 0");
                $firstSelect = $this->resource->getConnection()->select();
                $firstSelect->from(['main_table' => $subSelect], ['main_table.value']);

                $column = $firstSelect->getPart(Select::COLUMNS)[0];
                $firstSelect->reset(Select::COLUMNS);
                $rangeExpr = new \Zend_Db_Expr(
                    $this->connection->getIfNullSql(
                        $this->connection->quoteInto('FLOOR(' . $column[1] . ' / ? ) + 1', $range),
                        1
                    )
                );

                $firstSelect
                    ->columns(['range' => $rangeExpr])
                    ->columns(['metrix' => 'COUNT(*)'])
                    ->group('range')
                    ->order('range');
                $firstresult = $this->connection->fetchPairs($firstSelect);

                $mainTable['main_table']['tableName'] = $mainTable['main_table']['tableName']->where("main_table.value > 0");
                $select->setPart('from',$mainTable);
            }
            $column = $select->getPart(Select::COLUMNS)[0];
            $select->reset(Select::COLUMNS);
            $rangeExpr = new \Zend_Db_Expr(
                $this->connection->getIfNullSql(
                    $this->connection->quoteInto('FLOOR(' . $column[1] . ' / ? ) + 1', $range),
                    1
                )
            );

            $select
                ->columns(['range' => $rangeExpr])
                ->columns(['metrix' => 'COUNT(*)'])

                ->group('range')
                ->order('range');

            //$result = $this->connection->fetchPairs($select);
            if ($firstresult && isset($firstresult[1])) {
                $result = $this->connection->fetchPairs($select);
                $result[0] = $firstresult[1];
                ksort($result);
            } else {
                $result = $this->connection->fetchPairs($select);
            }
        } else {
            $select = $this->dataProvider->getDataSet($bucket, $dimensions, $entityStorage->getSource());
            $column = $select->getPart(Select::COLUMNS)[0];
            $select->reset(Select::COLUMNS);
            $rangeExpr = new \Zend_Db_Expr(
                $this->connection->getIfNullSql(
                    $this->connection->quoteInto('FLOOR(' . $column[1] . ' / ? ) + 1', $range),
                    1
                )
            );

            $select
                ->columns(['range' => $rangeExpr])
                ->columns(['metrix' => 'COUNT(*)'])
                ->group('range')
                ->order('range');
            $result = $this->connection->fetchPairs($select);

        }


        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareData($range, array $dbRanges)
    {
        $data = [];
        if (!empty($dbRanges)) {
            $lastIndex = array_keys($dbRanges);
            $lastIndex = $lastIndex[count($lastIndex) - 1];
            $flag = false;
            foreach ($dbRanges as $index => $count) {
                if ($index == 0) {
                    $data[] = [
                        'from' => 0,
                        'to' => 0.999,
                        'count' => $count,
                    ];
                    $flag = true;
                } elseif ($flag && $index == 1) {
                    $data[] = [
                        'from' => 1,
                        'to' => 99.99,
                        'count' => $count,
                    ];
                } else {
                    $fromPrice = $index == 1 ? '' : ($index - 1) * $range;
                    $toPrice = $index == $lastIndex ? '' : $index * $range;

                    $data[] = [
                        'from' => $fromPrice,
                        'to' => $toPrice,
                        'count' => $count,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * @return Select
     */
    private function getSelect()
    {
        return $this->connection->select();
    }
}
