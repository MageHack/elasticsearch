<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author adrian
 */
class Magehack_Elasticsearch_Block_Attribute_Edit_Tabs extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs
{
	protected function _beforeToHtml()
	{
        $this->addTabAfter('elasticsearch', array(
            'label'     => Mage::helper('catalog')->__('Elasticsearch'),
            'title'     => Mage::helper('catalog')->__('Elasticsearch'),
            'content'   => $this->getLayout()->createBlock('elasticsearch/attribute_edit_tab_elasticsearch')->toHtml(),
        ), 'labels');

        return parent::_beforeToHtml();
    }
}
