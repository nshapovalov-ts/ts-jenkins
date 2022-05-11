<?php

namespace Retailplace\Performance\Plugin;

class Url
{
    private $baseUrl;

    /**
     * @param \Magento\Framework\Url $subject
     * @param \Closure $proceed
     * @param array $params
     * @return string
     */
    public function aroundGetBaseUrl(
        \Magento\Framework\Url $subject,
        \Closure $proceed,
        $params = []
    ) {
        if (!empty($params)) {
            return $proceed($params);
        }
        if (!$this->baseUrl) {
            $this->baseUrl = $proceed($params);
        }

        return $this->baseUrl;
    }
}
