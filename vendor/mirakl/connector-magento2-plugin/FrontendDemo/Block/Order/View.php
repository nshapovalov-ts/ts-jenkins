<?php
namespace Mirakl\FrontendDemo\Block\Order;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\MMP\FrontOperator\Domain\Order\OrderLine;

class View extends Template
{
    /**
     * @var string
     */
    protected $_template = 'order/view.phtml';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   HttpContext $httpContext
     * @param   OrderHelper $orderHelper
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        HttpContext $httpContext,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $registry;
        $this->httpContext = $httpContext;
        $this->orderHelper = $orderHelper;
    }

    /**
     * Return back url for logged in and guest users
     *
     * @return  string
     */
    public function getBackUrl()
    {
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return $this->getUrl('sales/order/history');
        }

        return $this->getUrl('sales/order/form');
    }

    /**
     * Return back title for logged in and guest users
     *
     * @return  \Magento\Framework\Phrase
     */
    public function getBackTitle()
    {
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }

        return __('View Another Order');
    }

    /**
     * Retrieve current order model instance
     *
     * @return  Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @param   OrderLine   $orderLine
     * @return  \Magento\Sales\Model\Order\Item|null
     */
    private function getOrderLineItem(OrderLine $orderLine)
    {
        if ($orderLine->getOffer()->getId()) {
            foreach ($this->getOrder()->getAllItems() as $item) {
                if ($item->getMiraklOfferId() == $orderLine->getOffer()->getId()) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @param   OrderLine   $orderLine
     * @return  float
     */
    public function getOrderLinePrice(OrderLine $orderLine)
    {
        if ($item = $this->getOrderLineItem($orderLine)) {
            return $item->getPriceInclTax();
        }

        return $orderLine->getOffer()->getPrice();
    }

    /**
     * @param   OrderLine   $orderLine
     * @return  float
     */
    public function getOrderLinePriceExclTax(OrderLine $orderLine)
    {
        if ($item = $this->getOrderLineItem($orderLine)) {
            return $item->getPrice();
        }

        return $orderLine->getPrice();
    }

    /**
     * @param   OrderLine   $orderLine
     * @return  float
     */
    public function getOrderLineTotalPrice(OrderLine $orderLine)
    {
        if ($item = $this->getOrderLineItem($orderLine)) {
            return $item->getRowTotalInclTax();
        }

        return $orderLine->getPrice();
    }

    /**
     * @param   OrderLine   $orderLine
     * @return  float
     */
    public function getOrderLineTotalPriceExclTax(OrderLine $orderLine)
    {
        if ($item = $this->getOrderLineItem($orderLine)) {
            return $item->getRowTotal();
        }

        return $orderLine->getPrice();
    }

    /**
     * @return  \Mirakl\MMP\FrontOperator\Domain\Order
     */
    public function getMiraklOrder()
    {
        return $this->coreRegistry->registry('mirakl_order');
    }

    /**
     * @return  float
     */
    public function getOrderShippingPriceInclTax()
    {
        return $this->orderHelper->getMiraklShippingPriceInclTax($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderShippingPriceExclTax()
    {
        return $this->orderHelper->getMiraklShippingPriceExclTax($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderSubtotalPrice()
    {
        return $this->orderHelper->getMiraklSubtotalPrice($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderSubtotalPriceExclTax()
    {
        return $this->orderHelper->getMiraklSubtotalPriceExclTax($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderBaseTotalPrice()
    {
        return $this->orderHelper->getMiraklBaseTotalPrice($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderTotalPrice()
    {
        return $this->orderHelper->getMiraklTotalPrice($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderTotalTax()
    {
        return $this->orderHelper->getMiraklTotalTax($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * @return  float
     */
    public function getOrderTotalPriceExclTax()
    {
        return $this->orderHelper->getMiraklTotalPriceExclTax($this->getOrder(), $this->getMiraklOrder());
    }
}