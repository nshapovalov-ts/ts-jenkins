<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;

/**
 * Class SchemaLocator
 */
class SchemaLocator implements SchemaLocatorInterface
{
    /** @var string */
    const CONFIG_FILE_SCHEMA = 'attribute_updaters.xsd';

    /** @var string */
    protected $schema = null;

    /** @var string */
    protected $perFileSchema = null;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(Reader $moduleReader)
    {
        $configDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Retailplace_AttributesUpdater');
        $this->schema = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_FILE_SCHEMA;
        $this->perFileSchema = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_FILE_SCHEMA;
    }

    /**
     * Get path to merged config schema
     *
     * @return string
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * Get path to per file validation schema
     *
     * @return string
     */
    public function getPerFileSchema(): string
    {
        return $this->perFileSchema;
    }
}
