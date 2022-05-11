<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Config\Source\Customer;

use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Data\OptionSourceInterface;

class Attribute extends DataObject implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $attCollection;

    /**
     * Attribute constructor.
     * @param CollectionFactory $attCollection
     * @param array $data
     */
    public function __construct(
        CollectionFactory $attCollection,
        array $data = []
    ) {
        $this->attCollection = $attCollection;
        parent::__construct($data);
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $collection = $this->attCollection->create();
        /**
         * @var $item \Magento\Customer\Model\Attribute
         */
        foreach ($collection->getItems() as $item) {
            $options[] = [
                'value' => $item->getAttributeCode(),
                'label' => $item->getFrontendLabel()
            ];
        }
        return $options;
    }
}
