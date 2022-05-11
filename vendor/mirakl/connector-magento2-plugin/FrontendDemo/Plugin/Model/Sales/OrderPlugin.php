<?php
namespace Mirakl\FrontendDemo\Plugin\Model\Sales;

use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Mirakl\Connector\Helper\Order as OrderHelper;

class OrderPlugin
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var
     */
    protected $coreRegistry;

    /**
     * @param   OrderHelper $orderHelper
     * @param   Registry    $registry
     */
    public function __construct(OrderHelper $orderHelper, Registry $registry)
    {
        $this->orderHelper = $orderHelper;
        $this->coreRegistry = $registry;
    }

    /**
     * @param   Order       $subject
     * @param   \Closure    $proceed
     * @return  string
     */
    public function aroundGetShippingDescription(Order $subject, \Closure $proceed)
    {
        if ($this->coreRegistry->registry('current_invoice') || $this->coreRegistry->registry('current_shipment')) {
            return $proceed();
        }

        return $this->orderHelper->getShippingDescription($subject);
    }
}