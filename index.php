<!DOCTYPE html>
<html>
<head>
	
	<title>Jaarkaart Amsterdam</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<link href="https://fonts.googleapis.com/css?family=Nunito:300,700" rel="stylesheet">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

	<script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css" integrity="sha512-wcw6ts8Anuw10Mzh9Ytw4pylW8+NAD4ch3lqm9lzAsTxg0GFeJgoAtxuCLREZSC5lUXdVyo/7yfsqFjQ4S+aKw==" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js" integrity="sha512-mNqn2Wg7tSToJhvHcqfzLMU6J4mkOImSPTxVZAdo+lcPlk+GhZmYgACEe0x35K7YzW1zJ7XyJV/TT1MrdXvMcA==" crossorigin=""></script>


	<style>
		html, body{
			height: 100%;
			margin:0;
			font-family: 'Nunito', sans-serif;
		}
		#map {
			width: 100%;
			height: 60%;
		}
		.leaflet-left .leaflet-control{
			margin-top: 30px;
			margin-left: 20px;
		}
		.leaflet-container .leaflet-control-attribution{
			color: #DD0005;
		}
		.leaflet-control-attribution a{
			color: #DD0005;
		}
		.leaflet-touch .leaflet-control-layers, .leaflet-touch .leaflet-bar{
			border: 2px solid #717171;
		}
		#start{
			color: #000;
			border: 2px solid #717171;
			border-radius: 4px;
			background-color: #FEC609;
			padding: 10px 20px;
			width: 420px;
			text-align: left;
			position: absolute;
			right: 0;
			top:80px;
		}
		#legenda{
			color: #DD0005;
			position: absolute;
			z-index: 1000;
			right: 40px;
			top: 29px;
		}
		#year{
			font-size: 49px;
			width: 130px;
		}
		button:focus, input:focus {
			outline:0;
		}
		a, a:visited, a:hover{
			text-decoration: none;
			color: #000;
		}
		a:hover{
			text-decoration: underline;
		}
		ul{
			padding-left: 0;
			list-style-type: none;
		}
		#album img{
			width: 100%;
			margin-top: 20px;
		}
	</style>

	
</head>
<body>


<div id='map'>
</div>

<div id="legenda">
	<input id="year" type="year" value="1900" />
</div>

<div id="album" class="container-fluid">
</div>




<script>
	var map = L.map('map',{
  		scrollWheelZoom: false,
		attributionControl: false
	}).setView([52.369132, 4.893689], 15);

	

	L.tileLayer('//{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
	    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
	    maxZoom: 19
	}).addTo(map);

	L.control.attribution({position: 'bottomleft'}).addTo(map);

	refreshMap();

	function refreshMap(){

		var jaar = $('#year').val();
		
		buildings = L.geoJson(null, {
		    style: function(feature) {
		        return {
		            color: "#DD0005",
		            weight: 2,
		            opacity: 1,
		            clickable: true
		        };
		    },
		    onEachFeature: function(feature, layer) {
				layer.on({
			        click: whenBuildingClicked
			    });
		    },
	        pointToLayer: function (feature, latlng) {
				return L.circleMarker(latlng, {
					radius: 6,
					weight: 3,
					opacity: 0.7,
					fillOpacity: 0.3
				});
			}
		}).addTo(map);

		

	    geojsonfile = 'geojson-buildings.php?jaar='+jaar;
		
		$.getJSON(geojsonfile, function(data) {
	        buildings.addData(data).bringToFront();
	    });

	}

    function whenClicked(e) {
    	var props = e['target']['feature']['properties'];
		showLine(props.linenr);
	  	
	}

	function whenBuildingClicked(e) {
    	var props = e['target']['feature']['properties'];
		console.log(props);
    	$('#album').html('');
        
    	var jaar = $('#year').val();
        $('#album').load('building.php?uri=' + props.id + '&year=' + jaar);
	  	
	}

	$(document).ready(function(){


		$('input').bind("enterKey",function(e){
			buildings.clearLayers();
			refreshMap();
		});

		$('input').keyup(function(e){
			if(e.keyCode == 13){
				$(this).trigger("enterKey");
			}
		});
	});

</script>



</body>
</html>
