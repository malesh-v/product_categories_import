<?php

namespace Malesh\CustomImport\Block;

use Magento\Framework\View\Element\Template;

class ImportBlock extends Template
{
    public function getMessage()
    {
        return $this->getCsvProvidersCreator()->generateMessage();
    }

    public function getFinishedStatus()
    {
        return $this->getCsvProvidersCreator()->isError() ? 'error' : 'success';
    }
}
