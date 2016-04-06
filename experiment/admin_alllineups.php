<?php
include 'settings.php';
$debug = true;
include 'db.php';
$db = connectDB();
?>

<html>

    <head>
        <meta charset="UTF-8">
            <title>Admin - all lineups</title>
            <link href="css/style.php" rel="stylesheet" type="text/css"/> 
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
            <style> 
                .foldable {
                    cursor: pointer;
                }
            </style>
    </head>

    <body>

        <div class="main">

            <?php
            $result = runQuery($db, 'LINEUP', "SELECT id, geometry, target_moran, comparator_below, comparator_offset, variant"
                    . " FROM lineup"
                    . " ORDER BY geometry ASC, target_moran ASC, comparator_below ASC, comparator_offset DESC, variant ASC");
            
            if (!$result) {
                echo "Couldnt load lineup data: " . mysqli_error($db);
                exit;
            }

            function makeGeom() {
                global $currgeom;
                $id = "geom".$currgeom;
                
                echo "<h4 class=\"foldable\" onclick=\"toggle('$id')\";\">";
                echo "Geometry: $currgeom";
                echo "</h4>";
                echo "<div id=\"$id\">";
            }

            function makeMoran() {
                global $currgeom;
                global $currmoran;
                $id = "moran".$currgeom."_".$currmoran;
                
                echo "<h5 class=\"foldable\" onclick=\"toggle('$id')\";\">";
                echo "Moran: $currmoran";
                echo "</h5>";
                echo "<div id=\"$id\">";
            }

            function makeBelow() {
                global $currgeom;
                global $currmoran;
                global $currbelow;
                $id = "below".$currgeom."_".$currmoran."_".$currbelow;

                echo "<h6 class=\"foldable\" onclick=\"toggle('$id')\";\">";
                echo "Below: $currbelow";
                echo "</h6>";
                echo "<div id=\"$id\">";
            }

            function drawlineup($id) {
                global $db;
                $resultlu = runQuery($db, 'LINEUPORDER', "SELECT obfuscated, decoy FROM map WHERE lineupid = $id ORDER BY decoy ASC");
            
                echo '<table class="answertable">';

                $i = 0;
                while (list($obf, $dec) = $resultlu->fetch_row()) {
                    if ($i % 2 === 0) {
                        echo "<tr>";
                    }
                    ?>
                    <td>
                        <img class="answer" src="<?php echo $obf; ?>"/>
                    </td>
                    <?php
                    if ($i % 2 === 1) {
                        echo "</tr>";
                    }
                    $i ++;
                }
                if ($i % 2 === 1) {
                    echo "<td></td>";
                    echo "</tr>";
                }
                echo '</table>';
            }

            $currgeom = null;
            $currmoran = null;
            $currbelow = null;
            while (list($id, $geom, $moran, $below, $offset, $variant) = $result->fetch_row()) {
                if ($currgeom !== $geom) {
                    if ($currgeom !== null) {
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    $currgeom = $geom;
                    $currmoran = $moran;
                    $currbelow = $below;
                    makeGeom();
                    makeMoran();
                    makeBelow();
                } else if ($currmoran !== $moran) {
                    echo '</div>';
                    echo '</div>';
                    $currmoran = $moran;
                    $currbelow = $below;
                    makeMoran();
                    makeBelow();
                } else if ($currbelow !== $below) {
                    echo '</div>';
                    $currbelow = $below;
                    makeBelow();
                }

                echo "<p class='centered'>$offset :: $variant</p>";

                drawlineup($id);
            }

            echo '</div>';
            echo '</div>';
            echo '</div>';
            ?>
        </div>

    </body>
</html>