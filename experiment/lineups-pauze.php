<html>

    <head>
        <meta charset="UTF-8">
        <title>Questionnaire</title>
        <link href="css/style.php" rel="stylesheet" type="text/css"/>
        <script>
            var submitpressed = false;
            window.onbeforeunload = function () {
                if (!submitpressed) {
                    return "Please, do not use the back button or refresh the page, while filling in the questionnaire.";
                }
            };

            function keyPress(event) {
                if (event.keyCode === 32) {
                    // spacebar
                    submitpressed = true;
                    document.getElementById("form").submit();
                }
            }
        </script>
    </head>

    <body onkeydown="keyPress(event);">
        <div class="main">
            <?php
            if ($_SESSION['newstaircase']) {                
                if ($_SESSION['staircase'] === 0) {
                    ?>
                    <p>
                        This first run is going to be a <span class="emph">practice</span> run.
                        It consists of only 12 questions, just so you can get an idea of what the trials will look like.
                        <br></br>
                        Please, do take the questions seriously and try to do your best in answering them. When it comes to taking the real test, the better you perform the fewer tests you'll get!
                        <br></br>
                    </p>
                    <?php
                } else if (false) {
                    ?>
                    <p>
                        The next run is another <span class="emph">practice</span> run.
                    </p>
                    <?php
                } else if ($_SESSION['staircase'] === 1) {
                    ?>
                    <p>
                        Now that you've done the practice runs, we're going to start the <span class="emph">real</span> runs.
                        Each run consists of at most 50 questions, but if you perform well, fewer answers may be necessary to complete it!<br></br>
                    </p>
                    <?php
                } else {
                    ?>
                    <p>
                        Get ready for your next run! <br></br>
                    </p>
                    <?php
                }
                $_SESSION['newstaircase'] = false;
            } else {
                ?>
                <p>
                    <?php
                    echo 'Run ';
                    echo ($_SESSION['staircase'] + 1);
                    echo ' / ';
                    echo sizeof($_SESSION['order']);
                    echo ' &nbsp;&nbsp; Trial ';
                    echo $_SESSION['providedoffsets'];
                    echo ' / ';
                    echo ($_SESSION['staircase'] === 0 ? $practiceIterations : $maxIterations);
                    ?>
                </p>
            <?php } ?>
            <h5>
                Click the map with <span class="emph">greater</span> spatial autocorrelation!
            </h5>

            <form action='lineups-controller.php' id="form" method='post' onsubmit='submitpressed = true;'>
                <input type="hidden" name="action" value="unpauze"/>
                <p  class="centered">
                    <input id="submitbtn" type="submit" value="next question [spacebar]"/>
                </p>
            </form>
        </div>

    </body>
</html>
