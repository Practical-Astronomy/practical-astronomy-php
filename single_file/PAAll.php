<?php

namespace PA\Binary {

    use PA\Data\Binary\BinaryDataManager;

    use function PA\Macros\civil_date_to_julian_date;
    use function PA\Macros\eccentric_anomaly;
    use function PA\Macros\true_anomaly;
    use function PA\Macros\w_to_degrees;

    /**
     * Calculate orbital data for binary star.
     */
    function binary_star_orbit($greenwichDateDay, $greenwichDateMonth, $greenwichDateYear, $binaryName)
    {
        $binaryDataManager = new BinaryDataManager();

        $binaryData =  $binaryDataManager->GetBinaryRecord($binaryName);

        $yYears = $greenwichDateYear + (civil_date_to_julian_date($greenwichDateDay, $greenwichDateMonth, $greenwichDateYear) - civil_date_to_julian_date(0, 1, $greenwichDateYear)) / 365.242191 - $binaryData->epochPeri;
        $mDeg = 360 * $yYears / $binaryData->period;
        $mRad = deg2rad($mDeg - 360 * floor($mDeg / 360));
        $eccentricity = $binaryData->ecc;
        $trueAnomalyRad = true_anomaly($mRad, $eccentricity);
        $rArcsec = (1 - $eccentricity * cos(eccentric_anomaly($mRad, $eccentricity))) * $binaryData->axis;
        $taPeriRad = $trueAnomalyRad + deg2rad($binaryData->longPeri);

        $y = sin($taPeriRad) * cos(deg2rad($binaryData->incl));
        $x = cos($taPeriRad);
        $aDeg = w_to_degrees(atan2($y, $x));
        $thetaDeg1 = $aDeg + $binaryData->paNode;
        $thetaDeg2 = $thetaDeg1 - 360 * floor($thetaDeg1 / 360);
        $rhoArcsec = $rArcsec * cos($taPeriRad) / cos(deg2rad($thetaDeg2 - $binaryData->paNode));

        $positionAngleDeg = round($thetaDeg2, 1);
        $separationArcsec = round($rhoArcsec, 2);

        return array($positionAngleDeg, $separationArcsec);
    }
}

namespace PA\Data\Binary {
    class BinaryData
    {
        /** Name of binary system. */
        public $name;

        /** Period of the orbit. */
        public $period;

        /** Epoch of the perihelion. */
        public $epochPeri;

        /** Longitude of the perihelion. */
        public $longPeri;

        /** Eccentricity of the orbit. */
        public $ecc;

        /** Semi-major axis of the orbit. */
        public $axis;

        /** Orbital inclination. */
        public $incl;

        /** Position angle of the ascending node. */
        public $paNode;

        public function __construct($name, $period, $epochPeri, $longPeri, $ecc, $axis, $incl, $paNode)
        {
            $this->name = $name;
            $this->period = $period;
            $this->epochPeri = $epochPeri;
            $this->longPeri = $longPeri;
            $this->ecc = $ecc;
            $this->axis = $axis;
            $this->incl = $incl;
            $this->paNode = $paNode;
        }
    }

    class BinaryDataManager
    {
        public $binaryRecords;

        public function __construct()
        {

            $this->binaryRecords = [];

            $this->binaryRecords[] = new BinaryData("eta-Cor", 41.623, 1934.008, 219.907, 0.2763, 0.907, 59.025, 23.717);
            $this->binaryRecords[] = new BinaryData("gamma-Vir", 171.37, 1836.433, 252.88, 0.8808, 3.746, 146.05, 31.78);
            $this->binaryRecords[] = new BinaryData("eta-Cas", 480.0, 1889.6, 268.59, 0.497, 11.9939, 34.76, 278.42);
            $this->binaryRecords[] = new BinaryData("zeta-Ori", 1508.6, 2070.6, 47.3, 0.07, 2.728, 72.0, 155.5);
            $this->binaryRecords[] = new BinaryData("alpha-CMa", 50.09, 1894.13, 147.27, 0.5923, 7.5, 136.53, 44.57);
            $this->binaryRecords[] = new BinaryData("delta-Gem", 1200.0, 1437.0, 57.19, 0.11, 6.9753, 63.28, 18.38);
            $this->binaryRecords[] = new BinaryData("alpha-Gem", 420.07, 1965.3, 261.43, 0.33, 6.295, 115.94, 40.47);
            $this->binaryRecords[] = new BinaryData("aplah-CMi", 40.65, 1927.6, 269.8, 0.4, 4.548, 35.7, 284.3);
            $this->binaryRecords[] = new BinaryData("alpha-Cen", 79.92, 1955.56, 231.56, 0.516, 17.583, 79.24, 204.868);
            $this->binaryRecords[] = new BinaryData("alpha Sco", 900.0, 1889.0, 0.0, 0.0, 3.21, 86.3, 273.0);
        }

        public function GetBinaryRecord($name)
        {
            foreach ($this->binaryRecords as $binaryRecord) {
                if ($binaryRecord->name == $name) {
                    return $binaryRecord;
                }
            }

            return new BinaryData("NotFound", -99, -99, -99, -99, -99, -99, -99, -99, -99);
        }
    }
}

namespace PA\Comet {

    use PA\Data\Comet\CometDataManager;

    use function PA\Macros\civil_date_to_julian_date;
    use function PA\Macros\decimal_degrees_degrees;
    use function PA\Macros\decimal_degrees_minutes;
    use function PA\Macros\decimal_degrees_seconds;
    use function PA\Macros\decimal_degrees_to_degree_hours;
    use function PA\Macros\decimal_hours_hour;
    use function PA\Macros\decimal_hours_minute;
    use function PA\Macros\decimal_hours_second;
    use function PA\Macros\ec_dec;
    use function PA\Macros\ec_ra;
    use function PA\Macros\local_civil_time_greenwich_day;
    use function PA\Macros\local_civil_time_greenwich_month;
    use function PA\Macros\local_civil_time_greenwich_year;
    use function PA\Macros\p_comet_long_lat_dist;
    use function PA\Macros\sun_dist;
    use function PA\Macros\sun_long;
    use function PA\Macros\true_anomaly;
    use function PA\Macros\w_to_degrees;

    /** Calculate position of an elliptical comet.  */
    function position_of_elliptical_comet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $cometName)
    {
        $cometDataManager = new CometDataManager();

        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $greenwichDateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $greenwichDateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $greenwichDateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $cometInfo =  $cometDataManager->GetEllipticalRecord($cometName);

        $timeSinceEpochYears = (civil_date_to_julian_date($greenwichDateDay, $greenwichDateMonth, $greenwichDateYear) - civil_date_to_julian_date(0.0, 1, $greenwichDateYear)) / 365.242191 + $greenwichDateYear - $cometInfo->epoch_EpochOfPerihelion;
        $mcDeg = 360 * $timeSinceEpochYears / $cometInfo->period_PeriodOfOrbit;
        $mcRad = deg2rad($mcDeg - 360 * floor($mcDeg / 360));
        $eccentricity = $cometInfo->ecc_EccentricityOfOrbit;
        $trueAnomalyDeg = w_to_degrees(true_anomaly($mcRad, $eccentricity));
        $lcDeg = $trueAnomalyDeg + $cometInfo->peri_LongitudeOfPerihelion;
        $rAU = $cometInfo->axis_SemiMajorAxisOfOrbit * (1 - $eccentricity * $eccentricity) / (1 + $eccentricity * cos(deg2rad($trueAnomalyDeg)));
        $lcNodeRad = deg2rad($lcDeg - $cometInfo->node_LongitudeOfAscendingNode);
        $psiRad = asin(sin($lcNodeRad) * sin(deg2rad($cometInfo->incl_InclinationOfOrbit)));

        $y = sin($lcNodeRad) * cos(deg2rad($cometInfo->incl_InclinationOfOrbit));
        $x = cos($lcNodeRad);

        $ldDeg = w_to_degrees(atan2($y, $x)) + $cometInfo->node_LongitudeOfAscendingNode;
        $rdAU = $rAU * cos($psiRad);

        $earthLongitudeLeDeg = sun_long($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear) + 180.0;
        $earthRadiusVectorAU = sun_dist($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $leLdRad = deg2rad($earthLongitudeLeDeg - $ldDeg);
        $aRad = ($rdAU < $earthRadiusVectorAU)
            ? atan2(($rdAU * sin($leLdRad)), ($earthRadiusVectorAU - $rdAU * cos($leLdRad)))
            : atan2(($earthRadiusVectorAU * sin(-$leLdRad)), ($rdAU - $earthRadiusVectorAU * cos($leLdRad)));

        $cometLongDeg1 = ($rdAU < $earthRadiusVectorAU)
            ? 180.0 + $earthLongitudeLeDeg + w_to_degrees($aRad)
            : w_to_degrees($aRad) + $ldDeg;
        $cometLongDeg = $cometLongDeg1 - 360 * floor($cometLongDeg1 / 360);
        $cometLatDeg = w_to_degrees(atan($rdAU * tan($psiRad) * sin(deg2rad($cometLongDeg1 - $ldDeg)) / ($earthRadiusVectorAU * sin(-$leLdRad))));
        $cometRAHours1 = decimal_degrees_to_degree_hours(ec_ra($cometLongDeg, 0, 0, $cometLatDeg, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear));
        $cometDecDeg1 = ec_dec($cometLongDeg, 0, 0, $cometLatDeg, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear);
        $cometDistanceAU = sqrt(pow($earthRadiusVectorAU, 2) + pow($rAU, 2) - 2.0 * $earthRadiusVectorAU * $rAU * cos(deg2rad($lcDeg - $earthLongitudeLeDeg)) * cos($psiRad));

        $cometRAHour = decimal_hours_hour($cometRAHours1 + 0.008333);
        $cometRAMin = decimal_hours_minute($cometRAHours1 + 0.008333);
        $cometDecDeg = decimal_degrees_degrees($cometDecDeg1 + 0.008333);
        $cometDecMin = decimal_degrees_minutes($cometDecDeg1 + 0.008333);
        $cometDistEarth = round($cometDistanceAU, 2);

        return array($cometRAHour, $cometRAMin, $cometDecDeg, $cometDecMin, $cometDistEarth);
    }

    /** Calculate position of a parabolic comet.  */
    function position_of_parabolic_comet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $cometName)
    {
        $cometDataManager = new CometDataManager();

        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $greenwichDateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $greenwichDateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $greenwichDateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $cometInfo = $cometDataManager->GetParabolicRecord($cometName);

        $perihelionEpochDay = $cometInfo->epochPeriDay;
        $perihelionEpochMonth = $cometInfo->epochPeriMonth;
        $perihelionEpochYear = $cometInfo->epochPeriYear;
        $qAU = $cometInfo->periDist;
        $inclinationDeg = $cometInfo->incl;
        $perihelionDeg = $cometInfo->argPeri;
        $nodeDeg = $cometInfo->node;

        list($cometLongDeg, $cometLatDeg, $cometDistAU) =
            p_comet_long_lat_dist($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $perihelionEpochDay, $perihelionEpochMonth, $perihelionEpochYear, $qAU, $inclinationDeg, $perihelionDeg, $nodeDeg);

        $cometRAHours = decimal_degrees_to_degree_hours(ec_ra($cometLongDeg, 0, 0, $cometLatDeg, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear));
        $cometDecDeg1 = ec_dec($cometLongDeg, 0, 0, $cometLatDeg, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear);

        $cometRAHour = decimal_hours_hour($cometRAHours);
        $cometRAMin = decimal_hours_minute($cometRAHours);
        $cometRASec = decimal_hours_second($cometRAHours);
        $cometDecDeg = decimal_degrees_degrees($cometDecDeg1);
        $cometDecMin = decimal_degrees_minutes($cometDecDeg1);
        $cometDecSec = decimal_degrees_seconds($cometDecDeg1);
        $cometDistEarth = round($cometDistAU, 2);

        return array($cometRAHour, $cometRAMin, $cometRASec, $cometDecDeg, $cometDecMin, $cometDecSec, $cometDistEarth);
    }
}

namespace PA\Data\Comet {
    class CometDataElliptical
    {
        /** Name of comet */
        public $name;

        /** Epoch of the perihelion */
        public $epoch_EpochOfPerihelion;

        /** Longitude of the perihelion */
        public $peri_LongitudeOfPerihelion;

        /** Longitude of the ascending node */
        public $node_LongitudeOfAscendingNode;

        /** Period of the orbit */
        public $period_PeriodOfOrbit;

        /** Semi-major axis of the orbit */
        public $axis_SemiMajorAxisOfOrbit;

        /** Eccentricity of the orbit */
        public $ecc_EccentricityOfOrbit;

        /** Inclination of the orbit */
        public $incl_InclinationOfOrbit;

        public function __construct($name, $epoch_EpochOfPerihelion, $peri_LongitudeOfPerihelion, $node_LongitudeOfAscendingNode, $period_PeriodOfOrbit, $axis_SemiMajorAxisOfOrbit, $ecc_EccentricityOfOrbit, $incl_InclinationOfOrbit)
        {
            $this->name = $name;
            $this->epoch_EpochOfPerihelion = $epoch_EpochOfPerihelion;
            $this->peri_LongitudeOfPerihelion = $peri_LongitudeOfPerihelion;
            $this->node_LongitudeOfAscendingNode = $node_LongitudeOfAscendingNode;
            $this->period_PeriodOfOrbit = $period_PeriodOfOrbit;
            $this->axis_SemiMajorAxisOfOrbit = $axis_SemiMajorAxisOfOrbit;
            $this->ecc_EccentricityOfOrbit = $ecc_EccentricityOfOrbit;
            $this->incl_InclinationOfOrbit = $incl_InclinationOfOrbit;
        }
    }

    class CometDataParabolic
    {
        /** Name of comet */
        public $name;

        /** Epoch perihelion day */
        public $epochPeriDay;

        /** Epoch perihelion month */
        public $epochPeriMonth;

        /** Epoch perihelion year */
        public $epochPeriYear;

        /** Arg perihelion */
        public $argPeri;

        /** Comet's node */
        public $node;

        /** Distance at the perihelion */
        public $periDist;

        /** Inclination */
        public $incl;

        public function __construct($name, $epochPeriDay, $epochPeriMonth, $epochPeriYear, $argPeri, $node, $periDist, $incl)
        {
            $this->name = $name;
            $this->epochPeriDay = $epochPeriDay;
            $this->epochPeriMonth = $epochPeriMonth;
            $this->epochPeriYear = $epochPeriYear;
            $this->argPeri = $argPeri;
            $this->node = $node;
            $this->periDist = $periDist;
            $this->incl = $incl;
        }
    }

    class CometDataManager
    {
        public $cometEllipticalRecords;
        public $cometParabolicRecords;

        public function __construct()
        {
            $this->cometEllipticalRecords = [];
            $this->cometParabolicRecords = [];

            $this->cometEllipticalRecords[] = new CometDataElliptical("Encke", 1974.32, 160.1, 334.2, 3.3, 2.21, 0.85, 12.0);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Temple 2", 1972.87, 310.2, 119.3, 5.26, 3.02, 0.55, 12.5);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Haneda-Campos", 1978.77, 12.02, 131.7, 5.37, 3.07, 0.64, 5.81);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Schwassmann-Wachmann 2", 1974.7, 123.3, 126.0, 6.51, 3.49, 0.39, 3.7);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Borrelly", 1974.36, 67.8, 75.1, 6.76, 3.58, 0.63, 30.2);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Whipple", 1970.77, 18.2, 188.4, 7.47, 3.82, 0.35, 10.2);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Oterma", 1958.44, 150.0, 155.1, 7.88, 3.96, 0.14, 4.0);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Schaumasse", 1960.29, 138.1, 86.2, 8.18, 4.05, 0.71, 12.0);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Comas Sola", 1969.83, 102.9, 62.8, 8.55, 4.18, 0.58, 13.4);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Schwassmann-Wachmann 1", 1974.12, 334.1, 319.6, 15.03, 6.09, 0.11, 9.7);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Neujmin 1", 1966.94, 334.0, 347.2, 17.93, 6.86, 0.78, 15.0);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Crommelin", 1956.82, 86.4, 250.4, 27.89, 9.17, 0.92, 28.9);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Olbers", 1956.46, 150.0, 85.4, 69.47, 16.84, 0.93, 44.6);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Pons-Brooks", 1954.39, 94.2, 255.2, 70.98, 17.2, 0.96, 74.2);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Halley", 1986.112, 170.011, 58.154, 76.0081, 17.9435, 0.9673, 162.2384);
            $this->cometEllipticalRecords[] = new CometDataElliptical("Neppy", $this->epochToDecimalDate("2025-Mar-24 00:00:00"), 316.4674349, 131.8885759, 165.6477206, 30.16150571, 0.01222316, 1.773088852);

            $this->cometParabolicRecords[] = new CometDataParabolic("Kohler", 10.5659, 11, 1977, 163.4799, 181.8175, 0.990662, 48.7196);
        }

        private function epochToDecimalDate($epochDate)
        {
            /* implement details... */
        }

        public function GetEllipticalRecord($name)
        {
            foreach ($this->cometEllipticalRecords as $ellipticalRecord) {
                if ($ellipticalRecord->name == $name) {
                    return $ellipticalRecord;
                }
            }

            return new CometDataElliptical("NotFound", -99, -99, -99, -99, -99, -99, -99, -99, -99);
        }

        public function GetParabolicRecord($name)
        {
            foreach ($this->cometParabolicRecords as $parabolicRecord) {
                if ($parabolicRecord->name == $name) {
                    return $parabolicRecord;
                }
            }

            return new CometDataParabolic("NotFound", -99, -99, -99, -99, -99, -99, -99, -99, -99);
        }
    }
}

namespace PA\Coordinates {

    use PA\Macros as PA_Macros;
    use PA\Types as PA_Types;

    /**
     * Convert an Angle (degrees, minutes, and seconds) to Decimal Degrees
     */
    function angle_to_decimal_degrees($degrees, $minutes, $seconds)
    {
        $a = abs($seconds) / 60;
        $b = (abs($minutes) + $a) / 60;
        $c = abs($degrees) + $b;
        $d = ($degrees < 0 || $minutes < 0 || $seconds < 0) ? -$c : $c;

        return $d;
    }

    /**
     * Convert Decimal Degrees to an Angle (degrees, minutes, and seconds)
     */
    function decimal_degrees_to_angle($decimalDegrees)
    {
        $unsignedDecimal = abs($decimalDegrees);
        $totalSeconds = $unsignedDecimal * 3600;
        $seconds2DP = @round($totalSeconds % 60, 2);
        $correctedSeconds = ($seconds2DP == 60) ? 0 : $seconds2DP;
        $correctedRemainder = ($seconds2DP == 60) ? $totalSeconds + 60 : $totalSeconds;
        $minutes = floor($correctedRemainder / 60) % 60;
        $unsignedDegrees = floor($correctedRemainder / 3600);
        $signedDegrees = ($decimalDegrees < 0) ? -1 * $unsignedDegrees : $unsignedDegrees;

        return array($signedDegrees, $minutes, floor($correctedSeconds));
    }

    /**
     * Convert Right Ascension to Hour Angle
     */
    function right_ascension_to_hour_angle($raHours, $raMinutes, $raSeconds, $lctHours, $lctMinutes, $lctSeconds, $isDaylightSavings, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude)
    {
        $daylightSaving = ($isDaylightSavings) ? 1 : 0;

        $hourAngle = PA_Macros\right_ascension_to_hour_angle($raHours, $raMinutes, $raSeconds, $lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude);

        $hourAngleHours = PA_Macros\decimal_hours_hour($hourAngle);
        $hourAngleMinutes = PA_Macros\decimal_hours_minute($hourAngle);
        $hourAngleSeconds = PA_Macros\decimal_hours_second($hourAngle);

        return array($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds);
    }

    /**
     * Convert Hour Angle to Right Ascension
     */
    function hour_angle_to_right_ascension($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $lctHours, $lctMinutes, $lctSeconds, $isDaylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude)
    {
        $daylightSaving = ($isDaylightSaving) ? 1 : 0;

        $rightAscension = PA_Macros\hour_angle_to_right_ascension($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude);

        $rightAscensionHours = PA_Macros\decimal_hours_hour($rightAscension);
        $rightAscensionMinutes = PA_Macros\decimal_hours_minute($rightAscension);
        $rightAscensionSeconds = PA_Macros\decimal_hours_second($rightAscension);

        return array($rightAscensionHours, $rightAscensionMinutes, $rightAscensionSeconds);
    }

    /**
     * Convert Equatorial Coordinates to Horizon Coordinates
     */
    function equatorial_coordinates_to_horizon_coordinates($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude)
    {
        $azimuthInDecimalDegrees = PA_Macros\equatorial_coordinates_to_azimuth($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude);

        $altitudeInDecimalDegrees = PA_Macros\equatorial_coordinates_to_altitude($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude);

        $azimuthDegrees = PA_Macros\decimal_degrees_degrees($azimuthInDecimalDegrees);
        $azimuthMinutes = PA_Macros\decimal_degrees_minutes($azimuthInDecimalDegrees);
        $azimuthSeconds = PA_Macros\decimal_degrees_seconds($azimuthInDecimalDegrees);

        $altitudeDegrees = PA_Macros\decimal_degrees_degrees($altitudeInDecimalDegrees);
        $altitudeMinutes = PA_Macros\decimal_degrees_minutes($altitudeInDecimalDegrees);
        $altitudeSeconds = PA_Macros\decimal_degrees_seconds($altitudeInDecimalDegrees);

        return array($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds);
    }

    /**
     * Convert Horizon Coordinates to Equatorial Coordinates
     */

    function horizon_coordinates_to_equatorial_coordinates($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude)
    {
        $hourAngleInDecimalDegrees = PA_Macros\horizon_coordinates_to_hour_angle($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude);

        $declinationInDecimalDegrees = PA_Macros\horizon_coordinates_to_declination($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude);

        $hourAngleHours = PA_Macros\decimal_hours_hour($hourAngleInDecimalDegrees);
        $hourAngleMinutes = PA_Macros\decimal_hours_minute($hourAngleInDecimalDegrees);
        $hourAngleSeconds = PA_Macros\decimal_hours_second($hourAngleInDecimalDegrees);

        $declinationDegrees = PA_Macros\decimal_degrees_degrees($declinationInDecimalDegrees);
        $declinationMinutes = PA_Macros\decimal_degrees_minutes($declinationInDecimalDegrees);
        $declinationSeconds = PA_Macros\decimal_degrees_seconds($declinationInDecimalDegrees);

        return array($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds);
    }

    /**
     * Calculate Mean Obliquity of the Ecliptic for a Greenwich Date
     */
    function mean_obliquity_of_the_ecliptic($greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $jd = PA_Macros\civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear);
        $mjd = $jd - 2451545;
        $t = $mjd / 36525;
        $de1 = $t * (46.815 + $t * (0.0006 - ($t * 0.00181)));
        $de2 = $de1 / 3600;

