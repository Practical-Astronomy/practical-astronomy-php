<html>

<head>
    <title>Lunar Eclipse Circumstances</title>
</head>

<body>
    <h2>Lunar Eclipse Circumstances</h2>

    <form action="result.php" method="post">
        <table>
            <tr>
                <td><label for="observedate">Date to check:</label></td>
                <td><input type="date" id="observedate" name="observedate" value="2026-03-02"><br></td>
            </tr>
            <tr>
                <td><label for="dst">Daylight savings?</label></td>
                <td><input type="checkbox" id="dst" name="dst" checked><br></td>
            </tr>
            <tr>
                <td><label for="zonecorrect">Zone correction hours:</label></td>
                <td><input type="number" id="zonecorrect" name="zonecorrect" value=5><br></td>
            </tr>
        </table>

        <input type="submit" value="Get Info"> <input type="reset">
    </form>
</body>

</html>