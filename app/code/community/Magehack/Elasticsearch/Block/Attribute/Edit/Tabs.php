<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tabs
 *
 * @author adrian
 */
Class Magehack_Elasticsearch_Block_Attribute_Edit_Tabs extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs {
	
	protected function _beforeToHtml(){
        $this->addTabAfter('elasticsearch', array(
            'label'     => Mage::helper('catalog')->__('Elasticsearch'),
            'title'     => Mage::helper('catalog')->__('Elasticsearch'),
            'content'   => $this->getLayout()->createBlock('elasticsearch/attribute_edit_tab_elasticsearch')->toHtml(),
        ), 'labels');

        return parent::_beforeToHtml();
    }
}

