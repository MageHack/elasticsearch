<?php

class Magehack_Elasticsearch_Model_Layer_State extends Mage_Catalog_Model_Layer_State
{

	protected $_elasticFilters = array();

	/**
	 * Adds elasticsearch filter to layer state.
	 * Filters are also set in customer session var and retrievable via
	 * [customer_session]->getElasticFilters()
	 *
	 *
	 * @param string $key
	 * @param Elastica_Filter_Abstract $filter
	 */
	public function addElasticFilter($key, Elastica_Filter_Abstract $filter)
	{
		Mage::helper('elasticsearch')->getCustomerSession()->unsElasticFilters();
		$this->_elasticFilters[$key] = $filter;
		Mage::helper('elasticsearch')->getCustomerSession()->setElasticFilters($this->_elasticFilters);
	}

	/**
	 * Returns elasticsearch filters array in customer session
	 *
	 * @return array
	 */
	public function getElasticFilters ()
	{
		return $this->_elasticFilters;
	}

	/**
	 * Add filter item to layer state
	 *
	 * @param   string $key
	 * @param   Elastica_Filter_Abstract $filter
	 * @return  Magehack_Elasticsearch_Model_Layer_State
	 */
	public function addFilter($filter)
	{
		$filters = $this->getFilters();
		//$filters[$key] = $filter;
		$this->setFilters($filters);
		return $this;
	}

	/**
	 * Set layer state filter items
	 *
	 * @param   array $filters
	 * @return  Mage_Catalog_Model_Layer_State
	 */
	public function setFilters($filters)
	{
		if (!is_array($filters)) {
			Mage::throwException(Mage::helper('elasticsearch')->__('The filters must be an array.'));
		}
		$this->setData('filters', $filters);
		return $this;
	}

	/**
	 * Get applied to layer filter items
	 *
	 * @return array
	 */
	public function getFilters()
	{
		$filters = $this->getData('filters');

		if (is_null($filters)) {
			$filters = array();
			$this->setData('filters', $filters);
		}
		return $filters;
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
