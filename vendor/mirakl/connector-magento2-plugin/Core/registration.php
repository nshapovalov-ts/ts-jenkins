<?php
define('MIRAKL_BP', dirname(__DIR__));

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Mirakl_' . basename(__DIR__),
    __DIR__
);