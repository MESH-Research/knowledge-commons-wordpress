<?php

if(isset($_GET['graph']) && trim($_GET['graph']) !== '') {
    $resource = trim($_GET['graph']);
    $resourceFile = $resource . '.gexf';
} else {
    die('Requested resource is unavailable.');
}

$graphTitles = array(
    "whole-novel" => "Whole Novel <span class='number'>12 communities</span>",
    "part1" => "Part I <span class='number'>6 communities</span>",
    "part2" => "Part II <span class='number'>5 communities</span>",
    "part3" => "Part III <span class='number'>5 communities</span>",
    "part4" => "Part IV <span class='number'>7 communities</span>",
    "part5" => "Part V <span class='number'>6 communities</span>"
);

$urlPrefix = "./?graph=";
$url = $urlPrefix . $resource;

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Visualizing Les Misérables | Paris of Les Misérables | Graphs</title>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <link rel="stylesheet" type="text/css" href="styles/gexfjs.css" />
        <link rel="stylesheet" type="text/css" href="styles/jquery-ui-1.10.3.custom.min.css" />
        <link href='//fonts.googleapis.com/css?family=Alegreya+SC:400,700' rel='stylesheet' type='text/css'>
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
        <script type="text/javascript">
            if (typeof jQuery == 'undefined') {
                document.write(unescape("%3Cscript type='text/javascript' src='js/jquery-2.0.2.min.js'%3E%3C/script%3E"));
            }
        </script>
        <script type="text/javascript" src="js/vendors.min.js"></script>
        <script type="text/javascript" src="js/min/gexfjs-min.js"></script>
        <script type="text/javascript">
        setParams({
            graphFile : "<?php echo $resourceFile; ?>",
            /*
             The GEXF file to show ! -- can be overriden by adding
             a hash to the document location, e.g. index.html#celegans.gexf
             */
            showEdges : true,
            /*
             Default state of the "show edges" button
             */
            useLens : false,
            /*
             Default state of the "use lens" button
             */
            zoomLevel : -2,
            /*
             Default zoom level. At zoom = 0, the graph should fill a 800x700px zone
             */
            curvedEdges : true,
            /*
             False for curved edges, true for straight edges
             this setting can't be changed from the User Interface
             */
            edgeWidthFactor : 1,
            /*
             Change this parameter for wider or narrower edges
             this setting can't be changed from the User Interface
             */
            minEdgeWidth : 1,
            maxEdgeWidth : 6,
            textDisplayThreshold: 1,
            nodeSizeFactor : 1.75,
            /*
             Change this parameter for smaller or larger nodes
             this setting can't be changed from the User Interface
             */
            replaceUrls : true,
            /*
             Enable the replacement of Urls by Hyperlinks
             this setting can't be changed from the User Interface
             */
            showEdgeWeight : false,
            /*
             Show the weight of edges in the list
             this setting can't be changed from the User Interface
             */
            showEdgeLabel : false,
            language: false,
            /*
             Set to an ISO language code to switch the interface to that language.
             Available languages are:
             - German [de]
             - English [en]
             - French [fr]
             - Spanish [es]
             - Italian [it]
             - Finnish [fi]
             - Turkish [tr]
             - Greek [el].
             If set to false, the language will be that of the user's browser.
             */
        });
        </script>
    </head>

    <body class="graph">
        <div id="zonecentre" class="gradient">

            <canvas id="carte" width="0" height="0"></canvas>

            <ul id="ctlzoom">
                <li>
                    <a href="#" id="zoomPlusButton" title="S'approcher"> </a>
                </li>
                <li id="zoomSliderzone">
                    <div id="zoomSlider"></div>
                </li>
                <li>
                    <a href="#" id="zoomMinusButton" title="S'éloigner"> </a>
                </li>
                <li>
                    <a href="#" id="lensButton"> </a>
                </li>
                <li>
                    <a href="#" id="edgesButton"> </a>
                </li>
            </ul>
        </div>
        <div id="overviewzone" class="gradient">
            <canvas id="overview" width="0" height="0"></canvas>
        </div>
        <div id="leftcolumn">
            <div id="unfold">
                <a href="#" id="aUnfold" class="rightarrow"> </a>
            </div>
            <div id="leftcontent">
                <span class="p-message">Click on a node to see data</span>
            </div>
        </div>
        <div id="titlebar">
            <form id="recherche">
                <input id="searchinput" class="grey" autocomplete="off" />
                <input id="searchsubmit" type="submit" />
            </form>
            <h1 class="graph-title">
                <?php echo $graphTitles[strtolower($resource)]; ?>

                <a class="new-window" href="<?php echo $url; ?>" target="_blank">
                    open in new window
                </a>
            </h1>
        </div>
        <ul id="autocomplete"></ul>
    </body>
</html>