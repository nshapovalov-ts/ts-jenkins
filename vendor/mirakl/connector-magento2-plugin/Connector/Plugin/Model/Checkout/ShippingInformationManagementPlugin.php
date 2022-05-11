<?php
namespace Mirakl\Connector\Plugin\Model\Checkout;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Message\AbstractMessage;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\AddressFactory as QuoteAddressResourceFactory;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;

class ShippingInformationManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var QuoteAddressResourceFactory
     */
    protected $quoteAddressResourceFactory;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @param   CartRepositoryInterface     $quoteRepository
     * @param   QuoteHelper                 $quoteHelper
     * @param   QuoteAddressResourceFactory $quoteAddressResourceFactory
     * @param   QuoteUpdater                $quoteUpdater
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteHelper $quoteHelper,
        QuoteAddressResourceFactory $quoteAddressResourceFactory,
        QuoteUpdater $quoteUpdater
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteHelper = $quoteHelper;
        $this->quoteAddressResourceFactory = $quoteAddressResourceFactory;
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @param   ShippingInformationManagement   $subject
     * @param   \Closure                        $proceed
     * @param   int                             $cartId
     * @param   ShippingInformationInterface    $addressInformation
     * @return  PaymentDetailsInterface
     * @throws  StateException
     */
    public function aroundSaveAddressInformation(
        ShippingInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if ($shippingAddress = $addressInformation->getShippingAddress()) {
            $quote->setShippingAddress($shippingAddress);
            if ($extAttributes = $shippingAddress->getExtensionAttributes()) {
                parse_str($extAttributes->getAdditionalMethods(), $additionalMethods);
                if (isset($additionalMethods['shipping_method'])) {
                    $this->quoteUpdater->updateOffersShippingTypes($additionalMethods['shipping_method'], $quote);
                }
            }
        }

        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && !$shippingAddress->getShippingMethod()) {
            $shippingCarrierCode = $addressInformation->getShippingCarrierCode();
            $shippingMethodCode = $addressInformation->getShippingMethodCode();
            if (strlen($shippingCarrierCode) && strlen($shippingMethodCode)) {
                $shippingAddress->setShippingMethod($shippingCarrierCode . '_' . $shippingMethodCode);
                $this->quoteAddressResourceFactory->create()->save($shippingAddress);
            }
        }

        // Default Magento process
        $paymentDetails = $proceed($cartId, $addressInformation);

        if ($this->quoteHelper->isMiraklQuote($quote)) {
            // Verify that SH02 is still valid
            $this->quoteUpdater->synchronize($quote);

            if ($quote->getHasError()) {
                // Throw an exception if quote has been flagged as error
                $messages = $quote->getMessages();
                if (count($messages)) {
                    $message = current($messages);
                    if ($message instanceof AbstractMessage) {
                        $message = $message->getText();
                    }

                    throw new StateException(new Phrase((string) $message));
                }
            }
        }

        return $paymentDetails;
    }
}
