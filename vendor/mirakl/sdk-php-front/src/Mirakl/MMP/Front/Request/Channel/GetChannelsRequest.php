<?php
namespace Mirakl\MMP\Front\Request\Channel;

use Mirakl\MMP\Common\Request\Channel\AbstractGetChannelsRequest;

/**
 * (CH11) List all enabled channels
 *
 * Example:
 *
 * <code>
 * use Mirakl\MMP\Front\Client\FrontApiClient;
 * use Mirakl\MMP\Front\Request\Channel\GetChannelsRequest;
 *
 * $api = new FrontApiClient('API_URL', 'API_KEY');
 *
 * $request = new GetChannelsRequest();
 * $request->setLocale('fr_FR');
 *
 * $result = $api->getChannels($request);
 * // $result => @see \Mirakl\MMP\Common\Domain\Collection\Channel\ChannelCollection
 *
 * </code>
 */
class GetChannelsRequest extends AbstractGetChannelsRequest
{}