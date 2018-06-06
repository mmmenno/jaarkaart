<?php

$jaar = 1943;

if(isset($_GET['jaar'])){
	$jaar = $_GET['jaar'];
}

$sparqlquery = '
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX hg: <http://rdf.histograph.io/>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
SELECT DISTINCT ?building ?wkt (SAMPLE(?img) AS ?img) WHERE {
  {
    ?building a hg:Building .
    OPTIONAL{
      ?cho dct:spatial ?building .
      ?cho foaf:depiction ?img .
    }
    ?building sem:hasEarliestBeginTimeStamp ?beginmin .
    ?building sem:hasLatestBeginTimeStamp ?beginmax .
    ?building sem:hasEarliestEndTimeStamp ?endmin .
    ?building sem:hasLatestEndTimeStamp ?endmax .
    ?building geo:hasGeometry/geo:asWKT ?wkt .
    FILTER (year(xsd:dateTime(?beginmin)) < ' . $jaar . ')
    FILTER (year(xsd:dateTime(?endmax)) > ' . $jaar . ')
  }UNION{
    ?building a hg:Building .
    OPTIONAL{
      ?cho dct:spatial ?building .
      ?cho foaf:depiction ?img .
    }
    ?building sem:hasEarliestBeginTimeStamp ?beginmin .
    ?building sem:hasLatestBeginTimeStamp ?beginmax .
    ?building geo:hasGeometry/geo:asWKT ?wkt .
    FILTER NOT EXISTS {?building sem:hasEarliestEndTimeStamp ?endmin}
    FILTER NOT EXISTS {?building sem:hasLatestEndTimeStamp ?endmax}
    FILTER (year(xsd:dateTime(?beginmin)) < ' . $jaar . ')
  }
} 
GROUP BY ?building ?wkt
';


$url = "https://api.data.adamlink.nl/datasets/AdamNet/all/services/endpoint/sparql?default-graph-uri=&query=" . urlencode($sparqlquery) . "&format=application%2Fsparql-results%2Bjson&timeout=120000&debug=on";

$querylink = "https://data.adamlink.nl/AdamNet/all/services/endpoint#query=" . urlencode($sparqlquery) . "&contentTypeConstruct=text%2Fturtle&contentTypeSelect=application%2Fsparql-results%2Bjson&endpoint=https%3A%2F%2Fdata.adamlink.nl%2F_api%2Fdatasets%2Fmenno%2Falles%2Fservices%2Falles%2Fsparql&requestMethod=POST&tabTitle=Query&headers=%7B%7D&outputFormat=table";



$json = file_get_contents($url);

$data = json_decode($json,true);

$fc = array("type"=>"FeatureCollection","query" => $querylink, "features"=>array());


foreach ($data['results']['bindings'] as $row) {
	$line = array("type"=>"Feature");
	if($row['year']['value']==null){
		$year = "????";
	}else{
		$year = $row['year']['value'];
	}
	$props = array(
		"id" => $row['building']['value'],
		"name" => $row['label']['value']
	);
	$line['geometry'] = wkt2geojson($row['wkt']['value']);
	$line['properties'] = $props;
	$fc['features'][] = $line;
}


$json = json_encode($fc);

//file_put_contents('buildings.geojson', $json);

die($json);


function wkt2geojson($wkt){
	$coordsstart = strpos($wkt,"(");
	$type = trim(substr($wkt,0,$coordsstart));
	$coordstring = substr($wkt, $coordsstart);

	switch ($type) {
	    case "LINESTRING":
	    	$geom = array("type"=>"LineString","coordinates"=>array());
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$pairs = explode(",", $coordstring);
	    	foreach ($pairs as $k => $v) {
	    		$coords = explode(" ", trim($v));
	    		$geom['coordinates'][] = array((double)$coords[0],(double)$coords[1]);
	    	}
	    	return $geom;
	    	break;
	    case "POLYGON":
	    	$geom = array("type"=>"Polygon","coordinates"=>array());
			preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "MULTILINESTRING":
	    	$geom = array("type"=>"MultiLineString","coordinates"=>array());
	    	preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "POINT":
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$coords = explode(" ", $coordstring);
	    	//print_r($coords);
	    	$geom = array("type"=>"Point","coordinates"=>array((double)$coords[0],(double)$coords[1]));
	    	return $geom;
	        break;
	}
}
