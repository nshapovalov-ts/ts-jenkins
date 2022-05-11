<?php
namespace Mirakl\FrontendDemo\Block\Message;

use Mirakl\MMP\Common\Domain\Reason\ReasonType;

class FormNew extends AbstractForm
{
    /**
     * @var string
     */
    protected $_formTitle = 'Start a Conversation';

    /**
     * @var string
     */
    protected $_reasonsLabel = 'Topic';

    /**
     * {@inheritdoc}
     */
    public function getFormAction()
    {
        $params = [];

        $order = $this->getOrder();

        if (!$order && ($thread = $this->getThread())) {
            $order = $this->messageHelper->getOrderFromThread($thread);
        }

        if ($order && $order->getId()) {
            $params['order_id'] = $order->getId();
            if ($miraklOrderId = $order->getMiraklOrderId()) {
                $params['remote_id'] = $miraklOrderId;
            }
        }

        return $this->getUrl('marketplace/order/postThread', $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getReasons()
    {
        $locale = $this->coreConfig->getLocale();

        if ($order = $this->getOrder()) {
            return $this->reasonApi->getOrderMessageReasons($locale);
        }

        $thread = $this->getThread();

        if ($thread && $thread->getEntities()->count() == 1) {
            switch ($thread->getEntities()->first()->getType()) {
                case 'MMP_ORDER':
                case 'MPS_ORDER':
                    return $this->reasonApi->getOrderMessageReasons($locale);

                case 'MMP_OFFER':
                case 'MPS_OFFER':
                    return $this->reasonApi->getTypeReasons(ReasonType::OFFER_MESSAGING, $locale);
            }
        }

        return [];
    }

    /**
     * @return  bool
     */
    public function withFile()
    {
        return true;
    }
}
