<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Ui\Component\MiraklOrderListing\Grid\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class Action implements action column for MiraklOrder grid
 */
class Action extends Column
{
    /** Url path */
    public const ROW_DOWNLOAD_INVOICE_URL = 'retailplace_mirakl_order/order_files/getshippinginvoice';
    public const ROW_OPEN_ORDER_URL = 'sales/order/view';

    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['actual_shipping_uploaded']) && $item['actual_shipping_uploaded']) {
                    $item[$name]['download_shipping_invoice'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::ROW_DOWNLOAD_INVOICE_URL,
                            ['order_id' => $item['mirakl_order_id']]
                        ),
                        'label' => __('Download shipping invoice'),
                        'target' => 'blank'
                    ];
                }
            }
        }

        return $dataSource;
    }
}
