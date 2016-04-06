<?php 
session_start();
include 'unset.php';
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Questionnaire</title>
        <link href="css/style.php" rel="stylesheet" type="text/css"> 
    </head>
    <body>

        <div class="main">
            <h2>
                Error occurred (Code <?php echo $_GET['code'];?>)                 
            </h2>
            <?php
              if (isset($_GET['msg'])) {
                  echo "<p>".$_GET['msg']."</p>";
              }
            ?>
            <p>
                You are seeing this page because an error occurred. 
                If you haven't answered any questions after Section 1 (Configuration), feel free to restart the questionnaire.
                Otherwise, please refrain from filling in the questionnaire again.
                We thank you for your participation.
            </p>
        </div>
    </body>
</html>