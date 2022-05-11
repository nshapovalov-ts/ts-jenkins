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
namespace Eyemagine\HubSpot\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Class BeforeSaveNewsletterSubscriberObserver
 *
 * @package Eyemagine\HubSpot\Observer
 */
class BeforeSaveNewsletterSubscriberObserver implements ObserverInterface
{

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $subscriber = $observer->getSubscriber();
        $subscriber['change_status_at'] = (date("Y-m-d H:i:s", time()));
        
        return $this;
    }
}
