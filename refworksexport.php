<?php

/*

2006-05-11


Thanks to Brown University, and their willingness to share their code:

    http://dl.lib.brown.edu/josiah_to_refworks/josiah_to_refworks_project.zip

I used some of their code verbatim...here's a brief synopsis of my
changes in approach, and code:

 -Not using the EBADDRESS field for the export button/link display; 
  we're adding the export link to the briefcit display with this bit of html 
  and III token:

    <p>
    <a href="http://foam.lib.muohio.edu/catalog/refworks/export.php?bibnum=
    <!--{fieldspec:Fb081}-->
    " target="refworks">Export to Refworks</a>
    </p>

  briefcit.html is a customizable page in the innopac...of course the full
  record display isn't customizable (boooo-hisss), so here's my hack for
  that...
 
 -For displaying the export button/link on the full record, i'm abusing
  the permanent link field in the wwwoption file...here's what mine looks
  like:

    ICON_RECORDLINK=<div id="permlink" style="text-align: right"><a
    href="http://host.edu/export.php?url=%sa"
    target="refworks">Export to Refworks</a></div>
    BROWSE_t=245/ab/0|240/abc/0|970/t/0|/ab/0  

  %s is a special token for that field, and gives you permanent urls of
  this form:

        http://host.edu/record=b3405909

  as far as i can tell, you're pretty much stuck with the placement that
  the innopac chooses, altho with some creative CSS, you could maybe
  move it around a bit.

 -So, that's the gist of it...as with anything like this, its all based on
  hacks......this one doesn't use javascript, and i've collapsed all the
  functionality into one script. 

 -For testing purposes, you'll need to find a valid bibnum from your catalog
  before you try to import it into refworks, or else you'll get the dreaded

        "Direct Export Not Working"
  
  from RefWorks.

 */

/*
 Host of your innopac.
 */
//$opac_host = "http://wsuol2.wright.edu";
$opac_host = "http://innoserv.library.example.edu";

/*
 ezproxy style prepending...leave blank if you don't want to proxy
 the refworks connection
 */
//$proxy_base = "http://proxy.lib.muohio.edu/login?url=";
$proxy_base = "";
$refworks_base = "http://www.refworks.com/express/ExpressImport.asp";

/*
 The start and end strings of the marc records we're scraping.
 */
$start_string = "<div align=\"left\"><pre>\n";
$end_string = "</pre></div>";

/* 
 Turning on debug will print out a lot of info (like all the different
 urls, etc. that we're constructing, but it will also stop it from
 actually working
 */
$debug = 0;

/*
 Print out errors...only time this is used is when the script is called
 without correct parameters.
 */
$reportErrors = 1;

/*
 How refworks wants the marcformat request indicated.
*/
$marc_paramameter = "vendor=Marc%20Format";


/*
 You probably don't need to modify anything past that....
 */

debugMsg($_GET);

$local_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

if (isset($_GET['bibnum'])) {

    $bibnum = $_GET['bibnum'];
    
    if (substr($bibnum, 0, 1) != 'b') {
        $bibnum = "b" . $bibnum;
    }

}

function makeRefworksURL ($bibnum)
{

    global $proxy_base, $refworks_base, $marc_paramameter, $local_url;

    $local_full_url = $local_url . "?bibnum=" . $bibnum . "&marc=1";

    $encoded_url = urlencode($local_full_url);

    $refworks_url = $proxy_base . $refworks_base . "?encoding=65001&" . $marc_paramameter . "&url=" . $encoded_url; 

    return($refworks_url);

}

if (isset($_GET['url'])) {

    $parts = split("=", $_GET['url']);

    debugMsg($_GET['url']);

    $bibnum = $parts[1];

}

if ($bibnum && !isset($_GET['marc'])) {

    $url = makeRefworksURL($bibnum);
    debugMsg($url);
    if (!$debug) {
        header("Location: $url");
    }

} elseif ($bibnum && isset($_GET['marc'])) {

    $opac_url = $opac_host . "/search/." . $bibnum . "/." . $bibnum . "/1%2C1%2C1%2CB/marc~" . $bibnum;  

    debugMsg($opac_url);

    /*
     Didn't really think about it, but file_get_contents is relatively new,
     and the implode/file option is probably more portable.
     */
    //$opac_response = file_get_contents($opac_url);
    $opac_response = implode('', file($opac_url));

    $start_position = strpos($opac_response, $start_string);
    debugMsg($start_position);
    $start_position += strlen($start_string);
    debugMsg($start_position);
    
    $end_position = strpos($opac_response, $end_string);
    debugMsg($end_position);

    $characters_to_grab = $end_position - $start_position;
    debugMsg($characters_to_grab);

    $marc_data = substr($opac_response, $start_position, $characters_to_grab);
    debugMsg($marc_data);

    // I don't see a need for the <p> tags, and the </p> was getting included
    // in RefWork's parsing of some records -- gsf 2011/03/30
	//print "<p>" . $marc_data . "</p>";
    print $marc_data;
    
} else {
    errorMsg("<dl>
        <dt>bad input...i can only understand the following (these are just examples) :</dt>
            <dd>http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?bibnum=1938068</dd>
            <dd>http://" . $_SERVER['HTTP_HOST'] .  $_SERVER['PHP_SELF'] . "?bibnum=1938068&marc=1</dd>
            <dd>http://" . $_SERVER['HTTP_HOST'] .  $_SERVER['PHP_SELF'] . "?url=" . $opac_host . "/record=b1938068a</dd>
        <dt>you'll first want to find a valid bibnum from your catalog to do any testing, or you'll get the dreaded:</dt>
            <dd>\"Direct Export Not Working\"</dd>
    </dl>
    ");
}


function debugMsg($message)
{
    global $debug;

    if ($message && $debug) {
        print "<pre class=\"debug\">";

        if ( gettype($message) == "array" ) {
            print_r($message);
         } else {
            print @htmlentities($message);
        }

        print "</pre>\n";
    }

}

function errorMsg($message)
{

    global $reportErrors;

    if ($message && $reportErrors) {
        print "<pre class=\"error\">ERROR: ";

        if ( gettype($message) == "string" ){
            print $message;
        } elseif ( gettype($message) == "array" ) {
            print_r($message);
        }

        print "</pre>\n";
    }

}

?>
