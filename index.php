<?php
// make sure browsers see this page as utf-8 encoded HTML

header('Content-Type: text/html; charset=utf-8');

$limit =10;
$query =isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results =false;

if($query)
{
// The Apache Solr Client library should be on the include path
// which is usually most easily accomplished by placing in the
// same directory as this script ( . or current directory is a default
// php include path entry in the php.ini)

require_once('solr-php-client-master/Apache/Solr/Service.php');

// create a new solrservice instance -host, port, and corename
// path (all defaults in this example)

$solr =new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');

// if magic quotes is enabled then stripslashes will be needed

if(get_magic_quotes_gpc() ==1)
{
$query = stripslashes($query);
}
// in production code you'll always want to use a try /catch for any
// possible exceptions emitted  by searching (i.e. connection
// problems or a query parsing error)
try
{
 if (!isset($_GET['algorithm']))
	$_GET['algorithm'] = "lucene";
 if ($_GET['algorithm']=="lucene"){
	$solrParams = array(
	'fl' => 'title, og_url, og_description,id'
	);
 	$results =$solr->search($query, 0, $limit, $solrParams);
 }
 else{
 	$pagerankParams = array(
		'fl' => 'title, og_url, og_description,id',
		'sort' => 'pageRankFile.txt  desc');
 	$results =$solr->search($query, 0, $limit, $pagerankParams);
}
}


catch(Exception $e)
{
// in production you'd probably log or email this error to an admin
// and then show a special message to the user but for this example
// we're going to show the full exception
die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
}
}
?>
<html>
<head>
<title>PHP Solr Client Example</title>
</head>
<body>
<form accept-charset="utf-8" method="get">
<label for="q">Search:</label>
<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
<input type ="radio" name = "algorithm" <?php if($algorithm != "pagerank"){ echo 'checked="checked"';} ?> value = "lucene" /> Solr Lucene
<input type = "radio" name = "algorithm" <?php if($algorithm == "pagerank"){ echo 'checked="checked"'; } ?> value = "pagerank" /> Google PageRank   <br/> <br/>
<input type = "submit"/>
</form>

<?php
// display results
if($results)
{
$total =(int) $results->response->numFound;
$start =min(1, $total);
$end =min($limit, $total);

$inputfile = file("/home/karnat1802/Downloads/URLtoHTML_nypost.csv");

foreach($inputfile as $line)
{
    $file = str_getcsv($line);
    $URLMap[$file[0]] = trim($file[1]);
}

?>
<div>Results <?php echo $start; ?>-<?php echo $end;?> of <?php echo $total; ?>:</div>
<ol>
<?php 
   // iterate results documents
   foreach($results->response->docs as $doc)
   {
    // $id = str_replace("/home/karnat1802/Downloads/solr-7.5.0/nypost/","",$doc->id);
       $id =  $doc->id; 
       $key = str_replace("/home/karnat1802/Downloads/solr-7.5.0/nypost/","",$id);
      
       $url = $URLMap[$key];
?>


    <li> <a href = "<?php echo $url ?>"><?php
    if (isset($doc->title))
    {
      echo htmlspecialchars($doc->title, ENT_NOQUOTES, 'utf-8');
    }
    else 
    {
      echo "NA";
    } ?> </a><br>
    <a href = "<?php echo $url ?>"><?php echo $url ?></a><br>
    <?php echo $id ?> <br>
    <?php 
    if  (isset($doc->og_description)){
	echo htmlspecialchars($doc->og_description, ENT_NOQUOTES, 'utf-8');
	} 
    else{
	echo "NA";
    }
    ?>
    </li>
    <br>

<?php
   }
?>

	</ol>
<?php
}
?>
 </body>
</html>
	

