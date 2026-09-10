<?php

namespace Arzcode\LaravelCorreos\Resources;

use Arzcode\LaravelCorreos\Connectors\TrackingConnector;
use Arzcode\LaravelCorreos\Data\Tracking\ExpeditionResponseData;
use Arzcode\LaravelCorreos\Data\Tracking\ShipmentSearchResponseData;
use Arzcode\LaravelCorreos\Requests\Tracking\GetExpeditionRequest;
use Arzcode\LaravelCorreos\Requests\Tracking\SearchShipmentRequest;

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
