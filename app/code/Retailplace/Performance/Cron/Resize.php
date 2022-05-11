<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Performance\Cron;

class Resize
{
    protected $logger;
    protected $helper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Retailplace\Performance\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->helper->resizeProductImages();
        $this->logger->addInfo("Cronjob Resize is executed.");
    }
}
