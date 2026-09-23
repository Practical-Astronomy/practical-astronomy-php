<html>

<body>
    <button onclick="history.back()">Go Back</button>

    <h2>Observer Info</h2>

    Observation Date is <?php echo $_POST["observedate"]; ?><br>
    Daylight Savings is <?php echo $_POST["dst"]; ?><br>
    Zone Correction Hours is <?php echo $_POST["zonecorrect"]; ?><br>

    <?php
    include_once '../../single_file/PAAll.php';

    use PA\Eclipses as PA_Eclipses;

    function formatTime(int $hours, int $minutes)
    {
        return ($hours == -99) ? "N/A" : $hours . ':' . sprintf('%02d', $minutes);
    }

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

        return formatTime($localHours, $localMinutes);
    }

    $observeDate = $_POST["observedate"];
    $dateParts = explode("-", $observeDate);
    $monthOfObservation = (int) $dateParts[1];
    $dayOfObservation = (int) $dateParts[2];
    $yearOfObservation =  (int) $dateParts[0];

    $isDaylightSavings = (bool) $_POST["dst"];
    $zoneCorrectionHours = (int) $_POST["zonecorrect"];

    list($lunarEclipseCertainDateDay, $lunarEclipseCertainDateMonth, $lunarEclipseCertainDateYear, $utStartPenPhaseHour, $utStartPenPhaseMinutes, $utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes, $utStartTotalPhaseHour, $utStartTotalPhaseMinutes, $utMidEclipseHour, $utMidEclipseMinutes, $utEndTotalPhaseHour, $utEndTotalPhaseMinutes, $utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes, $utEndPenPhaseHour, $utEndPenPhaseMinutes, $eclipseMagnitude) = PA_Eclipses\lunar_eclipse_circumstances($dayOfObservation, $monthOfObservation, $yearOfObservation, $isDaylightSavings, $zoneCorrectionHours);

    echo '<h2>Next Lunar Eclipse</h2>';
    echo '<table>';
    echo '<tr><td>Certain Date:</td>' . '<td>' . $lunarEclipseCertainDateMonth . '/' . $lunarEclipseCertainDateDay . '/' . $lunarEclipseCertainDateYear . '</td</tr>';
    echo '<tr><td>Magnitude:</td>' . '<td>' . $eclipseMagnitude . '</td></tr>';
    echo '</table>';

    echo '<h3>Universal Time</h3>';
    echo '<table>';
    echo '<tr><td>Start Penumbral:</td>' . '<td>' . formatTime($utStartPenPhaseHour, $utStartPenPhaseMinutes) . '</td></tr>';
    echo '<tr><td>Start Umbral:</td>' . '<td>' . formatTime($utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes) . '</td></tr>';
    echo '<tr><td>Start Total Phase:</td>' . '<td>' . formatTime($utStartTotalPhaseHour, $utStartTotalPhaseMinutes) . '</td></tr>';
    echo '<tr><td>Mid-Eclipse:</td>' . '<td>' . formatTime($utMidEclipseHour, $utMidEclipseMinutes) . '</td></tr>';
    echo '<tr><td>End Total Phase:</td>' . '<td>' . formatTime($utEndTotalPhaseHour, $utEndTotalPhaseMinutes) . '</td></tr>';
    echo '<tr><td>End Umbral Phase:</td>' . '<td>' . formatTime($utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes) . '</td></tr>';
    echo '<tr><td>End Penumbral Phase:</td>' . '<td>' . formatTime($utEndPenPhaseHour, $utEndPenPhaseMinutes) . '</td></tr>';
    echo '</table>';

    echo '<h3>Local Time</h3>';
    echo '<table>';
    echo '<tr><td>Start Penumbral:</td>' . '<td>' . getLocalTime($utStartPenPhaseHour, $utStartPenPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '<tr><td>Start Umbral:</td>' . '<td>' . getLocalTime($utStartUmbralPhaseHour, $utStartUmbralPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '<tr><td>Start Total Phase:</td>' . '<td>' . getLocalTime($utStartTotalPhaseHour, $utStartTotalPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '<tr><td>Mid-Eclipse:</td>' . '<td>' . getLocalTime($utMidEclipseHour, $utMidEclipseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '<tr><td>End Total Phase:</td>' . '<td>' . getLocalTime($utEndTotalPhaseHour, $utEndTotalPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '<tr><td>End Umbral Phase:</td>' . '<td>' . getLocalTime($utEndUmbralPhaseHour, $utEndUmbralPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '<tr><td>End Penumbral Phase:</td>' . '<td>' . getLocalTime($utEndPenPhaseHour, $utEndPenPhaseMinutes, $zoneCorrectionHours, $isDaylightSavings) . '</td></tr>';
    echo '</table>';
    ?>
</body>

</html>