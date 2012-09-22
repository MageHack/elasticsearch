<?php

/**
 * Abstract class describing a Feed based on the Queue
 */
abstract class Magehack_Elasticsearch_Model_Feed_Abstract extends Varien_Object{

	// Array of items and data to be indexed / updated
	protected $_feedArray = array();
	
	// Type used in elasticsearch e.g. 'product'
	protected $_type = NULL;
	
	// List of skipped queue items;
	protected $_skipped = array();
	
	// Chunking for _bulk updates
	protected $_chunkSize = 500;
	
	protected $_eClient;
	/* @var $_eClient Elastica_Client */
	protected $_eIndex;
	/* @var $_eIndex Elastica_Index */
	protected $_eType;
	/* @var $_eType Elastica_Type */
	protected $_eMap;
	/* @var $_eMap Elastica_Type_Mapping */
	
	function __construct() {
		
		$this->_eClient = $this->_getElasticsearchApi()->getElasticaClient();
		
		$this->_eIndex = $this->_getElasticsearchApi()->getElasticaIndex();
		
		if(!$this->_type){
			Mage::helper('elasticsearch')->log('Type not set on current class "'.get_class($this).'"');
			Mage::throwException('Type not set on current class "'.get_class($this).'"');
		}
		
		$this->_eType = $this->_eIndex->getType($this->_type);
		
		try{
			$this->_eType->getMapping();
		}catch(Elastica_Exception_Response $e){
			$error = $e->getResponse()->getData();
			// Elasticsearch returns a 404 if type doesn't exist on a valid index (but elastica considers it an exception)
			if($error['status'] == 404){
				Mage::helper('elasticsearch')->log(get_class($this)."::__construct() Type '{$this->_type}' doesn't exist, applying mappings");
				try{
					$this->_eMap = new Elastica_Type_Mapping($this->getType());
					// Set mapping for current type
					$this->_eMap->setProperties($this->getMapping());
					$this->_eMap->send();
				}catch(Elastica_Exception_Response $e){
					Mage::helper('elasticsearch')->log(get_class($this)."::__construct() Whilst setting mappings - Elastica_Exception_Response:\n\t".$e->getMessage());
				}
			}else{
				Mage::helper('elasticsearch')->log(get_class($this)."::__construct() Error whilst getting mappings Elastica_Exception_Response:\n\t".$e->getMessage());
				Mage::throwException($e);
			}
		}catch(Exception $e){
			Mage::helper('elasticsearch')->log(get_class($this)."::__construct() Unknown exception - Exception:\n\t".$e->getMessage());
			Mage::throwException($e);
		}
	}
	
	/**
	 *
	 * @return Magehack_Elasticsearch_Model_Api_Elasticsearch 
	 */
	protected function _getElasticsearchApi(){
		if(!isset($this->_elasticsearch)){
			$this->_elasticsearch = Mage::getModel('elasticsearch/api_elasticsearch');
		}
		
		return $this->_elasticsearch;
	}
	
	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	protected function _getHelper(){
		if(!isset($this->_helper)){
			$this->_helper = Mage::helper('elasticsearch');
		}
		
		return $this->_helper;
	}
	
	/**
	 *
	 * @return Elastica_Client 
	 */
	public function getClient(){
		return $this->_eClient;
	}
	
	/**
	 *
	 * @return Elastica_Index 
	 */
	public function getIndex(){
		return $this->_eIndex;
	}
	
	/**
	 *
	 * @return Elastica_Type 
	 */
	public function getType(){
		return $this->_eType;
	}
	
	/**
	 *
	 * @return Elastica_Type_Mapping 
	 */
	public function getMap(){
		if(!isset($this->_eMap)){
			$this->_eMap = new Elastica_Type_Mapping($this->getType());
		}
		return $this->_eMap;
	}
	
	public function getTypeName(){
		return $this->_type;
	}
	
	protected function _addSkipped($id){
		$this->_skipped[] = $id;
	}
	
	public function getIndexName(){
		return $this->getIndex()->getName();
	}
	
	public function setChunkSize($size){
		$this->_chunkSize = (int) $size;
	}
	
	public function getChunkSize(){
		return $this->_chunkSize;
	}
	
	/**
	 * Main method, called from Cron according to admin-set schedule
	 */
	public function generateAndPush() {
		try {
			if ($this->generate()) {
				$this->push();

				$elasticsearch = $this->_getElasticsearchApi();
				$elasticsearch->refreshIndex();
			}
		} catch (Exception $e) {
			Mage::helper('elasticsearch')->log('Exception: ' . $e->getMessage(), Zend_Log::ERR);
			Mage::helper('elasticsearch')->log($e->getTraceAsString(), Zend_Log::ERR);

			// Unlock the queue
			Mage::getModel('elasticsearch/queue')->unlock();

			throw $e; // In case we're called in the admin panel
		}
	}

	/**
	 * Generate the feed array, based on the items in the queue
	 * 
	 * @return boolean 
	 */
	public function generate() {
		$startTime = microtime(TRUE);
		$queue = Mage::getModel('elasticsearch/queue');
		$result = FALSE;
		if ($queue->lock()) {
			$items = $this->_getQueueItems();

			if ($items->getSize()) {
				// Generate
				$this->_organiseData($items);
				$result = TRUE;
			} else {
				Mage::helper('elasticsearch')->log(get_class($this) . '::generate() Empty queue. Not generating.');
			}
			$queue->unlock();
		} else {
			Mage::helper('elasticsearch')->log(get_class($this) . '::generate() Cannot lock queue. Not generating.', Zend_Log::WARN);
		}

		$endTime = microtime(TRUE);
		$totalTime = round($endTime - $startTime, 2);
		Mage::helper('elasticsearch')->log(get_class($this) . '::generate() Done in ' . $totalTime . ' seconds');

		return $result;
	}

	/**
	 * Push changes
	 */
	public function push() {
		$this->_feedArray = array_chunk($this->_feedArray, $this->_chunkSize);
		
		foreach($this->_feedArray as $chunk => $data){
			try{
				$this->getClient()->bulk($data);
			}catch(Exception $e){
				Mage::helper('elasticsearch')->log(get_class($this)."::__construct() Unknown exception - Exception:\n\t".$e->getMessage());
				Mage::throwException($e);
			}
		}
	}
	
	/**
	 * Returns key of last item in array with out modifiying original arrays 
	 * internal pointer (pass by copy NOT reference)
	 * 
	 * @see http://uk.php.net/manual/en/function.end.php#107733
	 * 
	 * @param array $array
	 * @return string | int
	 */
	protected function _endKey($array){
		return key(end($array));
	}

	/**
	 * Returns queue items for this feed to process
	 */
	abstract protected function _getQueueItems();
	
	/**
	 * Sets $this->_feedArray to a assoc array key'd on ID
	 * 
	 */
	abstract protected function _organiseData($items);
	
	/**
	 * Returns array for type mapping 
	 */
	abstract public function getMapping();
	
	/**
	 * Returns collection of all items to be indexed 
	 */
	abstract public function getAllItems();
}