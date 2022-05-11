<?php
declare(strict_types=1);

/**
 * Retailplace_TopMenuFilter
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\TopMenuFilter\Block\Navigation;

use Magento\Catalog\Model\Layer\Resolver;
use Amasty\Shopby\Helper\FilterSetting;
use Amasty\ShopbyBase\Helper\Data as Basehelper;
use Amasty\Shopby\Helper\Data as ShopbyHelper;
use Amasty\Shopby\Helper\UrlBuilder;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Amasty\Shopby\Model\Source\DisplayMode;
use Magento\Framework\View\Element\Template\Context;
use Amasty\Shopby\Helper\Category;
use Amasty\Shopby\Api\Data\FromToFilterInterface;
use Amasty\Shopby\Block\Navigation\FilterRenderer as NavigationFilterRenderer;

/**
 * Class FilterRenderer
 */
class FilterRenderer extends NavigationFilterRenderer
{
    /**
     * FilterRenderer constructor.
     * @param Context $context
     * @param FilterSetting $settingHelper
     * @param UrlBuilder $urlBuilder
     * @param ShopbyHelper $helper
     * @param Category $categoryHelper
     * @param Resolver $resolver
     * @param Basehelper $baseHelper
     * @param array $data
     */
    public function __construct(Context $context, FilterSetting $settingHelper, UrlBuilder $urlBuilder, ShopbyHelper $helper, Category $categoryHelper, Resolver $resolver, Basehelper $baseHelper, array $data = [])
    {
        parent::__construct($context, $settingHelper, $urlBuilder, $helper, $categoryHelper, $resolver, $baseHelper, $data);
    }

    /**
     * Render
     *
     * @param FilterInterface $filter
     * @return string
     */
    public function render(FilterInterface $filter)
    {
        $this->filter = $filter;
        $setting = $this->settingHelper->getSettingByLayerFilter($filter);
        if ($setting->getDisplayMode() == DisplayMode::MODE_SLIDER) {
            $template = "Retailplace_TopMenuFilter::layer/filter_slider.phtml";
        } else {
            if ($setting->isMultiselect()) {
                $template = "Retailplace_TopMenuFilter::layer/filter_checkbox.phtml";
            } else {
                $template = "Retailplace_TopMenuFilter::layer/filter_radio.phtml";
            }
        }

        $this->setTemplate($template);
        $this->assign('filterSetting', $setting);
        if ($this->filter instanceof FromToFilterInterface) {
            $fromToConfig = $this->filter->getFromToConfig();
            $this->assign('fromToConfig', $fromToConfig);
        }

        $this->assign('filterItems', $filter->getItems());
        $html = $this->_toHtml();
        $this->assign('filterItems', []);
        $html = $html . $this->getTooltipHtml($setting) . $this->getShowMoreHtml($setting);
        return $html;
    }

    /**
     * Get From To Widget
     *
     * @param $type
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFromToWidget($type)
    {
        return $this->getLayout()->createBlock(
            \Amasty\Shopby\Block\Navigation\Widget\FromTo::class
        )
            ->setTemplate('Retailplace_TopMenuFilter::layer/widget/fromto.phtml')
            ->assign('filterSetting', $this->getFilterSetting())
            ->assign('fromToConfig', $this->filter->getFromToConfig())
            ->setSliderUrlTemplate($this->getSliderUrlTemplate())
            ->setFilter($this->filter)
            ->setWidgetType($type)
            ->toHtml();
    }
}
