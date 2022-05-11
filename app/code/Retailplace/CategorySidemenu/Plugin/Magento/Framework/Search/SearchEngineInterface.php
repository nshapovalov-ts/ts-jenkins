<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\CategorySidemenu\Plugin\Magento\Framework\Search;

use ReflectionClass;
use ReflectionException;
use Magento\Framework\App\Request\Http;
use Retailplace\CategorySidemenu\Helper\Data;

class SearchEngineInterface
{
    const CATEGORY_BUCKET = 'category_bucket';
    /**
     * @var Data
     */
    private $categorySidemenuHelper;

    /**
     * SearchEngineInterface constructor.
     * @param Data $categorySidemenuHelper
     */
    public function __construct(
        Data $categorySidemenuHelper
    ) {
        $this->categorySidemenuHelper = $categorySidemenuHelper;

    }

    /**
     * @param \Magento\Framework\Search\SearchEngineInterface $subject
     * @param $request
     * @return array
     */
    public function beforeSearch(
        \Magento\Framework\Search\SearchEngineInterface $subject,
        $request
    ) {
        if ($this->categorySidemenuHelper->isCategoryBucketDisabledOnCategoryPage()) {
            try {
                $request = $this->removeBucketsFromElasticsearchRequest($request, [self::CATEGORY_BUCKET]);
            } catch (ReflectionException $e) {
            }
        }
        //Your plugin code
        return [$request];
    }

    /**
     * @param $request
     * @param $removeBuckets
     * @return mixed
     * @throws ReflectionException
     */
    public function removeBucketsFromElasticsearchRequest(&$request, $removeBuckets)
    {
        $buckets = $request->getAggregation();
        foreach ($buckets as $key => $bucket) {
            if (in_array($bucket->getName(), $removeBuckets)) {
                unset($buckets[$key]);
            }
        }
        $reflection = new ReflectionClass($request);
        $bucketProperty = $reflection->getProperty('buckets');
        $bucketProperty->setAccessible(true);
        $bucketProperty->setValue($request, array_values($buckets));
        return $request;
    }
}
