<?php

session_start();

include 'unset.php';
include 'settings.php';

if ($askAMTid && (!isset($_POST['amtid']) || strlen($_POST['amtid']) < 1)) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['ratio'])) {
    $_SESSION['ratio'] = 1;
}

if (isset($_POST['shrink'])) {
    $_SESSION['ratio'] = 9.0 / 10.0 * $_SESSION['ratio'];
} else if (isset($_POST['grow'])) {
    $_SESSION['ratio'] = 10.0 / 9.0 * $_SESSION['ratio'];
}
?>

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
        </script>
    </head>
    <body>

        <div class="main">
            <h2>
                Section 1: Configuration
            </h2>


            <h6>Full-screen mode</h6>
            <p>
              <!-- <small> -->
                Please, put your browser in full-screen mode.
                Windows and Linux users can typically use the F11 key; for Mac users this is Cmd+Shift+F or Ctrl+Cmd+F.
                If you want to stop the questionnaire at any point, use the same hotkey to exit full-screen mode.
              <!-- </small> -->
            </p>

            <hr/>

            <h6>Display size</h6>
            <p>
              <!-- <small> -->
                It is important that you can easily read the text and clearly see the figures.
                Use the "Shrink" and "Grow" buttons below to find a comfortable size.
                Scroll down the figure below and make sure that the figure and the continue button fit in their entirety onto your screen.
              <!-- </small> -->
            </p>
            <form action='start.php' method='post' onsubmit='submitpressed = true;'>
                <?php if ($askAMTid) { ?>
                <input type="hidden" name="amtid" value="<?php echo $_POST['amtid']; ?>"/>
                <?php } ?>
                <p class="centered">
                    <input type="submit" value="shrink" name="shrink"/>
                    <input type="submit" value="grow" name="grow"/>
                </p>
            </form>

            <object id="configSvg" class="configsvg" type="image/svg+xml" data="img/sizeConfig.svg">

            </object>

            <hr/>

            <form action='initialize.php' method='post' onsubmit='submitpressed = true;'>
                <?php if ($askAMTid) { ?>
                <input type="hidden" name="amtid" value="<?php echo $_POST['amtid']; ?>"/>
                <?php } ?>
                <p class="centered">
                    <input id="startquestbtn" type='submit' value="continue"/>
                </p>
            </form>

        </div>
    </body>
</html>
