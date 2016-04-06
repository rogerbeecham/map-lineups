<?php

$receivedTime = time();

session_start();
if (!isset($_SESSION['userID'])) {
    header('Location: error.php?code=LCNOID');
    exit;
}
if (!isset($_POST['action'])) {
    header('Location: error.php?code=LCNOAC');
    exit;
}

include 'settings.php';
include 'db.php';

function doneWithStaircase() {
    global $bracketSize;
    global $numBrackets;
    global $practiceIterations;
    global $maxIterations;
    global $Fcrit;
    global $maxVariants;

    $n = sizeof($_SESSION['providedoffsets']);

    if ($_SESSION['offset_variants'][$_SESSION['curroffset']] > $maxVariants) {
        $_SESSION['offset_variants'][$_SESSION['curroffset']] = 1;
    } 
    
     
    if ($n >= ($_SESSION['staircase'] === 0 ? $practiceIterations : $maxIterations)) {
        return true;
    } else if ($n <= $numBrackets * $bracketSize) {
        return false;
    }
    

    $mean = 0;
    for ($i = 1; $i <= $numBrackets * $bracketSize; $i++) {
        $mean += $_SESSION['providedoffsets'][$n - $i];
    }
    $mean /= $numBrackets * $bracketSize;

    $Fbetween = 0;
    $Fwithin = 0;
    for ($b = 0; $b < $numBrackets; $b++) {
        $bmean = 0;
        for ($i = 1; $i <= $bracketSize; $i++) {
            $bmean += $_SESSION['providedoffsets'][$n - $i - $b * $bracketSize];
        }
        $bmean /= $bracketSize;
        $Fbetween += ($bmean - $mean) * ($bmean - $mean);

        for ($i = 1; $i <= $bracketSize; $i++) {
            $val = $_SESSION['providedoffsets'][$n - $i - $b * $bracketSize];
            $Fwithin += ($val - $bmean) * ($val - $bmean);
        }
    }
    $Fbetween *= $bracketSize / ($numBrackets - 1);
    $Fwithin /= ($numBrackets * $bracketSize - $numBrackets);

    $F = $Fbetween / $Fwithin;
    return $F <= $Fcrit;
}

function initNewStaircase() {

    if ($_SESSION['staircase'] >= sizeof($_SESSION['order'])) {
        header('Location: participant.php');
        exit;
    }
    
    $case = $_SESSION['order'][$_SESSION['staircase']];    
    // $case contains (geometry, moran, below)
    $geom = $case[0];
    $moran = $case[1];
    $below  = $case[2];

    $db = connectDB();
    $statement = prepQuery($db, 'LCSOF', "SELECT DISTINCT(comparator_offset) FROM lineup"
            . " WHERE geometry = ? AND"
            . " target_moran = ? AND"
            . " comparator_below = ?"
            . " ORDER BY comparator_offset DESC");
    $statement->bind_param('idi'
            , $geom
            , $moran
            , $below);
    $statement->execute();
    $statement->bind_result($decoy);

    $_SESSION['comparator_offsets'] = array();
    $_SESSION['offset_variants'] = array();
    while ($statement->fetch()) {
        array_push($_SESSION['comparator_offsets'], $decoy);
        if($_SESSION['staircase'] === 0 )
        {
        	array_push($_SESSION['offset_variants'], 7);
        }
        else
        {
        	array_push($_SESSION['offset_variants'], 1);
        }
    }

    mysqli_close($db);

    $_SESSION['curroffset'] = 0;
    while ($_SESSION['comparator_offsets'][$_SESSION['curroffset']] > 0.2
                             && $_SESSION['curroffset'] < sizeof($_SESSION['comparator_offsets']) - 1) {
                             $_SESSION['curroffset']++;
              }
    $_SESSION['providedoffsets'] = array();
    $_SESSION['newstaircase'] = true;
}

function initAction() {
    if (!isset($_SESSION['staircase'])) {
        // first load
        $_SESSION['staircase'] = 0;
        $_SESSION['lastshownquestion'] = -1;
        $_SESSION['currentquestion'] = 0;
        $_SESSION['sincelastpauze'] = 0;
        initNewStaircase();
    }
    return true;
}

function unpauzeAction() {
    return false;
}

function answersubmittedAction() {
    if ($_POST['questionnumber'] == $_SESSION['currentquestion']) {
        global $receivedTime;
        global $showCorrect;
        global $forward;
        global $backward;

        // make sure any new occurrance of this offset provides a new variant!
        $_SESSION['offset_variants'][$_SESSION['curroffset']] ++;

        $answer_id = $_SESSION[$_POST['answer'] . '_id'];

        // record the answer

        $db = connectDB();
        $statement = prepQuery($db, 'LCIAL', "INSERT INTO lineupanswer
            (userid, staircase, iteration, lineupid, correct_id, left_id, right_id, answer, answer_id, time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $s = sizeof($_SESSION['providedoffsets']);
        $t = $receivedTime - $_SESSION['starttime'];
        $statement->bind_param('iiiiiiisii'
                , $_SESSION['userID']
                , $_SESSION['staircase']
                , $s
                , $_SESSION['lineupid']
                , $_SESSION['correctid']
                , $_SESSION['left_id']
                , $_SESSION['right_id']
                , $_POST['answer']
                , $answer_id
                , $t);
        $statement->execute();

        mysqli_close($db);

        // check if it was correct?
        if ($answer_id === $_SESSION['correctid']) {
            $_SESSION['lastcorrect'] = true;
            $_SESSION['curroffset'] = min(
                    sizeof($_SESSION['comparator_offsets']) - 1, $_SESSION['curroffset'] + $forward
            );
        } else {
            $_SESSION['lastcorrect'] = false;
            $_SESSION['curroffset'] = max(
                    0, $_SESSION['curroffset'] - $backward);
        }

        $_SESSION['currentquestion'] ++;
        if ($showCorrect || $_SESSION['staircase'] == 0) {
            // always show in practice mode
            return false;
        } else {
            return answershownAction();
        }
    } else {
        // no answer found, refresh?
        return false;
    }
}

function answershownAction() {
    unset($_SESSION['lastcorrect']);
    if (doneWithStaircase()) {
        $_SESSION['staircase'] ++;
        initNewStaircase();
    }
    return true;
}

switch ($_POST['action']) {
    case 'init':
        $pauze = initAction();
        break;
    case 'unpauze':
        $pauze = unpauzeAction();
        break;
    case 'answersubmitted':
        $pauze = answersubmittedAction();
        break;
    case 'answershown':
        $pauze = answershownAction();
        break;
}

if ($_SESSION['newstaircase'] || ($pauze && $_SESSION['sincelastpauze'] >= $pauzeAfter)) {
    $_SESSION['sincelastpauze'] = 0;
    include 'lineups-pauze.php';
    exit;
} else {
    include 'lineups-questions.php';
    exit;
}
?>
