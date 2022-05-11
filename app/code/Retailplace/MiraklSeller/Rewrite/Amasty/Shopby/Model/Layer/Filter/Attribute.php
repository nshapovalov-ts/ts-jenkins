<?php
/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Rewrite\Amasty\Shopby\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Search\Model\SearchEngine;
use Amasty\Shopby\Helper\FilterSetting;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Amasty\Shopby\Model\Layer\Filter\Traits\FilterTrait;
use Amasty\Shopby\Helper\Group as GroupHelper;
use Magento\Framework\Filter\StripTags as TagFilter;
use Magento\Catalog\Model\Layer\Filter\ItemFactory as FilterItemFactory;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\LocalizedException;
use Amasty\ShopbyBase\Helper\OptionSetting;
use Magento\Framework\Message\ManagerInterface;
use Amasty\Shopby\Model\Request;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Search\ResponseInterface;
use Amasty\Shopby\Model\Layer\Filter\Attribute as AttributeModel;
use Magento\Store\Model\ScopeInterface;

/**
 * Layer attribute filter
 */
class Attribute extends AttributeModel
{
    use FilterTrait;

    /**
     * @var TagFilter
     */
    private $tagFilter;

    /**
     * @var FilterSettingInterface
     */
    private $filterSetting;

    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @var  FilterSetting
     */
    private $settingHelper;

    /**
     * @var  ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Request
     */
    private $shopbyRequest;

    /**
     * @var GroupHelper
     */
    private $groupHelper;

    /**
     * @var OptionSetting
     */
    private $optionSettingHelper;

    /**
     * @var ManagerInterface
     */
    private $messageManager;


    /**
     * @param FilterItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param ItemDataBuilder $itemDataBuilder
     * @param TagFilter $tagFilter
     * @param SearchEngine $searchEngine
     * @param FilterSetting $settingHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Request $shopbyRequest
     * @param GroupHelper $groupHelper
     * @param OptionSetting $optionSettingHelper
     * @param ManagerInterface $messageManager
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        FilterItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        ItemDataBuilder $itemDataBuilder,
        TagFilter $tagFilter,
        SearchEngine $searchEngine,
        FilterSetting $settingHelper,
        ScopeConfigInterface $scopeConfig,
        Request $shopbyRequest,
        GroupHelper $groupHelper,
        OptionSetting $optionSettingHelper,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $searchEngine,
            $settingHelper,
            $scopeConfig,
            $shopbyRequest,
            $groupHelper,
            $optionSettingHelper,
            $messageManager,
            $data
        );
        $this->tagFilter = $tagFilter;
        $this->settingHelper = $settingHelper;
        $this->shopbyRequest = $shopbyRequest;
        $this->groupHelper = $groupHelper;
        $this->scopeConfig = $scopeConfig;
        $this->searchEngine = $searchEngine;
        $this->optionSettingHelper = $optionSettingHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * Apply attribute option filter to product collection.
     *
     * @param RequestInterface $request
     * @return $this
     * @throws LocalizedException
     */

    /**
     * @return bool
     */
    private function isMultiSelectAllowed()
    {
        return $this->getFilterSetting()->isMultiselect();
    }

