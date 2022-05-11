<?php
namespace Mirakl\Mci\Model\System\Config\Source;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeModel;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class Attribute
{
    /**
     * @var AttributeCollection
     */
    private $allowedAttributes;

    /**
     * @var AttributeCollection
     */
    private $collection;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * Default excluded attribute codes
     *
     * @var array
     */
    protected $excludedAttributesRegexpArray = [
        'custom_layout',
        'custom_layout_update',
        'options_container',
        'custom_design.*',
        'page_layout',
        'tax_class_id',
        'is_recurring',
        'recurring_profile',
        'tier_price',
        'group_price',
        'mirakl_(?!image_\d+).*',
        'msrp.*',
        'price.*',
        'status',
        'visibility',
        'url_key',
        'special_from_date',
        'special_to_date',
        'news_from_date',
        'news_to_date',
    ];

    /**
     * Excluded attribute types
     *
     * @var array
     */
    protected $excludedTypes = ['price', 'gallery', 'hidden', 'multiline', 'media_image'];

    /**
     * @param   AttributeCollection     $collection
     * @param   EventManagerInterface   $eventManager
     */
    public function __construct(AttributeCollection $collection, EventManagerInterface $eventManager)
    {
        $this->collection = $collection;
        $this->eventManager = $eventManager;
    }

    /**
     * @param   AttributeModel  $attribute
     * @return  bool
     */
    private function isAttributeExportable(AttributeModel $attribute)
    {
        $exclAttrRegexp = sprintf('/^(%s)$/i', implode('|', $this->excludedAttributesRegexpArray));

        return $attribute->getFrontendLabel()
            && $attribute->getMiraklIsExportable()
            && !$attribute->isStatic()
            && !in_array($attribute->getData('frontend_input'), $this->excludedTypes)
            && !preg_match($exclAttrRegexp, $attribute->getAttributeCode());
    }

    /**
     * Retrieves exportable product attributes
     *
     * @return  AttributeCollection
     */
    public function getExportableAttributes()
    {
        if (null === $this->allowedAttributes) {
            $collection = $this->collection
                ->addVisibleFilter()
                ->setOrder('frontend_label', 'ASC');

            foreach ($collection as $key => $attribute) {
                /** @var AttributeModel $attribute */
                if (!$this->isAttributeExportable($attribute)) {
                    $collection->removeItemByKey($key);
                }
            }

            $this->eventManager->dispatch('mirakl_mci_exportable_attributes', ['attributes' => $collection]);

            $this->allowedAttributes = $collection;
        }

        return $this->allowedAttributes;
    }

    /**
     * Builds exportable attributes options
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getExportableAttributes() as $attribute) {
            /** @var AttributeModel $attribute */
            $options[] = [
                'value' => $attribute->getAttributeId(),
                'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
            ];
        }

        return $options;
    }
}
