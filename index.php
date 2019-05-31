<?php
include 'config.php';

$ITEMS_PER_PAGE = 10;
$LINE_LIMIT = 3;
$LINE_LIMIT_ENABLED = !isset($_GET['full']) || $_GET['full'] == 'false';


function CheckParam ( $param )
{
	if ( isset($_GET[$param]))
	{
		return $_GET[$param];
	}
	return null;
}

function RedirectToGoogleCode ( $version, $revision )
{
	if ($version != null)
	{
		echo "<script type=\"text/javascript\">location.replace('https://code.google.com/p/mtasa-blue/source/list?path=/";
		if ($version != "trunk" && $version != "master")
		{
			echo "branches/$version/";
		}
		else
		{
			echo "trunk/";
		}
		if ($revision != null)
		{
			echo "&start=$revision');</script>";
		}
		else
		{
			echo "&start=7088');</script>";
		}
	}
	else
	{
		echo "<script type=\"text/javascript\">location.replace('https://code.google.com/p/mtasa-blue/source/list?num=25";
		if ($revision != null)
		{
			echo "&start=$revision');</script>";
		}
		else
		{
			echo "&start=7088');</script>";
		}
	}
	die();
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>MTASA Build Information</title>
<link rel="stylesheet" type="text/css" href="css.css" />
<script type="text/javascript">
function OpenURL(e, url){

    if(e.ctrlKey){
	   window.open(url);
   }
   else {
       document.location = url;
   }
   return false;
};

function toggleFullMessages() {
	const url = new URL(window.location);
	const tick = "✔";
	if (url.searchParams.has("full", tick)) {
		url.searchParams.delete("full");
	} else {
		url.searchParams.set("full", tick);
	}

	window.location = url;
}
</script>
</head>
<body>
<form action="/">
<div id="test" style="background: -webkit-gradient(linear,left top,left bottom,from(#fff),to(#f1f1f1));
  background: -moz-linear-gradient(top,#fff,#f1f1f1);
  border-bottom: 1px solid #ccc;
  padding: 0 0 0 14px;
  height: 33px;">
  <span style='line-height: 33px;vertical-align: middle;'>
  <a href="?">All</a>

	   &nbsp;
  <a href="?Branch=master">Master</a>

	   &nbsp;
  <a href="?Branch=1.5">1.5</a>

	  &nbsp;
  <a href="?Branch=1.4">1.4</a>

	  &nbsp;

  </span>
  <div style="float:right;line-height: 33px;vertical-align: middle;padding-right: 20px;">
	<span style="margin-right: .25em;">
		<input id="shortenedcommits" type="checkbox" <?= $LINE_LIMIT_ENABLED ? "" : "checked" ?> onclick="toggleFullMessages()">
		<label for="shortenedcommits">Long commit messages</label>
	</span>
     <input name="SHA" placeholder="SHA filter" style="width:21em" value="<? echo $_GET['SHA']; ?>">

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
<div class="googlecodelink">

 <?php

// Get our parameters
$version = CheckParam('Branch');
$revision = CheckParam('Revision');
$user = CheckParam('Author');
$SHA = CheckParam('SHA');
$page = CheckParam('Page');
$limit = CheckParam('Limit');
if ($limit != null && $limit <= 50)
{
	$ITEMS_PER_PAGE = $limit;
}
if ($page == null)
{
	$page = 1;
}

// Anything less than this is google code
if ($revision != null && $revision < 7088)
{
	RedirectToGoogleCode ( $version, $revision  );
}

// Create connection
$conn = mysqli_connect($servername, $username, $password, "", 54006);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// escape page
$page = mysqli_real_escape_string ( $conn, $page );

// Start with WHERE and then move onto AND so like *WHERE* Version=1.5.0 *AND* Revision=7030
$word = " WHERE";

// build our massive where / and monstrosity
if ( $version == "master" )
{
	$subquery = $subquery . " WHERE Version='master'";
	$word = " AND";
	if ($revision == "latest")
	{
		$subquery = $subquery . $word . " Revision=(SELECT max(Revision) from mta_gitstuff.github WHERE Version='master')";
	}
	else if ( $revision != null )
	{
		$subquery = $subquery . $word . " Revision='" . mysqli_real_escape_string ( $conn, $revision ) . "'";
	}
	if ( $user != null )
	{
		$subquery = $subquery . $word .  " Author='" . mysqli_real_escape_string ( $conn, $user )  . "'";
	}
	if ( $SHA != null )
	{
		$subquery = $subquery . $word . " Revision=(SELECT Revision from mta_gitstuff.github WHERE SHA='" . mysqli_real_escape_string ( $conn, $SHA )  . "')";
	}
}
else if ( $version == null )
{
	if ( $revision != null )
	{
		if ($revision == "latest")
		{
			$subquery = $subquery . $word . " Revision=(SELECT max(Revision) from mta_gitstuff.github)";
			$word = " AND";
		}
		else
		{
			$subquery = $subquery . $word . " Revision='" . mysqli_real_escape_string ( $conn, $revision )  . "'";
			$word = " AND";
		}
		if ($user != null )
		{
			$subquery = $subquery . $word . " Author LIKE '" . mysqli_real_escape_string ( $conn, $user )  . "%'";
			$word = " AND";
		}
		if ( $SHA != null )
		{
			$subquery = $subquery . $word . " Revision=(SELECT Revision from mta_gitstuff.github WHERE SHA='" . mysqli_real_escape_string ( $conn, $SHA )  . "')";
			$word = " AND";
		}
	}
	else
	{
		if ($user != null )
		{
			$subquery = $subquery . $word . " Author LIKE '" . mysqli_real_escape_string ( $conn, $user )  . "%'";
			$word = " AND";
		}
		if ( $SHA != null )
		{
			$subquery = $subquery . $word . " Revision=(SELECT Revision from mta_gitstuff.github WHERE SHA='" . mysqli_real_escape_string ( $conn, $SHA )  . "')";
			$word = " AND";
		}
	}
}
else
{
	$subquery = $subquery . $word . " Version LIKE '" . mysqli_real_escape_string ( $conn, $version )  . "%'";
	$word = " AND";
	if ($revision == "latest")
	{
		$subquery = $subquery . $word . " Revision=(SELECT max(Revision) from mta_gitstuff.github WHERE Version LIKE '" . mysqli_real_escape_string ( $conn, $version ) . "%')";
	}
	else if ( $revision != null )
	{
		$subquery = $subquery . $word . " Revision='" . mysqli_real_escape_string ( $conn, $revision )  . "'";
	}
	if ($user != null )
	{
		$subquery = $subquery . $word . " Author LIKE '" . mysqli_real_escape_string ( $conn, $user )  . "%'";
	}
	if ( $SHA != null )
	{
		$subquery = $subquery . $word . " Revision=(SELECT Revision from mta_gitstuff.github WHERE SHA='" . mysqli_real_escape_string ( $conn, $SHA )  . "')";
	}
}

// get our number of commits so we can calcualte the number of pages
$result = $conn->query("SELECT COUNT(*) as Count FROM (SELECT Revision from mta_gitstuff.github $subquery group by Revision ) t;;");
$count = 0;

// make sure we have some rows
if ($result->num_rows > 0)
{
	// fetch our row
    $row = $result->fetch_assoc();
	// get our # of rows by dividing by the number of items per page and rounding up
	$count = round(($row["Count"] / $ITEMS_PER_PAGE) + 0.5);
}

if ($page != null && $page > $count && $count != 0)
{
	// close the database connection
	$conn->close();
	// Redirect to Google Code page
	RedirectToGoogleCode ( $version, $revision  );
}

// calculate next and previous page
 $nextpage = $page + 1;
 $previouspage = $page - 1;


 // case 1: empty GET so no parameters
 if (empty($_GET))
 {
	$NewerLink = "Page=$previouspage";
	$OlderLink = "Page=$nextpage";
 }
 // case 2: get queries but no page yet
 else if ( !isset($_GET['Page']) )
 {
	$NewerLink = $_SERVER['QUERY_STRING'] . "&Page=$previouspage";

	$OlderLink = $_SERVER['QUERY_STRING'] . "&Page=$nextpage";
 }
 // case 3: get query includes a page #
 else
 {
	$NewerLink = str_replace("Page=$page", "Page=$previouspage", $_SERVER['QUERY_STRING']);
	$OlderLink = str_replace("Page=$page", "Page=$nextpage", $_SERVER['QUERY_STRING']);
 }

 // Create a variable to store our next / previous page tag string and build it up so we can print it at the top and bottom
 $nextPreviousPage = "";

 // if we need to show the newer link
 if ($page > 1 ) {
	$nextPreviousPage = $nextPreviousPage . "<a href=\"index.php?";
	$nextPreviousPage = $nextPreviousPage . $NewerLink;
	$nextPreviousPage = $nextPreviousPage . "\"><b>&lsaquo;</b> Newer</a> ";
 }
 // show page #
 $nextPreviousPage = $nextPreviousPage . "Page " . $page . " of " . $count;

 // show older link
 $nextPreviousPage = $nextPreviousPage . " <a href=\"index.php?";
 $nextPreviousPage = $nextPreviousPage . $OlderLink;

 // show link to google code page
 if ($nextpage > $count && $count != 0)
 {
	$nextPreviousPage = $nextPreviousPage . "\">Older (Google Code) <b>&rsaquo;</b></a>";
 }
 else
 {
	$nextPreviousPage = $nextPreviousPage . "\">Older <b>&rsaquo;</b></a>";
 }
 // print next/previous page at the top
 echo $nextPreviousPage;

 ?>

 </div>
<b>Committed Changes</b>
</div>
<table class="results" id="resultstable">
  <tbody>
  <tr style="text-align:center">
    <th style="width:7ex;text-align:center"><b>Rev</b></th>
    <th style="width:3.5em;text-align:center"><b>Avatar</b></th>
    <th style="text-align:center;padding-right:10px;padding-left:10px;"><b>Author</b></th>
    <th style="text-align:center;padding-right:10px;padding-left:10px;"><b>Branch</b></th>
    <th style="width:80em;text-align:center"><b>Log Message</b></th>
    <th style="width:22em;text-align:center"><b>Date</b></th>
    <th style="width:54ex;text-align:center"><b>SHA</b></th>
  </tr>

<?php



$page = $page - 1;

$lowerLimit = $page * $ITEMS_PER_PAGE;
$MaxReturnAmount = $ITEMS_PER_PAGE;

// Create a select statement
$sql = "SELECT Revision, URL, LogMessage, DATE_FORMAT(Date, '%e %M, %Y') as Date, SHA, Author, AuthorAvatarURL, AuthorURL, Version FROM mta_gitstuff.github INNER JOIN (Select Revision as NewRevision from mta_gitstuff.github $subquery group by Revision ORDER BY Revision DESC LIMIT $lowerLimit, $MaxReturnAmount) t3 ON Revision = NewRevision";

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
			echo "<tr onclick=\"OpenURL(event, '" . $row["URL"]  . "');\">\n";
		}
		// even (impacts highlighting)
		else
		{
			echo "<tr class='even' onclick=\"OpenURL(event, '" . $row["URL"]  . "');\">\n";
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
        echo $border . "border-right: 0px solid #ccc;padding-left:2em;'>";
        $lineList = array_filter( explode( "\n", $row["LogMessage"] ) );

		$i = 0;
		// Indent wrapped lines, 5px between lines
        foreach( $lineList as $line )
        {
			$i++;
			if ( $i > $LINE_LIMIT && $LINE_LIMIT_ENABLED )
			{
				echo "...";
				break;
			}
			echo "<span style='display: block; padding-left: 0.80em; text-indent:-0.80em; margin: 5px 0;'>";
			echo preg_replace('/^\* /', '• ', $line) . "<br />";
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
	<td colspan='7' class="previoushistory">
		<strong>Previous history is available at <a style="text-decoration:underline;" href="https://code.google.com/p/mtasa-blue/source/list">our Google Code repository</a></strong>
	</td>
	</tr>
  </tbody>
 </table>
 </div>
 </div>

 <div class="bottomdiv">
		<div class="listbottom">
			<div class="googlecodelink">
				<?php
					// print our next / previous page tag at the bottom
					echo $nextPreviousPage;
				?>
			</div>
			<b>End of Committed Changes</b>
		</div>
 </div>

 </body>
 </html>
