<?php
namespace Mirakl\FrontendDemo\Block\Message;

use Magento\Sales\Model\Order;
use Mirakl\MMP\FrontOperator\Domain\Collection\Reason\ReasonCollection;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class FormOrder extends AbstractForm
{
    /**
     * @var string
     */
    protected $_formTitle = 'Start a Conversation';

    /**
     * {@inheritdoc}
     */
    public function getFormAction()
    {
        return $this->getUrl('marketplace/order/postThread', [
            'order_id' => $this->getOrder()->getId(),
            'remote_id' => $this->getMiraklOrder()->getId()
        ]);
    }

    /**
     * @return  ReasonCollection
     */
    public function getReasons()
    {
        $locale = $this->coreConfig->getLocale();

        return $this->reasonApi->getOrderMessageReasons($locale);
    }

    /**
     * @return  Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return  MiraklOrder
     */
    public function getMiraklOrder()
    {
        return $this->coreRegistry->registry('mirakl_order');
    }

    /**
     * @return  bool
     */
    public function withFile()
    {
        return true;
    }
}
