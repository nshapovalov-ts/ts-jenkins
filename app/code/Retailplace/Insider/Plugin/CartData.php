<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Plugin;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Retailplace\Insider\Model\BasketObjectProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class CartData
 */
class CartData
{
    /**
     * @var BasketObjectProvider
     */
    private $basketObjectProvider;

    /**
     * CartData constructor.
     *
     * @param BasketObjectProvider $basketObjectProvider
     */
    public function __construct(
        BasketObjectProvider $basketObjectProvider
    ) {
        $this->basketObjectProvider = $basketObjectProvider;
    }

    /**
     * add company members to customer data in local storage
     *
     * @param SectionSourceInterface $object
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(SectionSourceInterface $object, array $result): array
    {
        $result['insider_object'] = $this->basketObjectProvider->getConfig();

        return $result;
    }
}
