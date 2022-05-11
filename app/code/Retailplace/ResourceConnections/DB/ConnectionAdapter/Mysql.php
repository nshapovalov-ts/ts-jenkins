<?php
/**
 * Retailplace_ResourceConnection
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ResourceConnections\DB\ConnectionAdapter;

use Retailplace\ResourceConnections\DB\Adapter\Pdo\MysqlProxy;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DB;
use Magento\Framework\DB\Adapter\Pdo\MysqlFactory;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\Model\ResourceModel\Type\Db\Pdo\Mysql as DbPdoMysql;

/**
 * Class Mysql
 */
class Mysql extends DbPdoMysql
{
    /** @var HttpRequest */
    protected $request;

    /**
     * Constructor
     *
     * @param array $config
     * @param HttpRequest $request
     * @param MysqlFactory|null $mysqlFactory
     */
    public function __construct(
        array $config,
        HttpRequest $request,
        MysqlFactory $mysqlFactory = null
    ) {
        parent::__construct($config, $mysqlFactory);
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(DB\LoggerInterface $logger = null, SelectFactory $selectFactory = null)
    {
        $connection = $this->getDbConnectionInstance($logger, $selectFactory);
        if ($connection instanceof \Magento\Framework\DB\Adapter\Pdo\Mysql) {
            $profiler = $connection->getProfiler();
            if ($profiler instanceof DB\Profiler) {
                $profiler->setType($this->connectionConfig['type']);
                $profiler->setHost($this->connectionConfig['host']);
            }
        }
        return $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDbConnectionClassName()
    {
        if (isset($this->connectionConfig['slave']) && $this->request->isSafeMethod()) {
            return MysqlProxy::class;
        }
        unset($this->connectionConfig['slave']);
        return \Magento\Framework\DB\Adapter\Pdo\Mysql::class;
    }
}
