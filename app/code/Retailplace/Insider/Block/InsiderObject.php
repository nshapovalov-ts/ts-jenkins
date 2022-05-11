<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Retailplace\Insider\Model\InsiderObjectProvider;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * InsiderObject class
 */
class InsiderObject extends Template
{
    /** @var InsiderObjectProvider */
    private $insiderProvider;

    /** @var Json */
    private $serializer;

    /**
     * InsiderObject constructor
     *
     * @param Context $context
     * @param InsiderObjectProvider $insiderProvider
     * @param Json $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        InsiderObjectProvider $insiderProvider,
        Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->insiderProvider = $insiderProvider;
        $this->serializer = $serializer;
    }

    /**
     * Get JS layout
     *
     * @return string
     */
    public function getJsLayout(): string
    {
        return $this->serializer->serialize($this->jsLayout);
    }

    /**
     * Get custom config
     *
     * @return array
     */
    public function getCustomConfig(): array
    {
        return $this->insiderProvider->getConfig();
    }

    /**
     * Json encode
     *
     * @param array $data
     * @return string
     */
    public function jsonSerialize(array $data): string
    {
        return $this->serializer->serialize($data);
    }
}
