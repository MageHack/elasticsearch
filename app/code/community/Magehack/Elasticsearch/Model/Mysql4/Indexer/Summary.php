<?php
/**
 * Elasticsearch Indexer Model
 *
 * @category    MageHack
 * @package     MageHack_Elasticsearch
 * @author      MageHack Elasticsearch Team <git@magehack.com>
 */
class Magehack_Elasticsearch_Model_Mysql4_Indexer_Summary extends Mage_Catalog_Model_Resource_Product_Indexer_Abstract
{
    /**
     * Define main table
     *
     */
    protected function _construct()
    {
        $this->_init('elasticsearch/summary', 'queue_item_id');
    }

    /**
     * Process product save.
     * Method is responsible for index support when product was saved.
     *
     * @param Mage_Index_Model_Event $event
     * @return Magehack_Elasticsearch_Model_Resource_Indexer_Summary
     */
    public function catalogProductSave(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (empty($data['elasticsearch_reindex_required'])) {
            return $this;
        }

        return $this->aggregate($event->getEntityPk());
    }

    /**
     * Process product delete.
     * Method is responsible for index support when product was deleted
     *
     * @param Mage_Index_Model_Event $event
     * @return Magehack_Elasticsearch_Model_Resource_Indexer_Summary
     */
    public function catalogProductDelete(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (empty($data['elasticsearch_reindex_product_ids'])) {
            return $this;
        }
        return $this->aggregate($data['elasticsearch_reindex_product_ids']);
    }

    /**
     * Process product massaction
     *
     * @param Mage_Index_Model_Event $event
     * @return Magehack_Elasticsearch_Model_Resource_Indexer_Summary
     */
    public function catalogProductMassAction(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (empty($data['elasticsearch_reindex_product_ids'])) {
            return $this;
        }
        return $this->aggregate($data['elasticsearch_reindex_product_ids']);
    }

    /**
     * Reindex all data
     *
     * @return Magehack_Elasticsearch_Model_Resource_Indexer_Summary
     */
    public function reindexAll()
    {
        return $this->aggregate();
    }

    /**
     * Aggregate entities by specified ids
     *
     * @param null|int|array $entityIds
     * @return Magehack_Elasticsearch_Model_Resource_Indexer_Summary
     */
    public function aggregate($entityIds = null)
    {
        $helper = Mage::helper('elasticsearch')->remapReindexAll();

        return $this;
    }
}
