<?php

namespace Malesh\CustomImport\Provider;

class CsvProvidersCreator
{
    /** @var \Malesh\CustomImport\Provider\CsvProviderFactory */
    private $csvProviderFactory;

    /** @var array */
    private $files;

    /** @var array */
    private $error;

    /** @var array */
    private $providers;

    public function __construct(CsvProviderFactory $csvProviderFactory, $files)
    {
        $this->csvProviderFactory = $csvProviderFactory;
        $this->files = $files;
        $this->initProviders();
    }

    public function isError()
    {
        return (bool) $this->error;
    }

    public function generateMessage()
    {
        switch ($this->error['error_code']) {
            case 1:
                $m = 'File "'. $this->error['file_name'] .'" doesn\'t exist !';
                break;
            case 2:
                $m = 'File "'. $this->error['file_name'] .'" isn\'t valid !';
                break;
            default:
                $m = 'The Files were successfully imported !';
        }

        return $m;
    }

    public function getProviders()
    {
        return $this->providers;
    }

    private function initProviders()
    {
        foreach ($this->files as $fileName => $validColumns) {
            $item = $this->csvProviderFactory->create([
                'fileName' => $fileName,
                'validColumns' => $validColumns
            ]);

            if ($errorCode = $item->getErrorCode()) {
                $this->setError($errorCode, $fileName);
                break;
            }

            $this->providers[$fileName] = $item;
        }

        return $this;
    }

    private function setError($errorCode, $fileName)
    {
        $this->error = ['error_code' => $errorCode, 'file_name' => $fileName];
    }

}