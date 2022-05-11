<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Customer\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;

/**
 * Class Validator model
 */
class Validator
{

    const ALL_CUSTOMERS = 'all';
    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $ruleFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Validator constructor.
     * @param DateTime $date
     * @param Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param Registry $registry
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request,
        Registry $registry
    ) {
        $this->date = $date;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->ruleFactory = $ruleFactory;
        $this->productRepository =$productRepository;
        $this->request = $request;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $cmsRule
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isRestricted(\Magento\Framework\Model\AbstractModel $cmsRule)
    {
        $currentDate = $this->date->gmtDate('Y-m-d H:i:s');
        $currentDay = $this->date->gmtDate('Y-m-d');
        $currentTime = $this->date->gmtTimestamp();

        /* Check customer groups */
        $allowedGroupIds = trim( $cmsRule->getData('group_id'), ', ');

        if ($allowedGroupIds || 0 === $allowedGroupIds || '0' === $allowedGroupIds) {
            $allowedGroupIds = explode(',', $allowedGroupIds);
            if (!in_array(self::ALL_CUSTOMERS, $allowedGroupIds)
                && !in_array($this->customerSession->getCustomerGroupId(), $allowedGroupIds)
            ) {
                return true;

            }
        }

        /* Check Dates */
        $startDate = $cmsRule->getData('start_date');
        $finishDate = $cmsRule->getData('finish_date');
        if (!$this->isInTimeFrame($currentDate, $startDate, $finishDate)) {
            return true;
        }

        /* Check Times */
        $timeFrom = $cmsRule->getData('time_from');
        $timeTo = $cmsRule->getData('time_to');
        if (!$timeTo) {
            $timeTo = 86400;
        }

        if ($timeFrom == 86400) {
            $timeFrom = strtotime($currentDay);
        } else {
            $timeFrom = strtotime($currentDay) + $cmsRule->getData('time_from');
        }
        $timeTo = strtotime($currentDay) + $timeTo;

        if (!$this->isInTimeFrame($currentTime, $timeFrom, $timeTo)) {
            return true;
        }

        /* Check Day of the week */
        $daysOfWeeK = trim( $cmsRule->getData('days_of_week'), ', ');
        if ($daysOfWeeK) {
            $daysOfWeeK = explode(',', $daysOfWeeK);
            if (!$this->isDayOfWeek($daysOfWeeK)) {
                return true;
            }
        }

        if (!$this->isConditionsTrue($cmsRule)) {
            return true;
        }

        return false;
    }

    /**
     * @param $cmsRule
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isConditionsTrue($cmsRule)
    {
        $rule = $this->ruleFactory->create();
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getItemsQty() == $quote->getVirtualItemsQty()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }
        $address->setTotalQty($quote->getItemsQty());
        $rule->setData('conditions_serialized', $cmsRule->getData('conditions_serialized'));
        $product = $cmsRule->getProduct();
        if (!$product) {
            $product = $this->registry->registry('product');
        }

        if (!$product) {
            try {
                $productId = $this->request->getParam('product_id');
                if ($productId) {
                    $product = $this->productRepository->getById($productId);
                } else {
                    $product = false;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $product = false;
            }
        }
        $address = clone $address;
        if ($product && $product->getId()) {
            foreach ($product->getData() as $k => $v) {
                if (!$address->getData($k)) {
                    $address->setData($k, $v);
                }
                if ($k == 'quantity_and_stock_status') {
                    if ($v['is_in_stock'] == false) {
                        $address->setData('quantity_and_stock_status', 0);
                    } else {
                        $address->setData('quantity_and_stock_status', 1);
                    }
                }
            }
        }
        return $rule->validate($address);
    }

    /**
     * @param $current
     * @param $start
     * @param $finish
     * @return bool
     */
    public function isInTimeFrame($current, $start, $finish)
    {
        if ($start != $finish) {
            if ($start && $finish) {
                return ($current >= $start && $current <= $finish);
            } elseif ($start) {
                return ($start <= $current);
            } elseif ($finish) {
                return ($finish >= $current);
            }
        }
        return true;
    }

    /**
     * @param $daysOfWeek
     * @return bool
     */
    public function isDayOfWeek($daysOfWeek)
    {
        if (empty($daysOfWeek)) {
            return true;
        }

        if (in_array('all', $daysOfWeek)) {
            return true;
        } elseif (in_array($this->date->gmtDate('N'), $daysOfWeek)) {
            return true;
        }
        return false;
    }

    /**
     * @param $cmsRule
     * @return bool
     */
    public function hasDynamicConditions($cmsRule)
    {
        $keys = [
            'time_from', 'time_to', 'conditions_serialized',
        ];

        foreach ($keys as $key) {
            if ($value = $cmsRule->getData($key)) {
                if ($key == 'conditions_serialized') {
                    $value = @json_decode($value, true);
                    if (!empty($value['conditions'])) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }
        return false;
    }
}
