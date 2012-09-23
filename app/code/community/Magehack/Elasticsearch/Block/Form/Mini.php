<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GPMD_ElasticSearch_Block_Form_Mini
 *
 * @author User
 */
class Magehack_Elasticsearch_Block_Form_Mini extends Mage_Core_Block_Template {
	
	public function __construct() {
		parent::__construct();
		$this->helper('elasticsearch')->log('GPMD_Elasticsearch_Block_Form_Mini::__construct invokation');
	}
}

?>
