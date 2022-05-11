<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Block\Adminhtml\Sales\Order\View\Tab\Column;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text;
use Retailplace\MiraklOrder\Model\MiraklOrderInfo;
use Magento\Backend\Block\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\DataObject;

/**
 * Class DownloadInvoice implements column for download actual shipping invoice
 */
class DownloadInvoice extends Text
{
    /** @var string */
    public const URL_PATH = 'retailplace_mirakl_order/order_files/getshippinginvoice';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var MiraklOrderInfo */
    private $miraklOrderInfo;

    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param MiraklOrderInfo $miraklOrderInfo
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        MiraklOrderInfo $miraklOrderInfo,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->miraklOrderInfo = $miraklOrderInfo;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(DataObject $row)
    {
        $url = $this->urlBuilder->getUrl(self::URL_PATH, ['order_id' => $row->getId()]);
        $urlLink = '<br><a href="'.$url.'">'.__('Download').'</a><br>';
        $status = $this->miraklOrderInfo->isActualShippingInvoiceUploaded($row->getId());
        $html = $status ? __('Yes') : __('No');
        $html .= $status ? $urlLink : '';
        return $html;
    }
}
