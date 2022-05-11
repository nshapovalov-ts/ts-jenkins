<?php
namespace Mirakl\Connector\Eav\Model\Entity\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\ResourceModel\Entity\AttributeFactory;
use Magento\Framework\DB\Ddl\Table;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory;

class Shop extends AbstractSource
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
     * @param   AttributeFactory    $eavAttrEntity
     * @param   CollectionFactory   $collectionFactory
     */
    public function __construct(AttributeFactory $eavAttrEntity, CollectionFactory $collectionFactory)
    {
        $this->eavAttrEntity = $eavAttrEntity;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get list of all available shops
     *
     * @param   bool    $withEmpty  Add empty option to array
     * @return  array
     */
    public function getAllOptions($withEmpty = true)
    {
        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $collection */
        $collection = $this->collectionFactory->create();
        $options = $collection->toOptionArray();

        if ($withEmpty) {
            array_unshift($options, [
                'value' => '',
                'label' => __('-- Please Select --')
            ]);
        }

        // Sort options by shop name
        usort($options, function ($opt1, $opt2) {
            return strcmp($opt1['label'], $opt2['label']);
        });

        return $options;
    }

    /**
     * Retrieve option values array by ids
     *
     * @param   string|array    $ids
     * @param   bool            $withEmpty  Add empty option to array
     * @return  array
     */
    public function getSpecificOptions($ids, $withEmpty = true)
    {
        $options = $this->collectionFactory->create()
            ->setOrder('name', 'asc')
            ->addFieldToFilter('main_table.id', ['in' => $ids])
            ->load()
            ->toOptionArray();

        if ($withEmpty) {
            array_unshift($options, ['label' => '', 'value' => '']);
        }

        return $options;
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
                'default'  => null,
                'extra'    => null,
                'type'     => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'Mirakl Shop Id',
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
