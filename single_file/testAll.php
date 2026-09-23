<?php

namespace PA\Test\Binary {
    include_once 'PAAll.php';

    use PA\Binary as PA_Binary;

    use function PA\Utils\descriptive_assert;

    function binary_star_orbit($greenwichDateDay, $greenwichDateMonth, $greenwichDateYear, $binaryName, $expected_positionAngleDeg, $expected_separationArcsec)
    {
        $title = "Binary Star Orbit";

        list($positionAngleDeg, $separationArcsec) =
            PA_Binary\binary_star_orbit($greenwichDateDay, $greenwichDateMonth, $greenwichDateYear, $binaryName, $expected_positionAngleDeg, $expected_separationArcsec);

        descriptive_assert("[{$title}] Position Angle degrees", $positionAngleDeg, $expected_positionAngleDeg);
        descriptive_assert("[{$title}] Separation arcsecs", $separationArcsec, $expected_separationArcsec);

        echo "[{$title}] PASSED\n";
    }

    binary_star_orbit(1, 1, 1980, "eta-Cor", 318.5, 0.41);
}

namespace PA\Test\Comet {
    include_once 'PAAll.php';

    use PA\Comet as PA_Comet;

    use function PA\Utils\descriptive_assert;

    function position_of_elliptical_comet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $cometName, $expected_cometRAHour, $expected_cometRAMin, $expected_cometDecDeg, $expected_cometDecMin, $expected_cometDistEarth)
    {
        $title = "Position of Elliptical Comet";

        list($cometRAHour, $cometRAMin, $cometDecDeg, $cometDecMin, $cometDistEarth) =
            PA_Comet\position_of_elliptical_comet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $cometName);

        descriptive_assert("[{$title}] RA Hour", $cometRAHour, $expected_cometRAHour);
        descriptive_assert("[{$title}] RA Minutes", $cometRAMin, $expected_cometRAMin);
        descriptive_assert("[{$title}] Declination Degrees", $cometDecDeg, $expected_cometDecDeg);
        descriptive_assert("[{$title}] Declination Minutes", $cometDecMin, $expected_cometDecMin);
        descriptive_assert("[{$title}] Distance from Earth", $cometDistEarth, $expected_cometDistEarth);

        echo "[{$title}] PASSED\n";
    }

    function position_of_parabolic_comet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $cometName, $expected_cometRAHour, $expected_cometRAMin, $expected_cometRASec, $expected_cometDecDeg, $expected_cometDecMin, $expected_cometDecSec, $expected_cometDistEarth)
    {
        $title = "Position of Parabolic Comet";

        list($cometRAHour, $cometRAMin, $cometRASec, $cometDecDeg, $cometDecMin, $cometDecSec, $cometDistEarth) =
            PA_Comet\position_of_parabolic_comet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $cometName);

        descriptive_assert("[{$title}] RA Hour", $cometRAHour, $expected_cometRAHour);
        descriptive_assert("[{$title}] RA Minutes", $cometRAMin, $expected_cometRAMin);
        descriptive_assert("[{$title}] RA Seconds", $cometRASec, $expected_cometRASec);
        descriptive_assert("[{$title}] Declination Degrees", $cometDecDeg, $expected_cometDecDeg);
        descriptive_assert("[{$title}] Declination Minutes", $cometDecMin, $expected_cometDecMin);
        descriptive_assert("[{$title}] Declination Seconds", $cometDecSec, $expected_cometDecSec);
        descriptive_assert("[{$title}] Distance from Earth", $cometDistEarth, $expected_cometDistEarth);

        echo "[{$title}] PASSED\n";
    }

    position_of_elliptical_comet(0, 0, 0, false, 0, 1, 1, 1984, "Halley", 6, 29, 10, 13, 8.13);

    position_of_parabolic_comet(0, 0, 0, false, 0, 25, 12, 1977, "Kohler", 23, 17, 11.53, -33, 42, 26.42, 1.11);
}

namespace PA\Test\Coordinates {
    include_once 'PAAll.php';

    use PA\Coordinates as PA_Coord;
    use PA\Types as PA_Types;

    use function PA\Utils\descriptive_assert;

    function angle_to_decimal_degrees($degrees, $minutes, $seconds, $expectedDecimalDegrees)
    {
        $title = "Angle to Decimal Degrees";

        $decimalDegrees = round(PA_Coord\angle_to_decimal_degrees($degrees, $minutes, $seconds), 6);

        descriptive_assert("[{$title}] Decimal Degrees", $decimalDegrees, $expectedDecimalDegrees);

        echo "[{$title}] PASSED\n";
    }

    function decimal_degrees_to_angle($decimalDegrees, $expectedDegrees, $expectedMinutes, $expectedSeconds)
    {
        $title = "Decimal Degrees to Angle";

        list($degrees, $minutes, $seconds) = PA_Coord\decimal_degrees_to_angle($decimalDegrees);

        descriptive_assert("[{$title}] Degrees", $degrees, $expectedDegrees);
        descriptive_assert("[{$title}] Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title}] Seconds", $seconds, $expectedSeconds);

        echo "[{$title}] PASSED\n";
    }

    function right_ascension_to_hour_angle($raHours, $raMinutes, $raSeconds, $lctHours, $lctMinutes, $lctSeconds, $isDaylightSavings, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude, $expectedHourAngleHours, $expectedHourAngleMinutes, $expectedHourAngleSeconds)
    {
        $title = "Right Ascension to Hour Angle";

        list($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds) = PA_Coord\right_ascension_to_hour_angle($raHours, $raMinutes, $raSeconds, $lctHours, $lctMinutes, $lctSeconds, $isDaylightSavings, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude);

        descriptive_assert("[{$title}] Hour Angle Hours",  $hourAngleHours, $expectedHourAngleHours);
        descriptive_assert("[{$title}] Hour Angle Minutes",  $hourAngleMinutes, $expectedHourAngleMinutes);
        descriptive_assert("[{$title}] Hour Angle Seconds",  $hourAngleSeconds, $expectedHourAngleSeconds);

        echo "[{$title}] PASSED\n";
    }

    function hour_angle_to_right_ascension($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $lctHours, $lctMinutes, $lctSeconds, $isDaylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude, $expectedrightAscensionHours, $expectedRightAscensionMinutes, $expectedRightAscensionSeconds)
    {
        $title = "Hour Angle to Right Ascension";

        list($rightAscensionHours, $rightAscensionMinutes, $rightAscensionSeconds) = PA_Coord\hour_angle_to_right_ascension($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $lctHours, $lctMinutes, $lctSeconds, $isDaylightSaving, $zoneCorrection, $localDay, $localMonth, $localYear, $geographicalLongitude);

        descriptive_assert("[{$title}] RA Hours",  $rightAscensionHours, $expectedrightAscensionHours);
        descriptive_assert("[{$title}] RA Minutes", $rightAscensionMinutes, $expectedRightAscensionMinutes);
        descriptive_assert("[{$title}] RA Seconds", $rightAscensionSeconds, $expectedRightAscensionSeconds);

        echo "[{$title}] PASSED\n";
    }

    function equatorial_coordinates_to_horizon_coordinates($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude, $expectedAzimuthDegrees, $expectedAzimuthMinutes, $expectedAzimuthSeconds, $expectedAltitudeDegrees, $expectedAltitudeMinutes, $expectedAltitudeSeconds)
    {
        $title = "Equatorial Coordinates to Horizon Coordinates";

        list($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds) = PA_Coord\equatorial_coordinates_to_horizon_coordinates($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds, $geographicalLatitude);

        descriptive_assert("[{$title}] Azimuth Degrees", $azimuthDegrees, $expectedAzimuthDegrees);
        descriptive_assert("[{$title}] Azimuth Minutes", $azimuthMinutes, $expectedAzimuthMinutes);
        descriptive_assert("[{$title}] Azimuth Seconds", $azimuthSeconds, $expectedAzimuthSeconds);
        descriptive_assert("[{$title}] Altitude Degrees", $altitudeDegrees, $expectedAltitudeDegrees);
        descriptive_assert("[{$title}] Altitude Minutes", $altitudeMinutes, $expectedAltitudeMinutes);
        descriptive_assert("[{$title}] Altitude Seconds", $altitudeSeconds, $expectedAltitudeSeconds);

        echo "[{$title}] PASSED\n";
    }

    function horizon_coordinates_to_equatorial_coordinates($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude, $expectedHourAngleHours, $expectedHourAngleMinutes, $expectedHourAngleSeconds, $expectedDeclinationDegrees, $expectedDeclinationMinutes, $expectedDeclinationSeconds)
    {
        $title = "Horizon Coordinates to Equatorial Coordinates";

        list($hourAngleHours, $hourAngleMinutes, $hourAngleSeconds, $declinationDegrees, $declinationMinutes, $declinationSeconds) = PA_Coord\horizon_coordinates_to_equatorial_coordinates($azimuthDegrees, $azimuthMinutes, $azimuthSeconds, $altitudeDegrees, $altitudeMinutes, $altitudeSeconds, $geographicalLatitude);

        descriptive_assert("[{$title}] Hour Angle Hours", $hourAngleHours, $expectedHourAngleHours);
        descriptive_assert("[{$title}] Hour Angle Minutes", $hourAngleMinutes, $expectedHourAngleMinutes);
        descriptive_assert("[{$title}] Hour Angle Seconds", $hourAngleSeconds, $expectedHourAngleSeconds);
        descriptive_assert("[{$title}] Declination Degrees", $declinationDegrees, $expectedDeclinationDegrees);
        descriptive_assert("[{$title}] Declination Minutes", $declinationMinutes, $expectedDeclinationMinutes);
        descriptive_assert("[{$title}] Declination Seconds", $declinationSeconds, $expectedDeclinationSeconds);

        echo "[{$title}] PASSED\n";
    }

