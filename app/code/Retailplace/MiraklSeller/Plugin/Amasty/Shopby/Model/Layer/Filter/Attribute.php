<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin\Amasty\Shopby\Model\Layer\Filter;

use Amasty\Shopby\Model\Request;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

/**
 * Class Attribute
 */
class Attribute
{
    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @var Request
     */
    private $shopbyRequest;

    /**
     * @param SellerFilter $sellerFilter
     * @param Request $shopbyRequest
     */
    public function __construct(
        SellerFilter $sellerFilter,
        Request $shopbyRequest
    ) {
        $this->sellerFilter = $sellerFilter;
        $this->shopbyRequest = $shopbyRequest;
    }

    /**
     * @param \Amasty\Shopby\Model\Layer\Filter\Attribute $subject
     * @param \Amasty\Shopby\Model\Layer\Filter\Attribute $result
     * @param RequestInterface $request
     * @return \Amasty\Shopby\Model\Layer\Filter\Attribute
     * @throws LocalizedException
     */
    public function afterApply(
        \Amasty\Shopby\Model\Layer\Filter\Attribute $subject,
        \Amasty\Shopby\Model\Layer\Filter\Attribute $result,
        RequestInterface $request
    ): \Amasty\Shopby\Model\Layer\Filter\Attribute {
        $attribute = $subject->getAttributeModel();
        if ($attribute->getAttributeCode() !== 'mirakl_shop_ids') {
            return $result;
        }

        $requestedOptionsString = $this->shopbyRequest->getFilterParam($subject);
        if (empty($requestedOptionsString)) {
            return $result;
        }

        $optionValues = explode(',', $requestedOptionsString);
        $this->sellerFilter->setFilteredShopOptionIds($optionValues);

        return $result;
    }
}
