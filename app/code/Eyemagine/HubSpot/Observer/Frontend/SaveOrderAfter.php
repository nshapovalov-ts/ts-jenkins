<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Observer\Frontend;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\State as AppState;

class SaveOrderAfter implements ObserverInterface
{
    const AREA_ADMINHTML = \Magento\Framework\App\Area::AREA_ADMINHTML;

    /**
     * @var \Magento\Framework\App\State
    */
    protected $appState;

    /**
     * @var \Magento\Sales\Model\Order
    */
    protected $_orderModel;

    /**
     * @var \Magento\Framework\Event\ObserverInterface
    */
    protected $_objectManager;

    /**
     * @var \Psr\Log\LoggerInterface
    */
    private   $logger;

    /**
     * @param Context $context,
     * @param AppState $appState,
     * @param \Magento\Sales\Model\Order $orderModel,
     * @param \Psr\Log\LoggerInterface $logger
    */
    public function __construct(
        Context $context,
        AppState $appState,
        \Magento\Sales\Model\Order $orderModel,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_objectManager = $context->getObjectManager();
        $this->logger = $logger;
        $this->_orderModel = $orderModel;
        $this->appState = $appState;
    }


    public function execute(EventObserver $observer)
    {
        try{
            // get order ids
            $orderIds = $observer->getEvent()->getOrderIds();
            if($orderIds && !empty($orderIds) && $orderIds[0] != '') {
                // load again order
                $order = $this->_orderModel->load($orderIds[0]);
                // ignore cookie if admin store
                if ($this->appState->getAreaCode() != self::AREA_ADMINHTML) {
                    $utk = isset($_COOKIE['hubspotutk']) ? $_COOKIE['hubspotutk'] : null;
                    if (! empty($utk)) {
                        $order->setHubspotUserToken($utk);
                        $order->save();
                    }
                }
            }
        }catch (\Exception $e){
            $this->logger->info($e->getMessage());
        }
    }
}
