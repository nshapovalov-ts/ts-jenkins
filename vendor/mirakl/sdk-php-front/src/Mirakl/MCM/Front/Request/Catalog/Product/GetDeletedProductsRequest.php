<?php
namespace Mirakl\MCM\Front\Request\Catalog\Product;

use Mirakl\MCM\Front\Domain\Collection\Product\ProductDeletedCollection;
use Mirakl\MCM\FrontOperator\Request\Catalog\Product\AbstractGetDeletedProductsRequest;

/**
 * (CM61) Export deleted products
 *
 * Example:
 *
 * <code>
 * <?php
 * use Mirakl\MCM\Front\Client\FrontApiClient as MiraklApiClient;
 * use Mirakl\MCM\Front\Request\Catalog\Product\GetDeletedProductsRequest;
 *
 * // Environment parameters
 * $url = 'https://your.env/api';
 * $apiKey = '49936c2a-6b1a-4e0a-97c8-97bbf77630c0';
 *
 * try {
 * // Building request
 * $request = new GetDeletedProductsRequest();
 *
 * // Instantiating the Mirakl API Client
 * $api = new MiraklApiClient($url, $apiKey);
 *
 * // Set deleted since parameter date (optional)
 * $request->setDeletedFrom(new \DateTime('2020-11-26T11:14'));
 *
 * // Filter by product ids (optional)
 * $request->addProductId('8c7c87ae-7f65-464b-b83a-7283f48c6790');
 * $request->addProductId('bf1f0d61-919e-476b-939c-eddcff51a653');
 *
 * // Calling the API
 * $result = $api->exportDeletedProducts($request);
 *
 * // \Mirakl\MCM\Front\Domain\Collection\Product\ProductDeletedCollection
 * var_dump($result); // decorated response
 *
 * // You can also retrieve raw response by using run() method of API client:
 * $result = $api->run($request); // or $api->raw()->exportDeletedProducts($request)
 * //var_dump($result); // returns \Psr\Http\Message\ResponseInterface
 *
 * } catch (\Exception $e) {
 * // An exception is thrown if object requested is not found or if an error occurs
 * var_dump($e->getTraceAsString());
 * }
 * </code>
 */
class GetDeletedProductsRequest extends AbstractGetDeletedProductsRequest
{
    /**
     * @inheritdoc
     */
    public function getResponseDecorator()
    {
        return ProductDeletedCollection::decorator();
    }
}