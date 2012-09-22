<?php 
class Magehack_Elasticsearch_Block_Adminhtml_Run extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('elasticsearch/adminhtml_index/index');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel('Start Indexing Process')
                    ->setOnClick("setLocation('$url')")
                    ->toHtml();
        return $html;
    }
}