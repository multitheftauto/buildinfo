<?php
include 'config.php';

// Create connection
$conn = mysqli_connect($servername, $username, $password, "", 54006);

if ($_GET["json"] == "true" ) {
	header('Content-Type: application/json');
	$result = $conn->query("select * from mta_gitstuff.github order by revision desc, date desc");
	$myArray = array();

	while($row = $result->fetch_array(MYSQLI_ASSOC)) {
		$myArray[] = $row;
	}
	echo json_encode($myArray);

	$result->close();
	$conn->close();
	idie();
}

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


// Get our parameters
$version = CheckParam('Branch');
$revision = CheckParam('Revision');
$user = CheckParam('Author');
$SHA = CheckParam('SHA');
$page = CheckParam('Page');
$limit = CheckParam('Limit');
if (is_numeric($limit) && $limit <= 500)
{
	$ITEMS_PER_PAGE = $limit;
}
if (!is_numeric($page) || $page < 1)
{
	$page = 1;
}

// Anything less than this is google code
if ($revision != null && $revision < 7088)
{
	RedirectToGoogleCode ( $version, $revision  );
}
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>MTASA Build Information</title>
<!-- <link rel="stylesheet" type="text/css" href="css.css?1" /> -->
<link href="https://unpkg.com/@primer/css/dist/primer.css" rel="stylesheet" />
<script type="text/javascript">
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

function onload() {
	// Remove empty fields from search query
	const myForm = document.getElementById('searchform');
	myForm.addEventListener('submit', function () {
		const allInputs = myForm.getElementsByTagName('input');

		for (let i = 0; i < allInputs.length; i++) {
			const input = allInputs[i];
			if (input.name && !input.value) {
				input.name = '';
			}
		}
	});
}

window.addEventListener('load', onload);
</script>

<style>
.commit-header a {
	text-decoration: none;
	color: #444d56;
	font-weight: 600;
}

.commit-header a:hover {
	text-decoration: underline
}

.commit-sha a {
	text-decoration: none;
	color: #0366d6;
	/* font-weight: 600; */
	width: 100%;
	height: 100%;
}

.commit-sha a:hover {
	text-decoration: underline;
}
</style>

</head>
<body>
	<form id="searchform" action="">

		<nav class="UnderlineNav px-3" aria-label="navigation bar">
			<div class="UnderlineNav-body">
				<a href="?" class="UnderlineNav-item <?= $version == null ? "selected" : ""?>">All</a>
				<a href="?Branch=master" class="UnderlineNav-item <?= $version == 'master' ? "selected" : ""?>">Master</a>
				<a href="?Branch=1.5" class="UnderlineNav-item <?= $version == '1.5' ? "selected" : ""?>">1.5</a>
				<a href="?Branch=1.4" class="UnderlineNav-item <?= $version == '1.4' ? "selected" : ""?>">1.4</a>
			</div>

			<div class="UnderlineNav-actions">
				<input id="shortenedcommits" type="checkbox" <?= $LINE_LIMIT_ENABLED ? "" : "checked" ?> onclick="toggleFullMessages()">
				<label for="shortenedcommits">Long commit messages</label>

				<input class="form-control" name="SHA" placeholder="SHA filter" style="width:21em" value="<? echo htmlspecialchars($_GET['SHA']); ?>" />
				<input class="form-control" name="Author" placeholder="Author filter" value="<? echo htmlspecialchars($_GET['Author']); ?>" />
				<input class="form-control" name="Branch" placeholder="Branch filter" value="<? echo htmlspecialchars($_GET['Branch']); ?>" />
				<input class="form-control" name="Revision" placeholder="Revision filter" value="<? echo htmlspecialchars($_GET['Revision']); ?>">

				<input class="btn" type="button" onclick="document.location='index.php';" value="Reset" />
				<input class="btn" type="submit" value="Submit">
			</div>
		</nav>

	</form>

 <?php




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
	$NewerLink = str_replace("Page=" . $_GET['Page'], "Page=$previouspage", $_SERVER['QUERY_STRING']);
	$OlderLink = str_replace("Page=" . $_GET['Page'], "Page=$nextpage", $_SERVER['QUERY_STRING']);
 }

 $NewerLink = "index.php?" . $NewerLink;
 $OlderLink = "index.php?" . $OlderLink;

 // Create a variable to store our next / previous page tag string and build it up so we can print it at the top and bottom
 $nextPreviousPage = '<nav class="paginate-container" aria-label="Pagination"> <div class="pagination"> ';

 // if we need to show the "Previous" (newer) link
 if ($page > 1 ) {
	$nextPreviousPage = $nextPreviousPage . '<a class="previous_page" rel="previous" href="' . $NewerLink . '" aria-label="Previous Page">Previous</a> ';
 } else {
	$nextPreviousPage = $nextPreviousPage . '<span class="previous_page disabled">Previous</span> ';
 }


 // show older link
 $nextPreviousPage = $nextPreviousPage . ' <a class="next_page" rel="next" href="' . $OlderLink . '" aria-label="Next Page">';

 // show link to google code page
 if ($nextpage > $count && $count != 0)
 {
	$nextPreviousPage = $nextPreviousPage . "Next (Google Code)";
 }
 else
 {
	$nextPreviousPage = $nextPreviousPage . "Next";
 }

 $nextPreviousPage = $nextPreviousPage . "</a>";

 // show page #
 $nextPreviousPage = $nextPreviousPage . "<span class='disabled'>Page " . $page . " of " . $count . "</span></div></nav>";

 ?>


<div id="maincol">
	<div id="colcontrol">
		<div class="list">
			<div class="googlecodelink">
				<?= $nextPreviousPage ?>
			</div>
		</div>

		<table class="results" id="resultstable">
			<tbody>
				<tr style="text-align:center">
					<th style="width:7ex;text-align:center"><b>Rev</b></th>
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

$rev = 0;

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
			echo "<tr>\n";
		}
		// even (impacts highlighting)
		else
		{
			echo "<tr class='even'>\n";
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
		echo $border . "border-right: 0px solid #ccc;'><div style='display:flex; align-items:center;'><img class='m-2' style='border-radius: 2px; display:block;' src='" . $row["AuthorAvatarURL"] . "' height='20' width='20' alt='Avatar' />\n";
		$splitAuthor = explode ( '@', $row["Author"] );
		echo htmlspecialchars($splitAuthor[0]) . "</div></td>\n";

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
			echo "<span class='commit-header' style='display: block; padding-left: 0.80em; text-indent:-0.80em; margin: 5px 0;'>";
			$line = htmlspecialchars(preg_replace('/^-|\* /', '• ', $line));
			if ($i > 1) {
				$line = "<small>" . $line . "</small>";
			} else {
				$line = "<a href='" . $row["URL"] . "'>" . $line . "</a>";
			}
			echo $line . "<br />";
			echo "</span>";
        }
        echo "</td>\n";

		// output our Date and set the max column width
		echo $border . "border-right: 0px solid #ccc;'>" . $row["Date"] . "</td>";

		// output our SHA and set the max column width
		echo $border . "border-right: 0px solid #ccc;'><div style='width: 100%; height: 100%' class='commit-sha'><a href='" . $row["URL"] . "'>" . $row["SHA"] . "</a></div></td>\n";

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


  </tbody>
 </table>
 </div>
 </div>

<div class="bottomdiv">
	<div class="listbottom">
		<div class="googlecodelink">
			<!-- Pagination -->
			<?= $nextPreviousPage; ?>
		</div>
	</div>
</div>

 </body>
 </html>
