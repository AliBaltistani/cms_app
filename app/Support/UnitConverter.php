<?php

namespace App\Support;

class UnitConverter
{
    public const KG_TO_LBS = 2.20462;

    public static function kgToLbs(?float $kg): ?float
    {
        if ($kg === null) {
            return null;
        }
        return round($kg * self::KG_TO_LBS, 2);
    }

    public static function lbsToKg(?float $lbs): ?float
    {
        if ($lbs === null) {
            return null;
        }
        return round($lbs / self::KG_TO_LBS, 2);
    }
}