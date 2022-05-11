<?php
/**
 * Retailplace_CustomReports
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */

namespace Retailplace\CustomReports\Ui\Component\Listing\Columns;

use Magento\Framework\Url;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ProductFrontendUrl
 */
class ProductFrontendUrl extends Column
{
    /**
     * @var Url
     */
    private $urlBuilder;

    /** Url Path */
    const PRODUCT_URL_PATH_VIEW = 'catalog/product/view';

    /**
     * ProductFrontendUrl constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Url $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Url $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['url_key']) && $item['url_key']) {
                    $item[$name] = html_entity_decode('<a href="' . $this->urlBuilder->getDirectUrl($item['url_key'] . ".html") . '">' . __('Frontend Url') . '</a>');
                }
            }
        }
        return $dataSource;
    }
}
