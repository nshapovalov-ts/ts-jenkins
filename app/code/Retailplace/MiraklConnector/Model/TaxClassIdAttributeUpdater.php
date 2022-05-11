<?php
/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Psr\Log\LoggerInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Eav\Model\Config;
use Zend_Db_Select;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class AuPostAttributeUpdater
 */
class TaxClassIdAttributeUpdater
{
    /**
     * @var string
     */
    const ATTRIBUTE_CODE_GST_EXEMPT = 'gst_exempt';

    /**
     * @var string
     */
    const ATTRIBUTE_CODE_TAX_CLASS = 'tax_class_id';

    /**
     * @var CollectionFactory
     */
    private $productCollection;

    /**
     * @var ProductAction
     */
    private $productAction;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TaxClassManagementInterface
     */
    private $taxClassManagementInterface;

    /**
     * @var TaxClassKeyInterfaceFactory
     */
    private $taxClassKeyDataObjectFactory;

    /**
     * @var Config
     */
    private $eavConfig;

    /** @var AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var string|null
     */
    private $attributeGstExemptOptionId;

    /**
     * @var int|null
     */
    private $taxClassId;

    /**
     * AuPostAttributeUpdater constructor.
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CollectionFactory $collection
     * @param ProductAction $action
     * @param StoreManagerInterface $storeManager
     * @param TaxClassManagementInterface $taxClassManagementInterface
     * @param TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param Config $eavConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        CollectionFactory $collection,
        ProductAction $action,
        StoreManagerInterface $storeManager,
        TaxClassManagementInterface $taxClassManagementInterface,
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        Config $eavConfig,
        LoggerInterface $logger
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->productCollection = $collection;
        $this->productAction = $action;
        $this->storeManager = $storeManager;
        $this->taxClassManagementInterface = $taxClassManagementInterface;
        $this->taxClassKeyDataObjectFactory = $taxClassKeyDataObjectFactory;
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
    }

    /**
     * Update Attribute Values for Products
     *
     * @param string[] $skus
     * @throws NoSuchEntityException
     */
    public function update(array $skus = [])
    {
        $this->updateProductTaxClass($skus);
        $this->updateProductTaxClass($skus, false);
    }

    /**
     * Update Product Tax Class
     *
     * @param array $skus
     * @param bool $isSet
     * @throws NoSuchEntityException
     */
    private function updateProductTaxClass($skus = [], $isSet = true)
    {
        $updateAttributes = [];
        $storeId = $this->storeManager->getStore()->getId();

        $attributeGstExemptOptionId = $this->getGstExemptOptionId();

        $taxClassId = $this->getTaxClassId("Taxable Goods");
        $collection = $this->productCollection->create();

        $attributeTax = $this->getAttributeByCode(self::ATTRIBUTE_CODE_TAX_CLASS);
        $attributeGST = $this->getAttributeByCode(self::ATTRIBUTE_CODE_GST_EXEMPT);

        if (!empty($skus)) {
            $collection->addFieldToFilter('sku', ['in' => $skus]);
        }
        $select = $collection->getSelect();

        $select->joinLeft(
            ['attr_tax' => 'catalog_product_entity_int'],
            "attr_tax.entity_id = e.entity_id AND attr_tax.store_id = 0 AND attr_tax.attribute_id = "
            . $attributeTax->getAttributeId(),
            []
        );
        $select->joinLeft(
            ['attr_gst' => 'catalog_product_entity_int'],
            "attr_gst.entity_id = e.entity_id AND attr_gst.store_id = 0 AND attr_gst.attribute_id = "
            . $attributeGST->getAttributeId(),
            []
        );

        if ($isSet) {
            $select->where(
                "(attr_tax.value_id > 0 AND attr_tax.value != ?) OR attr_tax.value is null",
                $taxClassId
            );
            $select->where(
                "(attr_gst.value_id > 0 AND attr_gst.value != ?) OR attr_gst.value is null",
                $attributeGstExemptOptionId
            );

            $updateAttributes[self::ATTRIBUTE_CODE_TAX_CLASS] = $taxClassId;
        } else {
            $select->where("attr_tax.value_id > 0 AND attr_tax.value = ?", $taxClassId);
            $select->where("attr_gst.value_id > 0 AND attr_gst.value = ?", $attributeGstExemptOptionId);

            $updateAttributes[self::ATTRIBUTE_CODE_TAX_CLASS] = 0;
        }

        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(['id' => 'e.entity_id']);

        $ids = $collection->getConnection()->fetchCol($collection->getSelect());

        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 1000) as $chunkIds) {
            $this->productAction->updateAttributes($chunkIds, $updateAttributes, $storeId);
        }
    }

    /**
     * Get Attribute by Code
     *
     * @param string $attributeCode
     * @return AttributeInterface|null
     */
    private function getAttributeByCode(string $attributeCode): ?AttributeInterface
    {
        $attribute = null;
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                $attributeCode
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $attribute;
    }

    /**
     * Get Tax Class ID
     *
     * @param $className
     * @return string|null
     */
    public function getTaxClassId($className): ?string
    {
        if ($this->taxClassId !== null) {
            return $this->taxClassId;
        }

        return $this->taxClassId = $this->taxClassManagementInterface->getTaxClassId(
            $this->taxClassKeyDataObjectFactory->create()
                ->setType(TaxClassKeyInterface::TYPE_NAME)
                ->setValue($className)
        );
    }

    /**
     * Get Gst Exempt Option Id
     *
     * @return string|null
     */
    public function getGstExemptOptionId(): ?string
    {
        if ($this->attributeGstExemptOptionId !== null) {
            return $this->attributeGstExemptOptionId;
        }

        $attributeGstExempt = $this->getAttributeByCode(self::ATTRIBUTE_CODE_GST_EXEMPT);
        $attributeGstExemptOptionId = "";
        foreach ($attributeGstExempt->getOptions() as $option) {
            if ($option->getLabel() == 'Yes') {
                $attributeGstExemptOptionId = $option->getValue();
                break;
            }
        }

        return $this->attributeGstExemptOptionId = $attributeGstExemptOptionId;
    }
}