    function mean_obliquity_of_the_ecliptic($greenwichDay, $greenwichMonth, $greenwichYear, $expectedObliquity)
    {
        $title = "Mean Obliquity of the Ecliptic";

        $obliquity = round(PA_Coord\mean_obliquity_of_the_ecliptic($greenwichDay, $greenwichMonth, $greenwichYear), 8);

        descriptive_assert("[{$title}] Obliquity", $obliquity, $expectedObliquity);

        echo "[{$title}] PASSED\n";
    }

    function ecliptic_coordinate_to_equatorial_coordinate($eclipticLongitudeDegrees, $eclipticLongitudeMinutes, $eclipticLongitudeSeconds, $eclipticLatitudeDegrees, $eclipticLatitudeMinutes, $eclipticLatitudeSeconds, $greenwichDay, $greenwichMonth, $greenwichYear, $expectedOutRAHours, $expectedOutRAMinutes, $expectedOutRASeconds, $expectedOutDecDegrees, $expectedOutDecMinutes, $expectedOutDecSeconds)
    {
        $title = "Ecliptic Coordinate to Equatorial Coordinate";

        list($outRAHours, $outRAMinutes, $outRASeconds, $outDecDegrees, $outDecMinutes, $outDecSeconds) = PA_Coord\ecliptic_coordinate_to_equatorial_coordinate($eclipticLongitudeDegrees, $eclipticLongitudeMinutes, $eclipticLongitudeSeconds, $eclipticLatitudeDegrees, $eclipticLatitudeMinutes, $eclipticLatitudeSeconds, $greenwichDay, $greenwichMonth, $greenwichYear);

        descriptive_assert("[{$title}] RA Hours", $outRAHours, $expectedOutRAHours);
        descriptive_assert("[{$title}] RA Minutes", $outRAMinutes, $expectedOutRAMinutes);
        descriptive_assert("[{$title}] RA Seconds", $outRASeconds, $expectedOutRASeconds);
        descriptive_assert("[{$title}] Declination Degrees", $outDecDegrees, $expectedOutDecDegrees);
        descriptive_assert("[{$title}] Declination Minutes", $outDecMinutes, $expectedOutDecMinutes);
        descriptive_assert("[{$title}] Declination Seconds", $outDecSeconds, $expectedOutDecSeconds);

        echo "[{$title}] PASSED\n";
    }

    function equatorial_coordinate_to_ecliptic_coordinate($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds, $gwDay, $gwMonth, $gwYear, $expectedOutEclLongDeg, $expectedOutEclLongMin, $expectedOutEclLongSec, $expectedOutEclLatDeg, $expectedOutEclLatMin, $expectedOutEclLatSec)
    {
        $title = "Equatorial Coordinate to Ecliptic Coordinate";

        list($outEclLongDeg, $outEclLongMin, $outEclLongSec, $outEclLatDeg, $outEclLatMin, $outEclLatSec) = PA_Coord\equatorial_coordinate_to_ecliptic_coordinate($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds, $gwDay, $gwMonth, $gwYear);

        descriptive_assert("[{$title}] Ecliptic Longitude Degrees", $outEclLongDeg, $expectedOutEclLongDeg);
        descriptive_assert("[{$title}] Ecliptic Longitude Minutes", $outEclLongMin, $expectedOutEclLongMin);
        descriptive_assert("[{$title}] Ecliptic Longitude Seconds", $outEclLongSec, $expectedOutEclLongSec);
        descriptive_assert("[{$title}] Ecliptic Latitude Degrees", $outEclLatDeg, $expectedOutEclLatDeg);
        descriptive_assert("[{$title}] Ecliptic Latitude Minutes", $outEclLatMin, $expectedOutEclLatMin);
        descriptive_assert("[{$title}] Ecliptic Latitude Seconds", $outEclLatSec, $expectedOutEclLatSec);

        echo "[{$title}] PASSED\n";
    }

    function equatorial_coordinate_to_galactic_coordinate($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds, $expectedGalLongDeg, $expectedGalLongMin, $expectedGalLongSec, $expectedGalLatDeg, $expectedGalLatMin, $expectedGalLatSec)
    {
        $title = "Equatorial Coordinate to Galactic Coordinate";

        list($galLongDeg, $galLongMin, $galLongSec, $galLatDeg, $galLatMin, $galLatSec) = PA_Coord\equatorial_coordinate_to_galactic_coordinate($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds);

        descriptive_assert("[{$title}] Galactic Longitude Degrees", $galLongDeg, $expectedGalLongDeg);
        descriptive_assert("[{$title}] Galactic Longitude Minutes", $galLongMin, $expectedGalLongMin);
        descriptive_assert("[{$title}] Galactic Longitude Seconds", $galLongSec, $expectedGalLongSec);
        descriptive_assert("[{$title}] Galactic Latitude Degrees", $galLatDeg, $expectedGalLatDeg);
        descriptive_assert("[{$title}] Galactic Latitude Minutes", $galLatMin, $expectedGalLatMin);
        descriptive_assert("[{$title}] Galactic Latitude Seconds", $galLatSec, $expectedGalLatSec);

        echo "[{$title}] PASSED\n";
    }

    function galactic_coordinate_to_equatorial_coordinate($galLongDeg, $galLongMin, $galLongSec, $galLatDeg, $galLatMin, $galLatSec, $expectedRaHours, $expectedRaMinutes, $expectedRaSeconds, $expectedDecDegrees, $expectedDecMinutes, $expectedDecSeconds)
    {
        $title = "Galactic Coordinate to Equatorial Coordinate";

        list($raHours, $raMinutes, $raSeconds, $decDegrees, $decMinutes, $decSeconds) = PA_Coord\galactic_coordinate_to_equatorial_coordinate($galLongDeg, $galLongMin, $galLongSec, $galLatDeg, $galLatMin, $galLatSec);

        descriptive_assert("[{$title}] RA Hours", $raHours, $expectedRaHours);
        descriptive_assert("[{$title}] RA Minutes", $raMinutes, $expectedRaMinutes);
        descriptive_assert("[{$title}] RA Seconds", $raSeconds, $expectedRaSeconds);
        descriptive_assert("[{$title}] Declination Degrees", $decDegrees, $expectedDecDegrees);
        descriptive_assert("[{$title}] Declination Minutes", $decMinutes, $expectedDecMinutes);
        descriptive_assert("[{$title}] Declination Seconds", $decSeconds, $expectedDecSeconds);

        echo "[{$title}] PASSED\n";
    }

    function angle_between_two_objects($raLong1HourDeg, $raLong1Min, $raLong1Sec, $decLat1Deg, $decLat1Min, $decLat1Sec, $raLong2HourDeg, $raLong2Min, $raLong2Sec, $decLat2Deg, $decLat2Min, $decLat2Sec, PA_Types\AngleMeasure $hourOrDegree, $expectedAngleDeg, $expectedAngleMin, $expectedAngleSec)
    {
        $title = "Angle Between Two Objects";

        list($angleDeg, $angleMin, $angleSec) = PA_Coord\angle_between_two_objects($raLong1HourDeg, $raLong1Min, $raLong1Sec, $decLat1Deg, $decLat1Min, $decLat1Sec, $raLong2HourDeg, $raLong2Min, $raLong2Sec, $decLat2Deg, $decLat2Min, $decLat2Sec, $hourOrDegree);

        descriptive_assert("[{$title}] Angle Degrees", $angleDeg, $expectedAngleDeg);
        descriptive_assert("[{$title}] Angle Minutes", $angleMin, $expectedAngleMin);
        descriptive_assert("[{$title}] Angle Seconds", $angleSec, $expectedAngleSec);

        echo "[{$title}] PASSED\n";
    }

    function rising_and_setting($raHours, $raMinutes, $raSeconds, $decDeg, $decMin, $decSec, $gwDateDay, $gwDateMonth, $gwDateYear, $geogLongDeg, $geogLatDeg, $vertShiftDeg, $expectedRiseSetStatus, $expectedUtRiseHour, $expectedUtRiseMin, $expectedUtSetHour, $expectedUtSetMin, $expectedAzRise, $expectedAzSet)
    {
        $title = "Rising and Setting";

        list($riseSetStatus, $utRiseHour, $utRiseMin, $utSetHour, $utSetMin, $azRise, $azSet) = PA_Coord\rising_and_setting($raHours, $raMinutes, $raSeconds, $decDeg, $decMin, $decSec, $gwDateDay, $gwDateMonth, $gwDateYear, $geogLongDeg, $geogLatDeg, $vertShiftDeg);

        descriptive_assert("[{$title}] Rise/Set Status", $riseSetStatus->value, $expectedRiseSetStatus->value);
        descriptive_assert("[{$title}] UT Rise Hour", $utRiseHour, $expectedUtRiseHour);
        descriptive_assert("[{$title}] UT Rise Minutes", $utRiseMin, $expectedUtRiseMin);
        descriptive_assert("[{$title}] UT Set Hour", $utSetHour, $expectedUtSetHour);
        descriptive_assert("[{$title}] UT Set Minutes", $utSetMin, $expectedUtSetMin);
        descriptive_assert("[{$title}] Azimuth Rise", $azRise, $expectedAzRise);
        descriptive_assert("[{$title}] Azimuth Set", $azSet, $expectedAzSet);

        echo "[{$title}] PASSED\n";
    }