    /**
     * @return FilterSettingInterface
     */
    protected function getFilterSetting()
    {
        if ($this->filterSetting === null) {
            $this->filterSetting = $this->settingHelper->getSettingByLayerFilter($this);
        }
        return $this->filterSetting;
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        return count($this->getItems());
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    public function sortOption($a, $b)
    {
        $pattern = '@^(\d+)@';
        if (preg_match($pattern, $a['label'], $ma) && preg_match($pattern, $b['label'], $mb)) {
            $r = $ma[1] - $mb[1];
            if ($r != 0) {
                return $r;
            }
        }

        return strcasecmp($a['label'], $b['label']);
    }

    /**
     * Get data array for building attribute filter items.
     *
     * @return array
     * @throws LocalizedException
     */
    protected function _getItemsData()
    {
        $selected = !!$this->shopbyRequest->getFilterParam($this);
        if ($selected && !$this->isVisibleWhenSelected()) {
            return [];
        }

        $options = $this->getOptions();
        $optionsFacetedData = $this->getOptionsFacetedData();

        if (!$optionsFacetedData) {
            return [];
        }

        $this->addItemsToDataBuilder($options, $optionsFacetedData);

        $itemsData = $this->getItemsFromDataBuilder();

        return $itemsData;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getOptions()
    {
        $attribute = $this->getAttributeModel();
        $options = $attribute->getFrontend()->getSelectOptions();

        if ($this->getFilterSetting()->hasAttributeGroups()) {
            /**
             * @var \Amasty\Shopby\Api\Data\GroupAttrInterface[] $groups
             */
            $groups = $this->getFilterSetting()->getAttributeGroups();
            $groupOptions = [];
            $allGroupedOptions = [];
            foreach ($groups as $group) {
                $groupOptions[] = [
                    'label' => $group->getName(),
                    'value' => $group->getGroupCode()
                ];
                if ($group->hasOptions()) {
                    foreach ($group->getOptions() as $option) {
                        $allGroupedOptions[] = $option->getOptionId();
                    }
                }
            }

            if (count($allGroupedOptions)) {
                foreach ($options as $key => $value) {
                    if (in_array($value['value'], $allGroupedOptions)) {
                        unset($options[$key]);
                    }
                }
            }

            $options = array_merge($groupOptions, $options);
        }

        if ($this->getFilterSetting()->getSortOptionsBy() == \Amasty\Shopby\Model\Source\SortOptionsBy::NAME) {
            usort($options, [$this, 'sortOption']);
        }

        $this->sortOptionsByFeatured($options);

        return $options;
    }

    /**
     * Additional Sort options by is_featured setting
     *
     * @param $options
     * @return array
     * @throws LocalizedException
     */
    public function sortOptionsByFeatured(&$options)
    {
        $attribute = $this->getAttributeModel();
        $filterCode = FilterSetting::ATTR_PREFIX . $attribute->getAttributeCode();
        $featuredOptionArray = [];
        $nonFeaturedOptionArray = [];
        $featuredOptions = $this->optionSettingHelper->getAllFeaturedOptionsArray();
        foreach ($options as $option) {
            if ($this->isOptionFeatured($featuredOptions, $filterCode, $option)) {
                $featuredOptionArray[] = $option;
            } else {
                $nonFeaturedOptionArray[] = $option;
            }
        }
        $options = array_merge($featuredOptionArray, $nonFeaturedOptionArray);
        if (count($featuredOptionArray)
            && count($nonFeaturedOptionArray)
            && !$this->filterSetting->getNumberUnfoldedOptions()
        ) {
            $this->filterSetting->setNumberUnfoldedOptions(count($featuredOptionArray));
        }

        return $options;
    }

    /**
     * @param array $options
     * @param string $filterCode
     * @param array $option
     * @return bool
     */
    private function isOptionFeatured($options, $filterCode, $option)
    {
        $isFeatured = false;
        if (isset($options[$filterCode][$option['value']][$this->getStoreId()])) {
            $isFeatured = (bool) $options[$filterCode][$option['value']][$this->getStoreId()];
        } elseif (isset($options[$filterCode][$option['value']][Store::DEFAULT_STORE_ID])) {
            $isFeatured = (bool) $options[$filterCode][$option['value']][Store::DEFAULT_STORE_ID];
        }

        return $isFeatured;
    }

    /**
     * @return array
     *
     * @throws LocalizedException
     */
    private function getOptionsFacetedData()
    {
        $optionsFacetedData = $this->generateOptionsFacetedData();
        $optionsFacetedData = $this->adjustFacetedDataToGroup($optionsFacetedData);

        if (count($optionsFacetedData)) {
            $optionsFacetedData = $this->convertOptionsFacetedData($optionsFacetedData);
        }

        return $optionsFacetedData;
    }

    /**
     * @return ResponseInterface|null
     *
     * @throws LocalizedException
     */
    private function getAlteredQueryResponse()
    {
        $alteredQueryResponse = null;

        if ($this->hasCurrentValue() && !$this->getFilterSetting()->isUseAndLogic()) {
            $requestBuilder = $this->getRequestBuilder();
            $queryRequest = $requestBuilder->create();
            $alteredQueryResponse = $this->searchEngine->search($queryRequest);
        }

        return $alteredQueryResponse;
    }

    /**
     * @param array $optionsFacetedData
     *
     * @return array
     * @throws LocalizedException
     */
    private function adjustFacetedDataToGroup(array $optionsFacetedData)
    {
        if (!$optionsFacetedData) {
            return $optionsFacetedData;
        }

        $groups = $this->groupHelper->getGroupsByAttributeId($this->getAttributeModel()->getId());

        foreach ($groups as $group) {
            $key = GroupHelper::LAST_POSSIBLE_OPTION_ID - $group->getId();

            if (isset($optionsFacetedData[$key])) {
                $code = $group->getGroupCode();
                $optionsFacetedData[$code] = $optionsFacetedData[$key];
                unset($optionsFacetedData[$key]);
            }
        }

        return $optionsFacetedData;
    }

    /**
     * @param array $options
     * @param array $optionsFacetedData
     *
     * @throws LocalizedException
     */
    private function addItemsToDataBuilder(array $options, array $optionsFacetedData)
    {
        if (!$options) {
            return;
        }
        foreach ($options as $option) {
            if (empty($option['value'])) {
                continue;
            }

            $isFilterableAttribute = $this->getAttributeIsFilterable($this->getAttributeModel());
            if (isset($optionsFacetedData[$option['value']])
                || $isFilterableAttribute != static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
            ) {
                $count = 0;
                if (isset($optionsFacetedData[$option['value']]['count'])) {
                    $count = $optionsFacetedData[$option['value']]['count'];
                }
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($option['label']),
                    $option['value'],
                    $count
                );
            }
        }
    }

    /**
     * Get items data according to attribute settings.
     * @return array
     */
    private function getItemsFromDataBuilder()
    {
        $itemsData = $this->itemDataBuilder->build();

        $isReducedEnabled = (int) $this->scopeConfig->getValue(
            'amshopby/general/is_checking_options_reduce',
            ScopeInterface::SCOPE_STORE
        );

        if ($isReducedEnabled && count($itemsData) == 1
            && !$this->isOptionReducesResults(
                $itemsData[0]['count'],
                $this->getLayer()->getProductCollection()->getSize()
            )
        ) {
            $itemsData = $this->getReducedItemsData($itemsData);
        }

        return $itemsData;
    }
}
