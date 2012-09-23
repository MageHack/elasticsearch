<?php

class Magehack_Elasticsearch_Block_Layer_Filter_Attribute extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'elasticsearch/layer_filter_attribute';
    }
    
    protected function _prepareFilter()
    {
        $this->_filter->setAttributeModel($this->getAttributeModel());
        return $this;
    }
}
