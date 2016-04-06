<?php

function connectDB() {
    global $debug;

    $db = mysqli_connect("localhost", "root", "root");
    if (!$db) {
        if ($debug) {
            echo "Connection failed: " . mysqli_connect_error();
        } else {
            header('Location: error.php?code=DBCON');
        }
        exit;
    }

    $er = mysqli_select_db($db, "maplineups");
    if (!$er) {
        if ($debug) {
            echo "DB selection failed: " .  mysqli_error($db);
        } else {
            header('Location: error.php?code=DBSEL');
        }
        exit;
    }

    return $db;
}

function runQuery($db, $lbl, $qry) {
    global $debug;

    $result = mysqli_query($db, $qry);
    if (!$result) {
        if ($debug) {
            echo "Query ($lbl) failed: " . mysqli_error($db) . " " . mysqli_errno($db);
            echo "<br/>$qry";
        } else {
            header('Location: error.php?code='.$lbl);
        }
        exit;
    }
    return $result;
}

function prepQuery($db, $lbl, $qry) {
    global $debug;

    $statement = mysqli_prepare($db, $qry);
    if (!$statement) {
        if ($debug) {
            echo "Prepare ($lbl) failed: " . mysqli_error($db) . " " . mysqli_errno($db);
            echo "<br/>$qry";
        } else {
            header('Location: error.php?code='.$lbl);
        }
        exit;
    }
    return $statement;
}

?>
