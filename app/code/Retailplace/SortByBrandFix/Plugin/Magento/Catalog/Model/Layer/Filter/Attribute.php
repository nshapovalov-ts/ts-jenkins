<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\SortByBrandFix\Plugin\Magento\Catalog\Model\Layer\Filter;

use Magento\Eav\Model\Cache\Type as CacheType;
use Magento\Eav\Model\Entity\Attribute\Source\BooleanFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Magento\Store\Model\StoreManagerInterface;

class Attribute
{
    /**
     * Default cache tags values
     * will be used if no values in the constructor provided
     * @var array
     */
    private static $defaultCacheTags = [CacheType::CACHE_TAG, \Magento\Eav\Model\Entity\Attribute::CACHE_TAG];

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $cacheTags;

    /**
     * Reference to the attribute instance
     *
     * @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     */
    protected $_attribute;

    /**
     * @var BooleanFactory
     */
    protected $_attrBooleanFactory;
    public function __construct(
        BooleanFactory $attrBooleanFactory,
        CacheInterface $cache = null,
        $storeResolver = null,
        array $cacheTags = null,
        StoreManagerInterface $storeManager = null,
        Serializer $serializer = null
    ) {
        $this->_attrBooleanFactory = $attrBooleanFactory;
        $this->cache = $cache ?: ObjectManager::getInstance()->get(CacheInterface::class);
        $this->cacheTags = $cacheTags ?: self::$defaultCacheTags;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
    }
    public function beforeGetItems(
        \Magento\Catalog\Model\Layer\Filter\Attribute $subject
    ) {

        $attribute = $subject->getAttributeModel();
        if($attribute->getAttributeCode() == 'brand'){
            $beforeCacheKey = 'before-attribute-navigation-option-' .$attribute->getAttributeCode() . '-' .$this->storeManager->getStore()->getId();
            $optionString = $this->cache->load($beforeCacheKey);
            if (false === $optionString) {
                $cacheKey = 'attribute-navigation-option-' .$attribute->getAttributeCode() . '-' .$this->storeManager->getStore()->getId();
                $options = $attribute->getSource()->getAllOptions();
                if($attribute->getAttributeCode() == 'brand'){
                    $filterOptions = [];
                    foreach ($options as $key => $row)
                    {
                        $filterOptions[$key] = $row['label'];
                    }
                    array_multisort($filterOptions, SORT_ASC, $options);
                }
                $this->cache->save(
                    $this->serializer->serialize($options),
                    $cacheKey,
                    $this->cacheTags
                );
                $this->cache->save(
                    $this->serializer->serialize($options),
                    $beforeCacheKey,
                    $this->cacheTags
                );
            }
        }
        //Your plugin code
        return [];
    }
}

