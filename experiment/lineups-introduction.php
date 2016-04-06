<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: error.php?code=IPNOID');
    exit;
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
          <h1>
              Spatial autocorrelation in maps
          </h1>
          <hr/>

          <p>
            <!-- <small> -->
            Spatial autocorrelation describes the extent to which things that are located close to one another are similar and things that are located further away from one another are different.
              <br></br>
            The grids below are effectively maps. They consist of unit areas coloured according to some phenomenon — house prices for example. Lighter colours relate to areas with lower values (lower house prices in our example) and darker colours to areas with higher values (higher house prices). Notice that for the maps with greater autocorrelation,  areas that are close together have similar colours — meaning that values that are similar tend to be close together. In maps with less autocorrelation, similar colours occur close together less often and areas that are close together have different colours. 
              <br></br>
              In the survey you will be shown many pairs of maps and asked to choose which appears to have the <i><b>greater</b> spatial autocorrelation</i>. Do take a look at the maps below and then click 'continue', when you are ready to take a practice session.
            <!-- </small> -->
          <p>
          <br></br>
          <img src="img/training.png">
          </p>
          <noscript>
          <p>
              Javascript is not enabled for this website. Please, enable it and reload the page (F5).
          </p>
          </noscript>
          <hr/>
            <form action='lineups-controller.php' method='post' onsubmit='submitpressed = true;'>
                <input type="hidden" name="action" value="init"/>
                <p class="centered">
                    <input id="submitbtn" type="submit" value="continue"/>
                </p>
            </form>
        </div>

    </body>
</html>
