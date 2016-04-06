<?php 

// clear any used session variables, except 'ratio'

unset($_SESSION['userID']);
unset($_SESSION['amt_key']);
unset($_SESSION['order']);

unset($_SESSION['staircase']);
unset($_SESSION['sincelastpauze']);
unset($_SESSION['newstaircase']);
unset($_SESSION['comparator_offsets']);
unset($_SESSION['offset_variants']);
unset($_SESSION['curroffset']);
unset($_SESSION['providedoffsets']);

unset($_SESSION['lastshownquestion']);
unset($_SESSION['currentquestion']);
unset($_SESSION['lastcorrect']);

unset($_SESSION['left_id']);
unset($_SESSION['left_path']);
unset($_SESSION['right_id']);
unset($_SESSION['right_path']);
unset($_SESSION['correctid']);
unset($_SESSION['starttime']);

?>