<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


declare(strict_types=1);

namespace Amasty\Faq\Block\Lists;

use Amasty\Faq\Controller\RegistryRequestParamConstants;
use Amasty\Faq\Model\CategoryRepository;
use Amasty\Faq\Model\ConfigProvider;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class Pager extends \Magento\Theme\Block\Html\Pager
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var Registry
     */
    private $coreRegistry;

    public function __construct(
        Template\Context $context,
        ConfigProvider $configProvider,
        CategoryRepository $categoryRepository,
        Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
        $this->categoryRepository = $categoryRepository;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Rewrite getPageUrl to get correct URL with all rewrites since we doesn't use magento url_rewrite
     * Save only query and tag parameters and add page number
     */
    public function getPagerUrl($params = []): string
    {
        /**
         * Retrieve only FAQ search params (query, tag) from request.
         */
        $searchQueryParams = array_intersect_key(
            $this->_request->getParams(),
            array_flip(RegistryRequestParamConstants::FAQ_SEARCH_PARAMS)
        );
        $params = array_merge($params, $searchQueryParams);
        $urlRoute = $this->configProvider->getUrlKey() ? $this->getRoutePath() : '*/*';

        return $this->_urlBuilder->getUrl($urlRoute, ['_query' => $params]);
    }

    private function getRoutePath(): string
    {
        $category = $this->coreRegistry->registry('current_faq_category');
        $urlKey = $this->configProvider->getUrlKey();

        return $category ? $urlKey . '/' . $category->getUrlKey() : $urlKey . '/*';
    }
}
