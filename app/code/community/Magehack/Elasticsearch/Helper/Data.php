<?php

class Magehack_Elasticsearch_Helper_Data extends Mage_Core_Helper_Abstract
{
	
	const XML_PATH_GLOBAL_ENABLED = 'elasticsearch/globals/enabled';
	const XML_PATH_HAS_WILDCARD = 'elasticsearch/globals/has_wildcard';
	const XML_PATH_INDEX_NAME = 'elasticsearch/index/name';
	const XML_PATH_ELASTIC_HOST = 'elasticsearch/globals/host';
	const XML_PATH_ELASTIC_LIMIT = 'elasticsearch/globals/searchlimit';
	const XML_PATH_STATIC_MEMBERS_RESET = 'elasticsearch/index/reset_static_members';
	const XML_PATH_ELASTIC_PORT = 'elasticsearch/globals/port';
	const XML_PATH_ELASTIC_TRANSPORT = 'elasticsearch/globals/transport';
	const XML_PATH_INDEX_CUSTOM_SETTINGS = 'elasticsearch/index/custom_settings';
	const XML_PATH_SCHEDULE_TYPE = 'elasticsearch/schedule/type';
	const XML_PATH_SCHEDULE_CRON = 'elasticsearch/schedule/cron';
	const XML_PATH_EQUERY_SITESEARCH = 'global/elasticsearch/equery/sitesearch';
	const XML_PATH_EQUERY_FROMSIZESEARCH = 'global/elasticsearch/equery/fromsizesearch';
	const CRON_JOB_CODE = 'elasticsearch';
	const QUERY_VAR_NAME = 'q';
	const MAX_QUERY_LEN = 200;
	const CONTROLLER_SEARCH_RESULT_ACTION = 'elasticsearch/result';
	
	protected $_storeId = 0;
	protected $_moduleName = 'elasticsearch';
	
	/**
	 *
	 * @var Magehack_Elasticsearch_Helper_Inflector 
	 */
	protected $_inflector;

	
	public function __construct()
	{
		//$this->_storeId = Mage::app()->getStore()->getStoreId();
	}
	
	
	/**
	 * Magic method __call handles methods starting with:
	 * 
	 * getConfig********(config node)
	 * 
	 * @param string $name
	 * @param array $arguments
	 * @return mixed 
	 */
	public function __call($name, $arguments)
	{
		if (preg_match('/getConfig\w/', $name)) {
			$name = str_replace('getConfig', '', $name);
			$inflectedName = $this->getInflector()->underscore($name);
			return $this->getModuleConfig($this->_moduleName, $arguments[0], $inflectedName);
		}
	}
	
	/**
	 * Retrieve result page url and set "secure" param to avoid confirm
	 * message when we submit form from secure page to unsecure
	 *
	 * @param   string $query
	 * @return  string
	 */
	public function getResultUrl($query = null)
	{
		return $this->_getUrl(self::CONTROLLER_SEARCH_RESULT_ACTION, array(
					'_query' => array(self::QUERY_VAR_NAME => $query),
					'_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
				));
	}
	
	/**
	 * 
	 * @return Magehack_Elasticsearch_Helper_Inflector 
	 */
	public function getInflector()
	{
		if ($this->_inflector instanceof Magehack_Elasticsearch_Helper_Inflector) {
			return $this->_inflector;
		}
		return Mage::helper('elasticsearch/inflector');
	}

	
	/**
	 * Logging helper
	 * 
	 * @param type $message
	 * @param type $level 
	 */
	public function log($message, $level = null)
	{
		Mage::log($message, $level, 'magehack_elasticsearch.log');
	}

	
	
	/**
	 *  Gets store config value for node and key passed as argument.
	 * 
	 * @param string $moduleName
	 * @param string $node
	 * @param string $key
	 * @return mixed 
	 */
	protected function getModuleConfig($moduleName, $node, $key)
	{
		return Mage::getStoreConfig($moduleName . '/' . $node . '/' . $key, $this->getStoreId());
	}

