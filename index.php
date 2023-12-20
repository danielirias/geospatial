<h3>Operaciones con mallas geográficas y distancia entre puntos</h3>

<?php

	// Ubicación del orígen dentro de Tegucigalpa (Mall Las Cascadas)
	$originLocation = ["lat"=>"14.0773787107216", "lon"=>"-87.20083773247653"];

	// Coordenadas del destino:
	$destinationCordinates = ["lat"=>"14.094742", "lon"=>"-87.183208"]; //Dentro de Tegucigalpa
	//$destinationCordinates = ["lat"=>"14.118717", "lon"=>"-87.112196"]; //Fuera de Tegucigalpa

	//La ubicación donde se encuentra el destino:
	$destinationLocation = $destinationCordinates["lat"].', '.$destinationCordinates["lon"];


	// -------------------------------------------------------------------------------------------------
	// 1. CREAR MALLA DE OPERACIÓN

	// Una malla geográfica deben seguir las siguientes reglas:
	// 1. Las coordenadas del punto inicial siempre deben ser las mismas del punto final. De esta forma se cierra el polígono.
	// 2. Las coordenadas de los puntos deben seguir un orden en el que no se creen intersecciones entre los vectores creados por dos puntos diferentes.
	// 3. Los puntos deben crear un polígono.

	// Leo los datos de un archivo JSON.
	// El archivo tiene todos los puntos necesarios para crear una malla de la ciudad de Tegucigalpa.
	// Ruta al archivo JSON
	$jsonFile = 'json/polygon.json';

	// Leo  todo el contenido del archivo JSON
	$json_data = file_get_contents($jsonFile);

	// Decodificamos el JSON en un arreglo asociativo
	$points = json_decode($json_data, true);

	if ($points === null)
	{
		// Manejamos los errores de decodificación si los hay
		echo "Error al decodificar el JSON.";
	}
	else
	{
		// Ahora recupero los datos de cada elemento del arreglo
		$strPolygon = "";
		$polygonPoints = array();

		foreach ($points as $point)
		{
			// Con cada punto vamos a crear el polígono del mapa
			$strPolygon .=  '{lat: '.$point["lat"].', lng: '.$point["lon"].'},';

			// Agrego cada punto a un nuevo objeto polígono, que no contiene las claves lat y lon, solo los valores.
			// Este objeto nos servirá para hacer el cálculo de ubicación.
			// Cada punto debe separar lat y lon con una coma (,).
			array_push($polygonPoints, $point["lat"].', '.$point["lon"]);
		}


		// Compruebo si la ubicación del destino está dentro o fuera de la malla de operación descrita.
		require_once ("pointLocation.php");
		$pointLocation = new pointLocation();

		if ($pointLocation->pointInPolygon($destinationLocation, $polygonPoints) == "inside")
		{
			// ESTÁ DENTRO DE LA MALLA
			$message = "** El destino está DENTRO de la malla **";
		}

		if ($pointLocation->pointInPolygon($destinationLocation, $polygonPoints) == "outside")
		{
			// ESTÁ FUERA DE LA MALLA
			$message = "** El destino está FUERA de la malla **";
		}

		if ($pointLocation->pointInPolygon($destinationLocation, $polygonPoints) == "vertex")
		{
			// ESTÁ EXACTAMENTE EN UN VÉRTICE
			$message = "** El destino está en un VÉRTICE de la malla **";
		}

		echo $message;
	}


	// -------------------------------------------------------------------------------------------------
	// 2. OBTENGO LA DISTANCIA ENTRE LOS DOS PUNTOS: ORÍGEN Y DESTINO

	// Para ello recurro a la API de Google Maps. Es necesario obtener una clave API para poder visualizar el mapa.
	// La distancia también puede determinarse a través de la fórmula de Haversine y la función incluida en la clase pointLocation,
	// pero esta no ofrece un cálculo de tiempo, como sí lo hace la API de Google.

	$apiKey = ""; //Aquí va tu clave de API de Google

	//Origins: Representa la ubucacion del ORIGEN
	//Destinations: Representa la ubicación DESTINO
	$urlMatrix = "https://maps.googleapis.com/maps/api/distancematrix/json?key=".$apiKey."&units=metric&origins=".$originLocation["lat"].",".$originLocation["lon"]."&destinations=".$destinationCordinates["lat"].",".$destinationCordinates["lon"]."&mode=driving";
	//echo $urlMatrix; echo '<br>';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $urlMatrix);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$response = curl_exec($ch);
	curl_close($ch);

	$response_a = json_decode($response, true);

	$distance = $response_a['rows'][0]['elements'][0]['distance']['value'];
	$time = $response_a['rows'][0]['elements'][0]['duration']['value'];

	$originAddress = $response_a['origin_addresses'][0];
	$destinationAddress = $response_a['destination_addresses'][0];

	echo '<h3>Cálculo de distancia y tiempo usando el sistema métrico internacional (metro, segundo)</h3>';
	echo 'Distancia entre el orígen y el destino (metros): '. number_format(trim($distance), 0);
	echo '<br>';
	echo 'Duración del viaje hacia el destino (segundos): '. number_format(trim($time), 0);
	echo '<br>';
	echo 'Dirección del orígen: '.$originAddress;
	echo '<br>';
	echo 'Dirección del destino: '.$destinationAddress;
	echo '<br>';


	// -------------------------------------------------------------------------------------------------
	// DETERMINAR SI UN GRUPO DE UBICACIONES ESTÁN DENTRO DE UN RADIO ESPECÍFICO

	/*
	// Ubicaciones almacenadas en un Array:
	$locations = [
		["lat" => "14.0795967", "lon" => "-87.2409725"],
		["lat" => "14.0761", "lon" => "-87.2394705"],
		["lat" => "14.0727282", "lon" => "-87.2419167"],
		["lat" => "14.0722079", "lon" => "-87.2431827"]
	];
	*/

	// Ruta al archivo JSON
	$jsonFile = 'json/poi.json';

	// Leo  todo el contenido del archivo JSON
	$json_data = file_get_contents($jsonFile);

	// Decodificamos el JSON en un arreglo asociativo
	$locations = json_decode($json_data, true);

	// Ubicaciones que se encuentren en un radio de X km.
	$radiusDistance = 4.5;
	$filtered_locations = "";
	$counter = 0;
	foreach ($locations as $location)
	{
		//Obtengo la distancia entre los dos puntos
		$distance = $pointLocation->haversineDistance($location,  $originLocation);

		if ($distance <= $radiusDistance)
		{
			// $filtered_locations almacena los elementos dentro del radio deseado
			$filtered_locations .= '<tr><td>'.$location["lat"].", ".$location["lon"]."</td><td>".$distance."</td></tr>";
			$counter = $counter + 1;
		}
	}

	echo '<h3>Elementos dentro del radio de operación indicado: '.$radiusDistance.' km</h3>';
	echo $counter.' de '.count($locations).' ubicaciones están dentro del radio indicado.';
	echo '<br>';
	//echo '<table border="1"><th>Coordenadas</th><th>Distancia</th><tbody>'.$filtered_locations.'</tbody></table>';
