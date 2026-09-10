<?php

namespace Arzcode\LaravelCorreos\Resources;

use Arzcode\LaravelCorreos\Connectors\PreregisterConnector;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentExpeditionRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\AnnulmentResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\BackofficeResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\CnDeliveryResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\DeliveryRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\DeliveryResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\GenerateExpeditionResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\GenerateShipmentCodeRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\LabelsInfoResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\ModifyResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\PackageExpeditionResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\PackageReferenceResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\QueryRequestData;
use Arzcode\LaravelCorreos\Data\Preregister\QueryResponseData;
use Arzcode\LaravelCorreos\Data\Preregister\SearchLabelsInfoRequestData;
use Arzcode\LaravelCorreos\Requests\Preregister\CancelExpeditionRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\CancelShipmentRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\CreateCnShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\CreateShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GenerateShipmentCodeRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetBackofficeErrorsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetBackofficeShipmentRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetBackofficeTotalRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetBackofficeWaitingRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetExpeditionPackagesRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\GetPackagesByReferenceRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\ModifyShipmentRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\QueryShipmentsIrisRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\QueryShipmentsRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\SearchLabelsInfoRequest;
use Arzcode\LaravelCorreos\Requests\Preregister\ValidateShipmentsRequest;

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