    function correct_for_precession($raHour, $raMinutes, $raSeconds, $decDeg, $decMinutes, $decSeconds, $epoch1Day, $epoch1Month, $epoch1Year, $epoch2Day, $epoch2Month, $epoch2Year, $expectedCorrectedRAHour, $expectedCorrectedRAMinutes, $expectedCorrectedRASeconds, $expectedCorrectedDecDeg, $expectedCorrectedDecMinutes, $expectedCorrectedDecSeconds)
    {
        $title = "Correct for Precession";

        list($correctedRAHour, $correctedRAMinutes, $correctedRASeconds, $correctedDecDeg, $correctedDecMinutes, $correctedDecSeconds) = PA_Coord\correct_for_precession($raHour, $raMinutes, $raSeconds, $decDeg, $decMinutes, $decSeconds, $epoch1Day, $epoch1Month, $epoch1Year, $epoch2Day, $epoch2Month, $epoch2Year);

        descriptive_assert("[{$title}] Corrected RA Hour", $correctedRAHour, $expectedCorrectedRAHour);
        descriptive_assert("[{$title}] Corrected RA Minutes", $correctedRAMinutes, $expectedCorrectedRAMinutes);
        descriptive_assert("[{$title}] Corrected RA Seconds", $correctedRASeconds, $expectedCorrectedRASeconds);
        descriptive_assert("[{$title}] Corrected Declination Degrees", $correctedDecDeg, $expectedCorrectedDecDeg);
        descriptive_assert("[{$title}] Corrected Declination Minutes", $correctedDecMinutes, $expectedCorrectedDecMinutes);
        descriptive_assert("[{$title}] Corrected Declination Seconds", $correctedDecSeconds, $expectedCorrectedDecSeconds);

        echo "[{$title}] PASSED\n";
    }

    function nutation_in_ecliptic_longitude_and_obliquity($greenwichDay, $greenwichMonth, $greenwichYear, $expectedNutInLongDeg, $expectedNutInOblDeg)
    {
        $title = "Nutation in Ecliptic Longitude and Obliquity";

        list($nutInLongDeg, $nutInOblDeg) = PA_Coord\nutation_in_ecliptic_longitude_and_obliquity($greenwichDay, $greenwichMonth, $greenwichYear, $expectedNutInLongDeg, $expectedNutInOblDeg);

        descriptive_assert("[{$title}] Nuation in Longitude Degrees", round($nutInLongDeg, 9), $expectedNutInLongDeg);
        descriptive_assert("[{$title}] Nuation in Obliquity Degrees", round($nutInOblDeg, 7), $expectedNutInOblDeg);

        echo "[{$title}] PASSED\n";
    }

    function correct_for_aberration($utHour, $utMinutes, $utSeconds, $gwDay, $gwMonth, $gwYear, $trueEclLongDeg, $trueEclLongMin, $trueEclLongSec, $trueEclLatDeg, $trueEclLatMin, $trueEclLatSec, $expectedApparentEclLongDeg, $expectedApparentEclLongMin, $expectedApparentEclLongSec, $expectedApparentEclLatDeg, $expectedApparentEclLatMin, $expectedApparentEclLatSec)
    {
        $title = "Correct for Aberration";

        list($apparentEclLongDeg, $apparentEclLongMin, $apparentEclLongSec, $apparentEclLatDeg, $apparentEclLatMin, $apparentEclLatSec) = PA_Coord\correct_for_aberration($utHour, $utMinutes, $utSeconds, $gwDay, $gwMonth, $gwYear, $trueEclLongDeg, $trueEclLongMin, $trueEclLongSec, $trueEclLatDeg, $trueEclLatMin, $trueEclLatSec);

        descriptive_assert("[{$title}] Apparent Ecliptic Longitude Degrees", $apparentEclLongDeg, $expectedApparentEclLongDeg);
        descriptive_assert("[{$title}] Apparent Ecliptic Longitude Minutes", $apparentEclLongMin, $expectedApparentEclLongMin);
        descriptive_assert("[{$title}] Apparent Ecliptic Longitude Seconds", $apparentEclLongSec, $expectedApparentEclLongSec);
        descriptive_assert("[{$title}] Apparent Ecliptic Latitude Degrees", $apparentEclLatDeg, $expectedApparentEclLatDeg);
        descriptive_assert("[{$title}] Apparent Ecliptic Latitude Minutes", $apparentEclLatMin, $expectedApparentEclLatMin);
        descriptive_assert("[{$title}] Apparent Ecliptic Latitude Seconds", $apparentEclLatSec, $expectedApparentEclLatSec);

        echo "[{$title}] PASSED\n";
    }

    function atmospheric_refraction($trueRAHour, $trueRAMin, $trueRASec, $trueDecDeg, $trueDecMin, $trueDecSec, PA_Types\CoordinateType $coordinateType, $geogLongDeg, $geogLatDeg, $daylightSavingHours, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $lctHour, $lctMin, $lctSec, $atmosphericPressureMbar, $atmosphericTemperatureCelsius, $expectedCorrectedRAHour, $expectedCorrectedRAMin, $expectedCorrectedRASec, $expectedCorrectedDecDeg, $expectedCorrectedDecMin, $expectedCorrectedDecSec)
    {
        $title = "Atmospheric Refraction";

        list($correctedRAHour, $correctedRAMin, $correctedRASec, $correctedDecDeg, $correctedDecMin, $correctedDecSec) = PA_Coord\atmospheric_refraction($trueRAHour, $trueRAMin, $trueRASec, $trueDecDeg, $trueDecMin, $trueDecSec, $coordinateType, $geogLongDeg, $geogLatDeg, $daylightSavingHours, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $lctHour, $lctMin, $lctSec, $atmosphericPressureMbar, $atmosphericTemperatureCelsius);

        descriptive_assert("[{$title}] Corrected RA Hour", $correctedRAHour, $expectedCorrectedRAHour);
        descriptive_assert("[{$title}] Corrected RA Minutes", $correctedRAMin, $expectedCorrectedRAMin);
        descriptive_assert("[{$title}] Corrected RA Seconds", $correctedRASec, $expectedCorrectedRASec);
        descriptive_assert("[{$title}] Corrected Declination Degrees", $correctedDecDeg, $expectedCorrectedDecDeg);
        descriptive_assert("[{$title}] Corrected Declination Minutes", $correctedDecMin, $expectedCorrectedDecMin);
        descriptive_assert("[{$title}] Corrected Declination Seconds", $correctedDecSec, $expectedCorrectedDecSec);

        echo "[{$title}] PASSED\n";
    }

    function corrections_for_geocentric_parallax($raHour, $raMin, $raSec, $decDeg, $decMin, $decSec, PA_Types\CoordinateType $coordinateType, $equatorialHorParallaxDeg, $geogLongDeg, $geogLatDeg, $heightM, $daylightSaving, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $lctHour, $lctMin, $lctSec, $expectedCorrectedRAHour, $expectedCorrectedRAMin, $expectedCorrectedRASec, $expectedCorrectedDecDeg, $expectedCorrectedDecMin, $expectedCorrectedDecSec)
    {
        $title = "Corrections for Geocentric Parallax";

        list($correctedRAHour, $correctedRAMin, $correctedRASec, $correctedDecDeg, $correctedDecMin, $correctedDecSec) = PA_Coord\corrections_for_geocentric_parallax($raHour, $raMin, $raSec, $decDeg, $decMin, $decSec, $coordinateType, $equatorialHorParallaxDeg, $geogLongDeg, $geogLatDeg, $heightM, $daylightSaving, $timezoneHours, $lcdDay, $lcdMonth, $lcdYear, $lctHour, $lctMin, $lctSec);

        descriptive_assert("[{$title}] Corrected RA Hour", $correctedRAHour, $expectedCorrectedRAHour);
        descriptive_assert("[{$title}] Corrected RA Minutes", $correctedRAMin, $expectedCorrectedRAMin);
        descriptive_assert("[{$title}] Corrected RA Seconds", $correctedRASec, $expectedCorrectedRASec);
        descriptive_assert("[{$title}] Corrected Declination Hour", $correctedDecDeg, $expectedCorrectedDecDeg);
        descriptive_assert("[{$title}] Corrected Declination Minutes", $correctedDecMin, $expectedCorrectedDecMin);
        descriptive_assert("[{$title}] Corrected Declination Seconds", $correctedDecSec, $expectedCorrectedDecSec);

        echo "[{$title}] PASSED\n";
    }

    function heliographic_coordinates($helioPositionAngleDeg, $helioDisplacementArcmin, $gwdateDay, $gwdateMonth, $gwdateYear, $expectedHelioLongDeg, $expectedHelioLatDeg)
    {
        $title = "Heliographic Coordinates";

        list($helioLongDeg, $helioLatDeg) = PA_Coord\heliographic_coordinates($helioPositionAngleDeg, $helioDisplacementArcmin, $gwdateDay, $gwdateMonth, $gwdateYear);

        descriptive_assert("[{$title}] Heliographic Longitude Degrees", $helioLongDeg, $expectedHelioLongDeg);
        descriptive_assert("[{$title}] Heliographic Latitude Degrees", $helioLatDeg, $expectedHelioLatDeg);

        echo "[{$title}] PASSED\n";
    }

