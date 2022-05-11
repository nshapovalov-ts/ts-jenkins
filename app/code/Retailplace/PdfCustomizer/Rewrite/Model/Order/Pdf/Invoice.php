<?php
/**
 * Retailplace_PdfCustomizer
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\PdfCustomizer\Rewrite\Model\Order\Pdf;

use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection;
use Retailplace\PdfCustomizer\Block\Invoice as BlockInvoice;
use Dompdf\DompdfFactory;
use Dompdf\Options;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Sales Order Invoice PDF model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Invoice
{

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var DompdfFactory
     */
    private $dompdfFactory;

    /**
     * @var
     */
    private $dompdf;
    /**
     * @var string
     */
    private $html;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param LayoutInterface $layout
     * @param DompdfFactory $dompdfFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        LayoutInterface $layout,
        DompdfFactory $dompdfFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_localeResolver = $localeResolver;
        $this->layout = $layout;
        $this->dompdfFactory = $dompdfFactory;
    }

    /**
     * Return PDF document
     *
     * @param array|Collection $invoices
     * @return Invoice
     */
    public function getPdf($invoices = []): Invoice
    {
        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                $this->_localeResolver->emulate($invoice->getStoreId());
                $this->_storeManager->setCurrentStore($invoice->getStoreId());
            }
            $order = $invoice->getOrder();

            $this->html = $this->layout->createBlock(BlockInvoice::class)
                ->setTemplate('Retailplace_PdfCustomizer::invoice.phtml')
                ->setInvoice($invoice)
                ->setOrder($order)
                ->setData('cache_lifetime', false)
                ->toHtml();

            $this->dompdf = $this->dompdfFactory->create();
            $options = new Options();
            $options->set('dpi', '135');
            $options->setIsHtml5ParserEnabled(true);
            $options->set('enable_remote', true);
            $this->dompdf->setOptions($options);
            $this->dompdf->loadHtml($this->html);
            $this->dompdf->setPaper('A4', 'portrait');
        }
        return $this;
    }

    /**
     * Render
     *
     * @return mixed
     */
    public function render()
    {
        // Render the HTML as PDF
        $this->dompdf->render();

        // Parameters
        $x = 505;
        $y = 790;
        $text = "{PAGE_NUM} of {PAGE_COUNT}";
        $font = $this->dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
        $size = 10;
        $color = array(0, 0, 0);
        $word_space = 0.0;
        $char_space = 0.0;
        $angle = 0.0;

        $this->dompdf->getCanvas()->page_text(
            $x,
            $y,
            $text,
            $font,
            $size,
            $color,
            $word_space,
            $char_space,
            $angle
        );

        return $this->dompdf->output();
    }

    /**
     * Get Html
     * @return string|null
     */
    public function getHtml(): ?string
    {
        return $this->html;
    }
}
