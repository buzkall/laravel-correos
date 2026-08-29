<?php

namespace SmartDato\CorreosShipping\Resources;

use SmartDato\CorreosShipping\Connectors\TrackingConnector;
use SmartDato\CorreosShipping\Data\Tracking\ExpeditionResponseData;
use SmartDato\CorreosShipping\Data\Tracking\ShipmentSearchResponseData;
use SmartDato\CorreosShipping\Requests\Tracking\GetExpeditionRequest;
use SmartDato\CorreosShipping\Requests\Tracking\SearchShipmentRequest;

class TrackingResource extends CorreosResource
{
    public function __construct(TrackingConnector $connector)
    {
        parent::__construct($connector);
    }

    public function searchShipment(string $shippingCode): ShipmentSearchResponseData
    {
        return $this->send(new SearchShipmentRequest($shippingCode));
    }

    public function getExpedition(string $expeditionCode): ExpeditionResponseData
    {
        return $this->send(new GetExpeditionRequest($expeditionCode));
    }
}
