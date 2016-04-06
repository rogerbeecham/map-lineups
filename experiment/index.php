<?php
session_start();

if (!isset($_SESSION['ratio'])) {
    $_SESSION['ratio'] = 1;
}

include 'unset.php';
include 'settings.php';

$_SESSION['indexload'] = time();
?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Questionnaire</title>
        <link href="css/style.php" rel="stylesheet" type="text/css"/>
    </head>
    <body>

        <div class="main">

            <h1>
                Visual perception of autocorrelation in maps
            </h1>
            <hr/>

            <p>
              <!-- <small> -->
                This survey aims to understand how people interpret patterns in maps. You will be shown a series of screens with two maps. Your task is to identify which of the two shows <strong>more</strong> spatial autocorrelation.
                <br></br>
                Before the main task starts there will be a brief training section that introduces the basics of autocorrelation in maps. There will also be a very short questionnaire at the end of the survey asking for background information (should you not wish to disclose this information there is a <i>prefer not to say</i> option).
                The study itself will last 15-20 minutes (5 minutes training, 10-15 minutes main task). You can take the survey anywhere. However we ask that you use a laptop or desktop rather than a handheld mobile device.
                <br></br>
                You are free to withdraw from the survey at any point. The data collected from the survey will form findings in an academic research paper. 
                When completing the survey we ask that you:
                <li>Avoid other activities to minimise interruptions.</li>
                <li>Do not close your browser, use the "back" button or refresh the page -- this will cause you to lose your progress.</li>
                <li>Do not complete the questionnaire by giving random answers. If you wish to exit the study early, simply close your tab or browser.</li>
                </ul>
                <br></br>
                You may only complete this HIT once. You will not be able to register to take the test a second or third time. If you are still happy to proceed with the test, click 'continue'.
              <!-- </small> -->
            <hr/>
            <noscript>
            <p>
                Javascript is not enabled for this website. Please, enable it and reload the page (F5).
            </p>
            </noscript>

            <form method='post' onsubmit="" action="start.php">
                <p class="big centered">
                    <?php if ($askAMTid) { ?>
                        <!-- <small> -->
                            Your Amazon Worker ID
                        <!-- </small> -->
                        <input name="amtid" type="text" value="" required/>
                    <?php } ?>
                    <input id="startquestbtn" type="submit" value="continue" disabled/>
                </p>
            </form>

            <script>
                window.onload = function () {
                    setTimeout(function () {
                        document.getElementById('startquestbtn').disabled = false;
                    }, 10);
                };
            </script>
        </div>
    </body>
</html>
