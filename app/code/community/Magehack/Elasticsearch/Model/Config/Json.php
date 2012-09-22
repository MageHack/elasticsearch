<?php

/**
 * @deprecated
 */

/**
 * Description of Json
 *
 * @author adrian
 */
class Magehack_Elasticsearch_Model_Config_Json extends Mage_Core_Model_Config_Data {

	protected function _afterSave() {
		if($this->getValue() && json_decode($this->getValue()) === NULL){
			$error = NULL;
			if (strnatcmp(phpversion(),'5.3.0') >= 0){
				switch (json_last_error()){
					case JSON_ERROR_NONE:
						$error = ' - No errors';
						break;
					case JSON_ERROR_DEPTH:
						$error = ' - Maximum stack depth exceeded';
						break;
					case JSON_ERROR_STATE_MISMATCH:
						$error = ' - Underflow or the modes mismatch';
						break;
					case JSON_ERROR_CTRL_CHAR:
						$error = ' - Unexpected control character found';
						break;
					case JSON_ERROR_SYNTAX:
						$error = ' - Syntax error, malformed JSON';
						break;
					case JSON_ERROR_UTF8:
						$error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
						break;
					default:
						$error = ' - Unknown error';
						break;
				}
			}
			Mage::throwException("Error passing JSON string". $error);
		}
		parent::_afterSave();
	}
}

