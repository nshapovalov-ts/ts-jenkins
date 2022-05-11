<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Plugin;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;

/**
 * Class QuoteActions
 */
class QuoteActions
{
    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Retailplace\MultiQuote\Model\QuoteResource */
    private $quoteResourceModel;

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * QuoteActions Constructor
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        QuoteResourceModel $quoteResourceModel,
        OfferRepositoryInterface $offerRepository,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->offerRepository = $offerRepository;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Sync Products with Child Quotes after adding to Parent Quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Product $product
     * @param $request
     * @param string|null $processMode
     * @return array
     */
    public function beforeAddProduct(Quote $quote, Product $product, $request = null, $processMode = AbstractType::PROCESS_MODE_FULL): array
    {
        $offerId = $this->request->getParam('offer_id');
        if ($offerId && !$quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID)) {
            try {
                $offer = $this->offerRepository->getById((int)$offerId);
            } catch (Exception $e) {
                $offer = null;
            }
            if ($offer && $offer->getShopId()) {
                $childQuote = $this->quoteFactory->create();
                $this->quoteResourceModel->loadByCustomerId($childQuote, $quote->getCustomerId(), $offer->getShopId(), true);
                if ($childQuote->getId()) {
                    try {
                        $childQuote->addProduct($product, $request, $processMode);
                        $childQuote->collectTotals();
                        $this->quoteResourceModel->save($childQuote);
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }

        return [$product, $request, $processMode];
    }
}
