<?php

namespace App\Services;

use App\Models\Drivers\Driver;
use App\Models\GeneralSetting\GeneralSetting;
use App\Models\ShipmentInvoices\ShipmentInvoice;
use App\Models\Shipments\Shipment;
use App\Models\VehicleType\VehicleType;

class ShipmentInvoiceService
{
    /**
     * Full real-industry cost formula:
     *
     *   fuel_cost        = (miles / mpg) × fuel_price_per_gallon
     *   driver_cost      = driver_rate_per_mile × miles
     *   insurance_cost   = insurance_per_mile × miles
     *   maintenance_cost = maintenance_per_mile × miles
     *   overhead_cost    = overhead_per_mile × miles
     *   subtotal         = fuel + driver + insurance + maintenance + overhead + tolls
     *   customer_price   = subtotal × (1 + company_profit% / 100)
     *
     * Driver rate: uses driver.pay_rate if set, otherwise vehicleType.driver_cost_per_mile
     */
    public function createInvoice(Shipment $shipment, float $tollsFee = 0): ShipmentInvoice
    {
        $settings         = GeneralSetting::first();
        $fuelPrice        = (float) ($settings->fuel_price ?? 0);
        $profitPercentage = (float) ($settings->company_profit ?? 0);

        $vehicleType        = VehicleType::find($shipment->vehicle_type_id);
        $milesPerGallon     = (float) ($vehicleType->avg_fuel_efficiency ?? 0);
        $driverPayRate      = (float) ($vehicleType->driver_cost_per_mile ?? 0);
        $insurancePerMile   = (float) ($vehicleType->insurance_per_mile ?? 0.10);
        $maintenancePerMile = (float) ($vehicleType->maintenance_per_mile ?? 0.15);
        $overheadPerMile    = (float) ($vehicleType->overhead_per_mile ?? 0.10);
        $iftaPerMile        = (float) ($vehicleType->ifta_per_mile ?? 0.05);

        // Driver personal rate overrides vehicle type default
        if ($shipment->driver_id) {
            $driver = Driver::find($shipment->driver_id);
            if ($driver && $driver->pay_rate > 0) {
                $driverPayRate = (float) $driver->pay_rate;
            }
        }

        $miles = (float) ($shipment->distance_miles ?? 0);

        if ($milesPerGallon <= 0) {
            throw new \Exception(
                'Vehicle type "' . ($vehicleType->vehicle_type ?? 'unknown') . '" is missing avg fuel efficiency (MPG). Please set it in Vehicle Types.'
            );
        }

        // ── Accessorial charges from shipment ───────────────────────────────
        $deadheadMiles  = (float) ($shipment->deadhead_miles ?? 0);
        $detentionHours = (float) ($shipment->detention_hours ?? 0);
        $lumperFee      = (float) ($shipment->lumper_fee ?? 0);
        $perDiemDays    = (int)   ($shipment->per_diem_days ?? 0);
        $scaleFees      = (float) ($shipment->scale_fees ?? 0);
        $tarpFee        = $shipment->tarp_required ? 100.00 : 0;
        $permitFee      = (float) ($shipment->permit_fee ?? 0);

        $deadheadCost   = $deadheadMiles * 0.75;   // $0.75/deadhead mile
        $detentionCost  = $detentionHours * 65.00;  // $65/hr after free time
        $perDiemCost    = $perDiemDays * 65.00;     // $65/day driver per diem

        // ── Cost calculation ────────────────────────────────────────────────
        $fuelCost        = ($miles / $milesPerGallon) * $fuelPrice;
        $driverCost      = $driverPayRate * $miles;
        $insuranceCost   = $insurancePerMile * $miles;
        $maintenanceCost = $maintenancePerMile * $miles;
        $overheadCost    = $overheadPerMile * $miles;
        $iftaCost        = $iftaPerMile * $miles;

        $accessorialTotal = $deadheadCost + $detentionCost + $lumperFee + $perDiemCost + $scaleFees + $tarpFee + $permitFee;

        $subtotal        = $fuelCost + $driverCost + $insuranceCost + $maintenanceCost + $overheadCost + $iftaCost + $tollsFee + $accessorialTotal;
        $totalWithProfit = $subtotal * (1 + ($profitPercentage / 100));
        // ────────────────────────────────────────────────────────────────────

        $shipment->update(['estimated_cost' => round($totalWithProfit, 2)]);

        $invoiceData = [
            'miles_per_gallon'     => $milesPerGallon,
            'fuel_price'           => $fuelPrice,
            'fuel_cost'            => round($fuelCost, 2),
            'driver_pay'           => $driverPayRate,
            'driver_cost'          => round($driverCost, 2),
            'insurance_per_mile'   => $insurancePerMile,
            'insurance_cost'       => round($insuranceCost, 2),
            'maintenance_per_mile' => $maintenancePerMile,
            'maintenance_cost'     => round($maintenanceCost, 2),
            'overhead_per_mile'    => $overheadPerMile,
            'overhead_cost'        => round($overheadCost, 2),
            'ifta_per_mile'        => $iftaPerMile,
            'ifta_cost'            => round($iftaCost, 2),
            'tolls_fee'            => round($tollsFee, 2),
            'deadhead_cost'        => round($deadheadCost, 2),
            'detention_cost'       => round($detentionCost, 2),
            'lumper_fee'           => round($lumperFee, 2),
            'per_diem_cost'        => round($perDiemCost, 2),
            'scale_fees'           => round($scaleFees, 2),
            'tarp_fee'             => round($tarpFee, 2),
            'permit_fee'           => round($permitFee, 2),
            'accessorial_total'    => round($accessorialTotal, 2),
            'profit_percentage'    => $profitPercentage,
            'total_cost'           => round($subtotal, 2),
            'total_with_profit'    => round($totalWithProfit, 2),
            'invoice_note'         => "Auto-generated invoice for shipment #{$shipment->id}",
        ];

        $invoice = ShipmentInvoice::where('shipment_id', $shipment->id)->first();

        if ($invoice) {
            $invoice->update($invoiceData);
            return $invoice;
        }

        return ShipmentInvoice::create(array_merge($invoiceData, [
            'shipment_id' => $shipment->id,
        ]));
    }
}