    function carrington_rotation_number($gwdateDay, $gwdateMonth, $gwdateYear, $expectedCrn)
    {
        $title = "Carrington Rotation Number";

        $crn = PA_Coord\carrington_rotation_number($gwdateDay, $gwdateMonth, $gwdateYear);

        descriptive_assert("[{$title}] Carrington Rotation Number", $crn, $expectedCrn);

        echo "[{$title}] PASSED\n";
    }

    function selenographic_coordinates1($gwdateDay, $gwdateMonth, $gwdateYear, $expectedSubEarthLongitude, $expectedSubEarthLatitude, $expectedPositionAngleOfPole)
    {
        $title = "Selenograpic Coordinates 1";

        list($subEarthLongitude, $subEarthLatitude, $positionAngleOfPole) = PA_Coord\selenographic_coordinates1($gwdateDay, $gwdateMonth, $gwdateYear);

        descriptive_assert("[{$title}] Sub-Earth Longitude", $subEarthLongitude, $expectedSubEarthLongitude);
        descriptive_assert("[{$title}] Sub-Earth Latitude", $subEarthLatitude, $expectedSubEarthLatitude);
        descriptive_assert("[{$title}] Position Angle of Pole", $positionAngleOfPole, $expectedPositionAngleOfPole);

        echo "[{$title}] PASSED\n";
    }

    function selenographic_coordinates2($gwdateDay, $gwdateMonth, $gwdateYear, $expectedSubSolarLongitude, $expectedSubSolarColongitude, $expectedSubSolarLatitude)
    {
        $title = "Selenograpic Coordinates 2";

        list($subSolarLongitude, $subSolarColongitude, $subSolarLatitude) = PA_Coord\selenographic_coordinates2($gwdateDay, $gwdateMonth, $gwdateYear);

        descriptive_assert("[{$title}] Sub-Solar Longitude", $subSolarLongitude, $expectedSubSolarLongitude);
        descriptive_assert("[{$title}] Sub-Solar Co-Longitude", $subSolarColongitude, $expectedSubSolarColongitude);
        descriptive_assert("[{$title}] Sub-Solar Latitude", $subSolarLatitude, $expectedSubSolarLatitude);

        echo "[{$title}] PASSED\n";
    }

    angle_to_decimal_degrees(182, 31, 27, 182.524167);

    decimal_degrees_to_angle(182.524167, 182, 31, 27);

    right_ascension_to_hour_angle(18, 32, 21, 14, 36, 51.67, false, -4, 22, 4, 1980, -64, 9, 52, 23.66);

    hour_angle_to_right_ascension(9, 52, 23.66, 14, 36, 51.67, false, -4, 22, 4, 1980, -64, 18, 32, 21);

    equatorial_coordinates_to_horizon_coordinates(5, 51, 44, 23, 13, 10, 52, 283, 16, 15.7, 19, 20, 3.64);

    horizon_coordinates_to_equatorial_coordinates(283, 16, 15.7, 19, 20, 3.64, 52, 5, 51, 44, 23, 13, 10);

    mean_obliquity_of_the_ecliptic(6, 7, 2009, 23.43805531);

    ecliptic_coordinate_to_equatorial_coordinate(139, 41, 10, 4, 52, 31, 6, 7, 2009, 9, 34, 53.4, 19, 32, 8.52);

    equatorial_coordinate_to_ecliptic_coordinate(9, 34, 53.4, 19, 32, 8.52, 6, 7, 2009, 139, 41, 9.97, 4, 52, 30.99);

    equatorial_coordinate_to_galactic_coordinate(10, 21, 0, 10, 3, 11, 232, 14, 52.38, 51, 7, 20.16);

    galactic_coordinate_to_equatorial_coordinate(232, 14, 52.38, 51, 7, 20.16, 10, 21, 0, 10, 3, 11);

    angle_between_two_objects(5, 13, 31.7, -8, 13, 30, 6, 44, 13.4, -16, 41, 11, PA_Types\AngleMeasure::Hours, 23, 40, 25.86);

    rising_and_setting(23, 39, 20, 21, 42, 0, 24, 8, 2010, 64, 30, 0.5667, PA_Types\RiseSetStatus::OK, 14, 16, 4, 10, 64.36, 295.64);

    correct_for_precession(9, 10, 43, 14, 23, 25, 0.923, 1, 1950, 1, 6, 1979, 9, 12, 20.18, 14, 16, 9.12);

    nutation_in_ecliptic_longitude_and_obliquity(1, 9, 1988, 0.001525808, 0.0025671);

    correct_for_aberration(0, 0, 0, 8, 9, 1988, 352, 37, 10.1, -1, 32, 56.4, 352, 37, 30.45, -1, 32, 56.33);

    atmospheric_refraction(23, 14, 0, 40, 10, 0, PA_Types\CoordinateType::True, 0.17, 51.2036110, 0, 0, 23, 3, 1987, 1, 1, 24, 1012, 21.7, 23, 13, 44.74, 40, 19, 45.76);

    corrections_for_geocentric_parallax(22, 35, 19, -7, 41, 13, PA_Types\CoordinateType::True, 1.019167, -100, 50, 60, 0, -6, 26, 2, 1979, 10, 45, 0, 22, 36, 43.22, -8, 32, 17.4);

    heliographic_coordinates(220, 10.5, 1, 5, 1988, 142.59, -19.94);

    carrington_rotation_number(27, 1, 1975, 1624);

    selenographic_coordinates1(1, 5, 1988, -4.88, 4.04, 19.78);

    selenographic_coordinates2(1, 5, 1988, 6.81, 83.19, 1.19);
}

namespace PA\Test\DateTime {
    include_once 'PAAll.php';

    use PA\DateTime as PA_DateTime;

    use function PA\Utils\descriptive_assert;

    function date_of_easter($inputYear, $expectedMonth, $expectedDay)
    {
        $title = "Date of Easter";

        list($month, $day, $year) = PA_DateTime\get_date_of_easter($inputYear);

        descriptive_assert("[{$title}] Month", $month, $expectedMonth);
        descriptive_assert("[{$title}] Day", $day, $expectedDay);
        descriptive_assert("[{$title}] Year", $year, $inputYear);

        echo "[{$title}] PASSED\n";
    }

    function civil_date_to_day_number($month, $day, $year, $expectedDayNumber)
    {
        $title = "Civil Date to Day Number";

        $dayNumber = PA_DateTime\civil_date_to_day_number($month, $day, $year);

        descriptive_assert("[{$title}] Day Number", $dayNumber, $expectedDayNumber);

        echo "[{$title}] ({$expectedDayNumber}) PASSED\n";
    }

    function civil_time_to_decimal_hours($hours, $minutes, $seconds, $expectedDecimalHours)
    {
        $title = "Civil Time to Decimal Hours";

        $decimalHours = round(PA_DateTime\civil_time_to_decimal_hours($hours, $minutes, $seconds), 8);

        descriptive_assert("[{$title}] Decimal Hours", $decimalHours, $expectedDecimalHours);

        echo "[{$title}] PASSED\n";
    }

    function decimal_hours_to_civil_time($decimalHours, $expectedHours, $expectedMinutes, $expectedSeconds)
    {
        $title = "Decimal Hours to Civil Time";

        list($hours, $minutes, $seconds) = PA_DateTime\decimal_hours_to_civil_time($decimalHours);

        descriptive_assert("[{$title}] Hours", $hours, $expectedHours);
        descriptive_assert("[{$title}] Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title}] Minutes", $seconds, $expectedSeconds);

        echo "[{$title}] PASSED\n";
    }

