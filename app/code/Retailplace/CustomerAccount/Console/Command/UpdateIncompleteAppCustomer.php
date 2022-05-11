<?php
namespace Retailplace\CustomerAccount\Console\Command;

use Exception;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Retailplace\CustomerAccount\Model\Config\Source\IncompleteApplicationStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateIncompleteAppCustomer extends Command
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateIncompleteAppCustomer constructor.
     * @param Attribute $eavAttribute
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        Attribute $eavAttribute,
        LoggerInterface $logger,
        ResourceConnection $resource,
        State $appState,
        string $name = null
    ) {
        $this->logger = $logger;
        $this->eavAttribute = $eavAttribute;
        $this->resource = $resource;
        $this->appState = $appState;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('retailplace:customer:update-incomplete-app');
        $this->setDescription('Update incomplete application');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        $start = microtime(true);
        $counter = 0;
        try {
            $connection = $this->resource->getConnection();
            $customerEntityTable = $connection->getTableName('customer_entity');
            $customerEntityTextTable = $connection->getTableName('customer_entity_text');
            $customerEntityIntTable = $connection->getTableName('customer_entity_int');
            $isApprovedId = $this->eavAttribute->getIdByCode('customer', 'is_approved');
            $incompleteApplicationId = $this->eavAttribute->getIdByCode('customer', 'incomplete_application');
            $data = [];

            //Getting customer approval status
            $select = $connection->select()
                ->from(
                    ['e' => $customerEntityTable],
                    ['entity_id']
                )->joinLeft(
                    ['cet' => $customerEntityTextTable],
                    "cet.entity_id = e.entity_id"
                )->where(
                    'cet.attribute_id = ?',
                    $isApprovedId
                )->where(
                    'cet.value IN(?)',
                    ['approved', 'notapproved']
                );

            $approvalData = $connection->fetchAll($select);
            foreach ($approvalData as $row) {
                $data[] = [
                    'attribute_id' => (int) $incompleteApplicationId,
                    'entity_id'    => (int) $row['entity_id'],
                    'value'        => IncompleteApplicationStatus::COMPLETE_APPLICATION
                ];

                if (sizeof($data) >= 1000) {
                    $counter += count($data);
                    $connection->insertOnDuplicate($customerEntityIntTable, $data, ['attribute_id', 'entity_id', 'value']);
                    $data = [];
                }
            }

            if (!empty($data)) {
                $counter += count($data);
                $connection->insertOnDuplicate($customerEntityIntTable, $data, ['attribute_id', 'entity_id', 'value']);
            }
        } catch (Exception $e) {
            $output->writeln("<error>Something went wrong {$e->getMessage()}</error>");
            $output->writeln("Numbers of customer were updated: $counter");
            $this->logger->info("Numbers of customer were updated: $counter");
            $this->logger->error("Update Incomplete Application customer attribute failed: {$e->getMessage()}\n{$e->getTraceAsString()}");
            $time = round(microtime(true) - $start, 2);
            $output->writeln("Execute time: $time");

            return Cli::RETURN_FAILURE;
        }

        $output->writeln("Numbers of customer were updated: $counter");
        $time = round(microtime(true) - $start, 2);
        $output->writeln("Execute time: $time");

        return Cli::RETURN_SUCCESS;
    }
}
