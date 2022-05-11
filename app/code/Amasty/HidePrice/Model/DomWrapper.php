<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_HidePrice
 */


namespace Amasty\HidePrice\Model;

class DomWrapper
{
    /**
     * @var null|\Laminas\Dom\Query|\Zend\Dom\Query|\Zend_Dom_Query
     */
    private $domQuery = null;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var string
     */
    private $executeMethod = 'query';

    /**
     * @param \DOMDocument $domDocument
     */
    public function setContent(\DOMDocument $domDocument)
    {
        if (class_exists(\Laminas\Dom\Query::class)) {
            $this->domQuery = new \Laminas\Dom\Query($domDocument->saveHTML());
            $this->executeMethod = 'execute';
            $this->initialized = true;
        } elseif (class_exists(\Zend\Dom\Query::class)) {
            $this->domQuery = new \Zend\Dom\Query($domDocument->saveHTML());
            $this->executeMethod = 'execute';
        } elseif (class_exists(\Zend_Dom_Query::class)) {
            $this->domQuery = new \Zend_Dom_Query($domDocument);
            $this->initialized = true;
        }
    }

    /**
     * @param string $selector
     *
     * @return \Laminas\Dom\NodeList|array
     */
    public function query($selector)
    {
        if ($this->domQuery) {
            $result = $this->domQuery->{$this->executeMethod}($selector);
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getDocument()
    {
        if ($this->domQuery) {
            /** @var string|\DOMDocument $document */
            $document = $this->domQuery->getDocument();
            if (!is_string($document)) {
                $document = $document->saveHTML();
            }
        } else {
            $document = '';
        }

        return $document;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }
}
