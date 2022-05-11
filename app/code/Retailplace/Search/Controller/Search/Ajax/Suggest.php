<?php

namespace Retailplace\Search\Controller\Search\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Search\Model\QueryFactory;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory;

class Suggest extends \Magento\Search\Controller\Ajax\Suggest
{
    /**
     * Maximum results to display
     *
     * @var int
     */
    const MAX_RESULT_DISPLAY = 5;
    /**
     * @var Data
     */
    public $priceHelper;
    /**
     * Query factory
     *
     * @var QueryFactory
     */
    protected $_queryFactory;
    /**
     * Search helper
     *
     * @var SearchHelper
     */
    protected $_searchHelper;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * Autocomplete
     *
     * @var AutocompleteInterface
     */
    private $autocomplete;
    /**
     * @var CollectionFactory
     */
    private $_sellerCollectionFactory;

    /**
     * Initialize dependencies
     *
     * @param Context               $context
     * @param AutocompleteInterface $autocomplete
     * @param QueryFactory          $queryFactory
     * @param SearchHelper          $searchHelper
     * @param CollectionFactory     $sellerCollectionFactory
     * @param Data                  $priceHelper
     * @param UrlInterface          $urlBuilder
     */
    public function __construct(
        Context $context,
        AutocompleteInterface $autocomplete,
        QueryFactory $queryFactory,
        SearchHelper $searchHelper,
        CollectionFactory $sellerCollectionFactory,
        Data $priceHelper,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($context, $autocomplete);
        $this->autocomplete = $autocomplete;
        $this->_queryFactory = $queryFactory;
        $this->_searchHelper = $searchHelper;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->priceHelper = $priceHelper;
    }

    /**
     * Render results
     *
     * @return Json
     */
    public function execute()
    {
        $this->_view->loadLayout();
        if (!$this->getRequest()->getParam('q', false)) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_url->getBaseUrl());
            return $resultRedirect;
        }
        $searchQuery = $this->getRequest()->getParam('q');
        $query = $this->_queryFactory->get();

        $autocompleteData = $this->autocomplete->getItems();
        $responseData = [];
        $i = 0;
        foreach ($autocompleteData as $resultItem) {
            $response = $resultItem->toArray();
            /*$response['title'] = str_replace(
                $searchQuery,
                "<span class='highlight-query'>$searchQuery</span>",
                $response['title']
            );*/
            $responseData[] = $response;
            $i++;
            if ($i >= 5) {
                break;
            }
        }
        $sellerResult = $this->getSellerResults();
        $responseData = $this->_formatData($responseData, $query, $searchQuery, $sellerResult);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseData);
        return $resultJson;
    }

    public function getSellerResults()
    {
        $results = [];
        $searchQuery = $this->getRequest()->getParam('q');
        $sellerCollection = $this->_sellerCollectionFactory->create();
        $sellerCollection->addFieldToSelect('*');
        $sellerCollection->addFieldToFilter(['name','id'], [['like' => '%' . $searchQuery . '%'],['eq' =>  $searchQuery ]]);
        $sellerCollection->addFieldToFilter('state', ['eq' => 'open']);
        $sellerCollection->setPageSize(5);
        $sellerCollection = $sellerCollection->getData();
        foreach ($sellerCollection as $seller) {
            $minOrderAmount = $seller['min-order-amount'] ?? '';
            $minOrderAmount = $minOrderAmount > 0 ?
                $this->formatPrice($minOrderAmount). __(' Minimum Order Amount') :
                __('No Minimum Order Amount');
            $results[] = [
                'id' => $seller['id'],
                'name' => $seller['name'],
                'url' => $this->urlBuilder->getUrl(
                    'marketplace/shop/view',
                    [
                        'id' => $seller['id']
                    ]
                ),
                'logo' => $seller['logo'] ?? "",
                'min_order_amount' => $minOrderAmount,
            ];
        }
        return $results;
    }

    public function formatPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Format response data
     *
     * @param  $responseData
     * @param  Query        $query
     * @param  $searchQuery
     * @param  array        $seller
     * @return array
     */
    protected function _formatData($responseData, $query, $searchQuery, $seller = [])
    {
        return [
            'results' => $responseData,
            'sellers' => $seller,
            'query' => $searchQuery,
            'info' => [
                'size' => count($responseData),
                'url' => $this->_searchHelper->getResultUrl($query->getQueryText())
            ],
        ];
    }
}
