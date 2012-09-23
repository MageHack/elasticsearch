<?php

class Magehack_Elasticsearch_Model_Layer extends Mage_CatalogSearch_Model_Layer
{
	protected $_elasticCollection;
	protected $_isElasticCollectionSet = FALSE;
	
	/**
	 * Sets elasticsearch collection
	 * 
	 * @param type $collection
	 * @return \Magehack_Elasticsearch_Model_Layer 
	 */
	public function setElasticCollection ($collection)
	{
		$this->_elasticCollection = $collection;
		$this->_isElasticCollectionSet = TRUE;
		return $this;
	}
	/**
	 * Sets _isElasticCollectionSet value to given argument.
	 * 
	 * @param type $state
	 * @return \Magehack_Elasticsearch_Model_Layer 
	 */
	public function isElasticCollectionSet ($state) 
	{
		$this->_isElasticCollectionSet = $state;
		return $this;
	}
	
	/**
	 * Returns elasticsearch data helper
	 * 
	 * @return /Magehack_Elasticsearch_Helper_Data 
	 */
	public function getHelper () {
		return Mage::helper('elasticsearch');
	}
	
	/**
     * Get current layer product collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection
     */
    public function getProductCollection()
    {
        
		$stateFilters = $this->getState()->getFilters();
		$eFilters = $this->getHelper()->getElasticFilters(); 
		$this->getHelper()->unsElasticFilters();
		$activeFilters = array ();
		
		foreach ($stateFilters as $key => $filter) {
			$activeFilters[$key] = $eFilters[$key];
		}
		
		$this->getHelper()->getCustomerSession()->setElasticFilters ($activeFilters);
		
		if ($this->_isElasticCollectionSet) {
			$this->getHelper()->log(get_class($this) . '::getProductCollection() returning elastic collection');
			
			$this->_productCollections[$this->getCurrentCategory()->getId()] = $this->_elasticCollection;
			return $this->_elasticCollection;
		}
		
		if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $this->getHelper()->log(get_class($this) . '::getProductCollection() running elasticsearch query');
			
			// resetting elastic filters
			if (count($stateFilters) == 0){
				$this->getHelper()->unsElasticFilters();
			}
			$collection = $this->getHelper()->search();
			$this->_elasticCollection = $collection;
			$this->_isElasticCollectionSet = TRUE;
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }
	
	/**
     * Prepare product collection
	 * 
	 * @deprecated used in default Magento search
     *
     * @param Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
        return $this;
    }
	
	
	/**
     * Retrieve layer state object
     *
     * @return Magehack_Elasticsearch_Model_Layer_State
     */
    public function getState()
    {
        $state = $this->getData('state');
        if (is_null($state)) {
            Varien_Profiler::start(__METHOD__);
            $state = Mage::getModel('elasticsearch/layer_state');
            $this->setData('state', $state);
            Varien_Profiler::stop(__METHOD__);
        }

        return $state;
    }
}