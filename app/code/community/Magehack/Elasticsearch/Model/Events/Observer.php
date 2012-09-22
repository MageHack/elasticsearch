<?php
/**
 * Observer model
 *
 * @category    GPMD
 * @package     Magehack_Elasticsearch
 * @copyright
 * @license
 */

/**
 *
 * @category    GPMD
 * @package     Magehack_Elasticsearch
 * @author      Carlo Tasca <dev@gpmd.net>
 */
class Magehack_Elasticsearch_Model_Events_Observer {
	/**
	 * Inject 'Use in Autosuggest' field to add/edit attribute form
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function adminAttributeEdit(Varien_Event_Observer $observer){
		$form = $observer->getEvent()->getForm();
		if($form){
			$fieldset = $form->getElement('front_fieldset');
			$fieldset->addField('use_in_autosuggest', 'select', array(
				'name'     => 'use_in_autosuggest',
				'label'    => Mage::helper('catalog')->__('Use in Autosuggest'),
				'title'    => Mage::helper('catalog')->__('Use in Autosuggest'),
				'values'   => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
				'note'	   => Mage::helper('catalog')->__('Used in Elasticsearch, attribute is used for search box auto-suggestions'),
			));
		}
	}

	/**
	 * Re-index elasticsearch when any other reindex process occurs
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function reindex(Varien_Event_Observer $observer){
		Mage::helper('elasticsearch')->reindexAll();
	}
}