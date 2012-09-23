<?php

class Magehack_Elasticsearch_Block_Layer_Filter_Category extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'elasticsearch/layer_filter_category';
    }
}
