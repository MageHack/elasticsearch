<?php
class Magehack_Elasticsearch_Block_CatalogSearch_Result extends Mage_CatalogSearch_Block_Result{
	protected function _prepareLayout()
    {
		if(!$this->getRequest()->isXmlHttpRequest()){
    		return parent::_prepareLayout();
    	}else{
            return $this;
        }
    }
	
	/**
	 * Retrieve search result count
	 *
	 * @return string
	 */
	public function getResultCount() {
		return $this->_getProductCollection()->getElasticCollectionHitTotal();
	}

	/**
	 * Retrieve loaded category collection
	 *
	 * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
	 */
	protected function _getProductCollection() {
		Mage::helper('elasticsearch')->log(get_class($this) . '::_getProductCollection() invoked');
		if (is_null($this->_productCollection)) {
			$this->_productCollection = $this->getListBlock()->getLoadedProductCollection();
		}
		$this->_productCollection = $this->getListBlock()->getLoadedProductCollection();
		return $this->_productCollection;
	}

}