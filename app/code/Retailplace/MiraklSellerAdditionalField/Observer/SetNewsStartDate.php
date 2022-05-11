<?php

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Product;

/**
 *  Set value for News start and end date
 */
class SetNewsStartDate implements ObserverInterface
{
    const NEW_PRODUCT_DAYS = 30;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;
    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @param TimezoneInterface $localeDate
     * @param DateIntervalFactory $dateIntervalFactory
     */
    public function __construct(TimezoneInterface $localeDate, DateIntervalFactory $dateIntervalFactory)
    {
        $this->localeDate = $localeDate;
        $this->dateIntervalFactory = $dateIntervalFactory;
    }

    /**
     * Set the current date to news_from_date attribute if it's empty.
     * Set the current date to news_to_date attribute if it's empty.
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var  $product Product */
        $product = $observer->getEvent()->getProduct();

        if ($product->getId()) {
            return $this;
        }

        $product->setData('news_from_date', $this->localeDate->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s'));
        $interval = $this->dateIntervalFactory->create(['interval_spec' => 'P' . self::NEW_PRODUCT_DAYS . 'D']);
        $product->setData('news_to_date', $this->localeDate->date()->add($interval)->format('Y-m-d H:i:s'));

        return $this;
    }
}