        return 23.439292 - $de2;
    }

    /**
     * Convert Ecliptic Coordinates to Equatorial Coordinates
     */
    function ecliptic_coordinate_to_equatorial_coordinate($eclipticLongitudeDegrees, $eclipticLongitudeMinutes, $eclipticLongitudeSeconds, $eclipticLatitudeDegrees, $eclipticLatitudeMinutes, $eclipticLatitudeSeconds, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $eclonDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($eclipticLongitudeDegrees, $eclipticLongitudeMinutes, $eclipticLongitudeSeconds);
        $eclatDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($eclipticLatitudeDegrees, $eclipticLatitudeMinutes, $eclipticLatitudeSeconds);
        $eclonRad = deg2rad($eclonDeg);
        $eclatRad = deg2rad($eclatDeg);
        $obliqDeg = PA_Macros\obliq($greenwichDay, $greenwichMonth, $greenwichYear);
        $obliqRad = deg2rad($obliqDeg);
        $sinDec = sin($eclatRad) * cos($obliqRad) + cos($eclatRad) * sin($obliqRad) * sin($eclonRad);
        $decRad = asin($sinDec);
        $decDeg = PA_Macros\degrees($decRad);
        $y = sin($eclonRad) * cos($obliqRad) - tan($eclatRad) * sin($obliqRad);
        $x = cos($eclonRad);
        $raRad = atan2($y, $x);
        $raDeg1 = PA_Macros\degrees($raRad);
        $raDeg2 = $raDeg1 - 360 * floor($raDeg1 / 360);
        $raHours = PA_Macros\decimal_degrees_to_degree_hours($raDeg2);

        $outRAHours = PA_Macros\decimal_hours_hour($raHours);
        $outRAMinutes = PA_Macros\decimal_hours_minute($raHours);
        $outRASeconds = PA_Macros\decimal_hours_second($raHours);
        $outDecDegrees = PA_Macros\decimal_degrees_degrees($decDeg);
        $outDecMinutes = PA_Macros\decimal_degrees_minutes($decDeg);
        $outDecSeconds = PA_Macros\decimal_degrees_seconds($decDeg);

        return array($outRAHours, $outRAMinutes, $outRASeconds, $outDecDegrees, $outDecMinutes, $outDecSeconds);
    }

    /**
     * Convert Equatorial Coordinates to Ecliptic Coordinates
     */
    function equatorial_coordinate_to_ecliptic_coordinate($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds, $gwDay, $gwMonth, $gwYear)
    {
        $raDeg = PA_Macros\degree_hours_to_decimal_degrees(PA_Macros\hours_minutes_seconds_to_decimal_hours($raHours, $raMinutes, $raSeconds));
        $decDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decDegrees, $decMinutes, $decSeconds);
        $raRad = deg2rad($raDeg);
        $decRad = deg2rad($decDeg);
        $obliqDeg = PA_Macros\obliq($gwDay, $gwMonth, $gwYear);
        $obliqRad = deg2rad($obliqDeg);
        $sinEclLat = sin($decRad) * cos($obliqRad) - cos($decRad) * sin($obliqRad) * sin($raRad);
        $eclLatRad = asin($sinEclLat);
        $eclLatDeg = PA_Macros\degrees($eclLatRad);
        $y = sin($raRad) * cos($obliqRad) + tan($decRad) * sin($obliqRad);
        $x = cos($raRad);
        $eclLongRad = atan2($y, $x);
        $eclLongDeg1 = PA_Macros\degrees($eclLongRad);
        $eclLongDeg2 = $eclLongDeg1 - 360 * floor($eclLongDeg1 / 360);

        $outEclLongDeg = PA_Macros\decimal_degrees_degrees($eclLongDeg2);
        $outEclLongMin = PA_Macros\decimal_degrees_minutes($eclLongDeg2);
        $outEclLongSec = PA_Macros\decimal_degrees_seconds($eclLongDeg2);
        $outEclLatDeg = PA_Macros\decimal_degrees_degrees($eclLatDeg);
        $outEclLatMin = PA_Macros\decimal_degrees_minutes($eclLatDeg);
        $outEclLatSec = PA_Macros\decimal_degrees_seconds($eclLatDeg);

        return array($outEclLongDeg, $outEclLongMin, $outEclLongSec, $outEclLatDeg, $outEclLatMin, $outEclLatSec);
    }

    /**
     * Convert Equatorial Coordinates to Galactic Coordinates
     */
    function equatorial_coordinate_to_galactic_coordinate($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds)
    {
        $raDeg = PA_Macros\degree_hours_to_decimal_degrees(PA_Macros\hours_minutes_seconds_to_decimal_hours($raHours, $raMinutes, $raSeconds));
        $decDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decDegrees, $decMinutes, $decSeconds);
        $raRad = deg2rad($raDeg);
        $decRad = deg2rad($decDeg);
        $sinB = cos($decRad) * cos(deg2rad(27.4))  * cos($raRad - deg2rad(192.25)) + sin($decRad) * sin(deg2rad(27.4));
        $bRadians = asin($sinB);
        $bDeg = PA_Macros\degrees($bRadians);
        $y = sin($decRad) - $sinB * sin(deg2rad(27.4));
        $x = cos($decRad) * sin($raRad - deg2rad(192.25)) * cos(deg2rad(27.4));
        $longDeg1 = PA_Macros\degrees(atan2($y, $x)) + 33;
        $longDeg2 = $longDeg1 - 360 * floor($longDeg1 / 360);

        $galLongDeg = PA_Macros\decimal_degrees_degrees($longDeg2);
        $galLongMin = PA_Macros\decimal_degrees_minutes($longDeg2);
        $galLongSec = PA_Macros\decimal_degrees_seconds($longDeg2);
        $galLatDeg = PA_Macros\decimal_degrees_degrees($bDeg);
        $galLatMin = PA_Macros\decimal_degrees_minutes($bDeg);
        $galLatSec = PA_Macros\decimal_degrees_seconds($bDeg);

        return array($galLongDeg, $galLongMin, $galLongSec, $galLatDeg, $galLatMin, $galLatSec);
    }

    /**
     * Convert Galactic Coordinates to Equatorial Coordinates
     */
    function galactic_coordinate_to_equatorial_coordinate($galLongDeg, $galLongMin, $galLongSec, $galLatDeg, $galLatMin, $galLatSec)
    {
        $glongDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($galLongDeg, $galLongMin, $galLongSec);
        $glatDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($galLatDeg, $galLatMin, $galLatSec);
        $glongRad = deg2rad($glongDeg);
        $glatRad = deg2rad($glatDeg);
        $sinDec = cos($glatRad) * cos(deg2rad(27.4)) * sin($glongRad - deg2rad(33.0)) + sin($glatRad) * sin(deg2rad(27.4));
        $decRadians = asin($sinDec);
        $decDeg = PA_Macros\degrees($decRadians);
        $y = cos($glatRad) * cos($glongRad - deg2rad(33.0));
        $x = sin($glatRad) * cos(deg2rad(27.4)) - cos($glatRad) * sin(deg2rad(27.4)) * sin($glongRad - deg2rad(33.0));

        $raDeg1 = PA_Macros\degrees(atan2($y, $x)) + 192.25;
        $raDeg2 = $raDeg1 - 360 * floor($raDeg1 / 360);
        $raHours1 = PA_Macros\decimal_degrees_to_degree_hours($raDeg2);

        $raHours = PA_Macros\decimal_hours_hour($raHours1);
        $raMinutes = PA_Macros\decimal_hours_minute($raHours1);
        $raSeconds = PA_Macros\decimal_hours_second($raHours1);
        $decDegrees = PA_Macros\decimal_degrees_degrees($decDeg);
        $decMinutes = PA_Macros\decimal_degrees_minutes($decDeg);
        $decSeconds = PA_Macros\decimal_degrees_seconds($decDeg);

        return array($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds);
    }

    /**
     * Calculate the angle between two celestial objects
     */
    function angle_between_two_objects($raLong1HourDeg, $raLong1Min, $raLong1Sec, $decLat1Deg, $decLat1Min, $decLat1Sec, $raLong2HourDeg, $raLong2Min, $raLong2Sec, $decLat2Deg, $decLat2Min, $decLat2Sec, PA_Types\AngleMeasure $hourOrDegree)
    {
        $raLong1Decimal =
            ($hourOrDegree == PA_Types\AngleMeasure::Hours)
            ? PA_Macros\hours_minutes_seconds_to_decimal_hours($raLong1HourDeg, $raLong1Min, $raLong1Sec)
            : PA_Macros\degrees_minutes_seconds_to_decimal_degrees($raLong1HourDeg, $raLong1Min, $raLong1Sec);
        $raLong1Deg =
            ($hourOrDegree == PA_Types\AngleMeasure::Hours)
            ? PA_Macros\degree_hours_to_decimal_degrees($raLong1Decimal)
            : $raLong1Decimal;

        $raLong1Rad = deg2rad($raLong1Deg);
        $decLat1Deg1 = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decLat1Deg, $decLat1Min, $decLat1Sec);
        $decLat1Rad = deg2rad($decLat1Deg1);

        $raLong2Decimal =
            ($hourOrDegree == PA_Types\AngleMeasure::Hours)
            ? PA_Macros\hours_minutes_seconds_to_decimal_hours($raLong2HourDeg, $raLong2Min, $raLong2Sec)
            : PA_Macros\degrees_minutes_seconds_to_decimal_degrees($raLong2HourDeg, $raLong2Min, $raLong2Sec);
        $raLong2Deg = ($hourOrDegree == PA_Types\AngleMeasure::Hours)
            ? PA_Macros\degree_hours_to_decimal_degrees($raLong2Decimal)
            : $raLong2Decimal;
        $raLong2Rad = deg2rad($raLong2Deg);
        $decLat2Deg1 = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decLat2Deg, $decLat2Min, $decLat2Sec);
        $decLat2Rad = deg2rad($decLat2Deg1);

        $cosD = sin($decLat1Rad) * sin($decLat2Rad) + cos($decLat1Rad) * cos($decLat2Rad) * cos($raLong1Rad - $raLong2Rad);
        $dRad = acos($cosD);
        $dDeg = PA_Macros\degrees($dRad);

        $angleDeg = PA_Macros\decimal_degrees_degrees($dDeg);
        $angleMin = PA_Macros\decimal_degrees_minutes($dDeg);
        $angleSec = PA_Macros\decimal_degrees_seconds($dDeg);

        return array($angleDeg, $angleMin, $angleSec);
    }

    /**
     * Calculate rising and setting times for an object.
     */
    function rising_and_setting($raHours, $raMinutes, $raSeconds, $decDeg, $decMin, $decSec, $gwDateDay, $gwDateMonth, $gwDateYear, $geogLongDeg, $geogLatDeg, $vertShiftDeg)
    {
        $raHours1 = PA_Macros\hours_minutes_seconds_to_decimal_hours($raHours, $raMinutes, $raSeconds);
        $decRad = deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decDeg, $decMin, $decSec));
        $verticalDisplRadians = deg2rad($vertShiftDeg);
        $geoLatRadians = deg2rad($geogLatDeg);
        $cosH = - (sin($verticalDisplRadians) + sin($geoLatRadians) * sin($decRad)) / (cos($geoLatRadians) * cos($decRad));
        $hHours = PA_Macros\decimal_degrees_to_degree_hours(PA_Macros\degrees(acos($cosH)));
        $lstRiseHours = ($raHours1 - $hHours) - 24 * floor(($raHours1 - $hHours) / 24);
        $lstSetHours = ($raHours1 + $hHours) - 24 * floor(($raHours1 + $hHours) / 24);
        $aDeg = PA_Macros\degrees(acos((sin($decRad) + sin($verticalDisplRadians) * sin($geoLatRadians)) / (cos($verticalDisplRadians) * cos($geoLatRadians))));
        $azRiseDeg = $aDeg - 360 * floor($aDeg / 360);
        $azSetDeg = (360 - $aDeg) - 360 * floor((360 - $aDeg) / 360);
        $utRiseHours1 = PA_Macros\greenwich_sidereal_time_to_universal_time(PA_Macros\local_sidereal_time_to_greenwich_sidereal_time($lstRiseHours, 0, 0, $geogLongDeg), 0, 0, $gwDateDay, $gwDateMonth, $gwDateYear);
        $utSetHours1 = PA_Macros\greenwich_sidereal_time_to_universal_time(PA_Macros\local_sidereal_time_to_greenwich_sidereal_time($lstSetHours, 0, 0, $geogLongDeg), 0, 0, $gwDateDay, $gwDateMonth, $gwDateYear);
        $utRiseAdjustedHours = $utRiseHours1 + 0.008333;
        $utSetAdjustedHours = $utSetHours1 + 0.008333;

        $riseSetStatus = PA_Types\RiseSetStatus::OK;
        if ($cosH > 1)
            $riseSetStatus = PA_Types\RiseSetStatus::NeverRises;
        if ($cosH < -1)
            $riseSetStatus = PA_Types\RiseSetStatus::Circumpolar;

        $utRiseHour = ($riseSetStatus == PA_Types\RiseSetStatus::OK) ? PA_Macros\decimal_hours_hour($utRiseAdjustedHours) : 0;
        $utRiseMin = ($riseSetStatus == PA_Types\RiseSetStatus::OK) ? PA_Macros\decimal_hours_minute($utRiseAdjustedHours) : 0;
        $utSetHour = ($riseSetStatus == PA_Types\RiseSetStatus::OK) ? PA_Macros\decimal_hours_hour($utSetAdjustedHours) : 0;
        $utSetMin = ($riseSetStatus == PA_Types\RiseSetStatus::OK) ? PA_Macros\decimal_hours_minute($utSetAdjustedHours) : 0;
        $azRise = ($riseSetStatus == PA_Types\RiseSetStatus::OK) ? round($azRiseDeg, 2) : 0;
        $azSet = ($riseSetStatus == PA_Types\RiseSetStatus::OK) ? round($azSetDeg, 2) : 0;

        return array($riseSetStatus, $utRiseHour, $utRiseMin, $utSetHour, $utSetMin, $azRise, $azSet);
    }

    /**
     * Calculate precession (corrected coordinates between two epochs)
     */
    function correct_for_precession($raHour, $raMinutes, $raSeconds, $decDeg, $decMinutes, $decSeconds, $epoch1Day, $epoch1Month, $epoch1Year, $epoch2Day, $epoch2Month, $epoch2Year)
    {
        $ra1Rad = deg2rad(PA_Macros\degree_hours_to_decimal_degrees(PA_Macros\hours_minutes_seconds_to_decimal_hours($raHour, $raMinutes, $raSeconds)));
        $dec1Rad = deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decDeg, $decMinutes, $decSeconds));
        $tCenturies = (PA_Macros\civil_date_to_julian_date($epoch1Day, $epoch1Month, $epoch1Year) - 2415020) / 36525;
        $mSec = 3.07234 + (0.00186 * $tCenturies);
        $nArcsec = 20.0468 - (0.0085 * $tCenturies);
        $nYears = (PA_Macros\civil_date_to_julian_date($epoch2Day, $epoch2Month, $epoch2Year) - PA_Macros\civil_date_to_julian_date($epoch1Day, $epoch1Month, $epoch1Year)) / 365.25;
        $s1Hours = (($mSec + ($nArcsec * sin($ra1Rad) * tan($dec1Rad) / 15)) * $nYears) / 3600;
        $ra2Hours = PA_Macros\hours_minutes_seconds_to_decimal_hours($raHour, $raMinutes, $raSeconds) + $s1Hours;
        $s2Deg = ($nArcsec * cos($ra1Rad) * $nYears) / 3600;
        $dec2Deg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($decDeg, $decMinutes, $decSeconds) + $s2Deg;

        $correctedRAHour = PA_Macros\decimal_hours_hour($ra2Hours);
        $correctedRAMinutes = PA_Macros\decimal_hours_minute($ra2Hours);
        $correctedRASeconds = PA_Macros\decimal_hours_second($ra2Hours);
        $correctedDecDeg = PA_Macros\decimal_degrees_degrees($dec2Deg);
        $correctedDecMinutes = PA_Macros\decimal_degrees_minutes($dec2Deg);
        $correctedDecSeconds = PA_Macros\decimal_degrees_seconds($dec2Deg);

        return array($correctedRAHour, $correctedRAMinutes, $correctedRASeconds, $correctedDecDeg, $correctedDecMinutes, $correctedDecSeconds);
    }

    /**
     * Calculate nutation for two values: ecliptic longitude and obliquity, for a Greenwich date.
     */
    function nutation_in_ecliptic_longitude_and_obliquity($greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $jdDays = PA_Macros\civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear);
        $tCenturies = ($jdDays - 2415020) / 36525;
        $aDeg = 100.0021358 * $tCenturies;
        $l1Deg = 279.6967 + (0.000303 * $tCenturies * $tCenturies);
        $lDeg1 = $l1Deg + 360 * ($aDeg - floor($aDeg));
        $lDeg2 = $lDeg1 - 360 * floor($lDeg1 / 360);
        $lRad = deg2rad($lDeg2);
        $bDeg = 5.372617 * $tCenturies;
        $nDeg1 = 259.1833 - 360 * ($bDeg - floor($bDeg));
        $nDeg2 = $nDeg1 - 360 * (floor($nDeg1 / 360));
        $nRad = deg2rad($nDeg2);
        $nutInLongArcsec = -17.2 * sin($nRad) - 1.3 * sin(2 * $lRad);
        $nutInOblArcsec = 9.2 * cos($nRad) + 0.5 * cos(2 * $lRad);

        $nutInLongDeg = $nutInLongArcsec / 3600;
        $nutInOblDeg = $nutInOblArcsec / 3600;

        return array($nutInLongDeg, $nutInOblDeg);
    }

    /**
     * Correct ecliptic coordinates for the effects of aberration.
     */
    function correct_for_aberration($utHour, $utMinutes, $utSeconds, $gwDay, $gwMonth, $gwYear, $trueEclLongDeg, $trueEclLongMin, $trueEclLongSec, $trueEclLatDeg, $trueEclLatMin, $trueEclLatSec)
    {
        $trueLongDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($trueEclLongDeg, $trueEclLongMin, $trueEclLongSec);
        $trueLatDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees($trueEclLatDeg, $trueEclLatMin, $trueEclLatSec);
        $sunTrueLongDeg = PA_Macros\sun_long($utHour, $utMinutes, $utSeconds, 0, 0, $gwDay, $gwMonth, $gwYear);
        $dlongArcsec = -20.5 * cos(deg2rad($sunTrueLongDeg - $trueLongDeg)) / cos(deg2rad($trueLatDeg));
        $dlatArcsec = -20.5 * sin(deg2rad($sunTrueLongDeg - $trueLongDeg)) * sin(deg2rad($trueLatDeg));
        $apparentLongDeg = $trueLongDeg + ($dlongArcsec / 3600);
        $apparentLatDeg = $trueLatDeg + ($dlatArcsec / 3600);

        $apparentEclLongDeg = PA_Macros\decimal_degrees_degrees($apparentLongDeg);
        $apparentEclLongMin = PA_Macros\decimal_degrees_minutes($apparentLongDeg);
        $apparentEclLongSec = PA_Macros\decimal_degrees_seconds($apparentLongDeg);
        $apparentEclLatDeg = PA_Macros\decimal_degrees_degrees($apparentLatDeg);
        $apparentEclLatMin = PA_Macros\decimal_degrees_minutes($apparentLatDeg);
        $apparentEclLatSec = PA_Macros\decimal_degrees_seconds($apparentLatDeg);

        return array($apparentEclLongDeg, $apparentEclLongMin, $apparentEclLongSec, $apparentEclLatDeg, $apparentEclLatMin, $apparentEclLatSec);
    }

    /**
     * Calculate corrected RA/Dec, accounting for atmospheric refraction.
     */
    function atmospheric_refraction($trueRAHour, $trueRAMin, $trueRASec, $trueDecDeg, $trueDecMin, $trueDecSec, PA_Types\CoordinateType $coordinateType, $geogLongDeg, $geogLatDeg, $daylightSavingHours, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $lctHour, $lctMin, $lctSec, $atmosphericPressureMbar, $atmosphericTemperatureCelsius)
    {
        $haHour = PA_Macros\right_ascension_to_hour_angle($trueRAHour, $trueRAMin, $trueRASec, $lctHour, $lctMin, $lctSec, $daylightSavingHours, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $geogLongDeg);
        $azimuthDeg = PA_Macros\equatorial_coordinates_to_azimuth($haHour, 0, 0, $trueDecDeg, $trueDecMin, $trueDecSec, $geogLatDeg);
        $altitudeDeg = PA_Macros\equatorial_coordinates_to_altitude($haHour, 0, 0, $trueDecDeg, $trueDecMin, $trueDecSec, $geogLatDeg);
        $correctedAltitudeDeg = PA_Macros\refract($altitudeDeg, $coordinateType, $atmosphericPressureMbar, $atmosphericTemperatureCelsius);

        $correctedHAHour = PA_Macros\horizon_coordinates_to_hour_angle($azimuthDeg, 0, 0, $correctedAltitudeDeg, 0, 0, $geogLatDeg);
        $correctedRAHour1 = PA_Macros\hour_angle_to_right_ascension($correctedHAHour, 0, 0, $lctHour, $lctMin, $lctSec, $daylightSavingHours, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $geogLongDeg);
        $correctedDecDeg1 = PA_Macros\horizon_coordinates_to_declination($azimuthDeg, 0, 0, $correctedAltitudeDeg, 0, 0, $geogLatDeg);

        $correctedRAHour = PA_Macros\decimal_hours_hour($correctedRAHour1);
        $correctedRAMin = PA_Macros\decimal_hours_minute($correctedRAHour1);
        $correctedRASec = PA_Macros\decimal_hours_second($correctedRAHour1);
        $correctedDecDeg = PA_Macros\decimal_degrees_degrees($correctedDecDeg1);
        $correctedDecMin = PA_Macros\decimal_degrees_minutes($correctedDecDeg1);
        $correctedDecSec = PA_Macros\decimal_degrees_seconds($correctedDecDeg1);

        return array($correctedRAHour, $correctedRAMin, $correctedRASec, $correctedDecDeg, $correctedDecMin, $correctedDecSec);
    }

    /**
     * Calculate corrected RA/Dec, accounting for geocentric parallax.
     */
    function corrections_for_geocentric_parallax($raHour, $raMin, $raSec, $decDeg, $decMin, $decSec, PA_Types\CoordinateType $coordinateType, $equatorialHorParallaxDeg, $geogLongDeg, $geogLatDeg, $heightM, $daylightSaving, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $lctHour, $lctMin, $lctSec)
    {
        $haHours = PA_Macros\right_ascension_to_hour_angle($raHour, $raMin, $raSec, $lctHour, $lctMin, $lctSec, $daylightSaving, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $geogLongDeg);

        $correctedHAHours = PA_Macros\parallax_ha($haHours, 0, 0, $decDeg, $decMin, $decSec, $coordinateType, $geogLatDeg, $heightM, $equatorialHorParallaxDeg);

        $correctedRAHours = PA_Macros\hour_angle_to_right_ascension($correctedHAHours, 0, 0, $lctHour, $lctMin, $lctSec, $daylightSaving, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $geogLongDeg);

        $correctedDecDeg1 = PA_Macros\parallax_dec($haHours, 0, 0, $decDeg, $decMin, $decSec, $coordinateType, $geogLatDeg, $heightM, $equatorialHorParallaxDeg);

        $correctedRAHour = PA_Macros\decimal_hours_hour($correctedRAHours);
        $correctedRAMin = PA_Macros\decimal_hours_minute($correctedRAHours);
        $correctedRASec = PA_Macros\decimal_hours_second($correctedRAHours);
        $correctedDecDeg = PA_Macros\decimal_degrees_degrees($correctedDecDeg1);
        $correctedDecMin = PA_Macros\decimal_degrees_minutes($correctedDecDeg1);
        $correctedDecSec = PA_Macros\decimal_degrees_seconds($correctedDecDeg1);

        return array($correctedRAHour, $correctedRAMin, $correctedRASec, $correctedDecDeg, $correctedDecMin, $correctedDecSec);
    }

    /**
     * Calculate heliographic coordinates for a given Greenwich date, with a given heliographic position angle and heliographic displacement in arc minutes.
     */
    function heliographic_coordinates($helioPositionAngleDeg, $helioDisplacementArcmin, $gwdateDay, $gwdateMonth, $gwdateYear)
    {
        $julianDateDays = PA_Macros\civil_date_to_julian_date($gwdateDay, $gwdateMonth, $gwdateYear);
        $tCenturies = ($julianDateDays - 2415020) / 36525;
        $longAscNodeDeg = PA_Macros\degrees_minutes_seconds_to_decimal_degrees(74, 22, 0) + (84 * $tCenturies / 60);
        $sunLongDeg = PA_Macros\sun_long(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $y = sin(deg2rad($longAscNodeDeg - $sunLongDeg))  * cos(deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees(7, 15, 0)));
        $x = -cos(deg2rad($longAscNodeDeg - $sunLongDeg));
        $aDeg = PA_Macros\degrees(atan2($y, $x));
        $mDeg1 = 360 - (360 * ($julianDateDays - 2398220) / 25.38);
        $mDeg2 = $mDeg1 - 360 * floor($mDeg1 / 360);
        $l0Deg1 = $mDeg2 + $aDeg;
        $b0Rad = asin(sin(deg2rad($sunLongDeg - $longAscNodeDeg)) * sin(deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees(7, 15, 0))));
        $theta1Rad = atan(-cos(deg2rad($sunLongDeg)) * tan(deg2rad(PA_Macros\obliq($gwdateDay, $gwdateMonth, $gwdateYear))));
        $theta2Rad = atan(-cos(deg2rad($longAscNodeDeg - $sunLongDeg)) * tan(deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees(7, 15, 0))));
        $pDeg = PA_Macros\degrees($theta1Rad + $theta2Rad);
        $rho1Deg = $helioDisplacementArcmin / 60;
        $rhoRad = asin(2 * $rho1Deg / PA_Macros\sun_dia(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear)) - deg2rad($rho1Deg);
        $bRad = asin(sin($b0Rad) * cos($rhoRad) + cos($b0Rad) * sin($rhoRad) * cos(deg2rad($pDeg - $helioPositionAngleDeg)));
        $bDeg = PA_Macros\degrees($bRad);
        $lDeg1 = PA_Macros\degrees(asin(sin($rhoRad) * sin(deg2rad($pDeg - $helioPositionAngleDeg)) / cos($bRad))) + $l0Deg1;
        $lDeg2 = $lDeg1 - 360 * floor($lDeg1 / 360);

        $helioLongDeg = round($lDeg2, 2);
        $helioLatDeg = round($bDeg, 2);

        return array($helioLongDeg, $helioLatDeg);
    }

    /**
     * Calculate carrington rotation number for a Greenwich date
     */
    function carrington_rotation_number($gwdateDay, $gwdateMonth, $gwdateYear)
    {
        $julianDateDays = PA_Macros\civil_date_to_julian_date($gwdateDay, $gwdateMonth, $gwdateYear);

        $crn = 1690 + round(($julianDateDays - 2444235.34) / 27.2753, 0);

        return (int)$crn;
    }

    /**
     * Calculate selenographic (lunar) coordinates (sub-Earth)
     */
    function selenographic_coordinates1($gwdateDay, $gwdateMonth, $gwdateYear)
    {
        $julianDateDays = PA_Macros\civil_date_to_julian_date($gwdateDay, $gwdateMonth, $gwdateYear);
        $tCenturies = ($julianDateDays - 2451545) / 36525;
        $longAscNodeDeg = 125.044522 - 1934.136261 * $tCenturies;
        $f1 = 93.27191 + 483202.0175 * $tCenturies;
        $f2 = $f1 - 360 * floor($f1 / 360);
        $geocentricMoonLongDeg = PA_Macros\moon_long(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $geocentricMoonLatRad = deg2rad(PA_Macros\moon_lat(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear));
        $inclinationRad = deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees(1, 32, 32.7));
        $nodeLongRad = deg2rad($longAscNodeDeg - $geocentricMoonLongDeg);
        $sinBe = - (cos($inclinationRad)) * sin($geocentricMoonLatRad) + sin($inclinationRad) * cos($geocentricMoonLatRad) * sin($nodeLongRad);
        $subEarthLatDeg = PA_Macros\degrees(asin($sinBe));
        $aRad = atan2((-sin($geocentricMoonLatRad) * sin($inclinationRad) - cos($geocentricMoonLatRad) * cos($inclinationRad) * sin($nodeLongRad)), (cos($geocentricMoonLatRad) * cos($nodeLongRad)));
        $aDeg = PA_Macros\degrees($aRad);
        $subEarthLongDeg1 = $aDeg - $f2;
        $subEarthLongDeg2 = $subEarthLongDeg1 - 360 * floor($subEarthLongDeg1 / 360);
        $subEarthLongDeg3 = ($subEarthLongDeg2 > 180) ? $subEarthLongDeg2 - 360 : $subEarthLongDeg2;
        $c1Rad = atan(cos($nodeLongRad) * sin($inclinationRad) / (cos($geocentricMoonLatRad) * cos($inclinationRad) + sin($geocentricMoonLatRad) * sin($inclinationRad) * sin($nodeLongRad)));
        $obliquityRad = deg2rad(PA_Macros\obliq($gwdateDay, $gwdateMonth, $gwdateYear));
        $c2Rad = atan(sin($obliquityRad) * cos(deg2rad($geocentricMoonLongDeg)) / (sin($obliquityRad) * sin($geocentricMoonLatRad) * sin(deg2rad($geocentricMoonLongDeg)) - cos($obliquityRad) * cos($geocentricMoonLatRad)));
        $cDeg = PA_Macros\degrees($c1Rad + $c2Rad);

        $subEarthLongitude = round($subEarthLongDeg3, 2);
        $subEarthLatitude = round($subEarthLatDeg, 2);
        $positionAngleOfPole = round($cDeg, 2);

        return array($subEarthLongitude, $subEarthLatitude, $positionAngleOfPole);
    }

    /**
     * Calculate selenographic (lunar) coordinates (sub-Solar)
     */
    function selenographic_coordinates2($gwdateDay, $gwdateMonth, $gwdateYear)
    {
        $julianDateDays = PA_Macros\civil_date_to_julian_date($gwdateDay, $gwdateMonth, $gwdateYear);
        $tCenturies = ($julianDateDays - 2451545) / 36525;
        $longAscNodeDeg = 125.044522 - 1934.136261 * $tCenturies;
        $f1 = 93.27191 + 483202.0175 * $tCenturies;
        $f2 = $f1 - 360 * floor($f1 / 360);
        $sunGeocentricLongDeg = PA_Macros\sun_long(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $moonEquHorParallaxArcMin = PA_Macros\moon_hp(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear) * 60;
        $sunEarthDistAU = PA_Macros\sun_dist(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $geocentricMoonLatRad = deg2rad(PA_Macros\moon_lat(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear));
        $geocentricMoonLongDeg = PA_Macros\moon_long(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $adjustedMoonLongDeg = $sunGeocentricLongDeg + 180 + (26.4 * cos($geocentricMoonLatRad) * sin(deg2rad($sunGeocentricLongDeg - $geocentricMoonLongDeg)) / ($moonEquHorParallaxArcMin * $sunEarthDistAU));
        $adjustedMoonLatRad = 0.14666 * $geocentricMoonLatRad / ($moonEquHorParallaxArcMin * $sunEarthDistAU);
        $inclinationRad = deg2rad(PA_Macros\degrees_minutes_seconds_to_decimal_degrees(1, 32, 32.7));
        $nodeLongRad = deg2rad($longAscNodeDeg - $adjustedMoonLongDeg);
        $sinBs = -cos($inclinationRad) * sin($adjustedMoonLatRad) + sin($inclinationRad) * cos($adjustedMoonLatRad) * sin($nodeLongRad);
        $subSolarLatDeg = PA_Macros\degrees(asin($sinBs));
        $aRad = atan2((-sin($adjustedMoonLatRad) * sin($inclinationRad) - cos($adjustedMoonLatRad) * cos($inclinationRad) * sin($nodeLongRad)), (cos($adjustedMoonLatRad) * cos($nodeLongRad)));
        $aDeg = PA_Macros\degrees($aRad);
        $subSolarLongDeg1 = $aDeg - $f2;
        $subSolarLongDeg2 = $subSolarLongDeg1 - 360 * floor($subSolarLongDeg1 / 360);
        $subSolarLongDeg3 = ($subSolarLongDeg2 > 180) ? $subSolarLongDeg2 - 360 : $subSolarLongDeg2;
        $subSolarColongDeg = 90 - $subSolarLongDeg3;

        $subSolarLongitude = round($subSolarLongDeg3, 2);
        $subSolarColongitude = round($subSolarColongDeg, 2);
        $subSolarLatitude = round($subSolarLatDeg, 2);

        return array($subSolarLongitude, $subSolarColongitude, $subSolarLatitude);
    }
}

namespace PA\DateTime {

    use PA\Macros as PA_Macros;
    use PA\Utils as PA_Utils;

    /**
     * Calculates the date of Easter for the year specified.
     */
    function get_date_of_easter($inputYear)
    {
        $year = $inputYear;

        $a = $year % 19;

        $b = floor(($year / 100));
        $c = $year % 100;
        $d = floor(($b / 4));
        $e = $b % 4;
        $f = floor((($b + 8) / 25));
        $g = floor((($b - $f + 1) / 3));
        $h = ((19 * $a) + $b - $d - $g + 15) % 30;
        $i = floor(($c / 4));
        $k = $c % 4;
        $l = (32 + 2 * ($e + $i) - $h - $k) % 7;
        $m = floor((($a + (11 * $h) + (22 * $l)) / 451));
        $n = floor((($h + $l - (7 * $m) + 114) / 31));
        $p = ($h + $l - (7 * $m) + 114) % 31;

        $day = $p + 1;
        $month = $n;

        return array((int) $month, (int) $day, (int) $year);
    }

    /**
     * Calculate day number for a date.
     */
    function civil_date_to_day_number($month, $day, $year)
    {
        if ($month <= 2) {
            $month = $month - 1;
            $month = (PA_Utils\is_leap_year($year)) ? $month * 62 : $month * 63;
            $month = floor((int)((float)$month / 2));
        } else {
            $month = floor((int)(((float)$month + 1) * 30.6));
            $month = (PA_Utils\is_leap_year($year)) ? $month - 62 : $month - 63;
        }

        return $month + $day;
    }


    /**
     * Convert a Civil Time (hours,minutes,seconds) to Decimal Hours
     */
    function civil_time_to_decimal_hours($hours, $minutes, $seconds)
    {
        return PA_Macros\hours_minutes_seconds_to_decimal_hours($hours, $minutes, $seconds);
    }

    /**
     * Convert Decimal Hours to Civil Time
     */
    function decimal_hours_to_civil_time($decimalHours)
    {
        $hours = PA_Macros\decimal_hours_hour($decimalHours);
        $minutes = PA_Macros\decimal_hours_minute($decimalHours);
        $seconds = PA_Macros\decimal_hours_second($decimalHours);

        return array($hours, $minutes, $seconds);
    }

    /**
     * Convert local Civil Time to Universal Time
     */
    function local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $isDaylightSavings, $zoneCorrection, $localDay, $localMonth, $localYear)
    {
        $lct = civil_time_to_decimal_hours($lctHours, $lctMinutes, $lctSeconds);

        $daylightSavingsOffset = ($isDaylightSavings) ? 1 : 0;

        $utInterim = $lct - $daylightSavingsOffset - $zoneCorrection;
        $gdayInterim = $localDay + ($utInterim / 24);

        $jd = PA_Macros\civil_date_to_julian_date($gdayInterim, $localMonth, $localYear);

        $gDay = PA_Macros\julian_date_day($jd);
        $gMonth = PA_Macros\julian_date_month($jd);
        $gYear = PA_Macros\julian_date_year($jd);

        $ut = 24 * ($gDay - floor($gDay));

        $returnValue = array(
            PA_Macros\decimal_hours_hour($ut),
            PA_Macros\decimal_hours_minute($ut),
            (int)PA_Macros\decimal_hours_second($ut),
            (int)floor($gDay),
            $gMonth,
            $gYear
        );

        return $returnValue;
    }

    /**
     * Convert Universal Time to local Civil Time
     */
    function universal_time_to_local_civil_time_dt($utHours, $utMinutes, $utSeconds, $isDaylightSavings, $zoneCorrection, $gwDay, $gwMonth, $gwYear)
    {
        $dstValue = ($isDaylightSavings) ? 1 : 0;
        $ut = PA_Macros\hours_minutes_seconds_to_decimal_hours($utHours, $utMinutes, $utSeconds);
        $zoneTime = $ut + $zoneCorrection;
        $localTime = $zoneTime + $dstValue;
        $localJDPlusLocalTime = PA_Macros\civil_date_to_julian_date($gwDay, $gwMonth, $gwYear) + ($localTime / 24);
        $localDay = PA_Macros\julian_date_day($localJDPlusLocalTime);
        $integerDay = floor($localDay);
        $localMonth = PA_Macros\julian_date_month($localJDPlusLocalTime);
        $localYear = PA_Macros\julian_date_year($localJDPlusLocalTime);

        $lct = 24 * ($localDay - $integerDay);

        return array(
            PA_Macros\decimal_hours_hour($lct),
            PA_Macros\decimal_hours_minute($lct),
            (int) PA_Macros\decimal_hours_second($lct),
            (int) $integerDay,
            $localMonth,
            $localYear
        );
    }

    /**
     * Convert Universal Time to Greenwich Sidereal Time
     */
    function universal_time_to_greenwich_sidereal_time($utHours, $utMinutes, $utSeconds, $gwDay, $gwMonth, $gwYear)
    {
        $jd = PA_Macros\civil_date_to_julian_date($gwDay, $gwMonth, $gwYear);
        $s = $jd - 2451545;
        $t = $s / 36525;
        $t01 = 6.697374558 + (2400.051336 * $t) + (0.000025862 * $t * $t);
        $t02 = $t01 - (24.0 * floor($t01 / 24));
        $ut = PA_Macros\hours_minutes_seconds_to_decimal_hours($utHours, $utMinutes, $utSeconds);
        $a = $ut * 1.002737909;
        $gst1 = $t02 + $a;
        $gst2 = $gst1 - (24.0 * floor($gst1 / 24));

        $gstHours = PA_Macros\decimal_hours_hour($gst2);
        $gstMinutes = PA_Macros\decimal_hours_minute($gst2);
        $gstSeconds = PA_Macros\decimal_hours_second($gst2);

        return array($gstHours, $gstMinutes, $gstSeconds);
    }

    /**
     * Convert Greenwich Sidereal Time to Universal Time
     */
    function greenwich_sidereal_time_to_universal_time($gstHours, $gstMinutes, $gstSeconds, $gwDay, $gwMonth, $gwYear)
    {
        $jd = PA_Macros\civil_date_to_julian_date($gwDay, $gwMonth, $gwYear);
        $s = $jd - 2451545;
        $t = $s / 36525;
        $t01 = 6.697374558 + (2400.051336 * $t) + (0.000025862 * $t * $t);
        $t02 = $t01 - (24 * floor($t01 / 24));
        $gstHours1 = PA_Macros\hours_minutes_seconds_to_decimal_hours($gstHours, $gstMinutes, $gstSeconds);

        $a = $gstHours1 - $t02;
        $b = $a - (24 * floor($a / 24));
        $ut = $b * 0.9972695663;
        $utHours = PA_Macros\decimal_hours_hour($ut);
        $utMinutes = PA_Macros\decimal_hours_minute($ut);
        $utSeconds = PA_Macros\decimal_hours_second($ut);

        $warningFlag = ($ut < 0.065574) ? "Warning" : "OK";

        return array($utHours, $utMinutes, $utSeconds, $warningFlag);
    }

    /**
     * Convert Greenwich Sidereal Time to Local Sidereal Time
     */
    function greenwich_sidereal_time_to_local_sidereal_time($gstHours, $gstMinutes, $gstSeconds, $geographicalLongitude)
    {
        $gst = PA_Macros\hours_minutes_seconds_to_decimal_hours($gstHours, $gstMinutes, $gstSeconds);
        $offset = $geographicalLongitude / 15;  // Convert longitude to hours

        // Handle negative longitudes
        if ($offset < 0) {
            $offset += 24;
        }

        $lstHours1 = $gst + $offset;
        $lstHours2 = $lstHours1 - (24 * floor($lstHours1 / 24));

        $lstHours = PA_Macros\decimal_hours_hour($lstHours2);
        $lstMinutes = PA_Macros\decimal_hours_minute($lstHours2);
        $lstSeconds = PA_Macros\decimal_hours_second($lstHours2);

        return array($lstHours, $lstMinutes, $lstSeconds);
    }

    /**
     * Convert Local Sidereal Time to Greenwich Sidereal Time
     */
    function local_sidereal_time_to_greenwich_sidereal_time($lstHours, $lstMinutes, $lstSeconds, $geographicalLongitude)
    {
        $gst = PA_Macros\hours_minutes_seconds_to_decimal_hours($lstHours, $lstMinutes, $lstSeconds);
        $longHours = $geographicalLongitude / 15;
        $gst1 = $gst - $longHours;
        $gst2 = $gst1 - (24 * floor($gst1 / 24));

        $gstHours = PA_Macros\decimal_hours_hour($gst2);
        $gstMinutes = PA_Macros\decimal_hours_minute($gst2);
        $gstSeconds = PA_Macros\decimal_hours_second($gst2);

        return array($gstHours, $gstMinutes, $gstSeconds);
    }
}

namespace PA\Eclipses {

    use function PA\Macros\decimal_hours_hour;
    use function PA\Macros\decimal_hours_minute;
    use function PA\Macros\full_moon;
    use function PA\Macros\julian_date_day;
    use function PA\Macros\julian_date_month;
    use function PA\Macros\julian_date_year;
    use function PA\Macros\lunar_eclipse_occurrence;
    use function PA\Macros\mag_lunar_eclipse;
    use function PA\Macros\mag_solar_eclipse;
    use function PA\Macros\new_moon;
    use function PA\Macros\solar_eclipse_occurrence as solar_eclipse_occurrence_ma;
    use function PA\Macros\universal_time_local_civil_day;
    use function PA\Macros\universal_time_local_civil_month;
    use function PA\Macros\universal_time_local_civil_year;
    use function PA\Macros\ut_end_total_lunar_eclipse;
    use function PA\Macros\ut_end_umbra_lunar_eclipse;
    use function PA\Macros\ut_first_contact_lunar_eclipse;
    use function PA\Macros\ut_first_contact_solar_eclipse;
    use function PA\Macros\ut_last_contact_lunar_eclipse;
    use function PA\Macros\ut_last_contact_solar_eclipse;
    use function PA\Macros\ut_max_lunar_eclipse;
    use function PA\Macros\ut_max_solar_eclipse;
    use function PA\Macros\ut_start_total_lunar_eclipse;
    use function PA\Macros\ut_start_umbra_lunar_eclipse;

    /** Determine if a lunar eclipse is likely to occur. */
    function lunar_eclipse_occurrence_details($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $julianDateOfFullMoon = full_moon($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $gDateOfFullMoonDay = julian_date_day($julianDateOfFullMoon);
        $integerDay = floor($gDateOfFullMoonDay);
        $gDateOfFullMoonMonth = julian_date_month($julianDateOfFullMoon);
        $gDateOfFullMoonYear = julian_date_year($julianDateOfFullMoon);
        $utOfFullMoonHours = $gDateOfFullMoonDay - $integerDay;

        $localCivilDateDay = universal_time_local_civil_day($utOfFullMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);
        $localCivilDateMonth = universal_time_local_civil_month($utOfFullMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);
        $localCivilDateYear = universal_time_local_civil_year($utOfFullMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);

        $eclipseOccurrence = lunar_eclipse_occurrence($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $status = $eclipseOccurrence;
        $eventDateDay = $localCivilDateDay;
        $eventDateMonth = $localCivilDateMonth;
        $eventDateYear = $localCivilDateYear;

        return array($status, $eventDateDay, $eventDateMonth, $eventDateYear);
    }

    /** * Calculate the circumstances of a lunar eclipse.  */
    function lunar_eclipse_circumstances($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $julianDateOfFullMoon = full_moon($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gDateOfFullMoonDay = julian_date_day($julianDateOfFullMoon);
        $integerDay = floor($gDateOfFullMoonDay);
        $gDateOfFullMoonMonth = julian_date_month($julianDateOfFullMoon);
        $gDateOfFullMoonYear = julian_date_year($julianDateOfFullMoon);
        $utOfFullMoonHours = $gDateOfFullMoonDay - $integerDay;

        $localCivilDateDay = universal_time_local_civil_day($utOfFullMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);
        $localCivilDateMonth = universal_time_local_civil_month($utOfFullMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);
        $localCivilDateYear = universal_time_local_civil_year($utOfFullMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);

        $utMaxEclipse = ut_max_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);
        $utFirstContact = ut_first_contact_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);
        $utLastContact = ut_last_contact_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);
        $utStartUmbralPhase = ut_start_umbra_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);
        $utEndUmbralPhase = ut_end_umbra_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);
        $utStartTotalPhase = ut_start_total_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);
        $utEndTotalPhase = ut_end_total_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);

        $eclipseMagnitude1 = mag_lunar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours);

        $lunarEclipseCertainDateDay = $localCivilDateDay;
        $lunarEclipseCertainDateMonth = $localCivilDateMonth;
        $lunarEclipseCertainDateYear = $localCivilDateYear;

        $utStartPenPhaseHour = ($utFirstContact == -99.0) ? -99.0 : decimal_hours_hour($utFirstContact + 0.008333);
        $utStartPenPhaseMinutes = ($utFirstContact == -99.0) ? -99.0 : decimal_hours_minute($utFirstContact + 0.008333);

        $utStartUmbralPhaseHour = ($utStartUmbralPhase == -99.0) ? -99.0 : decimal_hours_hour($utStartUmbralPhase + 0.008333);
        $utStartUmbralPhaseMinutes = ($utStartUmbralPhase == -99.0) ? -99.0 : decimal_hours_minute($utStartUmbralPhase + 0.008333);

        $utStartTotalPhaseHour = ($utStartTotalPhase == -99.0) ? -99.0 : decimal_hours_hour($utStartTotalPhase + 0.008333);
        $utStartTotalPhaseMinutes = ($utStartTotalPhase == -99.0) ? -99.0 : decimal_hours_minute($utStartTotalPhase + 0.008333);

        $utMidEclipseHour = ($utMaxEclipse == -99.0) ? -99.0 : decimal_hours_hour($utMaxEclipse + 0.008333);
        $utMidEclipseMinutes = ($utMaxEclipse == -99.0) ? -99.0 : decimal_hours_minute($utMaxEclipse + 0.008333);

        $utEndTotalPhaseHour = ($utEndTotalPhase == -99.0) ? -99.0 : decimal_hours_hour($utEndTotalPhase + 0.008333);
        $utEndTotalPhaseMinutes = ($utEndTotalPhase == -99.0) ? -99.0 : decimal_hours_minute($utEndTotalPhase + 0.008333);

        $utEndUmbralPhaseHour = ($utEndUmbralPhase == -99.0) ? -99.0 : decimal_hours_hour($utEndUmbralPhase + 0.008333);
        $utEndUmbralPhaseMinutes = ($utEndUmbralPhase == -99.0) ? -99.0 : decimal_hours_minute($utEndUmbralPhase + 0.008333);

        $utEndPenPhaseHour = ($utLastContact == -99.0) ? -99.0 : decimal_hours_hour($utLastContact + 0.008333);
        $utEndPenPhaseMinutes = ($utLastContact == -99.0) ? -99.0 : decimal_hours_minute($utLastContact + 0.008333);

        $eclipseMagnitude = ($eclipseMagnitude1 == -99.0) ? -99.0 : round($eclipseMagnitude1, 2);

        return array($lunarEclipseCertainDateDay, $lunarEclipseCertainDateMonth, $lunarEclipseCertainDateYear, $utStartPenPhaseHour, $utStartPenPhaseMinutes, $utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes, $utStartTotalPhaseHour, $utStartTotalPhaseMinutes, $utMidEclipseHour, $utMidEclipseMinutes, $utEndTotalPhaseHour, $utEndTotalPhaseMinutes, $utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes, $utEndPenPhaseHour, $utEndPenPhaseMinutes, $eclipseMagnitude);
    }

    /** Determine if a solar eclipse is likely to occur. */
    function solar_eclipse_occurrence($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $julianDateOfNewMoon = new_moon($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gDateOfNewMoonDay = julian_date_day($julianDateOfNewMoon);
        $integerDay = floor($gDateOfNewMoonDay);
        $gDateOfNewMoonMonth = julian_date_month($julianDateOfNewMoon);
        $gDateOfNewMoonYear = julian_date_year($julianDateOfNewMoon);
        $utOfNewMoonHours = $gDateOfNewMoonDay - $integerDay;

        $localCivilDateDay = universal_time_local_civil_day($utOfNewMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $localCivilDateMonth = universal_time_local_civil_month($utOfNewMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $localCivilDateYear = universal_time_local_civil_year($utOfNewMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);

        $eclipseOccurrence = solar_eclipse_occurrence_ma($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $status = $eclipseOccurrence;
        $eventDateDay = $localCivilDateDay;
        $eventDateMonth = $localCivilDateMonth;
        $eventDateYear = $localCivilDateYear;

        return array($status, $eventDateDay, $eventDateMonth, $eventDateYear);
    }

    /** Calculate the circumstances of a solar eclipse. */
    function solar_eclipse_circumstances($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $julianDateOfNewMoon = new_moon($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gDateOfNewMoonDay = julian_date_day($julianDateOfNewMoon);
        $integerDay = floor($gDateOfNewMoonDay);
        $gDateOfNewMoonMonth = julian_date_month($julianDateOfNewMoon);
        $gDateOfNewMoonYear = julian_date_year($julianDateOfNewMoon);
        $utOfNewMoonHours = $gDateOfNewMoonDay - $integerDay;
        $localCivilDateDay = universal_time_local_civil_day($utOfNewMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $localCivilDateMonth = universal_time_local_civil_month($utOfNewMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $localCivilDateYear = universal_time_local_civil_year($utOfNewMoonHours, 0.0, 0.0, $daylightSaving, $zoneCorrectionHours, $integerDay, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);

        $utMaxEclipse = ut_max_solar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg);
        $utFirstContact = ut_first_contact_solar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg);
        $utLastContact = ut_last_contact_solar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg);
        $magnitude = mag_solar_eclipse($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg);

        $solarEclipseCertainDateDay = $localCivilDateDay;
        $solarEclipseCertainDateMonth = $localCivilDateMonth;
        $solarEclipseCertainDateYear = $localCivilDateYear;

        $utFirstContactHour = ($utFirstContact == -99.0)
            ? -99.0
            : decimal_hours_hour($utFirstContact + 0.008333);
        $utFirstContactMinutes = ($utFirstContact == -99.0)
            ? -99.0
            : decimal_hours_minute($utFirstContact + 0.008333);

        $utMidEclipseHour = ($utMaxEclipse == -99.0)
            ? -99.0
            : decimal_hours_hour($utMaxEclipse + 0.008333);
        $utMidEclipseMinutes = ($utMaxEclipse == -99.0)
            ? -99.0
            : decimal_hours_minute($utMaxEclipse + 0.008333);

        $utLastContactHour = ($utLastContact == -99.0)
            ? -99.0
            : decimal_hours_hour($utLastContact + 0.008333);
        $utLastContactMinutes = ($utLastContact == -99.0)
            ? -99.0
            : decimal_hours_minute($utLastContact + 0.008333);

        $eclipseMagnitude = ($magnitude == -99.0)
            ? -99.0
            : round($magnitude, 3);

        return array($solarEclipseCertainDateDay, $solarEclipseCertainDateMonth, $solarEclipseCertainDateYear, $utFirstContactHour, $utFirstContactMinutes, $utMidEclipseHour, $utMidEclipseMinutes, $utLastContactHour, $utLastContactMinutes, $eclipseMagnitude);
    }
}

namespace PA\Moon {

    use PA\Types\AccuracyLevel;

    use function PA\Macros\civil_date_to_julian_date;
    use function PA\Macros\decimal_degrees_degrees;
    use function PA\Macros\decimal_degrees_minutes;
    use function PA\Macros\decimal_degrees_seconds;
    use function PA\Macros\decimal_degrees_to_degree_hours;
    use function PA\Macros\decimal_hours_hour;
    use function PA\Macros\decimal_hours_minute;
    use function PA\Macros\decimal_hours_second;
    use function PA\Macros\ec_dec;
    use function PA\Macros\ec_ra;
    use function PA\Macros\full_moon;
    use function PA\Macros\julian_date_day;
    use function PA\Macros\julian_date_month;
    use function PA\Macros\julian_date_year;
    use function PA\Macros\local_civil_time_greenwich_day;
    use function PA\Macros\local_civil_time_greenwich_month;
    use function PA\Macros\local_civil_time_greenwich_year;
    use function PA\Macros\local_civil_time_to_universal_time;
    use function PA\Macros\moon_dist;
    use function PA\Macros\moon_hp;
    use function PA\Macros\moon_long_lat_hp;
    use function PA\Macros\moon_phase_ma;
    use function PA\Macros\moon_rise_az;
    use function PA\Macros\moon_rise_lc_dmy;
    use function PA\Macros\moon_rise_lct;
    use function PA\Macros\moon_set_az;
    use function PA\Macros\moon_set_lc_dmy;
    use function PA\Macros\moon_set_lct;
    use function PA\Macros\moon_size;
    use function PA\Macros\new_moon;
    use function PA\Macros\nutat_long;
    use function PA\Macros\sun_long;
    use function PA\Macros\sun_mean_anomaly;
    use function PA\Macros\universal_time_to_local_civil_time_ma;
    use function PA\Macros\universal_time_local_civil_day;
    use function PA\Macros\universal_time_local_civil_month;
    use function PA\Macros\universal_time_local_civil_year;
    use function PA\Macros\unwind_deg;
    use function PA\Macros\w_to_degrees;

    /** Calculate approximate position of the Moon. */
    function approximate_position_of_moon($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $l0 = 91.9293359879052;
        $p0 = 130.143076320618;
        $n0 = 291.682546643194;
        $i = 5.145396;

        $gdateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $utHours = local_civil_time_to_universal_time($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $dDays = civil_date_to_julian_date($gdateDay, $gdateMonth, $gdateYear) - civil_date_to_julian_date(0.0, 1, 2010) + $utHours / 24;
        $sunLongDeg = sun_long($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $sunMeanAnomalyRad = sun_mean_anomaly($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $lmDeg = unwind_deg(13.1763966 * $dDays + $l0);
        $mmDeg = unwind_deg($lmDeg - 0.1114041 * $dDays - $p0);
        $nDeg = unwind_deg($n0 - (0.0529539 * $dDays));
        $evDeg = 1.2739 * sin(deg2rad(2.0 * ($lmDeg - $sunLongDeg) - $mmDeg));
        $aeDeg = 0.1858 * sin($sunMeanAnomalyRad);
        $a3Deg = 0.37 * sin($sunMeanAnomalyRad);
        $mmdDeg = $mmDeg + $evDeg - $aeDeg - $a3Deg;
        $ecDeg = 6.2886 * sin(deg2rad($mmdDeg));
        $a4Deg = 0.214 * sin(2.0 * deg2rad($mmdDeg));
        $ldDeg = $lmDeg + $evDeg + $ecDeg - $aeDeg + $a4Deg;
        $vDeg = 0.6583 * sin(2.0 * deg2rad($ldDeg - $sunLongDeg));
        $lddDeg = $ldDeg + $vDeg;
        $ndDeg = $nDeg - 0.16 * sin($sunMeanAnomalyRad);
        $y = sin(deg2rad($lddDeg - $ndDeg)) * cos(deg2rad($i));
        $x = cos(deg2rad($lddDeg - $ndDeg));

        $moonLongDeg = unwind_deg(w_to_degrees(atan2($y, $x)) + $ndDeg);
        $moonLatDeg = w_to_degrees(asin(sin(deg2rad($lddDeg - $ndDeg)) * sin(deg2rad($i))));
        $moonRAHours1 = decimal_degrees_to_degree_hours(ec_ra($moonLongDeg, 0, 0, $moonLatDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear));
        $moonDecDeg1 = ec_dec($moonLongDeg, 0, 0, $moonLatDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear);

        $moonRAHour = decimal_hours_hour($moonRAHours1);
        $moonRAMin = decimal_hours_minute($moonRAHours1);
        $moonRASec = decimal_hours_second($moonRAHours1);
        $moonDecDeg = decimal_degrees_degrees($moonDecDeg1);
        $moonDecMin = decimal_degrees_minutes($moonDecDeg1);
        $moonDecSec = decimal_degrees_seconds($moonDecDeg1);

        return array($moonRAHour, $moonRAMin, $moonRASec, $moonDecDeg, $moonDecMin, $moonDecSec);
    }

    /** Calculate precise position of the Moon. */
    function precise_position_of_moon($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $gdateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        list($ml_moonLongDeg, $ml_moonLatDeg, $ml_moonHorPara) =
            moon_long_lat_hp($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $nutationInLongitudeDeg = nutat_long($gdateDay, $gdateMonth, $gdateYear);
        $correctedLongDeg = $ml_moonLongDeg + $nutationInLongitudeDeg;
        $earthMoonDistanceKM = 6378.14 / sin(deg2rad($ml_moonHorPara));
        $moonRAHours1 = decimal_degrees_to_degree_hours(ec_ra($correctedLongDeg, 0, 0, $ml_moonLatDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear));
        $moonDecDeg1 = ec_dec($correctedLongDeg, 0, 0, $ml_moonLatDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear);

        $moonRAHour = decimal_hours_hour($moonRAHours1);
        $moonRAMin = decimal_hours_minute($moonRAHours1);
        $moonRASec = decimal_hours_second($moonRAHours1);
        $moonDecDeg = decimal_degrees_degrees($moonDecDeg1);
        $moonDecMin = decimal_degrees_minutes($moonDecDeg1);
        $moonDecSec = decimal_degrees_seconds($moonDecDeg1);
        $earthMoonDistKM = round($earthMoonDistanceKM, 0);
        $moonHorParallaxDeg = round($ml_moonHorPara, 6);

        return array($moonRAHour, $moonRAMin, $moonRASec, $moonDecDeg, $moonDecMin, $moonDecSec, $earthMoonDistKM, $moonHorParallaxDeg);
    }

    /** Calculate Moon phase and position angle of bright limb. */
    function moon_phase($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $accuracyLevel)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $gdateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $sunLongDeg = sun_long($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        list($moonLongDeg, $moonLatDeg, $moonHorPara) = moon_long_lat_hp($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $dRad = deg2rad($moonLongDeg - $sunLongDeg);

        $moonPhase1 = ($accuracyLevel == AccuracyLevel::Precise)
            ? moon_phase_ma($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear)
            : (1.0 - cos($dRad)) / 2.0;

        $sunRARad = deg2rad(ec_ra($sunLongDeg, 0, 0, 0, 0, 0, $gdateDay, $gdateMonth, $gdateYear));
        $moonRARad = deg2rad(ec_ra($moonLongDeg, 0, 0, $moonLatDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear));
        $sunDecRad = deg2rad(ec_dec($sunLongDeg, 0, 0, 0, 0, 0, $gdateDay, $gdateMonth, $gdateYear));
        $moonDecRad = deg2rad(ec_dec($moonLongDeg, 0, 0, $moonLatDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear));

        $y = cos($sunDecRad) * sin($sunRARad - $moonRARad);
        $x = cos($moonDecRad) * sin($sunDecRad) - sin($moonDecRad) * cos($sunDecRad) * cos($sunRARad - $moonRARad);

        $chiDeg = w_to_degrees(atan2($y, $x));

        $moonPhase = round($moonPhase1, 2);
        $paBrightLimbDeg = round($chiDeg, 2);

        return array($moonPhase, $paBrightLimbDeg);
    }

    /** Calculate new moon and full moon instances. */
    function times_of_new_moon_and_full_moon($isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $jdOfNewMoonDays =  new_moon($daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $jdOfFullMoonDays = full_moon(3, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $gDateOfNewMoonDay = julian_date_day($jdOfNewMoonDays);
        $integerDay1 = floor($gDateOfNewMoonDay);
        $gDateOfNewMoonMonth = julian_date_month($jdOfNewMoonDays);
        $gDateOfNewMoonYear = julian_date_year($jdOfNewMoonDays);

        $gDateOfFullMoonDay = julian_date_day($jdOfFullMoonDays);
        $integerDay2 = floor($gDateOfFullMoonDay);
        $gDateOfFullMoonMonth = julian_date_month($jdOfFullMoonDays);
        $gDateOfFullMoonYear = julian_date_year($jdOfFullMoonDays);

        $utOfNewMoonHours = 24.0 * ($gDateOfNewMoonDay - $integerDay1);
        $utOfFullMoonHours = 24.0 * ($gDateOfFullMoonDay - $integerDay2);
        $lctOfNewMoonHours = universal_time_to_local_civil_time_ma($utOfNewMoonHours + 0.008333, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay1, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $lctOfFullMoonHours = universal_time_to_local_civil_time_ma($utOfFullMoonHours + 0.008333, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay2, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);

        $nmLocalTimeHour = decimal_hours_hour($lctOfNewMoonHours);
        $nmLocalTimeMin = decimal_hours_minute($lctOfNewMoonHours);
        $nmLocalDateDay = universal_time_local_civil_day($utOfNewMoonHours, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay1, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $nmLocalDateMonth = universal_time_local_civil_month($utOfNewMoonHours, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay1, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $nmLocalDateYear = universal_time_local_civil_year($utOfNewMoonHours, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay1, $gDateOfNewMoonMonth, $gDateOfNewMoonYear);
        $fmLocalTimeHour = decimal_hours_hour($lctOfFullMoonHours);
        $fmLocalTimeMin = decimal_hours_minute($lctOfFullMoonHours);
        $fmLocalDateDay = universal_time_local_civil_day($utOfFullMoonHours, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay2, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);
        $fmLocalDateMonth = universal_time_local_civil_month($utOfFullMoonHours, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay2, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);
        $fmLocalDateYear = universal_time_local_civil_year($utOfFullMoonHours, 0, 0, $daylightSaving, $zoneCorrectionHours, $integerDay2, $gDateOfFullMoonMonth, $gDateOfFullMoonYear);

        return array($nmLocalTimeHour, $nmLocalTimeMin, $nmLocalDateDay, $nmLocalDateMonth, $nmLocalDateYear, $fmLocalTimeHour, $fmLocalTimeMin, $fmLocalDateDay, $fmLocalDateMonth, $fmLocalDateYear);
    }

    /** Calculate Moon's distance, angular diameter, and horizontal parallax. */
    function moon_dist_ang_diam_hor_parallax($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $moonDistance = moon_dist($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $moonAngularDiameter = moon_size($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $moonHorizontalParallax = moon_hp($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $earthMoonDist = round($moonDistance, 0);
        $angDiameterDeg = decimal_degrees_degrees($moonAngularDiameter + 0.008333);
        $angDiameterMin = decimal_degrees_minutes($moonAngularDiameter + 0.008333);
        $horParallaxDeg = decimal_degrees_degrees($moonHorizontalParallax);
        $horParallaxMin = decimal_degrees_minutes($moonHorizontalParallax);
        $horParallaxSec = decimal_degrees_seconds($moonHorizontalParallax);

        return array($earthMoonDist, $angDiameterDeg, $angDiameterMin, $horParallaxDeg, $horParallaxMin, $horParallaxSec);
    }

    /** Calculate date/time of local moonrise and moonset. */
    function moonrise_and_moonset($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $localTimeOfMoonriseHours = moon_rise_lct($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);
        list($moonRiseLCResult_dy1, $moonRiseLCResult_mn1, $moonRiseLCResult_yr1) =
            moon_rise_lc_dmy($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);
        $localAzimuthDeg1 = moon_rise_az($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);

        $localTimeOfMoonsetHours = moon_set_lct($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);
        list($moonSetLCResult_dy1, $moonSetLCResult_mn1, $moonSetLCResult_yr1) =
            moon_set_lc_dmy($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);
        $localAzimuthDeg2 = moon_set_az($localDateDay, $localDateMonth, $localDateYear, $daylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);

        $mrLTHour = decimal_hours_hour($localTimeOfMoonriseHours + 0.008333);
        $mrLTMin = decimal_hours_minute($localTimeOfMoonriseHours + 0.008333);
        $mrLocalDateDay = $moonRiseLCResult_dy1;
        $mrLocalDateMonth = $moonRiseLCResult_mn1;
        $mrLocalDateYear = $moonRiseLCResult_yr1;
        $mrAzimuthDeg = round($localAzimuthDeg1, 2);
        $msLTHour = decimal_hours_hour($localTimeOfMoonsetHours + 0.008333);
        $msLTMin = decimal_hours_minute($localTimeOfMoonsetHours + 0.008333);
        $msLocalDateDay = $moonSetLCResult_dy1;
        $msLocalDateMonth = $moonSetLCResult_mn1;
        $msLocalDateYear = $moonSetLCResult_yr1;
        $msAzimuthDeg = round($localAzimuthDeg2, 2);

        return array($mrLTHour, $mrLTMin, $mrLocalDateDay, $mrLocalDateMonth, $mrLocalDateYear, $mrAzimuthDeg, $msLTHour, $msLTMin, $msLocalDateDay, $msLocalDateMonth, $msLocalDateYear, $msAzimuthDeg);
    }
}

namespace PA\Planets {

    use PA\Data\Planets\PlanetDataManager;

    use function PA\Macros\civil_date_to_julian_date;
    use function PA\Macros\decimal_degrees_degrees;
    use function PA\Macros\decimal_degrees_minutes;
    use function PA\Macros\decimal_degrees_seconds;
    use function PA\Macros\decimal_degrees_to_degree_hours;
    use function PA\Macros\decimal_hours_hour;
    use function PA\Macros\decimal_hours_minute;
    use function PA\Macros\decimal_hours_second;
    use function PA\Macros\ec_dec;
    use function PA\Macros\ec_ra;
    use function PA\Macros\local_civil_time_to_universal_time;
    use function PA\Macros\local_civil_time_greenwich_day;
    use function PA\Macros\local_civil_time_greenwich_month;
    use function PA\Macros\local_civil_time_greenwich_year;
    use function PA\Macros\planet_coordinates;
    use function PA\Macros\sun_long;
    use function PA\Macros\w_to_degrees;

    /**
     * Calculate approximate position of a planet.
     */
    function approximate_position_of_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName)
    {
        $planetDataManager = new PlanetDataManager();

        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $planetData = $planetDataManager->GetPlanetRecord($planetName);

        $gdateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $gdateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        $utHours = local_civil_time_to_universal_time($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $dDays = civil_date_to_julian_date($gdateDay + ($utHours / 24), $gdateMonth, $gdateYear) - civil_date_to_julian_date(0, 1, 2010);
        $npDeg1 = 360 * $dDays / (365.242191 * $planetData->tp_PeriodOrbit);
        $npDeg2 = $npDeg1 - 360 * floor($npDeg1 / 360);
        $mpDeg = $npDeg2 + $planetData->long_LongitudeEpoch - $planetData->peri_LongitudePerihelion;
        $lpDeg1 = $npDeg2 + (360 * $planetData->ecc_EccentricityOrbit * sin(deg2rad($mpDeg)) / pi()) + $planetData->long_LongitudeEpoch;
        $lpDeg2 = $lpDeg1 - 360 * floor($lpDeg1 / 360);
        $planetTrueAnomalyDeg = $lpDeg2 - $planetData->peri_LongitudePerihelion;
        $rAU = $planetData->axis_AxisOrbit * (1 - pow($planetData->ecc_EccentricityOrbit, 2)) / (1 + $planetData->ecc_EccentricityOrbit * cos(deg2rad($planetTrueAnomalyDeg)));

        $earthData = $planetDataManager->GetPlanetRecord("Earth");

        $neDeg1 = 360 * $dDays / (365.242191 * $earthData->tp_PeriodOrbit);
        $neDeg2 = $neDeg1 - 360 * floor($neDeg1 / 360);
        $meDeg = $neDeg2 + $earthData->long_LongitudeEpoch - $earthData->peri_LongitudePerihelion;
        $leDeg1 = $neDeg2 + $earthData->long_LongitudeEpoch + 360 * $earthData->ecc_EccentricityOrbit * sin(deg2rad($meDeg)) / pi();
        $leDeg2 = $leDeg1 - 360 * floor($leDeg1 / 360);
        $earthTrueAnomalyDeg = $leDeg2 - $earthData->peri_LongitudePerihelion;
        $rAU2 = $earthData->axis_AxisOrbit * (1 - pow($earthData->ecc_EccentricityOrbit, 2)) / (1 + $earthData->ecc_EccentricityOrbit * cos(deg2rad($earthTrueAnomalyDeg)));
        $lpNodeRad = deg2rad($lpDeg2 - $planetData->node_LongitudeAscendingNode);
        $psiRad = asin(sin($lpNodeRad) * sin(deg2rad($planetData->incl_OrbitalInclination)));
        $y = sin($lpNodeRad) * cos(deg2rad($planetData->incl_OrbitalInclination));
        $x = cos($lpNodeRad);
        $ldDeg =  w_to_degrees(atan2($y, $x)) + $planetData->node_LongitudeAscendingNode;
        $rdAU = $rAU * cos($psiRad);
        $leLdRad = deg2rad($leDeg2 - $ldDeg);
        $atan2Type1 = atan2(($rdAU * sin($leLdRad)), ($rAU2 - $rdAU * cos($leLdRad)));
        $atan2Type2 = atan2(($rAU2 * sin(-$leLdRad)), ($rdAU - $rAU2 * cos($leLdRad)));
        $aRad = ($rdAU < 1) ? $atan2Type1 : $atan2Type2;
        $lamdaDeg1 = ($rdAU < 1) ? 180 + $leDeg2 +  w_to_degrees($aRad) :  w_to_degrees($aRad) + $ldDeg;
        $lamdaDeg2 = $lamdaDeg1 - 360 * floor($lamdaDeg1 / 360);
        $betaDeg =  w_to_degrees(atan($rdAU * tan($psiRad) * sin(deg2rad($lamdaDeg2 - $ldDeg)) / ($rAU2 * sin(-$leLdRad))));
        $raHours = decimal_degrees_to_degree_hours(ec_ra($lamdaDeg2, 0, 0, $betaDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear));
        $decDeg =  ec_dec($lamdaDeg2, 0, 0, $betaDeg, 0, 0, $gdateDay, $gdateMonth, $gdateYear);

        $planetRAHour = decimal_hours_hour($raHours);
        $planetRAMin = decimal_hours_minute($raHours);
        $planetRASec = decimal_hours_second($raHours);
        $planetDecDeg = decimal_degrees_degrees($decDeg);
        $planetDecMin = decimal_degrees_minutes($decDeg);
        $planetDecSec = decimal_degrees_seconds($decDeg);

        return array($planetRAHour, $planetRAMin, $planetRASec, $planetDecDeg, $planetDecMin, $planetDecSec);
    }

    /**
     * Calculate precise position of a planet.
     */
    function precise_position_of_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        list($planetLongitude, $planetLatitude, $planetDistanceAU, $planetHLong1, $planetHLong2, $planetHLat, $planetRVect) =
            planet_coordinates($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName);

        $planetRAHours = decimal_degrees_to_degree_hours(ec_ra($planetLongitude, 0, 0, $planetLatitude, 0, 0, $localDateDay, $localDateMonth, $localDateYear));
        $planetDecDeg1 = ec_dec($planetLongitude, 0, 0, $planetLatitude, 0, 0, $localDateDay, $localDateMonth, $localDateYear);

        $planetRAHour = decimal_hours_hour($planetRAHours);
        $planetRAMin = decimal_hours_minute($planetRAHours);
        $planetRASec = decimal_hours_second($planetRAHours);
        $planetDecDeg = decimal_degrees_degrees($planetDecDeg1);
        $planetDecMin = decimal_degrees_minutes($planetDecDeg1);
        $planetDecSec = decimal_degrees_seconds($planetDecDeg1);

        return array($planetRAHour, $planetRAMin, $planetRASec, $planetDecDeg, $planetDecMin, $planetDecSec);
    }

    /**
     * Calculate several visual aspects of a planet.
     */
    function visual_aspects_of_a_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $greenwichDateDay = local_civil_time_greenwich_day($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $greenwichDateMonth = local_civil_time_greenwich_month($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $greenwichDateYear = local_civil_time_greenwich_year($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        list($planet_longitude, $planet_latitude, $planet_distance_au, $planet_h_long1, $planet_h_long2, $planet_h_lat, $planet_r_vec) = planet_coordinates($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName);

        $planetRARad = deg2rad(ec_ra($planet_longitude, 0, 0, $planet_latitude, 0, 0, $localDateDay, $localDateMonth, $localDateYear));
        $planetDecRad = deg2rad(ec_dec($planet_longitude, 0, 0, $planet_latitude, 0, 0, $localDateDay, $localDateMonth, $localDateYear));

        $lightTravelTimeHours = $planet_distance_au * 0.1386;

        $planetDataManager = new PlanetDataManager();
        $planetData = $planetDataManager->GetPlanetRecord($planetName);

        $angularDiameterArcsec = $planetData->theta0_AngularDiameter / $planet_distance_au;
        $phase1 = 0.5 * (1.0 + cos(deg2rad(($planet_longitude - $planet_h_long1))));

        $sunEclLongDeg = sun_long($lctHour, $lctMin, $lctSec, $daylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);
        $sunRARad = deg2rad(ec_ra($sunEclLongDeg, 0, 0, 0, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear));
        $sunDecRad = deg2rad(ec_dec($sunEclLongDeg, 0, 0, 0, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear));

        $y = cos($sunDecRad) * sin($sunRARad - $planetRARad);
        $x = cos($planetDecRad) * sin($sunDecRad) - sin($planetDecRad) * cos($sunDecRad) * cos($sunRARad - $planetRARad);
        $chiDeg = w_to_degrees(atan2($y, $x));
        $radiusVectorAU = $planet_r_vec;
        $approximateMagnitude1 = 5.0 * log10($radiusVectorAU * $planet_distance_au / sqrt($phase1)) + $planetData->v0_VisualMagnitude;

        $distanceAU =  round($planet_distance_au, 5);
        $angDiaArcsec = round($angularDiameterArcsec, 1);
        $phase = round($phase1, 2);
        $lightTimeHour = decimal_hours_hour($lightTravelTimeHours);
        $lightTimeMinutes = decimal_hours_minute($lightTravelTimeHours);
        $lightTimeSeconds = decimal_hours_second($lightTravelTimeHours);
        $posAngleBrightLimbDeg = round($chiDeg, 1);
        $approximateMagnitude = round($approximateMagnitude1, 1);

        return array($distanceAU, $angDiaArcsec, $phase, $lightTimeHour, $lightTimeMinutes, $lightTimeSeconds, $posAngleBrightLimbDeg, $approximateMagnitude);
    }
}

namespace PA\Data\Planets {
    class PlanetData
    {
        /**
         * Name of planet.
         */
        public $name;

        /**
         * Period of orbit.
         * 
         * Original element name: tp
         */
        public $tp_PeriodOrbit;

        /**
         * Longitude at the epoch.
         * 
         * Original element name: long
         */
        public $long_LongitudeEpoch;

        /**
         * Longitude of the perihelion.
         * 
         * Original element name: peri
         */
        public $peri_LongitudePerihelion;

        /**
         * Eccentricity of the orbit.
         * 
         * Original element name: ecc
         */
        public $ecc_EccentricityOrbit;

        /**
         * Semi-major axis of the orbit.
         * 
         * Original element name: axis
         */
        public $axis_AxisOrbit;

        /**
         * Orbital inclination.
         * 
         * Original element name: incl
         */
        public $incl_OrbitalInclination;

        /**
         * Longitude of the ascending node.
         * 
         * Original element name: node
         */
        public $node_LongitudeAscendingNode;

        /**
         * Angular diameter at 1 AU.
         * 
         * Original element name: theta0
         */
        public $theta0_AngularDiameter;

        /**
         * Visual magnitude at 1 AU.
         * 
         * Original element name: v0
         */
        public $v0_VisualMagnitude;


        public function __construct($name, $tp_PeriodOrbit, $long_LongitudeEpoch, $peri_LongitudePerihelion, $ecc_EccentricityOrbit, $axis_AxisOrbit, $incl_OrbitalInclination, $node_LongitudeAscendingNode, $theta0_AngularDiameter, $v0_VisualMagnitude)
        {
            $this->name = $name;
            $this->tp_PeriodOrbit = $tp_PeriodOrbit;
            $this->long_LongitudeEpoch = $long_LongitudeEpoch;
            $this->peri_LongitudePerihelion = $peri_LongitudePerihelion;
            $this->ecc_EccentricityOrbit = $ecc_EccentricityOrbit;
            $this->axis_AxisOrbit = $axis_AxisOrbit;
            $this->incl_OrbitalInclination = $incl_OrbitalInclination;
            $this->node_LongitudeAscendingNode = $node_LongitudeAscendingNode;
            $this->theta0_AngularDiameter = $theta0_AngularDiameter;
            $this->v0_VisualMagnitude = $v0_VisualMagnitude;
        }
    }

    class PlanetDataPrecise
    {
        /**
         * Name of planet.
         */
        public $name;

        /**
         * Working value 1.
         */
        public $value1;

        /**
         * Working value 2.
         */
        public $value2;

        /**
         * Working value 3.
         */
        public $value3;

        /**
         * Working value 4.
         */
        public $value4;

        /**
         * Working value 5.
         */
        public $value5;

        /**
         * Working value 6.
         */
        public $value6;

        /**
         * Working value 7.
         */
        public $value7;

        /**
         * Working value 8.
         */
        public $value8;

        /**
         * Working value 9.
         */
        public $value9;

        /**
         * Working AP value.
         */
        public $ap_value;

        public function __construct($name, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $ap_value)
        {
            $this->name = $name;
            $this->value1 = $value1;
            $this->value2 = $value2;
            $this->value3 = $value3;
            $this->value4 = $value4;
            $this->value5 = $value5;
            $this->value6 = $value6;
            $this->value7 = $value7;
            $this->value8 = $value8;
            $this->value9 = $value9;
            $this->ap_value = $ap_value;
        }
    }

    class PlanetDataManager
    {
        public $planetRecords;

        public function __construct()
        {
            $this->planetRecords = [];

            $this->planetRecords[] = new PlanetData("Mercury", 0.24085, 75.5671, 77.612, 0.205627, 0.387098, 7.0051, 48.449, 6.74, -0.42);
            $this->planetRecords[] = new PlanetData("Venus", 0.615207, 272.30044, 131.54, 0.006812, 0.723329, 3.3947, 76.769, 16.92, -4.4);
            $this->planetRecords[] = new PlanetData("Earth", 0.999996, 99.556772, 103.2055, 0.016671, 0.999985, -99.0, -99.0, -99.0, -99.0);
            $this->planetRecords[] = new PlanetData("Mars", 1.880765, 109.09646, 336.217, 0.093348, 1.523689, 1.8497, 49.632, 9.36, -1.52);
            $this->planetRecords[] = new PlanetData("Jupiter", 11.857911, 337.917132, 14.6633, 0.048907, 5.20278, 1.3035, 100.595, 196.74, -9.4);
            $this->planetRecords[] = new PlanetData("Saturn", 29.310579, 172.398316, 89.567, 0.053853, 9.51134, 2.4873, 113.752, 165.6, -8.88);
            $this->planetRecords[] = new PlanetData("Uranus", 84.039492, 356.135400, 172.884833, 0.046321, 19.21814, 0.773059, 73.926961, 65.8, -7.19);
            $this->planetRecords[] = new PlanetData("Neptune", 165.845392, 326.895127, 23.07, 0.010483, 30.1985, 1.7673, 131.879, 62.2, -6.87);
        }

        public function GetPlanetRecord($name)
        {
            foreach ($this->planetRecords as $planetRecord) {
                if ($planetRecord->name == $name) {
                    return $planetRecord;
                }
            }

            return new PlanetData("NotFound", -99, -99, -99, -99, -99, -99, -99, -99, -99);
        }
    }
}

namespace PA\Sun {

    use PA\Macros as PA_Macros;

    use PA\Types\AngleMeasure;
    use PA\Types\RiseSetStatus;
    use PA\Types\TwilightStatus;

    use function PA\Macros\greenwich_sidereal_time_to_universal_time;
    use function PA\Macros\decimal_degrees_to_degree_hours;
    use function PA\Macros\decimal_hours_hour;
    use function PA\Macros\decimal_hours_minute;
    use function PA\Macros\decimal_hours_second;
    use function PA\Macros\e_twilight;
    use function PA\Macros\ec_dec;
    use function PA\Macros\ec_ra;
    use function PA\Macros\sun_long;
    use function PA\Macros\twilight_am_lct;
    use function PA\Macros\twilight_pm_lct;
    use function PA\Macros\angle;

    /**
     * Calculate approximate position of the sun for a local date and time.
     */
    function approximate_position_of_sun($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection)
    {
        $daylightSaving = ($isDaylightSaving == true) ? 1 : 0;

        $greenwichDateDay = PA_Macros\local_civil_time_greenwich_day($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $greenwichDateMonth = PA_Macros\local_civil_time_greenwich_month($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $greenwichDateYear = PA_Macros\local_civil_time_greenwich_year($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $utHours = PA_Macros\local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $utDays = $utHours / 24;
        $jdDays = PA_Macros\civil_date_to_julian_date($greenwichDateDay, $greenwichDateMonth, $greenwichDateYear) + $utDays;
        $dDays = $jdDays - PA_Macros\civil_date_to_julian_date(0, 1, 2010);
        $nDeg = 360 * $dDays / 365.242191;
        $mDeg1 = $nDeg + PA_Macros\sun_e_long(0, 1, 2010) - PA_Macros\sun_peri(0, 1, 2010);
        $mDeg2 = $mDeg1 - 360 * floor($mDeg1 / 360);
        $eCDeg = 360 * PA_Macros\sun_ecc(0, 1, 2010) * sin(deg2rad($mDeg2)) / pi();
        $lSDeg1 = $nDeg + $eCDeg + PA_Macros\sun_e_long(0, 1, 2010);
        $lSDeg2 = $lSDeg1 - 360 * floor($lSDeg1 / 360);
        $raDeg = PA_Macros\ec_ra($lSDeg2, 0, 0, 0, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear);
        $raHours = PA_Macros\decimal_degrees_to_degree_hours($raDeg);
        $decDeg = PA_Macros\ec_dec($lSDeg2, 0, 0, 0, 0, 0, $greenwichDateDay, $greenwichDateMonth, $greenwichDateYear);

        $sunRAHour = PA_Macros\decimal_hours_hour($raHours);
        $sunRAMin = PA_Macros\decimal_hours_minute($raHours);
        $sunRASec = PA_Macros\decimal_hours_second($raHours);
        $sunDecDeg = PA_Macros\decimal_degrees_degrees($decDeg);
        $sunDecMin = PA_Macros\decimal_degrees_minutes($decDeg);
        $sunDecSec = PA_Macros\decimal_degrees_seconds($decDeg);

        return array($sunRAHour, $sunRAMin, $sunRASec, $sunDecDeg, $sunDecMin, $sunDecSec);
    }

    /**
     * Calculate precise position of the sun for a local date and time.
     */
    function precise_position_of_sun($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection)
    {
        $daylightSaving = ($isDaylightSaving == true) ? 1 : 0;

        $gDay = PA_Macros\local_civil_time_greenwich_day($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $gMonth = PA_Macros\local_civil_time_greenwich_month($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $gYear = PA_Macros\local_civil_time_greenwich_year($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $sunEclipticLongitudeDeg = PA_Macros\sun_long($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $raDeg = PA_Macros\ec_ra($sunEclipticLongitudeDeg, 0, 0, 0, 0, 0, $gDay, $gMonth, $gYear);
        $raHours = PA_Macros\decimal_degrees_to_degree_hours($raDeg);
        $decDeg = PA_Macros\ec_dec($sunEclipticLongitudeDeg, 0, 0, 0, 0, 0, $gDay, $gMonth, $gYear);

        $sunRAHour = PA_Macros\decimal_hours_hour($raHours);
        $sunRAMin = PA_Macros\decimal_hours_minute($raHours);
        $sunRASec = PA_Macros\decimal_hours_second($raHours);
        $sunDecDeg = PA_Macros\decimal_degrees_degrees($decDeg);
        $sunDecMin = PA_Macros\decimal_degrees_minutes($decDeg);
        $sunDecSec = PA_Macros\decimal_degrees_seconds($decDeg);

        return array($sunRAHour, $sunRAMin, $sunRASec, $sunDecDeg, $sunDecMin, $sunDecSec);
    }

    /**
     * Calculate distance to the Sun (in km), and angular size.
     */
    function sun_distance_and_angular_size($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection)
    {
        $daylightSaving = ($isDaylightSaving) ? 1 : 0;

        $gDay = PA_Macros\local_civil_time_greenwich_day($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $gMonth = PA_Macros\local_civil_time_greenwich_month($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $gYear = PA_Macros\local_civil_time_greenwich_year($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $trueAnomalyDeg = PA_Macros\sun_true_anomaly($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $trueAnomalyRad = deg2rad($trueAnomalyDeg);
        $eccentricity = PA_Macros\sun_ecc($gDay, $gMonth, $gYear);
        $f = (1 + $eccentricity * cos($trueAnomalyRad)) / (1 - $eccentricity * $eccentricity);
        $rKm = 149598500 / $f;
        $thetaDeg = $f * 0.533128;

        $sunDistKm = round($rKm, 0);
        $sunAngSizeDeg = PA_Macros\decimal_degrees_degrees($thetaDeg);
        $sunAngSizeMin = PA_Macros\decimal_degrees_minutes($thetaDeg);
        $sunAngSizeSec = PA_Macros\decimal_degrees_seconds($thetaDeg);

        return array($sunDistKm, $sunAngSizeDeg, $sunAngSizeMin, $sunAngSizeSec);
    }

    /**
     * Calculate local sunrise and sunset.
     */
    function sunrise_and_sunset($localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg)
    {
        $daylightSaving = ($isDaylightSaving) ? 1 : 0;

        $localSunriseHours = PA_Macros\sunrise_lct($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg);
        $localSunsetHours = PA_Macros\sunset_lct($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg);

        $sunRiseSetStatus = PA_Macros\e_sun_rs($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg);

        $adjustedSunriseHours = $localSunriseHours + 0.008333;
        $adjustedSunsetHours = $localSunsetHours + 0.008333;

        $azimuthOfSunriseDeg1 = PA_Macros\sunrise_az($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg);
        $azimuthOfSunsetDeg1 = PA_Macros\sunset_az($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg);

        $localSunriseHour = ($sunRiseSetStatus == RiseSetStatus::OK) ? PA_Macros\decimal_hours_hour($adjustedSunriseHours) : 0;
        $localSunriseMinute = ($sunRiseSetStatus == RiseSetStatus::OK) ? PA_Macros\decimal_hours_minute($adjustedSunriseHours) : 0;

        $localSunsetHour = ($sunRiseSetStatus == RiseSetStatus::OK) ? PA_Macros\decimal_hours_hour($adjustedSunsetHours) : 0;
        $localSunsetMinute = ($sunRiseSetStatus == RiseSetStatus::OK) ? PA_Macros\decimal_hours_minute($adjustedSunsetHours) : 0;

        $azimuthOfSunriseDeg = ($sunRiseSetStatus == RiseSetStatus::OK) ? round($azimuthOfSunriseDeg1, 2) : 0;
        $azimuthOfSunsetDeg = ($sunRiseSetStatus == RiseSetStatus::OK) ? round($azimuthOfSunsetDeg1, 2) : 0;

        $status = $sunRiseSetStatus;

        return array($localSunriseHour, $localSunriseMinute, $localSunsetHour, $localSunsetMinute, $azimuthOfSunriseDeg, $azimuthOfSunsetDeg, $status);
    }

    /**
     * Calculate times of morning and evening twilight.
     */
    function morning_and_evening_twilight($localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $twilightType)
    {
        $daylightSaving = $isDaylightSaving ? 1 : 0;

        $startOfAMTwilightHours = twilight_am_lct($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $twilightType);

        $endOfPMTwilightHours = twilight_pm_lct($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $twilightType);

        $twilightStatus = e_twilight($localDay, $localMonth, $localYear, $daylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $twilightType);

        $adjustedAMStartTime = $startOfAMTwilightHours + 0.008333;
        $adjustedPMStartTime = $endOfPMTwilightHours + 0.008333;

        $amTwilightBeginsHour = $twilightStatus == TwilightStatus::OK ? decimal_hours_hour($adjustedAMStartTime) : -99;
        $amTwilightBeginsMin = $twilightStatus == TwilightStatus::OK ? decimal_hours_minute($adjustedAMStartTime) : -99;

        $pmTwilightEndsHour =  $twilightStatus == TwilightStatus::OK ? decimal_hours_hour($adjustedPMStartTime) : -99;
        $pmTwilightEndsMin = $twilightStatus == TwilightStatus::OK ? decimal_hours_minute($adjustedPMStartTime) : -99;

        $status = $twilightStatus;

        return array($amTwilightBeginsHour, $amTwilightBeginsMin, $pmTwilightEndsHour, $pmTwilightEndsMin, $status);
    }

    /**
     * Calculate the equation of time. (The difference between the real Sun time and the mean Sun time.)
     */
    function equation_of_time($gwdateDay, $gwdateMonth, $gwdateYear)
    {
        $sunLongitudeDeg = sun_long(12, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $sunRAHours = decimal_degrees_to_degree_hours(ec_ra($sunLongitudeDeg, 0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear));
        $equivalentUTHours = greenwich_sidereal_time_to_universal_time($sunRAHours, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $equationOfTimeHours = (float)$equivalentUTHours - 12;

        $equationOfTimeMin = decimal_hours_minute($equationOfTimeHours);
        $equationOfTimeSec = decimal_hours_second($equationOfTimeHours);

        return array($equationOfTimeMin, $equationOfTimeSec);
    }

    /**
     * Calculate solar elongation for a celestial body.
     */
    function solar_elongation($raHour, $raMin, $raSec, $decDeg, $decMin, $decSec, $gwdateDay, $gwdateMonth, $gwdateYear)
    {
        $sunLongitudeDeg = sun_long(0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $sunRAHours = decimal_degrees_to_degree_hours(ec_ra($sunLongitudeDeg, 0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear));
        $sunDecDeg = ec_dec($sunLongitudeDeg, 0, 0, 0, 0, 0, $gwdateDay, $gwdateMonth, $gwdateYear);
        $solarElongationDeg =  angle($sunRAHours, 0, 0, $sunDecDeg, 0, 0, $raHour, $raMin, $raSec, $decDeg, $decMin, $decSec, AngleMeasure::Hours);

        return round($solarElongationDeg, 2);
    }
}

namespace PA\Macros {

    use PA\Data\Planets\PlanetDataPrecise;
    use PA\Types as PA_Types;
    use PA\Types\AngleMeasure;
    use PA\Types\CoordinateType;
    use PA\Types\EclipseOccurrence;
    use PA\Types\RiseSetStatus;
    use PA\Types\TwilightStatus;
    use PA\Types\WarningFlag;

    /**
     * Convert a Greenwich Date/Civil Date (day,month,year) to Julian Date
     *
     * Original macro name: CDJD
     */
    function civil_date_to_julian_date($day, $month, $year)
    {
        $fDay = (float) $day;
        $fMonth = (float) $month;
        $fYear = (float) $year;

        $y = ($fMonth < 3) ? $fYear - 1 : $fYear;
        $m = ($fMonth < 3) ? $fMonth + 12 : $fMonth;

        $b = 0; // Initialize $b

        if ($fYear > 1582) {
            $a = floor($y / 100);
            $b = 2 - $a + floor($a / 4);
        } else {
            if ($fYear == 1582 && $fMonth > 10) {
                $a = floor($y / 100);
                $b = 2 - $a + floor($a / 4);
            } else {
                if ($fYear == 1582 && $fMonth == 10 && $day >= 15) {
                    $a = floor($y / 100);
                    $b = 2 - $a + floor($a / 4);
                }
            }
        }

        $c = ($y < 0) ? floor((365.25 * $y) - 0.75) : floor(365.25 * $y);
        $d = floor(30.6001 * ($m + 1.0));

        return $b + $c + $d + $fDay + 1720994.5;
    }

    /**
     * Return Degrees part of Decimal Degrees
     * 
     * Original macro name: DDDeg
     */
    function decimal_degrees_degrees($decimalDegrees)
    {
        $a = abs($decimalDegrees);
        $b = $a * 3600;
        $c = round($b - 60 * floor($b / 60), 2);
        $e = ($c == 60) ? 60 : $b;

        return ($decimalDegrees < 0)
            ? -floor($e / 3600)
            : floor($e / 3600);
    }

    /**
     * Return Minutes part of Decimal Degrees
     * 
     * Original macro name: DDMin
     */
    function decimal_degrees_minutes(float $decimalDegrees)
    {
        $a = abs($decimalDegrees);
        $b = $a * 3600;
        $c = round($b - 60 * floor($b / 60), 2);
        $e = ($c == 60) ? $b + 60 : $b;

        return floor($e / 60) % 60;
    }

    /**
     * Return Seconds part of Decimal Degrees
     * 
     * Original macro name: DDSec
     */
    function decimal_degrees_seconds($decimalDegrees)
    {
        $a = abs($decimalDegrees);
        $b = $a * 3600;
        $c = round($b - 60 * floor($b / 60), 2);
        $d = ($c == 60) ? 0 : $c;

        return $d;
    }

    /**
     * Return the hour part of a Decimal Hours
     * 
     * Original macro name: DHHour
     */
    function decimal_hours_hour($decimalHours)
    {
        $a = abs($decimalHours);
        $b = $a * 3600;
        $c = round(floor($b - 60 * ($b / 60)), 2);
        $e = ($c == 60) ? $b + 60 : $b;

        return ($decimalHours < 0) ? (int)- (floor(($e / 3600))) : (int)floor($e / 3600);
    }

    /**
     * Return the minutes part of a Decimal Hours
     *
     * Original macro name: DHMin
     */
    function decimal_hours_minute($decimalHours)
    {
        $a = abs($decimalHours);
        $b = $a * 3600;
        $c = round($b - 60 * floor($b / 60), 2);
        $e = ($c == 60) ? $b + 60 : $b;

        return (int)floor($e / 60) % 60;
    }

    /**
     * Return the seconds part of a Decimal Hours
     *
     * Original macro name: DHSec
     */
    function decimal_hours_second($decimalHours)
    {
        $a = abs($decimalHours);
        $b = $a * 3600;
        $c = round($b - 60 * floor($b / 60), 2);
        $d = ($c == 60) ? 0 : $c;

        return $d;
    }

    /**
     * Convert Degrees Minutes Seconds to Decimal Degrees
     * 
     * Original macro name: DMSDD
     */
    function degrees_minutes_seconds_to_decimal_degrees($degrees, $minutes, $seconds)
    {
        // Calculate the decimal portions of minutes and seconds
        $a = abs($seconds) / 60;
        $b = (abs($minutes) + $a) / 60;
        $c = abs($degrees) + $b;

        // Apply negative sign if any input was negative
        return ($degrees < 0 || $minutes < 0 || $seconds < 0) ? -$c : $c;
    }

    /**
     * Convert W to Degrees
     * 
     * Original macro name: Degrees
     */
    function w_to_degrees($w)
    {
        return $w * 57.29577951;
    }

    /**
     * Convert Equatorial Coordinates to Altitude (in decimal degrees)
     * 
     * Original macro name: EQAlt
     */
    function equatorial_coordinates_to_altitude($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude)
    {
        $a = hours_minutes_seconds_to_decimal_hours($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds);
        $b = $a * 15;
        $c = deg2rad($b);
        $d = degrees_minutes_seconds_to_decimal_degrees($declinationDegrees, $declinationMinutes, $declinationSeconds);
        $e = deg2rad($d);
        $f = deg2rad($geographicalLatitude);
        $g = sin($e) * sin($f) + cos($e) * cos($f) * cos($c);

        return degrees(asin($g));
    }

    /**
     * Convert Equatorial Coordinates to Azimuth (in decimal degrees)
     * 
     * Original macro name: EQAz
     */
    function equatorial_coordinates_to_azimuth($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude)
    {
        $a = hours_minutes_seconds_to_decimal_hours($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds);
        $b = $a * 15;
        $c = deg2rad($b);
        $d = degrees_minutes_seconds_to_decimal_degrees($declinationDegrees, $declinationMinutes, $declinationSeconds);
        $e = deg2rad($d);
        $f = deg2rad($geographicalLatitude);
        $g = sin($e) * sin($f) + cos($e) * cos($f) * cos($c);
        $h = -cos($e) * cos($f) * sin($c);
        $i = sin($e) - (sin($f) * $g);
        $j = degrees(atan2($h, $i));

        return $j - 360.0 * floor($j / 360);
    }

    /**
     * Convert Greenwich Sidereal Time to Local Sidereal Time
     * 
     * Original macro name: GSTLST
     */
    function greenwich_sidereal_time_to_local_sidereal_time($greenwichHours, $greenwichMinutes, $greenwichSeconds, $geographicalLongitude)
    {
        $a = hours_minutes_seconds_to_decimal_hours($greenwichHours, $greenwichMinutes, $greenwichSeconds);
        $b = $geographicalLongitude / 15;
        $c = $a + $b;

        return $c - (24 * floor($c / 24));
    }

    /**
     * Convert Horizon Coordinates to Declination (in decimal degrees)
     *
     * Original macro name: HORDec
     */
    function horizon_coordinates_to_declination($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude)
    {
        $a = degrees_minutes_seconds_to_decimal_degrees($azimuthDegrees, $azimuthMinutes, $azimuthSeconds);
        $b = degrees_minutes_seconds_to_decimal_degrees($altitudeDegrees, $altitudeMinutes, $altitudeSeconds);
        $c = deg2rad($a);
        $d = deg2rad($b);
        $e = deg2rad($geographicalLatitude);
        $f = sin($d) * sin($e) + cos($d) * cos($e) * cos($c);

        return degrees(asin($f));
    }

    /**
     * Convert Horizon Coordinates to Hour Angle (in decimal degrees)
     *
     * Original macro name: HORHa
     */
    function horizon_coordinates_to_hour_angle($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude)
    {
        $a = degrees_minutes_seconds_to_decimal_degrees($azimuthDegrees, $azimuthMinutes, $azimuthSeconds);
        $b = degrees_minutes_seconds_to_decimal_degrees($altitudeDegrees, $altitudeMinutes, $altitudeSeconds);
        $c = deg2rad($a);
        $d = deg2rad($b);
        $e = deg2rad($geographicalLatitude);
        $f = sin($d) * sin($e) + cos($d) * cos($e) * cos($c);
        $g = -cos($d) * cos($e) * sin($c);
        $h = sin($d) - sin($e) * $f;
        $i = decimal_degrees_to_degree_hours(degrees(atan2($g, $h)));

        return $i - 24 * floor($i / 24);
    }

    /**
     * Convert Decimal Degrees to Degree-Hours
     *
     * Original macro name: DDDH
     */
    function decimal_degrees_to_degree_hours($decimalDegrees)
    {
        return $decimalDegrees / 15;
    }


    /**
     * 
     * Convert W to Degrees
     *
     * Original macro name: Degrees
     */
    function degrees($w)
    {
        return $w * 57.29577951;
    }

    /**
     * Convert Hour Angle to Right Ascension
     * 
     * Original macro name: HARA
     */
    function hour_angle_to_right_ascension($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude)
    {
        $a = local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $b = local_civil_time_greenwich_day($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $c = local_civil_time_greenwich_month($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $d = local_civil_time_greenwich_year($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $e = universal_time_to_greenwich_sidereal_time($a, 0, 0, $b, $c, $d);
        $f = greenwich_sidereal_time_to_local_sidereal_time($e, 0, 0, $geographicalLongitude);

        $g = hours_minutes_seconds_to_decimal_hours($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds);
        $h = $f - $g;

        return ($h < 0) ? 24 + $h : $h;
    }

    /**
     * Convert a Civil Time (hours,minutes,seconds) to Decimal Hours
     * 
     * Original macro name: HMSDH
     */
    function hours_minutes_seconds_to_decimal_hours($hours,  $minutes,  $seconds)
    {
        (float) $fHours = $hours;
        (float) $fMinutes = $minutes;
        (float) $fSeconds = $seconds;

        $a = abs($fSeconds) / 60;
        $b = (abs($fMinutes) + $a) / 60;
        $c = abs($fHours) + $b;

        return ($fHours < 0 || $fMinutes < 0 || $fSeconds < 0) ? -$c : $c;
    }

    /**
     * Returns the day part of a Julian Date
     *
     * Original macro name: JDCDay
     */
    function julian_date_day($julianDate)
    {
        $i = floor($julianDate + 0.5);
        $f = $julianDate + 0.5 - $i;
        $a = floor(($i - 1867216.25) / 36524.25);
        $b = ($i > 2299160) ? $i + 1 + $a - floor($a / 4) : $i;
        $c = $b + 1524;
        $d = floor(($c - 122.1) / 365.25);
        $e = floor(365.25 * $d);
        $g = floor(($c - $e) / 30.6001);

        return $c - $e + $f - floor(30.6001 * $g);
    }

    /**
     * Returns the month part of a Julian Date
     *
     * Original macro name: JDCMonth
     */
    function julian_date_month($julianDate)
    {
        $i = floor($julianDate + 0.5);
        $a = floor(($i - 1867216.25) / 36524.25);
        $b = ($i > 2299160) ? $i + 1 + $a - floor($a / 4) : $i;
        $c = $b + 1524;
        $d = floor(($c - 122.1) / 365.25);
        $e = floor(365.25 * $d);
        $g = floor(($c - $e) / 30.6001);

        $returnValue = ($g < 13.5) ? $g - 1 : $g - 13;

        return  $returnValue;
    }

    /**
     * Returns the year part of a Julian Date
     *
     * Original macro name: JDCYear
     */
    function julian_date_year($julianDate)
    {
        $i = floor($julianDate + 0.5);
        $a = floor(($i - 1867216.25) / 36524.25);
        $b = ($i > 2299160) ? $i + 1.0 + $a - floor($a / 4.0) : $i;
        $c = $b + 1524;
        $d = floor(($c - 122.1) / 365.25);
        $e = floor(365.25 * $d);
        $g = floor(($c - $e) / 30.6001);
        $h = ($g < 13.5) ? $g - 1 : $g - 13;

        $returnValue = ($h > 2.5) ? $d - 4716 : $d - 4715;

        return  $returnValue;
    }

    /**
     * Determine Greenwich Day for Local Time
     * 
     * Original macro name: LctGDay
     */
    function local_civil_time_greenwich_day($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($lctHours, $lctMinutes, $lctSeconds);
        $b = $a - $daylightSaving - $zoneCorrection;
        $c = $localDay + ($b / 24);
        $d = civil_date_to_julian_date($c, $localMonth, $localYear);
        $e = julian_date_day($d);

        return floor($e);
    }

    /**
     * Determine Greenwich Month for Local Time
     * 
     * Original macro name: LctGMonth
     */
    function local_civil_time_greenwich_month($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($lctHours, $lctMinutes, $lctSeconds);
        $b = $a - $daylightSaving - $zoneCorrection;
        $c = $localDay + ($b / 24);
        $d = civil_date_to_julian_date($c, $localMonth, $localYear);

        return julian_date_month($d);
    }

    /**
     * Determine Greenwich Year for Local Time
     * 
     * Original macro name: LctGYear
     * 
     */
    function local_civil_time_greenwich_year($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($lctHours, $lctMinutes, $lctSeconds);
        $b = $a - $daylightSaving - $zoneCorrection;
        $c = $localDay + ($b / 24);
        $d = civil_date_to_julian_date($c, $localMonth, $localYear);

        return julian_date_year($d);
    }

    /**
     * Convert Local Civil Time to Universal Time
     * 
     * Original macro name: LctUT
     */
    function local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($lctHours, $lctMinutes, $lctSeconds);
        $b = $a - $daylightSaving - $zoneCorrection;
        $c = $localDay + ($b / 24);

        $d = civil_date_to_julian_date($c, $localMonth, $localYear);

        $e = julian_date_day($d);
        $e1 = floor($e);

        return 24 * ($e - $e1);
    }

    /**
     * Convert Universal Time to Local Civil Time
     * 
     * Original macro name: UTLct
     */
    function universal_time_to_local_civil_time_ma($uHours, $uMinutes, $uSeconds, $daylightSaving, $zoneCorrection, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($uHours, $uMinutes, $uSeconds);
        $b = $a + $zoneCorrection;
        $c = $b + $daylightSaving;
        $d = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear) + ($c / 24);
        $e = julian_date_day($d);
        $e1 = floor($e);

        return 24 * ($e - $e1);
    }

    /**
     * Get Local Civil Day for Universal Time
     * 
     * Original macro name: UTLcDay
     */
    function universal_time_local_civil_day($uHours, $uMinutes, $uSeconds, $daylightSaving, $zoneCorrection, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($uHours, $uMinutes, $uSeconds);
        $b = $a + $zoneCorrection;
        $c = $b + $daylightSaving;
        $d = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear) + ($c / 24.0);
        $e = julian_date_day($d);
        $e1 = floor($e);

        return $e1;
    }

    /**
     * Get Local Civil Month for Universal Time
     * 
     * Original macro name: UTLcMonth
     */
    function universal_time_local_civil_month($uHours, $uMinutes, $uSeconds, $daylightSaving, $zoneCorrection, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($uHours, $uMinutes, $uSeconds);
        $b = $a + $zoneCorrection;
        $c = $b + $daylightSaving;
        $d = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear) + ($c / 24.0);

        return julian_date_month($d);
    }

    /**
     * Get Local Civil Year for Universal Time
     * 
     * Original macro name: UTLcYear
     */
    function universal_time_local_civil_year($uHours, $uMinutes, $uSeconds, $daylightSaving, $zoneCorrection, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a = hours_minutes_seconds_to_decimal_hours($uHours, $uMinutes, $uSeconds);
        $b = $a + $zoneCorrection;
        $c = $b + $daylightSaving;
        $d = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear) + ($c / 24.0);

        return julian_date_year($d);
    }


    /**
     * Convert Right Ascension to Hour Angle
     * 
     * Original macro name: RAHA
     */
    function right_ascension_to_hour_angle($raHours, $raMinutes, $raSeconds, $lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude)
    {
        $a = local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $b = local_civil_time_greenwich_day($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $c = local_civil_time_greenwich_month($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $d = local_civil_time_greenwich_year($lctHours, $lctMinutes, $lctSeconds, $daylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear);
        $e = universal_time_to_greenwich_sidereal_time($a, 0, 0, $b, $c, $d);
        $f = greenwich_sidereal_time_to_local_sidereal_time($e, 0, 0, $geographicalLongitude);

        $g = hours_minutes_seconds_to_decimal_hours($raHours, $raMinutes, $raSeconds);
        $h = $f - $g;

        return ($h < 0) ? 24 + $h : $h;
    }

    /**
     * Convert Universal Time to Greenwich Sidereal Time
     * 
     * Original macro name: UTGST
     */
    function universal_time_to_greenwich_sidereal_time($uHours, $uMinutes, $uSeconds, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a =  civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear);
        $b = $a - 2451545;
        $c = $b / 36525;
        $d = 6.697374558 + (2400.051336 * $c) + (0.000025862 * $c * $c);
        $e = $d - floor($d / 24) * 24;

        $f = $uHours + $uMinutes / 60 + $uSeconds / 3600;
        $g = $f * 1.002737909;
        $h = $e + $g;

        return $h - floor($h / 24) * 24;
    }

    /**
     * Obliquity of the Ecliptic for a Greenwich Date
     *
     * Original macro name: Obliq
     */
    function obliq($greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear);
        $b = $a - 2415020;
        $c = ($b / 36525) - 1;
        $d = $c * (46.815 + $c * (0.0006 - ($c * 0.00181)));
        $e = $d / 3600;

        return 23.43929167 - $e + nutat_obl($greenwichDay, $greenwichMonth, $greenwichYear);
    }

    /**
     * Nutation amount to be added in ecliptic longitude, in degrees.
     *
     * Original macro name: NutatLong
     */
    function nutat_long($gd, $gm, $gy)
    {
        $dj = civil_date_to_julian_date($gd, $gm, $gy) - 2415020;
        $t = $dj / 36525;
        $t2 = $t * $t;

        $a = 100.0021358 * $t;
        $b = 360 * ($a - floor($a));

        $l1 = 279.6967 + 0.000303 * $t2 + $b;
        $l2 = 2 * deg2rad($l1);

        $a = 1336.855231 * $t;
        $b = 360 * ($a - floor($a));

        $d1 = 270.4342 - 0.001133 * $t2 + $b;
        $d2 = 2 * deg2rad($d1);

        $a = 99.99736056 * $t;
        $b = 360 * ($a - floor($a));

        $m1 = 358.4758 - 0.00015 * $t2 + $b;
        $m1 = deg2rad($m1);

        $a = 1325.552359 * $t;
        $b = 360 * ($a - floor($a));

        $m2 = 296.1046 + 0.009192 * $t2 + $b;
        $m2 = deg2rad($m2);

        $a = 5.372616667 * $t;
        $b = 360 * ($a - floor($a));

        $n1 = 259.1833 + 0.002078 * $t2 - $b;
        $n1 = deg2rad($n1);

        $n2 = 2.0 * $n1;

        $dp = (-17.2327 - 0.01737 * $t) * sin($n1);
        $dp = $dp + (-1.2729 - 0.00013 * $t) * sin($l2)  + 0.2088 * sin($n2);
        $dp = $dp - 0.2037 * sin($d2) + (0.1261 - 0.00031 * $t) * sin($m1);
        $dp = $dp + 0.0675 * sin($m2) - (0.0497 - 0.00012 * $t) * sin($l2 + $m1);
        $dp = $dp - 0.0342 * sin($d2 - $n1) - 0.0261 * sin($d2 + $m2);
        $dp = $dp + 0.0214 * sin($l2 - $m1) - 0.0149 * sin($l2 - $d2 + $m2);
        $dp = $dp + 0.0124 * sin($l2 - $n1) + 0.0114 * sin($d2 - $m2);

        return $dp / 3600;
    }

    /**
     * Nutation of Obliquity
     *
     * Original macro name: NutatObl
     */
    function nutat_obl($greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $dj = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear) - 2415020;
        $t = $dj / 36525;
        $t2 = $t * $t;

        $a = 100.0021358 * $t;
        $b = 360 * ($a - floor($a));

        $l1 = 279.6967 + 0.000303 * $t2 + $b;
        $l2 = 2 * deg2rad($l1);

        $a = 1336.855231 * $t;
        $b = 360 * ($a - floor($a));

        $d1 = 270.4342 - 0.001133 * $t2 + $b;
        $d2 = 2 * deg2rad($d1);

        $a = 99.99736056 * $t;
        $b = 360 * ($a - floor($a));

        $m1 = deg2rad(358.4758 - 0.00015 * $t2 + $b);

        $a = 1325.552359 * $t;
        $b = 360 * ($a - floor($a));

        $m2 = deg2rad(296.1046 + 0.009192 * $t2 + $b);

        $a = 5.372616667 * $t;
        $b = 360 * ($a - floor($a));

        $n1 = deg2rad(259.1833 + 0.002078 * $t2 - $b);

        $n2 = 2 * $n1;

        $ddo = (9.21 + 0.00091 * $t) * cos($n1);
        $ddo += (0.5522 - 0.00029 * $t) * cos($l2) - 0.0904 * cos($n2);
        $ddo += 0.0884 * cos($d2) + 0.0216 * cos($l2 + $m1);
        $ddo += 0.0183 * cos($d2 - $n1) + 0.0113 * cos($d2 + $m2);
        $ddo -= 0.0093 * cos($l2 - $m1) - 0.0066 * cos($l2 - $n1);

        return $ddo / 3600;
    }

    /**
     * Convert Degree-Hours to Decimal Degrees
     *
     * Original macro name: DHDD
     */
    function degree_hours_to_decimal_degrees($degreeHours)
    {
        return $degreeHours * 15;
    }

    /**
     * Convert Greenwich Sidereal Time to Universal Time
     *
     * Original macro name: GSTUT
     */
    function greenwich_sidereal_time_to_universal_time($greenwichSiderealHours, $greenwichSiderealMinutes, $greenwichSiderealSeconds, $greenwichDay, $greenwichMonth, $greenwichYear)
    {
        $a = civil_date_to_julian_date($greenwichDay, $greenwichMonth, $greenwichYear);
        $b = $a - 2451545;
        $c = $b / 36525;
        $d = 6.697374558 + (2400.051336 * $c) + (0.000025862 * $c * $c);
        $e = $d - (24 * floor($d / 24));
        $f = hours_minutes_seconds_to_decimal_hours($greenwichSiderealHours, $greenwichSiderealMinutes, $greenwichSiderealSeconds);
        $g = $f - $e;
        $h = $g - (24 * floor($g / 24));

        return $h * 0.9972695663;
    }

    /**
     * Convert Local Sidereal Time to Greenwich Sidereal Time
     *
     * Original macro name: LSTGST
     */
    function local_sidereal_time_to_greenwich_sidereal_time($localHours, $localMinutes, $localSeconds, $longitude)
    {
        $a = hours_minutes_seconds_to_decimal_hours($localHours, $localMinutes, $localSeconds);
        $b = $longitude / 15;
        $c = $a - $b;

        return $c - (24 * floor($c / 24));
    }

    /**
     * Status of conversion of Greenwich Sidereal Time to Universal Time.
     * 
     * Original macro name: eGSTUT
     */
    function eg_st_ut($gsh, $gsm, $gss, $gd, $gm, $gy)
    {
        $a = civil_date_to_julian_date($gd, $gm, $gy);
        $b = $a - 2451545;
        $c = $b / 36525;
        $d = 6.697374558 + (2400.051336 * $c) + (0.000025862 * $c * $c);
        $e = $d - (24 * floor($d / 24));
        $f = hours_minutes_seconds_to_decimal_hours($gsh, $gsm, $gss);
        $g = $f - $e;
        $h = $g - (24 * floor($g / 24));

        return (($h * 0.9972695663) < (4.0 / 60.0)) ? WarningFlag::Warning : WarningFlag::OK;
    }

    /**
     * Calculate Sun's ecliptic longitude
     *
     * Original macro name: SunLong
     */
    function sun_long($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly)
    {
        $aa = local_civil_time_greenwich_day($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $bb = local_civil_time_greenwich_month($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $cc = local_civil_time_greenwich_year($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $ut = local_civil_time_to_universal_time($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $dj = civil_date_to_julian_date($aa, $bb, $cc) - 2415020;
        $t = ($dj / 36525) + ($ut / 876600);
        $t2 = $t * $t;
        $a = 100.0021359 * $t;
        $b = 360.0 * ($a - floor($a));

        $l = 279.69668 + 0.0003025 * $t2 + $b;
        $a = 99.99736042 * $t;
        $b = 360 * ($a - floor($a));

        $m1 = 358.47583 - (0.00015 + 0.0000033 * $t) * $t2 + $b;
        $ec = 0.01675104 - 0.0000418 * $t - 0.000000126 * $t2;

        $am = deg2rad($m1);
        $at = true_anomaly($am, $ec);

        $a = 62.55209472 * $t;
        $b = 360 * ($a - floor($a));

        $a1 = deg2rad(153.23 + $b);
        $a = 125.1041894 * $t;
        $b = 360 * ($a - floor($a));

        $b1 = deg2rad(216.57 + $b);
        $a = 91.56766028 * $t;
        $b = 360.0 * ($a - floor($a));

        $c1 = deg2rad(312.69 + $b);
        $a = 1236.853095 * $t;
        $b = 360.0 * ($a - floor($a));

        $d1 = deg2rad(350.74 - 0.00144 * $t2 + $b);
        $e1 = deg2rad(231.19 + 20.2 * $t);
        $a = 183.1353208 * $t;
        $b = 360.0 * ($a - floor($a));
        $h1 = deg2rad(353.4 + $b);

        $d2 = 0.00134 * cos($a1) + 0.00154 * cos($b1) + 0.002 * cos($c1);
        $d2 = $d2 + 0.00179 * sin($d1) + 0.00178 * sin($e1);
        $d3 = 0.00000543 * sin($a1) + 0.00001575 * sin($b1);
        $d3 = $d3 + 0.00001627 * sin($c1) + 0.00003076 * cos($d1);

        $sr = $at + deg2rad($l - $m1 + $d2);
        $tp = 6.283185308;

        $sr = $sr - $tp * floor($sr / $tp);

        return degrees($sr);
    }

    /**
     * Calculate Sun's angular diameter in decimal degrees
     *
     * Original macro name: SunDia
     */
    function sun_dia($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly)
    {
        $a = sun_dist($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);

        return 0.533128 / $a;
    }

    /**
     * Calculate Sun's distance from the Earth in astronomical units
     *
     * Original macro name: SunDist
     */
    function sun_dist($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly)
    {
        $aa = local_civil_time_greenwich_day($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $bb = local_civil_time_greenwich_month($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $cc = local_civil_time_greenwich_year($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $ut = local_civil_time_to_universal_time($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $dj = civil_date_to_julian_date($aa, $bb, $cc) - 2415020;

        $t = ($dj / 36525) + ($ut / 876600);
        $t2 = $t * $t;

        $a = 100.0021359 * $t;
        $b = 360 * ($a - floor($a));
        $a = 99.99736042 * $t;
        $b = 360 * ($a - floor($a));
        $m1 = 358.47583 - (0.00015 + 0.0000033 * $t) * $t2 + $b;
        $ec = 0.01675104 - 0.0000418 * $t - 0.000000126 * $t2;

        $am = deg2rad($m1);
        $ae = eccentric_anomaly($am, $ec);

        $a = 62.55209472 * $t;
        $b = 360 * ($a - floor($a));
        $a1 = deg2rad(153.23 + $b);
        $a = 125.1041894 * $t;
        $b = 360 * ($a - floor($a));
        $b1 = deg2rad(216.57 + $b);
        $a = 91.56766028 * $t;
        $b = 360 * ($a - floor($a));
        $c1 = deg2rad(312.69 + $b);
        $a = 1236.853095 * $t;
        $b = 360 * ($a - floor($a));
        $d1 = deg2rad(350.74 - 0.00144 * $t2 + $b);
        $e1 = deg2rad(231.19 + 20.2 * $t);
        $a = 183.1353208 * $t;
        $b = 360 * ($a - floor($a));
        $h1 = deg2rad(353.4 + $b);

        $d3 = (0.00000543 * sin($a1) + 0.00001575 * sin($b1)) + (0.00001627 * sin($c1) + 0.00003076 * cos($d1)) + (0.00000927 * sin($h1));

        return 1.0000002 * (1 - $ec * cos($ae)) + $d3;
    }

    /**
     * Solve Kepler's equation, and return value of the true anomaly in radians
     *
     * Original macro name: TrueAnomaly
     */
    function true_anomaly($am, $ec)
    {
        $tp = 6.283185308;
        $m = $am - $tp * floor($am / $tp);
        $ae = $m;

        while (1 == 1) {
            $d = $ae - ($ec * sin($ae)) - $m;
            if (abs($d) < 0.000001) {
                break;
            }
            $d = $d / (1.0 - ($ec * cos($ae)));
            $ae = $ae - $d;
        }
        $a = sqrt((1 + $ec) / (1 - $ec)) * tan($ae / 2);
        $at = 2.0 * atan($a);

        return $at;
    }

    /**
     * Solve Kepler's equation, and return value of the eccentric anomaly in radians
     *
     * Original macro name: EccentricAnomaly
     */
    function eccentric_anomaly($am, $ec)
    {
        $tp = 6.283185308;
        $m = $am - $tp * floor($am / $tp);
        $ae = $m;

        while (1 == 1) {
            $d = $ae - ($ec * sin($ae)) - $m;

            if (abs($d) < 0.000001) {
                break;
            }

            $d = $d / (1 - ($ec * cos($ae)));
            $ae = $ae - $d;
        }

        return $ae;
    }

    /**
     * Calculate effects of refraction
     *
     * Original macro name: Refract
     */
    function refract($y2, $sw, $pr, $tr)
    {
        $y = deg2rad($y2);

        $d = ($sw == CoordinateType::True) ? -1.0 : 1.0;

        if ($d == -1) {
            $y3 = $y;
            $y1 = $y;
            $r1 = 0.0;

            while (1 == 1) {
                $yNew = $y1 + $r1;
                $rfNew = refract_l3035($pr, $tr, $yNew, $d);

                if ($y < -0.087)
                    return 0;

                $r2 = $rfNew;

                if (($r2 == 0) || (abs($r2 - $r1) < 0.000001)) {
                    $qNew = $y3;

                    return degrees($qNew + $rfNew);
                }

                $r1 = $r2;
            }
        }

        $rf = refract_l3035($pr, $tr, $y, $d);

        if ($y < -0.087)
            return 0;

        $q = $y;

        return degrees($q + $rf);
    }

    /**
     * Helper function for refract
     */
    function refract_l3035($pr, $tr, $y, $d)
    {
        if ($y < 0.2617994) {
            if ($y < -0.087)
                return 0;

            $yd = degrees($y);
            $a = ((0.00002 * $yd + 0.0196) * $yd + 0.1594) * $pr;
            $b = (273.0 + $tr) * ((0.0845 * $yd + 0.505) * $yd + 1);

            return deg2rad(- ($a / $b) * $d);
        }

        return -$d * 0.00007888888 * $pr / ((273.0 + $tr) * tan($y));
    }

    /**
     * Calculate corrected hour angle in decimal hours
     *
     * Original macro name: ParallaxHA
     */
    function parallax_ha($hh, $hm, $hs, $dd, $dm, $ds, $sw, $gp, $ht, $hp)
    {
        $a = deg2rad($gp);
        $c1 = cos($a);
        $s1 = sin($a);

        $u = atan(0.996647 * $s1 / $c1);
        $c2 = cos($u);
        $s2 = sin($u);
        $b = $ht / 6378160;

        $rs = (0.996647 * $s2) + ($b * $s1);

        $rc = $c2 + ($b * $c1);
        $tp = 6.283185308;

        $rp = 1.0 / sin(deg2rad($hp));

        $x = deg2rad(degree_hours_to_decimal_degrees(hours_minutes_seconds_to_decimal_hours($hh, $hm, $hs)));
        $x1 = $x;
        $y = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $y1 = $y;

        $d = ($sw == CoordinateType::True) ? 1.0 : -1.0;

        if ($d == 1) {
            list($p, $q) = parallax_ha_l2870($x, $y, $rc, $rp, $rs, $tp);

            return decimal_degrees_to_degree_hours(degrees($p));
        }

        $p1 = 0.0;
        $q1 = 0.0;
        $xLoop = $x;
        $yLoop = $y;

        while (1 == 1) {
            list($p, $q) = parallax_ha_l2870($xLoop, $yLoop, $rc, $rp, $rs, $tp);
            $p2 = $p - $xLoop;
            $q2 = $q - $yLoop;

            $aa = abs($p2 - $p1);
            $bb = abs($q2 - $q1);

            if (($aa < 0.000001) && ($bb < 0.000001)) {
                $p = $x1 - $p2;

                return decimal_degrees_to_degree_hours(degrees($p));
            }

            $xLoop = $x1 - $p2;
            $yLoop = $y1 - $q2;
            $p1 = $p2;
            $q1 = $q2;
        }
    }

    /**
     * Helper function for parallax_ha
     */
    function parallax_ha_l2870($x, $y, $rc, $rp, $rs, $tp)
    {
        $cx = cos($x);
        $sy = sin($y);
        $cy = cos($y);

        $aa = ($rc * sin($x)) / (($rp * $cy) - ($rc * $cx));

        $dx = atan($aa);
        $p = $x + $dx;
        $cp = cos($p);

        $p = $p - $tp * floor($p / $tp);
        $q = atan($cp * ($rp * $sy - $rs) / ($rp * $cy * $cx - $rc));

        return array($p, $q);
    }

    /**
     * Calculate corrected declination in decimal degrees
     *
     * Original macro name: ParallaxDec
     */
    function parallax_dec($hh, $hm, $hs, $dd, $dm, $ds, $sw, $gp, $ht, $hp)
    {
        $a = deg2rad($gp);
        $c1 = cos($a);
        $s1 = sin($a);

        $u = atan(0.996647 * $s1 / $c1);

        $c2 = cos($u);
        $s2 = sin($u);
        $b = $ht / 6378160;
        $rs = (0.996647 * $s2) + ($b * $s1);

        $rc = $c2 + ($b * $c1);
        $tp = 6.283185308;

        $rp = 1.0 / sin(deg2rad($hp));

        $x = deg2rad(degree_hours_to_decimal_degrees(hours_minutes_seconds_to_decimal_hours($hh, $hm, $hs)));
        $x1 = $x;

        $y = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $y1 = $y;

        $d = ($sw == CoordinateType::True) ? 1.0 : -1.0;

        if ($d == 1) {
            list($p, $q) = parallax_dec_l2870($x, $y, $rc, $rp, $rs, $tp);

            return degrees($q);
        }

        $p1 = 0.0;
        $q1 = 0.0;

        $xLoop = $x;
        $yLoop = $y;

        while (1 == 1) {
            list($p, $q) = parallax_dec_l2870($xLoop, $yLoop, $rc, $rp, $rs, $tp);
            $p2 = $p - $xLoop;
            $q2 = $q - $yLoop;
            $aa = abs($p2 - $p1);

            if (($aa < 0.000001) && ($b < 0.000001)) {
                $q = $y1 - $q2;

                return degrees($q);
            }
            $xLoop = $x1 - $p2;
            $yLoop = $y1 - $q2;
            $p1 = $p2;
            $q1 = $q2;
        }
    }

    /**
     * Helper function for parallax_dec
     */
    function parallax_dec_l2870($x, $y, $rc, $rp, $rs, $tp)
    {
        $cx = cos($x);
        $sy = sin($y);
        $cy = cos($y);

        $aa = ($rc * sin($x)) / (($rp * $cy) - ($rc * $cx));
        $dx = atan($aa);
        $p = $x + $dx;
        $cp = cos($p);

        $p = $p - $tp * floor($p / $tp);
        $q = atan($cp * ($rp * $sy - $rs) / ($rp * $cy * $cx - $rc));

        return array($p, $q);
    }

    /**
     * Calculate geocentric ecliptic longitude for the Moon
     *
     * Original macro name: MoonLong
     */
    function moon_long($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $ut = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $t = ((civil_date_to_julian_date($gd, $gm, $gy) - 2415020) / 36525) + ($ut / 876600);
        $t2 = $t * $t;

        $m1 = 27.32158213;
        $m2 = 365.2596407;
        $m3 = 27.55455094;
        $m4 = 29.53058868;
        $m5 = 27.21222039;
        $m6 = 6798.363307;
        $q = civil_date_to_julian_date($gd, $gm, $gy) - 2415020 + ($ut / 24);
        $m1 = $q / $m1;
        $m2 = $q / $m2;
        $m3 = $q / $m3;
        $m4 = $q / $m4;
        $m5 = $q / $m5;
        $m6 = $q / $m6;
        $m1 = 360 * ($m1 - floor($m1));
        $m2 = 360 * ($m2 - floor($m2));
        $m3 = 360 * ($m3 - floor($m3));
        $m4 = 360 * ($m4 - floor($m4));
        $m5 = 360 * ($m5 - floor($m5));
        $m6 = 360 * ($m6 - floor($m6));

        $ml = 270.434164 + $m1 - (0.001133 - 0.0000019 * $t) * $t2;
        $ms = 358.475833 + $m2 - (0.00015 + 0.0000033 * $t) * $t2;
        $md = 296.104608 + $m3 + (0.009192 + 0.0000144 * $t) * $t2;
        $me1 = 350.737486 + $m4 - (0.001436 - 0.0000019 * $t) * $t2;
        $mf = 11.250889 + $m5 - (0.003211 + 0.0000003 * $t) * $t2;
        $na = 259.183275 - $m6 + (0.002078 + 0.0000022 * $t) * $t2;
        $a = deg2rad(51.2 + 20.2 * $t);
        $s1 = sin($a);
        $s2 = sin(deg2rad($na));
        $b = 346.56 + (132.87 - 0.0091731 * $t) * $t;
        $s3 = 0.003964 * sin(deg2rad($b));
        $c = deg2rad($na + 275.05 - 2.3 * $t);
        $s4 = sin($c);
        $ml = $ml + 0.000233 * $s1 + $s3 + 0.001964 * $s2;
        $ms = $ms - 0.001778 * $s1;
        $md = $md + 0.000817 * $s1 + $s3 + 0.002541 * $s2;
        $mf = $mf + $s3 - 0.024691 * $s2 - 0.004328 * $s4;
        $me1 = $me1 + 0.002011 * $s1 + $s3 + 0.001964 * $s2;
        $e = 1.0 - (0.002495 + 0.00000752 * $t) * $t;
        $e2 = $e * $e;
        $ml = deg2rad($ml);
        $ms = deg2rad($ms);
        $me1 = deg2rad($me1);
        $mf = deg2rad($mf);
        $md = deg2rad($md);

        $l = 6.28875 * sin($md) + 1.274018 * sin(2.0 * $me1 - $md);
        $l = $l + 0.658309 * sin(2.0 * $me1) + 0.213616 * sin(2.0 * $md);
        $l = $l - $e * 0.185596 * sin($ms) - 0.114336 * sin(2.0 * $mf);
        $l = $l + 0.058793 * sin(2.0 * ($me1 - $md));
        $l = $l + 0.057212 * $e * sin(2.0 * $me1 - $ms - $md) + 0.05332 * sin(2.0 * $me1 + $md);
        $l = $l + 0.045874 * $e * sin(2.0 * $me1 - $ms) + 0.041024 * $e * sin($md - $ms);
        $l = $l - 0.034718 * sin($me1) - $e * 0.030465 * sin($ms + $md);
        $l = $l + 0.015326 * sin(2.0 * ($me1 - $mf)) - 0.012528 * sin(2.0 * $mf + $md);
        $l = $l - 0.01098 * sin(2.0 * $mf - $md) + 0.010674 * sin(4.0 * $me1 - $md);
        $l = $l + 0.010034 * sin(3.0 * $md) + 0.008548 * sin(4.0 * $me1 - 2.0 * $md);
        $l = $l - $e * 0.00791 * sin($ms - $md + 2.0 * $me1) - $e * 0.006783 * sin(2.0 * $me1 + $ms);
        $l = $l + 0.005162 * sin($md - $me1) + $e * 0.005 * sin($ms + $me1);
        $l = $l + 0.003862 * sin(4.0 * $me1) + $e * 0.004049 * sin($md - $ms + 2.0 * $me1);
        $l = $l + 0.003996 * sin(2.0 * ($md + $me1)) + 0.003665 * sin(2.0 * $me1 - 3.0 * $md);
        $l = $l + $e * 0.002695 * sin(2.0 * $md - $ms) + 0.002602 * sin($md - 2.0 * ($mf + $me1));
        $l = $l + $e * 0.002396 * sin(2.0 * ($me1 - $md) - $ms) - 0.002349 * sin($md + $me1);
        $l = $l + $e2 * 0.002249 * sin(2.0 * ($me1 - $ms)) - $e * 0.002125 * sin(2.0 * $md + $ms);
        $l = $l - $e2 * 0.002079 * sin(2.0 * $ms) + $e2 * 0.002059 * sin(2.0 * ($me1 - $ms) - $md);
        $l = $l - 0.001773 * sin($md + 2.0 * ($me1 - $mf)) - 0.001595 * sin(2.0 * ($mf + $me1));
        $l = $l + $e * 0.00122 * sin(4.0 * $me1 - $ms - $md) - 0.00111 * sin(2.0 * ($md + $mf));
        $l = $l + 0.000892 * sin($md - 3.0 * $me1) - $e * 0.000811 * sin($ms + $md + 2.0 * $me1);
        $l = $l + $e * 0.000761 * sin(4.0 * $me1 - $ms - 2.0 * $md);
        $l = $l + $e2 * 0.000704 * sin($md - 2.0 * ($ms + $me1));
        $l = $l + $e * 0.000693 * sin($ms - 2.0 * ($md - $me1));
        $l = $l + $e * 0.000598 * sin(2.0 * ($me1 - $mf) - $ms);
        $l = $l + 0.00055 * sin($md + 4.0 * $me1) + 0.000538 * sin(4.0 * $md);
        $l = $l + $e * 0.000521 * sin(4.0 * $me1 - $ms) + 0.000486 * sin(2.0 * $md - $me1);
        $l = $l + $e2 * 0.000717 * sin($md - 2.0 * $ms);
        $mm = unwind($ml + deg2rad($l));

        return degrees($mm);
    }

    /**
     * Calculate geocentric ecliptic latitude for the Moon
     *
     * Original macro name: MoonLat
     */
    function moon_lat($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $ut = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $t = ((civil_date_to_julian_date($gd, $gm, $gy) - 2415020) / 36525) + ($ut / 876600);
        $t2 = $t * $t;

        $m1 = 27.32158213;
        $m2 = 365.2596407;
        $m3 = 27.55455094;
        $m4 = 29.53058868;
        $m5 = 27.21222039;
        $m6 = 6798.363307;
        $q = civil_date_to_julian_date($gd, $gm, $gy) - 2415020 + ($ut / 24);
        $m1 = $q / $m1;
        $m2 = $q / $m2;
        $m3 = $q / $m3;
        $m4 = $q / $m4;
        $m5 = $q / $m5;
        $m6 = $q / $m6;
        $m1 = 360 * ($m1 - floor($m1));
        $m2 = 360 * ($m2 - floor($m2));
        $m3 = 360 * ($m3 - floor($m3));
        $m4 = 360 * ($m4 - floor($m4));
        $m5 = 360 * ($m5 - floor($m5));
        $m6 = 360 * ($m6 - floor($m6));

        $ml = 270.434164 + $m1 - (0.001133 - 0.0000019 * $t) * $t2;
        $ms = 358.475833 + $m2 - (0.00015 + 0.0000033 * $t) * $t2;
        $md = 296.104608 + $m3 + (0.009192 + 0.0000144 * $t) * $t2;
        $me1 = 350.737486 + $m4 - (0.001436 - 0.0000019 * $t) * $t2;
        $mf = 11.250889 + $m5 - (0.003211 + 0.0000003 * $t) * $t2;
        $na = 259.183275 - $m6 + (0.002078 + 0.0000022 * $t) * $t2;
        $a = deg2rad(51.2 + 20.2 * $t);
        $s1 = sin($a);
        $s2 = sin(deg2rad($na));
        $b = 346.56 + (132.87 - 0.0091731 * $t) * $t;
        $s3 = 0.003964 * sin(deg2rad($b));
        $c = deg2rad($na + 275.05 - 2.3 * $t);
        $s4 = sin($c);
        $ml = $ml + 0.000233 * $s1 + $s3 + 0.001964 * $s2;
        $ms = $ms - 0.001778 * $s1;
        $md = $md + 0.000817 * $s1 + $s3 + 0.002541 * $s2;
        $mf = $mf + $s3 - 0.024691 * $s2 - 0.004328 * $s4;
        $me1 = $me1 + 0.002011 * $s1 + $s3 + 0.001964 * $s2;
        $e = 1.0 - (0.002495 + 0.00000752 * $t) * $t;
        $e2 = $e * $e;
        $ms = deg2rad($ms);
        $na = deg2rad($na);
        $me1 = deg2rad($me1);
        $mf = deg2rad($mf);
        $md = deg2rad($md);

        $g = 5.128189 * sin($mf) + 0.280606 * sin($md + $mf);
        $g = $g + 0.277693 * sin($md - $mf) + 0.173238 * sin(2.0 * $me1 - $mf);
        $g = $g + 0.055413 * sin(2.0 * $me1 + $mf - $md) + 0.046272 * sin(2.0 * $me1 - $mf - $md);
        $g = $g + 0.032573 * sin(2.0 * $me1 + $mf) + 0.017198 * sin(2.0 * $md + $mf);
        $g = $g + 0.009267 * sin(2.0 * $me1 + $md - $mf) + 0.008823 * sin(2.0 * $md - $mf);
        $g = $g + $e * 0.008247 * sin(2.0 * $me1 - $ms - $mf) + 0.004323 * sin(2.0 * ($me1 - $md) - $mf);
        $g = $g + 0.0042 * sin(2.0 * $me1 + $mf + $md) + $e * 0.003372 * sin($mf - $ms - 2.0 * $me1);
        $g = $g + $e * 0.002472 * sin(2.0 * $me1 + $mf - $ms - $md);
        $g = $g + $e * 0.002222 * sin(2.0 * $me1 + $mf - $ms);
        $g = $g + $e * 0.002072 * sin(2.0 * $me1 - $mf - $ms - $md);
        $g = $g + $e * 0.001877 * sin($mf - $ms + $md) + 0.001828 * sin(4.0 * $me1 - $mf - $md);
        $g = $g - $e * 0.001803 * sin($mf + $ms) - 0.00175 * sin(3.0 * $mf);
        $g = $g + $e * 0.00157 * sin($md - $ms - $mf) - 0.001487 * sin($mf + $me1);
        $g = $g - $e * 0.001481 * sin($mf + $ms + $md) + $e * 0.001417 * sin($mf - $ms - $md);
        $g = $g + $e * 0.00135 * sin($mf - $ms) + 0.00133 * sin($mf - $me1);
        $g = $g + 0.001106 * sin($mf + 3.0 * $md) + 0.00102 * sin(4.0 * $me1 - $mf);
        $g = $g + 0.000833 * sin($mf + 4.0 * $me1 - $md) + 0.000781 * sin($md - 3.0 * $mf);
        $g = $g + 0.00067 * sin($mf + 4.0 * $me1 - 2.0 * $md) + 0.000606 * sin(2.0 * $me1 - 3.0 * $mf);
        $g = $g + 0.000597 * sin(2.0 * ($me1 + $md) - $mf);
        $g = $g + $e * 0.000492 * sin(2.0 * $me1 + $md - $ms - $mf) + 0.00045 * sin(2.0 * ($md - $me1) - $mf);
        $g = $g + 0.000439 * sin(3.0 * $md - $mf) + 0.000423 * sin($mf + 2.0 * ($me1 + $md));
        $g = $g + 0.000422 * sin(2.0 * $me1 - $mf - 3.0 * $md) - $e * 0.000367 * sin($ms + $mf + 2.0 * $me1 - $md);
        $g = $g - $e * 0.000353 * sin($ms + $mf + 2.0 * $me1) + 0.000331 * sin($mf + 4.0 * $me1);
        $g = $g + $e * 0.000317 * sin(2.0 * $me1 + $mf - $ms + $md);
        $g = $g + $e2 * 0.000306 * sin(2.0 * ($me1 - $ms) - $mf) - 0.000283 * sin($md + 3.0 * $mf);
        $w1 = 0.0004664 * cos($na);
        $w2 = 0.0000754 * cos($c);
        $bm = deg2rad($g) * (1.0 - $w1 - $w2);

        return degrees($bm);
    }

    /**
     * Calculate horizontal parallax for the Moon
     *
     * Original macro name: MoonHP
     */
    function moon_hp($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $ut = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $t = ((civil_date_to_julian_date($gd, $gm, $gy) - 2415020) / 36525) + ($ut / 876600);
        $t2 = $t * $t;

        $m1 = 27.32158213;
        $m2 = 365.2596407;
        $m3 = 27.55455094;
        $m4 = 29.53058868;
        $m5 = 27.21222039;
        $m6 = 6798.363307;
        $q = civil_date_to_julian_date($gd, $gm, $gy) - 2415020 + ($ut / 24);
        $m1 = $q / $m1;
        $m2 = $q / $m2;
        $m3 = $q / $m3;
        $m4 = $q / $m4;
        $m5 = $q / $m5;
        $m6 = $q / $m6;
        $m1 = 360 * ($m1 - floor($m1));
        $m2 = 360 * ($m2 - floor($m2));
        $m3 = 360 * ($m3 - floor($m3));
        $m4 = 360 * ($m4 - floor($m4));
        $m5 = 360 * ($m5 - floor($m5));
        $m6 = 360 * ($m6 - floor($m6));

        $ml = 270.434164 + $m1 - (0.001133 - 0.0000019 * $t) * $t2;
        $ms = 358.475833 + $m2 - (0.00015 + 0.0000033 * $t) * $t2;
        $md = 296.104608 + $m3 + (0.009192 + 0.0000144 * $t) * $t2;
        $me1 = 350.737486 + $m4 - (0.001436 - 0.0000019 * $t) * $t2;
        $mf = 11.250889 + $m5 - (0.003211 + 0.0000003 * $t) * $t2;
        $na = 259.183275 - $m6 + (0.002078 + 0.0000022 * $t) * $t2;
        $a = deg2rad(51.2 + 20.2 * $t);
        $s1 = sin($a);
        $s2 = sin(deg2rad($na));
        $b = 346.56 + (132.87 - 0.0091731 * $t) * $t;
        $s3 = 0.003964 * sin(deg2rad($b));
        $c = deg2rad($na + 275.05 - 2.3 * $t);
        $s4 = sin($c);
        $ml = $ml + 0.000233 * $s1 + $s3 + 0.001964 * $s2;
        $ms = $ms - 0.001778 * $s1;
        $md = $md + 0.000817 * $s1 + $s3 + 0.002541 * $s2;
        $mf = $mf + $s3 - 0.024691 * $s2 - 0.004328 * $s4;
        $me1 = $me1 + 0.002011 * $s1 + $s3 + 0.001964 * $s2;
        $e = 1.0 - (0.002495 + 0.00000752 * $t) * $t;
        $e2 = $e * $e;
        $ms = deg2rad($ms);
        $me1 = deg2rad($me1);
        $mf = deg2rad($mf);
        $md = deg2rad($md);

        $pm = 0.950724 + 0.051818 * cos($md) + 0.009531 * cos(2.0 * $me1 - $md);
        $pm = $pm + 0.007843 * cos(2.0 * $me1) + 0.002824 * cos(2.0 * $md);
        $pm = $pm + 0.000857 * cos(2.0 * $me1 + $md) + $e * 0.000533 * cos(2.0 * $me1 - $ms);
        $pm = $pm + $e * 0.000401 * cos(2.0 * $me1 - $md - $ms);
        $pm = $pm + $e * 0.00032 * cos($md - $ms) - 0.000271 * cos($me1);
        $pm = $pm - $e * 0.000264 * cos($ms + $md) - 0.000198 * cos(2.0 * $mf - $md);
        $pm = $pm + 0.000173 * cos(3.0 * $md) + 0.000167 * cos(4.0 * $me1 - $md);
        $pm = $pm - $e * 0.000111 * cos($ms) + 0.000103 * cos(4.0 * $me1 - 2.0 * $md);
        $pm = $pm - 0.000084 * cos(2.0 * $md - 2.0 * $me1) - $e * 0.000083 * cos(2.0 * $me1 + $ms);
        $pm = $pm + 0.000079 * cos(2.0 * $me1 + 2.0 * $md) + 0.000072 * cos(4.0 * $me1);
        $pm = $pm + $e * 0.000064 * cos(2.0 * $me1 - $ms + $md) - $e * 0.000063 * cos(2.0 * $me1 + $ms - $md);
        $pm = $pm + $e * 0.000041 * cos($ms + $me1) + $e * 0.000035 * cos(2.0 * $md - $ms);
        $pm = $pm - 0.000033 * cos(3.0 * $md - 2.0 * $me1) - 0.00003 * cos($md + $me1);
        $pm = $pm - 0.000029 * cos(2.0 * ($mf - $me1)) - $e * 0.000029 * cos(2.0 * $md + $ms);
        $pm = $pm + $e2 * 0.000026 * cos(2.0 * ($me1 - $ms)) - 0.000023 * cos(2.0 * ($mf - $me1) + $md);
        $pm = $pm + $e * 0.000019 * cos(4.0 * $me1 - $ms - $md);

        return $pm;
    }

    /**
     * Calculate distance from the Earth to the Moon (km)
     *
     * Original macro name: MoonDist
     */
    function moon_dist($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $hp = deg2rad(moon_hp($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr));
        $r = 6378.14 / sin($hp);

        return $r;
    }

    /**
     * Calculate the Moon's angular diameter (degrees)
     * 
     * Original macro name: MoonSize
     */
    function moon_size($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $hp = deg2rad(moon_hp($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr));
        $r = 6378.14 / sin($hp);
        $th = 384401.0 * 0.5181 / $r;

        return $th;
    }

    /**
     * Convert angle in radians to equivalent angle in degrees.
     * 
     * Original macro name: Unwind
     */
    function unwind($w)
    {
        return $w - 6.283185308 * floor($w / 6.283185308);
    }

    /**
     * Convert angle in degrees to equivalent angle in the range 0 to 360 degrees.
     * 
     * Original macro name: UnwindDeg
     */
    function unwind_deg($w)
    {
        return $w - 360 * floor($w / 360);
    }

    /**
     * Mean ecliptic longitude of the Sun at the epoch
     * 
     * Original macro name: SunElong
     */
    function sun_e_long($gd, $gm, $gy)
    {
        $t = (civil_date_to_julian_date($gd, $gm, $gy) - 2415020) / 36525;
        $t2 = $t * $t;
        $x = 279.6966778 + 36000.76892 * $t + 0.0003025 * $t2;

        return $x - 360 * floor($x / 360);
    }

    /**
     * Longitude of the Sun at perigee
     * 
     * Original macro name: SunPeri
     */
    function sun_peri($gd, $gm, $gy)
    {
        $t = (civil_date_to_julian_date($gd, $gm, $gy) - 2415020) / 36525;
        $t2 = $t * $t;
        $x = 281.2208444 + 1.719175 * $t + 0.000452778 * $t2;

        return $x - 360 * floor($x / 360);
    }

    /**
     * Eccentricity of the Sun-Earth orbit
     * 
     * Original macro name: SunEcc
     */
    function sun_ecc($gd, $gm, $gy)
    {
        $t = (civil_date_to_julian_date($gd, $gm, $gy) - 2415020) / 36525;
        $t2 = $t * $t;

        return 0.01675104 - 0.0000418 * $t - 0.000000126 * $t2;
    }

    /**
     * Ecliptic - Declination (degrees)
     *
     * Original macro name: ECDec
     */
    function ec_dec($eld, $elm, $els, $bd, $bm, $bs, $gd, $gm, $gy)
    {
        $a =  deg2rad(degrees_minutes_seconds_to_decimal_degrees($eld, $elm, $els));
        $b = deg2rad(degrees_minutes_seconds_to_decimal_degrees($bd, $bm, $bs));
        $c = deg2rad(obliq($gd, $gm, $gy));
        $d = sin($b) * cos($c) + cos($b) * sin($c) * sin($a);

        return degrees(asin($d));
    }

    /**
     * Ecliptic - Right Ascension (degrees)
     * 
     * Original macro name: ECRA
     */
    function ec_ra($eld, $elm, $els, $bd, $bm, $bs, $gd, $gm, $gy)
    {
        $a = deg2rad(degrees_minutes_seconds_to_decimal_degrees($eld, $elm, $els));
        $b = deg2rad(degrees_minutes_seconds_to_decimal_degrees($bd, $bm, $bs));
        $c = deg2rad(obliq($gd, $gm, $gy));
        $d = sin($a) * cos($c) - tan($b) * sin($c);
        $e = cos($a);
        $f = degrees(atan2($d, $e));

        return $f - 360 * floor($f / 360);
    }

    /**
     * Calculate Sun's true anomaly, i.e., how much its orbit deviates from a true circle to an ellipse.
     * 
     * Original macro name: SunTrueAnomaly
     */
    function sun_true_anomaly($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly)
    {
        $aa = local_civil_time_greenwich_day($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $bb = local_civil_time_greenwich_month($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $cc = local_civil_time_greenwich_year($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $ut = local_civil_time_to_universal_time($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $dj = civil_date_to_julian_date($aa, $bb, $cc) - 2415020;

        $t = ($dj / 36525) + ($ut / 876600);
        $t2 = $t * $t;

        $a = 99.99736042 * $t;
        $b = 360 * ($a - floor($a));

        $m1 = 358.47583 - (0.00015 + 0.0000033 * $t) * $t2 + $b;
        $ec = 0.01675104 - 0.0000418 * $t - 0.000000126 * $t2;

        $am = deg2rad($m1);

        return degrees(true_anomaly($am, $ec));
    }

    /**
     * Calculate the Sun's mean anomaly.
     * 
     * Original macro name: SunMeanAnomaly
     */
    function sun_mean_anomaly($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly)
    {
        $aa = local_civil_time_greenwich_day($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $bb = local_civil_time_greenwich_month($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $cc = local_civil_time_greenwich_year($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $ut = local_civil_time_to_universal_time($lch, $lcm, $lcs, $ds, $zc, $ld, $lm, $ly);
        $dj = civil_date_to_julian_date($aa, $bb, $cc) - 2415020;
        $t = ($dj / 36525) + ($ut / 876600);
        $t2 = $t * $t;
        $a = 100.0021359 * $t;
        $b = 360 * ($a - floor($a));
        $m1 = 358.47583 - (0.00015 + 0.0000033 * $t) * $t2 + $b;
        $am = unwind(deg2rad($m1));

        return $am;
    }

    /**
     * Calculate local civil time of sunrise.
     *
     * Original macro name: SunriseLCT
     */
    function sunrise_lct($ld, $lm, $ly, $ds, $zc, $gl, $gp)
    {
        $di = 0.8333333;
        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($a, $x, $y, $la, $s) = sunrise_lct_3710($gd, $gm, $gy, $sr, $di, $gp);

        $xx = 0.0;
        if ($s != RiseSetStatus::OK) {
            $xx = -99.0;
        } else {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0, 0, $gl);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);


            if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK) {
                $xx = -99.0;
            } else {
                $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);
                list($a, $x, $y, $la, $s) = sunrise_lct_3710($gd, $gm, $gy, $sr, $di, $gp);

                if ($s != RiseSetStatus::OK) {
                    $xx = -99.0;
                } else {
                    $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0, 0, $gl);
                    $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);
                    $xx = universal_time_to_local_civil_time_ma($ut, 0, 0, $ds, $zc, $gd, $gm, $gy);
                }
            }
        }

        return $xx;
    }

    /**
     * Helper function for sunrise_lct()
     */
    function sunrise_lct_3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_rise(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0.0, 0.0, $y, 0.0, 0.0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Calculate local civil time of sunset.
     * 
     * Original macro name: SunsetLCT
     */
    function sunset_lct($ld, $lm, $ly, $ds, $zc, $gl, $gp)
    {
        $di = 0.8333333;
        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($a, $x, $y, $la, $s) = sunset_lct_l3710($gd, $gm, $gy, $sr, $di, $gp);

        $xx = 0.0;
        if ($s != RiseSetStatus::OK) {
            $xx = -99.0;
        } else {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0, 0, $gl);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

            if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK) {
                $xx = -99.0;
            } else {
                $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);
                list($a, $x, $y, $la, $s) = sunset_lct_l3710($gd, $gm, $gy, $sr, $di, $gp);

                if ($s != RiseSetStatus::OK) {
                    $xx = -99;
                } else {
                    $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0, 0, $gl);
                    $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);
                    $xx = universal_time_to_local_civil_time_ma($ut, 0, 0, $ds, $zc, $gd, $gm, $gy);
                }
            }
        }
        return $xx;
    }

    /**
     * Helper function for sunset_lct().
     */
    function sunset_lct_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0.0, 0.0, 0.0, 0.0, 0.0, $gd, $gm, $gy);
        $y = ec_dec($a, 0.0, 0.0, 0.0, 0.0, 0.0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_set(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Local sidereal time of rise, in hours.
     * 
     * Original macro name: RSLSTR
     */
    function rise_set_local_sidereal_time_rise($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g)
    {
        $a = hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras);
        $b = deg2rad(degree_hours_to_decimal_degrees($a));
        $c = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $d = deg2rad($vd);
        $e = deg2rad($g);
        $f = - (sin($d) + sin($e) * sin($c)) / (cos($e) * cos($c));
        $h = (abs($f) < 1) ? acos($f) : 0;
        $i = decimal_degrees_to_degree_hours(degrees($b - $h));

        return $i - 24 * floor($i / 24);
    }

    /**
     * Local sidereal time of setting, in hours.
     * 
     * Original macro name: RSLSTS
     */
    function  rise_set_local_sidereal_time_set($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g)
    {
        $a = hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras);
        $b = deg2rad(degree_hours_to_decimal_degrees($a));
        $c = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $d = deg2rad($vd);
        $e = deg2rad($g);
        $f = - (sin($d) + sin($e) * sin($c)) / (cos($e) * cos($c));
        $h = (abs($f) < 1) ? acos($f) : 0;
        $i = decimal_degrees_to_degree_hours(degrees($b + $h));

        return $i - 24 * floor($i / 24);
    }

    /**
     * Azimuth of rising, in degrees.
     * 
     * Original macro name: RSAZR
     */
    function rise_set_azimuth_rise($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g)
    {
        $a = hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras);
        $c = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $d = deg2rad($vd);
        $e = deg2rad($g);
        $f = (sin($c) + sin($d) * sin($e)) / (cos($d) * cos($e));
        $h = ers($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g) == RiseSetStatus::OK ? acos($f) : 0;
        $i = degrees($h);

        return $i - 360 * floor($i / 360);
    }

    /**
     * Azimuth of setting, in degrees.
     * 
     * Original macro name: RSAZS
     */
    function rise_set_azimuth_set($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g)
    {
        $a = hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras);
        $c = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $d = deg2rad($vd);
        $e = deg2rad($g);
        $f = (sin($c) + sin($d) * sin($e)) / (cos($d) * cos($e));
        $h = ers($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g) == RiseSetStatus::OK ? acos($f) : 0;
        $i = 360 - degrees($h);

        return $i - 360 * floor($i / 360);
    }

    /**
     * Rise/Set status
     * 
     * Original macro name: eRS
     */
    function ers($rah, $ram, $ras, $dd, $dm, $ds, $vd, $g)
    {
        $a = hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras);
        $c = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $d = deg2rad($vd);
        $e = deg2rad($g);
        $f = - (sin($d) + sin($e) * sin($c)) / (cos($e)  * cos($c));

        $returnValue = RiseSetStatus::OK;
        if ($f >= 1)
            $returnValue = RiseSetStatus::NeverRises;
        if ($f <= -1)
            $returnValue = RiseSetStatus::Circumpolar;

        return $returnValue;
    }

    /**
     * Sunrise/Sunset calculation status.
     * 
     * Original macro name: eSunRS
     */
    function e_sun_rs($ld, $lm, $ly, $ds, $zc, $gl, $gp)
    {
        $di = 0.8333333;
        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($a, $x, $y, $la, $s) = e_sun_rs_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($s != RiseSetStatus::OK) {
            return $s;
        } else {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0, 0, $gl);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);
            $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);
            list($a, $x, $y, $la, $s) = e_sun_rs_l3710($gd, $gm, $gy, $sr, $di, $gp);
            if ($s != RiseSetStatus::OK) {
                return $s;
            } else {
                $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0, 0, $gl);

                if (eg_st_ut($x, 0, 0, $gd, $gm, $gy)   != WarningFlag::OK) {
                    $s = RiseSetStatus::GstToUtConversionWarning;

                    return $s;
                }

                return $s;
            }
        }
    }

    /**
     * Helper function for e_sun_rs
     */
    function e_sun_rs_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_rise(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Calculate azimuth of sunrise.
     * 
     * Original macro name: SunriseAz
     */
    function sunrise_az($ld, $lm, $ly, $ds, $zc, $gl, $gp)
    {
        $di = 0.8333333;
        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($result1_a, $result1_x, $result1_y, $result1_la, $result1_s) = sunrise_az_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result1_s != RiseSetStatus::OK) {
            return -99.0;
        }

        $x = local_sidereal_time_to_greenwich_sidereal_time($result1_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

        if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK) {
            return -99.0;
        }

        $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);
        list($result2_a, $result2_x, $result2_y, $result2_la, $result2_s) = sunrise_az_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result2_s != RiseSetStatus::OK) {
            return -99.0;
        }

        return rise_set_azimuth_rise(decimal_degrees_to_degree_hours($x), 0, 0, $result2_y, 0.0, 0.0, $di, $gp);
    }

    /**
     * Helper function for sunrise_az()
     */
    function sunrise_az_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_rise(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Calculate azimuth of sunset.
     * 
     * Original macro name: SunsetAz
     */
    function sunset_az($ld, $lm, $ly, $ds, $zc, $gl, $gp)
    {
        $di = 0.8333333;
        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($result1_a, $result1_x, $result1_y, $result1_la, $result1_s) = sunset_az_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result1_s != RiseSetStatus::OK) {
            return -99.0;
        }

        $x = local_sidereal_time_to_greenwich_sidereal_time($result1_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

        if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK) {
            return -99.0;
        }

        $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);

        list($result2_a, $result2_x, $result2_y, $result2_la, $result2_s) = sunset_az_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result2_s != RiseSetStatus::OK) {
            return -99.0;
        }

        return rise_set_azimuth_set(decimal_degrees_to_degree_hours($x), 0, 0, $result2_y, 0, 0, $di, $gp);
    }

    /**
     * Helper function for sunset_az()
     */
    function sunset_az_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_set(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Calculate morning twilight start, in local time.
     * 
     * Original macro name: TwilightAMLCT
     */
    function twilight_am_lct($ld, $lm, $ly, $ds, $zc, $gl, $gp, $tt)
    {
        $di = (float)$tt->value;

        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($result1_a, $result1_x, $result1_y, $result1_la, $result1_s) = twilight_am_lct_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result1_s != RiseSetStatus::OK)
            return -99.0;

        $x = local_sidereal_time_to_greenwich_sidereal_time($result1_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

        if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK)
            return -99.0;

        $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);

        list($result2_a, $result2_x, $result2_y, $result2_la, $result2_s) = twilight_am_lct_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result2_s != RiseSetStatus::OK)
            return -99.0;

        $x = local_sidereal_time_to_greenwich_sidereal_time($result2_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

        $xx = universal_time_to_local_civil_time_ma($ut, 0, 0, $ds, $zc, $gd, $gm, $gy);

        return $xx;
    }

    /**
     * Helper function for twilight_am_lct()
     */
    function twilight_am_lct_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_rise(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Calculate evening twilight end, in local time.
     * 
     * Original macro name: TwilightPMLCT
     */
    function twilight_pm_lct($ld, $lm, $ly, $ds, $zc, $gl, $gp, $tt)
    {
        $di = (float)$tt->value;

        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($result1_a, $result1_x, $result1_y, $result1_la, $result1_s) = twilight_pm_lct_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result1_s != RiseSetStatus::OK)
            return 0.0;

        $x = local_sidereal_time_to_greenwich_sidereal_time($result1_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

        if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK)
            return 0.0;

        $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);

        list($result2_a, $result2_x, $result2_y, $result2_la, $result2_s) = twilight_pm_lct_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result2_s != RiseSetStatus::OK)
            return 0.0;

        $x = local_sidereal_time_to_greenwich_sidereal_time($result2_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);

        return universal_time_to_local_civil_time_ma($ut, 0, 0, $ds, $zc, $gd, $gm, $gy);
    }

    /**
     * Helper function for twilight_pm_lct()
     */
    function twilight_pm_lct_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_set(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        return array($a, $x, $y, $la, $s);
    }

    /**
     * Twilight calculation status.
     * 
     * Original macro name: eTwilight
     */
    function e_twilight($ld, $lm, $ly, $ds, $zc, $gl, $gp, $tt)
    {
        $di = (float)$tt->value;

        $gd = local_civil_time_greenwich_day(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gm = local_civil_time_greenwich_month(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $gy = local_civil_time_greenwich_year(12, 0, 0, $ds, $zc, $ld, $lm, $ly);
        $sr = sun_long(12, 0, 0, $ds, $zc, $ld, $lm, $ly);

        list($result1_a, $result1_x, $result1_y, $result1_la, $result1_s) = e_twilight_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result1_s != TwilightStatus::OK)
            return $result1_s;

        $x = local_sidereal_time_to_greenwich_sidereal_time($result1_la, 0, 0, $gl);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0, 0, $gd, $gm, $gy);
        $sr = sun_long($ut, 0, 0, 0, 0, $gd, $gm, $gy);

        list($result2_a, $result2_x, $result2_y, $result2_la, $result2_s) = e_twilight_l3710($gd, $gm, $gy, $sr, $di, $gp);

        if ($result2_s != TwilightStatus::OK)
            return $result2_s;

        $x = local_sidereal_time_to_greenwich_sidereal_time($result2_la, 0, 0, $gl);

        if (eg_st_ut($x, 0, 0, $gd, $gm, $gy) != WarningFlag::OK) {
            $result2_s = TwilightStatus::GstToUtConversionWarning;

            return $result2_s;
        }

        return $result2_s;
    }

    /**
     * Helper function for e_twilight()
     */
    function e_twilight_l3710($gd, $gm, $gy, $sr, $di, $gp)
    {
        $a = $sr + nutat_long($gd, $gm, $gy) - 0.005694;
        $x = ec_ra($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $y = ec_dec($a, 0, 0, 0, 0, 0, $gd, $gm, $gy);
        $la = rise_set_local_sidereal_time_rise(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);
        $s = ers(decimal_degrees_to_degree_hours($x), 0, 0, $y, 0, 0, $di, $gp);

        $ts = TwilightStatus::OK;

        if ($s == RiseSetStatus::Circumpolar)
            $ts = TwilightStatus::LastsAllNight;

        if ($s == RiseSetStatus::NeverRises)
            $ts = TwilightStatus::SunTooFarBelowHorizon;

        return array($a, $x, $y, $la, $ts);
    }

    /**
     * Calculate the angle between two celestial objects
     */
    function angle($xx1, $xm1, $xs1, $dd1, $dm1, $ds1, $xx2, $xm2, $xs2, $dd2, $dm2, $ds2, $s)
    {
        $a = ($s == AngleMeasure::Hours)
            ? degree_hours_to_decimal_degrees(hours_minutes_seconds_to_decimal_hours($xx1, $xm1, $xs1))
            : degrees_minutes_seconds_to_decimal_degrees($xx1, $xm1, $xs1);
        $b = deg2rad($a);
        $c = degrees_minutes_seconds_to_decimal_degrees($dd1, $dm1, $ds1);
        $d = deg2rad($c);
        $e = ($s == AngleMeasure::Hours)
            ? degree_hours_to_decimal_degrees(hours_minutes_seconds_to_decimal_hours($xx2, $xm2, $xs2))
            : degrees_minutes_seconds_to_decimal_degrees($xx2, $xm2, $xs2);
        $f = deg2rad($e);
        $g = degrees_minutes_seconds_to_decimal_degrees($dd2, $dm2, $ds2);
        $h = deg2rad($g);
        $i = acos(sin($d) * sin($h) + cos($d) * cos($h) * cos($b - $f));

        return w_to_degrees($i);
    }

    /**
     * Calculate several planetary properties.
     *
     * Original macro names: PlanetLong, PlanetLat, PlanetDist, PlanetHLong1, PlanetHLong2, PlanetHLat, PlanetRVect
     */
    function planet_coordinates($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr, $s)
    {
        $a11 = 178.179078;
        $a12 = 415.2057519;
        $a13 = 0.0003011;
        $a14 = 0.0;
        $a21 = 75.899697;
        $a22 = 1.5554889;
        $a23 = 0.0002947;
        $a24 = 0.0;
        $a31 = 0.20561421;
        $a32 = 0.00002046;
        $a33 = -0.00000003;
        $a34 = 0.0;
        $a41 = 7.002881;
        $a42 = 0.0018608;
        $a43 = -0.0000183;
        $a44 = 0.0;
        $a51 = 47.145944;
        $a52 = 1.1852083;
        $a53 = 0.0001739;
        $a54 = 0.0;
        $a61 = 0.3870986;
        $a62 = 6.74;
        $a63 = -0.42;

        $b11 = 342.767053;
        $b12 = 162.5533664;
        $b13 = 0.0003097;
        $b14 = 0.0;
        $b21 = 130.163833;
        $b22 = 1.4080361;
        $b23 = -0.0009764;
        $b24 = 0.0;
        $b31 = 0.00682069;
        $b32 = -0.00004774;
        $b33 = 0.000000091;
        $b34 = 0.0;
        $b41 = 3.393631;
        $b42 = 0.0010058;
        $b43 = -0.000001;
        $b44 = 0.0;
        $b51 = 75.779647;
        $b52 = 0.89985;
        $b53 = 0.00041;
        $b54 = 0.0;
        $b61 = 0.7233316;
        $b62 = 16.92;
        $b63 = -4.4;

        $c11 = 293.737334;
        $c12 = 53.17137642;
        $c13 = 0.0003107;
        $c14 = 0.0;
        $c21 = 334.218203;
        $c22 = 1.8407584;
        $c23 = 0.0001299;
        $c24 = -0.00000119;
        $c31 = 0.0933129;
        $c32 = 0.000092064;
        $c33 = -0.000000077;
        $c34 = 0.0;
        $c41 = 1.850333;
        $c42 = -0.000675;
        $c43 = 0.0000126;
        $c44 = 0.0;
        $c51 = 48.786442;
        $c52 = 0.7709917;
        $c53 = -0.0000014;
        $c54 = -0.00000533;
        $c61 = 1.5236883;
        $c62 = 9.36;
        $c63 = -1.52;

        $d11 = 238.049257;
        $d12 = 8.434172183;
        $d13 = 0.0003347;
        $d14 = -0.00000165;
        $d21 = 12.720972;
        $d22 = 1.6099617;
        $d23 = 0.00105627;
        $d24 = -0.00000343;
        $d31 = 0.04833475;
        $d32 = 0.00016418;
        $d33 = -0.0000004676;
        $d34 = -0.0000000017;
        $d41 = 1.308736;
        $d42 = -0.0056961;
        $d43 = 0.0000039;
        $d44 = 0.0;
        $d51 = 99.443414;
        $d52 = 1.01053;
        $d53 = 0.00035222;
        $d54 = -0.00000851;
        $d61 = 5.202561;
        $d62 = 196.74;
        $d63 = -9.4;

        $e11 = 266.564377;
        $e12 = 3.398638567;
        $e13 = 0.0003245;
        $e14 = -0.0000058;
        $e21 = 91.098214;
        $e22 = 1.9584158;
        $e23 = 0.00082636;
        $e24 = 0.00000461;
        $e31 = 0.05589232;
        $e32 = -0.0003455;
        $e33 = -0.000000728;
        $e34 = 0.00000000074;
        $e41 = 2.492519;
        $e42 = -0.0039189;
        $e43 = -0.00001549;
        $e44 = 0.00000004;
        $e51 = 112.790414;
        $e52 = 0.8731951;
        $e53 = -0.00015218;
        $e54 = -0.00000531;
        $e61 = 9.554747;
        $e62 = 165.6;
        $e63 = -8.88;

        $f11 = 244.19747;
        $f12 = 1.194065406;
        $f13 = 0.000316;
        $f14 = -0.0000006;
        $f21 = 171.548692;
        $f22 = 1.4844328;
        $f23 = 0.0002372;
        $f24 = -0.00000061;
        $f31 = 0.0463444;
        $f32a = -0.00002658;
        $f33 = 0.000000077;
        $f34 = 0.0;
        $f41 = 0.772464;
        $f42 = 0.0006253;
        $f43 = 0.0000395;
        $f44 = 0.0;
        $f51 = 73.477111;
        $f52 = 0.4986678;
        $f53 = 0.0013117;
        $f54 = 0.0;
        $f61 = 19.21814;
        $f62 = 65.8;
        $f63 = -7.19;

        $g11 = 84.457994;
        $g12 = 0.6107942056;
        $g13 = 0.0003205;
        $g14 = -0.0000006;
        $g21 = 46.727364;
        $g22 = 1.4245744;
        $g23 = 0.00039082;
        $g24 = -0.000000605;
        $g31 = 0.00899704;
        $g32 = 0.00000633;
        $g33 = -0.000000002;
        $g34 = 0.0;
        $g41 = 1.779242;
        $g42 = -0.0095436;
        $g43 = -0.0000091;
        $g44 = 0.0;
        $g51 = 130.681389;
        $g52 = 1.098935;
        $g53 = 0.00024987;
        $g54 = -0.000004718;
        $g61 = 30.10957;
        $g62 = 62.2;
        $g63 = -6.87;

        $planet_data = [];

        $planet_data[] = new PlanetDataPrecise("", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

        $ip = 0;
        $b = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $a = civil_date_to_julian_date($gd, $gm, $gy);
        $t = (($a - 2415020.0) / 36525.0) + ($b / 876600.0);

        $a0 = $a11;
        $a1 = $a12;
        $a2 = $a13;
        $a3 = $a14;
        $b0 = $a21;
        $b1 = $a22;
        $b2 = $a23;
        $b3 = $a24;
        $c0 = $a31;
        $c1 = $a32;
        $c2 = $a33;
        $c3 = $a34;
        $d0 = $a41;
        $d1 = $a42;
        $d2 = $a43;
        $d3 = $a44;
        $e0 = $a51;
        $e1 = $a52;
        $e2 = $a53;
        $e3 = $a54;
        $f = $a61;
        $g = $a62;
        $h = $a63;
        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Mercury",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $a0 = $b11;
        $a1 = $b12;
        $a2 = $b13;
        $a3 = $b14;
        $b0 = $b21;
        $b1 = $b22;
        $b2 = $b23;
        $b3 = $b24;
        $c0 = $b31;
        $c1 = $b32;
        $c2 = $b33;
        $c3 = $b34;
        $d0 = $b41;
        $d1 = $b42;
        $d2 = $b43;
        $d3 = $b44;
        $e0 = $b51;
        $e1 = $b52;
        $e2 = $b53;
        $e3 = $b54;
        $f = $b61;
        $g = $b62;
        $h = $b63;
        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Venus",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $a0 = $c11;
        $a1 = $c12;
        $a2 = $c13;
        $a3 = $c14;
        $b0 = $c21;
        $b1 = $c22;
        $b2 = $c23;
        $b3 = $c24;
        $c0 = $c31;
        $c1 = $c32;
        $c2 = $c33;
        $c3 = $c34;
        $d0 = $c41;
        $d1 = $c42;
        $d2 = $c43;
        $d3 = $c44;
        $e0 = $c51;
        $e1 = $c52;
        $e2 = $c53;
        $e3 = $c54;
        $f = $c61;
        $g = $c62;
        $h = $c63;

        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Mars",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $a0 = $d11;
        $a1 = $d12;
        $a2 = $d13;
        $a3 = $d14;
        $b0 = $d21;
        $b1 = $d22;
        $b2 = $d23;
        $b3 = $d24;
        $c0 = $d31;
        $c1 = $d32;
        $c2 = $d33;
        $c3 = $d34;
        $d0 = $d41;
        $d1 = $d42;
        $d2 = $d43;
        $d3 = $d44;
        $e0 = $d51;
        $e1 = $d52;
        $e2 = $d53;
        $e3 = $d54;
        $f = $d61;
        $g = $d62;
        $h = $d63;

        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Jupiter",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $a0 = $e11;
        $a1 = $e12;
        $a2 = $e13;
        $a3 = $e14;
        $b0 = $e21;
        $b1 = $e22;
        $b2 = $e23;
        $b3 = $e24;
        $c0 = $e31;
        $c1 = $e32;
        $c2 = $e33;
        $c3 = $e34;
        $d0 = $e41;
        $d1 = $e42;
        $d2 = $e43;
        $d3 = $e44;
        $e0 = $e51;
        $e1 = $e52;
        $e2 = $e53;
        $e3 = $e54;
        $f = $e61;
        $g = $e62;
        $h = $e63;

        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Saturn",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $a0 = $f11;
        $a1 = $f12;
        $a2 = $f13;
        $a3 = $f14;
        $b0 = $f21;
        $b1 = $f22;
        $b2 = $f23;
        $b3 = $f24;
        $c0 = $f31;
        $c1 = $f32a;
        $c2 = $f33;
        $c3 = $f34;
        $d0 = $f41;
        $d1 = $f42;
        $d2 = $f43;
        $d3 = $f44;
        $e0 = $f51;
        $e1 = $f52;
        $e2 = $f53;
        $e3 = $f54;
        $f = $f61;
        $g = $f62;
        $h = $f63;

        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Uranus",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $a0 = $g11;
        $a1 = $g12;
        $a2 = $g13;
        $a3 = $g14;
        $b0 = $g21;
        $b1 = $g22;
        $b2 = $g23;
        $b3 = $g24;
        $c0 = $g31;
        $c1 = $g32;
        $c2 = $g33;
        $c3 = $g34;
        $d0 = $g41;
        $d1 = $g42;
        $d2 = $g43;
        $d3 = $g44;
        $e0 = $g51;
        $e1 = $g52;
        $e2 = $g53;
        $e3 = $g54;
        $f = $g61;
        $g = $g62;
        $h = $g63;

        $aa = $a1 * $t;
        $b = 360.0 * ($aa - floor($aa));
        $c = $a0 + $b + ($a3 * $t + $a2) * $t * $t;

        $planet_data[] = new PlanetDataPrecise(
            "Neptune",
            $c - 360.0 * floor($c / 360.0),
            ($a1 * 0.009856263) + ($a2 + $a3) / 36525.0,
            (($b3 * $t + $b2) * $t + $b1) * $t + $b0,
            (($c3 * $t + $c2) * $t + $c1) * $t + $c0,
            (($d3 * $t + $d2) * $t + $d1) * $t + $d0,
            (($e3 * $t + $e2) * $t + $e1) * $t + $e0,
            $f,
            $g,
            $h,
            0
        );

        $check_planet = new PlanetDataPrecise("NotFound", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        foreach ($planet_data as $planet_record) {
            if ($planet_record->name == $s) {
                $check_planet = $planet_record;
            }
        }

        if ($check_planet->name == "NotFound")
            return array(w_to_degrees(unwind(0)), w_to_degrees(unwind(0)), w_to_degrees(unwind(0)), w_to_degrees(unwind(0)), w_to_degrees(unwind(0)), w_to_degrees(unwind(0)), w_to_degrees(unwind(0)));

        $li = 0.0;
        $ms = sun_mean_anomaly($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $sr = deg2rad(sun_long($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr));
        $re = sun_dist($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $lg = $sr + pi();

        $l0 = 0.0;
        $s0 = 0.0;
        $p0 = 0.0;
        $vo = 0.0;
        $lp1 = 0.0;
        $ll = 0.0;
        $rd = 0.0;
        $pd = 0.0;
        $sp = 0.0;
        $ci = 0.0;

        for ($k = 1; $k <= 3; $k++) {
            foreach ($planet_data as $planet_record) {
                $planet_record->ap_value = deg2rad($planet_record->value1 - $planet_record->value3 - $li * $planet_record->value2);
            }

            $qa = 0.0;
            $qb = 0.0;
            $qc = 0.0;
            $qd = 0.0;
            $qe = 0.0;
            $qf = 0.0;
            $qg = 0.0;

            if ($s == "Mercury") {
                list($l4685_qa, $l4685_qb) = planet_long_l4685($planet_data);

                $qa = $l4685_qa;
                $qb = $l4685_qb;
            }

            if ($s == "Venus") {
                list($l4735_qa, $l4735_qb, $l4735_qc, $l4735_qe) = planet_long_l4735($planet_data, $ms, $t);

                $qa = $l4735_qa;
                $qb = $l4735_qb;
                $qc = $l4735_qc;
                $qe = $l4735_qe;
            }

            if ($s == "Mars") {
                list($l4810_a, $l4810_sa, $l4810_ca, $l4810_qc, $l4810_qe, $l4810_qa, $l4810_qb) = planet_long_l4810($planet_data, $ms);

                $qc = $l4810_qc;
                $qe = $l4810_qe;
                $qa = $l4810_qa;
                $qb = $l4810_qb;
            }


            $match_planet = new PlanetDataPrecise("NotFound", 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
            foreach ($planet_data as $planet_record) {
                if ($planet_record->name == $s) {
                    $match_planet = $planet_record;
                }
            }

            if (in_array($s, ["Jupiter", "Saturn", "Uranus", "Neptune"])) {

                list($l4945_qa, $l4945_qb, $l4945_qc, $l4945_qd, $l4945_qe, $l4945_qf, $l4945_qg) = planet_long_l4945($t, $match_planet);

                $qa = $l4945_qa;
                $qb = $l4945_qb;
                $qc = $l4945_qc;
                $qd = $l4945_qd;
                $qe = $l4945_qe;
                $qf = $l4945_qf;
                $qg = $l4945_qg;
            }

            $ec = $match_planet->value4 + $qd;
            $am = $match_planet->ap_value + $qe;
            $at = true_anomaly($am, $ec);
            $pvv = ($match_planet->value7 + $qf) * (1.0 - $ec * $ec) / (1.0 + $ec * cos($at));
            $lp = w_to_degrees($at) + $match_planet->value3 + w_to_degrees($qc - $qe);
            $lp = deg2rad($lp);
            $om = deg2rad($match_planet->value6);
            $lo = $lp - $om;
            $so = sin($lo);
            $co = cos($lo);
            $inn = deg2rad($match_planet->value5);
            $pvv += $qb;
            $sp = $so * sin($inn);
            $y = $so * cos($inn);
            $ps = asin($sp) + $qg;
            $sp = sin($ps);
            $pd = atan2($y, $co) + $om + deg2rad($qa);
            $pd = unwind($pd);
            $ci = cos($ps);
            $rd = $pvv * $ci;
            $ll = $pd - $lg;
            $rh = $re * $re + $pvv * $pvv - 2.0 * $re * $pvv * $ci * cos($ll);
            $rh = sqrt($rh);
            $li = $rh * 0.005775518;

            if ($k == 1) {
                $l0 = $pd;
                $s0 = $ps;
                $p0 = $pvv;
                $vo = $rh;
                $lp1 = $lp;
            }
        }

        $l1 = sin($ll);
        $l2 = cos($ll);

        $ep = ($ip < 3)
            ? atan(-1.0 * $rd * $l1 / ($re - $rd * $l2)) + $lg + pi()
            : atan($re * $l1 / ($rd - $re * $l2)) + $pd;
        $ep = unwind($ep);

        $bp = atan($rd * $sp * sin($ep - $pd) / ($ci * $re * $l1));

        $planet_longitude = w_to_degrees(unwind($ep));
        $planet_latitude = w_to_degrees(unwind($bp));
        $planet_distance_au = $vo;
        $planet_h_long1 = w_to_degrees($lp1);
        $planet_h_long2 = w_to_degrees($l0);
        $planet_h_lat = w_to_degrees($s0);
        $planet_r_vec = $p0;

        return array($planet_longitude, $planet_latitude, $planet_distance_au, $planet_h_long1, $planet_h_long2, $planet_h_lat, $planet_r_vec);
    }

    /** Helper function for planet_coordinates() */
    function planet_long_l4685($pl)
    {
        $qa = 0.00204 * cos(5.0 * $pl[2]->ap_value - 2.0 * $pl[1]->ap_value + 0.21328);
        $qa += 0.00103 * cos(2.0 * $pl[2]->ap_value - $pl[1]->ap_value - 2.8046);
        $qa += 0.00091 * cos(2.0 * $pl[4]->ap_value - $pl[1]->ap_value - 0.64582);
        $qa += 0.00078 * cos(5.0 * $pl[2]->ap_value - 3.0 * $pl[1]->ap_value + 0.17692);

        $qb = 0.000007525 * cos(2.0 * $pl[4]->ap_value - $pl[1]->ap_value + 0.925251);
        $qb += 0.000006802 * cos(5.0 * $pl[2]->ap_value - 3.0 * $pl[1]->ap_value - 4.53642);
        $qb += 0.000005457 * cos(2.0 * $pl[2]->ap_value - 2.0 * $pl[1]->ap_value - 1.24246);
        $qb += 0.000003569 * cos(5.0 * $pl[2]->ap_value - $pl[1]->ap_value - 1.35699);

        return array($qa, $qb);
    }

    /** Helper function for planet_coordinates() */
    function planet_long_l4735($pl, $ms, $t)
    {
        $qc = 0.00077 * sin(4.1406 + $t * 2.6227);
        $qc = deg2rad($qc);
        $qe = $qc;

        $qa = 0.00313 * cos(2.0 * $ms - 2.0 * $pl[2]->ap_value - 2.587);
        $qa += 0.00198 * cos(3.0 * $ms - 3.0 * $pl[2]->ap_value + 0.044768);
        $qa += 0.00136 * cos($ms - $pl[2]->ap_value - 2.0788);
        $qa += 0.00096 * cos(3.0 * $ms - 2.0 * $pl[2]->ap_value - 2.3721);
        $qa += 0.00082 * cos($pl[4]->ap_value - $pl[2]->ap_value - 3.6318);

        $qb = 0.000022501 * cos(2.0 * $ms - 2.0 * $pl[2]->ap_value - 1.01592);
        $qb += 0.000019045 * cos(3.0 * $ms - 3.0 * $pl[2]->ap_value + 1.61577);
        $qb += 0.000006887 * cos($pl[4]->ap_value - $pl[2]->ap_value - 2.06106);
        $qb += 0.000005172 * cos($ms - $pl[2]->ap_value - 0.508065);
        $qb += 0.00000362 * cos(5.0 * $ms - 4.0 * $pl[2]->ap_value - 1.81877);
        $qb += 0.000003283 * cos(4.0 * $ms - 4.0 * $pl[2]->ap_value + 1.10851);
        $qb += 0.000003074 * cos(2.0 * $pl[4]->ap_value - 2.0 * $pl[2]->ap_value - 0.962846);

        return array($qa, $qb, $qc, $qe);
    }

    /** Helper function for planet_coordinates() */
    function planet_long_l4810($pl, $ms)
    {
        $a = 3.0 * $pl[4]->ap_value - 8.0 * $pl[3]->ap_value + 4.0 * $ms;
        $sa = sin($a);
        $ca = cos($a);
        $qc = - (0.01133 * $sa + 0.00933 * $ca);
        $qc = deg2rad($qc);
        $qe = $qc;

        $qa = 0.00705 * cos($pl[4]->ap_value - $pl[3]->ap_value - 0.85448);
        $qa += 0.00607 * cos(2.0 * $pl[4]->ap_value - $pl[3]->ap_value - 3.2873);
        $qa += 0.00445 * cos(2.0 * $pl[4]->ap_value - 2.0 * $pl[3]->ap_value - 3.3492);
        $qa += 0.00388 * cos($ms - 2.0 * $pl[3]->ap_value + 0.35771);
        $qa += 0.00238 * cos($ms - $pl[3]->ap_value + 0.61256);
        $qa += 0.00204 * cos(2.0 * $ms - 3.0 * $pl[3]->ap_value + 2.7688);
        $qa += 0.00177 * cos(3.0 * $pl[3]->ap_value - $pl[2]->ap_value - 1.0053);
        $qa += 0.00136 * cos(2.0 * $ms - 4.0 * $pl[3]->ap_value + 2.6894);
        $qa += 0.00104 * cos($pl[4]->ap_value + 0.30749);

        $qb = 0.000053227 * cos($pl[4]->ap_value - $pl[3]->ap_value + 0.717864);
        $qb += 0.000050989 * cos(2.0 * $pl[4]->ap_value - 2.0 * $pl[3]->ap_value - 1.77997);
        $qb += 0.000038278 * cos(2.0 * $pl[4]->ap_value - $pl[3]->ap_value - 1.71617);
        $qb += 0.000015996 * cos($ms - $pl[3]->ap_value - 0.969618);
        $qb += 0.000014764 * cos(2.0 * $ms - 3.0 * $pl[3]->ap_value + 1.19768);
        $qb += 0.000008966 * cos($pl[4]->ap_value - 2.0 * $pl[3]->ap_value + 0.761225);
        $qb += 0.000007914 * cos(3.0 * $pl[4]->ap_value - 2.0 * $pl[3]->ap_value - 2.43887);
        $qb += 0.000007004 * cos(2.0 * $pl[4]->ap_value - 3.0 * $pl[3]->ap_value - 1.79573);
        $qb += 0.00000662 * cos($ms - 2.0 * $pl[3]->ap_value + 1.97575);
        $qb += 0.00000493 * cos(3.0 * $pl[4]->ap_value - 3.0 * $pl[3]->ap_value - 1.33069);
        $qb += 0.000004693 * cos(3.0 * $ms - 5.0 * $pl[3]->ap_value + 3.32665);
        $qb += 0.000004571 * cos(2.0 * $ms - 4.0 * $pl[3]->ap_value + 4.27086);
        $qb += 0.000004409 * cos(3.0 * $pl[4]->ap_value - $pl[3]->ap_value - 2.02158);

        return array($a, $sa, $ca, $qc, $qe, $qa, $qb);
    }

    /** Helper function for planet_coordinates() */
    function planet_long_l4945($t, $planet)
    {
        $qa = 0.0;
        $qb = 0.0;
        $qc = 0.0;
        $qd = 0.0;
        $qe = 0.0;
        $qf = 0.0;
        $qg = 0.0;
        $vk = 0.0;
        $ja = 0.0;
        $jb = 0.0;
        $jc = 0.0;

        $j1 = $t / 5.0 + 0.1;
        $j2 = unwind(4.14473 + 52.9691 * $t);
        $j3 = unwind(4.641118 + 21.32991 * $t);
        $j4 = unwind(4.250177 + 7.478172 * $t);
        $j5 = 5.0 * $j3 - 2.0 * $j2;
        $j6 = 2.0 * $j2 - 6.0 * $j3 + 3.0 * $j4;

        if (in_array($planet->name, ["Mercury", "Venus", "Mars"])) {
            return array($qa, $qb, $qc, $qd, $qe, $qf, $qg);
        }

        if (in_array($planet->name, ["Jupiter", "Saturn"])) {
            $j7 = $j3 - $j2;
            $u1 = sin($j3);
            $u2 = cos($j3);
            $u3 = sin(2.0 * $j3);
            $u4 = cos(2.0 * $j3);
            $u5 = sin($j5);
            $u6 = cos($j5);
            $u7 = sin(2.0 * $j5);
            $u8a = sin($j6);
            $u9 = sin($j7);
            $ua = cos($j7);
            $ub = sin(2.0 * $j7);
            $uc = cos(2.0 * $j7);
            $ud = sin(3.0 * $j7);
            $ue = cos(3.0 * $j7);
            $uf = sin(4.0 * $j7);
            $ug = cos(4.0 * $j7);
            $vh = cos(5.0 * $j7);

            if ($planet->name == "Saturn") {
                $ui = sin(3.0 * $j3);
                $uj = cos(3.0 * $j3);
                $uk = sin(4.0 * $j3);
                $ul = cos(4.0 * $j3);
                $vi = cos(2.0 * $j5);
                $un = sin(5.0 * $j7);
                $j8 = $j4 - $j3;
                $uo = sin(2.0 * $j8);
                $up = cos(2.0 * $j8);
                $uq = sin(3.0 * $j8);
                $ur = cos(3.0 * $j8);

                $qc = 0.007581 * $u7 - 0.007986 * $u8a - 0.148811 * $u9;
                $qc -= (0.814181 - (0.01815 - 0.016714 * $j1) * $j1) * $u5;
                $qc -= (0.010497 - (0.160906 - 0.0041 * $j1) * $j1) * $u6;
                $qc = $qc - 0.015208 * $ud - 0.006339 * $uf - 0.006244 * $u1;
                $qc = $qc - 0.0165 * $ub * $u1 - 0.040786 * $ub;
                $qc = $qc + (0.008931 + 0.002728 * $j1) * $u9 * $u1 - 0.005775 * $ud * $u1;
                $qc = $qc + (0.081344 + 0.003206 * $j1) * $ua * $u1 + 0.015019 * $uc * $u1;
                $qc = $qc + (0.085581 + 0.002494 * $j1) * $u9 * $u2 + 0.014394 * $uc * $u2;
                $qc = $qc + (0.025328 - 0.003117 * $j1) * $ua * $u2 + 0.006319 * $ue * $u2;
                $qc = $qc + 0.006369 * $u9 * $u3 + 0.009156 * $ub * $u3 + 0.007525 * $uq * $u3;
                $qc = $qc - 0.005236 * $ua * $u4 - 0.007736 * $uc * $u4 - 0.007528 * $ur * $u4;
                $qc = deg2rad($qc);

                $qd = (-7927.0 + (2548.0 + 91.0 * $j1) * $j1) * $u5;
                $qd = $qd + (13381.0 + (1226.0 - 253.0 * $j1) * $j1) * $u6 + (248.0 - 121.0 * $j1) * $u7;
                $qd = $qd - (305.0 + 91.0 * $j1) * $vi + 412.0 * $ub + 12415.0 * $u1;
                $qd = $qd + (390.0 - 617.0 * $j1) * $u9 * $u1 + (165.0 - 204.0 * $j1) * $ub * $u1;
                $qd = $qd + 26599.0 * $ua * $u1 - 4687.0 * $uc * $u1 - 1870.0 * $ue * $u1 - 821.0 * $ug * $u1;
                $qd = $qd - 377.0 * $vh * $u1 + 497.0 * $up * $u1 + (163.0 - 611.0 * $j1) * $u2;
                $qd = $qd - 12696.0 * $u9 * $u2 - 4200.0 * $ub * $u2 - 1503.0 * $ud * $u2 - 619.0 * $uf * $u2;
                $qd = $qd - 268.0 * $un * $u2 - (282.0 + 1306.0 * $j1) * $ua * $u2;
                $qd = $qd + (-86.0 + 230.0 * $j1) * $uc * $u2 + 461.0 * $uo * $u2 - 350.0 * $u3;
                $qd = $qd + (2211.0 - 286.0 * $j1) * $u9 * $u3 - 2208.0 * $ub * $u3 - 568.0 * $ud * $u3;
                $qd = $qd - 346.0 * $uf * $u3 - (2780.0 + 222.0 * $j1) * $ua * $u3;
                $qd = $qd + (2022.0 + 263.0 * $j1) * $uc * $u3 + 248.0 * $ue * $u3 + 242.0 * $uq * $u3;
                $qd = $qd + 467.0 * $ur * $u3 - 490.0 * $u4 - (2842.0 + 279.0 * $j1) * $u9 * $u4;
                $qd = $qd + (128.0 + 226.0 * $j1) * $ub * $u4 + 224.0 * $ud * $u4;
                $qd = $qd + (-1594.0 + 282.0 * $j1) * $ua * $u4 + (2162.0 - 207.0 * $j1) * $uc * $u4;
                $qd = $qd + 561.0 * $ue * $u4 + 343.0 * $ug * $u4 + 469.0 * $uq * $u4 - 242.0 * $ur * $u4;
                $qd = $qd - 205.0 * $u9 * $ui + 262.0 * $ud * $ui + 208.0 * $ua * $uj - 271.0 * $ue * $uj;
                $qd = $qd - 382.0 * $ue * $uk - 376.0 * $ud * $ul;
                $qd *= 0.0000001;

                $vk = (0.077108 + (0.007186 - 0.001533 * $j1) * $j1) * $u5;
                $vk -= 0.007075 * $u9;
                $vk += (0.045803 - (0.014766 + 0.000536 * $j1) * $j1) * $u6;
                $vk = $vk - 0.072586 * $u2 - 0.075825 * $u9 * $u1 - 0.024839 * $ub * $u1;
                $vk = $vk - 0.008631 * $ud * $u1 - 0.150383 * $ua * $u2;
                $vk = $vk + 0.026897 * $uc * $u2 + 0.010053 * $ue * $u2;
                $vk = $vk - (0.013597 + 0.001719 * $j1) * $u9 * $u3 + 0.011981 * $ub * $u4;
                $vk -= (0.007742 - 0.001517 * $j1) * $ua * $u3;
                $vk += (0.013586 - 0.001375 * $j1) * $uc * $u3;
                $vk -= (0.013667 - 0.001239 * $j1) * $u9 * $u4;
                $vk += (0.014861 + 0.001136 * $j1) * $ua * $u4;
                $vk -= (0.013064 + 0.001628 * $j1) * $uc * $u4;
                $qe = $qc - (deg2rad($vk) / $planet->value4);

                $qf = 572.0 * $u5 - 1590.0 * $ub * $u2 + 2933.0 * $u6 - 647.0 * $ud * $u2;
                $qf = $qf + 33629.0 * $ua - 344.0 * $uf * $u2 - 3081.0 * $uc + 2885.0 * $ua * $u2;
                $qf = $qf - 1423.0 * $ue + (2172.0 + 102.0 * $j1) * $uc * $u2 - 671.0 * $ug;
                $qf = $qf + 296.0 * $ue * $u2 - 320.0 * $vh - 267.0 * $ub * $u3 + 1098.0 * $u1;
                $qf = $qf - 778.0 * $ua * $u3 - 2812.0 * $u9 * $u1 + 495.0 * $uc * $u3 + 688.0 * $ub * $u1;
                $qf = $qf + 250.0 * $ue * $u3 - 393.0 * $ud * $u1 - 856.0 * $u9 * $u4 - 228.0 * $uf * $u1;
                $qf = $qf + 441.0 * $ub * $u4 + 2138.0 * $ua * $u1 + 296.0 * $uc * $u4 - 999.0 * $uc * $u1;
                $qf = $qf + 211.0 * $ue * $u4 - 642.0 * $ue * $u1 - 427.0 * $u9 * $ui - 325.0 * $ug * $u1;
                $qf = $qf + 398.0 * $ud * $ui - 890.0 * $u2 + 344.0 * $ua * $uj + 2206.0 * $u9 * $u2;
                $qf -= 427.0 * $ue * $uj;
                $qf *= 0.000001;

                $qg = 0.000747 * $ua * $u1 + 0.001069 * $ua * $u2 + 0.002108 * $ub * $u3;
                $qg = $qg + 0.001261 * $uc * $u3 + 0.001236 * $ub * $u4 - 0.002075 * $uc * $u4;
                $qg = deg2rad($qg);

                return array($qa, $qb, $qc, $qd, $qe, $qf, $qg);
            }

            $qc = (0.331364 - (0.010281 + 0.004692 * $j1) * $j1) * $u5;
            $qc += (0.003228 - (0.064436 - 0.002075 * $j1) * $j1) * $u6;
            $qc -= (0.003083 + (0.000275 - 0.000489 * $j1) * $j1) * $u7;
            $qc = $qc + 0.002472 * $u8a + 0.013619 * $u9 + 0.018472 * $ub;
            $qc = $qc + 0.006717 * $ud + 0.002775 * $uf + 0.006417 * $ub * $u1;
            $qc = $qc + (0.007275 - 0.001253 * $j1) * $u9 * $u1 + 0.002439 * $ud * $u1;
            $qc = $qc - (0.035681 + 0.001208 * $j1) * $u9 * $u2 - 0.003767 * $uc * $u1;
            $qc = $qc - (0.033839 + 0.001125 * $j1) * $ua * $u1 - 0.004261 * $ub * $u2;
            $qc = $qc + (0.001161 * $j1 - 0.006333) * $ua * $u2 + 0.002178 * $u2;
            $qc = $qc - 0.006675 * $uc * $u2 - 0.002664 * $ue * $u2 - 0.002572 * $u9 * $u3;
            $qc = $qc - 0.003567 * $ub * $u3 + 0.002094 * $ua * $u4 + 0.003342 * $uc * $u4;
            $qc = deg2rad($qc);

            $qd = (3606.0 + (130.0 - 43.0 * $j1) * $j1) * $u5 + (1289.0 - 580.0 * $j1) * $u6;
            $qd = $qd - 6764.0 * $u9 * $u1 - 1110.0 * $ub * $u1 - 224.0 * $ud * $u1 - 204.0 * $u1;
            $qd = $qd + (1284.0 + 116.0 * $j1) * $ua * $u1 + 188.0 * $uc * $u1;
            $qd = $qd + (1460.0 + 130.0 * $j1) * $u9 * $u2 + 224.0 * $ub * $u2 - 817.0 * $u2;
            $qd = $qd + 6074.0 * $u2 * $ua + 992.0 * $uc * $u2 + 508.0 * $ue * $u2 + 230.0 * $ug * $u2;
            $qd = $qd + 108.0 * $vh * $u2 - (956.0 + 73.0 * $j1) * $u9 * $u3 + 448.0 * $ub * $u3;
            $qd = $qd + 137.0 * $ud * $u3 + (108.0 * $j1 - 997.0) * $ua * $u3 + 480.0 * $uc * $u3;
            $qd = $qd + 148.0 * $ue * $u3 + (99.0 * $j1 - 956.0) * $u9 * $u4 + 490.0 * $ub * $u4;
            $qd = $qd + 158.0 * $ud * $u4 + 179.0 * $u4 + (1024.0 + 75.0 * $j1) * $ua * $u4;
            $qd = $qd - 437.0 * $uc * $u4 - 132.0 * $ue * $u4;
            $qd *= 0.0000001;

            $vk = (0.007192 - 0.003147 * $j1) * $u5 - 0.004344 * $u1;
            $vk += ($j1 * (0.000197 * $j1 - 0.000675) - 0.020428) * $u6;
            $vk = $vk + 0.034036 * $ua * $u1 + (0.007269 + 0.000672 * $j1) * $u9 * $u1;
            $vk = $vk + 0.005614 * $uc * $u1 + 0.002964 * $ue * $u1 + 0.037761 * $u9 * $u2;
            $vk = $vk + 0.006158 * $ub * $u2 - 0.006603 * $ua * $u2 - 0.005356 * $u9 * $u3;
            $vk = $vk + 0.002722 * $ub * $u3 + 0.004483 * $ua * $u3;
            $vk = $vk - 0.002642 * $uc * $u3 + 0.004403 * $u9 * $u4;
            $vk = $vk - 0.002536 * $ub * $u4 + 0.005547 * $ua * $u4 - 0.002689 * $uc * $u4;
            $qe = $qc - (deg2rad($vk) / $planet->value4);

            $qf = 205.0 * $ua - 263.0 * $u6 + 693.0 * $uc + 312.0 * $ue + 147.0 * $ug + 299.0 * $u9 * $u1;
            $qf = $qf + 181.0 * $uc * $u1 + 204.0 * $ub * $u2 + 111.0 * $ud * $u2 - 337.0 * $ua * $u2;
            $qf -= 111.0 * $uc * $u2;
            $qf *= 0.000001;

            return array($qa, $qb, $qc, $qd, $qe, $qf, $qg);
        }

        if (in_array($planet->name, ["Uranus", "Neptune"])) {
            $j8 = unwind(1.46205 + 3.81337 * $t);
            $j9 = 2.0 * $j8 - $j4;
            $vj = sin($j9);
            $uu = sin($j9);
            $uv = sin(2.0 * $j9);
            $uw = cos(2.0 * $j9);

            if ($planet->name == "Neptune") {
                $ja = $j8 - $j2;
                $jb = $j8 - $j3;
                $jc = $j8 - $j4;
                $qc = (0.001089 * $j1 - 0.589833) * $vj;
                $qc = $qc + (0.004658 * $j1 - 0.056094) * $uu - 0.024286 * $uv;
                $qc = deg2rad($qc);

                $vk = 0.024039 * $vj - 0.025303 * $uu + 0.006206 * $uv;
                $vk -= 0.005992 * $uw;
                $qe = $qc - (deg2rad($vk) / $planet->value4);

                $qd = 4389.0 * $vj + 1129.0 * $uv + 4262.0 * $uu + 1089.0 * $uw;
                $qd *= 0.0000001;

                $qf = 8189.0 * $uu - 817.0 * $vj + 781.0 * $uw;
                $qf *= 0.000001;

                $vd = sin(2.0 * $jc);
                $ve = cos(2.0 * $jc);
                $vf = sin($j8);
                $vg = cos($j8);
                $qa = -0.009556 * sin($ja) - 0.005178 * sin($jb);
                $qa = $qa + 0.002572 * $vd - 0.002972 * $ve * $vf - 0.002833 * $vd * $vg;

                $qg = 0.000336 * $ve * $vf + 0.000364 * $vd * $vg;
                $qg = deg2rad($qg);

                $qb = -40596.0 + 4992.0 * cos($ja) + 2744.0 * cos($jb);
                $qb = $qb + 2044.0 * cos($jc) + 1051.0 * $ve;
                $qb *= 0.000001;

                return array($qa, $qb, $qc, $qd, $qe, $qf, $qg);
            }

            $ja = $j4 - $j2;
            $jb = $j4 - $j3;
            $jc = $j8 - $j4;
            $qc = (0.864319 - 0.001583 * $j1) * $vj;
            $qc = $qc + (0.082222 - 0.006833 * $j1) * $uu + 0.036017 * $uv;
            $qc = $qc - 0.003019 * $uw + 0.008122 * sin($j6);
            $qc = deg2rad($qc);

            $vk = 0.120303 * $vj + 0.006197 * $uv;
            $vk += (0.019472 - 0.000947 * $j1) * $uu;
            $qe = $qc - (deg2rad($vk) / $planet->value4);

            $qd = (163.0 * $j1 - 3349.0) * $vj + 20981.0 * $uu + 1311.0 * $uw;
            $qd *= 0.0000001;

            $qf = -0.003825 * $uu;

            $qa = (-0.038581 + (0.002031 - 0.00191 * $j1) * $j1) * cos($j4 + $jb);
            $qa += (0.010122 - 0.000988 * $j1) * sin($j4 + $jb);
            $a = (0.034964 - (0.001038 - 0.000868 * $j1) * $j1) * cos(2.0 * $j4 + $jb);
            $qa = $a + $qa + 0.005594 * sin($j4 + 3.0 * $jc) - 0.014808 * sin($ja);
            $qa = $qa - 0.005794 * sin($jb) + 0.002347 * cos($jb);
            $qa = $qa + 0.009872 * sin($jc) + 0.008803 * sin(2.0 * $jc);
            $qa -= 0.004308 * sin(3.0 * $jc);

            $ux = sin($jb);
            $uy = cos($jb);
            $uz = sin($j4);
            $va = cos($j4);
            $vb = sin(2.0 * $j4);
            $vc = cos(2.0 * $j4);
            $qg = (0.000458 * $ux - 0.000642 * $uy - 0.000517 * cos(4.0 * $jc)) * $uz;
            $qg -= (0.000347 * $ux + 0.000853 * $uy + 0.000517 * sin(4.0 * $jb)) * $va;
            $qg += 0.000403 * (cos(2.0 * $jc) * $vb + sin(2.0 * $jc) * $vc);
            $qg = deg2rad($qg);

            $qb = -25948.0 + 4985.0 * cos($ja) - 1230.0 * $va + 3354.0 * $uy;
            $qb = $qb + 904.0 * cos(2.0 * $jc) + 894.0 * (cos($jc) - cos(3.0 * $jc));
            $qb += (5795.0 * $va - 1165.0 * $uz + 1388.0 * $vc) * $ux;
            $qb += (1351.0 * $va + 5702.0 * $uz + 1388.0 * $vb) * $uy;
            $qb *= 0.000001;

            return array($qa, $qb, $qc, $qd, $qe, $qf, $qg);
        }

        return array($qa, $qb, $qc, $qd, $qe, $qf, $qg);
    }

    /**
     * For W, in radians, return S, also in radians.
     * 
     * Original macro name: SolveCubic
     */
    function solve_cubic($w)
    {
        $s = $w / 3.0;

        while (true) {
            $s2 = $s * $s;
            $d = ($s2 + 3.0) * $s - $w;

            if (abs($d) < 0.000001) {
                return $s;
            }

            $s = ((2.0 * $s * $s2) + $w) / (3.0 * ($s2 + 1.0));
        }
    }

    /**
     * Calculate longitude, latitude, and distance of parabolic-orbit comet.
     *
     * Original macro names: PcometLong, PcometLat, PcometDist
     */
    function p_comet_long_lat_dist($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr, $td, $tm, $ty, $q, $i, $p, $n)
    {
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $ut = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $tpe = ($ut / 365.242191) + civil_date_to_julian_date($gd, $gm, $gy) - civil_date_to_julian_date($td, $tm, $ty);
        $lg = deg2rad(sun_long($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr) + 180.0);
        $re = sun_dist($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);

        $rh2 = 0.0;
        $rd = 0.0;
        $s3 = 0.0;
        $c3 = 0.0;
        $lc = 0.0;
        $s2 = 0.0;
        $c2 = 0.0;

        for ($k = 1; $k < 3; $k++) {
            $s = solve_cubic(0.0364911624 * $tpe / ($q * sqrt($q)));
            $nu = 2.0 * atan($s);
            $r = $q * (1.0 + $s * $s);
            $l = $nu + deg2rad($p);
            $s1 = sin($l);
            $c1 = cos($l);
            $i1 = deg2rad($i);
            $s2 = $s1 * sin($i1);
            $ps = asin($s2);
            $y = $s1 * cos($i1);
            $lc = atan2($y, $c1) + deg2rad($n);
            $c2 = cos($ps);
            $rd = $r * $c2;
            $ll = $lc - $lg;
            $c3 = cos($ll);
            $s3 = sin($ll);
            $rh = sqrt(($re * $re) + ($r * $r) - (2.0 * $re * $rd * $c3 * cos($ps)));
            if ($k == 1) {
                $rh2 = sqrt(($re * $re) + ($r * $r) - (2.0 * $re * $r * cos($ps) * cos($l + deg2rad($n) - $lg)));
            }
        }

        $ep = ($rd < $re)
            ? atan(-$rd * $s3 / ($re - ($rd * $c3))) + $lg + 3.141592654
            : atan($re * $s3 / ($rd - ($re * $c3))) + $lc;
        $ep = unwind($ep);

        $tb = $rd * $s2 * sin($ep - $lc) / ($c2 * $re * $s3);
        $bp = atan($tb);

        $cometLongDeg = w_to_degrees($ep);
        $cometLatDeg = w_to_degrees($bp);
        $cometDistAU = $rh2;

        return array($cometLongDeg, $cometLatDeg, $cometDistAU);
    }

    /**
     * Calculate longitude, latitude, and horizontal parallax of the Moon.
     * 
     * Original macro names: MoonLong, MoonLat, MoonHP
     */
    function moon_long_lat_hp($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $ut = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $t = ((civil_date_to_julian_date($gd, $gm, $gy) - 2415020.0) / 36525.0) + ($ut / 876600.0);
        $t2 = $t * $t;

        $m1 = 27.32158213;
        $m2 = 365.2596407;
        $m3 = 27.55455094;
        $m4 = 29.53058868;
        $m5 = 27.21222039;
        $m6 = 6798.363307;
        $q = civil_date_to_julian_date($gd, $gm, $gy) - 2415020.0 + ($ut / 24.0);
        $m1 = $q / $m1;
        $m2 = $q / $m2;
        $m3 = $q / $m3;
        $m4 = $q / $m4;
        $m5 = $q / $m5;
        $m6 = $q / $m6;
        $m1 = 360.0 * ($m1 - floor($m1));
        $m2 = 360.0 * ($m2 - floor($m2));
        $m3 = 360.0 * ($m3 - floor($m3));
        $m4 = 360.0 * ($m4 - floor($m4));
        $m5 = 360.0 * ($m5 - floor($m5));
        $m6 = 360.0 * ($m6 - floor($m6));

        $ml = 270.434164 + $m1 - (0.001133 - 0.0000019 * $t) * $t2;
        $ms = 358.475833 + $m2 - (0.00015 + 0.0000033 * $t) * $t2;
        $md = 296.104608 + $m3 + (0.009192 + 0.0000144 * $t) * $t2;
        $me1 = 350.737486 + $m4 - (0.001436 - 0.0000019 * $t) * $t2;
        $mf = 11.250889 + $m5 - (0.003211 + 0.0000003 * $t) * $t2;
        $na = 259.183275 - $m6 + (0.002078 + 0.0000022 * $t) * $t2;
        $a = deg2rad(51.2 + 20.2 * $t);
        $s1 = sin($a);
        $s2 = sin(deg2rad($na));
        $b = 346.56 + (132.87 - 0.0091731 * $t) * $t;
        $s3 = 0.003964 * sin(deg2rad($b));
        $c = deg2rad($na + 275.05 - 2.3 * $t);
        $s4 = sin($c);
        $ml = $ml + 0.000233 * $s1 + $s3 + 0.001964 * $s2;
        $ms -= 0.001778 * $s1;
        $md = $md + 0.000817 * $s1 + $s3 + 0.002541 * $s2;
        $mf = $mf + $s3 - 0.024691 * $s2 - 0.004328 * $s4;
        $me1 = $me1 + 0.002011 * $s1 + $s3 + 0.001964 * $s2;
        $e = 1.0 - (0.002495 + 0.00000752 * $t) * $t;
        $e2 = $e * $e;
        $ml = deg2rad($ml);
        $ms = deg2rad($ms);
        $na = deg2rad($na);
        $me1 = deg2rad($me1);
        $mf = deg2rad($mf);
        $md = deg2rad($md);

        // Longitude-specific
        $l = 6.28875 * sin($md) + 1.274018 * sin(2.0 * $me1 - $md);
        $l = $l + 0.658309 * sin(2.0 * $me1) + 0.213616 * sin(2.0 * $md);
        $l = $l - $e * 0.185596 * sin($ms) - 0.114336 * sin(2.0 * $mf);
        $l += 0.058793 * sin(2.0 * ($me1 - $md));
        $l = $l + 0.057212 * $e * sin(2.0 * $me1 - $ms - $md) + 0.05332 * sin(2.0 * $me1 + $md);
        $l = $l + 0.045874 * $e * sin(2.0 * $me1 - $ms) + 0.041024 * $e * sin($md - $ms);
        $l = $l - 0.034718 * sin($me1) - $e * 0.030465 * sin($ms + $md);
        $l = $l + 0.015326 * sin(2.0 * ($me1 - $mf)) - 0.012528 * sin(2.0 * $mf + $md);
        $l = $l - 0.01098 * sin(2.0 * $mf - $md) + 0.010674 * sin(4.0 * $me1 - $md);
        $l = $l + 0.010034 * sin(3.0 * $md) + 0.008548 * sin(4.0 * $me1 - 2.0 * $md);
        $l = $l - $e * 0.00791 * sin($ms - $md + 2.0 * $me1) - $e * 0.006783 * sin(2.0 * $me1 + $ms);
        $l = $l + 0.005162 * sin($md - $me1) + $e * 0.005 * sin($ms + $me1);
        $l = $l + 0.003862 * sin(4.0 * $me1) + $e * 0.004049 * sin($md - $ms + 2.0 * $me1);
        $l = $l + 0.003996 * sin(2.0 * ($md + $me1)) + 0.003665 * sin(2.0 * $me1 - 3.0 * $md);
        $l = $l + $e * 0.002695 * sin(2.0 * $md - $ms) + 0.002602 * sin($md - 2.0 * ($mf + $me1));
        $l = $l + $e * 0.002396 * sin(2.0 * ($me1 - $md) - $ms) - 0.002349 * sin($md + $me1);
        $l = $l + $e2 * 0.002249 * sin(2.0 * ($me1 - $ms)) - $e * 0.002125 * sin(2.0 * $md + $ms);
        $l = $l - $e2 * 0.002079 * sin(2.0 * $ms) + $e2 * 0.002059 * sin(2.0 * ($me1 - $ms) - $md);
        $l = $l - 0.001773 * sin($md + 2.0 * ($me1 - $mf)) - 0.001595 * sin(2.0 * ($mf + $me1));
        $l = $l + $e * 0.00122 * sin(4.0 * $me1 - $ms - $md) - 0.00111 * sin(2.0 * ($md + $mf));
        $l = $l + 0.000892 * sin($md - 3.0 * $me1) - $e * 0.000811 * sin($ms + $md + 2.0 * $me1);
        $l += $e * 0.000761 * sin(4.0 * $me1 - $ms - 2.0 * $md);
        $l += $e2 * 0.000704 * sin($md - 2.0 * ($ms + $me1));
        $l += $e * 0.000693 * sin($ms - 2.0 * ($md - $me1));
        $l += $e * 0.000598 * sin(2.0 * ($me1 - $mf) - $ms);
        $l = $l + 0.00055 * sin($md + 4.0 * $me1) + 0.000538 * sin(4.0 * $md);
        $l = $l + $e * 0.000521 * sin(4.0 * $me1 - $ms) + 0.000486 * sin(2.0 * $md - $me1);
        $l += $e2 * 0.000717 * sin($md - 2.0 * $ms);
        $mm = unwind($ml + deg2rad($l));

        // Latitude-specific
        $g = 5.128189 * sin($mf) + 0.280606 * sin($md + $mf);
        $g = $g + 0.277693 * sin($md - $mf) + 0.173238 * sin(2.0 * $me1 - $mf);
        $g = $g + 0.055413 * sin(2.0 * $me1 + $mf - $md) + 0.046272 * sin(2.0 * $me1 - $mf - $md);
        $g = $g + 0.032573 * sin(2.0 * $me1 + $mf) + 0.017198 * sin(2.0 * $md + $mf);
        $g = $g + 0.009267 * sin(2.0 * $me1 + $md - $mf) + 0.008823 * sin(2.0 * $md - $mf);
        $g = $g + $e * 0.008247 * sin(2.0 * $me1 - $ms - $mf) + 0.004323 * sin(2.0 * ($me1 - $md) - $mf);
        $g = $g + 0.0042 * sin(2.0 * $me1 + $mf + $md) + $e * 0.003372 * sin($mf - $ms - 2.0 * $me1);
        $g += $e * 0.002472 * sin(2.0 * $me1 + $mf - $ms - $md);
        $g += $e * 0.002222 * sin(2.0 * $me1 + $mf - $ms);
        $g += $e * 0.002072 * sin(2.0 * $me1 - $mf - $ms - $md);
        $g = $g + $e * 0.001877 * sin($mf - $ms + $md) + 0.001828 * sin(4.0 * $me1 - $mf - $md);
        $g = $g - $e * 0.001803 * sin($mf + $ms) - 0.00175 * sin(3.0 * $mf);
        $g = $g + $e * 0.00157 * sin($md - $ms - $mf) - 0.001487 * sin($mf + $me1);
        $g = $g - $e * 0.001481 * sin($mf + $ms + $md) + $e * 0.001417 * sin($mf - $ms - $md);
        $g = $g + $e * 0.00135 * sin($mf - $ms) + 0.00133 * sin($mf - $me1);
        $g = $g + 0.001106 * sin($mf + 3.0 * $md) + 0.00102 * sin(4.0 * $me1 - $mf);
        $g = $g + 0.000833 * sin($mf + 4.0 * $me1 - $md) + 0.000781 * sin($md - 3.0 * $mf);
        $g = $g + 0.00067 * sin($mf + 4.0 * $me1 - 2.0 * $md) + 0.000606 * sin(2.0 * $me1 - 3.0 * $mf);
        $g += 0.000597 * sin(2.0 * ($me1 + $md) - $mf);
        $g = $g + $e * 0.000492 * sin(2.0 * $me1 + $md - $ms - $mf) + 0.00045 * sin(2.0 * ($md - $me1) - $mf);
        $g = $g + 0.000439 * sin(3.0 * $md - $mf) + 0.000423 * sin($mf + 2.0 * ($me1 + $md));
        $g = $g + 0.000422 * sin(2.0 * $me1 - $mf - 3.0 * $md) - $e * 0.000367 * sin($ms + $mf + 2.0 * $me1 - $md);
        $g = $g - $e * 0.000353 * sin($ms + $mf + 2.0 * $me1) + 0.000331 * sin($mf + 4.0 * $me1);
        $g += $e * 0.000317 * sin(2.0 * $me1 + $mf - $ms + $md);
        $g = $g + $e2 * 0.000306 * sin(2.0 * ($me1 - $ms) - $mf) - 0.000283 * sin($md + 3.0 * $mf);
        $w1 = 0.0004664 * cos($na);
        $w2 = 0.0000754 * cos($c);
        $bm = deg2rad($g) * (1.0 - $w1 - $w2);

        // Horizontal parallax-specific
        $pm = 0.950724 + 0.051818 * cos($md) + 0.009531 * cos(2.0 * $me1 - $md);
        $pm = $pm + 0.007843 * cos(2.0 * $me1) + 0.002824 * cos(2.0 * $md);
        $pm = $pm + 0.000857 * cos(2.0 * $me1 + $md) + $e * 0.000533 * cos(2.0 * $me1 - $ms);
        $pm += $e * 0.000401 * cos(2.0 * $me1 - $md - $ms);
        $pm = $pm + $e * 0.00032 * cos($md - $ms) - 0.000271 * cos($me1);
        $pm = $pm - $e * 0.000264 * cos($ms + $md) - 0.000198 * cos(2.0 * $mf - $md);
        $pm = $pm + 0.000173 * cos(3.0 * $md) + 0.000167 * cos(4.0 * $me1 - $md);
        $pm = $pm - $e * 0.000111 * cos($ms) + 0.000103 * cos(4.0 * $me1 - 2.0 * $md);
        $pm = $pm - 0.000084 * cos(2.0 * $md - 2.0 * $me1) - $e * 0.000083 * cos(2.0 * $me1 + $ms);
        $pm = $pm + 0.000079 * cos(2.0 * $me1 + 2.0 * $md) + 0.000072 * cos(4.0 * $me1);
        $pm = $pm + $e * 0.000064 * cos(2.0 * $me1 - $ms + $md) - $e * 0.000063 * cos(2.0 * $me1 + $ms - $md);
        $pm = $pm + $e * 0.000041 * cos($ms + $me1) + $e * 0.000035 * cos(2.0 * $md - $ms);
        $pm = $pm - 0.000033 * cos(3.0 * $md - 2.0 * $me1) - 0.00003 * cos($md + $me1);
        $pm = $pm - 0.000029 * cos(2.0 * ($mf - $me1)) - $e * 0.000029 * cos(2.0 * $md + $ms);
        $pm = $pm + $e2 * 0.000026 * cos(2.0 * ($me1 - $ms)) - 0.000023 * cos(2.0 * ($mf - $me1) + $md);
        $pm += $e * 0.000019 * cos(4.0 * $me1 - $ms - $md);

        $moonLongDeg = w_to_degrees($mm);
        $moonLatDeg = w_to_degrees($bm);
        $moonHorPara = $pm;

        return array($moonLongDeg, $moonLatDeg, $moonHorPara);
    }

    /**
     * Calculate current phase of Moon.
     * 
     * Original macro name: MoonPhase
     */
    function moon_phase_ma($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        list($moonLongDeg, $moonLatDeg, $moonHorPara) = moon_long_lat_hp($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);

        $cd = cos(deg2rad(($moonLongDeg - sun_long($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)))) * cos(deg2rad($moonLatDeg));

        $d = acos($cd);
        $sd = sin($d);
        $i = 0.1468 * $sd * (1.0 - 0.0549 * sin(moon_mean_anomaly($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)));
        $i /= (1.0 - 0.0167 * sin(sun_mean_anomaly($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)));
        $i = 3.141592654 - $d - deg2rad($i);
        $k = (1.0 + cos($i)) / 2.0;

        return round($k, 2);
    }

    /**
     * Calculate the Moon's mean anomaly.
     * 
     * Original macro name: MoonMeanAnomaly
     */
    function moon_mean_anomaly($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr)
    {
        $ut = local_civil_time_to_universal_time($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gd = local_civil_time_greenwich_day($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gm = local_civil_time_greenwich_month($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $gy = local_civil_time_greenwich_year($lh, $lm, $ls, $ds, $zc, $dy, $mn, $yr);
        $t = ((civil_date_to_julian_date($gd, $gm, $gy) - 2415020.0) / 36525.0) + ($ut / 876600.0);
        $t2 = $t * $t;

        $m1 = 27.32158213;
        $m2 = 365.2596407;
        $m3 = 27.55455094;
        $m4 = 29.53058868;
        $m5 = 27.21222039;
        $m6 = 6798.363307;
        $q = civil_date_to_julian_date($gd, $gm, $gy) - 2415020.0 + ($ut / 24.0);
        $m1 = $q / $m1;
        $m2 = $q / $m2;
        $m3 = $q / $m3;
        $m4 = $q / $m4;
        $m5 = $q / $m5;
        $m6 = $q / $m6;
        $m1 = 360.0 * ($m1 - floor($m1));
        $m2 = 360.0 * ($m2 - floor($m2));
        $m3 = 360.0 * ($m3 - floor($m3));
        $m4 = 360.0 * ($m4 - floor($m4));
        $m5 = 360.0 * ($m5 - floor($m5));
        $m6 = 360.0 * ($m6 - floor($m6));

        $ml = 270.434164 + $m1 - (0.001133 - 0.0000019 * $t) * $t2;
        $ms = 358.475833 + $m2 - (0.00015 + 0.0000033 * $t) * $t2;
        $md = 296.104608 + $m3 + (0.009192 + 0.0000144 * $t) * $t2;
        $na = 259.183275 - $m6 + (0.002078 + 0.0000022 * $t) * $t2;
        $a = deg2rad(51.2 + 20.2 * $t);
        $s1 = sin($a);
        $s2 = sin(deg2rad($na));
        $b = 346.56 + (132.87 - 0.0091731 * $t) * $t;
        $s3 = 0.003964 * sin(deg2rad($b));
        $c = deg2rad($na + 275.05 - 2.3 * $t);
        $md = $md + 0.000817 * $s1 + $s3 + 0.002541 * $s2;

        return deg2rad($md);
    }

    /**
     * Calculate Julian date of New Moon.
     * 
     * Original macro name: NewMoon
     */
    function new_moon($ds, $zc, $dy, $mn, $yr)
    {
        $d0 = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $m0 = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $y0 = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);

        $j0 = civil_date_to_julian_date(0.0, 1, $y0) - 2415020.0;
        $dj = civil_date_to_julian_date($d0, $m0, $y0) - 2415020.0;
        $k = lint((($y0 - 1900.0 + (($dj - $j0) / 365.0)) * 12.3685) + 0.5);
        $tn = $k / 1236.85;
        $tf = ($k + 0.5) / 1236.85;
        $t = $tn;
        list($nmfmResult1_a, $nmfmResult1_b, $nmfmResult1_f) = new_moon_full_moon_l6855($k, $t);
        $ni = $nmfmResult1_a;
        $nf = $nmfmResult1_b;
        $t = $tf;
        $k += 0.5;
        list($nmfmResult2_a, $nmfmResult2_b, $nmfmResult2_f) = new_moon_full_moon_l6855($k, $t);

        return $ni + 2415020.0 + $nf;
    }

    /**
     * Calculate Julian date of Full Moon.
     * 
     * Original macro name: FullMoon
     */
    function full_moon($ds, $zc, $dy, $mn, $yr)
    {
        $d0 = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $m0 = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $y0 = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);

        $j0 = civil_date_to_julian_date(0.0, 1, $y0) - 2415020.0;
        $dj = civil_date_to_julian_date($d0, $m0, $y0) - 2415020.0;
        $k = lint((($y0 - 1900.0 + (($dj - $j0) / 365.0)) * 12.3685) + 0.5);
        $tn = $k / 1236.85;
        $tf = ($k + 0.5) / 1236.85;
        $t = $tn;
        list($nmfmResult1_a, $nmfmResult1_b, $nmfmResult1_f) = new_moon_full_moon_l6855($k, $t);
        $t = $tf;
        $k += 0.5;
        list($nmfmResult2_a, $nmfmResult2_b, $nmfmResult2_f) = new_moon_full_moon_l6855($k, $t);
        $fi = $nmfmResult2_a;
        $ff = $nmfmResult2_b;

        return $fi + 2415020.0 + $ff;
    }

    /** Helper function for newMoon() and fullMoon() */
    function new_moon_full_moon_l6855($k, $t)
    {
        $t2 = $t * $t;
        $e = 29.53 * $k;
        $c = 166.56 + (132.87 - 0.009173 * $t) * $t;
        $c = deg2rad($c);
        $b = 0.00058868 * $k + (0.0001178 - 0.000000155 * $t) * $t2;
        $b = $b + 0.00033 * sin($c) + 0.75933;
        $a = $k / 12.36886;
        $a1 = 359.2242 + 360.0 * fract($a) - (0.0000333 + 0.00000347 * $t) * $t2;
        $a2 = 306.0253 + 360.0 * fract($k / 0.9330851);
        $a2 += (0.0107306 + 0.00001236 * $t) * $t2;
        $a = $k / 0.9214926;
        $f = 21.2964 + 360.0 * fract($a) - (0.0016528 + 0.00000239 * $t) * $t2;
        $a1 = unwind_deg($a1);
        $a2 = unwind_deg($a2);
        $f = unwind_deg($f);
        $a1 = deg2rad($a1);
        $a2 = deg2rad($a2);
        $f = deg2rad($f);

        $dd = (0.1734 - 0.000393 * $t) * sin($a1) + 0.0021 * sin(2.0 * $a1);
        $dd = $dd - 0.4068 * sin($a2) + 0.0161 * sin(2.0 * $a2) - 0.0004 * sin(3.0 * $a2);
        $dd = $dd + 0.0104 * sin(2.0 * $f) - 0.0051 * sin($a1 + $a2);
        $dd = $dd - 0.0074 * sin($a1 - $a2) + 0.0004 * sin(2.0 * $f + $a1);
        $dd = $dd - 0.0004 * sin(2.0 * $f - $a1) - 0.0006 * sin(2.0 * $f + $a2) + 0.001 * sin(2.0 * $f - $a2);
        $dd += 0.0005 * sin($a1 + 2.0 * $a2);
        $e1 = floor($e);
        $b = $b + $dd + ($e - $e1);
        $b1 = floor($b);
        $a = $e1 + $b1;
        $b -= $b1;

        return array($a, $b, $f);
    }

    /** Original macro name: FRACT */
    function fract($w)
    {
        return $w - lint($w);
    }

    /** Original macro name: LINT */
    function lint($w)
    {
        return i_int($w) + i_int(((1.0 * sign($w)) - 1.0) / 2.0);
    }

    /** Original macro name: IINT */
    function i_int($w)
    {
        return sign($w) * floor(abs($w));
    }

    /** Calculate sign of number. */
    function sign($numberToCheck)
    {
        $signValue = 0.0;

        if ($numberToCheck < 0.0)
            $signValue = -1.0;

        if ($numberToCheck > 0.0)
            $signValue = 1.0;

        return $signValue;
    }

    /** Original macro name: UTDayAdjust */
    function ut_day_adjust($ut, $g1)
    {
        $returnValue = $ut;

        if (($ut - $g1) < -6.0)
            $returnValue = $ut + 24.0;

        if (($ut - $g1) > 6.0)
            $returnValue = $ut - 24.0;

        return $returnValue;
    }

    /** Original macro name: Fpart */
    function f_part($w)
    {
        return $w - lint($w);
    }

    /** Original macro name: EQElat */
    function eq_e_lat($rah, $ram, $ras, $dd, $dm, $ds, $gd, $gm, $gy)
    {
        $a = deg2rad(degree_hours_to_decimal_degrees(hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras)));
        $b = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $c = deg2rad(obliq($gd, $gm, $gy));
        $d = sin($b) * cos($c) - cos($b) * sin($c) * sin($a);

        return w_to_degrees(asin($d));
    }

    /** Original macro name: EQElong */
    function eq_e_long($rah, $ram, $ras, $dd, $dm, $ds, $gd, $gm, $gy)
    {
        $a = deg2rad(degree_hours_to_decimal_degrees(hours_minutes_seconds_to_decimal_hours($rah, $ram, $ras)));
        $b = deg2rad(degrees_minutes_seconds_to_decimal_degrees($dd, $dm, $ds));
        $c = deg2rad(obliq($gd, $gm, $gy));
        $d = sin($a) * cos($c) + tan($b) * sin($c);
        $e = cos($a);
        $f = w_to_degrees(atan2($d, $e));

        return $f - 360.0 * floor($f / 360.0);
    }

    /**
     * Local time of moonrise.
     * 
     * Original macro name: MoonRiseLCT
     */
    function moon_rise_lct($dy, $mn, $yr, $ds, $zc, $gLong, $gLat)
    {
        $gdy = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gmn = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gyr = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $lct = 12.0;
        $dy1 = $dy;
        $mn1 = $mn;
        $yr1 = $yr;

        list($lct6700result1_mm, $lct6700result1_bm, $lct6700result1_pm, $lct6700result1_dp, $lct6700result1_th, $lct6700result1_di, $lct6700result1_p, $lct6700result1_q, $lct6700result1_lu, $lct6700result1_lct) =
            moon_rise_lct_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
        $lu = $lct6700result1_lu;
        $lct = $lct6700result1_lct;

        if ($lct == -99.0)
            return $lct;

        $la = $lu;

        $g1 = 0.0;
        $gu = 0.0;

        for ($k = 1; $k < 9; $k++) {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

            $g1 = ($k == 1) ? $ut : $gu;

            $gu = $ut;
            $ut = $gu;

            list($lct6680result_ut, $lct6680result_lct, $lct6680result_dy1, $lct6680result_mn1, $lct6680result_yr1, $lct6680result_gdy, $lct6680result_gmn, $lct6680result_gyr) =
                moon_rise_lct_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut);
            $lct = $lct6680result_lct;
            $dy1 = $lct6680result_dy1;
            $mn1 = $lct6680result_mn1;
            $yr1 = $lct6680result_yr1;
            $gdy = $lct6680result_gdy;
            $gmn = $lct6680result_gmn;
            $gyr = $lct6680result_gyr;

            list($lct6700result2_mm, $lct6700result2_bm, $lct6700result2_pm, $lct6700result2_dp, $lct6700result2_th, $lct6700result2_di, $lct6700result2_p, $lct6700result2_q, $lct6700result2_lu, $lct6700result2_lct) =
                moon_rise_lct_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
            $lu = $lct6700result2_lu;
            $lct = $lct6700result2_lct;

            if ($lct == -99.0)
                return $lct;

            $la = $lu;
        }

        $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);

        return $lct;
    }

    /** Helper function for moon_rise_lct */
    function moon_rise_lct_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut)
    {
        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $gdy = local_civil_time_greenwich_day($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gmn = local_civil_time_greenwich_month($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gyr = local_civil_time_greenwich_year($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $ut -= 24.0 * floor($ut / 24.0);

        return array($ut, $lct, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr);
    }

    /** Helper function for moon_rise_lct */
    function moon_rise_lct_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat)
    {
        $mm = moon_long($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $bm = moon_lat($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $pm = deg2rad(moon_hp($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1));
        $dp = nutat_long($gdy, $gmn, $gyr);
        $th = 0.27249 * sin($pm);
        $di = $th + 0.0098902 - $pm;
        $p = decimal_degrees_to_degree_hours(ec_ra($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr));
        $q = ec_dec($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr);
        $lu = rise_set_local_sidereal_time_rise($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);

        if (ers($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat) != RiseSetStatus::OK)
            $lct = -99.0;

        return array($mm, $bm, $pm, $dp, $th, $di, $p, $q, $lu, $lct);
    }

    /**
     * Local date of moonrise.
     * 
     * Original macro names: MoonRiseLcDay, MoonRiseLcMonth, MoonRiseLcYear
     */
    function moon_rise_lc_dmy($dy, $mn, $yr, $ds, $zc, $gLong, $gLat)
    {
        $gdy = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gmn = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gyr = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $lct = 12.0;
        $dy1 = $dy;
        $mn1 = $mn;
        $yr1 = $yr;

        list($lct6700result1_mm, $lct6700result1_bm, $lct6700result1_pm, $lct6700result1_dp, $lct6700result1_th, $lct6700result1_di, $lct6700result1_p, $lct6700result1_q, $lct6700result1_lu, $lct6700result1_lct) =
            moon_rise_lc_dmy_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
        $lu = $lct6700result1_lu;
        $lct = $lct6700result1_lct;

        if ($lct == -99.0)
            return array($lct, (int) $lct, (int) $lct);

        $la = $lu;

        $g1 = 0.0;
        $gu = 0.0;
        for ($k = 1; $k < 9; $k++) {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

            $g1 = ($k == 1) ? $ut : $gu;

            $gu = $ut;
            $ut = $gu;

            list($lct6680result1_ut, $lct6680result1_lct, $lct6680result1_dy1, $lct6680result1_mn1, $lct6680result1_yr1, $lct6680result1_gdy, $lct6680result1_gmn, $lct6680result1_gyr) =
                moon_rise_lc_dmy_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut);
            $lct = $lct6680result1_lct;
            $dy1 = $lct6680result1_dy1;
            $mn1 = $lct6680result1_mn1;
            $yr1 = $lct6680result1_yr1;
            $gdy = $lct6680result1_gdy;
            $gmn = $lct6680result1_gmn;
            $gyr = $lct6680result1_gyr;

            list($lct6700result2_mm, $lct6700result2_bm, $lct6700result2_pm, $lct6700result2_dp, $lct6700result2_th, $lct6700result2_di, $lct6700result2_p, $lct6700result2_q, $lct6700result2_lu, $lct6700result2_lct) =
                moon_rise_lc_dmy_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);

            $lu = $lct6700result2_lu;
            $lct = $lct6700result2_lct;

            if ($lct == -99.0)
                return array($lct, (int) $lct, (int) $lct);

            $la = $lu;
        }

        $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);

        return array($dy1, $mn1, $yr1);
    }

    /** Helper function for moon_rise_lc_dmy */
    function moon_rise_lc_dmy_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut)
    {
        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $gdy = local_civil_time_greenwich_day($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gmn = local_civil_time_greenwich_month($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gyr = local_civil_time_greenwich_year($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $ut -= 24.0 * floor($ut / 24.0);

        return array($ut, $lct, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr);
    }

    /** Helper function for moon_rise_lc_dmy */
    function moon_rise_lc_dmy_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat)
    {
        $mm = moon_long($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $bm = moon_lat($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $pm = deg2rad(moon_hp($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1));
        $dp = nutat_long($gdy, $gmn, $gyr);
        $th = 0.27249 * sin($pm);
        $di = $th + 0.0098902 - $pm;
        $p = decimal_degrees_to_degree_hours(ec_ra($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr));
        $q = ec_dec($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr);
        $lu = rise_set_local_sidereal_time_rise($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);

        return array($mm, $bm, $pm, $dp, $th, $di, $p, $q, $lu, $lct);
    }

    /**
     * Local azimuth of moonrise.
     * 
     * Original macro name: MoonRiseAz
     */
    function moon_rise_az($dy, $mn, $yr, $ds, $zc, $gLong, $gLat)
    {
        $gdy = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gmn = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gyr = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $lct = 12.0;
        $dy1 = $dy;
        $mn1 = $mn;
        $yr1 = $yr;

        list($az6700result1_mm, $az6700result1_bm, $az6700result1_pm, $az6700result1_dp, $az6700result1_th, $az6700result1_di, $az6700result1_p, $az6700result1_q, $az6700result1_lu, $az6700result1_lct, $az6700result1_au) =
            moon_rise_az_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
        $lu = $az6700result1_lu;
        $lct = $az6700result1_lct;

        if ($lct == -99.0)
            return $lct;

        $la = $lu;

        $gu = 0.0;
        $aa = 0.0;
        for ($k = 1; $k < 9; $k++) {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

            $g1 = ($k == 1) ? $ut : $gu;

            $gu = $ut;
            $ut = $gu;

            list($az6680result1_ut, $az6680result1_lct, $az6680result1_dy1, $az6680result1_mn1, $az6680result1_yr1, $az6680result1_gdy, $az6680result1_gmn, $az6680result1_gyr) =
                moon_rise_az_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut);
            $lct = $az6680result1_lct;
            $dy1 = $az6680result1_dy1;
            $mn1 = $az6680result1_mn1;
            $yr1 = $az6680result1_yr1;
            $gdy = $az6680result1_gdy;
            $gmn = $az6680result1_gmn;
            $gyr = $az6680result1_gyr;

            list($az6700result2_mm, $az6700result2_bm, $az6700result2_pm, $az6700result2_dp, $az6700result2_th, $az6700result2_di, $az6700result2_p, $az6700result2_q, $az6700result2_lu, $az6700result2_lct, $az6700result2_au) =
                moon_rise_az_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
            $lu = $az6700result2_lu;
            $lct = $az6700result2_lct;
            $au = $az6700result2_au;

            if ($lct == -99.0)
                return $lct;

            $la = $lu;
            $aa = $au;
        }

        $au = $aa;

        return $au;
    }

    /** Helper function for moon_rise_az */
    function moon_rise_az_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut)
    {
        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $gdy = local_civil_time_greenwich_day($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gmn = local_civil_time_greenwich_month($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gyr = local_civil_time_greenwich_year($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $ut -= 24.0 * floor($ut / 24.0);

        return array($ut, $lct, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr);
    }

    /** Helper function for moon_rise_az */
    function moon_rise_az_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat)
    {
        $mm = moon_long($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $bm = moon_lat($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $pm = deg2rad(moon_hp($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1));
        $dp = nutat_long($gdy, $gmn, $gyr);
        $th = 0.27249 * sin($pm);
        $di = $th + 0.0098902 - $pm;
        $p = decimal_degrees_to_degree_hours(ec_ra($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr));
        $q = ec_dec($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr);
        $lu = rise_set_local_sidereal_time_rise($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);
        $au = rise_set_azimuth_rise($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);

        return array($mm, $bm, $pm, $dp, $th, $di, $p, $q, $lu, $lct, $au);
    }

    /**
     * Local time of moonset.
     * 
     * Original macro name: MoonSetLCT
     */
    function moon_set_lct($dy, $mn, $yr, $ds, $zc, $gLong, $gLat)
    {
        $gdy = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gmn = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gyr = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $lct = 12.0;
        $dy1 = $dy;
        $mn1 = $mn;
        $yr1 = $yr;

        list($lct6700result1_mm, $lct6700result1_bm, $lct6700result1_pm, $lct6700result1_dp, $lct6700result1_th, $lct6700result1_di, $lct6700result1_p, $lct6700result1_q, $lct6700result1_lu, $lct6700result1_lct) =
            moon_set_lct_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
        $lu = $lct6700result1_lu;
        $lct = $lct6700result1_lct;

        if ($lct == -99.0)
            return $lct;

        $la = $lu;

        $g1 = 0.0;
        $gu = 0.0;
        for ($k = 1; $k < 9; $k++) {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

            $g1 = ($k == 1) ? $ut : $gu;

            $gu = $ut;
            $ut = $gu;

            list($lct6680result1_ut, $lct6680result1_lct, $lct6680result1_dy1, $lct6680result1_mn1, $lct6680result1_yr1, $lct6680result1_gdy, $lct6680result1_gmn, $lct6680result1_gyr) =
                moon_set_lct_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut);
            $lct = $lct6680result1_lct;
            $dy1 = $lct6680result1_dy1;
            $mn1 = $lct6680result1_mn1;
            $yr1 = $lct6680result1_yr1;
            $gdy = $lct6680result1_gdy;
            $gmn = $lct6680result1_gmn;
            $gyr = $lct6680result1_gyr;

            list($lct6700result2_mm, $lct6700result2_bm, $lct6700result2_pm, $lct6700result2_dp, $lct6700result2_th, $lct6700result2_di, $lct6700result2_p, $lct6700result2_q, $lct6700result2_lu, $lct6700result2_lct) =
                moon_set_lct_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
            $lu = $lct6700result2_lu;
            $lct = $lct6700result2_lct;

            if ($lct == -99.0)
                return $lct;

            $la = $lu;
        }

        $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);

        return $lct;
    }

    /** Helper function for moon_set_lct */
    function moon_set_lct_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut)
    {
        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $gdy = local_civil_time_greenwich_day($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gmn = local_civil_time_greenwich_month($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gyr = local_civil_time_greenwich_year($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $ut -= 24.0 * floor($ut / 24.0);

        return array($ut, $lct, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr);
    }

    /** Helper function for moon_set_lct */
    function moon_set_lct_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat)
    {
        $mm = moon_long($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $bm = moon_lat($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $pm = deg2rad(moon_hp($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1));
        $dp = nutat_long($gdy, $gmn, $gyr);
        $th = 0.27249 * sin($pm);
        $di = $th + 0.0098902 - $pm;
        $p = decimal_degrees_to_degree_hours(ec_ra($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr));
        $q = ec_dec($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr);
        $lu = rise_set_local_sidereal_time_set($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);

        if (ers($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat) != RiseSetStatus::OK)
            $lct = -99.0;

        return array($mm, $bm, $pm, $dp, $th, $di, $p, $q, $lu, $lct);
    }

    /**
     * Local date of moonset.
     * 
     * Original macro names: MoonSetLcDay, MoonSetLcMonth, MoonSetLcYear
     */
    function moon_set_lc_dmy($dy, $mn, $yr, $ds, $zc, $gLong, $gLat)
    {
        $gdy = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gmn = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gyr = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $lct = 12.0;
        $dy1 = $dy;
        $mn1 = $mn;
        $yr1 = $yr;

        list($dmy6700result1_mm, $dmy6700result1_bm, $dmy6700result1_pm, $dmy6700result1_dp, $dmy6700result1_th, $dmy6700result1_di, $dmy6700result1_p, $dmy6700result1_q, $dmy6700result1_lu, $dmy6700result1_lct) =
            moon_set_lc_dmy_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
        $lu = $dmy6700result1_lu;
        $lct = $dmy6700result1_lct;

        if ($lct == -99.0)
            return array($lct, (int) $lct, (int) $lct);

        $la = $lu;

        $g1 = 0.0;
        $gu = 0.0;
        for ($k = 1; $k < 9; $k++) {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

            $g1 = ($k == 1) ? $ut : $gu;

            $gu = $ut;
            $ut = $gu;

            list($dmy6680result1_ut, $dmy6680result1_lct, $dmy6680result1_dy1, $dmy6680result1_mn1, $dmy6680result1_yr1, $dmy6680result1_gdy, $dmy6680result1_gmn, $dmy6680result1_gyr) =
                moon_set_lc_dmy_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut);
            $lct = $dmy6680result1_lct;
            $dy1 = $dmy6680result1_dy1;
            $mn1 = $dmy6680result1_mn1;
            $yr1 = $dmy6680result1_yr1;
            $gdy = $dmy6680result1_gdy;
            $gmn = $dmy6680result1_gmn;
            $gyr = $dmy6680result1_gyr;

            list($dmy6700result2_mm, $dmy6700result2_bm, $dmy6700result2_pm, $dmy6700result2_dp, $dmy6700result2_th, $dmy6700result2_di, $dmy6700result2_p, $dmy6700result2_q, $dmy6700result2_lu, $dmy6700result2_lct) =
                moon_set_lc_dmy_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
            $lu = $dmy6700result2_lu;
            $lct = $dmy6700result2_lct;

            if ($lct == -99.0)
                return array($lct, (int) $lct, (int) $lct);

            $la = $lu;
        }

        $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
        $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);

        return array($dy1, $mn1, $yr1);
    }

    /** Helper function for moon_set_lc_dmy */
    function moon_set_lc_dmy_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut)
    {
        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $gdy = local_civil_time_greenwich_day($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gmn = local_civil_time_greenwich_month($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gyr = local_civil_time_greenwich_year($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $ut -= 24.0 * floor($ut / 24.0);

        return array($ut, $lct, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr);
    }

    /** Helper function for moon_set_lc_dmy */
    function moon_set_lc_dmy_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat)
    {
        $mm = moon_long($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $bm = moon_lat($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $pm = deg2rad(moon_hp($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1));
        $dp = nutat_long($gdy, $gmn, $gyr);
        $th = 0.27249 * sin($pm);
        $di = $th + 0.0098902 - $pm;
        $p = decimal_degrees_to_degree_hours(ec_ra($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr));
        $q = ec_dec($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr);
        $lu = rise_set_local_sidereal_time_set($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);

        return array($mm, $bm, $pm, $dp, $th, $di, $p, $q, $lu, $lct);
    }

    /**
     * Local azimuth of moonset.
     * 
     * Original macro name: MoonSetAz
     */
    function moon_set_az($dy, $mn, $yr, $ds, $zc, $gLong, $gLat)
    {
        $gdy = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gmn = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $gyr = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $lct = 12.0;
        $dy1 = $dy;
        $mn1 = $mn;
        $yr1 = $yr;

        list($az6700result1_mm, $az6700result1_bm, $az6700result1_pm, $az6700result1_dp, $az6700result1_th, $az6700result1_di, $az6700result1_p, $az6700result1_q, $az6700result1_lu, $az6700result1_lct, $az6700result1_au) =
            moon_set_az_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
        $lu = $az6700result1_lu;
        $lct = $az6700result1_lct;

        if ($lct == -99.0)
            return $lct;

        $la = $lu;

        $gu = 0.0;
        $aa = 0.0;
        for ($k = 1; $k < 9; $k++) {
            $x = local_sidereal_time_to_greenwich_sidereal_time($la, 0.0, 0.0, $gLong);
            $ut = greenwich_sidereal_time_to_universal_time($x, 0.0, 0.0, $gdy, $gmn, $gyr);

            $g1 = ($k == 1) ? $ut : $gu;

            $gu = $ut;
            $ut = $gu;

            list($az6680result1_ut, $az6680result1_lct, $az6680result1_dy1, $az6680result1_mn1, $az6680result1_yr1, $az6680result1_gdy, $az6680result1_gmn, $az6680result1_gyr) =
                moon_set_az_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut);
            $lct = $az6680result1_lct;
            $dy1 = $az6680result1_dy1;
            $mn1 = $az6680result1_mn1;
            $yr1 = $az6680result1_yr1;
            $gdy = $az6680result1_gdy;
            $gmn = $az6680result1_gmn;
            $gyr = $az6680result1_gyr;

            list($az6700result2_mm, $az6700result2_bm, $az6700result2_pm, $az6700result2_dp, $az6700result2_th, $az6700result2_di, $az6700result2_p, $az6700result2_q, $az6700result2_lu, $az6700result2_lct, $az6700result2_au) =
                moon_set_az_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat);
            $lu = $az6700result2_lu;
            $lct = $az6700result2_lct;
            $au = $az6700result2_au;

            if ($lct == -99.0)
                return $lct;

            $la = $lu;
            $aa = $au;
        }

        $au = $aa;

        return $au;
    }

    /** Helper function for moon_set_az */
    function moon_set_az_l6680($x, $ds, $zc, $gdy, $gmn, $gyr, $g1, $ut)
    {
        if (eg_st_ut($x, 0.0, 0.0, $gdy, $gmn, $gyr) != WarningFlag::OK)
            if (abs($g1 - $ut) > 0.5)
                $ut += 23.93447;

        $ut = ut_day_adjust($ut, $g1);
        $lct = universal_time_to_local_civil_time_ma($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $dy1 = universal_time_local_civil_day($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $mn1 = universal_time_local_civil_month($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $yr1 = universal_time_local_civil_year($ut, 0.0, 0.0, $ds, $zc, $gdy, $gmn, $gyr);
        $gdy = local_civil_time_greenwich_day($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gmn = local_civil_time_greenwich_month($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $gyr = local_civil_time_greenwich_year($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $ut -= 24.0 * floor($ut / 24.0);

        return array($ut, $lct, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr);
    }

    /** Helper function for moon_set_az */
    function moon_set_az_l6700($lct, $ds, $zc, $dy1, $mn1, $yr1, $gdy, $gmn, $gyr, $gLat)
    {
        $mm = moon_long($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $bm = moon_lat($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1);
        $pm = deg2rad(moon_hp($lct, 0.0, 0.0, $ds, $zc, $dy1, $mn1, $yr1));
        $dp = nutat_long($gdy, $gmn, $gyr);
        $th = 0.27249 * sin($pm);
        $di = $th + 0.0098902 - $pm;
        $p = decimal_degrees_to_degree_hours(ec_ra($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr));
        $q = ec_dec($mm + $dp, 0.0, 0.0, $bm, 0.0, 0.0, $gdy, $gmn, $gyr);
        $lu = rise_set_local_sidereal_time_set($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);
        $au = rise_set_azimuth_set($p, 0.0, 0.0, $q, 0.0, 0.0, w_to_degrees($di), $gLat);

        return array($mm, $bm, $pm, $dp, $th, $di, $p, $q, $lu, $lct, $au);
    }

    /**
     * Determine if a lunar eclipse is likely to occur.
     * 
     * Original macro name: LEOccurrence
     */
    function lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr)
    {
        $d0 = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $m0 = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $y0 = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);

        $j0 = civil_date_to_julian_date(0.0, 1, $y0);
        $dj = civil_date_to_julian_date($d0, $m0, $y0);
        $k = ($y0 - 1900.0 + (($dj - $j0) * 1.0 / 365.0)) * 12.3685;
        $k = lint($k + 0.5);
        $tn = $k / 1236.85;
        $tf = ($k + 0.5) / 1236.85;
        $t = $tn;
        list($l6855result1_f, $l6855result1_dd, $l6855result1_e1, $l6855result1_b1, $l6855result1_a, $l6855result1_b) =
            lunar_eclipse_occurrence_l6855($t, $k);
        $t = $tf;
        $k += 0.5;
        list($l6855result2_f, $l6855result2_dd, $l6855result2_e1, $l6855result2_b1, $l6855result2_a, $l6855result2_b) =
            lunar_eclipse_occurrence_l6855($t, $k);
        $fb = $l6855result2_f;

        $df = abs($fb - 3.141592654 * lint($fb / 3.141592654));

        if ($df > 0.37)
            $df = 3.141592654 - $df;

        $s = EclipseOccurrence::EclipseCertain;
        if ($df >= 0.242600766) {
            $s = EclipseOccurrence::EclipsePossible;

            if ($df > 0.37)
                $s = EclipseOccurrence::NoEclipse;
        }

        return $s;
    }

    /** Helper function for lunar_eclipse_occurrence */
    function lunar_eclipse_occurrence_l6855($t, $k)
    {
        $t2 = $t * $t;
        $e = 29.53 * $k;
        $c = 166.56 + (132.87 - 0.009173 * $t) * $t;
        $c = deg2rad($c);
        $b = 0.00058868 * $k + (0.0001178 - 0.000000155 * $t) * $t2;
        $b = $b + 0.00033 * sin($c) + 0.75933;
        $a = $k / 12.36886;
        $a1 = 359.2242 + 360.0 * f_part($a) - (0.0000333 + 0.00000347 * $t) * $t2;
        $a2 = 306.0253 + 360.0 * f_part($k / 0.9330851);
        $a2 += (0.0107306 + 0.00001236 * $t) * $t2;
        $a = $k / 0.9214926;
        $f = 21.2964 + 360.0 * f_part($a) - (0.0016528 + 0.00000239 * $t) * $t2;
        $a1 = unwind_deg($a1);
        $a2 = unwind_deg($a2);
        $f = unwind_deg($f);
        $a1 = deg2rad($a1);
        $a2 = deg2rad($a2);
        $f = deg2rad($f);

        $dd = (0.1734 - 0.000393 * $t) * sin($a1) + 0.0021 * sin(2.0 * $a1);
        $dd = $dd - 0.4068 * sin($a2) + 0.0161 * sin(2.0 * $a2) - 0.0004 * sin(3.0 * $a2);
        $dd = $dd + 0.0104 * sin(2.0 * $f) - 0.0051 * sin($a1 + $a2);
        $dd = $dd - 0.0074 * sin($a1 - $a2) + 0.0004 * sin(2.0 * $f + $a1);
        $dd = $dd - 0.0004 * sin(2.0 * $f - $a1) - 0.0006 * (2.0 * $f + $a2) + 0.001 * sin(2.0 * $f - $a2);
        $dd += 0.0005 * sin($a1 + 2.0 * $a2);
        $e1 = floor($e);
        $b = $b + $dd + ($e - $e1);
        $b1 = floor($b);
        $a = $e1 + $b1;
        $b -= $b1;

        return array($f, $dd, $e1, $b1, $a, $b);
    }

    /**
     * Calculate time of maximum shadow for lunar eclipse (UT)
     * 
     * Original macro name: UTMaxLunarEclipse
     */
    function ut_max_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $rp = ($hd + $rn + $ps) * 1.02;
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        return $z1;
    }

    /**
     * Calculate time of first shadow contact for lunar eclipse (UT)
     * 
     * Original macro name: UTFirstContactLunarEclipse
     */
    function ut_first_contact_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $rp = ($hd + $rn + $ps) * 1.02;
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        if ($z6 < 0.0)
            $z6 += 24.0;

        return $z6;
    }

    /**
     * Calculate time of last shadow contact for lunar eclipse (UT)
     * 
     * Original macro name: UTLastContactLunarEclipse
     */
    function ut_last_contact_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $rp = ($hd + $rn + $ps) * 1.02;
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z7 = $z1 + $zd - lint(($z1 + $zd) / 24.0) * 24.0;

        return $z7;
    }

    /**
     * Calculate start time of umbra phase of lunar eclipse (UT)
     * 
     * Original macro name: UTStartUmbraLunarEclipse
     */
    function ut_start_umbra_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $ru = ($hd - $rn + $ps) * 1.02;
        $rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        $r = $rm + $ru;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z8 = $z1 - $zd;

        if ($z8 < 0.0)
            $z8 += 24.0;

        return $z8;
    }

    /**
     * Calculate end time of umbra phase of lunar eclipse (UT)
     * 
     * Original macro name: UTEndUmbraLunarEclipse
     */
    function ut_end_umbra_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $ru = ($hd - $rn + $ps) * 1.02;
        $rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        $r = $rm + $ru;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z9 = $z1 + $zd - lint(($z1 + $zd) / 24.0) * 24.0;

        return $z9;
    }

    /**
     * Calculate start time of total phase of lunar eclipse (UT)
     * 
     * Original macro name: UTStartTotalLunarEclipse
     */
    function ut_start_total_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $ru = ($hd - $rn + $ps) * 1.02;
        $rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        $r = $rm + $ru;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z8 = $z1 - $zd;

        $r = $ru - $rm;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $zcc = $z1 - $zd;

        if ($zcc < 0.0)
            $zcc = $zc + 24.0;

        return $zcc;
    }

    /**
     * Calculate end time of total phase of lunar eclipse (UT)
     * 
     * Original macro name: UTEndTotalLunarEclipse
     */
    function ut_end_total_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $ru = ($hd - $rn + $ps) * 1.02;
        $rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        $r = $rm + $ru;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z8 = $z1 - $zd;

        $r = $ru - $rm;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $zb = $z1 + $zd - lint(($z1 + $zd) / 24.0) * 24.0;

        return $zb;
    }

    /**
     * Calculate magnitude of lunar eclipse.
     * 
     * Original macro name: MagLunarEclipse
     */
    function mag_lunar_eclipse($dy, $mn, $yr, $ds, $zc)
    {
        $tp = 2.0 * pi();

        if (lunar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = full_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utfm = $xi * 24.0;
        $ut = $utfm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utfm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utfm;
        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $q = 0.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $sr = $sr + pi() - lint(($sr + pi()) / $tp) * $tp;
        $by -= $q;
        $bz -= $q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $ru = ($hd - $rn + $ps) * 1.02;
        $rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rp;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        $r = $rm + $ru;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);
        $mg = ($rm + $rp - $pj) / (2.0 * $rm);

        if ($dd < 0.0)
            return $mg;

        $zd = sqrt($dd);
        $z8 = $z1 - $zd;

        $r = $ru - $rm;
        $dd = $z1 - $x0;
        $mg = ($rm + $ru - $pj) / (2.0 * $rm);

        return $mg;
    }

    /**
     * Determine if a solar eclipse is likely to occur.
     * 
     * Original macro name: SEOccurrence
     */
    function solar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr)
    {
        $d0 = local_civil_time_greenwich_day(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $m0 = local_civil_time_greenwich_month(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);
        $y0 = local_civil_time_greenwich_year(12.0, 0.0, 0.0, $ds, $zc, $dy, $mn, $yr);

        $j0 = civil_date_to_julian_date(0.0, 1, $y0);
        $dj = civil_date_to_julian_date($d0, $m0, $y0);
        $k = ($y0 - 1900.0 + (($dj - $j0) * 1.0 / 365.0)) * 12.3685;
        $k = lint($k + 0.5);
        $tn = $k / 1236.85;
        $tf = ($k + 0.5) / 1236.85;
        $t = $tn;
        list($l6855result1_f, $l6855result1_dd, $l6855result1_e1, $l6855result1_b1, $l6855result1_a, $l6855result1_b) =
            solar_eclipse_occurrence_l6855($t, $k);
        $nb = $l6855result1_f;
        $t = $tf;
        $k += 0.5;
        list($l6855result2_f, $l6855result2_dd, $l6855result2_e1, $l6855result2_b1, $l6855result2_a, $l6855result2_b) =
            solar_eclipse_occurrence_l6855($t, $k);

        $df = abs($nb - 3.141592654 * lint($nb / 3.141592654));

        if ($df > 0.37)
            $df = 3.141592654 - $df;

        $s = EclipseOccurrence::EclipseCertain;
        if ($df >= 0.242600766) {
            $s = EclipseOccurrence::EclipsePossible;
            if ($df > 0.37)
                $s = EclipseOccurrence::NoEclipse;
        }

        return $s;
    }

    /** Helper function for solar_eclipse_occurrence */
    function solar_eclipse_occurrence_l6855($t, $k)
    {
        $t2 = $t * $t;
        $e = 29.53 * $k;
        $c = 166.56 + (132.87 - 0.009173 * $t) * $t;
        $c = deg2rad($c);
        $b = 0.00058868 * $k + (0.0001178 - 0.000000155 * $t) * $t2;
        $b = $b + 0.00033 * sin($c) + 0.75933;
        $a = $k / 12.36886;
        $a1 = 359.2242 + 360.0 * f_part($a) - (0.0000333 + 0.00000347 * $t) * $t2;
        $a2 = 306.0253 + 360.0 * f_part($k / 0.9330851);
        $a2 += (0.0107306 + 0.00001236 * $t) * $t2;
        $a = $k / 0.9214926;
        $f = 21.2964 + 360.0 * f_part($a) - (0.0016528 + 0.00000239 * $t) * $t2;
        $a1 = unwind_deg($a1);
        $a2 = unwind_deg($a2);
        $f = unwind_deg($f);
        $a1 = deg2rad($a1);
        $a2 = deg2rad($a2);
        $f = deg2rad($f);

        $dd = (0.1734 - 0.000393 * $t) * sin($a1) + 0.0021 * sin(2.0 * $a1);
        $dd = $dd - 0.4068 * sin($a2) + 0.0161 * sin(2.0 * $a2) - 0.0004 * sin(3.0 * $a2);
        $dd = $dd + 0.0104 * sin(2.0 * $f) - 0.0051 * sin($a1 + $a2);
        $dd = $dd - 0.0074 * sin($a1 - $a2) + 0.0004 * sin(2.0 * $f + $a1);
        $dd = $dd - 0.0004 * sin(2.0 * $f - $a1) - 0.0006 * sin(2.0 * $f + $a2) + 0.001 * sin(2.0 * $f - $a2);
        $dd += 0.0005 * sin($a1 + 2.0 * $a2);
        $e1 = floor($e);
        $b = $b + $dd + ($e - $e1);
        $b1 = floor($b);
        $a = $e1 + $b1;
        $b -= $b1;

        return array($f, $dd, $e1, $b1, $a, $b);
    }

    /**
     * Calculate time of maximum shadow for solar eclipse (UT)
     * 
     * Original macro name: UTMaxSolarEclipse
     */
    function ut_max_solar_eclipse($dy, $mn, $yr, $ds, $zc, $glong, $glat)
    {
        $tp = 2.0 * pi();

        if (solar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = new_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utnm = $xi * 24.0;
        $ut = $utnm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utnm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utnm;
        $x = $my;
        $y = $by;
        $tm = $xh - 1.0;
        $hp = $hy;
        list($l7390result1_paa, $l7390result1_qaa, $l7390result1_xaa, $l7390result1_pbb, $l7390result1_qbb, $l7390result1_xbb, $l7390result1_p, $l7390result1_q) =
            ut_max_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $my = $l7390result1_p;
        $by = $l7390result1_q;
        $x = $mz;
        $y = $bz;
        $tm = $xh + 1.0;
        $hp = $hz;
        list($l7390result2_paa, $l7390result2_qaa, $l7390result2_xaa, $l7390result2_pbb, $l7390result2_qbb, $l7390result2_xbb, $l7390result2_p, $l7390result2_q) =
            ut_max_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $mz = $l7390result2_p;
        $bz = $l7390result2_q;

        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $x = $sr;
        $y = 0.0;
        $tm = $ut;
        $hp = 0.00004263452 / $rr;
        list($l7390result3_paa, $l7390result3_qaa, $l7390result3_xaa, $l7390result3_pbb, $l7390result3_qbb, $l7390result3_xbb, $l7390result3_p, $l7390result3_q) =
            ut_max_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $sr = $l7390result3_p;
        $by -= $l7390result3_q;
        $bz -= $l7390result3_q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $_ru = ($hd - $rn + $ps) * 1.02;
        $_rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rn;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);

        return $z1;
    }

    /** Helper function for ut_max_solar_eclipse */
    function ut_max_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp)
    {
        $paa = ec_ra(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $qaa = ec_dec(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $xaa = right_ascension_to_hour_angle(decimal_degrees_to_degree_hours($paa), 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $pbb = parallax_ha($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $qbb = parallax_dec($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $xbb = hour_angle_to_right_ascension($pbb, 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $p = deg2rad(eq_e_long($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));
        $q = deg2rad(eq_e_lat($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));

        return array($paa, $qaa, $xaa, $pbb, $qbb, $xbb, $p, $q);
    }

    /**
     * Calculate time of first contact for solar eclipse (UT)
     * 
     * Original macro name: UTFirstContactSolarEclipse
     */
    function ut_first_contact_solar_eclipse($dy, $mn, $yr, $ds, $zc, $glong, $glat)
    {
        $tp = 2.0 * pi();

        if (solar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = new_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utnm = $xi * 24.0;
        $ut = $utnm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utnm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utnm;
        $x = $my;
        $y = $by;
        $tm = $xh - 1.0;
        $hp = $hy;

        list($l7390result1_paa, $l7390result1_qaa, $l7390result1_xaa, $l7390result1_pbb, $l7390result1_qbb, $l7390result1_xbb, $l7390result1_p, $l7390result1_q) =
            ut_first_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $my = $l7390result1_p;
        $by = $l7390result1_q;
        $x = $mz;
        $y = $bz;
        $tm = $xh + 1.0;
        $hp = $hz;
        list($l7390result2_paa, $l7390result2_qaa, $l7390result2_xaa, $l7390result2_pbb, $l7390result2_qbb, $l7390result2_xbb, $l7390result2_p, $l7390result2_q) =
            ut_first_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $mz = $l7390result2_p;
        $bz = $l7390result2_q;

        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $x = $sr;
        $y = 0.0;
        $tm = $ut;
        $hp = 0.00004263452 / $rr;
        list($l7390result3_paa, $l7390result3_qaa, $l7390result3_xaa, $l7390result3_pbb, $l7390result3_qbb, $l7390result3_xbb, $l7390result3_p, $l7390result3_q) =
            ut_first_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $sr = $l7390result3_p;
        $by -= $l7390result3_q;
        $bz -= $l7390result3_q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $_ru = ($hd - $rn + $ps) * 1.02;
        $_rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rn;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z6 = $z1 - $zd;

        if ($z6 < 0.0)
            $z6 += 24.0;

        return $z6;
    }

    /** Helper function for ut_first_contact_solar_eclipse */
    function ut_first_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp)
    {
        $paa = ec_ra(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $qaa = ec_dec(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $xaa = right_ascension_to_hour_angle(decimal_degrees_to_degree_hours($paa), 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $pbb = parallax_ha($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $qbb = parallax_dec($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $xbb = hour_angle_to_right_ascension($pbb, 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $p = deg2rad(eq_e_long($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));
        $q = deg2rad(eq_e_lat($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));

        return array($paa, $qaa, $xaa, $pbb, $qbb, $xbb, $p, $q);
    }

    /**
     * Calculate time of last contact for solar eclipse (UT)
     * 
     * Original macro name: UTLastContactSolarEclipse
     */
    function ut_last_contact_solar_eclipse($dy, $mn, $yr, $ds, $zc, $glong, $glat)
    {
        $tp = 2.0 * pi();

        if (solar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = new_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utnm = $xi * 24.0;
        $ut = $utnm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utnm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utnm;
        $x = $my;
        $y = $by;
        $tm = $xh - 1.0;
        $hp = $hy;
        list($l7390result1_paa, $l7390result1_qaa, $l7390result1_xaa, $l7390result1_pbb, $l7390result1_qbb, $l7390result1_xbb, $l7390result1_p, $l7390result1_q) =
            ut_last_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $my = $l7390result1_p;
        $by = $l7390result1_q;
        $x = $mz;
        $y = $bz;
        $tm = $xh + 1.0;
        $hp = $hz;
        list($l7390result2_paa, $l7390result2_qaa, $l7390result2_xaa, $l7390result2_pbb, $l7390result2_qbb, $l7390result2_xbb, $l7390result2_p, $l7390result2_q) =
            ut_last_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $mz = $l7390result2_p;
        $bz = $l7390result2_q;

        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $x = $sr;
        $y = 0.0;
        $tm = $ut;
        $hp = 0.00004263452 / $rr;
        list($l7390result3_paa, $l7390result3_qaa, $l7390result3_xaa, $l7390result3_pbb, $l7390result3_qbb, $l7390result3_xbb, $l7390result3_p, $l7390result3_q) =
            ut_last_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $sr = $l7390result3_p;
        $by -= $l7390result3_q;
        $bz -= $l7390result3_q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $_ru = ($hd - $rn + $ps) * 1.02;
        $_rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rn;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);
        $z7 = $z1 + $zd - lint(($z1 + $zd) / 24.0) * 24.0;

        return $z7;
    }

    /** Helper function for ut_last_contact_solar_eclipse */
    function ut_last_contact_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp)
    {
        $paa = ec_ra(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $qaa = ec_dec(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $xaa = right_ascension_to_hour_angle(decimal_degrees_to_degree_hours($paa), 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $pbb = parallax_ha($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $qbb = parallax_dec($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $xbb = hour_angle_to_right_ascension($pbb, 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $p = deg2rad(eq_e_long($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));
        $q = deg2rad(eq_e_lat($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));

        return array($paa, $qaa, $xaa, $pbb, $qbb, $xbb, $p, $q);
    }

    /**
     * Calculate magnitude of solar eclipse.
     * 
     * Original macro name: MagSolarEclipse
     */
    function mag_solar_eclipse($dy, $mn, $yr, $ds, $zc, $glong, $glat)
    {
        $tp = 2.0 * pi();

        if (solar_eclipse_occurrence($ds, $zc, $dy, $mn, $yr) == EclipseOccurrence::NoEclipse)
            return -99.0;

        $dj = new_moon($ds, $zc, $dy, $mn, $yr);
        $gday = julian_date_day($dj);
        $gmonth = julian_date_month($dj);
        $gyear = julian_date_year($dj);
        $igday = floor($gday);
        $xi = $gday - $igday;
        $utnm = $xi * 24.0;
        $ut = $utnm - 1.0;
        $ly = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $my = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $by = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hy = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $ut = $utnm + 1.0;
        $sb = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear)) - $ly;
        $mz = deg2rad(moon_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $bz = deg2rad(moon_lat($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $hz = deg2rad(moon_hp($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));

        if ($sb < 0.0)
            $sb += $tp;

        $xh = $utnm;
        $x = $my;
        $y = $by;
        $tm = $xh - 1.0;
        $hp = $hy;
        list($l7390result1_paa, $l7390result1_qaa, $l7390result1_xaa, $l7390result1_pbb, $l7390result1_qbb, $l7390result1_xbb, $l7390result1_p, $l7390result1_q) =
            mag_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $my = $l7390result1_p;
        $by = $l7390result1_q;
        $x = $mz;
        $y = $bz;
        $tm = $xh + 1.0;
        $hp = $hz;
        list($l7390result2_paa, $l7390result2_qaa, $l7390result2_xaa, $l7390result2_pbb, $l7390result2_qbb, $l7390result2_xbb, $l7390result2_p, $l7390result2_q) =
            mag_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $mz = $l7390result2_p;
        $bz = $l7390result2_q;

        $x0 = $xh + 1.0 - (2.0 * $bz / ($bz - $by));
        $dm = $mz - $my;

        if ($dm < 0.0)
            $dm += $tp;

        $lj = ($dm - $sb) / 2.0;
        $mr = $my + ($dm * ($x0 - $xh + 1.0) / 2.0);
        $ut = $x0 - 0.13851852;
        $rr = sun_dist($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear);
        $sr = deg2rad(sun_long($ut, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear));
        $sr += deg2rad(nutat_long($igday, $gmonth, $gyear) - 0.00569);
        $x = $sr;
        $y = 0.0;
        $tm = $ut;
        $hp = 0.00004263452 / $rr;
        list($l7390result3_paa, $l7390result3_qaa, $l7390result3_xaa, $l7390result3_pbb, $l7390result3_qbb, $l7390result3_xbb, $l7390result3_p, $l7390result3_q) =
            mag_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp);
        $sr = $l7390result3_p;
        $by -= $l7390result3_q;
        $bz -= $l7390result3_q;
        $p3 = 0.00004263;
        $zh = ($sr - $mr) / $lj;
        $tc = $x0 + $zh;
        $sh = ((($bz - $by) * ($tc - $xh - 1.0) / 2.0) + $bz) / $lj;
        $s2 = $sh * $sh;
        $z2 = $zh * $zh;
        $ps = $p3 / ($rr * $lj);
        $z1 = ($zh * $z2 / ($z2 + $s2)) + $x0;
        $h0 = ($hy + $hz) / (2.0 * $lj);
        $rm = 0.272446 * $h0;
        $rn = 0.00465242 / ($lj * $rr);
        $hd = $h0 * 0.99834;
        $_ru = ($hd - $rn + $ps) * 1.02;
        $_rp = ($hd + $rn + $ps) * 1.02;
        $pj = abs($sh * $zh / sqrt($s2 + $z2));
        $r = $rm + $rn;
        $dd = $z1 - $x0;
        $dd = $dd * $dd - (($z2 - ($r * $r)) * $dd / $zh);

        if ($dd < 0.0)
            return -99.0;

        $zd = sqrt($dd);

        $mg = ($rm + $rn - $pj) / (2.0 * $rn);

        return $mg;
    }

    /** Helper function for mag_solar_eclipse */
    function mag_solar_eclipse_l7390($x, $y, $igday, $gmonth, $gyear, $tm, $glong, $glat, $hp)
    {
        $paa = ec_ra(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $qaa = ec_dec(w_to_degrees($x), 0.0, 0.0, w_to_degrees($y), 0.0, 0.0, $igday, $gmonth, $gyear);
        $xaa = right_ascension_to_hour_angle(decimal_degrees_to_degree_hours($paa), 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $pbb = parallax_ha($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $qbb = parallax_dec($xaa, 0.0, 0.0, $qaa, 0.0, 0.0, CoordinateType::True, $glat, 0.0, w_to_degrees($hp));
        $xbb = hour_angle_to_right_ascension($pbb, 0.0, 0.0, $tm, 0.0, 0.0, 0, 0, $igday, $gmonth, $gyear, $glong);
        $p = deg2rad(eq_e_long($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));
        $q = deg2rad(eq_e_lat($xbb, 0.0, 0.0, $qbb, 0.0, 0.0, $igday, $gmonth, $gyear));

        return array($paa, $qaa, $xaa, $pbb, $qbb, $xbb, $p, $q);
    }
}

namespace PA\Types {
    enum AccuracyLevel: string
    {
        case Approximate = "Approximate";
        case Precise = "Precise";
    }

    enum AngleMeasure: string
    {
        case Degrees = "Degrees";
        case Hours = "Hours";
    }

    enum RiseSetStatus: string
    {
        case OK = "OK";
        case NeverRises = "never rises";
        case Circumpolar = "circumpolar";
        case GstToUtConversionWarning = "gst to ut conversion warning";
    }

    enum CoordinateType: string
    {
        case True = "True";
        case Apparent = "Apparent";
    }

    enum WarningFlag: string
    {
        case OK = "OK";
        case Warning = "Warning";
    }

    enum TwilightType: int
    {
        case Civil = 6;
        case Nautical = 12;
        case Astronomical = 18;
    }

    enum TwilightStatus: string
    {
        case OK = "OK";
        case LastsAllNight = "Lasts all night";
        case SunTooFarBelowHorizon = "Sun too far below horizon";
        case GstToUtConversionWarning = "GST to UT conversion warning";
    }

    enum EclipseOccurrence: string
    {
        case EclipseCertain = "EclipseCertain";
        case EclipsePossible = "EclipsePossible";
        case NoEclipse = "NoEclipse";
    }
}

namespace PA\Utils {
    /**
     * Determine if year is a leap year. 
     */
    function is_leap_year($inputYear)
    {
        $year = $inputYear;

        if ($year % 4 == 0) {
            if ($year % 100 == 0)
                return ($year % 400 == 0) ? true : false;
            else
                return true;
        } else
            return false;
    }

    /**
     * Assert that two values are equal and display a descriptive message if they aren't.
     */
    function descriptive_assert($field_name, $actual_value, $expected_value)
    {
        assert($actual_value == $expected_value, "[{$field_name}] Expected {$expected_value}, got {$actual_value}");
    }
}
