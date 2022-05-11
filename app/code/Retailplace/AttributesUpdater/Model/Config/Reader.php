<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Config;

use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\ValidationStateInterface;

/**
 * Class Reader
 */
class Reader extends Filesystem
{
    /** @var string */
    public const CONFIG_FILE_NAME = 'attribute_updaters.xml';

    /**
     * List of paths to identifiable nodes
     *
     * @var array
     */
    protected $_idAttributes = [
        '/config/updaters/updater' => 'name'
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Config\FileResolverInterface $fileResolver
     * @param \Retailplace\AttributesUpdater\Model\Config\Converter $converter
     * @param \Retailplace\AttributesUpdater\Model\Config\SchemaLocator $schemaLocator
     * @param \Magento\Framework\Config\ValidationStateInterface $validationState
     * @param string $fileName
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = self::CONFIG_FILE_NAME
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $this->_idAttributes
        );
    }
}
