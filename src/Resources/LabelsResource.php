<?php

namespace Arzcode\LaravelCorreos\Resources;

use Arzcode\LaravelCorreos\Connectors\LabelsConnector;
use Arzcode\LaravelCorreos\Data\Labels\DocumentBackofficeResponseData;
use Arzcode\LaravelCorreos\Data\Labels\DocumentResponseData;
use Arzcode\LaravelCorreos\Data\Labels\LabelsResponseData;
use Arzcode\LaravelCorreos\Data\Labels\PrintDocumentsRequestData;
use Arzcode\LaravelCorreos\Data\Labels\PrintLabelsRequestData;
use Arzcode\LaravelCorreos\Requests\Labels\GetDocumentBackofficeRequest;
use Arzcode\LaravelCorreos\Requests\Labels\PrintDocumentsRequest;
use Arzcode\LaravelCorreos\Requests\Labels\PrintLabelsRequest;

class LabelsResource extends CorreosResource
{
    public function __construct(LabelsConnector $connector)
    {
        parent::__construct($connector);
    }

    public function printLabels(PrintLabelsRequestData $data): LabelsResponseData
    {
        return $this->send(new PrintLabelsRequest($data));
    }

    public function printDocuments(PrintDocumentsRequestData $data): DocumentResponseData
    {
        return $this->send(new PrintDocumentsRequest($data));
    }

    public function getDocumentBackoffice(string $shipment): DocumentBackofficeResponseData
    {
        return $this->send(new GetDocumentBackofficeRequest($shipment));
    }
}
