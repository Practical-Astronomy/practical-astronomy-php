<?php

include_once '../../single_file/PAAll.php';

require "vendor/autoload.php";

use Flight\Engine;
use PA\Eclipses as PA_Eclipses;

function getLocalTime(int $utcHours,  int $utcMinutes,  int $zoneCorrectionHours, bool $isDaylightSavings)
{
    if ($utcHours == -99) {
        return "N/A";
    }

    if ($isDaylightSavings) {
        $zoneCorrectionHours = $zoneCorrectionHours - 1;
    }

    $offsetMinutes = (int) (- ($zoneCorrectionHours * 60));

    $totalMinutes = ($utcHours * 60 + $utcMinutes + $offsetMinutes + 1440) % 1440;

    $localHours = floor($totalMinutes / 60);
    $localMinutes = $totalMinutes % 60;

    return [$localHours, $localMinutes];
}

class PAController
{
    protected Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    public function heartbeat(): void
    {
        echo "Service is alive!";
    }

    public function getLunarOccurrence(): void
    {
        // Retrieve the POSTed data into an array:
        $data = Flight::request()->data;

        // Extract the fields into individual variables, including splitting the observation date into month, day, year parts:
        $observeDate = $data->observeDate ?? null;
        $dateParts = explode("-", $observeDate);
        $monthOfObservation = (int) $dateParts[1];
        $dayOfObservation = (int) $dateParts[2];
        $yearOfObservation =  (int) $dateParts[0];
        $isDaylightSavings = (bool) $data->isDaylightSavings ?? null;
        $zoneCorrectionHours = (int) $data->zoneCorrectionHours ?? null;

        // Get lunar eclipse occurrence info, in universal time:
        list($lunarEclipseCertainDateDay, $lunarEclipseCertainDateMonth, $lunarEclipseCertainDateYear, $utStartPenPhaseHour, $utStartPenPhaseMinutes, $utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes, $utStartTotalPhaseHour, $utStartTotalPhaseMinutes, $utMidEclipseHour, $utMidEclipseMinutes, $utEndTotalPhaseHour, $utEndTotalPhaseMinutes, $utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes, $utEndPenPhaseHour, $utEndPenPhaseMinutes, $eclipseMagnitude) = PA_Eclipses\lunar_eclipse_circumstances($dayOfObservation, $monthOfObservation, $yearOfObservation, $isDaylightSavings, $zoneCorrectionHours);

        // Convert universal time values to local time:
        [$ltStartPenPhaseHour, $ltStartPenPhaseMinutes] = getLocalTime($utStartPenPhaseHour, $utStartPenPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings);
        [$ltStartUmbralPhaseHour, $ltStartUmbralPhaseMinutes] = getLocalTime($utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings);
        [$ltStartTotalPhaseHour, $ltStartTotalPhaseMinutes] = getLocalTime($utStartTotalPhaseHour, $utStartTotalPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings);
        [$ltMidEclipseHour, $ltMidEclipseMinutes] = getLocalTime($utMidEclipseHour, $utMidEclipseMinutes, $zoneCorrectionHours, $isDaylightSavings);
        [$ltEndTotalPhaseHour, $ltEndTotalPhaseMinutes] = getLocalTime($utEndTotalPhaseHour, $utEndTotalPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings);
        [$ltEndUmbralPhaseHour, $ltEndUmbralPhaseMinutes] = getLocalTime($utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings);
        [$ltEndPenPhaseHour, $ltEndPenPhaseMinutes] = getLocalTime($utEndPenPhaseHour, $utEndPenPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings);

        // Construct results as JSON:
        Flight::json([
            'lunarEclipseCertainDateDay' => $lunarEclipseCertainDateDay,
            'lunarEclipseCertainDateMonth' => $lunarEclipseCertainDateMonth,
            'lunarEclipseCertainDateYear' => $lunarEclipseCertainDateYear,
            'utStartPenPhaseHour' => $utStartPenPhaseHour,
            'utStartPenPhaseMinutes' => $utStartPenPhaseMinutes,
            'utStartUmbralPhaseHour' => $utStartUmbralPhaseHour,
            'utStartUmbralPhaseMinutes' => $utStartUmbralPhaseMinutes,
            'utStartTotalPhaseHour' => $utStartTotalPhaseHour,
            'utStartTotalPhaseMinutes' => $utStartTotalPhaseMinutes,
            'utMidEclipseHour' => $utMidEclipseHour,
            'utMidEclipseMinutes' => $utMidEclipseMinutes,
            'utEndTotalPhaseHour' => $utEndTotalPhaseHour,
            'utEndTotalPhaseMinutes' => $utEndTotalPhaseMinutes,
            'utEndUmbralPhaseHour' => $utEndUmbralPhaseHour,
            'utEndUmbralPhaseMinutes' => $utEndUmbralPhaseMinutes,
            'utEndPenPhaseHour' => $utEndPenPhaseHour,
            'utEndPenPhaseMinutes' => $utEndPenPhaseMinutes,
            'ltStartPenPhaseHour' => $ltStartPenPhaseHour,
            'ltStartPenPhaseMinutes' => $ltStartPenPhaseMinutes,
            'ltStartUmbralPhaseHour' => $ltStartUmbralPhaseHour,
            'ltStartUmbralPhaseMinutes' => $ltStartUmbralPhaseMinutes,
            'ltStartTotalPhaseHour' => $ltStartTotalPhaseHour,
            'ltStartTotalPhaseMinutes' => $ltStartTotalPhaseMinutes,
            'ltMidEclipseHour' => $ltMidEclipseHour,
            'ltMidEclipseMinutes' => $ltMidEclipseMinutes,
            'ltEndTotalPhaseHour' => $ltEndTotalPhaseHour,
            'ltEndTotalPhaseMinutes' => $ltEndTotalPhaseMinutes,
            'ltEndUmbralPhaseHour' => $ltEndUmbralPhaseHour,
            'ltEndUmbralPhaseMinutes' => $ltEndUmbralPhaseMinutes,
            'ltEndPenPhaseHour' => $ltEndPenPhaseHour,
            'ltEndPenPhaseMinutes' => $ltEndPenPhaseMinutes,
            'eclipseMagnitude' => $eclipseMagnitude
        ]);
    }
}

$app = Flight::app();
$pac = new PAController($app);

Flight::route("GET /heartbeat", [$pac, "heartbeat"]);
FLIGHT::route("POST /lunar", [$pac, "getLunarOccurrence"]);

Flight::start();
