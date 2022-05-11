<?php

/**
 * Retailplace_MiraklMcm
 *
 * @copyright Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author    Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Retailplace\MiraklConnector\Setup\Patch\Data\AddBestsellerAttribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class BestSellerAttributeUpdater
 */
class BestSellerAttributeUpdater
{
    /**
     * Product attribute code
     */
    const TOP_PRODUCT_ATTRIBUTE = 'top_product';
    /**
     * Product attribute option label of top_product attribute
     */
    const BEST_SELLER_OPTION_LABEL = 'Best seller';
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * BestSellerAttributeUpdater constructor.
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResourceConnection $resourceConnection
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function updateBestSellerValues(): int
    {
        $bestSellerAttribute = $this->getAttribute(AddBestsellerAttribute::BEST_SELLER);
        $bestSellerOptionId = $this->getBestsellerOptionIdFromTopProductAttribute();
        $productSearchCriteria = $this->searchCriteriaBuilder
            ->addFilter(self::TOP_PRODUCT_ATTRIBUTE, $bestSellerOptionId, 'eq')
            ->create();
        $products = $this->productRepository->getList($productSearchCriteria);
        $insertData = [];
        foreach ($products->getItems() as $product) {
            $insertData[] = [
                'attribute_id' => $bestSellerAttribute->getAttributeId(),
                'store_id'     => Store::DEFAULT_STORE_ID,
                'entity_id'    => $product->getId(),
                'value'        => 1
            ];
        }
        $simpleProductEntityIds = array_column($insertData, 'entity_id');
        if ($simpleProductEntityIds) {
            $configurableProductCollection = $this->productCollectionFactory->create();
            $configurableProductCollection->addAttributeToFilter('type_id', Configurable::TYPE_CODE);
            $configurableProductCollection->setFlag('has_stock_status_filter', false);
            $configurableProductCollection->setFlag('has_shop_ids_filter', false);
            $configurableProductCollection->getSelect()
                ->join(
                    ['cpsl' => 'catalog_product_super_link'],
                    '`cpsl`.`parent_id` = `e`.`entity_id`',
                    ''
                )->join(
                    ['o' => $configurableProductCollection->getResource()->getTable('mirakl_offer')],
                    'o.entity_id = cpsl.product_id AND o.active = "true"',
                    []
                )->where("cpsl.product_id IN (?)", $simpleProductEntityIds)
                ->group('cpsl.parent_id');
            foreach ($configurableProductCollection as $configurableProduct) {
                $insertData[] = [
                    'attribute_id' => $bestSellerAttribute->getAttributeId(),
                    'store_id'     => Store::DEFAULT_STORE_ID,
                    'entity_id'    => $configurableProduct->getId(),
                    'value'        => 1
                ];
            }
        }

        $this->resourceConnection->getConnection()->delete(
            $bestSellerAttribute->getBackendTable(),
            ['attribute_id = ?' => $bestSellerAttribute->getAttributeId()]
        );
        if (count($insertData)) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $bestSellerAttribute->getBackendTable(),
                $insertData
            );
        }
        return count($insertData);
    }

    /**
     * @param string $attributeCode
     * @return ProductAttributeInterface
     * @throws NoSuchEntityException
     */
    public function getAttribute(string $attributeCode): ProductAttributeInterface
    {
        return $this->attributeRepository->get($attributeCode);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBestsellerOptionIdFromTopProductAttribute(): string
    {
        return $this->getAttribute(self::TOP_PRODUCT_ATTRIBUTE)->getSource()->getOptionId(self::BEST_SELLER_OPTION_LABEL);
    }
}
