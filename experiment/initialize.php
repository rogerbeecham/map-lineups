<?php

session_start();
if (!isset($_SESSION['ratio'])) {
    $_SESSION['ratio'] = 1;
}

include 'settings.php';

$ipremote = $_SERVER["REMOTE_ADDR"];
$ipforward = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? isset($_SERVER["HTTP_X_FORWARDED_FOR"]) : "undefined";

function getBrowser() {
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
        if (preg_match('/NT 6.2/i', $u_agent)) {
            $platform .= ' 8';
        } elseif (preg_match('/NT 6.3/i', $u_agent)) {
            $platform .= ' 8.1';
        } elseif (preg_match('/NT 6.1/i', $u_agent)) {
            $platform .= ' 7';
        } elseif (preg_match('/NT 6.0/i', $u_agent)) {
            $platform .= ' Vista';
        } elseif (preg_match('/NT 5.1/i', $u_agent)) {
            $platform .= ' XP';
        } elseif (preg_match('/NT 5.0/i', $u_agent)) {
            $platform .= ' 2000';
        }
        if (preg_match('/WOW64/i', $u_agent) || preg_match('/x64/i', $u_agent)) {
            $platform .= ' (x64)';
        }
    }

    // Next get the name of the useragent yes seperately and for good reason
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif (preg_match('/Trident/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer Trident';
        $ub = "Trident";
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
            $version = $matches['version'][0];
        } else {
            $version = $matches['version'][1];
        }
    } else {
        $version = $matches['version'][0];
    }

    // check if we have a number
    if ($version === null || $version === "") {
        $version = "?";
    }

    return $u_agent . ' -- browser ' . $bname . ' ' . $version . ' on ' . $platform . ' | ' . $pattern;

//    return array(
//        'userAgent' => $u_agent,
//        'name' => $bname,
//        'version' => $version,
//        'platform' => $platform,
//        'pattern' => $pattern
//    );
}

function randomString($cnt) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < $cnt; $i++) {
        $randstring .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randstring;
}

$browser = getBrowser();

$_SESSION['amt_key'] = randomString(10);

include 'db.php';
$db = connectDB();

$_SESSION['order'] = array();
array_push($_SESSION['order'], array($practiceGeometry, $practiceMoran, $practiceBelow));

// select a geometry/moran combination
$statement = prepQuery($db, 'INPGSEL', "SELECT id, geometry, moran_low, moran_high, high_first, IFNULL (IF(cnt < $participantsPerGroup, 0, cnt ), 0) AS completed"
        . " FROM participantgroup LEFT JOIN"
        . " (SELECT groupid, COUNT(id) AS cnt FROM user WHERE done OR TIMESTAMPDIFF(MINUTE, starttime, NOW()) < $timeToDone GROUP BY groupid) AS usercnts"
        . " ON usercnts.groupid = participantgroup.id"
        . " WHERE geometry != 6 AND geometry > 2"
        . " ORDER BY completed, geometry, moran_low, high_first"
        . " LIMIT 1");
$statement->execute();
$statement->bind_result($grpid, $geometry, $moran_low, $moran_high, $high_first, $completed);
while ($statement->fetch()) {
    if ($high_first) {
        array_push($_SESSION['order'], array($geometry, $moran_high, 1));
        array_push($_SESSION['order'], array($geometry, $moran_high, 0));
        array_push($_SESSION['order'], array($geometry, $moran_low, 0));
        array_push($_SESSION['order'], array($geometry, $moran_low, 1));
    } else {
        array_push($_SESSION['order'], array($geometry, $moran_low, 0));
        array_push($_SESSION['order'], array($geometry, $moran_low, 1));
        array_push($_SESSION['order'], array($geometry, $moran_high, 1));
        array_push($_SESSION['order'], array($geometry, $moran_high, 0));        
    }
}

$amtid = isset($_POST['amtid']) ? $_POST['amtid'] : '<not provided>';
$statement4 = prepQuery($db, 'DSIER'
        , "INSERT INTO user (starttime, endtime, amt_id, amt_key, groupid, scaleratio, ipremote, ipforward, browser)"
        . " VALUES (NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?)");
$statement4->bind_param(
        'ssidsss'
        , $amtid
        , $_SESSION['amt_key']
        , $grpid
        , $_SESSION['ratio']
        , $ipremote
        , $ipforward
        , $browser);
$statement4->execute();

$_SESSION["userID"] = mysqli_insert_id($db);

mysqli_close($db);

header('Location: lineups-introduction.php');
?>
