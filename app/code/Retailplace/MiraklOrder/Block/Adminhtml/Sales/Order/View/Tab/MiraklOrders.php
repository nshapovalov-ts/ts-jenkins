<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay.tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Block\Adminhtml\Sales\Order\View\Tab;

use Magento\Framework\DataObject;
use Retailplace\MiraklOrder\Block\Adminhtml\Sales\Order\View\Tab\Column\DownloadInvoice;
use Retailplace\MiraklOrder\Model\MiraklOrderInfo;
use Mirakl\Adminhtml\Block\Sales\Order\View\Tab\MiraklOrders as CoreMiraklOrders;
use Mirakl\Adminhtml\Block\Widget\Grid\Column\Renderer\MiraklOrder\Action;
use Mirakl\MMP\Common\Domain\Order\OrderState;

/**
 * Class MiraklOrders implements grid for Mirakl Orders
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class MiraklOrders extends CoreMiraklOrders
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $collection = $this->getCollection();
        foreach ($collection->getItems() as $item) {
            $this->setOrderAdditionalData($item);
        }

        return $this;
    }

    /**
     * @param DataObject $item
     */
    private function setOrderAdditionalData(DataObject $item)
    {
        foreach ($item->getOrderAdditionalFields() as $additionalField) {
            if ($additionalField->getCode() == MiraklOrderInfo::MIRAKL_ACTUAL_SHIPPING_AMOUNT_FIELD) {
                $item->setData(MiraklOrderInfo::ACTUAL_SHIPPING_AMOUNT_FIELD, $additionalField->getValue());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn(MiraklOrderInfo::ACTUAL_SHIPPING_AMOUNT_FIELD, [
            'header'            => __('Actual Shipping Amount'),
            'index'             => MiraklOrderInfo::ACTUAL_SHIPPING_AMOUNT_FIELD,
            'type'              => 'currency',
            'currency'          => 'currency_iso_code',
            'rate'              => 1,
            'sortable'          => false
        ]);

        $this->addColumn('shipping_invoice_downloaded', [
            'header'            => __('Shipping Invoice Was Uploaded'),
            'index'             => 'shipping_invoice_downloaded',
            'type'              => 'currency',
            'currency'          => 'currency_iso_code',
            'rate'              => 1,
            'sortable'          => false,
            'align'             => 'center',
            'html_decorators' => ['nobr'],
            'renderer'        => DownloadInvoice::class,
        ]);

        $this->addColumn(
            'action',
            [
                'header'   => __('Action'),
                'align'    => 'center',
                'type'     => 'action',
                'getter'   => 'getId',
                'actions'  => [
                    [
                        'caption'  => __('Validate Order'),
                        'url'      => [
                            'base' => 'mirakl/order/validate/order_id/' . $this->getOrder()->getId(),
                        ],
                        'field'    => 'remote_id',
                        'confirm'  => __('Are you sure? This will impact all Mirakl orders of this list.'),
                        'statuses' => [OrderState::STAGING],
                        'type'     => 'order',
                    ],
                    [
                        'caption'  => __('Invalidate Order'),
                        'url'      => [
                            'base' => 'mirakl/order/invalidate/order_id/' . $this->getOrder()->getId(),
                        ],
                        'field'    => 'remote_id',
                        'confirm'  => __('Are you sure? This will impact all Mirakl orders of this list.'),
                        'statuses' => [OrderState::STAGING],
                        'type'     => 'order',
                    ],
                    [
                        'caption' => __('Validate Payment'),
                        'url'     => [
                            'base' => 'mirakl/payment/validate/order_id/' . $this->getOrder()->getId(),
                        ],
                        'field'   => 'remote_id',
                        'confirm' => __('Are you sure?'),
                        'type'    => 'payment',
                    ],
                    [
                        'caption' => __('Refuse Payment'),
                        'url'     => [
                            'base' => 'mirakl/payment/refuse/order_id/' . $this->getOrder()->getId(),
                        ],
                        'field'   => 'remote_id',
                        'confirm' => __('Are you sure?'),
                        'type'    => 'payment',
                    ],
                ],
                'filter'   => false,
                'sortable' => false,
                'renderer' => Action::class,
            ]
        );

        return $this;
    }
}