    function local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $isDaylightSavings, $zoneCorrection, $localDay, $localMonth, $localYear, $expectedHours, $expectedMinutes, $expectedSeconds, $expectedDay, $expectedMonth, $expectedYear)
    {
        $title = "Local Civil Time to Universal Time";

        list($hours, $minutes, $seconds, $day, $month, $year) = PA_DateTime\local_civil_time_to_universal_time($lctHours, $lctMinutes, $lctSeconds, $isDaylightSavings, $zoneCorrection, $localDay, $localMonth, $localYear);

        descriptive_assert("[{$title}] Hours", $hours, $expectedHours);
        descriptive_assert("[{$title}] Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title}] Seconds", $seconds, $expectedSeconds);
        descriptive_assert("[{$title}] Day", $day, $expectedDay);
        descriptive_assert("[{$title}] Month", $month, $expectedMonth);
        descriptive_assert("[{$title}] Year", $year, $expectedYear);

        echo "[{$title}] PASSED\n";
    }

    function universal_time_to_local_civil_time_dt($utHours, $utMinutes, $utSeconds, $isDaylightSavings, $zoneCorrection, $gwDay, $gwMonth, $gwYear, $expectedHours, $expectedMinutes, $expectedSeconds, $expectedDay, $expectedMonth, $expectedYear)
    {
        $title = "Universal Time to Local Civil Time";

        list($hours, $minutes, $seconds, $day, $month, $year) = PA_DateTime\universal_time_to_local_civil_time_dt($utHours, $utMinutes, $utSeconds, $isDaylightSavings, $zoneCorrection, $gwDay, $gwMonth, $gwYear);

        descriptive_assert("[{$title}] Hours", $hours, $expectedHours);
        descriptive_assert("[{$title}] Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title}] Seconds", $seconds, $expectedSeconds);
        descriptive_assert("[{$title}] Day", $day, $expectedDay);
        descriptive_assert("[{$title}] Month", $month, $expectedMonth);
        descriptive_assert("[{$title}] Year", $year, $expectedYear);

        echo "[{$title}] PASSED\n";
    }

    function universal_time_to_greenwich_sidereal_time($utHours, $utMinutes, $utSeconds, $gwDay, $gwMonth, $gwYear, $expectedHours, $expectedMinutes, $expectedSeconds)
    {
        $title = "Universal Time to Greenwich Sidereal Time";

        list($hours, $minutes, $seconds) = PA_DateTime\universal_time_to_greenwich_sidereal_time($utHours, $utMinutes, $utSeconds, $gwDay, $gwMonth, $gwYear);

        descriptive_assert("[{$title}] Hours", $hours, $expectedHours);
        descriptive_assert("[{$title}] Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title}] Seconds", $seconds, $expectedSeconds);

        echo "[{$title}] PASSED\n";
    }

    function greenwich_sidereal_time_to_universal_time($gstHours, $gstMinutes, $gstSeconds, $gwDay, $gwMonth, $gwYear, $expectedHours, $expectedMinutes, $expectedSeconds, $expectedWarningFlag)
    {
        $title = "Greenwich Sidereal Time to Universal Time";

        list($hours, $minutes, $seconds, $warningFlag) = PA_DateTime\greenwich_sidereal_time_to_universal_time($gstHours, $gstMinutes, $gstSeconds, $gwDay, $gwMonth, $gwYear);

        descriptive_assert("[{$title}] Hours", $hours, $expectedHours);
        descriptive_assert("[{$title}] Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title}] Seconds", $seconds, $expectedSeconds);
        descriptive_assert("[{$title}] Warning Flag", $warningFlag, $expectedWarningFlag);

        echo "[{$title}] PASSED\n";
    }

    function greenwich_sidereal_time_to_local_sidereal_time($gstHours, $gstMinutes, $gstSeconds, $geographicalLongitude, $expectedHours, $expectedMinutes, $expectedSeconds)
    {
        $title = "Greenwich Sidereal Time to Local Sidereal Time";

        list($hours, $minutes, $seconds)  = PA_DateTime\greenwich_sidereal_time_to_local_sidereal_time($gstHours, $gstMinutes, $gstSeconds, $geographicalLongitude);

        descriptive_assert("[{$title} Hours", $hours, $expectedHours);
        descriptive_assert("[{$title} Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title} Seconds", $seconds, $expectedSeconds);

        echo "[{$title}] PASSED\n";
    }

    function local_sidereal_time_to_greenwich_sidereal_time($lstHours, $lstMinutes, $lstSeconds, $geographicalLongitude, $expectedHours, $expectedMinutes, $expectedSeconds)
    {
        $title = "Local Sidereal Time to Greenwich Sidereal Time";

        list($hours, $minutes, $seconds) = PA_DateTime\local_sidereal_time_to_greenwich_sidereal_time($lstHours, $lstMinutes, $lstSeconds, $geographicalLongitude);

        descriptive_assert("[{$title} Hours", $hours, $expectedHours);
        descriptive_assert("[{$title} Minutes", $minutes, $expectedMinutes);
        descriptive_assert("[{$title} Seconds", $seconds, $expectedSeconds);

        echo "[{$title}] PASSED\n";
    }

    date_of_easter(2023, 4, 9);

    civil_date_to_day_number(1, 1, 2000, 1);
    civil_date_to_day_number(3, 1, 2000, 61);
    civil_date_to_day_number(6, 1, 2003, 152);
    civil_date_to_day_number(11, 27, 2009, 331);

    civil_time_to_decimal_hours(18, 31, 27, 18.52416667);

    decimal_hours_to_civil_time(18.52416667, 18, 31, 27);

    local_civil_time_to_universal_time(3.0, 37.0, 0.0, true, 4, 1.0, 7, 2013, 22, 37, 0, 30, 6, 2013);

    universal_time_to_local_civil_time_dt(22, 37, 0, true, 4, 30, 6, 2013, 3, 37, 0, 1, 7, 2013);

    universal_time_to_greenwich_sidereal_time(14, 36, 51.67, 22, 4, 1980, 4, 40, 5.23);

    greenwich_sidereal_time_to_universal_time(4, 40, 5.23, 22, 4, 1980, 14, 36, 51.67, "OK");

    greenwich_sidereal_time_to_local_sidereal_time(4, 40, 5.23, -64, 0, 24, 5.23);

    local_sidereal_time_to_greenwich_sidereal_time(0, 24, 5.23, -64, 4, 40, 5.23);
}

namespace PA\Test\Eclipses {
    include_once 'PAAll.php';

    use PA\Eclipses as PA_Eclipses;
    use PA\Types\EclipseOccurrence;

    use function PA\Utils\descriptive_assert;

    function lunar_eclipse_occurrence_details($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $expected_status, $expected_eventDateDay, $expected_eventDateMonth, $expected_eventDateYear)
    {
        $title = "Lunar Eclipse Occurrence";

        list($status, $eventDateDay, $eventDateMonth, $eventDateYear) =
            PA_Eclipses\lunar_eclipse_occurrence_details($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours);

        descriptive_assert("[{$title}] Status", $status->value, $expected_status->value);
        descriptive_assert("[{$title}] Event Date - Day", $eventDateDay, $expected_eventDateDay);
        descriptive_assert("[{$title}] Event Date - Month", $eventDateMonth, $expected_eventDateMonth);
        descriptive_assert("[{$title}] Event Date - Year", $eventDateYear, $expected_eventDateYear);

        echo "[{$title}] PASSED\n";
    }

    function lunar_eclipse_circumstances($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $expected_lunarEclipseCertainDateDay, $expected_lunarEclipseCertainDateMonth, $expected_lunarEclipseCertainDateYear, $expected_utStartPenPhaseHour, $expected_utStartPenPhaseMinutes, $expected_utStartUmbralPhaseHour, $expected_utStartUmbralPhaseMinutes, $expected_utStartTotalPhaseHour, $expected_utStartTotalPhaseMinutes, $expected_utMidEclipseHour, $expected_utMidEclipseMinutes, $expected_utEndTotalPhaseHour, $expected_utEndTotalPhaseMinutes, $expected_utEndUmbralPhaseHour, $expected_utEndUmbralPhaseMinutes, $expected_utEndPenPhaseHour, $expected_utEndPenPhaseMinutes, $expected_eclipseMagnitude)
    {
        $title = "Lunar Eclipse Circumstances";

        list($lunarEclipseCertainDateDay, $lunarEclipseCertainDateMonth, $lunarEclipseCertainDateYear, $utStartPenPhaseHour, $utStartPenPhaseMinutes, $utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes, $utStartTotalPhaseHour, $utStartTotalPhaseMinutes, $utMidEclipseHour, $utMidEclipseMinutes, $utEndTotalPhaseHour, $utEndTotalPhaseMinutes, $utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes, $utEndPenPhaseHour, $utEndPenPhaseMinutes, $eclipseMagnitude) =
            PA_Eclipses\lunar_eclipse_circumstances($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours);

        descriptive_assert("[{$title}] Date - Day", $lunarEclipseCertainDateDay, $expected_lunarEclipseCertainDateDay);
        descriptive_assert("[{$title}] Date - Month", $lunarEclipseCertainDateMonth, $expected_lunarEclipseCertainDateMonth);
        descriptive_assert("[{$title}] Date - Year", $lunarEclipseCertainDateYear, $expected_lunarEclipseCertainDateYear);
        descriptive_assert("[{$title}] Start Penumbral Phase - Hour", $utStartPenPhaseHour, $expected_utStartPenPhaseHour);
        descriptive_assert("[{$title}] Start Penumbral Phase - Minutes", $utStartPenPhaseMinutes, $expected_utStartPenPhaseMinutes);
        descriptive_assert("[{$title}] Start Umbral Phase - Hour", $utStartUmbralPhaseHour, $expected_utStartUmbralPhaseHour);
        descriptive_assert("[{$title}] Start Umbral Phase - Minutes", $utStartUmbralPhaseMinutes, $expected_utStartUmbralPhaseMinutes);
        descriptive_assert("[{$title}] Start Total Phase - Hour", $utStartTotalPhaseHour, $expected_utStartTotalPhaseHour);
        descriptive_assert("[{$title}] Start Total Phase - Minutes", $utStartTotalPhaseMinutes, $expected_utStartTotalPhaseMinutes);
        descriptive_assert("[{$title}] Mid-Eclipse - Hour", $utMidEclipseHour, $expected_utMidEclipseHour);
        descriptive_assert("[{$title}] Mid-Eclipse - Minutes", $utMidEclipseMinutes, $expected_utMidEclipseMinutes);
        descriptive_assert("[{$title}] End Total Phase - Hour", $utEndTotalPhaseHour, $expected_utEndTotalPhaseHour);
        descriptive_assert("[{$title}] End Total Phase - Minutes", $utEndTotalPhaseMinutes, $expected_utEndTotalPhaseMinutes);
        descriptive_assert("[{$title}] End Umbral Phase - Hour", $utEndUmbralPhaseHour, $expected_utEndUmbralPhaseHour);
        descriptive_assert("[{$title}] End Umbral Phase - Minutes", $utEndUmbralPhaseMinutes, $expected_utEndUmbralPhaseMinutes);
        descriptive_assert("[{$title}] End Penumbral Phase - Hour", $utEndPenPhaseHour, $expected_utEndPenPhaseHour);
        descriptive_assert("[{$title}] End Penumbral Phase - Minutes", $utEndPenPhaseMinutes, $expected_utEndPenPhaseMinutes);
        descriptive_assert("[{$title}] Magnitude", $eclipseMagnitude, $expected_eclipseMagnitude);

        echo "[{$title}] PASSED\n";
    }

