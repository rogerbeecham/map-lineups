<?php
session_start();

include 'settings.php';

$amt_key = $_SESSION['amt_key'];
$finishedAll = isset($_SESSION['staircase']) && $_SESSION['staircase'] >= sizeof($_SESSION['order']);

include 'unset.php';
?>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Questionnaire</title>
        <link href="css/style.php" rel="stylesheet" type="text/css"/>
    </head>

    <body>

        <div class="main">

            <h2>
                Questionnaire completed!
            </h2>
           
            <p>
                Thank you for participating in our questionnaire. 
                Your unique AMT code is given below.
                Your answers have been processed and are stored anonymously for research purposes.
            </p>
            <hr/>
            <p class="centered emph">
                <?php echo $amt_key; ?>
            </p>
            <hr/>
            <p>
               We would request that you do NOT fill in the questionnaire a second time.
               However, we would greatly appreciate it if you would recommend our questionnaire to family, friends or colleagues!
            </p>
            <hr/>
            <p>
                Press the same hotkey you pressed to go into full-screen mode to exit (F11 on Windows/Linux; Cmd+Shift+F or Cmd+Ctrl+F on MacOS).
                Please, close this tab or browser at this point.
            </p>
        </div>

    </body>
</html>
