<?php
namespace Mirakl\FrontendDemo\Block\Order;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\Core\Helper\Config as ConfigHelper;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class SalesList extends Template
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var OrderCollection
     */
    protected $orders = [];

    /**
     * @var string
     */
    protected $htmlClassName = 'default';

    /**
     * Constructor
     *
     * @param   Template\Context    $context
     * @param   ManagerInterface    $messageManager
     * @param   OrderHelper         $orderHelper
     * @param   ConfigHelper        $configHelper
     * @param   array               $data
     */
    public function __construct(
        Template\Context $context,
        ManagerInterface $messageManager,
        OrderHelper $orderHelper,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->messageManager = $messageManager;
        $this->orderHelper = $orderHelper;
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Adds remote orders information to specified Magento orders
     *
     * @return  $this
     */
    public function addMiraklOrdersToCollection()
    {
        try {
            if ($this->orders instanceof OrderCollection) {
                $this->orderHelper->addMiraklOrdersToCollection($this->orders);
            }
        } catch (\Exception $e) {
            $this->_logger->warning($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred. Please try again later or contact store owner if the problem persists.')
            );
        }
    
        return $this;
    }

    /**
     * Returns Magento store name if configured
     *
     * @return  string
     */
    public function getStoreName()
    {
        return $this->configHelper->getStoreName();
    }

    /**
     * Returns commercial orders
     *
     * @return  OrderCollection
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Sets commercial orders
     *
     * @param   OrderCollection $collection
     * @return  $this
     */
    public function setOrders(OrderCollection $collection)
    {
        $this->orders = $collection;

        return $this;
    }

    /**
     * Return main html class name
     *
     * @return  string
     */
    public function getHtmlClassName()
    {
        return $this->htmlClassName;
    }

    /**
     * Set main html class name
     *
     * @param   string  $htmlClassName
     * @return  $this
     */
    public function setHtmlClassName($htmlClassName)
    {
        $this->htmlClassName = $htmlClassName;

        return $this;
    }

    /**
     * Returns view URL of specified order
     *
     * @param   Order       $order
     * @param   MiraklOrder $miraklOrder
     * @return  string
     */
    public function getMiraklOrderViewUrl(Order $order, MiraklOrder $miraklOrder)
    {
        return $this->getUrl('marketplace/order/view', [
            'order_id' => $order->getId(),
            'remote_id' => $miraklOrder->getId()
        ]);
    }
}