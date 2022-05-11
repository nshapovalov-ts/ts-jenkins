<?php
namespace Mirakl\FrontendDemo\Block\Order;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\FrontendDemo\Helper\Order as OrderHelper;

class Link extends \Magento\Sales\Block\Order\Link
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @param   Context                 $context
     * @param   DefaultPathInterface    $defaultPath
     * @param   Registry                $registry
     * @param   OrderHelper             $orderHelper
     * @param   ConnectorConfig         $connectorConfig
     * @param   array                   $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        Registry $registry,
        OrderHelper $orderHelper,
        ConnectorConfig $connectorConfig,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $registry, $data);
        $this->orderHelper = $orderHelper;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * Retrieve current order model instance
     *
     * @return  \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        return $this->_registry->registry('current_order');
    }

    /**
     * Retrieve remote order model instance
     *
     * @return  \Mirakl\MMP\FrontOperator\Domain\Order
     */
    private function getMiraklOrder()
    {
        return $this->_registry->registry('mirakl_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getHref()
    {
        return $this->getUrl($this->getPath(), [
            'order_id' => $this->getOrder()->getId(),
            'remote_id' => $this->getMiraklOrder()->getId(),
        ]);
    }

    /**
     * @return  bool
     */
    public function hasEvaluation()
    {
        $miraklOrder = $this->getMiraklOrder();

        return $miraklOrder->getCanEvaluate() || $this->orderHelper->getOrderEvaluation($miraklOrder);
    }

    /**
     * @return  bool
     */
    public function hasShipments()
    {
        return $this->connectorConfig->isEnableMultiShipments();
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if ($this->hasKey()
            && method_exists($this, 'has' . $this->getKey())
            && !$this->{'has' . $this->getKey()}()
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
