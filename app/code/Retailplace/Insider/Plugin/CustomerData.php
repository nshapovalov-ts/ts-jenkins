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
use Retailplace\Insider\Model\UserObjectProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class CustomerData
 */
class CustomerData
{
    /**
     * @var UserObjectProvider
     */
    private $userObjectProvider;

    /**
     * CustomerData constructor.
     *
     * @param UserObjectProvider $userObjectProvider
     */
    public function __construct(
        UserObjectProvider $userObjectProvider
    ) {
        $this->userObjectProvider = $userObjectProvider;
    }

    /**
     * Add company members to customer data in local storage
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
        $result['insider_object'] = $this->userObjectProvider->getConfig();

        return $result;
    }
}
