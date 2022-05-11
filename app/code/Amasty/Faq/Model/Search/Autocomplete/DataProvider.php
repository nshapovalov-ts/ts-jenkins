<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\Search\Autocomplete;

use Amasty\Faq\Model\ConfigProvider;
use Amasty\Faq\Model\ResourceModel\Question\Collection;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;

class DataProvider implements DataProviderInterface
{
    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Context
     */
    private $httpContext;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ItemFactory $itemFactory,
        Collection $collection,
        RequestInterface $request,
        Context $httpContext,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        ConfigProvider $configProvider
    ) {
        $this->collection = $collection;
        $this->itemFactory = $itemFactory;
        $this->request = $request;
        $this->httpContext = $httpContext;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $query = $this->request->getParam(QueryFactory::QUERY_VAR_NAME);
        $collection = $this->collection->getAutosuggestCollection($query);
        $collection->addFrontendFilters(
            (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH),
            $this->storeManager->getStore()->getId(),
            null,
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP)
        );
        $result = [];
        $urlKey = $this->configProvider->getUrlKey();
        foreach ($collection->getData() as $item) {
            $result[] = $this->itemFactory->create([
                'title' => $item['title'],
                'category' => $item['category'],
                'url' => $this->urlBuilder->getUrl($urlKey . '/' . $item['url_key'])
            ]);
        }

        return $result;
    }
}
