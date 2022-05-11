<?php
namespace Mirakl\Api\Helper;

use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Collection\Product\Offer\ProductWithOffersCollection;
use Mirakl\MMP\Common\Domain\Message\MessageCreated;
use Mirakl\MMP\Front\Domain\Collection\Offer\State\OfferStateCollection;
use Mirakl\MMP\Front\Domain\Offer\Message\CreateOfferMessage;
use Mirakl\MMP\Front\Request\Offer\GetOfferRequest;
use Mirakl\MMP\Front\Request\Offer\Message\CreateOfferMessageRequest;
use Mirakl\MMP\Front\Request\Offer\State\GetOfferStateListRequest;
use Mirakl\MMP\FrontOperator\Domain\Collection\Offer\ExportOfferCollection;
use Mirakl\MMP\FrontOperator\Request\Offer\OffersExportFileRequest;
use Mirakl\MMP\FrontOperator\Request\Offer\OffersExportRequest;
use Mirakl\MMP\FrontOperator\Request\Product\Offer\GetOffersOnProductsRequest;

class Offer extends ClientHelper\MMP
{
    /**
     * (OF22) Fetches offer by offer id
     *
     * @param   string  $offerId
     * @return  \Mirakl\MMP\FrontOperator\Domain\Offer
     */
    public function getOffer($offerId)
    {
        $request = new GetOfferRequest($offerId);

        return $this->send($request);
    }

    /**
     * (OF51) Fetches Mirakl offer list modified since the given datetime
     *
     * @param   \DateTime   $since
     * @return  ExportOfferCollection
     */
    public function getOffers(\DateTime $since = null)
    {
        $request = new OffersExportRequest();
        if ($since) {
            $request->setLastRequestDate($since);
        }

        $this->_eventManager->dispatch('mirakl_api_get_offers_before', [
            'request' => $request,
            'since'   => $since,
        ]);

        return $this->send($request);
    }

    /**
     * (OF51) Fetches Mirakl offer list (as file) modified since the given datetime
     *
     * @param   \DateTime   $since
     * @return  FileWrapper
     */
    public function getOffersFile(\DateTime $since = null)
    {
        $request = new OffersExportFileRequest();
        if ($since) {
            $request->setLastRequestDate($since);
        }

        $this->_eventManager->dispatch('mirakl_api_get_offers_file_before', [
            'request' => $request,
            'since'   => $since,
        ]);

        return $this->send($request);
    }

    /**
     * (P11) Fetches offers of specified product collection
     *
     * @param   array   $skus
     * @param   bool    $allOffers
     * @return  ProductWithOffersCollection
     */
    public function getOffersOnProducts(array $skus, $allOffers = false)
    {
        $offers = new ProductWithOffersCollection();

        if (!empty($skus)) {
            $request = new GetOffersOnProductsRequest(array_values($skus));
            $request->setAllOffers($allOffers);

            $this->_eventManager->dispatch('mirakl_api_get_products_offers_before', [
                'request' => $request,
                'skus'    => $skus,
            ]);

            $offers = $this->send($request);
        }

        return $offers;
    }

    /**
     * (OF61) Returns available offer states
     *
     * @return  OfferStateCollection
     */
    public function getStates()
    {
        $request = new GetOfferStateListRequest();

        return $this->send($request);
    }

    /**
     * (OF42) Send a message to a seller
     *
     * @param  string               $offerId
     * @param  CreateOfferMessage   $message
     * @return MessageCreated
     */
    public function createOfferMessage($offerId, CreateOfferMessage $message)
    {
        $request = new CreateOfferMessageRequest($offerId, $message);

        return $this->send($request);
    }
}
