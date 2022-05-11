<?php
namespace Mirakl\Adminhtml\Ui\Component\Listing\Column\Order;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mirakl\Connector\Helper\Order as OrderHelper;

class Flag extends Column
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderResourceFactory
     */
    protected $orderResourceFactory;

    /**
     * @param   ContextInterface        $context
     * @param   UiComponentFactory      $uiComponentFactory
     * @param   OrderHelper             $orderHelper
     * @param   OrderFactory            $orderFactory
     * @param   OrderResourceFactory    $orderResourceFactory
     * @param   array                   $components
     * @param   array                   $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderHelper $orderHelper,
        OrderFactory $orderFactory,
        OrderResourceFactory $orderResourceFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->orderHelper = $orderHelper;
        $this->orderFactory = $orderFactory;
        $this->orderResourceFactory = $orderResourceFactory;
    }

    /**
     * @param   array   $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $order = $this->orderFactory->create();
                $this->orderResourceFactory->create()->load($order, $item['entity_id']);
                $item[$this->getData('name')] = $this->decorateFlag($order);
            }
        }

        return $dataSource;
    }

    /**
     * Handles decoration of the flag column
     *
     * @param   Order   $order
     * @return  string
     */
    public function decorateFlag($order)
    {
        $class = 'magento';
        $label = __('Operator');
        if ($this->orderHelper->isFullMiraklOrder($order)) {
            $class = 'marketplace';
            $label = __('Marketplace');
        } elseif ($this->orderHelper->isMiraklOrder($order)) {
            $class = 'magento marketplace';
            $label = __('Mixed');
        }

        return sprintf('<span class="%s">%s</span>', $class, $label);
    }
}