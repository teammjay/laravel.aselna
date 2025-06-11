<?php

namespace App\Utils\shipment;


use App\Constants\ImageRotate;
use App\Constants\PrintStatus;
use App\Constants\ShippingDocumentType;
use App\Constants\ShippingLabelFormat;
use App\Models\OrderOperation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShipmentDocumentsCreator
{
    private $storage;
    private $shipment;
    private $labelFormat;
    private $imageRotate;
    private $labelDocumentBuilder = null;
    private $packingSlipDocumentBuilder = null;

    public function __construct(OrderOperation $shipment)
    {
        $this->shipment = $shipment;
        $this->storage = Storage::disk('local');

        $shipFromDocs = $this->getDocumentsSettings($shipment);
        Log::info('Shipment Documents Creator :: ' . json_encode($shipFromDocs));
        $labelDoc = $shipFromDocs[0];
        $packingSlipDoc = $shipFromDocs[1];

//        $useDazzle = $shipment->shippingAccount->shippingSolution->isEndiciaDazzle();
        $this->labelFormat = $labelDoc->printer->labelFormat;
        $this->imageRotate = $labelDoc->rotate;

        if ($shipment->print_to_file) {
            $this->labelFormat = ShippingLabelFormat::JPEG;
        }
//
        if ($this->labelFormat == ShippingLabelFormat::PDF || $this->labelFormat == ShippingLabelFormat::JPEG) {
            $this->imageRotate = ImageRotate::None;
        }
//
//        if ($useDazzle || $shipment->print_to_file) {
//            $this->labelDocumentBuilder = new SimpleShipmentDocumentBuilder(
//                $labelDoc,
//                $shipment->print_to_file ? strtolower($this->labelFormat) : 'txt'
//            );
//        } else {
//            $this->labelDocumentBuilder = ShipmentDocumentFactory::create($labelDoc);
//        }
//
//        if ($packingSlipDoc) {
//            if ($labelDoc->is($packingSlipDoc)) {
//                if ($useDazzle) {
//                    throw new \RuntimeException("Dazzle in use packing slip cannot be printed with dazzle.");
//                }
//                $this->packingSlipDocumentBuilder = $this->labelDocumentBuilder;
//            } else {
//                $this->packingSlipDocumentBuilder = ShipmentDocumentFactory::create($packingSlipDoc);
//            }
//        }
    }

//    public function writeLabel(LabelResponse $labelResponse, string $trackingNumber, ?string $extension = null)
//    {
//        if ($this->labelDocumentBuilder) {
//            if ($extension) {
//                $this->labelDocumentBuilder->setExtension($extension);
//            }
//            $this->labelDocumentBuilder->setName($trackingNumber);
//            $this->labelDocumentBuilder->writeLabel($labelResponse->getData());
//        }
//    }

//    public function writePackingSlip()
//    {
//        if ($this->packingSlipDocumentBuilder) {
//            if (!$this->packingSlipDocumentBuilder->getName()) {
//                $this->packingSlipDocumentBuilder->setName("packingSlip" . $this->shipment->id);
//            }
//            $this->packingSlipDocumentBuilder->writePackingSlip($this->shipment);
//        }
//    }

    public function getLabelFormat()
    {
        return $this->labelFormat;
    }

    public function buildDocuments(): array
    {
//        $res = [null, null];
//
//        if ($this->labelDocumentBuilder) {
//            $res[0] = $this->createShipmentDocument($this->shipment, $this->labelDocumentBuilder);
//            if ($this->shipment->shippingAccount->shippingSolution->isEndiciaDazzle()) {
//                $res[0]->print_status = PrintStatus::Printed;
//            }
//        }
//
//        if ($this->packingSlipDocumentBuilder &&
//            (!$this->labelDocumentBuilder || !$this->packingSlipDocumentBuilder->equals($this->labelDocumentBuilder))) {
//            $res[1] = $this->createShipmentDocument($this->shipment, $this->packingSlipDocumentBuilder);
//        }
//
//        return $res;
    }

//    private function createShipmentDocument(OrderOperation $shipment, $docBuilder): ShipmentDocument
//    {
//        $doc = new ShipmentDocument();
//
//        $setting = $docBuilder->getDocumentSetting();
//        $printer = $setting->printer;
//
//        $doc->client()->associate($shipment->client);
//        $doc->shipment()->associate($shipment);
//        $doc->document = $docBuilder->build($this->storage);
//        $doc->document_setting()->associate($setting);
//
//        if ($printer->is_pdf_printer || $shipment->print_to_file) {
//            $doc->print_status = PrintStatus::Printed;
//        } else {
//            $doc->print_status = PrintStatus::Pending;
//        }
//
//        return $doc;
//    }

    public static function getDocumentsSettings(OrderOperation $shipment): array
    {
        $res = [null, null];
        $labelDoc = null;
        $packingSlipDoc = null;

        foreach ($shipment->shipFrom->documents as $docSetting) {
            $docType = $docSetting->document_type;

            if (!$docSetting->enabled) {
                continue;
            }

            if ($docType == ShippingDocumentType::Label || $docType == ShippingDocumentType::LabelWithPackingSlip) {
                $labelDoc = $docSetting;
            }
            if ($docType == ShippingDocumentType::PackingSlip || $docType == ShippingDocumentType::LabelWithPackingSlip) {
                $packingSlipDoc = $docSetting;
            }
        }

        $res[0] = $labelDoc;
        $res[1] = $packingSlipDoc;

        return $res;
    }

    public function getImageRotate()
    {
        return $this->imageRotate;
    }
}
