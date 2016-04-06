<?php

// echo 'Uncomment the exit on line 4 to prevent accidental reinitialization';
// exit;

set_time_limit(180);

include 'settings.php';
$debug = true;
include 'db.php';
$db = connectDB();

$basepath = './tests';
$extension = '.png';
$tsvextension = '.txt';
$tsvnames = 'real_moran_linear, real_moran_dsquared, real_moran_unweighted, avg_dataval_per_area';
$tsvcolumns = 'real_moran_linear FLOAT, real_moran_dsquared FLOAT, real_moran_unweighted FLOAT, avg_dataval_per_area FLOAT';

runQuery($db, '', "DROP TABLE IF EXISTS lineupanswer");
runQuery($db, '', "DROP TABLE IF EXISTS user");
runQuery($db, '', "DROP TABLE IF EXISTS participantgroup");
runQuery($db, '', "DROP TABLE IF EXISTS map");
runQuery($db, '', "DROP TABLE IF EXISTS lineup");

runQuery($db, '', "CREATE TABLE lineup (
        id INT NOT NULL auto_increment,
        geometry SMALLINT,
        target_moran DECIMAL(5,4),
        comparator_offset DECIMAL(5,4),
        comparator_below TINYINT,
        variant SMALLINT,
        PRIMARY KEY (id)
        )");
runQuery($db, '', "CREATE TABLE map (
        id INT NOT NULL auto_increment,
        decoy TINYINT,
        path VARCHAR(200),
        obfuscated VARCHAR(200),
        lineupid INT,
        $tsvcolumns ,
        PRIMARY KEY (id),
        FOREIGN KEY (lineupid) REFERENCES lineup(id)
        )");
runQuery($db, '', "CREATE TABLE IF NOT EXISTS participantgroup (
        id INT NOT NULL auto_increment,
        geometry SMALLINT,
	moran_low DECIMAL(5,4),
	moran_high DECIMAL(5,4),
	high_first TINYINT,
        PRIMARY KEY (id)
        )");
runQuery($db, '', "CREATE TABLE user (
        id INT NOT NULL auto_increment,
        amt_id VARCHAR(20),
        amt_key VARCHAR(10),
        totaltime INT DEFAULT -1,
        starttime DATETIME,
        endtime DATETIME,
        groupid INT,
        scaleratio FLOAT,
        country VARCHAR(100) DEFAULT '',
        admin_notes TEXT DEFAULT '',
        done BOOL DEFAULT FALSE,
        gender CHAR(1) DEFAULT 'U',
        age TINYINT DEFAULT -1,
        background VARCHAR(6) DEFAULT '',
        familiar TINYINT DEFAULT -1,
        events TEXT DEFAULT '',
        comments TEXT DEFAULT '',
        ipremote VARCHAR(200),
        ipforward VARCHAR(200),
        browser TEXT,
        PRIMARY KEY (id),
        FOREIGN KEY (groupid) REFERENCES participantgroup(id)
        )");
runQuery($db, '', "CREATE TABLE IF NOT EXISTS lineupanswer (
        userid INT,
        staircase TINYINT,
        iteration TINYINT,
	lineupid INT,
        correct_id INT,
        left_id INT,
        right_id INT,
        answer VARCHAR(7),
	answer_id INT,
        time INT,
        FOREIGN KEY (userid) REFERENCES user(id),
        FOREIGN KEY (correct_id) REFERENCES map(id),
        FOREIGN KEY (left_id) REFERENCES map(id),
        FOREIGN KEY (right_id) REFERENCES map(id),
        FOREIGN KEY (answer_id) REFERENCES map(id)
        )");

// ./tests/geography_1/moran_0.8/above/step_0.1/iteration_1/target.svg
// ./tests/geography_1/moran_0.8/above/step_0.1/iteration_1/comparator.svg
// ./tests/geography_2/moran_0.7/below/step_0.05/iteration_2/target.svg
// ./tests/geography_2/moran_0.7/below/step_0.05/iteration_2/comparator.svg

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
}

function readGeographies($currpath) {
    $handle = opendir("$currpath");
    if (!$handle) {
        echo "Failed to open directory1 '$currpath'";
        exit;
    }

    while (false !== ($entry = readdir($handle))) {
        // skip over parent path and local path stuff
        if (startsWith($entry, ".") || endsWith($entry, ".txt")) {
            continue;
        }
        $e = explode("_", $entry);
        $geography = intval($e[1]);
        //$geography = intval(explode("_", $entry)[1]);
        echo "Reading $entry<br/>";
        readMorans($currpath . "/" . $entry, $geography);
    }

    closedir($handle);
}

function readMorans($currpath, $geography) {
    $handle = opendir("$currpath");
    if (!$handle) {
        echo "Failed to open directory2 '$currpath'";
        exit;
    }

    while (false !== ($entry = readdir($handle))) {
        // skip over parent path and local path stuff
        if (startsWith($entry, ".") || endsWith($entry, ".txt")) {
            continue;
        }
        $e = explode("_", $entry);
        $moran = floatval($e[1]);
        //$moran = floatval(explode("_", $entry)[1]);
        echo "&nbsp;&nbsp;Reading $entry<br/>";
        readAboveBelow($currpath . "/" . $entry, $geography, $moran);
    }

    closedir($handle);
}

