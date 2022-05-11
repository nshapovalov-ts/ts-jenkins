<?php
namespace Mirakl\Connector\Model\System\Config\Source\Order;

use Magento\Framework\Option\ArrayInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;

class Status implements ArrayInterface
{
    /**
     * @var OrderStatusCollectionFactory
     */
    private $orderStatusCollectionFactory;

    /**
     * @param   OrderStatusCollectionFactory    $orderStatusCollectionFactory
     */
    public function __construct(OrderStatusCollectionFactory $orderStatusCollectionFactory)
    {
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $statuses = $this->orderStatusCollectionFactory->create()->toOptionHash();

        $options = [];
        foreach ($statuses as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => sprintf('%s [%s]', $label, $code),
            ];
        }

        // Sort options by name
        uasort($options, function ($opt1, $opt2) {
            return strcmp($opt1['label'], $opt2['label']);
        });

        return $options;
    }
}