<?php
session_start();

if (!isset($_SESSION['userID'])) {
    header('Location: error.php?code=PANOID');
    exit;
}
include 'settings.php';

$gender = "U";
$age = 0;
$background = "U";
$country = "";

$familiar = -1;

$events = "";
$comments = "";
$warnfamiliar = false;

if (isset($_POST['submitted'])) {
    if (isset($_POST["gender"])) {
        $gender = $_POST["gender"];
    }

    if (isset($_POST["age"])) {
        $age = $_POST["age"];
    }

    if (isset($_POST["background"])) {
        $background = $_POST["background"];
    }

    $country = $_POST['country'];

    if (isset($_POST['familiar'])) {
        $familiar = $_POST['familiar'];
    } else {
        $warnfamiliar = true;
    }

    $events = $_POST['events'];
    $comments = $_POST['comments'];

    if (!$warnfamiliar) {

        // clean up the comments from apostrophes to avoid problems...
        //$events = str_replace("'", "-", $events);
        //$comments = str_replace("'", "-", $comments);
        //$country = str_replace("'", "-", $country);

        include 'db.php';
        $db = connectDB();

        $totaltime = time() - $_SESSION['indexload'];

        $statement = prepQuery($db, 'PAUPFA', "UPDATE user
                          SET done=true,
                              totaltime=?,
                              endtime=NOW(),
                              age=?,
                              gender=?,
                              background=?,
                              country=?,
                              familiar=?,
                              events=?,
                              comments=?
                          WHERE id = ?;");
        $statement->bind_param('iisssissi'
                , $totaltime
                , $age
                , $gender
                , $background
                , $country
                , $familiar
                , $events
                , $comments
                , $_SESSION['userID']);
        $statement->execute();

        //relink to next page
        header('Location: end.php');
        exit;
    }
}
?>

<html>

    <head>
        <meta charset="UTF-8">
        <title>Questionnaire</title>
        <link href="css/style.php" rel="stylesheet" type="text/css">
        <script>
            function update(field) {
                var slider = document.getElementById('range' + field);
                var label = document.getElementById('span' + field);
                label.innerHTML = slider.value;
            }

            var submitpressed = false;
            window.onbeforeunload = function () {
                if (!submitpressed) {
                    return "Please, do not use the back button or refresh the page, while filling in the questionnaire.";
                }
            };
        </script>
    </head>

    <body>

        <div class="main">
            <form action='participant.php' method='post' onsubmit='submitpressed = true;'>
                <input type="hidden" name="submitted" value="true"/>

                <h2>Section 3: Background information</h2>

                <p>
                    In this last section, we ask you for some additional background information.
                    The first questions are optional (up to and including "Country of residence").
                </p>

                <?php if ($warnfamiliar) { ?>
                    <p class="error">
                      <small>
                        A required answer is missing.
                      </small>
                    </p>
                <?php } ?>

                <hr/>

                <table>
                    <tr>
                        <td>Gender:</td>
                        <td>
                            <label><input type="radio" name="gender" value="M" <?php if ($gender === "M") {
                    echo "checked ";
                } ?>/> Male</label><br/>
                            <label><input type="radio" name="gender" value="F" <?php if ($gender === "F") {
                    echo "checked ";
                } ?>/> Female</label><br/>
                            <label><input type="radio" name="gender" value="U" <?php if ($gender === "U") {
                    echo "checked ";
                } ?>/> No answer</label>
                        </td>
                        <td class="small">
                        </td>
                    </tr>
                    <tr><td colspan="3" class="small">&nbsp;</td></tr>
                    <tr>
                        <td>Age:</td>
                        <td>
                            <input name="age" id="age" type="number" min="0" max="200" value='<?php echo $age; ?>'/>
                        </td>
                        <td class="small">
                            Set to "0" in case you do not want to answer
                        </td>
                    </tr>
                    <tr><td colspan="3" class="small">&nbsp;</td></tr>
                    <tr>
                        <td>Highest degree obtained:</td>
                        <td>
                              <label><input type="radio" name="background" value="school" <?php if ($background === "school") {
                            echo "checked ";
                          } ?>/> High School</label><br/>
                              <label><input type="radio" name="background" value="BA" <?php if ($background === "BA") {
                            echo "checked ";
                          } ?>/> Bachelors </label><br/>
                              <label><input type="radio" name="background" value="MA" <?php if ($background === "MA") {
                            echo "checked ";
                          } ?>/> Masters </label><br/>
                              <label><input type="radio" name="background" value="PhD" <?php if ($background === "PhD") {
                            echo "checked ";
                          } ?>/> PhD </label><br/>
                              <label><input type="radio" name="background" value="other" <?php if ($background === "other") {
                            echo "checked ";
                          } ?>/> Other </label><br/>
                        </td>
                    </tr>
                    <tr><td colspan="3" class="small">&nbsp;</td></tr>
                    <tr>
                        <td class="top">Country of residence:</td>
                        <td>
                            <input size="30" type="text" name="country" id="country" value="<?php echo $country; ?>"/>
                        </td>
                        <td class="small">
                        </td>
                    </tr>
                </table>

                <hr/>

                <p>
                    Before taking this questionnaire, how familiar were you with spatial autocorrelation?
                </p>

<?php if ($warnfamiliar) { ?>
                    <p class="error">
                        Answer required
                    </p>
<?php } ?>

                <table>
                    <tr>
                        <td>
                            <input type="radio" required name="familiar" id="familiar1" value="1" <?php if ($familiar === 1) {
    echo "checked ";
} ?>/>
                        </td>
                        <td class="emph" width="22%">
                            <label for="familiar1">
                                Very unfamiliar
                            </label>
                        </td>
                        <td class="small">
                            I never heard of it before.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" required name="familiar" id="familiar2" value="2" <?php if ($familiar === 2) echo "checked "; ?>/>
                        </td>
                        <td class="emph">
                            <label for="familiar2">
                                Unfamiliar
                            </label>
                        </td>
                        <td class="small">
                            I heard of it, but didn't really know what it meant.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" required name="familiar" id="familiar3" value="3" <?php if ($familiar === 3) echo "checked "; ?>/>
                        </td>
                        <td class="emph">
                            <label for="familiar3">
                                Neutral
                            </label>
                        </td>
                        <td class="small">
                            I knew roughly what it is.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" required name="familiar" id="familiar4" value="4" <?php if ($familiar === 4) echo "checked "; ?>/>
                        </td>
                        <td class="emph">
                            <label for="familiar4">
                                Familiar
                            </label>
                        </td>
                        <td class="small">
                            I have a basic understanding of it.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" required name="familiar" id="familiar5" value="5" <?php if ($familiar === 5) echo "checked "; ?>/>
                        </td>
                        <td class="emph">
                            <label for="familiar5">
                                Very familiar
                            </label>
                        </td>
                        <td class="small">
                            I have a thorough understanding of it.
                        </td>
                    </tr>
                </table>

                <hr/>

                <p>
                    Did anything happen when you were filling in the questionnaire that you feel could affect your results?
                    For example, did you pause to take a phone call, or get a cup of coffee?
                </p>
                <p>
                    <textarea rows="5" maxlength="65000" cols="80" name="events"><?php echo $events; ?></textarea>
                </p>

                <p>
                    If you have any other comments, please fill them in below.
                </p>
                <p>
                    <textarea rows="5" maxlength="65000" cols="80" name="comments"><?php echo $comments; ?></textarea>
                    <br></br>
                </p>

                <p class="centered">
                    <input type="submit" name="end" id="end" value='Submit'/>
                </p>

            </form>
        </div>

    </body>
</html>
