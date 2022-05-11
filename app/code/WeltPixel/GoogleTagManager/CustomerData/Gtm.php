<?php

namespace WeltPixel\GoogleTagManager\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Gtm section
 */
class Gtm extends \Magento\Framework\DataObject implements SectionSourceInterface
{

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $catalogSession;

    /**
     * Constructor
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\SessionFactory $_checkoutSession,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \WeltPixel\GoogleTagManager\Helper\Data $gtmHelper,
        \Magento\Catalog\Model\Session $catalogSession,
        array $data = []
    )
    {
        parent::__construct($data);
        $this->jsonHelper = $jsonHelper;
        $this->_checkoutSession = $_checkoutSession->create();
        $this->customerSession = $customerSession->create();
        $this->catalogSession = $catalogSession;
        $this->gtmHelper = $gtmHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $data = [];

        /** AddToCart data verifications */
        if ($addToCartData = $this->_checkoutSession->getAddToCartData()) {
            $data[] = $addToCartData;
        }

        $this->_checkoutSession->setAddToCartData(null);

        /** RemoveFromCart data verifications */
        if ($removeFromCartData = $this->_checkoutSession->getRemoveFromCartData()) {
            $data[] = $removeFromCartData;
        }

        $this->_checkoutSession->setRemoveFromCartData(null);

        /** Checkout Steps data verifications */
        if ($checkoutOptionsData = $this->_checkoutSession->getCheckoutOptionsData()) {
            $checkoutOptions = $checkoutOptionsData;
            foreach ($checkoutOptions as $options) {
                $data[] = $options;
            }
        }
        $this->_checkoutSession->setCheckoutOptionsData(null);

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
