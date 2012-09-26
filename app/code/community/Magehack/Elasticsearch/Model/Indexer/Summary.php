<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Indexer_Summary extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('elasticsearch/indexer_summary');
    }

    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('elasticsearch')->__('Elasticsearch Data');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('elasticsearch')->__('Rebuild Elasticsearch data');
    }

    /**
     * Register data required by process in event object
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        if ($event->getEntity() == Mage_Catalog_Model_Product::ENTITY) {
            $this->_registerCatalogProduct($event);
        } elseif ($event->getEntity() == Mage_Cms_Model_Page::ENTITY) {
            $this->_registerPage($event);
        }
    }

    /**
     * Register data required by catalog product save process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerCatalogProductSaveEvent(Mage_Index_Model_Event $event)
    {
        /* @var $product Mage_Catalog_Model_Product */
        $product = $event->getDataObject();
        $reindexTag = $product->getForceReindexRequired();

        foreach ($this->_getProductAttributesDependOn() as $attributeCode) {
            $reindexTag = $reindexTag || $product->dataHasChangedFor($attributeCode);
        }

        if (!$product->isObjectNew() && $reindexTag) {
            $event->addNewData('elasticsearch_reindex_required', true);
        }
    }

    /**
     * Register data required by CMS page save process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerPageSaveEvent(Mage_Index_Model_Event $event)
    {
        /* @var $page Mage_Cms_Model_Page */
        $page = $event->getDataObject();

        if (!$page->isObjectNew()) {
            $event->addNewData('elasticsearch_reindex_required', true);
        }
    }

    /**
     * Register data required by catalog product delete process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerCatalogProductDeleteEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData('elasticsearch_reindex_product_ids', $event->getEntityPk());
    }

    /**
     * Register data required by CMS page delete process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerPageDeleteEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData('elasticsearch_reindex_page_ids', $event->getEntityPk());
    }

    /**
     * Register data required by catalog product massaction process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerCatalogProductMassActionEvent(Mage_Index_Model_Event $event)
    {
        /* @var $actionObject Varien_Object */
        $actionObject = $event->getDataObject();
        $attributes   = $this->_getProductAttributesDependOn();
        $reindexTags  = false;

        // check if attributes changed
        $attrData = $actionObject->getAttributesData();
        if (is_array($attrData)) {
            foreach ($attributes as $attributeCode) {
                if (array_key_exists($attributeCode, $attrData)) {
                    $reindexTags = true;
                    break;
                }
            }
        }

        // check changed websites
        if ($actionObject->getWebsiteIds()) {
            $reindexTags = true;
        }

        // register affected tags
        if ($reindexTags) {
            $event->addNewData('elasticsearch_reindex_product_ids', $actionObject->getProductIds());
        }
    }

    /**
     * Register data required by CMS page massaction process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerPageActionEvent(Mage_Index_Model_Event $event)
    {
        /* @var $actionObject Varien_Object */
        $actionObject = $event->getDataObject();

        // check changed websites
        if ($actionObject->getWebsiteIds()) {
            $reindexTags = true;
        }

        // register affected pages
        if ($reindexTags) {
            $event->addNewData('elasticsearch_reindex_page_ids', $actionObject->getEntityIds());
        }
    }

    protected function _registerCatalogProduct(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                $this->_registerCatalogProductSaveEvent($event);
                break;

            case Mage_Index_Model_Event::TYPE_DELETE:
                $this->_registerCatalogProductDeleteEvent($event);
                break;

            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                $this->_registerCatalogProductMassActionEvent($event);
                break;
        }
    }

    protected function _registerPage(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                $this->_registerPageSaveEvent($event);
                break;

            case Mage_Index_Model_Event::TYPE_DELETE:
                $this->_registerPageDeleteEvent($event);
                break;

            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                $this->_registerPageMassActionEvent($event);
                break;
        }
    }

    /**
     * Process event
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $this->callEventHandler($event);
    }
}
