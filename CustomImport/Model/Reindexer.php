<?php

namespace Malesh\CustomImport\Model;

use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\Indexer\CollectionFactory;

class Reindexer
{
    /** @var \Magento\Indexer\Model\IndexerFactory */
    protected $_indexerFactory;

    /** @var \Magento\Indexer\Model\Indexer\CollectionFactory */
    protected $_indexerCollectionFactory;

    public function __construct(
        IndexerFactory $indexerFactory,
        CollectionFactory $indexerCollectionFactory
    )
    {
        $this->_indexerFactory = $indexerFactory;
        $this->_indexerCollectionFactory = $indexerCollectionFactory;
    }

    public function run()
    {
        $indexerCollection = $this->_indexerCollectionFactory->create()->getAllIds();

        foreach ($indexerCollection as $indexerId) {
            $indexer = $this->_indexerFactory->create();
            $indexer->load($indexerId);

            try { $indexer->reindexAll(); }
            catch (\Exception $e) { }
        }
    }
}
