<?php
require 'config.php';

const DEFAULT_ITEMS_PER_PAGE = 10;
const DEFAULT_LIMIT_MESSAGE_ENABLED = true;
const MESSAGE_LINE_LIMIT = 3;

function getParameterFromQuery($param)
{
    return $_GET[$param] ?? null;
}

function redirectToGoogleCode($version, $revision)
{
    $redirectUrl = 'https://code.google.com/p/mtasa-blue/source/list?num=25';

    if ($version != null) {
        $redirectUrl = 'https://code.google.com/p/mtasa-blue/source/list?path=/';

        if ($version != 'trunk' && $version != 'master') {
            $redirectUrl .= "branches/$version/";
        }

        $redirectUrl .= 'trunk/';
    }

    if ($revision != null) {
        $redirectUrl .= "&start=$revision";
    } else {
        $redirectUrl .= '&start=7088';
    }

    header('Location: ' . $redirectUrl);
}

$dbConnection = mysqli_connect($servername, $username, $password, '', 3306);

if (!$dbConnection) {
    die('Connection failed: ' . mysqli_connect_error());
}

$json = $_GET['json'] ?? null;
if ($json == 'true') {
	header('Content-Type: application/json');
	$result = $dbConnection->query('SELECT * FROM mta_gitstuff.github ORDER BY revision DESC, date DESC');
	$commits = $result->fetch_all(MYSQLI_ASSOC);

	echo json_encode($commits);
    return;
}

$itemsPerPage = DEFAULT_ITEMS_PER_PAGE;
$lineLimitEnabled = isset($_GET['full']) ? $_GET['full'] == 'false' : DEFAULT_LIMIT_MESSAGE_ENABLED;

$version = getParameterFromQuery('Branch');
$revision = getParameterFromQuery('Revision');
$user = getParameterFromQuery('Author');
$SHA = getParameterFromQuery('SHA');
$currentPage = (int) getParameterFromQuery('Page');
$limit = getParameterFromQuery('Limit');

if (is_numeric($limit) && $limit <= 500) {
	$itemsPerPage = $limit;
}
if ($currentPage < 1) {
	$currentPage = 1;
}

// Anything less than this is google code
if ($revision != null && $revision < 7088) {
	redirectToGoogleCode ( $version, $revision  );
}

// Start with WHERE and then move onto AND so like *WHERE* Version=1.5.0 *AND* Revision=7030
$word = ' WHERE';
$subquery = '';

if ($version) {
    $subquery .= sprintf('%s Version LIKE \'%%%s%%\'', $word, mysqli_real_escape_string ( $dbConnection, $version ));
    $word = ' AND';
}

if ($revision) {
    $subquery .= sprintf('%s Revision = \'%s\'', $word, mysqli_real_escape_string($dbConnection, $revision));
    $word = ' AND';
}

if ($user) {
    $subquery .= sprintf('%s Author LIKE \'%s%%\'', $word, mysqli_real_escape_string ( $dbConnection, $user ));
    $word = ' AND';
}

if ($SHA) {
    $subquery .= sprintf('%s SHA = \'%s\'', $word, mysqli_real_escape_string ( $dbConnection, $SHA ));
    $word = ' AND';
}

