<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;

class SalesQuoteAfterSaveObserver implements ObserverInterface
{
    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @param QuoteUpdater $quoteUpdater
     */
    public function __construct(QuoteUpdater $quoteUpdater)
    {
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getQuote();

        $this->quoteUpdater->synchronize($quote);
    }
}
