<?php
// make sure browsers see this page as utf-8 encoded HTML
ini_set('memory_limit',-1);
include 'SpellCorrector.php';
include 'simple_html_dom.php';
header('Content-Type: text/html; charset=utf-8');
define('ROOT_PATH',$_SERVER['DOCUMENT_ROOT']);
$inputfile = file("URLtoHTML_nypost.csv");
$path ="/Users/krishnanparamaguru/Downloads/solr-7.5.0/nypost/";
foreach($inputfile as $line)
{
$file = str_getcsv($line);
$URLMap[$file[0]] = trim($file[1]);
}
fclose($inputfile);
$limit =10;
$query =isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results =false;
$div = false;
$str = "";
$correct_Query = "";
$output = "";
if($query)
{
// The Apache Solr Client library should be on the include path
// which is usually most easily accomplished by placing in the
// same directory as this script ( . or current directory is a default
// php include path entry in the php.ini)

require_once('solr-php-client-master/apache/solr/Service.php');
// create a new solrservice instance -host, port, and corename
// path (all defaults in this example)
$solr =new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
// if magic quotes is enabled then stripslashes will be needed
if(get_magic_quotes_gpc() ==1)
{
$query = stripslashes($query);
}
// in production code you'll always want to use a try /catch for any
// possible exceptions emitted by searching (i.e. connection
// problems or a query parsing error)
try
{
if (!isset($_GET['algorithm'])){

$_GET['algorithm'] = "lucene";
$choice = "lucene";
}
if ($_GET['algorithm']=="lucene"){
$choice = "lucene";
}
else{
$choice = "pagerank";
$params = array(
'sort' => 'pageRankFile.txt desc');
}
$word = explode(" ",$query);
for($i=0;$i<sizeOf($word);$i++){
$intermediate = SpellCorrector::correct($word[$i]) ;
if($str!="")$str = $str."+".trim($intermediate);
else{
$str = trim($intermediate);}
$correct_Query = $correct_Query." ".trim($intermediate);
}
$correct_Query = str_replace("+"," ",$str);
$div=false;
if(strtolower($query)==strtolower($correct_Query)){
if($choice == "lucene")
$results = $solr->search($query, 0, $limit);
else
$results = $solr->search($query, 0, $limit,$params);
}
else {
$div =true;
if($choice == "lucene")
$results = $solr->search($query, 0, $limit);
else
$results = $solr->search($query, 0, $limit,$params);
$link = "http://localhost/index.php?q=$str&sort=$choice";
$output = "Did you mean: <a href='$link'>$correct_Query</a>";
}
}
catch(Exception $e)
{
// in production you'd probably log or email this error to an admin
// and then show a special message to the user but for this example
// we're going to show the full exception
die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre>
</body></html>");
}
}
?>
<html>

<head>
<title>PHP Solr Client Example</title>
<script src="http://code.jquery.com/jquery-1.10.2.js"></script>

<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-
ui.css">

