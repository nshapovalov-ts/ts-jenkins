<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Mirakl\Api\Helper\Config;
use Magento\Framework\App\ResourceConnection;



class SyncMiraklAdditionalField extends Command
{
    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";
 
    
    /**
     * Code of "Integrity constraint violation: 1062 Duplicate entry" error
     */
    const ERROR_CODE_DUPLICATE_ENTRY = 23000;
 
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;
 
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var State
     */
    private $appState;

    

    /**
     * @var Config
     */
    private $config;

    

    /**
     * @param   State           $state
     * @param   Config          $config
     * @param   string|null     $name
     */
    public function __construct(
        State $state,
        Config $config,
        ResourceConnection $resource,
        $name = null
    ) {
        parent::__construct($name);
        $this->appState        = $state;
        $this->config          = $config;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->appState ->setAreaCode(Area::AREA_ADMINHTML);
        $isEnabled = $this->config->isEnabled();
        $apiUrl = $this->config->getApiUrl();
        $apiKey = $this->config->getApiKey();

        if ($isEnabled) {
            $output->writeln('Importing Additional Field ...');
            $additionField = json_decode($this->getAdditionalField($apiUrl, $apiKey),true);
            if(isset($additionField['additional_fields'])){
                $bulkInsertIndustryExclusions = [];
                $bulkInsertChannelExclusions = [];
                $bulkInsertExclusionLogic = [];
                foreach ($additionField['additional_fields'] as $key => $value) {
                    $code = $value['code'] ?? "";
                    //channel-exclusions
                    //industry-exclusions
                    //exclusions-logic
                    if($code == "industry-exclusions"){
                        $industryexclusions = $this->getTableData('mirakl_additionalfield_industryexclusions' );
                        $industryexclusions = array_values(array_diff (array_values($value['accepted_values']),array_values($industryexclusions)));
                        if($industryexclusions){
                            $bulkInsertIndustryExclusions = array_map(function($v){
                                return ['code' => $v];
                            },$industryexclusions);
                            $this->insertMultiple('mirakl_additionalfield_industryexclusions', $bulkInsertIndustryExclusions);    
                        }
                    }
                   
                    if($code == "channel-exclusions"){
                        $channelExclusions = $this->getTableData('mirakl_additionalfield_channelexclusions' );
                        $channelExclusions = array_values(array_diff (array_values($value['accepted_values']),array_values($channelExclusions)));
                        if($channelExclusions ){
                            $bulkInsertChannelExclusions = array_map(function($v){
                                return ['code' => $v];
                            },$channelExclusions);
                            $this->insertMultiple('mirakl_additionalfield_channelexclusions', $bulkInsertChannelExclusions);    
                        }
                    }
                    /*if($code == "exclusions-logic"){
                        $exclusionLogic = $this->getTableData('mirakl_additionalfield_exclusionslogic' );
                        $exclusionLogic = array_values(array_diff (array_values($value['accepted_values']),array_values($exclusionLogic)));
                        if( $exclusionLogic){
                            $bulkInsertExclusionLogic = array_map(function($v){
                                return ['code' => $v];
                            },$exclusionLogic);
                            $this->insertMultiple('mirakl_additionalfield_exclusionslogic', $bulkInsertExclusionLogic);    
                        }
                    }*/
                }
                
            }
            

           

           // $this->importProcess->runApi($updatedSince);
        } else {
            $output->writeln('Mirakl Api is not activated in your configuration');
        }

        /*$name = $input->getArgument(self::NAME_ARGUMENT);
        $option = $input->getOption(self::NAME_OPTION);
        $output->writeln("Hello " . $name);*/
    }
    /**
     * Insert multiple
     *
     * @param array $data
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Exception
     */
    public function insertMultiple($tableName , $data)
    {
        try {
            $tableName = $this->resource->getTableName($tableName);
            return $this->connection->insertMultiple($tableName, $data);
        } catch (\Exception $e) {
            if ($e->getCode() === self::ERROR_CODE_DUPLICATE_ENTRY
                && preg_match('#SQLSTATE\[23000\]: [^:]+: 1062[^\d]#', $e->getMessage())
            ) {
                throw new \Magento\Framework\Exception\AlreadyExistsException(
                    __('URL key for specified store already exists.')
                );
            }
            throw $e;
        }
    }
    public function getTableData($tableName )
    {
        $tableName = $this->resource->getTableName($tableName);
        $select = $this->connection->select()
                                ->from(
                                        $tableName ,['code']
                                );
        return $this->connection->fetchCol($select);
        
        
    }
    public function getAdditionalField($apiUrl, $apiKey){
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "$apiUrl/additional_fields",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Authorization:  $apiKey"
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return  $response;

    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("mirakl:sync:additional_field");
        $this->setDescription("Sync Mirakl Additional Field");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
   
}
