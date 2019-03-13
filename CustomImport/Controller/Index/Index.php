<?php

namespace Malesh\CustomImport\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Malesh\CustomImport\Provider\CsvProvidersCreatorFactory;
use Malesh\CustomImport\Importer\ImporterFactory;
use Malesh\CustomImport\Config\ConfigImport;
use Malesh\CustomImport\Model\Reindexer;
use Malesh\CustomImport\Model\ConfigurableProductBuilderFactory;

class Index extends Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory;

    /** @var \Malesh\CustomImport\Provider\CsvProvidersCreatorFactory */
    private $csvProvidersCreatorFactory;

    /** @var \Malesh\CustomImport\Importer\ImporterFactory */
    private $importerFactory;

    /** @var \Malesh\CustomImport\Model\Reindexer */
    private $reindexer;

    /** @var \Malesh\CustomImport\Model\ConfigurableProductBuilderFactory */
    private $configurableProductBuilderFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CsvProvidersCreatorFactory $csvProvidersCreatorFactory,
        ImporterFactory $importerFactory,
        Reindexer $reindexer,
        ConfigurableProductBuilderFactory $configurableProductBuilderFactory
    )
    {
        $this->_resultPageFactory = $resultPageFactory;
        $this->csvProvidersCreatorFactory = $csvProvidersCreatorFactory;
        $this->importerFactory = $importerFactory;
        $this->reindexer = $reindexer;
        $this->configurableProductBuilderFactory = $configurableProductBuilderFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        $csvProvidersCreator = $this->csvProvidersCreatorFactory->create([
            'files' => [
                ConfigImport::CATEGORIES_CSV_FILENAME => ConfigImport::VALID_CATEGORIES_COLUMN_NAMES,
                ConfigImport::PRODUCTS_CSV_FILENAME => ConfigImport::VALID_PRODUCTS_COLUMN_NAMES
            ]
        ]);

        if (!$csvProvidersCreator->isError()) {
            $importer = $this->importerFactory->create(['csvProviders' => $csvProvidersCreator])->import();

            //create configurable product
            $configurableCreator = $this->configurableProductBuilderFactory->create([
                'importer' => $importer
            ]);

            $resultPage->getLayout()->getBlock('custom.import')->setConfigurable($configurableCreator);

            //run reindex
            $this->reindexer->run();
        }

        $resultPage->getLayout()->getBlock('custom.import')->setCsvProvidersCreator($csvProvidersCreator);

        return $resultPage;
    }
}
