 <?php
 // Server information up here so html errors can't expose our secret ip, username and password
$servername = "";
$username = "";
$password = "";

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>MTASA Build Information</title>
<link rel="stylesheet" type="text/css" href="css.css" />
</head>
<body>
<form action="/">
<div id="test" style="background: -webkit-gradient(linear,left top,left bottom,from(#fff),to(#f1f1f1));
  background: -moz-linear-gradient(top,#fff,#f1f1f1);
  border-bottom: 1px solid #ccc;
  padding: 0 0 0 14px;
  height: 33px;">
  <span style='line-height: 33px;vertical-align: middle;'>
  <a href="/">All</a>  
       
	   &nbsp;
  <a href="?Branch=master">Master</a>  
       
	   &nbsp;
  <a href="?Branch=1.5.0">1.5.0</a>  
      
	  &nbsp;
  <a href="?Branch=1.4.1">1.4.1</a>  
      
	  &nbsp;
	  
  </span>
  <div style="float:right;line-height: 33px;vertical-align: middle;padding-right: 20px;">
     <input name="Author" placeholder="Author filter" value="<? echo $_GET['Author']; ?>">
       
     <input name="Branch" placeholder="Branch filter" value="<? echo $_GET['Branch']; ?>">
	   
     <input name="Revision" placeholder="Revision filter" value="<? echo $_GET['Revision']; ?>">
	   
     <input type="button" onclick="document.location='index.php';" value="Reset" />
     <input type="submit" value="Submit">
  </div>
</div>
</form>
 <div id="maincol">
 <div id="colcontrol">
<div class="list">
 
 <?php
// Get our parameters
$version = $_GET['Branch'] or null;
$revision = $_GET['Revision'] or null;
$user = $_GET['Author'] or null;

// Create connection
$conn = mysqli_connect($servername, $username, $password, "", 54006);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>

<b>Committed Changes</b>
</div>
<table class="results" id="resultstable">
  <tbody>
  <tr style="text-align:center">
    <th style="width:7ex;text-align:center"><b>Rev</b></th>		
    <th style="width:3.5em;text-align:center"><b>Avatar</b></th>		
    <th style="text-align:center"><b>Author</b></th>		
    <th style="text-align:center"><b>Branch</b></th>
    <th style="width:80em;text-align:center"><b>Log Message</b></th>
    <th style="width:24em;text-align:center"><b>Date</b></th>
    <th style="width:50ex;text-align:center"><b>SHA</b></th>	
  </tr>
  
<?php


// it works... I don't even...
if ( $version == "master" )
{
	$subquery = $subquery . " WHERE Version='master'";
	if ($revision == "latest")
	{
		$subquery = $subquery . " and Revision=(SELECT max(Revision) from mta_gitstuff.github WHERE Version='master')";
	}
	else if ( $revision != null )
	{
		$subquery = $subquery . " and Revision='" . mysqli_real_escape_string ( $conn, $revision ) . "'";
	}
	if ($user != null )
	{
		$subquery = $subquery . " and Author='" . mysqli_real_escape_string ( $conn, $user )  . "'";
	}
}
else if ( $version == null )
{
	if ( $revision != null )
	{
		if ($revision == "latest")
		{
			$subquery = $subquery . " WHERE Revision=(SELECT max(Revision) from mta_gitstuff.github)";
		}
		else 
		{
			$subquery = $subquery . " WHERE Revision='" . mysqli_real_escape_string ( $conn, $revision )  . "'";
		}
		if ($user != null )
		{
			$subquery = $subquery . " and Author LIKE '" . mysqli_real_escape_string ( $conn, $user )  . "%'";
		}
	}
	else
	{
		if ($user != null )
		{
			$subquery = $subquery . " WHERE Author LIKE '" . mysqli_real_escape_string ( $conn, $user )  . "%'";
		}
	}
}
else
{
	$subquery = $subquery . " WHERE Version='" . mysqli_real_escape_string ( $conn, $version )  . "'";
	if ($revision == "latest")
	{
		$subquery = $subquery . " and Revision=(SELECT max(Revision) from mta_gitstuff.github WHERE Version='" . mysqli_real_escape_string ( $conn, $version ) . "')";
	}
	else if ( $revision != null )
	{
		$subquery = $subquery . " and Revision='" . mysqli_real_escape_string ( $conn, $revision )  . "'";
	}
	if ($user != null )
	{
		$subquery = $subquery . " and Author LIKE '" . mysqli_real_escape_string ( $conn, $user )  . "%'";
	}
}

$page = mysqli_real_escape_string ( $conn, $page );


$lowerLimit = $page*5;
$upperLimit = 5;

// Create a select statement
$sql = "SELECT Revision, URL, LogMessage, DATE_FORMAT(Date, '%e %b, %Y') as Date, SHA, Author, AuthorAvatarURL, AuthorURL, Version FROM mta_gitstuff.github";

// add in our where clauses
$sql = $sql . $subquery;

// order by revision descending

$sql = $sql . "  ORDER BY Revision DESC;";

// start the query
$result = $conn->query($sql);

// make sure we have some rows
if ($result->num_rows > 0) {
	// binary true/false for even and odd highlighting
	$bTest = false;
	// revision number variable so we don't print the revision number more than once
	$rev = 0;
	$border = "<td style='border-top: 0px solid #ccc; border-bottom: 0px solid #ccc;";
	$bBorderChange = false;
	// while we have a result
    while($row = $result->fetch_assoc()) {
	
		// odd  (impacts highlighting)
		if ( $bTest == true )
		{
			echo "<tr onclick=\"document.location ='" . $row["URL"]  ."'\">\n";
		}
		// even (impacts highlighting)
		else
		{
			echo "<tr class='even' onclick=\"document.location ='" . $row["URL"]  ."'\">\n";
		}
		// invert bTest
		$bTest = !$bTest;
		
		// if this is a new revision
		if ($rev != $row["Revision"])
		{
			echo $border . "border-right: 1px solid #ccc;text-align:center;'>";
			// output the revision cell
			echo "<a href='?Revision=" . $row["Revision"] . "&amp;Branch=" . $version . "'>r" . $row["Revision"] . "</a>" . "</td>\n"; 
			$rev = $row["Revision"];
		}
		else
		{
			// blank revision cell
			echo "<td style='border-top: 0px solid #ccc; border-bottom: 0px solid #ccc; border-right: 1px solid #ccc;'>" . "     </td>\n";
		}
		
		if ( $bBorderChange == true )
		{
			$border = "<td style='border-top: 1px solid #ccc; border-bottom: 0px solid #ccc;";
		}
		$bBorderChange = true;
		
		// output our Author and his avatar and set the max column width
		echo $border . "border-right: 0px solid #ccc;text-align:center;'><img style='vertical-align:middle;' src='" . $row["AuthorAvatarURL"] . "' height='25' alt='Avatar' /> </td>\n";
		$splitAuthor = explode ( '@', $row["Author"] );
		echo $border . "border-right: 0px solid #ccc;'>" . $splitAuthor[0] . "</td>\n"; 
		
		// master branch is just null in the database
		$modifiedVersion = $row["Version"] ? $row["Version"] : "master";
		// output our Branch and set the max column width
		echo $border . "border-right: 0px solid #ccc;text-align:center;'><a href='?Branch=" . $modifiedVersion . "&amp;Revision=" . $revision . "'>" . $modifiedVersion . "</a></td>\n"; 
		
		// output our Log Message, set the max column width and replace any new lines with br tags
//		echo $border . "border-right: 0px solid #ccc;'>" . str_replace ( "\n", "<br /><br />", str_replace ("\n\n", "\n", str_replace ( '>', "&gt;", str_replace ( '<', "&lt;", $row["LogMessage"]) ) ) ) . "</td>\n"; 	
        // OMG - Fiddled with by *someone*
        echo $border . "border-right: 0px solid #ccc;'>"; 
        $lineList = array_filter( explode( "\n", $row["LogMessage"] ) );
        foreach( $lineList as $line )
        {
            // Indent wrapped lines, 5px between lines
            echo "<span style='display: block; padding-left: 0.80em; text-indent:-0.80em; margin: 5px 0;'>"; 
            echo "• " . $line . "<br />"; 
            echo "</span>";
        }
        echo "</td>\n"; 

		// output our Date and set the max column width
		echo $border . "border-right: 0px solid #ccc;'>" . $row["Date"] . "</td>"; 
		
		// output our SHA and set the max column width
		echo $border . "border-right: 0px solid #ccc;'>" . $row["SHA"] . "</td>\n"; 
		
		// end of row
		echo "</tr>\n";
	}
} else {
    // new row
	echo "<tr>\n";
    // cell spans the whole width
	echo "<td style='text-align:center;' colspan='7'>\n";
	// no results
    echo "<strong>0 results</strong>\n";
	// close the cell and row tags
	echo "</td>\n";
	echo "</tr>\n";
}

// close the database connection
$conn->close();

?>

	<tr onclick="document.location='https://code.google.com/p/mtasa-blue/source/list'">
	<td colspan='7' style='border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;text-align:center;'>
		<strong>Previous history is available at <a style="text-decoration:underline;" href="https://code.google.com/p/mtasa-blue/source/list">our Google Code repository</a></strong>
	</td>
	</tr>
  </tbody>
</table>
 </div>
 </div>
 </body>
 </html>
