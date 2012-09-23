<?php
class Magehack_Elasticsearch_Model_Layer_Filter_Category extends Mage_Catalog_Model_Layer_Filter_Category
{
	//protected $_elasticFilter;
	
	public function _construct() {
		parent::_construct();
		//$this->_elasticFilter = new Elastica_Filter_Terms();
	}
	
	/**
	 * 
	 * @return Magehack_Elasticsearch_Helper_Data 
	 */
	
	protected function _getHelper () {
		return Mage::helper('elasticsearch');
	}
	
	/**
	 *
	 * Takes a category filter as argument and returns elastic search terms filter
	 * instance.
	 * 
	 * @param string/int $filter
	 * @return Elastica_Filter_Terms 
	 */
	protected function _getTermsFilter ($filter) {
		return $this->_getHelper()->getElasticaFilterTerms('categories', array($filter));
	}
	
	/**
     * Apply category filter to layer
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Mage_Core_Block_Abstract $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Category
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        // make search with filter
		$filter = (int) $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }
		$this->_categoryId = $filter;

		$categoriesFilter = $this->_getTermsFilter($filter);
		$collection = $this->_getHelper()->search(array($categoriesFilter));
		$this->getLayer()->setElasticCollection($collection);
        Mage::register('current_category_filter', $this->getCategory(), true);

        $this->_appliedCategory = Mage::getModel('catalog/category')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($filter);
		
        if ($this->_isValidCategory($this->_appliedCategory)) {
            $this->getLayer()->getProductCollection()
                ->addCategoryFilter($this->_appliedCategory);

            $this->getLayer()->getState()->addFilter($this->getRequestVar(),
                $this->_createItem($this->_appliedCategory->getName(), $filter)
            );
			
			$this->getLayer()->getState()->addElasticFilter ($this->getRequestVar(), $categoriesFilter);
        }

        return $this;
    }
	
	public function _addElasticsearchFilter (Elastica_Filter_Abstract $filter) {
		
	}
	
}