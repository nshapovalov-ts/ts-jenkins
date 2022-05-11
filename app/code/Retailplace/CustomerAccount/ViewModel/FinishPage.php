<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class FinishPage
 */
class FinishPage implements ArgumentInterface
{
    /** @var \Magento\Framework\Url\DecoderInterface */
    private $urlDecoder;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Magento\Framework\UrlInterface */
    private $urlBuilder;

    /**
     * FinishPage constructor.
     *
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        DecoderInterface $urlDecoder,
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->urlDecoder = $urlDecoder;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get Referer link to redirect after Signing Up
     *
     * @return string
     */
    public function getRefererUrl(): string
    {
        $url = $this->urlBuilder->getBaseUrl();
        $referer = $this->request->getParam('referer');
        if ($referer) {
            $url = $this->urlDecoder->decode($referer);
        }

        return $url;
    }
}
