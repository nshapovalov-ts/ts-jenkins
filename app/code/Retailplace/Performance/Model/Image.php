<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Mirakl\Mci\Helper\Data as MciHelper;
use Magento\Eav\Model\Config as EavConfig;
use Psr\Log\LoggerInterface;

/**
 * Class for retrieval of all product images
 */
class Image
{
    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var Generator
     */
    private $batchQueryGenerator;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var \Mirakl\Mci\Helper\Data
     */
    private $mciHelper;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\DB\Query\Generator $generator
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param int $batchSize
     */
    public function __construct(
        Generator $generator,
        ResourceConnection $resourceConnection,
        MciHelper $mciHelper,
        EavConfig $eavConfig,
        LoggerInterface $logger,
        $batchSize = 100
    ) {
        $this->batchQueryGenerator = $generator;
        $this->resourceConnection = $resourceConnection;
        $this->connection = $this->resourceConnection->getConnection();
        $this->batchSize = $batchSize;
        $this->mciHelper = $mciHelper;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
    }

    /**
     * Returns product images
     *
     * @return \Generator
     */
    public function getAllProductImages(): \Generator
    {
        $batchSelectIterator = $this->batchQueryGenerator->generate(
            'value_id',
            $this->getVisibleImagesSelect(),
            $this->batchSize,
            \Magento\Framework\DB\Query\BatchIteratorInterface::NON_UNIQUE_FIELD_ITERATOR
        );

        foreach ($batchSelectIterator as $select) {
            foreach ($this->connection->fetchAll($select) as $key => $value) {
                yield $key => $value;
            }
        }
    }

    /**
     * Get the number of unique pictures of products
     *
     * @return int
     */
    public function getCountAllProductImages(): int
    {
        $select = $this->getVisibleImagesSelect()
            ->reset('columns')
            ->reset('distinct')
            ->columns(
                new \Zend_Db_Expr('count(distinct value)')
            );

        return (int) $this->connection->fetchOne($select);
    }

    /**
     * Return Select to fetch all products images
     *
     * @return Select
     */
    private function getVisibleImagesSelect(): Select
    {
        $selectStatusAttributeId = $this->connection->select()->from(
            ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
            'attribute_id'
        )->where(
            "attribute_code = 'status'"
        )->where(
            'entity_type_id = 4'
        );
        $selectEntityIds = $this->connection->fetchCol($this->connection->select()
            ->from(
                ['cpei' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                'entity_id'
            )->where(
                "attribute_id = ($selectStatusAttributeId)"
            )->where(
                "value = 1"
            ));
        $selectValueIds = $this->connection->fetchCol($this->connection->select()
            ->from(
                ['cpemgvte' => $this->resourceConnection->getTableName('catalog_product_entity_media_gallery_value_to_entity')],
                'value_id'
            )->where(
                "entity_id IN (?)",$selectEntityIds
            ));

        //$selectValueIds = 7;

        return $this->connection->select()->distinct()
            ->from(
                ['images' => $this->resourceConnection->getTableName(Gallery::GALLERY_TABLE)],
                ['value as filepath','value_id']
            )->where(
                'disabled = 0'
            )->where(
                'is_cached = 0'
            )->where(
                "value_id IN (?)",$selectValueIds
            );
    }

    /**
     * Get mirakl image status attributes
     *
     * @return array
     */
    public function getNonProcessedImageAttributes()
    {
        $result = [];
        $attributes = $this->mciHelper->getImagesAttributes();
        foreach ($attributes as $attribute) {
            try {
                $processedAttribute = $this->eavConfig->getAttribute(
                    Product::ENTITY,
                    'non_processed_' . $attribute->getAttributeCode()
                );

                if ($processedAttribute) {
                    $result[] = $processedAttribute;
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        return $result;
    }
}
