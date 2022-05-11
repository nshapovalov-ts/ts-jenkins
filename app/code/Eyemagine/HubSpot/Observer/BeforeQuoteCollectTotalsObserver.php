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
use Magento\Framework\App\State as AppState;

/**
 * Class BeforeSaveNewsletterSubscriberObserver
 *
 * @package Eyemagine\HubSpot\Observer
 */
class BeforeQuoteCollectTotalsObserver implements ObserverInterface
{

    const AREA_ADMINHTML = \Magento\Framework\App\Area::AREA_ADMINHTML;

    protected $appState;

    /**
     *
     * @param Item $item
     */
    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        
        // ignore cookie if admin store
        if ($this->appState->getAreaCode() != self::AREA_ADMINHTML) {
            $utk = isset($_COOKIE['hubspotutk']) ? $_COOKIE['hubspotutk'] : null;
            
            if (! empty($utk)) {
                $quote->setHubspotUserToken($utk);
            }
        }
        
        return $this;
    }
}
