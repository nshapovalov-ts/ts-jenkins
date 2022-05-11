<?php
namespace Mirakl\FrontendDemo\Observer;

use Magento\Framework\Event\Observer;

class UpdateShippingInfoObserver extends AbstractObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->apiConfig->isEnabled()) {
            /** @var \Magento\Framework\App\Request\Http $request */
            $request = $observer->getEvent()->getData('request');
            if ($request && $request->isGet()) {
                $this->quoteUpdater->synchronize($this->quoteHelper->getQuote());
            }
        }
    }
}
