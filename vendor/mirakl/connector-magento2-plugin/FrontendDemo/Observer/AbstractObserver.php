<?php
namespace Mirakl\FrontendDemo\Observer;

use Magento\Framework\Event\ObserverInterface;
use Mirakl\Api\Helper\Config as ApiConfig;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\FrontendDemo\Model\Quote\Updater as QuoteUpdater;

abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @var ApiConfig
     */
    protected $apiConfig;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @param   ApiConfig       $apiConfig
     * @param   QuoteHelper     $quoteHelper
     * @param   QuoteUpdater    $quoteUpdater
     */
    public function __construct(
        ApiConfig $apiConfig,
        QuoteHelper $quoteHelper,
        QuoteUpdater $quoteUpdater
    ) {
        $this->apiConfig = $apiConfig;
        $this->quoteHelper = $quoteHelper;
        $this->quoteUpdater = $quoteUpdater;
    }
}