<?php
/**
 * Retailplace_Misspell
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Misspell\Model\Search;

use Exception;
use Mirasvit\SearchElastic\Model\Config;
use Mirasvit\SearchElastic\Model\Engine as SearchEngine;
use Magento\Eav\Model\Config as EavConfig;
use Psr\Log\LoggerInterface;

/**
 * Engine Class
 */
class Engine extends SearchEngine
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * Engine constructor.
     *
     * @param Config $config
     * @param EavConfig $eavConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config          $config,
        EavConfig       $eavConfig,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $config,
            $eavConfig,
            $logger
        );

        $this->config = $config;
        $this->logger = $logger;
    }


    /**
     * Ensure Index
     *
     * @param string $indexName
     * @return bool
     */
    public function ensureIndex($indexName): bool
    {
        try {
            if (!$this->isIndexExists($indexName)) {
                $this->getClient()->indices()->create([
                    'index' => $this->config->getIndexName($indexName),
                    'body' => [
                        'settings' => [
                            'index.mapping.total_fields.limit' => 1000000,
                            'max_result_window' => 1000000,
                            'analysis' => [
                                'analyzer' => [
                                    'autocomplete' => [
                                        'type' => 'custom',
                                        'tokenizer' => 'standard',
                                        'filter' => [
                                            'lowercase',
                                            'autocomplete_filter',
                                        ],
                                    ]
                                ],
                                'filter' => [
                                    'autocomplete_filter' => [
                                        'type' => 'ngram',
                                        'min_gram' => 1,
                                        'max_gram' => 50
                                    ],
                                ]
                            ],
                            "max_ngram_diff" => "50"
                        ],
                    ],
                ]);
                $this->getClient()->cluster()->health([
                    'index' => $this->config->getIndexName($indexName),
                    'wait_for_active_shards' => 1,
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error($e);
        }

        $this->ensureDocumentType($indexName);

        return true;
    }

    /**
     * Ensure Document Type
     *
     * @param string $indexName
     * @return bool
     */
    public function ensureDocumentType($indexName): bool
    {
        try {
            if (!$this->isMappingExists($indexName)) {
                $mapping = [
                    'index' => $this->config->getIndexName($indexName),
                    $this->setDocumentTypeKey() => $this->setDocumentType($indexName),
                    'body' => [
                        'properties' => [
                            'keyword' => [
                                'type' => 'text',
                                'analyzer' => 'autocomplete',
                                'search_analyzer' => 'standard'
                            ]
                        ]
                    ],
                ];

                foreach ($mapping['body']['properties'] as $key => $property) {
                    if ($property['type'] == 'text') {
                        $mapping['body']['properties'][$key]['fielddata'] = true;
                    }
                }

                $this->getClient()->indices()->putMapping($mapping);
            }
        } catch (Exception $e) {
            $this->logger->error($e);
        }

        return true;
    }

    /**
     * Is Index Exists
     *
     * @param string $indexName
     * @return bool
     * @throws Exception
     */
    private function isIndexExists(string $indexName): bool
    {
        return $this->getClient()->indices()->exists([
            'index' => $this->config->getIndexName($indexName),
        ]);
    }


    /**
     * Is Mapping Exists
     *
     * @param string $indexName
     * @return bool
     */
    private function isMappingExists(string $indexName): bool
    {
        try {
            $mapping = $this->getClient()->indices()->getMapping([
                'index' => $this->config->getIndexName($indexName),
                $this->setDocumentTypeKey() => $this->setDocumentType($indexName)
            ]);
        } catch (Exception $e) {
            return false;
        }

        return (bool)$mapping;
    }

    /**
     * Set Document Type Key
     *
     * @return string
     * @throws Exception
     */
    private function setDocumentTypeKey(): string
    {
        if (version_compare($this->getEsVersion(), '7.0.0', '<')) {
            return 'type';
        } else {
            return 'index';
        }
    }

    /**
     * Set Document Type
     *
     * @param string $indexName
     * @return string
     * @throws Exception
     */
    private function setDocumentType(string $indexName): string
    {
        if (version_compare($this->getEsVersion(), '7.0.0', '<')) {
            return Config::DOCUMENT_TYPE;
        } else {
            return $this->config->getIndexName($indexName);
        }
    }

    /**
     * Query
     *
     * @param string $indexName
     * @param array $body
     * @return array
     * @throws Exception
     */
    public function query(string $indexName, array $body): array
    {
        $query = [
            'index' => $this->config->getIndexName($indexName),
            $this->setDocumentTypeKey() => $this->setDocumentType($indexName),
            'body' => $body,
        ];

        $result = [];
        try {
            $result = $this->getClient()->search($query);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $result;
    }
}
