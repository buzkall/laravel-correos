<?php

namespace SmartDato\CorreosShipping\Resources;

use SmartDato\CorreosShipping\Connectors\LabelsConnector;
use SmartDato\CorreosShipping\Data\Labels\DocumentBackofficeResponseData;
use SmartDato\CorreosShipping\Data\Labels\DocumentResponseData;
use SmartDato\CorreosShipping\Data\Labels\LabelsResponseData;
use SmartDato\CorreosShipping\Data\Labels\PrintDocumentsRequestData;
use SmartDato\CorreosShipping\Data\Labels\PrintLabelsRequestData;
use SmartDato\CorreosShipping\Requests\Labels\GetDocumentBackofficeRequest;
use SmartDato\CorreosShipping\Requests\Labels\PrintDocumentsRequest;
use SmartDato\CorreosShipping\Requests\Labels\PrintLabelsRequest;

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
