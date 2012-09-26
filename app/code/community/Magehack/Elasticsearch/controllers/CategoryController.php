<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
require_once BP.'/app/code/core/Mage/Catalog/controllers/CategoryController.php';

class Magehack_Elasticsearch_CategoryController extends Mage_Catalog_CategoryController
{
	protected function _getListHtml()
	{
    	$layout = $this->getLayout();
        $layout->getUpdate()->load('catalog_category_ajax_view');
        $layout->generateXml()->generateBlocks();
        $output = $layout->getOutput();
        return $output;
  }

	protected function _getLayeredNavHtml()
	{
    	$layout = $this->getLayout();
        $layout->getUpdate()->load('catalog_category_layered_ajax');
        $layout->generateXml()->generateBlocks();
        $output = $layout->getOutput();
        return $output;
  }

	public function viewAction()
  {
    	$oneRequest = $this->getRequest();
		Varien_Profiler::enable();
		Varien_Profiler::start('ajax');
    	if($oneRequest->isXmlHttpRequest()){
    		if ($category = $this->_initCatagory()) {
    			//if($lnav){
    				$this->getResponse()->setBody($this->_getLayeredNavHtml());
    			//}else{
    			//	$this->getResponse()->setBody($this->_getListHtml());
    			//}
    		}
    	}else{
    		parent::viewAction();
    	}
		Varien_Profiler::stop('ajax');
		$profilerFetch = Varien_Profiler::fetch('ajax');
		Mage::log(__METHOD__ . ' profile time: ' . $profilerFetch, Zend_Log::INFO ,'profiler.log');
		Varien_Profiler::disable();
  }
}
