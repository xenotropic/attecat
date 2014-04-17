<html>
<head>
<meta charset="utf-8">
<script src="./jquery-1.9.1.js"></script>
<script src="./jquery-ui.js"></script>
<link rel="stylesheet" href="./jquery-ui.css">
<link rel="stylesheet" href="./style.css">
<title>Attecat</title>
<script>
   $(function() {
       $( "#tabs" ).tabs();
     });

$(function() {
    $( document ).tooltip();
  });


$(function() {

    $( "#datepicker" ).datepicker({ beforeShowDay: $.datepicker.noWeekends });

  });

$(function() {
    $('#group_action_selector').change(function(){
        $('.group_actions').hide();
        $('#' + $(this).val()).show();
      });
  });

// enter moves down in form
$(function() {
    $('table input').keypress(function(e) {
	if (e.keyCode == 13) {
	  var $this = $(this),
            index = $this.closest('td').index();
	  $this.closest('tr').next().find('td').eq(index).find('input').focus();
	  e.preventDefault();
	}
      });

  });


  function keepSessionAlive() {
  $.post("ping.php");
}
 
$(function() { window.setInterval("keepSessionAlive()", 60000); });

  </script>
<style>
  label {
display: inline-block;
width: 5em;
    }
  </style>

</head>
<body>
<?
$db = new SQLite3 ('./db/student_attendance.db');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
require_once ("passwordhash.php");
ini_set('session.gc_maxlifetime', 604800);

    // Security and password processing
if (empty($_SERVER['HTTPS'])) {
  echo "Attecat only works over SSL. Try adding https:// to the URL in the address bar. If that does not work, contact your system administrator.";
  exit;
}
session_start();

if ($_POST['Logout']=="Sign Out") {
  session_destroy();
  $_SESSION = array();
  echo " ";
}
$result = $db->query ("SELECT * FROM admin");
$row = $result->fetchArray(SQLITE3_ASSOC);
if ($row != FALSE &! $_SESSION['logged_in']) { // a password has been set since result is not false (empty), and session says we are not logged in
  $hasher = new PasswordHash (8, false);
  $password = $_POST['login_password'];
  if (strlen($password) > 48)  die("Password must be 48 characters or less."); 
  $check = $hasher->CheckPassword ($row['salt'] . $password, $row['password'] );
  if ($check) {
    $_SESSION['logged_in']=TRUE;
  } else {    // no password or wrong password, form for logging in and exit
    echo "<p> <p> <center><div class=\"RoundTable\"><table><tr><td><form method=POST action=\"./\">Password: <input type=password name=\"login_password\"><input type=submit value=\"Submit\" name=\"login_submit\"></form></td></tr></table></div></body></html>";
    exit;
  }
}
echo "<table border=0 width=\"100%\"><tr><td width=\"95%\" style=\"vertical-align:center\"><CENTER><H3 style=\"font-family:Helvetica\">Attecat Attendance Tracker</h3></center><td style=\"font-size: small; font-weight: normal; text-align: center\">";
if ($row != FALSE ) echo "<form method=POST><input type=submit name=\"Logout\" value=\"Sign Out\">";
echo "</table></form></div>";
?>
<div id="tabs">
<ul>
<li><a href="#tabs-1">Show Group</a></li>
<li><a href="#tabs-2">Add Group</a></li>
<li><a href="#tabs-3">Student Record</a></li>
<li><a href="#tabs-5">Admin</a></li>
</ul>

<?
// Logic for processing "Add Group" tab post -- putting here since needs to happen pre-tab 1
$status; $tab1message;
if ($_POST['newstudents'] == "Submit") {
  foreach ($_POST as $param_name => $param_val) {
    $$param_name = $param_val; // assiging post variables to have those names, e.g., $section
  }
  $student_array = multiexplode ( array(",", PHP_EOL), $student_names);
  $array_size = count ($student_array);
  for ( $i = 0; $i < $array_size; $i++ ) {
    $statement = $db->prepare ('INSERT INTO student (student_name, current_section, current_student ) VALUES (:name, :section, 1)');
    $statement->bindValue (':name', $student_array[$i], SQLITE3_TEXT);
    $statement->bindValue (':section', $section, SQLITE3_TEXT);
    $status = $statement->execute();
    if ( get_class ($status) == "SQLite3Result"  ) $tab1message = "Group for $section added.";
  }
}


  // logic for processing new attendance records - again so shows up on tab 1
