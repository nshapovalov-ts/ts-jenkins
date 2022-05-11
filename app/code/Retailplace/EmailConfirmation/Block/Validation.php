<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Block;

use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Validation
 */
class Validation extends Template
{
    /** @var \Magento\Framework\Url\DecoderInterface */
    private $urlDecoder;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param array $data
     */
    public function __construct(
        Context $context,
        DecoderInterface $urlDecoder,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->urlDecoder = $urlDecoder;
    }

    /**
     * Get Email from Request
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->decodeUrlParam($this->getRequest()->getParam('email'));
    }

    /**
     * Get Referer Url from Request
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->_urlBuilder->getUrl('sign-up-page', [
            'referer' => $this->getRequest()->getParam('referer')
        ]);
    }

    /**
     * Decode URL Params
     *
     * @param string $param
     * @return string
     */
    private function decodeUrlParam(string $param): string
    {
        return $this->urlDecoder->decode($param);
    }
}
