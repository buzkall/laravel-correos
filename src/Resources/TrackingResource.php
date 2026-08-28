<?php

namespace SmartDato\CorreosShipping\Resources;

use Saloon\Http\Response;
use SmartDato\CorreosShipping\Connectors\TrackingConnector;
use SmartDato\CorreosShipping\Data\Tracking\ExpeditionResponseData;
use SmartDato\CorreosShipping\Data\Tracking\ShipmentSearchResponseData;
use SmartDato\CorreosShipping\Requests\Tracking\GetExpeditionRequest;
use SmartDato\CorreosShipping\Requests\Tracking\SearchShipmentRequest;

class TrackingResource
{
    protected ?Response $lastResponse = null;

    public function __construct(
        protected TrackingConnector $connector,
    ) {}

    public function lastResponse(): ?Response
    {
        return $this->lastResponse;
    }

    public function searchShipment(string $shippingCode): ShipmentSearchResponseData
    {
        return ($this->lastResponse = $this->connector->send(new SearchShipmentRequest($shippingCode)))->throw()->dto();
    }

    public function getExpedition(string $expeditionCode): ExpeditionResponseData
    {
        return ($this->lastResponse = $this->connector->send(new GetExpeditionRequest($expeditionCode)))->throw()->dto();
    }
}