if ($_POST['process_new_records']=="Submit") {
  $students = explode ( "%", $_POST['student_list']);
  foreach ( $students as $student ) {
    if ($student == "") continue;
    $stmt = $db->prepare ('INSERT INTO attendance_record (student_id, attendance, section, notes, attendance_date) VALUES (:student_id, :attendance, :section, :notes, :attendance_date)');
    $stmt->bindValue (':student_id', $student);
    $stmt->bindValue (':attendance',  $_POST['att'.$student]);
    $stmt->bindValue (':section', $_POST['section']);
    $stmt->bindValue (':notes', $_POST['note'.$student]);
    $stmt->bindValue (':attendance_date', date ("Y-m-d", strtotime ($_POST['new_date'])));
    $results=$stmt->execute();
    if ( $results == FALSE ) echo "<b>Insert statement failed</b>";
  } 
}


?>

<!-- ***** Tab showing existing group and add new daily record -->

<div id="tabs-1">

<!-- ***** Form for adding new attendence data for a group -->

<?
  if ($tab1message != NULL) echo "<p><b>$tab1message</b>";

if ( $_GET['section'] != "" ) echo "Showing <b>" . $_GET['section'] . "</b>.";


   // Processing logic for adding to notepad for whole section
if ($_POST['before_after_submit']=="Submit") {
  $students = explode ( "%", rtrim ($_POST['studentlist'], "%") ); // removing trailing pct sign    
  $success = TRUE;
  foreach ( $students as $student ) {
    $student = rtrim ($student);
    $newnotes = $_POST['newnotes'.$student];
    $statement;
    if ($_POST['before_after'] == 'before') {
      $statement = $db->prepare('UPDATE student SET notes = :newnotes || notes WHERE student_id=:student_id');
      $newnotes .= PHP_EOL;
    } else { // adding to end of notepad
      $statement = $db->prepare('UPDATE student SET notes = notes || :newnotes WHERE student_id=:student_id');
      $newnotes = PHP_EOL . $newnotes;
    }
    $statement->bindValue (':student_id', $student);
    $statement->bindValue (':newnotes', $newnotes);
    $results = $statement->execute();
    if ( $results = FALSE ) {
      $success=FALSE;
      break;
    }
  }
  if ($success ) echo "Updated notepads for " . sizeof ($students) . " students.<p>"; else echo "Notepad update failed.";

}

?>
<p><form style=" display: inline; ">
Select <? if ( $_GET['section'] != "" ) echo "another" ?> section:
 <select name="section" onchange='this.form.submit()'>
<? 
$results = $db->query('SELECT DISTINCT current_section FROM student WHERE current_student=1');

if ( $_GET['section'] == NULL ) echo "<option value=\"\" selected> </option>";

while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
  echo "<option value=\"".$row['current_section']."\"";
  if ( $row['current_section'] == $_GET['section']) echo " selected";
  echo ">".$row['current_section']."</option>";
}

?>
</select>
<noscript><input type=submit name="show_section" value="Submit"></noscript>
</form>

<!-- This drop down will switch whether daily attendance or notepad addition is shown -->
  

<?

  if ($_GET['section'] != "")  echo "and <select id=\"group_action_selector\"><option value=\"show_attendance\">take attendance</option><option value=\"add_to_notepad\">add to notepad</option></select><hr>";
echo "<p>";
  // showing attendance table/form
