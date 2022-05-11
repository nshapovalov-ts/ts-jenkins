<?php
namespace Mirakl\Connector\Model\Quote;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Store\Model\StoreManagerInterface;

class Loader
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteResourceFactory
     */
    protected $quoteResourceFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param   Session                 $session
     * @param   CartRepositoryInterface $quoteRepository
     * @param   QuoteFactory            $quoteFactory
     * @param   QuoteResourceFactory    $quoteResourceFactory
     * @param   StoreManagerInterface   $storeManager
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteFactory $quoteFactory,
        QuoteResourceFactory $quoteResourceFactory,
        Session $session,
        StoreManagerInterface $storeManager
    ) {
        $this->quoteRepository      = $quoteRepository;
        $this->quoteFactory         = $quoteFactory;
        $this->quoteResourceFactory = $quoteResourceFactory;
        $this->checkoutSession      = $session;
        $this->storeManager         = $storeManager;
    }

    /**
     * @return  int
     */
    protected function getCurrentWebsiteId()
    {
        return $this->storeManager->getWebsite()->getId();
    }

    /**
     * @return  Quote
     */
    public function getQuote()
    {
        static $loading = false;

        if (!$loading) {
            $loading = true;
            try {
                $quote = $this->checkoutSession->getQuote();
            } catch (\LogicException $e) {
                // Avoid infinite loop after clicking add to cart
                $quote = $this->loadQuote();
            }
            $loading = false;
        } else {
            // Load quote manually to avoid potential infinite loop when using the default method
            $quote = $this->loadQuote();
        }

        return $quote;
    }

    /**
     * @return  Quote
     */
    protected function loadQuote()
    {
        $websiteId = $this->getCurrentWebsiteId();
        if ($quoteId = $this->checkoutSession->getData('quote_id_' . $websiteId)) {
            $quote = $this->quoteFactory->create();
            $this->quoteResourceFactory->create()->loadActive($quote, $quoteId);
        } elseif ($quoteId = $this->checkoutSession->getQuoteId()) {
            $quote = $this->quoteRepository->getActive($quoteId);
        } else {
            $quote = $this->quoteFactory->create();
        }

        return $quote;
    }
}
