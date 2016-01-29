# osm

> OpenStreetMap API Integration

# Basic Usage

Find nodes in Germany bounding box:
NE 55.05814, 15.04205
SW 47.27021, 5.86624

```php
$south = 47.27021;
$west = 5.86624;
$north = 55.05814;
$east = 15.04205;

// create bounding box
$bBox = new \KageNoNeko\OSM\BoundingBox($south, $west, $north, $east);

// slice to lesser boxes to reduce memory usage
$slices = $bBox->slices(4, 4);

// create connection to Overpass API source
$osm = new \KageNoNeko\OSM\OverpassConnection(['interpreter' => 'http://overpass-api.de/api/interpreter']);

// build query, like we usually do with Database queries
$q = $osm->element('node')
    ->whereTag('aeroway', 'aerodrome')
    ->verbosity('meta')
    ->asJson();

$nodes = [];
foreach ($slices as $slice) {
    //add constraint by boundary box of each slice and get result
    $response = $q->whereInBBox($slice)->get();
    $data = json_decode($response->getBody()->getContents());
    $nodes = array_merge($nodes, $data->elements);
}
```