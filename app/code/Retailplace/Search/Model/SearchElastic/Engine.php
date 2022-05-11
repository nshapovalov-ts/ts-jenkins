<?php

/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\Search\Model\SearchElastic;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Mirasvit\SearchElastic\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Class Engine
 */
class Engine extends \Mirasvit\SearchElastic\Model\Engine
{
    const FULLTEXT_MAX_TERMS_COUNT = 262144;

    /** @var string */
    private const XML_SLOW_LOG_SECTION_PATH = "search/slow_log/";

    /** @var string */
    private const XML_SLOW_LOG_ENABLED = "search/slow_log/slow_log_enable";

    /** @var string */
    private const XML_SLOW_LOG_CHARACTERS_AMOUNT = "index.indexing.slowlog.source";

    /** @var string[] */
    private const XML_SLOW_LOG_LEVEL_TYPE = [
        "index.search.slowlog.level",
        "index.indexing.slowlog.level"
    ];

    /** @var string[] */
    private const XML_SLOW_LOG_TIMING_CONFIG = [
        "index.search.slowlog.threshold.query.warn",
        "index.search.slowlog.threshold.query.info",
        "index.search.slowlog.threshold.query.debug",
        "index.search.slowlog.threshold.query.trace",
        "index.search.slowlog.threshold.fetch.warn",
        "index.search.slowlog.threshold.fetch.info",
        "index.search.slowlog.threshold.fetch.debug",
        "index.search.slowlog.threshold.fetch.trace",
        "index.indexing.slowlog.threshold.index.warn",
        "index.indexing.slowlog.threshold.index.info",
        "index.indexing.slowlog.threshold.index.debug",
        "index.indexing.slowlog.threshold.index.trace"
    ];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Engine constructor.
     *
     * @param Config $config
     * @param EavConfig $eavConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        EavConfig $eavConfig,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        parent::__construct($config, $eavConfig, $logger);
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param string $indexName
     *
     * @return bool
     */
    public function ensureIndex($indexName)
    {
        try {
            if (!$this->isIndexExists($indexName)) {
                $settings = [
                    'index.mapping.total_fields.limit' => 1000000,
                    'max_result_window'                => 1000000,
                    'analysis'                         => [
                        'analyzer' => [
                            'custom'     => [
                                'type'      => 'custom',
                                'tokenizer' => 'whitespace',
                                'filter'    => [
                                    'word',
                                    'lowercase',
                                    'asciifolding',
                                ],
                            ],
                            'custom_raw' => [
                                'type'      => 'custom',
                                'tokenizer' => 'keyword',
                                'filter'    => [
                                ],
                            ],
                        ],
                        'filter'   => [
                            'word' => [
                                'type'                    => 'word_delimiter',
                                'generate_word_parts'     => false,
                                'generate_number_parts'   => false,
                                'catenate_words'          => false,
                                'catenate_numbers'        => false,
                                'catenate_all'            => false,
                                'split_on_case_change'    => false,
                                'preserve_original'       => true,
                                'split_on_numerics'       => false,
                                'stem_english_possessive' => true,
                            ],
                        ],
                    ],
                ];
                if (strpos($indexName, 'catalogsearch_fulltext') !== false) {
                    $settings['max_terms_count'] = self::FULLTEXT_MAX_TERMS_COUNT;
                }

                if ($this->scopeConfig->isSetFlag(self::XML_SLOW_LOG_ENABLED)) {
                    $settings = array_merge($settings, $this->getSlowLogSettings());
                }

                $this->getClient()->indices()->create([
                    'index' => $this->config->getIndexName($indexName),
                    'body'  => [
                        'settings' => $settings
                    ],
                ]);
                $this->getClient()->cluster()->health([
                    'index'                  => $this->config->getIndexName($indexName),
                    'wait_for_active_shards' => 1,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        $this->ensureDocumentType($indexName);

        return true;
    }

    /**
     * @param string $indexName
     *
     * @return bool
     */
    private function isIndexExists($indexName)
    {
        return $this->getClient()->indices()->exists([
            'index' => $this->config->getIndexName($indexName),
        ]);
    }

    /**
     * Get Slow Log Settings for Elasticsearch
     *
     * @return array
     */
    private function getSlowLogSettings(): array
    {
        $result = [];

        foreach (self::XML_SLOW_LOG_LEVEL_TYPE as $value) {
            $result[$value] = $this->getSlowLogConfig($value);
        }

        foreach (self::XML_SLOW_LOG_TIMING_CONFIG as $value) {
            $result[$value] = $this->getSlowLogConfig($value) . 'ms';
        }

        $result[self::XML_SLOW_LOG_CHARACTERS_AMOUNT] = $this->getSlowLogConfig(
            self::XML_SLOW_LOG_CHARACTERS_AMOUNT
        );

        return $result;
    }

    /**
     * Get config value by path prefixed 'search/slow_log/'
     *
     * @param string $path
     * @return mixed
     */
    private function getSlowLogConfig(string $path)
    {
        return $this->scopeConfig->getValue(self::XML_SLOW_LOG_SECTION_PATH . $path);
    }
}
