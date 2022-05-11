<?php
/**
 * Retailplace_ResourceConnection
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ResourceConnections\Console\Command;

use Exception;
use InvalidArgumentException;
use LogicException;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Console\Cli;
use Retailplace\ResourceConnections\App\DeploymentConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddSlave implements command for adding slave connection to the env.php file
 */
class AddSlave extends Command
{
    /** @var string */
    const HOST = 'host';

    /** @var string */
    const DB_NAME = 'dbname';

    /** @var string */
    const USER_NAME = 'username';

    /** @var string */
    const PASSWORD = 'password';

    /** @var string */
    const CONNECTION = 'connection';

    /** @var string */
    const RESOURCE = 'resource';

    /** @var string */
    private const MAX_LAG = 'maxAllowedLag';

    /** @var Writer */
    private $configWriter;

    /** @var Reader */
    private $configReader;

    /**
     * Constructor.
     *
     * @param Writer $configWriter
     * @param Reader $configReader
     * @param string|null $name
     * @throws LogicException When the command name is empty
     */
    public function __construct(
        Writer $configWriter,
        Reader $configReader,
        string $name = null
    ) {
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        parent::__construct($name);
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'setup:db-schema:add-slave';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Add slave DB connection';
    }

    /**
     * @return array
     */
    protected function getCommandDefinition(): array
    {
        return [
            new InputOption(
                self::HOST,
                null,
                InputOption::VALUE_REQUIRED,
                'Slave DB Server host',
                'localhost'
            ),
            new InputOption(
                self::DB_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Slave Database Name'
            ),
            new InputOption(
                self::USER_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'Slave DB user name',
                'root'
            ),
            new InputOption(
                self::PASSWORD,
                null,
                InputOption::VALUE_OPTIONAL,
                'Slave DB user password'
            ),
            new InputOption(
                self::CONNECTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Slave connection name',
                'default'
            ),
            new InputOption(
                self::RESOURCE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Slave Resource name',
                'default'
            ),
            new InputOption(
                self::MAX_LAG,
                null,
                InputOption::VALUE_OPTIONAL,
                'Max Allowed Lag Slave Connection (in seconds)',
                ''
            )
        ];
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName($this->getCommandName())
            ->setDescription($this->getCommandDescription())
            ->setDefinition($this->getCommandDefinition());
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->generateConfig($input);
        $this->configWriter->saveConfig([ConfigFilePool::APP_ENV => $config], true);
        $output->writeln('Slave has been added successfully!');
        return Cli::RETURN_SUCCESS;
    }

    /**
     * Generate environment configuration
     *
     * @param InputInterface $input
     * @return array
     * @throws Exception
     */
    protected function generateConfig(InputInterface $input): array
    {
        $config = $this->configReader->load(ConfigFilePool::APP_ENV);

        if (!isset($config['db'][DeploymentConfig::SLAVE_CONNECTION][$input->getOption(self::CONNECTION)])) {
            $config['db'][DeploymentConfig::SLAVE_CONNECTION][$input->getOption(self::CONNECTION)] = [
                'host' => $input->getOption(self::HOST),
                'dbname' => $input->getOption(self::DB_NAME),
                'username' => $input->getOption(self::USER_NAME),
                'password' => $input->getOption(self::PASSWORD),
                'maxAllowedLag' => $input->getOption(self::MAX_LAG),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ];
        } else {
            throw new InvalidArgumentException('Connection with same name already exists');
        }

        if (!isset($config['resource'][$input->getOption(self::RESOURCE)])) {
            $config['resource'][$input->getOption(self::RESOURCE)] = [
                'connection' => $input->getOption(self::CONNECTION)
            ];
        }
        return $config;
    }
}
