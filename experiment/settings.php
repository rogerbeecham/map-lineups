<?php

$debug = false;

$askAMTid = false;

$practiceGeometry = 7;
$practiceMoran = 0.6;
$practiceBelow = false;

$forward = 1;
$backward = 3;
$practiceIterations = 12;
$maxIterations = 50;
$numBrackets = 3;
$bracketSize = 8;
$maxVariants = 13;
$Fcrit = 2.57456939;
// Fcrit(2,31) = F crit (numBrackets-1, (bracketSize - 1) * numBrackets) at alpha = 0.1
// http://www.danielsoper.com/statcalc3/calc.aspx?id=4

$participantsPerGroup = 15; //, number for starting with low and high separately, e.g., 15 results in 2x15 = 30 people doing each geometry/moran's I combination (both above and below approach)
$timeToDone = 25; // in minutes!
$morandiff = 0.3;

$pauzeAfter = 100; // set to something high to disable
$showCorrect = true;
$showCorrectTime = 800; // in ms

?>
