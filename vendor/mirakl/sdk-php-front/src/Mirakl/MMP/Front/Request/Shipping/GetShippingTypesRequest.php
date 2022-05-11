<?php
namespace Mirakl\MMP\Front\Request\Shipping;

use Mirakl\MMP\Common\Request\Shipping\AbstractGetShippingTypesRequest;

/**
 * (SH12) List all active shipping methods
 *
 * Example:
 *
 * <code>
 * use Mirakl\MMP\Front\Client\FrontApiClient;
 * use Mirakl\MMP\Front\Request\Shipping\GetShippingTypesRequest;
 *
 * $api = new FrontApiClient('API_URL', 'API_KEY');
 *
 * $request = new GetShippingTypesRequest();
 * $request->setLocale('fr_FR');
 *
 * $result = $api->getShippingTypes($request);
 * // $result => @see \Mirakl\MMP\Common\Domain\Collection\Shipping\ShippingTypeWithDescriptionCollection
 *
 * </code>
 */
class GetShippingTypesRequest extends AbstractGetShippingTypesRequest
{}