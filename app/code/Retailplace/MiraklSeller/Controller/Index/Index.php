<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultInterface;

/**
 * Index Action class
 */
class Index extends Action
{
    /**
     * New products and suppliers pages URLs
     */
    public const NEW_SUPPLIERS_PAGE = 'new-suppliers';
    public const NEW_PRODUCTS_PAGE = 'new-products';

    /**
     * @var array
     */
    const CUSTOM_PAGES = ['boutique', 'clearance', 'madeinau', 'sale', 'seller-specials', self::NEW_PRODUCTS_PAGE, self::NEW_SUPPLIERS_PAGE];

    /**
     * @var PageFactory
     */
    protected $pageFactory;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->request = $context->getRequest();
        parent::__construct($context);
    }

    /**
     * Execute view action
     * @return ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $routeName = $this->request->getRouteName();

        //prepare product collection for page
        switch ($routeName) {
            case "sale":
                $title = __('Sales');
                break;
            case "madeinau":
                $title = __('Made In AU');
                break;
            case "clearance":
                $title = __('Clearance');
                break;
            case "au_post":
                $title = __('Australia Post Sellers');
                break;
            case "boutique":
                $title = __('Boutique Brands');
                break;
            case "seller-specials":
                $title = __('Seller Specials');
                break;
            case self::NEW_PRODUCTS_PAGE:
                $title = __('New Products');
                break;
            case self::NEW_SUPPLIERS_PAGE:
                $title = __('New Suppliers');
                break;
            default:
                $title = '';
        }

        $resultPage->getConfig()
            ->getTitle()
            ->set($title);

        if (in_array($routeName, self::CUSTOM_PAGES)) {
            $layout = $resultPage->getLayout();

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
        }

        return $resultPage;
    }
}
