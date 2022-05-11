<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model;

use Magento\Framework\ObjectManagerInterface;
use Retailplace\AttributesUpdater\Model\Config\Data as ConfigData;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class UpdatersManagement
 */
class UpdatersManagement
{
    /** @var \Retailplace\AttributesUpdater\Model\Config\Data */
    private $updatersConfig;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * Constructor
     *
     * @param \Retailplace\AttributesUpdater\Model\Config\Data $updatersConfig
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        ConfigData $updatersConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->updatersConfig = $updatersConfig;
        $this->objectManager = $objectManager;
    }

    /**
     * Run Updaters
     *
     * @param array $names
     * @return array
     */
    public function getUpdaters(array $names = []): array
    {
        $updaters = [];
        $this->validateNames($names);
        foreach ($this->getUpdatersData() as $sortOrder => $updater) {
            if (!count($names) || in_array($updater['name'], $names)) {
                /** @var \Retailplace\AttributesUpdater\Api\UpdaterInterface $updaterInstance */
                $updaterInstance = $this->objectManager->create($updater['class']);
                $updaterInstance->setName($updater['name']);
                $updaters[] = $updaterInstance;
            }
        }

        return $updaters;
    }

    /**
     * Get list of Updater Names
     *
     * @return string[]
     */
    public function getNames(): array
    {
        $updaters = [];
        foreach ($this->getUpdatersData() as $sortOrder => $updater) {
            $updaters[] = sprintf('%-40s %s (%s)', $updater['name'], $updater['class'], $sortOrder);
        }

        return $updaters;
    }

    /**
     * Validate list of names from the command argument
     *
     * @param string[] $names
     */
    private function validateNames(array $names)
    {
        $updaterNames = [];
        foreach ($this->getUpdatersData() as $updater) {
            $updaterNames[] = $updater['name'];
        }
        foreach ($names as $name) {
            if (!in_array($name, $updaterNames)) {
                throw new InvalidArgumentException(sprintf('There are no updaters with name %s', $name));
            }
        }
    }

    /**
     * Get list of updaters from XML
     *
     * @return array
     */
    private function getUpdatersData(): array
    {
        $updaters = $this->updatersConfig->get('updaters');
        if (!$updaters) {
            $updaters = [];
        }
        ksort($updaters);

        return $updaters;
    }
}
