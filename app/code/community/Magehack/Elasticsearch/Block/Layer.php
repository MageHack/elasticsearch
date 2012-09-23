<?php

/**
 * Magehack_Elasticsearch_Block_Layer
 *
 * @category    Magehack 
 * @package     Magehack_Elasticsearch
 * @author      Carlo Tasca
 */
class Magehack_Elasticsearch_Block_Layer extends Magehack_Elasticsearch_Block_Layer_View
{
    
    /**
     * Internal constructor
     */
    protected function _construct()
    {
        parent::_construct();
        Mage::register('current_layer', $this->getLayer(), true);
    }

    /**
     * Get attribute filter block name
     *
     * @deprecated after 1.4.1.0
     *
     * @return string
     */
    protected function _getAttributeFilterBlockName()
    {
        return 'elasticsearch/layer_filter_attribute';
    }

    /**
     * Initialize blocks names
     */
    protected function _initBlocks()
    {
        parent::_initBlocks();

        $this->_attributeFilterBlockName = 'elasticsearch/layer_filter_attribute';
    }

    /**
     * Get layer object
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer()
    {
        return Mage::getSingleton('elasticsearch/layer');
    }

    /**
     * Check availability display layer block
     *
     * @return bool
     */
    public function canShowBlock()
    {
        $_isLNAllowedByEngine = Mage::helper('catalogsearch')->getEngine()->isLeyeredNavigationAllowed();
        if (!$_isLNAllowedByEngine) {
            return false;
        }
        $availableResCount = (int) Mage::app()->getStore()
            ->getConfig(Mage_CatalogSearch_Model_Layer::XML_PATH_DISPLAY_LAYER_COUNT);

		$resultSet = Mage::helper('elasticsearch')->search();

        if (!$availableResCount
            || ($availableResCount>=$this->getLayer()->setElasticCollection($resultSet)->getProductCollection()->getSize())) {
            return parent::canShowBlock();
        }
        return false;
    }
}