function readAboveBelow($currpath, $geography, $moran) {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;Reading below<br/>";
    readSteps($currpath . "/below", $geography, $moran, 1);
    echo "&nbsp;&nbsp;&nbsp;&nbsp;Reading above<br/>";
    readSteps($currpath . "/above", $geography, $moran, 0);
}

function readSteps($currpath, $geography, $moran, $below) {
    $handle = opendir("$currpath");
    if (!$handle) {
        echo "Failed to open directory3 '$currpath'";
        exit;
    }

    while (false !== ($entry = readdir($handle))) {
        // skip over parent path and local path stuff
        if (startsWith($entry, ".") || endsWith($entry, ".txt")) {
            continue;
        }
        $e = explode("_", $entry);
        $dev = floatval($e[1]);
        //$dev = floatval(explode("_", $entry)[1]);
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Reading $entry<br/>";
        readIterations($currpath . "/" . $entry, $geography, $moran, $below, $dev);
    }

    closedir($handle);
}

function readIterations($currpath, $geography, $moran, $below, $dev) {
    $handle = opendir("$currpath");
    if (!$handle) {
        echo "Failed to open directory4 '$currpath'";
        exit;
    }

    while (false !== ($entry = readdir($handle))) {
        // skip over parent path and local path stuff
        if (startsWith($entry, ".") || endsWith($entry, ".txt")) {
            continue;
        }
        $e = explode("_", $entry);
        $it = intval($e[1]);
        //$it = intval(explode("_", $entry)[1]);
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Reading $entry<br/>";
        makeCase($currpath . "/" . $entry, $geography, $moran, $below, $dev, $it);
    }

    closedir($handle);
}

function makeCase($currpath, $geography, $moran, $below, $dev, $it) {

    global $db;
    global $counter;
    global $extension;
    global $tsvnames;
    global $tsvextension;

    runQuery($db, '', "INSERT INTO lineup (geometry, target_moran, comparator_offset, comparator_below, variant)
                VALUES ($geography, $moran, $dev, $below, $it)");

    $lineupid = mysqli_insert_id($db);

    $handle = opendir("$currpath");
    if (!$handle) {
        echo "Failed to open directory5 '$currpath'";
        exit;
    }

    $obfusc_target = "./stimuli/"
            . rand(0, 9) . rand(0, 9) . rand(0, 9)
            . $counter
            . rand(0, 9) . rand(0, 9) . rand(0, 9)
            . $extension;
    $counter += rand(1, 3);
    copy($currpath . '/target' . $extension, $obfusc_target);
    $values_target = readtsv($currpath . '/target' . $tsvextension);

    $obfusc_comparator = "./stimuli/"
            . rand(0, 9) . rand(0, 9) . rand(0, 9)
            . $counter
            . rand(0, 9) . rand(0, 9) . rand(0, 9)
            . $extension;
    $counter += rand(1, 3);
    copy($currpath . '/comparator' . $extension, $obfusc_comparator);
    $values_comparator = readtsv($currpath . '/comparator' . $tsvextension);

    runQuery($db, '', "
INSERT INTO map (decoy, path, obfuscated, lineupid, $tsvnames)
VALUES (0, '$currpath/target$extension', '$obfusc_target', $lineupid, $values_target),
       (1, '$currpath/comparator$extension', '$obfusc_comparator', $lineupid, $values_comparator)
");

    closedir($handle);
}

function readtsv($file) {
    global $tsvnames;

    if (!file_exists($file)) {
        echo 'File does not exist: ' . $file;
        exit;
    }
    if (($handle = fopen($file, 'r')) !== false) {
        $contents = fread($handle, filesize($file));
        fclose($handle);
    } else {
        echo 'There was an error opening the file';
        exit;
    }
    $result = str_replace("\t", ",", explode('\n', $contents)[0]);
    $cnt = substr_count($result, ",");
    $tar = substr_count($tsvnames, ",");
    //echo "pre: ".$tar." ".$cnt."<br/>";
    while ($cnt < $tar) {
      $result .= ',0';
      $cnt++;
      //echo "in: ". $tar." ".$cnt."<br/";
    }
    //echo "post: ". $tar." ".$cnt."<br/";
    return $result;
}

if (!is_dir("./stimuli/")) {
    mkdir("./stimuli/");
} else {
    $files = glob("./stimuli/*");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

$counter = rand(100000, 190000);
readGeographies("$basepath");

runQuery($db, '', "
INSERT INTO participantgroup (geometry, moran_low, moran_high, high_first)
SELECT *
FROM
((SELECT DISTINCT lineup1.geometry AS geometry, lineup1.target_moran AS moran_low, lineup2.target_moran AS moran_high, 1 AS high_first
FROM lineup AS lineup1, lineup AS lineup2
WHERE lineup1.geometry = lineup2.geometry
  AND lineup1.target_moran = lineup2.target_moran - $morandiff)
UNION
(SELECT DISTINCT lineup1.geometry AS geometry, lineup1.target_moran AS moran_low, lineup2.target_moran AS moran_high, 0 AS high_first
FROM lineup AS lineup1, lineup AS lineup2
WHERE lineup1.geometry = lineup2.geometry
  AND lineup1.target_moran = lineup2.target_moran - $morandiff)) AS t
ORDER BY geometry
");

echo 'done';
?>
