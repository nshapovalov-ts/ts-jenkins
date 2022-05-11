<?php

/**
 * Retailplace_Variantsfix
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Variantsfix\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Serialize\Serializer\Json;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Retailplace\Offerdetail\Model\ConfigProvider as OfferConfigProvider;

class Configurable
{
    /**
     * @var ConfigurableHelper $configurableHelper
     */
    protected $configurableHelper;

    /**
     * @var ConfigurableAttributeData
     */
    protected $configurableAttributeData;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @var Data
     */
    private $priceHelper;

    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @var \Retailplace\MiraklFrontendDemo\Helper\Data
     */
    private $helper;

    /**
     * @var OfferConfigProvider
     */
    private $offerConfigProvider;

    /**
     * @param ConfigurableHelper $configurableHelper
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param Data $priceHelper
     * @param Json $serializer
     * @param OfferHelper $offerHelper
     * @param \Retailplace\MiraklFrontendDemo\Helper\Data $helper
     * @param OfferConfigProvider $offerConfigProvider
     */
    public function __construct(
        ConfigurableHelper $configurableHelper,
        ConfigurableAttributeData $configurableAttributeData,
        Data $priceHelper,
        Json $serializer,
        OfferHelper $offerHelper,
        \Retailplace\MiraklFrontendDemo\Helper\Data $helper,
        OfferConfigProvider $offerConfigProvider
    ) {
        $this->configurableHelper = $configurableHelper;
        $this->configurableAttributeData = $configurableAttributeData;
        $this->serializer = $serializer;
        $this->priceHelper = $priceHelper;
        $this->offerHelper = $offerHelper;
        $this->helper = $helper;
        $this->offerConfigProvider = $offerConfigProvider;
    }

    /**
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(\Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject, $result)
    {
        $config = $this->serializer->unserialize($result);
        $configurableProduct = $subject->getProduct();
        $config['configurable_description'] = $this->getProductAttributeValue($configurableProduct, 'description');
        /*$config['configurable_short_description'] =  $this->getProductAttributeValue($configurableProduct, 'short_description');*/
        foreach ($subject->getAllowProducts() as $simpleProduct) {
            $config['skus'][$simpleProduct->getId()] = $simpleProduct->getSku();
            $config['names'][$simpleProduct->getId()] = $simpleProduct->getName();
            $config['descriptions'][$simpleProduct->getId()] = $this->getProductAttributeValue($simpleProduct, 'description');
            /*$config['short_descriptions'][$simpleProduct->getId()] = $this->getProductAttributeValue($simpleProduct, 'short_description');*/
            $retailPrice = $this->getProductAttributeValue($simpleProduct, 'retail_price');
            $config['retail_price'][$simpleProduct->getId()] = $this->getFormattedPrice($retailPrice);
            $offer = $this->offerHelper->getBestOffer($simpleProduct);
            $config['lead_time_to_ship'][$simpleProduct->getId()] =
                $offer ? $offer->getLeadtimeToShip() : $this->offerConfigProvider->leadTimeDefaultValue();
            $config['margin'][$simpleProduct->getId()] = $this->helper->getCalculatedMargin($simpleProduct);
        }
        $config['defaultValues'] = $this->getDefaultValues($subject);
        return $this->serializer->serialize($config);
    }

    public function getProductAttributeValue($product, $attributeCode)
    {
        return $product->getResource()
            ->getAttributeRawValue($product->getId(), [$attributeCode], $product->getStoreId());
    }

    public function getFormattedPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject
     * @return array
     */
    protected function getDefaultValues(\Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject)
    {
        $defaultValues = [];
        $currentProduct = $subject->getProduct();
        $options = $this->configurableHelper->getOptions($currentProduct, $subject->getAllowProducts());
        $attributesData = $this->configurableAttributeData->getAttributesData($currentProduct, $options);

        if (!$currentProduct->hasPreconfiguredValues() || empty($attributesData['defaultValues'])) {
            $defaultSimpleProduct = null;
            $simpleProducts = $subject->getAllowProducts();
            foreach ($simpleProducts as $simpleProduct) {
                $defaultSimpleProduct = $simpleProduct;
                break;
            }

            if ($defaultSimpleProduct) {
                foreach ($attributesData['attributes'] as $attribute) {
                    $defaultValues[$attribute['id']] = $defaultSimpleProduct->getData($attribute['code']);
                }
            }
        } else {
            $defaultValues = $attributesData['defaultValues'];
        }

        return $defaultValues;
    }
}