    function solar_eclipse_occurrence($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $expected_status, $expected_eventDateDay, $expected_eventDateMonth, $expected_eventDateYear)
    {
        $title = "Solar Eclipse Occurrence";

        list($status, $eventDateDay, $eventDateMonth, $eventDateYear) =
            PA_Eclipses\solar_eclipse_occurrence($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours);

        descriptive_assert("[{$title}] Status", $status->value, $expected_status->value);
        descriptive_assert("[{$title}] Event Date - Day", $eventDateDay, $expected_eventDateDay);
        descriptive_assert("[{$title}] Event Date - Month", $eventDateMonth, $expected_eventDateMonth);
        descriptive_assert("[{$title}] Event Date - Year", $eventDateYear, $expected_eventDateYear);

        echo "[{$title}] PASSED\n";
    }

    function solar_eclipse_circumstances($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg, $expected_solarEclipseCertainDateDay, $expected_solarEclipseCertainDateMonth, $expected_solarEclipseCertainDateYear, $expected_utFirstContactHour, $expected_utFirstContactMinutes, $expected_utMidEclipseHour, $expected_utMidEclipseMinutes, $expected_utLastContactHour, $expected_utLastContactMinutes, $expected_eclipseMagnitude)
    {
        $title = "Solar Eclipse Circumstances";

        list($solarEclipseCertainDateDay, $solarEclipseCertainDateMonth, $solarEclipseCertainDateYear, $utFirstContactHour, $utFirstContactMinutes, $utMidEclipseHour, $utMidEclipseMinutes, $utLastContactHour, $utLastContactMinutes, $eclipseMagnitude) =
            PA_Eclipses\solar_eclipse_circumstances($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $geogLongitudeDeg, $geogLatitudeDeg);

        descriptive_assert("[{$title}] Certain Date - Day", $solarEclipseCertainDateDay, $expected_solarEclipseCertainDateDay);
        descriptive_assert("[{$title}] Certain Date - Month", $solarEclipseCertainDateMonth, $expected_solarEclipseCertainDateMonth);
        descriptive_assert("[{$title}] Certain Date - Year", $solarEclipseCertainDateYear, $expected_solarEclipseCertainDateYear);
        descriptive_assert("[{$title}] First Contact - Hour", $utFirstContactHour, $expected_utFirstContactHour);
        descriptive_assert("[{$title}] First Contact - Minutes", $utFirstContactMinutes, $expected_utFirstContactMinutes);
        descriptive_assert("[{$title}] Mid-Eclipse - Hour", $utMidEclipseHour, $expected_utMidEclipseHour);
        descriptive_assert("[{$title}] Mid-Eclipse - Minutes", $utMidEclipseMinutes, $expected_utMidEclipseMinutes);
        descriptive_assert("[{$title}] Last Contact - Hour", $utLastContactHour, $expected_utLastContactHour);
        descriptive_assert("[{$title}] Last Contact - Minutes", $utLastContactMinutes, $expected_utLastContactMinutes);
        descriptive_assert("[{$title}] Magnitude", $eclipseMagnitude, $expected_eclipseMagnitude);

        echo "[{$title}] PASSED\n";
    }

    lunar_eclipse_occurrence_details(1, 4, 2015, false, 10, EclipseOccurrence::EclipseCertain, 4, 4, 2015);

    lunar_eclipse_circumstances(1, 4, 2015, false, 10, 4, 4, 2015, 9, 0, 10, 16, 11, 55, 12, 1, 12, 7, 13, 46, 15, 1, 1.01);

    solar_eclipse_occurrence(1, 4, 2015, false, 0, EclipseOccurrence::EclipseCertain, 20, 3, 2015);

    solar_eclipse_circumstances(20, 3, 2015, false, 0, 0, 68.65, 20, 3, 2015, 8, 55, 9, 57, 10, 58, 1.016);
}

namespace PA\Test\Moon {
    include_once 'PAAll.php';

    use PA\Moon as PA_Moon;
    use PA\Types\AccuracyLevel;

    use function PA\Utils\descriptive_assert;

