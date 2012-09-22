<?php

class Magehack_Elasticsearch_Helper_Data extends Mage_Core_Helper_Abstract
{
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