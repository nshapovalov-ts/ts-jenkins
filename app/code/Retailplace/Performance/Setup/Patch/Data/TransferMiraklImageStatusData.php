<?php

/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use Mirakl\Mci\Helper\Data as MciHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use \Magento\Framework\App\ResourceConnectionFactory;
use \Magento\Framework\App\ResourceConnection;
use Magento\Eav\Api\AttributeRepositoryInterface;
use \Exception;
use Psr\Log\LoggerInterface;
use Magento\Eav\Api\Data\AttributeInterface;

/**
 * Class TransferMiraklImageStatusData
 */
class TransferMiraklImageStatusData implements DataPatchInterface
{
    /** @var \Mirakl\Mci\Helper\Data */
    private $mciHelper;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /** @var \Magento\Framework\App\ResourceConnectionFactory */
    private $resourceConnectionFactory;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * TransferMiraklImageStatusData constructor
     *
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\App\ResourceConnectionFactory $resourceConnectionFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        MciHelper $mciHelper,
        ProductCollectionFactory $productCollectionFactory,
        ResourceConnectionFactory $resourceConnectionFactory,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface  $logger
    ) {
        $this->mciHelper = $mciHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceConnectionFactory = $resourceConnectionFactory;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $attributes = $this->mciHelper->getImagesAttributes();

        foreach ($attributes as $attribute) {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter([[
                'attribute' => $attribute->getAttributeCode(),
                'like'      => 'http%processed=false%',
            ]], null, 'left');

            $productIds = $productCollection->getColumnValues('entity_id');
            $processedAttribute = $this->getAttributeByCode('non_processed_' . $attribute->getAttributeCode());
            if ($processedAttribute && count($productIds)) {
                $insertData = [];
                foreach ($productIds as $productId) {
                    $insertData[] = [
                        'attribute_id' => $processedAttribute->getAttributeId(),
                        'store_id' => Store::DEFAULT_STORE_ID,
                        'entity_id' => $productId,
                        'value' => 1
                    ];
                }
                /** @var ResourceConnection $resourceConnection */
                $resourceConnection = $this->resourceConnectionFactory->create();
                $resourceConnection->getConnection()->insertOnDuplicate(
                    $processedAttribute->getBackendTable(),
                    $insertData
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [CreateMiraklImageStatusAttributes::class];
    }

    /**
     * Return attribute by code
     *
     * @param string $attributeCode
     * @return \Magento\Eav\Api\Data\AttributeInterface|null
     */
    private function getAttributeByCode(string $attributeCode): ?AttributeInterface
    {
        $attribute = null;
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                $attributeCode
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $attribute;
    }
}
