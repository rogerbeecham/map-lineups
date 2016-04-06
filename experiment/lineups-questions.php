<?php
$receivedTime = time();

//session_start();
//if (!isset($_SESSION['userID'])) {
//    header('Location: error.php?code=LQNOID');
//    exit;
//}
//include 'settings.php';
// $displaymode can have three values:
// 0: new question
// 1: refreshed (should ideally not happen)
// 2: show answer

if (isset($_SESSION['lastcorrect'])) {
    // show answer
    $displaymode = 2;
    $isCorrect = $_SESSION['lastcorrect'];
    $isLeftCorrect = $_SESSION['correctid'] === $_SESSION['left_id'];
    $isPractice = $_SESSION['staircase'] === 0;
} else if ($_SESSION['lastshownquestion'] === $_SESSION['currentquestion']) {
    // refresh?
    $displaymode = 1;
} else {
    // new question
    $displaymode = 0;

    $_SESSION['sincelastpauze'] ++;
    $_SESSION['lastshownquestion'] = $_SESSION['currentquestion'];

    // init question
    $case = $_SESSION['order'][$_SESSION['staircase']];    
    // $case contains (geometry, moran, below)
    $geom = $case[0];
    $moran = $case[1];
    $below  = $case[2];

    $comparator_offset = $_SESSION['comparator_offsets'][$_SESSION['curroffset']];

    $db = connectDB();
    $statement1 = prepQuery($db, 'LQSLI', "SELECT id
FROM lineup
WHERE lineup.geometry = ? AND
lineup.target_moran = ? AND
comparator_offset = ? AND
comparator_below = ? AND
variant = ?");
    $statement1->bind_param('iddii'
            , $geom
            , $moran
            , $comparator_offset
            , $below
            , $_SESSION['offset_variants'][$_SESSION['curroffset']]);
    $statement1->execute();
    $statement1->bind_result($id);
    
    while ($statement1->fetch()) {
        $_SESSION['lineupid'] = $id;
    }

    $statement2 = prepQuery($db, 'LQSMD', "SELECT id, decoy, obfuscated"
            . " FROM map"
            . " WHERE lineupid = ?"
            . " ORDER BY RAND()");
    $statement2->bind_param('i', $_SESSION['lineupid']);
    $statement2->execute();
    $statement2->bind_result($id, $decoy, $obfus);

    $left = true;
    while ($statement2->fetch()) {
        if ($left) {
            $_SESSION['left_id'] = $id;
            $_SESSION['left_path'] = $obfus;
        } else {
            $_SESSION['right_id'] = $id;
            $_SESSION['right_path'] = $obfus;
        }
        $left = !$left;
        if ($decoy) {
            if (!$below) {
                $_SESSION['correctid'] = $id;
            }
        } else if ($below) {
            $_SESSION['correctid'] = $id;
        }
    }

    mysqli_close($db);

    array_push($_SESSION['providedoffsets'], $_SESSION['curroffset']);
}

function printCorrectLabel($left) {

    global $displaymode;
    if ($displaymode !== 2) {
        echo '&nbsp;<br/>';
        echo '&nbsp;';
        return;
    }

    global $isCorrect;
    global $isLeftCorrect;
    global $isPractice;

    if ($isCorrect) {
        if (($isLeftCorrect && $left) || (!$isLeftCorrect && !$left)) {
            echo '<font color="green">Correct!</font><br/>';
            if ($isPractice) {
                echo 'We\'ll be making the next question harder';
            } else {
                echo '&nbsp;';
            }
        } else {
            echo '&nbsp;<br/>';
            echo '&nbsp;';
        }
    } else {
        if (($isLeftCorrect && $left) || (!$isLeftCorrect && !$left)) {
            // echo 'This was the correct answer<br/>';
            // if ($isPractice) {
            //     echo 'We\'ll be making the next question easier';
            // } else {
            //     echo '&nbsp;';
            // }
        } else {
            echo '<font color="red">Incorrect!</font><br/>';
            if ($isPractice) {
                echo 'We\'ll be making the next question easier';
            } else {
                echo '&nbsp;';
            }
            echo '&nbsp;';
        }
    }
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

<?php if ($displaymode !== 2) { ?>
                function keyPress(event) {
                    if (event.keyCode === 37) {
                        // left
                        document.getElementById("answerleft").click();
                    } else if (event.keyCode === 39) {
                        // right
                        document.getElementById("answerright").click();
                    }
                }
<?php } else { ?>
                function timeOut() {
                    setTimeout(function () {
                        submitpressed = true;
                        document.getElementById("form").submit();
                    }, <?php echo $showCorrectTime; ?>);
                }
<?php } ?>
        </script>
    </head>
    <?php
    if ($displaymode !== 2) {
        echo '<body onkeydown="keyPress(event);">';
    } else {
        echo '<body onload="timeOut();">';
    }
    ?>
    <div class="main">
        <p>
            <?php
            echo 'Run ';
            echo ($_SESSION['staircase'] + 1);
            echo ' / ';
            echo sizeof($_SESSION['order']);
            echo ' &nbsp;&nbsp; Trial ';
            echo sizeof($_SESSION['providedoffsets']);
            echo ' / ';
            echo ($_SESSION['staircase'] === 0 ? $practiceIterations : $maxIterations);
            ?>
        </p>
        <h5>
            Click the map with <span class="emph">greater</span> spatial autocorrelation!
        </h5>
        <form action='lineups-controller.php' id="form" method='post' onsubmit='submitpressed = true;'>
            <?php if ($displaymode === 2) { ?>
                <input type="hidden" name="action" value="answershown"/>
            <?php } else { ?>
                <input type="hidden" name="action" value="answersubmitted"/>
                <input type="hidden" name="questionnumber" value="<?php echo $_SESSION['currentquestion']; ?>"/>
            <?php } ?>
            <table class="answertable">
                <tr>
                    <td>
                        <label style="padding: 0; margin: 0;">
                            <?php if ($displaymode !== 2) { ?>
                                <input type="submit" class="hidden" id="answerleft" name="answer" value="left"/>
                            <?php } ?>
                            <img class="answer" src="<?php echo $_SESSION['left_path']; ?>"/>
                        </label>
                    </td>
                    <td>
                        <label>
                            <?php if ($displaymode !== 2) { ?>
                                <input type="submit" class="hidden" id="answerright" name="answer" value="right"/>
                            <?php } ?>
                            <img class="answer" src="<?php echo $_SESSION['right_path']; ?>"/>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class='centered'>
                        <?php printCorrectLabel(true); ?>
                    </td>
                    <td class='centered'>
                        <?php printCorrectLabel(false); ?>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>
<?php
if ($displaymode === 0) {
    $_SESSION['starttime'] = time();
}
?>
