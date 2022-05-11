<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\Evaluation\EvaluationCollection;
use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;
use Mirakl\MMP\FrontOperator\Request\Shop\GetShopEvaluationsRequest;
use Mirakl\MMP\FrontOperator\Request\Shop\GetShopsRequest;

class Shop extends ClientHelper\MMP
{
    /**
     * (S20) Fetches Mirakl shop list
     *
     * @param   \DateTime   $since
     * @param   bool        $paginate
     * @param   int         $offset
     * @param   int         $max
     * @return  ShopCollection
     */
    public function getShops(\DateTime $since = null, $paginate = false, $offset = 0, $max = 10)
    {
        $request = new GetShopsRequest();
        if ($since) {
            $request->setUpdatedSince($since);
        }

        $request->setPaginate($paginate);
        if (true === $paginate) {
            $request->setOffset($offset);
            $request->setMax($max);
        }

        $this->_eventManager->dispatch('mirakl_api_get_shops_before', [
            'request'  => $request,
            'since'    => $since,
            'paginate' => $paginate,
            'offset'   => $offset,
            'max'      => $max,
        ]);

        return $this->send($request);
    }

    /**
     * (S20) Fetches all Mirakl shop list with pagination
     *
     * @param   \DateTime   $since
     * @return  ShopCollection
     */
    public function getAllShops(\DateTime $since = null)
    {
        $offset = 0;
        $max = 100;
        $shops = [];
        while (true) {
            $result = $this->getShops($since, true, $offset, $max);
            $shops = array_merge($shops, $result->getItems());
            if (!$result->count() || count($shops) >= $result->getTotalCount()) {
                break;
            }
            $offset += $max;
        }

        return new ShopCollection($shops, count($shops));
    }

    /**
     * (S03) Fetches specified shop evaluations
     *
     * @param   string  $shopId
     * @param   int     $limit
     * @param   int     $offset
     * @param   string  $locale
     * @param   string  $sortBy
     * @param   string  $dir
     * @return  EvaluationCollection
     */
    public function getShopEvaluations(
        $shopId,
        $limit = 10,
        $offset = 0,
        $locale = null,
        $sortBy = GetShopEvaluationsRequest::SORT_BY_DATE,
        $dir = 'DESC'
    ) {
        $request = new GetShopEvaluationsRequest($shopId);
        $request->setMax($limit)
            ->setOffset($offset)
            ->setLocale($locale)
            ->setSortBy($sortBy)
            ->setDir($dir);

        $this->_eventManager->dispatch('mirakl_api_get_shop_evaluations_before', [
            'request' => $request,
        ]);

        return $this->send($request);
    }
}
