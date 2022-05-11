<?php
declare(strict_types=1);

/**
 * Retailplace_Gtm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Gtm\Rewrite\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Json\Helper\Data as JsonData;

/**
 * Gtm section
 */
class Gtm extends DataObject implements SectionSourceInterface
{

    /**
     * @var JsonData
     */
    protected $jsonHelper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * Constructor
     * @param JsonData $jsonHelper
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        JsonData $jsonHelper,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($data);
        $this->jsonHelper = $jsonHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * Get Section Data
     *
     * @return array
     */
    public function getSectionData()
    {
        $data = [];

        /** Newsletter data verifications */
        if ($newsLetterData = $this->customerSession->getNewsLetterSubscriptionData()) {
            $data[] = $newsLetterData;
        }
        $this->customerSession->setNewsLetterSubscriptionData(null);

        /** Customer Registration data verifications */
        if ($customerRegistrationData = $this->customerSession->getCustomerAccountData()) {
            foreach ($customerRegistrationData as $dataLayer) {
                $data[] = $dataLayer;
            }
        }
        $this->customerSession->setCustomerAccountData(null);

        /** AddToCart data verifications */
        if ($addToCartData = $this->checkoutSession->getAddToCartData()) {
            $data[] = $addToCartData;
        }

        $this->checkoutSession->setAddToCartData(null);

        /** RemoveFromCart data verifications */
        if ($removeFromCartData = $this->checkoutSession->getRemoveFromCartData()) {
            $data[] = $removeFromCartData;
        }

        $this->checkoutSession->setRemoveFromCartData(null);

        /** Checkout Steps data verifications */
        if ($checkoutOptionsData = $this->checkoutSession->getCheckoutOptionsData()) {
            $checkoutOptions = $checkoutOptionsData;
            foreach ($checkoutOptions as $options) {
                $data[] = $options;
            }
        }
        $this->checkoutSession->setCheckoutOptionsData(null);

        /** Add To Wishlist Data */
        if ($addToWishListData = $this->customerSession->getAddToWishListData()) {
            $data[] = $addToWishListData;
        }
        $this->customerSession->setAddToWishListData(null);

        /** Add To Compare Data */
        if ($addToCompareData = $this->customerSession->getAddToCompareData()) {
            $data[] = $addToCompareData;
        }
        $this->customerSession->setAddToCompareData(null);

        return [
            'datalayer' => $this->jsonHelper->jsonEncode($data)
        ];
    }
}
