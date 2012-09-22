<?php

/**
 * Magento
 *
 *
 * @category    Elasticsearch
 * @package     Magehack_Elasticsearch
 * 
 */

/**
 * Catalog Search Controller
 */
class Magehack_Elasticsearch_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{

	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	protected function _getHelper(){
		if(!isset($this->_helper)){
			$this->_helper = Mage::helper('elasticsearch');
		}
		return $this->_helper;
	}
	
	public function indexAction() {
		$config = Mage::getModel('adminhtml/config_data')->setSection('elasticsearch')->load();

		$this->_getHelper()->remap();
		
		if($this->_getHelper()->isScheduled($config) && $this->_getHelper()->getCronExpr($config)){
			$job_code = Magehack_Elasticsearch_Helper_Data::CRON_JOB_CODE;
			try {
				$schedule = Mage::getModel('cron/schedule');
				$schedule->setJobCode($job_code)
					->setCronExpr($this->_getHelper()->getCronExpr($config))
					->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
					->save();
			} catch (Exception $e) {
				$this->_getHelper()->log(get_class($this)."::adminSystemConfigChange() Error saving cron. Exception:\n\t".$e->getMessage());
				Mage::throwException($e);
			}
			
			Mage::getConfig()->setNode('crontab/jobs/elasticsearch/schedule/cron_expr/', $this->_getHelper()->getCronExpr($config), TRUE);
			Mage::getConfig()->saveCache();
		}
		
		$this->_getHelper()->reindexAll();
		$message = $this->__('Elasticsearch index successfully created');
		Mage::getSingleton('adminhtml/session')->addSuccess($message);
		$this->_redirect('adminhtml/system_config/edit/section/elasticsearch');	
	}

}
