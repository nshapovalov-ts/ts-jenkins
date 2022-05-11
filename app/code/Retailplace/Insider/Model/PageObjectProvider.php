<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Retailplace\Insider\Api\InsiderObjectProviderInterface;
use Magento\Framework\App\RequestInterface;

/**
 * PageObjectProvider class
 */
class PageObjectProvider implements InsiderObjectProviderInterface
{
    /** @var RequestInterface */
    private $request;

    /**
     * PageObjectProvider constructor
     *
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        $config = [];
        if ($this->getPageType()) {
            $config = [
                'page' => [
                    'type' => $this->getPageType()
                ]
            ];
        }

        return $config;
    }

    /**
     * Get page type
     *
     * @return false|string
     */
    private function getPageType()
    {
        switch ($this->request->getFullActionName()) {
            case 'catalog_category_view':
                $result = 'Category';
                break;
            case 'catalog_product_view':
                $result = 'Product';
                break;
            case 'catalogsearch_result_index':
                $result = 'Search';
                break;
            case 'checkout_cart_index':
                $result = 'Basket';
                break;
            case 'checkout_index_index':
                $result = 'Checkout';
                break;
            case 'checkout_onepage_success':
                $result = 'Confirmation';
                break;
            case 'cms_index_index':
                $result = 'Home';
                break;
            case 'cms_page_view':
                $result = 'Content';
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }
}
