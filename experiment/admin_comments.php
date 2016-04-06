<?php
include 'settings.php';
$debug = true;
include 'db.php';
$db = connectDB();

date_default_timezone_set('Europe/Berlin');
?>

<html>

    <head>
        <title>Admin - Comments</title>
        <link href="css/style.php" rel="stylesheet" type="text/css">        
    </head>

    <body>

        <div class="main">

            <h4><?php echo date('m/d/Y H:i:s'); ?></h4>
            <h2>Comments</h2>

            <?php
            $users = runQuery($db, 'USERS', "SELECT totaltime, comments, events, id, geometry
		 FROM user WHERE done = true");

            while (list($time, $comments, $events, $id, $geometry) = $users->fetch_row()) {
                echo "<hr/>";
                echo "<h6>$time - $id - $geometry</h6>";
                echo "<p><b>Comments:</b> $comments</p>";
                echo "<p><b>Events:</b> $events</p>";
            }
            ?>            

        </div>

    </body>
</html>