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
		$this->_storeId = Mage::app()->getStore()->getStoreId();
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

}