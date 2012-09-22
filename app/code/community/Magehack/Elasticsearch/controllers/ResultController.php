<?php
require_once BP.'/app/code/core/Mage/CatalogSearch/controllers/ResultController.php';
class Magehack_Elasticsearch_ResultController extends Mage_CatalogSearch_ResultController{
	public function indexAction(){
		$oneRequest = $this->getRequest();
		$query = Mage::helper('catalogsearch')->getQuery();
        /* @var $query Mage_CatalogSearch_Model_Query */

        $query->setStoreId(Mage::app()->getStore()->getId());

        if ($query->getQueryText()) {
            if (Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->setId(0)
                    ->setIsActive(1)
                    ->setIsProcessed(1);
            }
            else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity()+1);
                }
                else {
                    $query->setPopularity(1);
                }

                if ($query->getRedirect()){
                    $query->save();
                    $this->getResponse()->setRedirect($query->getRedirect());
                    return;
                }
                else {
                    $query->prepare();
                }
            }

            Mage::helper('catalogsearch')->checkNotes();
            if($oneRequest->isXmlHttpRequest()){
				$this->getResponse()->setBody($this->_getAjaxSearchResult());
	    			
	    	}else{
	    		$this->loadLayout();
	            $this->_initLayoutMessages('catalog/session');
	            $this->_initLayoutMessages('checkout/session');
	            $this->renderLayout();
	    	}

            if (!Mage::helper('catalogsearch')->isMinQueryLength()) {
                $query->save();
            }
        }
        else {
            $this->_redirectReferer();
        }
	}
	protected function _getAjaxSearchResult(){
		$layout = $this->getLayout();
        $layout->getUpdate()->load('catalogsearch_result_ajax');
        $layout->generateXml()->generateBlocks();
        $output = $layout->getOutput();
        return $output;
	}
}