    function approximate_position_of_moon($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $expected_moonRAHour, $expected_moonRAMin, $expected_moonRASec, $expected_moonDecDeg, $expected_moonDecMin, $expected_moonDecSec)
    {
        $title = "Approximate Position of Moon";

        list($moonRAHour, $moonRAMin, $moonRASec, $moonDecDeg, $moonDecMin, $moonDecSec) =
            PA_Moon\approximate_position_of_moon($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        descriptive_assert("[{$title}] RA Hour", $moonRAHour, $expected_moonRAHour);
        descriptive_assert("[{$title}] RA Minutes", $moonRAMin, $expected_moonRAMin);
        descriptive_assert("[{$title}] RA Seconds", $moonRASec, $expected_moonRASec);
        descriptive_assert("[{$title}] Declination Degrees", $moonDecDeg, $expected_moonDecDeg);
        descriptive_assert("[{$title}] Declination Minutes", $moonDecMin, $expected_moonDecMin);
        descriptive_assert("[{$title}] Declination Seconds", $moonDecSec, $expected_moonDecSec);

        echo "[{$title}] PASSED\n";
    }

    function precise_position_of_moon($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $expected_moonRAHour, $expected_moonRAMin, $expected_moonRASec, $expected_moonDecDeg, $expected_moonDecMin, $expected_moonDecSec, $expected_earthMoonDistKM, $expected_moonHorParallaxDeg)
    {
        $title = "Precise Position of Moon";

        list($moonRAHour, $moonRAMin, $moonRASec, $moonDecDeg, $moonDecMin, $moonDecSec, $earthMoonDistKM, $moonHorParallaxDeg) =
            PA_Moon\precise_position_of_moon($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        descriptive_assert("[{$title}] RA Hour", $moonRAHour, $expected_moonRAHour);
        descriptive_assert("[{$title}] RA Minutes", $moonRAMin, $expected_moonRAMin);
        descriptive_assert("[{$title}] RA Seconds", $moonRASec, $expected_moonRASec);
        descriptive_assert("[{$title}] Declination Degrees", $moonDecDeg, $expected_moonDecDeg);
        descriptive_assert("[{$title}] Declination Minutes", $moonDecMin, $expected_moonDecMin);
        descriptive_assert("[{$title}] Declination Seconds", $moonDecSec, $expected_moonDecSec);
        descriptive_assert("[{$title}] Earth-Moon Distance", $earthMoonDistKM, $expected_earthMoonDistKM);
        descriptive_assert("[{$title}] Horizontal Parallax", $moonHorParallaxDeg, $expected_moonHorParallaxDeg);

        echo "[{$title}] PASSED\n";
    }

    function moon_phase($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $accuracyLevel, $expected_moonPhase, $expected_paBrightLimbDeg)
    {
        $title = "Moon Phase";

        list($moonPhase, $paBrightLimbDeg) =
            PA_Moon\moon_phase($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $accuracyLevel);

        descriptive_assert("[{$title}] Moon Phase", $moonPhase, $expected_moonPhase);
        descriptive_assert("[{$title}] Bright Limb degrees", $paBrightLimbDeg, $expected_paBrightLimbDeg);

        echo "[{$title}] PASSED\n";
    }

    function times_of_new_moon_and_full_moon($isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $expected_nmLocalTimeHour, $expected_nmLocalTimeMin, $expected_nmLocalDateDay, $expected_nmLocalDateMonth, $expected_nmLocalDateYear, $expected_fmLocalTimeHour, $expected_fmLocalTimeMin, $expected_fmLocalDateDay, $expected_fmLocalDateMonth, $expected_fmLocalDateYear)
    {
        $title = "Times of New Moon and Full Moon";

        list($nmLocalTimeHour, $nmLocalTimeMin, $nmLocalDateDay, $nmLocalDateMonth, $nmLocalDateYear, $fmLocalTimeHour, $fmLocalTimeMin, $fmLocalDateDay, $fmLocalDateMonth, $fmLocalDateYear) =
            PA_Moon\times_of_new_moon_and_full_moon($isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        descriptive_assert("[{$title}] New Moon - Local Time - Hours", $nmLocalTimeHour, $expected_nmLocalTimeHour);
        descriptive_assert("[{$title}] New Moon - Local Time - Minutes", $nmLocalTimeMin, $expected_nmLocalTimeMin);
        descriptive_assert("[{$title}] New Moon - Local Date - Day", $nmLocalDateDay, $expected_nmLocalDateDay);
        descriptive_assert("[{$title}] New Moon - Local Date - Month", $nmLocalDateMonth, $expected_nmLocalDateMonth);
        descriptive_assert("[{$title}] New Moon - Local Date - Year", $nmLocalDateYear, $expected_nmLocalDateYear);
        descriptive_assert("[{$title}] Full Moon - Local Time - Hours", $fmLocalTimeHour, $expected_fmLocalTimeHour);
        descriptive_assert("[{$title}] Full Moon - Local Time - Minutes", $fmLocalTimeMin, $expected_fmLocalTimeMin);
        descriptive_assert("[{$title}] Full Moon - Local Date - Day", $fmLocalDateDay, $expected_fmLocalDateDay);
        descriptive_assert("[{$title}] Full Moon - Local Date - Month", $fmLocalDateMonth, $expected_fmLocalDateMonth);
        descriptive_assert("[{$title}] Full Moon - Local Date - Year", $fmLocalDateYear, $expected_fmLocalDateYear);

        echo "[{$title}] PASSED\n";
    }

    function moon_dist_ang_diam_hor_parallax($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $expected_earthMoonDist, $expected_angDiameterDeg, $expected_angDiameterMin, $expected_horParallaxDeg, $expected_horParallaxMin, $expected_horParallaxSec)
    {
        $title = "Moon Distance, Angular Diameter, and Horizontal Parallax";

        list($earthMoonDist, $angDiameterDeg, $angDiameterMin, $horParallaxDeg, $horParallaxMin, $horParallaxSec) =
            PA_Moon\moon_dist_ang_diam_hor_parallax($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear);

        descriptive_assert("[{$title}] Distance", $earthMoonDist, $expected_earthMoonDist);
        descriptive_assert("[{$title}] Angular Diameter degrees", $angDiameterDeg, $expected_angDiameterDeg);
        descriptive_assert("[{$title}] Angular Diameter minutes", $angDiameterMin, $expected_angDiameterMin);
        descriptive_assert("[{$title}] Horizontal Parallax degrees", $horParallaxDeg, $expected_horParallaxDeg);
        descriptive_assert("[{$title}] Horizontal Parallax minutes", $horParallaxMin, $expected_horParallaxMin);
        descriptive_assert("[{$title}] Horizontal Parallax seconds", $horParallaxSec, $expected_horParallaxSec);

        echo "[{$title}] PASSED\n";
    }

    function moonrise_and_moonset($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg, $expected_mrLTHour, $expected_mrLTMin, $expected_mrLocalDateDay, $expected_mrLocalDateMonth, $expected_mrLocalDateYear, $expected_mrAzimuthDeg, $expected_msLTHour, $expected_msLTMin, $expected_msLocalDateDay, $expected_msLocalDateMonth, $expected_msLocalDateYear, $expected_msAzimuthDeg)
    {
        $title = "Moonrise and Moonset";

        list($mrLTHour, $mrLTMin, $mrLocalDateDay, $mrLocalDateMonth, $mrLocalDateYear, $mrAzimuthDeg, $msLTHour, $msLTMin, $msLocalDateDay, $msLocalDateMonth, $msLocalDateYear, $msAzimuthDeg) =
            PA_Moon\moonrise_and_moonset($localDateDay, $localDateMonth, $localDateYear, $isDaylightSaving, $zoneCorrectionHours, $geogLongDeg, $geogLatDeg);

        descriptive_assert("[{$title}] Moonrise - Local Time - Hour", $mrLTHour, $expected_mrLTHour);
        descriptive_assert("[{$title}] Moonrise - Local Time - Minutes", $mrLTMin, $expected_mrLTMin);
        descriptive_assert("[{$title}] Moonrise - Local Date - Day", $mrLocalDateDay, $expected_mrLocalDateDay);
        descriptive_assert("[{$title}] Moonrise - Local Date - Month", $mrLocalDateMonth, $expected_mrLocalDateMonth);
        descriptive_assert("[{$title}] Moonrise - Local Date - Year", $mrLocalDateYear, $expected_mrLocalDateYear);
        descriptive_assert("[{$title}] Moonrise - Azimuth degrees", $mrAzimuthDeg, $expected_mrAzimuthDeg);

        descriptive_assert("[{$title}] Moonset - Local Time - Hour", $msLTHour, $expected_msLTHour);
        descriptive_assert("[{$title}] Moonset - Local Time - Minutes", $msLTMin, $expected_msLTMin);
        descriptive_assert("[{$title}] Moonset - Local Date - Day", $msLocalDateDay, $expected_msLocalDateDay);
        descriptive_assert("[{$title}] Moonset - Local Date - Month", $msLocalDateMonth, $expected_msLocalDateMonth);
        descriptive_assert("[{$title}] Moonset - Local Date - Year", $msLocalDateYear, $expected_msLocalDateYear);
        descriptive_assert("[{$title}] Moonset - Azimuth degrees", $msAzimuthDeg, $expected_msAzimuthDeg);

        echo "[{$title}] PASSED\n";
    }

    approximate_position_of_moon(0, 0, 0, false, 0, 1, 9, 2003, 14, 12, 42.31, -11, 31, 38.27);

    precise_position_of_moon(0, 0, 0, false, 0, 1, 9, 2003, 14, 12, 10.21, -11, 34, 57.83, 367964, 0.993191);

    moon_phase(0, 0, 0, false, 0, 1, 9, 2003, AccuracyLevel::Approximate, 0.22, -71.58);

    times_of_new_moon_and_full_moon(false, 0, 1, 9, 2003, 17, 27, 27, 8, 2003, 16, 36, 10, 9, 2003);

    moon_dist_ang_diam_hor_parallax(0, 0, 0, false, 0, 1, 9, 2003, 367964, 0, 32, 0, 59, 35.49);

    moonrise_and_moonset(6, 3, 1986, false, -5, -71.05, 42.3667, 4, 21, 6, 3, 1986, 127.34, 13, 8, 6, 3, 1986, 234.05);
}

namespace PA\Test\Planets {
    include_once 'PAAll.php';

    use PA\Planets as PA_Planets;

    use function PA\Utils\descriptive_assert;

    function approximate_position_of_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName, $expected_planetRAHour, $expected_planetRAMin, $expected_planetRASec, $expected_planetDecDeg, $expected_planetDecMin, $expected_planetDecSec)
    {
        $title = "Approximate Position of Planet";

        list($planetRAHour, $planetRAMin, $planetRASec, $planetDecDeg, $planetDecMin, $planetDecSec) =
            PA_Planets\approximate_position_of_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName);

        descriptive_assert("[{$title}] RA Hour", $planetRAHour, $expected_planetRAHour);
        descriptive_assert("[{$title}] RA Minutes", $planetRAMin, $expected_planetRAMin);
        descriptive_assert("[{$title}] RA Seconds", $planetRASec, $expected_planetRASec);
        descriptive_assert("[{$title}] Declination Degrees", $planetDecDeg, $expected_planetDecDeg);
        descriptive_assert("[{$title}] Declination Minutes", $planetDecMin, $expected_planetDecMin);
        descriptive_assert("[{$title}] Declination Seconds", $planetDecSec, $expected_planetDecSec);

        echo "[{$title}] PASSED\n";
    }

    function precise_position_of_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName, $expected_planetRAHour, $expected_planetRAMin, $expected_planetRASec, $expected_planetDecDeg, $expected_planetDecMin, $expected_planetDecSec)
    {
        $title = "Precise Position of Planet";

        list($planetRAHour, $planetRAMin, $planetRASec, $planetDecDeg, $planetDecMin, $planetDecSec) =
            PA_Planets\precise_position_of_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName);

        descriptive_assert("[{$title}] RA Hour", $planetRAHour, $expected_planetRAHour);
        descriptive_assert("[{$title}] RA Minutes", $planetRAMin, $expected_planetRAMin);
        descriptive_assert("[{$title}] RA Seconds", $planetRASec, $expected_planetRASec);
        descriptive_assert("[{$title}] Declination Degrees", $planetDecDeg, $expected_planetDecDeg);
        descriptive_assert("[{$title}] Declination Minutes", $planetDecMin, $expected_planetDecMin);
        descriptive_assert("[{$title}] Declination Seconds", $planetDecSec, $expected_planetDecSec);

        echo "[{$title}] PASSED\n";
    }

    function visual_aspects_of_a_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName, $expected_distanceAU, $expected_angDiaArcsec, $expected_phase, $expected_lightTimeHour, $expected_lightTimeMinutes, $expected_lightTimeSeconds, $expected_posAngleBrightLimbDeg, $expected_approximateMagnitude)
    {
        $title = "Visual Aspects of a Planet";

        list($distanceAU, $angDiaArcsec, $phase, $lightTimeHour, $lightTimeMinutes, $lightTimeSeconds, $posAngleBrightLimbDeg, $approximateMagnitude) =
            PA_Planets\visual_aspects_of_a_planet($lctHour, $lctMin, $lctSec, $isDaylightSaving, $zoneCorrectionHours, $localDateDay, $localDateMonth, $localDateYear, $planetName);

        descriptive_assert("[{$title}] Distance AU", $distanceAU, $expected_distanceAU);
        descriptive_assert("[{$title}] Angular Diameter arcseconds", $angDiaArcsec, $expected_angDiaArcsec);
        descriptive_assert("[{$title}] Phase", $phase, $expected_phase);
        descriptive_assert("[{$title}] Light Time hours", $lightTimeHour, $expected_lightTimeHour);
        descriptive_assert("[{$title}] Light Time minutes", $lightTimeMinutes, $expected_lightTimeMinutes);
        descriptive_assert("[{$title}] Light Time seconds", $lightTimeSeconds, $expected_lightTimeSeconds);
        descriptive_assert("[{$title}] Position Angle of Bright Limb degrees", $posAngleBrightLimbDeg, $expected_posAngleBrightLimbDeg);
        descriptive_assert("[{$title}] Approximate Magnitude", $approximateMagnitude, $expected_approximateMagnitude);

        echo "[{$title}] PASSED\n";
    }

    approximate_position_of_planet(0, 0, 0, false, 0, 22, 11, 2003, "Jupiter", 11, 11, 13.8, 6, 21, 25.1);

    precise_position_of_planet(0, 0, 0, false, 0, 22, 11, 2003, "Jupiter", 11, 10, 30.99, 6, 25, 49.46);

    visual_aspects_of_a_planet(0, 0, 0, false, 0, 22, 11, 2003, "Jupiter", 5.59829, 35.1, 0.99, 0, 46, 33.32, 113.2, -2.0);
}