// get our number of commits so we can calculate the number of pages
$result = $dbConnection
    ->query('
        SELECT COUNT(*) 
        FROM mta_gitstuff.github
        $subquery
        GROUP BY Revision ');

$totalRevisionsCount = $result->fetch_row()[0] ?? 0;
$totalPagesCount = round(($totalRevisionsCount / $itemsPerPage) + 0.5);

if ($currentPage != null && $currentPage > $totalPagesCount && $totalPagesCount != 0) {
    redirectToGoogleCode ( $version, $revision  );
}

$previousPage = $currentPage - 1;
$nextPage = $currentPage + 1;

$baseLink = 'index.php?';
$previousPageLink = $baseLink . http_build_query(['Page' => $previousPage] + $_GET);
$nextPageLink = $baseLink . http_build_query(['Page' => $nextPage] + $_GET);

$previousPageComponent = '<span class="previous_page disabled">Previous</span>';
if ($currentPage > 1) {
    $previousPageComponent = '<a class="previous_page" rel="previous" href="' . $previousPageLink . '" aria-label="Previous Page">Previous</a> ';
}

$nextPageLabel = 'Next';
if ($nextPage > $totalPagesCount && $totalPagesCount != 0) {
    $nextPageLabel = 'Next (Google Code)';
}

$lowerLimit = ($currentPage - 1) * $itemsPerPage;

$sql = "SELECT 
            Revision, 
            URL, 
            LogMessage, 
            DATE_FORMAT(Date, '%e %M, %Y') as Date, 
            SHA, 
            Author, 
            AuthorAvatarURL, 
            AuthorURL, 
            Version 
        FROM mta_gitstuff.github 
        $subquery
        ORDER BY Revision DESC LIMIT $lowerLimit, $itemsPerPage";

$result = $dbConnection->query($sql);
$rows = $result->fetch_all(MYSQLI_ASSOC);

$lastRevision = null;

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>MTASA Build Information</title>
        <link rel="stylesheet" href="css.css" />
        <link href="https://unpkg.com/@primer/css@15.0.0/dist/primer.css" rel="stylesheet" />
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
                    <input id="shortenedcommits" type="checkbox" <?= $lineLimitEnabled ? "" : "checked" ?> onclick="toggleFullMessages()">
                    <label for="shortenedcommits">Long commit messages</label>

                    <input class="form-control" name="SHA" placeholder="SHA filter" style="width:21em" value="<?= htmlspecialchars($_GET['SHA'] ?? null) ?>" />
                    <input class="form-control" name="Author" placeholder="Author filter" value="<?= htmlspecialchars($_GET['Author'] ?? null) ?>" />
                    <input class="form-control" name="Branch" placeholder="Branch filter" value="<?= htmlspecialchars($_GET['Branch'] ?? null) ?>" />
                    <input class="form-control" name="Revision" placeholder="Revision filter" value="<?= htmlspecialchars($_GET['Revision'] ?? null) ?>">

                    <input class="btn" type="button" onclick="document.location='index.php';" value="Reset" />
                    <input class="btn" type="submit" value="Submit">
                </div>
            </nav>
        </form>
        <div id="maincol">
            <div id="colcontrol">
                <div class="list">
                    <div class="googlecodelink">
                        <nav class="paginate-container" aria-label="Pagination">
                            <div class="pagination">
                                <?= $previousPageComponent ?>
                                <a class="next_page" rel="next" href="<?= $nextPageLink ?>" aria-label="Next Page"><?= $nextPageLabel ?></a>
                                <span class='disabled'>Page <?= $currentPage ?> of <?= $totalPagesCount ?></span>
                            </div>
                        </nav>
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
<?php if (count($rows) == 0) { ?>
                        <tr>
                            <td style='text-align:center;' colspan='7'>
                                <strong>0 results</strong>
                            </td>
                        </tr>
<?php
} else {
    foreach($rows as $index => $row) {
        $currentRevision = $row['Revision'];
        $currentAuthorAvatarUrl = $row['AuthorAvatarURL'];
        $currentAuthor = $row['Author'];
        $currentVersion = $row['Version'];
        $currentLogMessage = $row['LogMessage'];
        $currentCommitUrl = $row['URL'];
        $currentCommitHash = $row['SHA'];
        $currentCommitDate = $row['Date'];


        $sameRevision = $lastRevision === $currentRevision;
        $lastRevision = $currentRevision;
        $revisionCellClass = $sameRevision ? 'same-revision-cell' : 'revision-cell';
        $revisionCellBody = !$sameRevision ?
            sprintf('<a href="?Revision=%1$s&amp;Branch=%2$s">r%1$s</a>', $currentRevision, $version)
            : '     ';

        $splitAuthor = explode ( '@', $currentAuthor );
        $authorName = htmlspecialchars($splitAuthor[0]);

        // master branch is just null in the database
        $modifiedVersion = $currentVersion ?? 'master';

        // Remove empty lines from log message
        $lineList = array_filter(explode( "\n", $currentLogMessage ));

?>
                        <tr>
                            <td class="<?= $revisionCellClass ?>"><?= $revisionCellBody ?></td>
                            <td class='author-cell'>
                                <div style='display:flex; align-items:center;'>
                                    <img class='m-2 author-image' src='<?= $currentAuthorAvatarUrl ?>' alt='Avatar' />
                                    <?= $authorName ?>
                                </div>
                            </td>
                            <td class='branch-cell'>
                                <a href='?Branch=<?= $modifiedVersion ?>&amp;Revision=<?= $revision ?>'><?= $modifiedVersion ?></a>
                            </td>
                            <td class='message-cell'>
<?php
		// Indent wrapped lines, 5px between lines
        foreach($lineList as $i => $line) {
			if ( $i > MESSAGE_LINE_LIMIT && $lineLimitEnabled ) {
				echo '...';

				break;
			}

            // Replace "-" and "*" for bullets
            $line = htmlspecialchars(preg_replace('/^-|\* /', '• ', $line));

			$messageFormat = $i > 0 ?
                '<small>%2$s</small>'
                : '<a href="%s">%s</a>';
?>
                            <span class='commit-header' style='display: block; padding-left: 0.80em; text-indent:-0.80em; margin: 5px 0;'>
                                <?= sprintf($messageFormat, $currentCommitUrl, $line) ?>
                                <br />
                            </span>
<?php
        }
?>
                            </td>
                            <td class='date-cell'><?= $currentCommitDate ?></td>
                            <td class='hash-cell'>
                                <div style='width: 100%; height: 100%' class='commit-sha'>
                                    <a href='<?= $currentCommitUrl ?>'><?= $currentCommitHash ?></a>
                                </div>
                            </td>
                        </tr>
<?php
	}
}
?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bottomdiv">
            <div class="listbottom">
                <div class="googlecodelink">
                    <nav class="paginate-container" aria-label="Pagination">
                        <div class="pagination">
                            <?= $previousPageComponent ?>
                            <a class="next_page" rel="next" href="<?= $nextPageLink ?>" aria-label="Next Page"><?= $nextPageLabel ?></a>
                            <span class='disabled'>Page <?= $currentPage ?> of <?= $totalPagesCount ?></span>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
     </body>
 </html>
