<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Block_Layer_View  extends Mage_Catalog_Block_Layer_View
{
	/**
     * Internal constructor
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_initBlocks();
    }

    /**
     * Initialize blocks names
	 *
	 * @todo override also other blocks to encapsulate in elasticsearch
	 *
     */
    protected function _initBlocks()
    {
        $this->_stateBlockName              = 'elasticsearch/layer_state';
        $this->_categoryBlockName           = 'elasticsearch/layer_filter_category';
        $this->_attributeFilterBlockName    = 'elasticsearch/layer_filter_attribute';
        $this->_priceFilterBlockName        = 'catalog/layer_filter_price';
        $this->_decimalFilterBlockName      = 'catalog/layer_filter_decimal';
    }
}
