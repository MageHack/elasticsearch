<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Block_Layer_State extends Mage_Catalog_Block_Layer_State
{

	/**
	 * Initialize Layer State template
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('catalog/layer/state.phtml');
	}

	/**
	 * Retrieve active filters
	 *
	 * @return array
	 */
	public function getActiveFilters()
	{
		$filters = $this->getLayer()->getState()->getFilters();

		if (!is_array($filters)) {
			$filters = array();
		}
		return $filters;
	}

	/**
	 * Retrieve Layer object
	 *
	 * @return Mage_Catalog_Model_Layer
	 */
	public function getLayer()
	{
		if (!$this->hasData('layer')) {
			$this->setLayer(Mage::getSingleton('elasticsearch/layer'));
		}
		return $this->_getData('layer');
	}

	/**
	 * Unsets session elasticsearch filters
	 */
	protected function _unsElasticFilters()
	{
		$helper = Mage::helper('elasticsearch');
		$helper->unsElasticFilters();
	}

}
