<?php
namespace Mirakl\Core\Observer\Quote\Address;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddMiraklTaxesAppliedObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = $observer->getEvent()->getShippingAssignment();
        $address = $shippingAssignment->getShipping()->getAddress();

        if ($address->getAddressType() !== 'shipping') {
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        /** @var \Magento\Quote\Model\Quote\Address\Total $total */
        $total = $observer->getEvent()->getTotal();

        $appliedTaxes = $total->getAppliedTaxes() ?: [];
        $miraklCustomTaxes = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($customTaxApplied = unserialize($item->getMiraklCustomTaxApplied())) {
                $customTaxes = array_merge($customTaxApplied['taxes'], $customTaxApplied['shipping_taxes']);
                foreach ($customTaxes as $tax) {
                    $code = 'Marketplace ' . $tax['type'] . '-' . $tax['name'];
                    if (!isset($miraklCustomTaxes[$code])) {
                        $miraklCustomTaxes[$code] = [
                            'id'          => $code,
                            'amount'      => 0,
                            'base_amount' => 0,
                            'percent'     => '',
                            'rates'       => [
                                [
                                    'percent' => '',
                                    'code'    => $tax['type'],
                                    'title'   => sprintf('%s (%s)', $tax['name'], $tax['type']),
                                ],
                            ],
                            'item_id'     => $item->getId(),
                            'item_type'   => 'product',
                            'process'     => 0,
                        ];
                    }
                    $miraklCustomTaxes[$code]['amount'] += $tax['amount'];
                    $miraklCustomTaxes[$code]['base_amount'] += $tax['base_amount'];
                }
            } elseif ($miraklShippingTaxApplied = unserialize($item->getMiraklShippingTaxApplied())) {
                foreach ($miraklShippingTaxApplied as $miraklAppliedTax) {
                    if (isset($appliedTaxes[$miraklAppliedTax['id']])) {
                        $appliedTaxes[$miraklAppliedTax['id']]['amount'] += $miraklAppliedTax['amount'];
                        $appliedTaxes[$miraklAppliedTax['id']]['base_amount'] += $miraklAppliedTax['base_amount'];
                    } else {
                        $appliedTaxes[$miraklAppliedTax['id']] = $miraklAppliedTax;
                    }
                }
            }
        }

        if (!empty($miraklCustomTaxes)) {
            $total->setAppliedTaxes(array_merge($appliedTaxes, $miraklCustomTaxes));
        } else {
            $total->setAppliedTaxes($appliedTaxes);
        }
    }
}