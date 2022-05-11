<?php
namespace Mirakl\Mci\Helper\Product\Import;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Url
{
    /**
     * @var ProductUrlRewriteGenerator
     */
    private $productUrlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @param   ProductUrlRewriteGenerator  $productUrlRewriteGenerator
     * @param   UrlPersistInterface         $urlPersist
     * @param   ProductRepositoryInterface  $productRepository
     * @param   ProductResourceFactory      $productResourceFactory
     */
    public function __construct(
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        UrlPersistInterface $urlPersist,
        ProductRepositoryInterface $productRepository,
        ProductResourceFactory $productResourceFactory
    ) {
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->productRepository = $productRepository;
        $this->productResource = $productResourceFactory->create();
    }

    /**
     * @param   Product $product
     */
    public function generateUrlKey(Product $product)
    {
        $modifyUrl = function ($urlKey) {
            return preg_match('/(.*)-(\d+)$/', $urlKey, $matches)
                ? $matches[1] . '-' . ($matches[2] + 1)
                : $urlKey . '-1';
        };

        $attribute = $this->productResource->getAttribute('url_key');

        while (!$attribute->getEntity()->checkAttributeUniqueValue($attribute, $product)) {
            $urlKey = $modifyUrl($product->getUrlKey());
            $product->setUrlKey($urlKey);
            $product->setUrlPath($urlKey);
        };
    }

    /**
     * @param   Product $product
     */
    public function deleteProductUrlRewrites($product)
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_ID     => $product->getId(),
            UrlRewrite::ENTITY_TYPE   => ProductUrlRewriteGenerator::ENTITY_TYPE,
            UrlRewrite::REDIRECT_TYPE => 0,
            UrlRewrite::STORE_ID      => $product->getStoreId(),
        ]);
    }

    /**
     * @param   int $productId
     * @param   int $storeId
     */
    public function refreshProductUrlRewrites($productId, $storeId)
    {
        /** @var Product $product */
        $product = $this->productRepository->getById($productId, false, $storeId);
        $product->setStoreId($storeId);

        $this->generateUrlKey($product);

        $this->deleteProductUrlRewrites($product);

        if ($product->isVisibleInSiteVisibility()) {
            try {
                $this->urlPersist->replace($this->productUrlRewriteGenerator->generate($product));
            } catch (\Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException $e) {
                // If URL rewrite is already used, make it unique and generate again
                $urlKey = $product->getUrlKey() . '-' . $product->getId();
                $product->setUrlKey($urlKey);
                $product->setUrlPath($urlKey);
                $this->urlPersist->replace($this->productUrlRewriteGenerator->generate($product));
            }
        }
    }
}
