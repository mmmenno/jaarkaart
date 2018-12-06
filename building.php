<?php

$uri = $_GET['uri'];
$marginAhead = $_GET['year']+10;
$marginBefore = $_GET['year']-2;

$sparqlquery = '
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX schema: <http://schema.org/>
PREFIX edm: <http://www.europeana.eu/schemas/edm/>
PREFIX hg: <http://rdf.histograph.io/>
PREFIX sem: <http://semanticweb.cs.vu.nl/2009/11/sem/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX geo: <http://www.opengis.net/ont/geosparql#>

SELECT 	?prefl ?beginmin ?beginmax ?endmin ?endmax 
		?namebeginmin ?namebeginmax ?nameendmin ?nameendmax 
		?namelabel ?cho ?img ?imgdate WHERE {
    <' . $uri . '> sem:hasEarliestBeginTimeStamp ?beginmin .
    <' . $uri . '> sem:hasLatestBeginTimeStamp ?beginmax .
    OPTIONAL{
    	<' . $uri . '> sem:hasEarliestEndTimeStamp ?endmin .
    	<' . $uri . '> sem:hasLatestEndTimeStamp ?endmax .
	}
    <' . $uri . '> skos:prefLabel ?prefl .
    <' . $uri . '> schema:name ?name .
    OPTIONAL{
      ?cho dct:spatial <' . $uri . '> .
      ?cho foaf:depiction ?img .
      ?cho sem:hasBeginTimeStamp ?imgdate .
      FILTER (year(xsd:dateTime(?imgdate)) > ' . $marginBefore . ')
    }
    <' . $uri . '> schema:name ?name .
	?name rdfs:label ?namelabel .
	OPTIONAL {
		?name sem:hasEarliestBeginTimeStamp ?namebeginmin .
		?name sem:hasLatestBeginTimeStamp ?namebeginmax .
		?name sem:hasEarliestEndTimeStamp ?nameendmin .
		?name sem:hasLatestEndTimeStamp ?nameendmax . 
	}
}
ORDER BY ?imgdate
LIMIT 10
';

//#FILTER (year(xsd:dateTime(?imgdate)) < ' . $marginAhead . ') .
      

$url = "https://api.data.adamlink.nl/datasets/AdamNet/all/services/endpoint/sparql?default-graph-uri=&query=" . urlencode($sparqlquery) . "&format=application%2Fsparql-results%2Bjson&timeout=120000&debug=on";

$querylink = "https://data.adamlink.nl/AdamNet/all/services/endpoint#query=" . urlencode($sparqlquery) . "&contentTypeConstruct=text%2Fturtle&contentTypeSelect=application%2Fsparql-results%2Bjson&endpoint=https%3A%2F%2Fdata.adamlink.nl%2F_api%2Fdatasets%2Fmenno%2Falles%2Fservices%2Falles%2Fsparql&requestMethod=POST&tabTitle=Query&headers=%7B%7D&outputFormat=table";



$json = file_get_contents($url);

$data = json_decode($json,true);

$i = 0;

$names = array();
$checknames = array();
$checkimgs = array();
$imgs = array();

foreach ($data['results']['bindings'] as $row) {

	$titel = $row['prefl']['value'];
	$beginmin = $row['beginmin']['value'];
	$beginmax = $row['beginmax']['value'];
	$endmin = $row['endmin']['value'];
	$endmax = $row['endmax']['value'];

	if(isset($row['namelabel']) && !in_array($row['namelabel']['value'], $checknames)){
		$checknames[] = $row['namelabel']['value'];
		$thisname = array("name"=>$row['namelabel']['value']);
		if(isset($row['namebeginmin'])){
			$thisname['namebeginmin'] = $row['namebeginmin']['value'];
		}
		if(isset($row['namebeginmax'])){
			$thisname['namebeginmax'] = $row['namebeginmax']['value'];
		}
		if(isset($row['nameendmin'])){
			$thisname['nameendmin'] = $row['nameendmin']['value'];
		}
		if(isset($row['nameendmax'])){
			$thisname['nameendmax'] = $row['nameendmax']['value'];
		}
		$names[] = $thisname;
	}

	if(isset($row['cho']) && !in_array($row['cho']['value'], $checkimgs)){
		$checkimgs[] = $row['cho']['value'];
		$imgs[] = array("img"=>$row['img']['value'],"cho"=>$row['cho']['value'],"date"=>substr($row['imgdate']['value'],0,4));
	}
	
}

// years of building
if($beginmin==$beginmax){
	$years = $beginmin;
}else{
	$years = $beginmin . " / " . $beginmax;
}
$years .= " - ";
if(isset($endmin) && $endmin>0){
	$years .= $endmin;
}
if(isset($endmax) && $endmax>0 && $endmax > $endmin){
	$years .= " / " . $endmax;
}

// valid name for year?
foreach ($names as $v) {
	if(isset($v['namebeginmin']) && isset($v['nameendmax'])){
		if($_GET['year'] >= $v['namebeginmin'] && $_GET['year'] <= $v['nameendmax']){
			$titel = $v['name'];
		}
	}
	if(isset($v['namebeginmin']) && !isset($v['nameendmax'])){
		if($_GET['year'] >= $v['namebeginmin']){
			$titel = $v['name'];
		}
	}
}


?>

<div class="row">
	<div class="col-md-3">
		<h1><?= $titel ?></h1>

		bestaansperiode gebouw:
		<h3><?= $years ?></h3>
	</div>
	<div class="col-md-3">
		<h1>bekend als</h1>
		<ul>
		<?php
		foreach ($names as $v) {
			echo "<li>" . $v['name'];
			if(isset($v['namebeginmin']) && isset($v['nameendmax'])){
				echo " (" . $v['namebeginmin'] . "-" . $v['nameendmax'] . ")";
			}
			if(isset($v['namebeginmin']) && !isset($v['nameendmax'])){
				echo " (vanaf " . $v['namebeginmin'] . ")";
			}
			echo "</li>";
		}
		?>
		</ul>
	</div>
	<div class="col-md-3">
		<a target="_blank" href="<?= $imgs[0]['cho'] ?>"><img src="<?= $imgs[0]['img'] ?>" />
		<?= $imgs[0]['date'] ?>	
	</div>
	<div class="col-md-3">
		<a target="_blank" href="<?= $imgs[1]['cho'] ?>"><img src="<?= $imgs[1]['img'] ?>" />
		<?= $imgs[1]['date'] ?>
	</div>
</div>

<a href="<?= $querylink ?>">SPARQL it yourself</a>
<?php

//print_r(count($imgs));