?>

	<!-- Dibujo el mapa con la zona de cobertura -->
	<div id="mapa" style="height: 400px; width: 100%; margin-top: 20px;"></div>

	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $apiKey; ?>"></script>

	<script>
		//Las coordenadas del destino
		const destinationLatLon = { lat: <?php echo $destinationCordinates["lat"]; ?>, lng: <?php echo $destinationCordinates["lon"]; ?>};

		// Creo el mapa y lo centro en la ubicación del destino
		var thisMap = new google.maps.Map(document.getElementById("mapa"), {
			zoom: 12,
			center: destinationLatLon,
			mapTypeId: "roadmap"
		});

		// El marcador de la ubicación del destino
		new google.maps.Marker({
			position: destinationLatLon,
			map: thisMap,
			label: "B",
			title: "DESTINO"
		});

		// El marcador de la ubicación de nuestro orígen
		const originLatLon = { lat: <?php echo $originLocation["lat"]; ?>, lng: <?php echo $originLocation["lon"]; ?>};
		new google.maps.Marker({
			position: originLatLon,
			map: thisMap,
			label: "A",
			title: "ORÍGEN"
		});

		// Establezco las coordenadas de cada punto del polígono.
		var triangleCoords = [
			<?php echo $strPolygon; ?>
		];

		// Construyo el polígono.
		var Mallapoligono = new google.maps.Polygon({
			paths: triangleCoords,
			strokeColor: "#FF0000",
			strokeOpacity: 0.8,
			strokeWeight: 2,
			fillColor: "#FF0000",
			fillOpacity: 0.1
		});

		Mallapoligono.setMap(thisMap);

		// Crear un círculo alrededor del punto central
		var coverageRadius = new google.maps.Circle({
			strokeColor: '#FA9000',
			strokeOpacity: 0.8,
			strokeWeight: 2,
			fillColor: '#FA9000',
			fillOpacity: 0.2,
			center: originLatLon,
			radius: <?php echo $radiusDistance; ?> * 1000, // Radio en metros
		});

		coverageRadius.setMap(thisMap);
	</script>