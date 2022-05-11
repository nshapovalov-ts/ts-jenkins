<?php

namespace Retailplace\MiraklSeller\Plugin;

use Magento\Sales\Model\Order\Item;

class Order
{
    /**
     * Allow credit memo if at least one order item isn't refunded
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param $result
     * @return bool
     */
    public function aroundCanCreditmemo(
        \Magento\Sales\Model\Order $subject,
        $result
    ) {
        if ($subject->hasForcedCanCreditmemo()) {
            return $subject->getForcedCanCreditmemo();
        }

        if ($subject->canUnhold() || $subject->isPaymentReview() ||
            $subject->isCanceled() || $subject->getState() === \Magento\Sales\Model\Order::STATE_CLOSED) {
            return false;
        }

        /** @var Item $orderItem */
        foreach ($subject->getItems() as $orderItem) {
            if ($orderItem->canRefund()) {
                return true;
            }
        }

        return false;
    }
}
