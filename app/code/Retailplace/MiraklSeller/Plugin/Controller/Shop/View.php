<?php
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Plugin\Controller\Shop;

use Magento\Framework\View\Result\Page;
use Magento\Framework\App\Action\Context;
use Mirakl\FrontendDemo\Controller\Shop\View as ShopView;
use Magento\Framework\App\RequestInterface;

/**
 * Class View
 */
class View
{

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Index constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->request = $context->getRequest();
    }

    /**
     * @param ShopView $subject
     * @param Page $resultPage
     *
     * @return Page
     */
    public function afterExecute(ShopView $subject, $resultPage)
    {
        $layout = $resultPage->getLayout();

        $uri = $this->request->getUri();
        $url = $uri->getScheme() . "://" . $uri->getHost() . $uri->getPath();
        $resultPage->getConfig()->addRemotePageAsset(
            $url,
            'link_rel',
            ['attributes' => ['rel' => 'canonical']]
        );

        $stateBlock = $layout->getBlock('amshopby.catalog.topnav.state');
        if (empty($stateBlock)) {
            $stateBlock = $layout->getBlock('catalogsearch.navigation.state');
        }

        if ($stateBlock) {
            $activeFilters = $stateBlock->getActiveFilters();
            if (!empty($activeFilters) && count($activeFilters) > 0) {
                $resultPage->getConfig()->setRobots('NOINDEX,NOFOLLOW');
            }
        }

        return $resultPage;
    }
}
