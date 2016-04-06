<?php
header("Content-type: text/css; charset: UTF-8");

session_start();

$ratio = 1;
if (isset($_SESSION['ratio'])) {
    $ratio = $_SESSION['ratio'];
}

function sizecm($measure) {
    // 18 cm -> 833px
    global $ratio;
    return $measure/18 * 833 * $ratio . 'px';
}

function sizepx($measure) {
    // 18 cm -> 833px
    global $ratio;
    return $measure * $ratio . 'px';
}
?>

/************************************************************************************
GENERAL STYLING
*************************************************************************************/
body, html, p, div, span, td, tr, table {
font: <?php echo sizecm(0.35); ?>/150% "Avenir"; font-family:"Avenir", "Arial", "sans-serif";
}

body, html {
color: #000;
margin: 0;
padding: 0;
text-align: center;
background: #000;
height: 100%;
}


.main {
margin: 0 auto;
padding: <?php echo sizecm(0.15); ?> <?php echo sizecm(1.5); ?> <?php echo sizecm(0.25); ?>;
width: <?php echo sizecm(18); ?>;
min-height: 87.5%;
height: auto;
text-align: left;
background: #fff;
}

hr {
margin-top: <?php echo sizecm(0.5); ?>;
margin-bottom: <?php echo sizecm(0.5); ?>;
clear: both;
}

.smallsvg {
border: 1px solid black;
width: <?php echo sizecm(5.0); ?>;
height: <?php echo sizecm(5.0); ?>;
float: right;
margin-bottom: <?php echo sizecm(0.5); ?>;
margin-left: <?php echo sizecm(0.5); ?>;
margin-top: 0;
margin-right: 0;
}

.threesvg {
border: 1px solid black;
width: <?php echo sizecm(5.8); ?>;
height: <?php echo sizecm(5.8); ?>;
margin: 0;
}

.prototypesvg {
border: 1px solid black;
width: <?php echo sizecm(7); ?>;
height: <?php echo sizecm(7); ?>;
margin-bottom: <?php echo sizecm(0.5); ?>;
margin-left: 0;
margin-top: 0;
margin-right: <?php echo sizecm(0.5); ?>;
}

.centersvg {
width: <?php echo sizecm(13.7); ?>;
margin: 0 auto <?php echo sizecm(0.1); ?>;
}

.bigsvg {
border: 1px solid black;
width: <?php echo sizecm(13.7); ?>;
height: <?php echo sizecm(13.7); ?>;
margin: 0;
}

.answertable {
margin: 0 auto <?php echo sizecm(0.1); ?>;
}

.answer {
width: <?php echo sizecm(8.5); ?>;
height: auto;
margin: 0;
border: 1px solid #bbb;
cursor: pointer;
}
.answer:hover {
border: 1px solid #111;
}

.hidden {
display: none;
}

.configsvg {
border: 1px solid black;
width: <?php echo sizecm(17.5); ?>;
height: <?php echo sizecm(17.5); ?>;
margin: <?php echo sizecm(0.5); ?> 0 0;
}

.inlinesvg {
border: 0;
height: <?php echo sizecm(0.45); ?>;
margin: 0 0 <?php echo sizecm(-0.1); ?>;
}

#disablednote {
margin-left: <?php echo sizecm(0.25); ?>;
color: #f00;
}

a {
text-decoration: none;
outline: none;
color: #026acb;
}
a:hover {
text-decoration: underline;
}
.centered{
text-align: center;
}

h1, h2, h3, h4, h5, h6 {
margin: 0 0 <?php echo sizecm(0.3); ?>;
color: #333;
text-align: center;
}
h1 {
font-size: 180%;
}
h2 {
font-size: 100%;
}
h3 {
font-size: 125%;
}
h4 {
font-size: 115%;
}
h5 {
font-size: 105%;
}
h6 {
font-size: 100%;
}
p {
margin: <?php echo sizecm(0.10); ?> 0;
text-align: justify;
}
.small {
font-size: 100%;
}

.emph {
font-weight: bold;
font-style: italic;
}

td {
vertical-align: top;
}

.midalign {
vertical-align: middle;
}

/* progress */
.progressoutline {
margin-left: auto;
margin-right: auto;
margin-top: 0;
margin-bottom: <?php echo sizecm(0.5); ?>;
height: <?php echo sizecm(0.5); ?>;
width: <?php echo sizecm(9); ?>;
border: <?php echo sizecm(0.1); ?> solid #ccc;
}

.progressfill {
height: <?php echo sizecm(0.5); ?>;
background-color: #ddd;
margin-left: 0;
margin-right: auto;
}

/* forms */
form {
margin: 0;
padding: 0;
}

input[type='submit']{
width:  <?php echo sizecm(6); ?>;
}

input[type='button']:enabled, input[type='submit']:enabled, input[type='text']:enabled {
background: #ccc;
border: <?php echo sizecm(0.1); ?> #000;
height: 1.5em;
font-size: 1.0em;
font-family: "Avenir";
line-height: 1.2em;
}

input[type='button']:disabled, input[type='submit']:disabled, input[type='text']:enabled {
background: #eee;
border: <?php echo sizecm(0.1); ?> #000;
height: 1.5em;
font-size: 0.8em;
line-height: 1.2em;
}

