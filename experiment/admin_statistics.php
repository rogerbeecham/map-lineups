<?php
include 'settings.php';
$debug = true;
include 'db.php';
$db = connectDB();

$agethresholds = array(0, 0, 17, 25, 40, 67, 99);
$nAgeBrackets = count($agethresholds) - 1;

function constructAgeIf($index) {
    global $nAgeBrackets;
    global $agethresholds;

    if ($index >= $nAgeBrackets - 1) {
        return $index;
    } else {
        return "IF(age <= " . $agethresholds[$index + 1] . ",$index," . constructAgeIf($index + 1) . ")";
    }
}

date_default_timezone_set('Europe/Berlin');
?>

<html>

    <head>
        <title>Admin - Statistics</title>
        <link href="css/style.php" rel="stylesheet" type="text/css">
        <link href="css/svg.css" rel="stylesheet" type="text/css">
        <style>
            table {
                border-spacing:0;
                border-collapse:collapse;
                margin-left:auto; 
                margin-right:auto;                 
            }
            td, tr {
                border-spacing:0;
                border-collapse:collapse;
                padding: 4px;                
            }
            .colhead {
                font-weight: bold;
                border-bottom: 1px solid black;
            }
            .rowhead {
                font-weight: bold;
                border-right: 1px solid black;
            }
            .colaggregate {
                border-top: 1px solid black;
            }
            .rowaggregate {
                border-left: 1px solid black;
            }

            .svgpath {
                width: 500px;
                height: 200px;
                margin-left: auto;
                margin-right: auto;
                margin-bottom: 25px;
            }

            .svgdist {
                width: 200px;
                height: 200px;
                margin-left: auto;
                margin-right: auto;
                margin-bottom: 25px;
            }

            .svgtime {
                width: 500px;
                height: 200px;
                margin-left: auto;
                margin-right: auto;
                margin-bottom: 25px;
            }

            .foldable {
                cursor: pointer;
            }
        </style>
        <script src="http://d3js.org/d3.v3.min.js"></script>
        <script>
            function toggle(eltid) {
                var elt = document.getElementById(eltid);
                if (elt.style.display === 'none') {
                    elt.style.display = elt.olddisplay;
                } else {
                    elt.olddisplay = elt.style.display;
                    elt.style.display = 'none';
                }
            }
        </script>
    </head>

    <body>

        <div class="main">

            <h4><?php echo date('m/d/Y H:i:s'); ?></h4>
            <h2>Statistics</h2>

            <hr/>
            <h5 class="foldable" onclick="toggle('secPart');">Participants</h5>

            <div id="secPart">
                <h6>User overview</h6>

                <?php
                $users = runQuery($db, '', "SELECT gender, agebracket, COUNT(*) AS cnt
		 FROM (SELECT gender, " . constructAgeIf(0) . " AS agebracket FROM user WHERE done=1) AS bracketed
		 GROUP BY gender, agebracket
		 ORDER BY gender, agebracket");

                for ($i = 0; $i < $nAgeBrackets; $i++) {
                    $genderage["M"][$i] = 0;
                    $genderage["F"][$i] = 0;
                    $genderage["U"][$i] = 0;
                }

                while (list($gender, $agebracket, $cnt) = $users->fetch_row()) {
                    $genderage[$gender][$agebracket] = $cnt;
                }


                $gender["M"] = 0;
                $gender["F"] = 0;
                $gender["U"] = 0;
                for ($i = 0; $i < $nAgeBrackets; $i++) {
                    $gender["M"] += $genderage["M"][$i];
                    $gender["F"] += $genderage["F"][$i];
                    $gender["U"] += $genderage["U"][$i];
                    $age[$i] = $genderage["M"][$i] + $genderage["F"][$i] + $genderage["U"][$i];
                }
                $allusers = $gender["M"] + $gender["F"] + $gender["U"];
                ?>        
                <table>
                    <tr>
                        <td class="colhead rowhead">&nbsp;</td>
                        <?php
                        echo '<td class="colhead">' . $agethresholds[1] . '</td>';
                        for ($i = 1; $i < $nAgeBrackets; $i++) {
                            echo '<td class="colhead">' . ($agethresholds[$i] + 1) . ' - ' . $agethresholds[$i + 1] . '</td>';
                        }
                        ?>
                        <td class="colhead rowaggregate">Total</td>
                    </tr>
                    <tr>
                        <td class="rowhead">Male</td>
                        <?php
                        for ($i = 0; $i < $nAgeBrackets; $i++) {
                            echo '<td>' . $genderage["M"][$i] . '</td>';
                        }
                        ?>
                        <td class="rowaggregate"><?php echo $gender["M"]; ?></td>
                    </tr>
                    <tr>
                        <td class="rowhead">Female</td>
                        <?php
                        for ($i = 0; $i < $nAgeBrackets; $i++) {
                            echo '<td>' . $genderage["F"][$i] . '</td>';
                        }
                        ?>
                        <td class="rowaggregate"><?php echo $gender["F"]; ?></td>
                    </tr>
                    <tr>
                        <td class="rowhead">Unspecified</td>
                        <?php
                        for ($i = 0; $i < $nAgeBrackets; $i++) {
                            echo '<td>' . $genderage["U"][$i] . '</td>';
                        }
                        ?>
                        <td class="rowaggregate"><?php echo $gender["U"]; ?></td>
                    </tr>
                    <tr>
                        <td class="rowhead colaggregate">Total</td>
                        <?php
                        for ($i = 0; $i < $nAgeBrackets; $i++) {
                            echo '<td class="colaggregate">' . $age[$i] . '</td>';
                        }
                        ?>
                        <td class="colaggregate rowaggregate"><?php echo $allusers; ?></td>
                    </tr>
                </table>
            </div>

            <hr/>

            <h5 class="foldable" onclick="toggle('secJND');">JND</h5>

            <div id="secJND">

                TODO

            </div>

            <hr/>

            <h5 class="foldable" onclick="toggle('secStairs');">Staircases</h5>

            <div id="secStairs">
                <?php
                $result = runQuery($db, '', "SELECT userid, geometry, target_moran, comparator_below, staircase, iteration, comparator_offset"
                        . " FROM lineupanswer, lineup"
                        . " WHERE lineupanswer.lineupid = lineup.id"
                        . " ORDER BY userid ASC, staircase ASC, iteration ASC");

                function printStroke($base, $below, $values, $maxit, $col) {

                    global $bracketSize;
                    global $numBrackets;

                    $nmsr = min($bracketSize * $numBrackets, sizeof($values));
                    $jnd = 0;
                    for ($i = 1; $i <= $nmsr; $i++) {
                        $jnd += $values[sizeof($values) - $i];
                    }
                    $jnd /= $nmsr;
                    $yjnd = 100 * (1 - ($below ? $base - $jnd : $base + $jnd));

                    $xs = array();
                    $ys = array();
                    foreach ($values as $i => $off) {
                        $x = $i / $maxit * 200;
                        if ($below) {
                            $m = $base - $off;
                        } else {
                            $m = $base + $off;
                        }
                        $y = 100 * (1 - $m);

                        array_push($xs, $x);
                        array_push($ys, $y);
                    }
                    $xlast = 0;
                    echo "<path d=\"";
                    foreach ($xs as $i => $x) {
                        if ($i === 0) {
                            echo 'M';
                        } else {
                            echo 'L';
                        }
                        $xlast = $x;
                        echo $x . ' ' . $ys[$i];
                    }
                    echo "\" style=\"fill: none; stroke: $col; stroke-width: 1.25; stroke-linecap:round;stroke-linejoin:round;\"/>";

                    foreach ($xs as $i => $x) {
                        echo "<circle r=\"1.5\" cx=\"";
                        echo $x;
                        echo "\" cy=\"";
                        echo $ys[$i];
                        ;
                        echo "\" style=\"fill: $col; stroke: none;\"/>";
                    }

                    if ($base > 0) {
                        $y = 100 * (1 - $base);
                        echo "<path d=\"M0 $y L200 $y\" ";
                        echo "style=\"fill: none; stroke: $col; stroke-width: 1.25; stroke-linecap:round;stroke-linejoin:round;\"/>";
                    }

                        echo "<path d=\"M$xlast $yjnd L200 $yjnd\" ";
                    echo "style=\"fill: none; stroke: $col; stroke-width: 0.75; stroke-linecap:round;stroke-linejoin:round;\"/>";
                }

                function printChart() {

                    global $user;
                    global $usergeom;
                    global $moranH;
                    global $moranL;
                    global $offsets;
                    global $order;
                    global $maxIterations;
                    global $morans;
                    global $belows;
                    ?>                

                    <h6 class="foldable" onclick="toggle('secStairs<?php echo $user; ?>');"> 
                        User: <?php echo $user . ':' . $usergeom . ':' . $moranH . ':' . $moranL; ?>
                    </h6>

                    <div id = "secStairs<?php echo $user; ?>">

                        <svg preserveAspectRatio="none" class="svgpath" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 200 100">
                        <rect x="0" y="0" width="200" height="100" class="strokeBlack stokeThin fillNone"></rect>

                        <?php
                        forEach ($offsets as $staircase => $offsetArray) {
                            printStroke(0, false, $offsetArray, $maxIterations, $order[$staircase][3]);
                        }
                        ?>                   
                        </svg>                         
                        <svg preserveAspectRatio="none" class="svgpath" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 200 100">
                        <rect x="0" y="0" width="200" height="100" class="strokeBlack stokeThin fillNone"></rect>
                        <?php
                        forEach ($offsets as $staircase => $offsetArray) {
                            printStroke($morans[$staircase], $belows[$staircase], $offsetArray, $maxIterations, $order[$staircase][3]);
                        }
                        ?>                   
                        </svg> 
                    </div>
                    <?php
                }

                $user = null;

                while (list($userid, $geometry, $moran, $below, $staircase, $iteration, $comparator_offset) = $result->fetch_row()) {

                    if ($user !== $userid) {
                        if ($user !== null) {
                            printChart();
                        }
                        $user = $userid;
                        $usergeom = $geometry;
                        $offsets = array();
                        $morans = array();
                        $belows = array();
                    }

                    if ($moran <= $moranLowHigh) {
                        $moranL = $moran;
                    } else {
                        $moranH = $moran;
                    }

                    if ($staircase >= sizeof($offsets)) {
                        array_push($offsets, array());
                        array_push($morans, $moran);
                        array_push($belows, $below);
                    }

                    array_push($offsets[$staircase], $comparator_offset);
                }

                if ($user !== null) {
                    printChart();
                }
                ?>

            </div>

        </div>

    </body>
</html>