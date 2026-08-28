<?php

namespace SmartDato\CorreosShipping\Resources;

use SmartDato\CorreosShipping\Connectors\PreregisterConnector;
use SmartDato\CorreosShipping\Data\Preregister\AnnulmentExpeditionRequestData;
use SmartDato\CorreosShipping\Data\Preregister\AnnulmentRequestData;
use SmartDato\CorreosShipping\Data\Preregister\AnnulmentResponseData;
use SmartDato\CorreosShipping\Data\Preregister\BackofficeResponseData;
use SmartDato\CorreosShipping\Data\Preregister\CnDeliveryResponseData;
use SmartDato\CorreosShipping\Data\Preregister\DeliveryRequestData;
use SmartDato\CorreosShipping\Data\Preregister\DeliveryResponseData;
use SmartDato\CorreosShipping\Data\Preregister\GenerateExpeditionResponseData;
use SmartDato\CorreosShipping\Data\Preregister\GenerateShipmentCodeRequestData;
use SmartDato\CorreosShipping\Data\Preregister\LabelsInfoResponseData;
use SmartDato\CorreosShipping\Data\Preregister\ModifyResponseData;
use SmartDato\CorreosShipping\Data\Preregister\PackageExpeditionResponseData;
use SmartDato\CorreosShipping\Data\Preregister\PackageReferenceResponseData;
use SmartDato\CorreosShipping\Data\Preregister\QueryRequestData;
use SmartDato\CorreosShipping\Data\Preregister\QueryResponseData;
use SmartDato\CorreosShipping\Data\Preregister\SearchLabelsInfoRequestData;
use SmartDato\CorreosShipping\Requests\Preregister\CancelExpeditionRequest;
use SmartDato\CorreosShipping\Requests\Preregister\CancelShipmentRequest;
use SmartDato\CorreosShipping\Requests\Preregister\CreateCnShipmentsRequest;
use SmartDato\CorreosShipping\Requests\Preregister\CreateShipmentsRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GenerateShipmentCodeRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GetBackofficeErrorsRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GetBackofficeShipmentRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GetBackofficeTotalRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GetBackofficeWaitingRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GetExpeditionPackagesRequest;
use SmartDato\CorreosShipping\Requests\Preregister\GetPackagesByReferenceRequest;
use SmartDato\CorreosShipping\Requests\Preregister\ModifyShipmentRequest;
use SmartDato\CorreosShipping\Requests\Preregister\QueryShipmentsIrisRequest;
use SmartDato\CorreosShipping\Requests\Preregister\QueryShipmentsRequest;
use SmartDato\CorreosShipping\Requests\Preregister\SearchLabelsInfoRequest;
use SmartDato\CorreosShipping\Requests\Preregister\ValidateShipmentsRequest;

class PreregisterResource extends CorreosResource
{
    public function __construct(PreregisterConnector $connector)
    {
        parent::__construct($connector);
    }

    public function validateShipments(DeliveryRequestData $data): DeliveryResponseData
    {
        return $this->send(new ValidateShipmentsRequest($data));
    }

    public function createShipments(DeliveryRequestData $data): DeliveryResponseData
    {
        return $this->send(new CreateShipmentsRequest($data));
    }

    public function createCnShipments(DeliveryRequestData $data): CnDeliveryResponseData
    {
        return $this->send(new CreateCnShipmentsRequest($data));
    }

    public function queryShipments(QueryRequestData $data): QueryResponseData
    {
        return $this->send(new QueryShipmentsRequest($data));
    }

    public function queryShipmentsIris(QueryRequestData $data): QueryResponseData
    {
        return $this->send(new QueryShipmentsIrisRequest($data));
    }

    public function modifyShipment(DeliveryRequestData $data): ModifyResponseData
    {
        return $this->send(new ModifyShipmentRequest($data));
    }

    public function cancelShipment(AnnulmentRequestData $data): AnnulmentResponseData
    {
        return $this->send(new CancelShipmentRequest($data));
    }

    public function cancelExpedition(AnnulmentExpeditionRequestData $data): AnnulmentResponseData
    {
        return $this->send(new CancelExpeditionRequest($data));
    }

    public function generateShipmentCode(GenerateShipmentCodeRequestData $data): GenerateExpeditionResponseData
    {
        return $this->send(new GenerateShipmentCodeRequest($data));
    }

    public function getExpeditionPackages(string $expeditionCode): PackageExpeditionResponseData
    {
        return $this->send(new GetExpeditionPackagesRequest($expeditionCode));
    }

    public function getPackagesByReference(string $clientReference, ?string $contractNumber = null, ?string $clientNumber = null): PackageReferenceResponseData
    {
        return $this->send(new GetPackagesByReferenceRequest($clientReference, $contractNumber, $clientNumber));
    }

    public function searchLabelsInfo(SearchLabelsInfoRequestData $data): LabelsInfoResponseData
    {
        return $this->send(new SearchLabelsInfoRequest($data));
    }

    public function getBackofficeShipment(string $shipmentCode): BackofficeResponseData
    {
        return $this->send(new GetBackofficeShipmentRequest($shipmentCode));
    }

    public function getBackofficeErrors(?string $contractNumber = null, ?string $clientNumber = null, ?string $dateFrom = null, ?string $dateTo = null): BackofficeResponseData
    {
        return $this->send(new GetBackofficeErrorsRequest($contractNumber, $clientNumber, $dateFrom, $dateTo));
    }

    public function getBackofficeTotal(?string $contractNumber = null, ?string $clientNumber = null, ?string $dateFrom = null, ?string $dateTo = null): BackofficeResponseData
    {
        return $this->send(new GetBackofficeTotalRequest($contractNumber, $clientNumber, $dateFrom, $dateTo));
    }

    public function getBackofficeWaiting(?string $contractNumber = null, ?string $clientNumber = null, ?string $dateFrom = null, ?string $dateTo = null): BackofficeResponseData
    {
        return $this->send(new GetBackofficeWaitingRequest($contractNumber, $clientNumber, $dateFrom, $dateTo));
    }
}