<?php if (true) { ?>
input[type=range] {
  width: <?php echo sizecm(9); ?>;
}
<?php } else { ?>
input[type=range] {
  -webkit-appearance: none;
  width: <?php echo sizecm(9); ?>;
  margin: <?php echo sizepx(1); ?> 0;
}
input[type=range]:focus {
  outline: none;
}
input[type=range]::-webkit-slider-runnable-track {
  width: 100%;
  height: <?php echo sizepx(8.4); ?>;
  cursor: pointer;
  background: #3071a9;
  border-radius: <?php echo sizepx(24.8); ?>;
  border: <?php echo sizepx(0.2); ?> solid #010101;
}
input[type=range]::-webkit-slider-thumb {
  border: <?php echo sizepx(1); ?> solid #000000;
  height: <?php echo sizepx(15); ?>;
  width: <?php echo sizepx(7); ?>;
  border-radius: <?php echo sizepx(10); ?>;
  background: #ffffff;
  cursor: pointer;
  -webkit-appearance: none;
  margin-top: <?php echo sizepx(-3.5); ?>;
}
input[type=range]:focus::-webkit-slider-runnable-track {
  background: #367ebd;
}
input[type=range]::-moz-range-track {
  width: 100%;
  height: <?php echo sizepx(8.4); ?>;
  cursor: pointer;
  background: #3071a9;
  border-radius: <?php echo sizepx(24.8); ?>;
  border: <?php echo sizepx(0.2); ?> solid #010101;
}
input[type=range]::-moz-range-thumb {
  border: <?php echo sizepx(1); ?> solid #000000;
  height: <?php echo sizepx(15); ?>;
  width: <?php echo sizepx(7); ?>;
  border-radius: <?php echo sizepx(10); ?>;
  background: #ffffff;
  cursor: pointer;
}
input[type=range]::-ms-track {
  width: 100%;
  height: <?php echo sizepx(8.4); ?>;
  cursor: pointer;
  background: transparent;
  border-color: transparent;
  color: transparent;
}
input[type=range]::-ms-fill-lower {
  background: #2a6495;
  border: <?php echo sizepx(0.2); ?> solid #010101;
  border-radius: <?php echo sizepx(49.6); ?>;
}
input[type=range]::-ms-fill-upper {
  background: #3071a9;
  border: <?php echo sizepx(0.2); ?> solid #010101;
  border-radius: <?php echo sizepx(49.6); ?>;
}
input[type=range]::-ms-thumb {
  border: <?php echo sizepx(1); ?> solid #000000;
  height: <?php echo sizepx(15); ?>;
  width: <?php echo sizepx(7); ?>;
  border-radius: <?php echo sizepx(10); ?>;
  background: #ffffff;
  cursor: pointer;
  height: <?php echo sizepx(8.4); ?>;
}
input[type=range]:focus::-ms-fill-lower {
  background: #3071a9;
}
input[type=range]:focus::-ms-fill-upper {
  background: #367ebd;
}
<?php } ?>

input[type='radio'], input[type='checkbox'] {
border: <?php echo sizecm(0.1); ?> #000;
height: 1em;
width: 1em;
}

img {
    width:100%;
}


/*
 PARTICIPANT
*/

.error {
color: red;
}

.preftable {
margin-left: auto;
margin-right: auto;
}

.preftable input[type='range'] {
width: <?php echo sizecm(6); ?>;
}

/*
DISTRIBUTION
*/

.distTable {
width: 100%;
}

.distTable, .distTable tbody, .distTable tr, .distTable td {
padding: 0px;
margin: 0px;
}

.distSliderCell {
text-align: center;
width: 100%;
}

.distSliderCell>input {
width: 100%;
margin-left: auto;
margin-right: auto;
}

.distMarkCell {
height: 0.25cm;
font-size: 1px;
width: 12.5%;
}

.distMarkLeft {
border-left: 1px solid black;
}

.distMarkRight {
border-right: 1px solid black;
}

.distTextLeft {
text-align: left;
}

.distTextMid {
text-align: center;
}

.distTextRight {
text-align: right;
}

/*
PATH
*/

.pathanswertable {
width: 100%;
}

.pathanswertableleft {
text-align: left;
width: 35%;
}

.pathanswertablemid {
text-align: center;
width: 30%;
}

.pathanswertableright {
text-align: right;
width: 35%;
}

.flashing {
animation-name: flash;
animation-duration: 2s;
animation-iteration-count: infinite;
animation-direction: alternate;
animation-timing-function: linear;

-webkit-animation-name: flash;
-webkit-animation-duration: 2s;
-webkit-animation-iteration-count: infinite;
-webkit-animation-direction: alternate;
-webkit-animation-timing-function: linear;
}

@keyframes flash {
  0% {opacity: 1.0;}
 25% {opacity: 0.9;}
 50% {opacity: 0.7;}
 75% {opacity: 0.4;}
100% {opacity: 0.1;}
}

@-webkit-keyframes flash {
  0% {opacity: 1.0;}
 25% {opacity: 0.9;}
 50% {opacity: 0.7;}
 75% {opacity: 0.4;}
100% {opacity: 0.1;}
}
