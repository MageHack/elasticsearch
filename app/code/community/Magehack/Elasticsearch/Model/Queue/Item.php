<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Queue_Item extends Mage_Core_Model_Abstract
{
	const MESSAGE_UNPUBLISHED = 'unpublished';
	const MESSAGE_CHANGED = 'changed';

	public function _construct()
	{
		$this->_init('elasticsearch/queue_item');
	}

	/**
	 * Returns true if the product should be updated
	 *
	 * @return type
	 */
	public function isMessageChanged()
	{
		return ($this->getData('message') == self::MESSAGE_CHANGED);
	}

	/**
	 * Returns true if the product should be unpublished
	 *
	 * @return type
	 */
	public function isMessageUnpublished()
	{
		return ($this->getData('message') == self::MESSAGE_UNPUBLISHED);
	}

	/**
	 * Get real model associated with this item (if stil exists)
	 *
	 * @return type
	 */
	public function getModel()
	{
		$modelId = $this->getData('model_id');
		Mage::helper('elasticsearch')->log('Item model id: ' . $modelId);
		$modelClass = (string) $this->getModelClass();
		Mage::helper('elasticsearch')->log('Item model class: ' . var_export($modelClass, true));
		$item = Mage::getModel($modelClass)->load($modelId);

		if ($item->getId()) {
			return $item;
		}

		return FALSE;
	}

	/**
	 * Mark queue item as processed
	 */
	public function markAsProcessed()
	{
		$this->setData('processed', 1);
		$this->save();
	}
}