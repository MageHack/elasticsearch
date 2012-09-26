<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author adrian
 */
class Magehack_Elasticsearch_Block_Attribute_Edit_Tab_Elasticsearch extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_attribute = null;

    public function setAttributeObject($attribute)
    {
        $this->_attribute = $attribute;
        return $this;
    }

    public function getAttributeObject()
    {
        if (null === $this->_attribute) {
            return Mage::registry('entity_attribute');
        }
        return $this->_attribute;
    }

	/**
     * Adding product form elements for editing attribute
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tab_Main
     */
    protected function _prepareForm()
		{
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post'
        ));

        // frontend properties fieldset
        $fieldset = $form->addFieldset('elasticsearch_props', array('legend'=>Mage::helper('catalog')->__('Elasticsearch Properties')));

        $fieldset->addField('elasticsearch_query_boost', 'text', array(
            'name'     => 'elasticsearch_query_boost',
            'label'    => Mage::helper('catalog')->__('Boost Value (used per query)'),
            'title'    => Mage::helper('catalog')->__('Boost Value (used per query)'),
			'class' => 'validate-number',
        ));

        $fieldset->addField('elasticsearch_custom_map', 'textarea', array(
            'name'     => 'elasticsearch_custom_map',
            'label'    => Mage::helper('catalog')->__('Custom Mapping Options'),
            'title'    => Mage::helper('catalog')->__('Custom Mapping Options'),
			'class'    => 'validate-json',
			'note'     => 'Must be in valid <a href="http://www.json.org/">JSON format</a>. Can be used to override default mapping for this attribute (e.g. set custom analysis).'
        ));

        $this->setForm($form);

        return parent::_prepareForm();
	}

    /**
     * This method is called before rendering HTML
     *
     * @return Mage_Eav_Block_Adminhtml_Attribute_Edit_Main_Abstract
     */
    protected function _beforeToHtml()
		{
        parent::_beforeToHtml();

        $attributeObject = $this->getAttributeObject();
        if ($attributeObject->getId()) {
            $form = $this->getForm();
            $disableAttributeFields = Mage::helper('eav')
                ->getAttributeLockedFields($attributeObject->getEntityType()->getEntityTypeCode());
            if (isset($disableAttributeFields[$attributeObject->getAttributeCode()])) {
                foreach ($disableAttributeFields[$attributeObject->getAttributeCode()] as $field) {
                    if ($elm = $form->getElement($field)) {
                        $elm->setDisabled(1);
                        $elm->setReadonly(1);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Initialize form fileds values
     *
     * @return Mage_Eav_Block_Adminhtml_Attribute_Edit_Main_Abstract
     */
    protected function _initFormValues()
		{
        //Mage::dispatchEvent('adminhtml_block_eav_attribute_edit_form_init', array('form' => $this->getForm()));
        $this->getForm()
            ->addValues($this->getAttributeObject()->getData());
        return parent::_initFormValues();
    }

    /**
     * Processing block html after rendering
     * Adding js block to the end of this block
     *
     * @param   string $html
     * @return  string
     */
    protected function _afterToHtml($html)
		{
        $jsScripts = $this->getLayout()
            ->createBlock('eav/adminhtml_attribute_edit_js')->toHtml();
        return $html.$jsScripts;
    }
}