<?php

namespace Retailplace\Search\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetSearchableAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var EavConfig
     */
    protected $_eavConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param EavConfig $eavConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        EavConfig $eavConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->_eavConfig = $eavConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $searchableAttributes = $this->_getSearchableAttributes();

        $attributes = $this->_eavConfig->getEntityType(Product::ENTITY)
            ->getAttributeCollection();

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if (isset($searchableAttributes[$code])) {
                $eavSetup->updateAttribute(Product::ENTITY, $code, 'is_searchable', true);
                $eavSetup->updateAttribute(Product::ENTITY, $code, 'search_weight', $searchableAttributes[$code]);
            } else {
                if ($attribute->getIsSearchable()) {
                    $eavSetup->updateAttribute(Product::ENTITY, $code, 'is_searchable', false);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function _getSearchableAttributes()
    {
        return [
            'name'              => 10,
            'sku'               => 9,
            'short_description' => 8,
            'brand'             => 7,
            'description'       => 6,
            'size'              => 5,
            'colour'            => 4,
            'seo'               => 1,
            'mirakl_shop_ids'   => 1,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
