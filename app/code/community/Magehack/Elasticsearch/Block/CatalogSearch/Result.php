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
}