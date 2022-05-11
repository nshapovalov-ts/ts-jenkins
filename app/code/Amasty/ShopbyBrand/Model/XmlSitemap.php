<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_ShopbyBrand
 */


declare(strict_types=1);

namespace Amasty\ShopbyBrand\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObjectFactory as ObjectFactory;

class XmlSitemap
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var ObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Amasty\ShopbyBrand\Helper\Data
     */
    private $helper;

    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Amasty\ShopbyBrand\Helper\Data $helper,
        ObjectFactory $dataObjectFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->helper = $helper;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @param string $attrCode
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeByCode($attrCode)
    {
        return $this->eavConfig->getAttribute(Product::ENTITY, $attrCode);
    }

    /**
     * @param $storeId
     * @param null $baseUrl
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBrandUrls($storeId, $baseUrl = null)
    {
        $result = [];

        foreach ($this->getBrandOptions() as $option) {
            if ($option['value']) {
                $url = $this->helper->getBrandUrl($option, $storeId);
                if ($baseUrl) {
                    $url = str_replace($baseUrl, '', $url);
                }

                $result[] = $this->dataObjectFactory->create()->setUrl($url);
            }
        }

        return $result;
    }

    /**
     * @return array|\Magento\Eav\Api\Data\AttributeOptionInterface[]|null
     */
    public function getBrandOptions()
    {
        $options = [];
        $attrCode = $this->helper->getBrandAttributeCode();
        if ($attrCode) {
            $brandAttribute = $this->getAttributeByCode($attrCode);
            $options = $brandAttribute->getOptions();
        }

        return $options;
    }
}
