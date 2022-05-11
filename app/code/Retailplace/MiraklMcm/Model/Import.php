<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Store\Model\Store;
use Mirakl\Mci\Helper\Data as MciHelper;
use Retailplace\MiraklMcm\Helper\Data;

/**
 * Class Import
 */
class Import
{
    /**
     * @var string
     */
    const ATTRIBUTE_CODE_MIRAKL_MCM_VARIANT_GROUP_CODE = 'mirakl_mcm_variant_group_code';

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var MciHelper
     */
    private $mciHelper;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param ResourceConnection $resource
     * @param MciHelper $mciHelper
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        ResourceConnection $resource,
        MciHelper $mciHelper
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->connection = $resource->getConnection();
        $this->mciHelper = $mciHelper;
    }

    /**
     * Get Faulty Products
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFaultyProducts(): array
    {
        $variants = $this->mciHelper->getVariantAttributes();

        $attributes = [];
        $attributesDefaultOptions = [];
        $storeId = Store::DEFAULT_STORE_ID;

        foreach ($variants as $key => $attribute) {
            $attributeDefaultOptionId = $this->getAttributeDefaultOptionId($attribute);
            if (empty($attributeDefaultOptionId)) {
                continue;
            }
            $attributes[$key] = $attribute;
            $attributesDefaultOptions[$key] = $attributeDefaultOptionId;
        }

        $attributeMCMVariantGroupCode = $this->getAttribute(self::ATTRIBUTE_CODE_MIRAKL_MCM_VARIANT_GROUP_CODE);
        $MCMVariantGroupCodeAttributeId = $attributeMCMVariantGroupCode->getAttributeId();

        $select = $this->connection->select();
        $select->from(
            ['e' => $this->connection->getTableName('catalog_product_entity')],
            [
                'entity_id'       => 'e.entity_id',
                'sku'             => 'e.sku',
                'variant_group_code' => 'attr_variant_group_code.value',
                'simple_skus'     => 'GROUP_CONCAT(DISTINCT ce.sku)',
            ]
        );

        $having = [];

        $select->joinInner(
            ['cpr' => $this->connection->getTableName('catalog_product_relation')],
            'cpr.parent_id = e.entity_id',
            []
        );
        $select->joinInner(
            ['ce' => $this->connection->getTableName('catalog_product_entity')],
            'ce.entity_id = cpr.child_id',
            []
        );
        $select->joinInner(
            ['attr_variant_group_code' => $this->connection->getTableName('catalog_product_entity_varchar')],
            "attr_variant_group_code.entity_id = cpr.child_id AND attr_variant_group_code.attribute_id = $MCMVariantGroupCodeAttributeId AND attr_variant_group_code.store_id = $storeId AND attr_variant_group_code.value is not null",
            []
        );

        foreach ($attributes as $code => $attribute) {
            $attributeId = $attribute->getAttributeId();
            $attributeDefaultOptionId = $attributesDefaultOptions[$code];
            $attrTable = $attrCode = 'attr_' . $code;

            $select->joinLeft(
                [$attrTable => $this->connection->getTableName($attribute->getBackendTable())],
                "$attrTable.entity_id = cpr.child_id AND $attrTable.attribute_id = $attributeId AND $attrTable.store_id = $storeId",
                [
                    $attrCode => "MAX(IF(su.attribute_id = $attributeId, 1, 0))",
                    $code     => "MAX(IF($attrTable.value is not null AND $attrTable.value != '' AND $attrTable.value != $attributeDefaultOptionId, 1, 0))"
                ]
            );

            $having[] = "$attrCode != $code";
        }

        $select->joinLeft(
            ['su' => $this->connection->getTableName('catalog_product_super_attribute')],
            "su.product_id = e.entity_id",
            []
        );
        $select->where("e.type_id = 'configurable'");
        $select->group(["e.entity_id"]);
        $select->having(implode(' OR ', $having));

        return $this->connection->fetchAssoc($select);
    }

    /**
     * @param string $attributeCode
     * @return ProductAttributeInterface
     * @throws NoSuchEntityException
     */
    private function getAttribute(string $attributeCode): ProductAttributeInterface
    {
        return $this->attributeRepository->get($attributeCode);
    }

    /**
     * Get Attribute Default Option Id
     *
     * @param ProductAttributeInterface $attribute
     * @return mixed|null
     */
    private function getAttributeDefaultOptionId(ProductAttributeInterface $attribute)
    {
        if (!$attribute->usesSource()) {
            return null;
        }

        $attribute->setStoreId(Store::DEFAULT_STORE_ID);
        return $attribute->getSource()->getOptionId(Data::EMPTY_VALUE_PLACEHOLDER);
    }
}