<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
</head>
<body>
<form accept-charset="utf-8" method="get">
<label for="q">Search:</label>
<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query,
ENT_QUOTES, 'utf-8'); ?>"/>
<input type ="radio" name = "algorithm" <?php if($algorithm != "pagerank"){ echo
'checked="checked"';} ?> value = "lucene" /> Solr Lucene
<input type = "radio" name = "algorithm" <?php if($algorithm == "pagerank"){ echo
'checked="checked"'; } ?> value = "pagerank" /> Google PageRank <br/> <br/>
<input type = "submit"/>
</form>
<script>
$(function() {
var URL_PREFIX = "http://localhost:8983/solr/myexample/suggest?q=";
var URL_SUFFIX = "&wt=json&indent=true";
var arr = [];
$("#q").autocomplete({
source : function(request, response) {
var last_word= "";
var str1="";
var character_count = $("#q").val().toLowerCase().length - ($("#q").val().toLowerCase().match(/ /g) || []).length;
var space = $("#q").val().toLowerCase().lastIndexOf(' ');
if($("#q").val().toLowerCase().length-1>space && space!=-1){
last_word=$("#q").val().toLowerCase().substr(space+1);
str1 = $("#q").val().toLowerCase().substr(0,space);
}
else{
last_word=$("#q").val().toLowerCase().substr(0);
}
var URL = URL_PREFIX + last_word+ URL_SUFFIX;
$.ajax({
url : URL,
success : function(data) {
console.log(data);
var jsonData = JSON.parse(JSON.stringify(data.suggest.suggest));
var result = jsonData[last_word].suggestions;
var j=0;
var stack =[];
for(var i=0;i<5 && j<result.length;i++,j++){
if(result[j].term==last_word)
{
i--;
continue;
}
for(var k=0;k<i && i>0;k++){
if(arr[k].indexOf(result[j].term) >=0){
i--;
continue;
}
}
if(result[j].term.indexOf('.')>=0 || result[j].term.indexOf('_')>=0)
{
i--;
continue;
}
var s =(result[j].term);
if(stack.length == 5)
break;
if(stack.indexOf(s) == -1)
{
stack.push(s);
if(str1==""){
arr[i]=s;
}
else
{
arr[i] = str1+" ";
arr[i]+=s;
}
}
}
console.log(arr);
response(arr);
},
dataType : 'jsonp',
jsonp : 'json.wrf'
});
},
minLength : 1
})
});
</script>


<?php
// display results
if($div){
  echo $output;
}
if($results)
{
$total =(int) $results->response->numFound;
$start =min(1, $total);
$end =min($limit, $total);
echo "<div>Results {$start} - {$end} of {$total}:</div>";
}
?>

<ol>
<?php
// iterate result documents
foreach ($results->response->docs as $doc)
{
// iterate document fields / values
echo "<li>";
$doc_title = "";
$doc_url = "";
$id = "";
$doc_descp = "";
foreach ($doc as $field => $value)
{
if($field == "description"){
$doc_descp = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
}
if($field == "title"){
$doc_title = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
}
if($field == "id"){
$file_loc = $value;
$id = htmlspecialchars($value, ENT_NOQUOTES, 'utf-8');
$id = str_replace($path, "", $id);
}
}
if($id != ""){
$doc_url = $URLMap[$id];
}
echo "<a  target= '_blank'  href='{$doc_url}'><b>".$doc_title."</b></a></br>";
echo "<a  target= '_blank' href='{$doc_url}'>".$doc_url."</a></td></br>";
echo $id;
echo "</br>";

$html = $doc_descp.".".file_get_contents($file_loc).".".$doc_title;
$sentences = explode(".",$html);
$words = explode(" ", $query);
$snippet="";
$text="/";
foreach($words as $w){
    $text=$text."(?=.*?\b".$w."\b)";
}
$text=$text."^.*$/i";

foreach($sentences as $sentence){
        $sentence=strip_tags($sentence);
       if (preg_match($text, $sentence)>0){

            if (preg_match("(&gt|&lt|\/|{|}|[|]|\|\%|>|<|:)",$sentence)>0){
                continue;
            }
           else{
             $snippet = $snippet.$sentence;
             if(strlen($snippet)>156) break;
           }
      }
}
if(strlen($snippet)<5){
foreach($sentences as $sentence){
            $sentence=strip_tags($sentence);
      foreach($words as $word){
            if (preg_match($word, $sentence)>0){
               if (preg_match("(&gt|&lt|\/|{|}|[|]|\|\%|>|<|:)",$sentence)>0){
                 continue;
               }
                else{
                  $snippet = $snippet.$sentence;
                  if(strlen($snippet)>156)break;
                }
              }
         }
    }
}
if (strpos($snippet, 'keywords') !== false)
{
    if(empty($snippet)){
        echo "No Snippet Found";
    }
    else{
         echo "...".$doc_descp."...";
    }
}
else
{
    if(empty($snippet)){
        echo "No Snippet Found";
    }
    else{
         echo "...".$snippet."...";
    }
}
echo "</li></br>";
}
?>
</ol>

</div>
<script>

</body> </html>