	/**
	 * Returns store id
	 * 
	 * @return int 
	 */
	public function getStoreId()
	{
		if (Mage::app()->isSingleStoreMode()) {
			$this->_storeId = 0;
			return $this->_storeId;
		}
		return $this->_storeId = Mage::app()->getStore()->getId();
	}
	/**
	 * Returns customer session singleton
	 * @return type 
	 */
	public function getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}
	
	/**
	 * Is module enabled, determined via:
	 * 
	 * @see XML_PATH_GLOBAL_ENABLED
	 * 
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->getConfigIsEnabled('globals');
	}
	
	/**
	 * Return product type ids that this module can handle
	 * 
	 * @return type 
	 */
	public function getSupportedProductTypes()
	{
		return array(
			Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
			Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			Mage_Catalog_Model_Product_Type::TYPE_GROUPED
		);
	}
	
	/**
	 * Takes a string and determines if it is prefixed with a '-'
	 * 
	 * e.g. '-Analyzer' returns TRUE
	 * 		'Analyzer' returns FALSE
	 * 
	 * @param string $string
	 * @param array $exceptions
	 * @return FALSE | string
	 */
	public function stringCheckForUnset($string, $exceptions = array('type'))
	{
		$string = trim($string);
		// If first character is '-'
		if (strstr($string, '-', true) === '') {
			// Remove first character form $string so we can 
			// match in original array and unset
			$real_string = substr($string, 1);
			if (!empty($exceptions)) {
				foreach ($exceptions as $exception) {
					// Not allowed to unset 'type' required by elasticsearch
					if ($real_string == $exception) {
						return FALSE;
					}
				}
			}
			return $real_string;
		}

		return FALSE;
	}
	
	/**
	 * Convert Magento model class name to getModel class string
	 * 
	 * e.g. 'Mage_Catalog_Model_Product' returns 'catalog/product'
	 * 
	 * @param Varien_Object $item
	 * @return string 
	 */
	public function generateClassString(Varien_Object $item)
	{
		$parts = explode('_', get_class($item));
		/*
		 * 0 = Namespace (e.g. Mage)
		 * 1 = Module (e.g. Core)
		 * 2 = Magento Type (e.g. Model)
		 * 3... = Path (e.g. Product_Stock_Item)
		 */
		if (!isset($parts[2]) || $parts[2] != 'Model') {
			$this->log(get_class($this) . '::generateClassString() Model not passed or bad class name');
			Mage::throwException('Model not passed or bad class name');
		}

		$module = $parts[1];
		unset($parts[0], $parts[1], $parts[2]); // Remove namespace, type and module

		return strtolower($module . '/' . implode('_', $parts));
	}
	
	/**
	 * Compares current and original data values of an object to determine if it 
	 * has changed or is new (and so requires saving)
	 * 
	 * @param Mage_Core_Model_Abstract $object Model to check
	 * @param array $keys Data keys to compare, along with optional casting
	 */
	public function hasChanged($object, $keys)
	{
		if ($object->isObjectNew() || $object->isDeleted()) {
			$changed = true;
		} else {
			$changed = false;
			foreach ($keys as $key => $cast) {
				if (is_numeric($key)) {
					$key = $cast;
					$cast = 'string';
				}
				$orig_data = $object->getOrigData($key);
				$new_data = $object->getData($key);
				if (method_exists($this, '_castTo' . $cast)) {
					$cast_method = '_castTo' . $cast;
					$orig_data = $this->$cast_method($orig_data);
					$new_data = $this->$cast_method($new_data);
				} else {
					settype($orig_data, $cast);
					settype($new_data, $cast);
				}
				if ($orig_data != $new_data) {
					$changed = true;
				}
			}
		}
		return $changed;
	}
	
	protected function _castToList($value)
	{
		if (is_array($value)) {
			return implode(',', $value);
		} else {
			return $value;
		}
	}

	protected function _castToInteger($value)
	{
		return intval($value);
	}

	protected function _castToPrice($value)
	{
		return sprintf("%01.2f", $value);
	}
	
	/**
	 * Remaps and then reindexs all the available Etypes
	 */
	public function remapReindexAll()
	{
		$this->remap();
		$this->reindexAll();
	}

	/**
	 * Reindexes all available Etypes
	 * 
	 * @param boolean $force force a generate and push on all types
	 */
	public function reindexAll($force = TRUE)
	{
		$types = Mage::getModel('elasticsearch/etype')->getCollection();
		$queue = Mage::getModel('elasticsearch/queue');
		/* @var $queue GPMD_Elasticsearch_Model_Queue */
		foreach ($types as $etype) {
			/* @var $etype GPMD_Elasticsearch_Model_Etype */
			$feed_model = $etype->getFeedModel();
			$result = $queue->addAllItems($feed_model->getAllItems());
			if (!$result) {
				$this->log("Failed to add all items for type: {$feed_model->getIndexName()}");
			}

			if ($this->isRealtime() || $force) {
				$feed_model->generateAndPush();
			}
		}
	}

	/**
	 * Recreates elasticsearch index, all data is lost after calling this 
	 */
	public function remap()
	{
		$elasticsearch = Mage::getModel('elasticsearch/api_elasticsearch');
		$elasticsearch->deleteIndex();
		$elasticsearch->refreshIndex();
	}
	
	/**
	 * Gets option attribute value by id.
	 * 
	 * Takes attribute id as argument and loops through options value
	 * to find a match.
	 * 
	 * If found, it returns it, or returns false otherwise
	 * 
	 * @param type $arg_id
	 * @return type 
	 */
	public function getAttributeOptionValueById($arg_id)
	{
		$attribute = $this->getAttributeModel();
		$values = array();
		$valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
				->setAttributeFilter($attribute->getId())
				->setStoreFilter(0, false)
				->load();
		foreach ($valuesCollection as $item) {
			if ($item->getId() == $arg_id) {
				return $item->getValue();
			}
		}
		return false;
	}
	
	
	public function addLnav($url){
		if(strpos($url, 'l=0')===false&&strpos($url, 'l=1'===false)){
			$url .= '&l=0';
		}
		return $url;
	}
	public function addLnav1($url){
		$search = array('&l=0','l=0','%26l%3d0');
		$url = str_replace($search,'',$url);
		if(strpos($url, 'l=1')===false){
			$url .= '&l=1';
		}
		return $url;
	}
	public function clearLnav($url){
		$search = array('&l=1','l=1','%26l%3d1','#%21l=1','%23!l%3d1','#!l=1');
		return str_replace($search,'',$url);
	}
}