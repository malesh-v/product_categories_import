<?php

namespace Malesh\CustomImport\Provider;

use Magento\Framework\File\Csv;

class CsvProvider
{
    /** @var \Magento\Framework\File\Csv */
    private $csvReader;

    /** @var string */
    private $filePath;

    /** @var integer */
    private $errorCode;

    /** @var array */
    private $data;

    /** @var array */
    private $validColumns;

    public function __construct(Csv $csv, $fileName, $validColumns)
    {
        $this->filePath = BP . '/' . $fileName;
        $this->csvReader = $csv;
        $this->validColumns = $validColumns;
        $this->setDataFromCsv();
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getPreparedData ()
    {
        return $this->getErrorCode() ? [] : $this->data;
    }

    private function setDataFromCsv() {
        if ($this->validateCsv()) { $this->prepareData(); }

        return $this;
    }

    private function validateCsv()
    {
        $filePath = $this->filePath;
        if (file_exists($filePath)) {
            $dataCsv = $this->csvReader->getData($filePath);
        } else {
            $this->errorCode = 1;
            return false;
        }

        if (count($dataCsv) < 2 || !$this->checkColumnNames($dataCsv[0])) {
            $this->errorCode = 2;
            return false;
        }
        $this->data = $dataCsv;

        return true;
    }

    private function checkColumnNames($names)
    {
        $this->validColumns;
        $result = true;

        if (in_array('', $names, true)) { return false; }

        foreach ($this->validColumns as $value) {
            if (!in_array($value, $names, true)){
                $result = false;
                break;
            }
        }

        return $result ?: false;
    }

    private function prepareData()
    {
        $preparedData = [];
        $header = array_shift($this->data);

        foreach ($this->data as $key => $value) {
            $preparedData[] = array_combine($header, $value);
        }

        $this->data = $preparedData;

        return $this;
    }
}
