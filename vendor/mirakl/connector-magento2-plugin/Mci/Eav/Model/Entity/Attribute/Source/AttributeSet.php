<?php
namespace Mirakl\Mci\Eav\Model\Entity\Attribute\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\AttributeFactory;
use Magento\Framework\DB\Ddl\Table;

class AttributeSet extends AbstractSource implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var AttributeFactory
     */
    private $eavAttrEntity;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @param   AttributeFactory    $eavAttrEntity
     * @param   CollectionFactory   $collectionFactory
     * @param   EavConfig           $eavConfig
     */
    public function __construct(
        AttributeFactory $eavAttrEntity,
        CollectionFactory $collectionFactory,
        EavConfig $eavConfig
    ) {
        $this->eavAttrEntity = $eavAttrEntity;
        $this->collectionFactory = $collectionFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Get list of all available attribute sets
     *
     * @return  array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $collection */
            $collection = $this->collectionFactory->create();
            $collection->setEntityTypeFilter($this->eavConfig->getEntityType(Product::ENTITY)->getId());
            $this->_options = $collection->toOptionArray();

            array_unshift($this->_options, ['value' => '', 'label' => __('-- Please Select --')]);
        }

        return $this->_options;
    }

    /**
     * Retrieve flat column definition
     *
     * @return  array
     */
    public function getFlatColums()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();

        return [
            $attributeCode => [
                'unsigned' => true,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => 'Mirakl Attribute Set Id',
            ],
        ];
    }

    /**
     * Retrieve Select For Flat Attribute update
     *
     * @param   int $store
     * @return  \Magento\Framework\DB\Select|null
     */
    public function getFlatUpdateSelect($store)
    {
        return $this->eavAttrEntity->create()->getFlatUpdateSelect($this->getAttribute(), $store);
    }
}
