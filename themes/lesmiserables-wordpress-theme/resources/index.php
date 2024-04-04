<?php

if(isset($_GET['resource']) && trim($_GET['resource']) !== '') {
    $resource = trim($_GET['resource']);
} else {
    die('Requested resource is unavailable.');
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visualizing Les Misérables | Paris of Les Misérables | Sites</title>
    <link href='http://fonts.googleapis.com/css?family=Alegreya+SC:400' rel='stylesheet' type='text/css'>
    <script src="../resources/openseadragon.min.js"></script>
</head>
<style>
    body { background: #333; }

    #resource {
        height: 35em;
        background: #373737;
    }

    div.openseadragon-canvas + div div div div:last-child { display: none !important; }
</style>
<body class="<?php echo $resource; ?> map">
    <div id="resource"></div>

    <script>
        var viewer = OpenSeadragon({
            id: 'resource',
            prefixUrl: "../resources/images/",
            tileSources: "../img/library/<?php echo $resource; ?>.dzi",
            maxZoomLevel: 3,
            minZoomImageRatio: 0.8,
            maxZoomPixelRatio: 2,
        });
    </script>

</body>
</html>