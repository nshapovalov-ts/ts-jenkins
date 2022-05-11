<?php
namespace Mirakl\FrontendDemo\Plugin\Model\Checkout;

use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Checkout\Model\TotalsInformationManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;

class TotalsInformationManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    /**
     * @param   CartRepositoryInterface $quoteRepository
     * @param   QuoteHelper             $quoteHelper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteHelper $quoteHelper
    ) {
        $this->cartRepository = $quoteRepository;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @param   TotalsInformationManagement $subject
     * @param   int                         $cartId
     * @param   TotalsInformationInterface  $addressInformation
     */
    public function beforeCalculate(
        TotalsInformationManagement $subject,
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->getActive($cartId);

        if (0 === strpos($addressInformation->getShippingCarrierCode(), 'marketplace_')) {
            list (, $offerId) = explode('_', $addressInformation->getShippingCarrierCode());
            if ($offerId) {
                $this->quoteHelper->updateOffersShippingTypes(
                    [$offerId => $addressInformation->getShippingMethodCode()], $quote, false, true
                );
            }
        }
    }
}
