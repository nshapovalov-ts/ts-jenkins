<?php
/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Search\Plugin;

use Magento\Framework\Search\Request\Binder as RequestBinder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;

/**
 * Class IsSalable
 */
class IsSalable
{
    /**
     * @type string
     */
    const ATTRIBUTE_NAME = 'am_is_salable';

    /**
     * @type string
     */
    const ATTRIBUTE_TYPE = 'integer';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * OnSale constructor.
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Session $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Bind data to request data
     *
     * @param RequestBinder $subject
     * @param array $data
     * @return array
     */
    public function afterBind(RequestBinder $subject, array $data): array
    {
        if (empty($data['filters']['am_is_salable_filter']['is_bind'])) {
            return $data;
        }

        if (empty($data['dimensions']['scope']['value'])) {
            return $data;
        }

        $storeId = $data['dimensions']['scope']['value'];
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $fieldName = self::ATTRIBUTE_NAME . '_' . $customerGroupId . '_' . $websiteId;
        $data['filters']['am_is_salable_filter']['field'] = $fieldName;

        return $data;
    }
}