if ($_GET['section']!="") {
 
  echo "<div id=\"show_attendance\" class=\"group_actions\">";
  $statement = $db->prepare('SELECT DISTINCT attendance_date FROM attendance_record WHERE section=:section ORDER BY attendance_date DESC LIMIT 5');
  $statement->bindValue (':section', $_GET['section']);
  $results = $statement->execute();
  $date_array = array();
  while ($att_array = $results->fetchArray(SQLITE3_ASSOC)) {
    array_push ($date_array, $att_array['attendance_date']);
  }
  $past_date_count = count ($att_array);

  // the event.keyCode prevents submitting via Enter
  echo "<div class=\"RoundTable\"><table><form onkeypress=\"return event.keyCode != 13;\" action=\"./?section=".$_GET['section']."\" method=post><tr><td><b>Student Name</b></td><td colspan=2>New Attendance for <input type=text name=new_date id=\"datepicker\" value=\"". date ( "m/d/y" ) ."\"></td>";
  foreach ( $date_array as $date ) echo ("<td>" . date ( "D. M. j", strtotime($date)) . "</td>" );
  echo "</tr>";
  $statement = $db->prepare('SELECT student_name, notes, student_id FROM student WHERE current_section=:section AND current_student=1');
  $statement->bindValue (':section', $_GET['section']);
  $results = $statement->execute();
  $studentlist = "";
  while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
    echo "<tr><td><a href=\"./?section=".$_GET['section']."&showid=".$row['student_id']."#tabs-3\">" . $row['student_name'] . "</a></td><td>Attendance: <input type=text maxlength=5 size=5 name=\"att".$row['student_id']."\"></td><td>Notes:<input type=text name=\"note".$row['student_id']."\"></td>";
    foreach ( $date_array as $date ) { // going through list of dates from above to get attendance
      $statement2 = $db->prepare('SELECT attendance, notes FROM attendance_record WHERE student_id=:student_id AND attendance_date=:date');
      $statement2->bindValue (':student_id', $row['student_id']);
      $statement2->bindValue (':date', $date );
      $att_results = $statement2->execute();
      $att_result = $att_results->fetchArray(SQLITE3_ASSOC); // no while loop because should only be one record per date/student
      echo "<td";
      if ($att_result['attendance']=="A") echo " style=\"background-color:#E1E667\"";
      echo "><b>" . $att_result['attendance'] . "</b> - ";   // attendance record
      if ( $att_result['notes'] != "" ) {
	if (strlen($att_result['notes']) < 10) echo $att_result['notes']; else  
	  echo "<a title=\"" . htmlspecialchars ($att_result['notes']) . "\"> ".substr($att_result['notes'], 0, 10)." [...]</a>";
      }
      echo "</td>";
    }
    echo "</tr>";
    $studentlist .= $row['student_id'];
    $studentlist .= "%";
  }
  $studentlist = rtrim ($studentlist, "%"); // removing trailing pct sign
    // need to put hidden field that gives $studentlist to the processing logic
  echo "<tr><td colspan=3><input type=submit name=\"process_new_records\" value=\"Submit\"><input type=hidden name=\"section\" value=\"".$_GET['section']."\"><input type=hidden name=\"student_list\" value=\"$studentlist\"></tr>";
  echo "</form></table></div><!-- closing div for roundtable formatting --></div><!-- closing div for show attendance table/form for show/hide purposes -->";
}

?>

<div id="add_to_notepad" class="group_actions" style="display:none">

<?
  // Form for adding to notepad for section
if ($_GET['submit']="show_section") {
  echo "<form method=post action=\"./?section=".$_GET['section']."\">Add to notepad <select name=\"before_after\"><option value=\"before\">before</option><option value=\"after\">after</option></select> existing content.<p>";
  $statement = $db->prepare('SELECT student_name, student_id FROM student WHERE current_section=:section AND current_student=1');
  $statement->bindValue (':section', $_GET['section']);
  $results = $statement->execute();
  echo "<div class=\"RoundTable\"><table><tr><td>Student Name</td><td>Notepad Addition</td></tr>";
  $studentlist="";
  while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
    echo "<tr><td style=\"width: 25%\">".$row['student_name']."</td><td><input type=text style=\" width: 100%; \" name=\"newnotes".$row['student_id']."\"></td></tr>";
    $studentlist .= rtrim ($row['student_id'], ' ') . "%";
  }
  echo "<tr><td colspan=2 style=\"align: center; \"><input type=submit name=\"before_after_submit\" value=\"Submit\"></td></tr>";
  echo "</table></div>";
  echo "<input type=\"hidden\" name=\"studentlist\" value=\"".$studentlist."\"></form>";
} else echo "No section to show.";

?>

</div><!-- closing div for adding to notepads -->

</div><!-- closing div for jquery tab -->

<!-- ***** Form for adding new group of students -->

<div id="tabs-2">
   <p>This is the tab for adding a new group of new students, separated by commas or newlines.
<form action="./#tabs-1" method=post>
   Section: <input type=text name="section"><p>
   Student names:<br><textarea rows=10 cols=60 name="student_names"></textarea>
   <br><input type=submit name="newstudents" value="Submit">
</form>

</div>

<!-- ***** Tab for showing information on a single student -->

<div id="tabs-3">

<?
   // Processing submission of notepad

if ($_POST['update_notepad']=="Update") {
  $statement = $db->prepare('UPDATE student SET notes=:notes WHERE student_id=:student_id');
  $statement->bindValue (':student_id', $_POST['student_id']);
  $statement->bindValue (':notes', $_POST['notepad_update_text']);
  $results = $statement->execute();
}