namespace PA\Test\Sun {
    include_once 'PAAll.php';

    use PA\Sun as PA_Sun;

    use PA\Types\RiseSetStatus;
    use PA\Types\TwilightStatus;
    use PA\Types\TwilightType;

    use function PA\Utils\descriptive_assert;

    function approximate_position_of_sun($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $expectedSunRAHour, $expectedSunRAMin, $expectedSunRASec, $expectedSunDecDeg, $expectedSunDecMin, $expectedSunDecSec)
    {
        $title = "Approximate Position of Sun";

        list($sunRAHour, $sunRAMin, $sunRASec, $sunDecDeg, $sunDecMin, $sunDecSec) = PA_Sun\approximate_position_of_sun($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection);

        descriptive_assert("[{$title}] RA Hour", $sunRAHour, $expectedSunRAHour);
        descriptive_assert("[{$title}] RA Minutes", $sunRAMin, $expectedSunRAMin);
        descriptive_assert("[{$title}] RA Seconds", $sunRASec, $expectedSunRASec);
        descriptive_assert("[{$title}] Dec Degrees", $sunDecDeg, $expectedSunDecDeg);
        descriptive_assert("[{$title}] Dec Minutes", $sunDecMin, $expectedSunDecMin);
        descriptive_assert("[{$title}] Dec Seconds", $sunDecSec, $expectedSunDecSec);

        echo "[{$title}] PASSED\n";
    }

    function precise_position_of_sun($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $expectedSunRAHour, $expectedSunRAMin, $expectedSunRASec, $expectedSunDecDeg, $expectedSunDecMin, $expectedSunDecSec)
    {
        $title = "Precise Position of Sun";

        list($sunRAHour, $sunRAMin, $sunRASec, $sunDecDeg, $sunDecMin, $sunDecSec) = PA_Sun\precise_position_of_sun($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection);

        descriptive_assert("[{$title}] RA Hour", $sunRAHour, $expectedSunRAHour);
        descriptive_assert("[{$title}] RA Minutes", $sunRAMin, $expectedSunRAMin);
        descriptive_assert("[{$title}] RA Seconds", $sunRASec, $expectedSunRASec);
        descriptive_assert("[{$title}] Dec Degrees", $sunDecDeg, $expectedSunDecDeg);
        descriptive_assert("[{$title}] Dec Minutes", $sunDecMin, $expectedSunDecMin);
        descriptive_assert("[{$title}] Dec Seconds", $sunDecSec, $expectedSunDecSec);

        echo "[{$title}] PASSED\n";
    }

    function sun_distance_and_angular_size($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $expectedSunDistKm, $expectedSunAngSizeDeg, $expectedSunAngSizeMin, $expectedSunAngSizeSec)
    {
        $title = "Sun Distance and Angular Size";

        list($sunDistKm, $sunAngSizeDeg, $sunAngSizeMin, $sunAngSizeSec) = PA_Sun\sun_distance_and_angular_size($lctHours, $lctMinutes, $lctSeconds, $localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection);

        descriptive_assert("[{$title}] Distance km", $sunDistKm, $expectedSunDistKm);
        descriptive_assert("[{$title}] Angular Size degrees", $sunAngSizeDeg, $expectedSunAngSizeDeg);
        descriptive_assert("[{$title}] Angular Size minutes", $sunAngSizeMin, $expectedSunAngSizeMin);
        descriptive_assert("[{$title}] Angular Size seconds", $sunAngSizeSec, $expectedSunAngSizeSec);

        echo "[{$title}] PASSED\n";
    }

    function sunrise_and_sunset($localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $expected_localSunriseHour, $expected_localSunriseMinute, $expected_localSunsetHour, $expected_localSunsetMinute, $expected_azimuthOfSunriseDeg, $expected_azimuthOfSunsetDeg, $expected_status)
    {
        $title = "Sunrise and Sunset";

        list($localSunriseHour, $localSunriseMinute, $localSunsetHour, $localSunsetMinute, $azimuthOfSunriseDeg, $azimuthOfSunsetDeg, $status) = PA_Sun\sunrise_and_sunset($localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg);

        descriptive_assert("[{$title}] Sunrise Hour", $localSunriseHour, $expected_localSunriseHour);
        descriptive_assert("[{$title}] Sunrise Minute", $localSunriseMinute, $expected_localSunriseMinute);
        descriptive_assert("[{$title}] Sunset Hour", $localSunsetHour, $expected_localSunsetHour);
        descriptive_assert("[{$title}] Sunset Minute", $localSunsetMinute, $expected_localSunsetMinute);
        descriptive_assert("[{$title}] Azimuth of Sunrise", $azimuthOfSunriseDeg, $expected_azimuthOfSunriseDeg);
        descriptive_assert("[{$title}] Azimuth of Sunset", $azimuthOfSunsetDeg, $expected_azimuthOfSunsetDeg);
        descriptive_assert("[{$title}] Status", $status->value, $expected_status->value);

        echo "[{$title}] PASSED\n";
    }

    function morning_and_evening_twilight($localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $twilightType, $expected_amTwilightBeginsHour, $expected_amTwilightBeginsMin, $expected_pmTwilightEndsHour, $expected_pmTwilightEndsMin, $expected_status)
    {
        $title = "Morning and Evening Twilight";

        list($amTwilightBeginsHour, $amTwilightBeginsMin, $pmTwilightEndsHour, $pmTwilightEndsMin, $status) =
            PA_Sun\morning_and_evening_twilight($localDay, $localMonth, $localYear, $isDaylightSaving, $zoneCorrection, $geographicalLongDeg, $geographicalLatDeg, $twilightType);

        descriptive_assert("[{$title}] Twilight Begins Hour", $amTwilightBeginsHour, $expected_amTwilightBeginsHour);
        descriptive_assert("[{$title}] Twilight Begins Minute", $amTwilightBeginsMin, $expected_amTwilightBeginsMin);
        descriptive_assert("[{$title}] Twilight Ends Hour", $pmTwilightEndsHour, $expected_pmTwilightEndsHour);
        descriptive_assert("[{$title}] Twilight Ends Minute", $pmTwilightEndsMin, $expected_pmTwilightEndsMin);
        descriptive_assert("[{$title}] Twilight Status", $status->value, $expected_status->value);

        echo "[{$title}] PASSED\n";
    }

    function equation_of_time($gwdateDay, $gwdateMonth, $gwdateYear, $expected_equationOfTimeMin, $expected_equationOfTimeSec)
    {
        $title = "Equation of Time";

        list($equationOfTimeMin, $equationOfTimeSec) = PA_Sun\equation_of_time($gwdateDay, $gwdateMonth, $gwdateYear);

        descriptive_assert("[{$title}] Minutes", $equationOfTimeMin, $expected_equationOfTimeMin);
        descriptive_assert("[{$title}] Seconds", $equationOfTimeSec, $expected_equationOfTimeSec);

        echo "[{$title}] PASSED\n";
    }

    function solar_elongation($raHour, $raMin, $raSec, $decDeg, $decMin, $decSec, $gwdateDay, $gwdateMonth, $gwdateYear, $expected_solarElongation)
    {
        $title = "Solar Elongation";

        $solarElongation = PA_Sun\solar_elongation(10, 6, 45, 11, 57, 27, 27.8333333, 7, 2010);

        descriptive_assert("[{$title}]", $solarElongation, $expected_solarElongation);

        echo "[{$title}] PASSED\n";
    }

    approximate_position_of_sun(0, 0, 0, 27, 7, 2003, false, 0, 8, 23, 33.73, 19, 21, 14.32);

    precise_position_of_sun(0, 0, 0, 27, 7, 1988, false, 0, 8, 26, 3.83, 19, 12, 49.72);

    sun_distance_and_angular_size(0, 0, 0, 27, 7, 1988, false, 0, 151920130, 0, 31, 29.93);

    sunrise_and_sunset(10, 3, 1986, false, -5, -71.05, 42.37, 6, 5, 17, 45, 94.83, 265.43, RiseSetStatus::OK);

    morning_and_evening_twilight(7, 9, 1979, false, 0, 0, 52, TwilightType::Astronomical, 3, 17, 20, 37, TwilightStatus::OK);

    equation_of_time(27, 7, 2010, 6, 31.52);

    solar_elongation(10, 6, 45, 11, 57, 27, 27.8333333, 7, 2010, 24.78);
}