if (is_numeric($_GET['showid'])) {
  $statement = $db->prepare('SELECT student_name, current_section, notes FROM student WHERE student_id=:student_id');
  $statement->bindValue (':student_id', $_GET['showid']);
  $results = $statement->execute();
  $row = $results->fetchArray(SQLITE3_ASSOC);
  if ( $row != FALSE ) {
    echo "<center><b>".$row['student_name']." in ".$row['current_section']."</b></center><p>";
  } else echo "No record found for student " . $_GET['showid'];

  $statement = $db->prepare('SELECT * FROM attendance_record WHERE student_id=:student_id');
  $statement->bindValue (':student_id', $_GET['showid']);
  $results = $statement->execute();
  echo "<div class=\"RoundTable\"><table><tr><td>Date</td><td>Score</td><td>Notes</td></tr>";
  while ($row2 = $results->fetchArray(SQLITE3_ASSOC)) { 
    echo "<tr";
    if ($row2['attendance']=="A") echo " style=\"background-color:#E1E667\"";
    echo "><td>".$row2['attendance_date']."</td><td>".$row2['attendance']."</td><td>".$row2['notes']."</td>";
  }
  echo "</tr></table></div>";
  echo "<p><center>Notepad for ".$row['student_name']."<p><form method=post><textarea cols=100 rows=10 name=\"notepad_update_text\">".$row['notes']."</textarea><input type=\"hidden\" name=\"student_id\" value=\"".$_GET['showid']."\"><input type=submit name=\"update_notepad\" value=\"Update\" style=\"vertical-align:bottom\"></form></center>";
} else echo "<p>No student selected.";
?>

</div> <!-- end of student record tab -->

<div id="tabs-5"> <!-- admin tab -->

<?
     // Processing logic for password setting

if ( $_POST['init_password_set']=='Submit' ) {
  $hasher = new PasswordHash (8, false);
  $password = $_POST['init_pass'];
  if (strlen($password) > 48) die( "Password must be 48 characters or less"); 
  $salt = openssl_random_pseudo_bytes ( 64 );
  $hash = $hasher->HashPassword ( $salt . $password );
  $db->query ('DELETE FROM admin'); // May want something less thorough later . . . 
  $statement = $db->prepare('INSERT INTO admin (password, salt) VALUES (:password, :salt)');
  $statement->bindValue (':password', $hash);
  $statement->bindValue (':salt', $salt);
  $results = $statement->execute();
  if ($results != FALSE) echo "Password set.<p>";}
else if ( $_SESSION['logged_in'] ) {
  if ($_POST['password_change_request']=="Change Password") {
    if ($_POST['change_pass_1'] != $_POST['change_pass_2']) {
      echo "<b>Passwords did not match.";
    } else {
      $hasher = new PasswordHash (8, false);
      $password = $_POST['change_pass_1'];
      if (strlen($password) > 48) die( "Password must be 48 characters or less"); 
      $salt = openssl_random_pseudo_bytes ( 64 );
      $hash = $hasher->HashPassword ( $salt . $password );
      $statement = $db->prepare('UPDATE admin SET password=:password, salt=:salt');
      $statement->bindValue (':password', $hash);
      $statement->bindValue (':salt', $salt);
      $results = $statement->execute();
      if ($results != FALSE) echo "Password changed.<p>"; else echo "Error changing password.";
    }
  }
  echo "<div class=\"RoundTable\"><table><tr><td colspan=2><b>Password Change</b></td></tr><tr><td> Existing password:</td><td> <form action=\"./index.php#tabs-5\" method=POST><input type=password name=\"pwd_check_on_change\">  </td></tr><tr><td>New password:</td><td><input type=password name=\"change_pass_1\"> </td></tr><tr><td>Confirm new password:</td><td><input type=password name=\"change_pass_2\"></td></tr><tr><td colspan=2><center><input type=submit name=\"password_change_request\" value=\"Change Password\"></center></form></td></tr></table></div>";
} else {  // We're here but not logged in, so must be no password set.
    echo "Set password: <p><form method=post action=\"./index.php#tabs-5\"> <input type=password name=\"init_pass\"><input type=submit name=\"init_password_set\" value=\"Submit\"></form>";
}


// Functions

function multiexplode ($delimiters,$string) {
    
  $ready = str_replace($delimiters, $delimiters[0], $string);
  $launch = explode($delimiters[0], $ready);
  return  $launch;
}

?>

</div> <!-- end of admin tab -->
</div> <!-- end of all tabs -->
</body>
</html>
<?  $db->close(); ?>