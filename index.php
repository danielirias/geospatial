<h3>Operaciones con mallas geográficas y distancia entre puntos</h3>

<?php

	// Ubicación de la tienda dentro de Tegucigalpa (Mall Las Cascadas)
	$storeLocation = ["lat"=>"14.0773787107216", "lon"=>"-87.20083773247653"];

	// Coordenadas del cliente:
	$customerCordinates = ["lat"=>"14.094742", "lon"=>"-87.183208"]; //Dentro de Tegucigalpa
	//$customerCordinates = ["lat"=>"14.118717", "lon"=>"-87.112196"]; //Fuera de Tegucigalpa

	//La ubicación donde se encuentra el cliente:
	$customerLocation = $customerCordinates["lat"].', '.$customerCordinates["lon"];


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


		// Compruebo si la ubicación del cliente está dentro o fuera de la malla de operación descrita.
		require_once ("pointLocation.php");
		$pointLocation = new pointLocation();

		if ($pointLocation->pointInPolygon($customerLocation, $polygonPoints) == "inside")
		{
			// ESTÁ DENTRO DE LA MALLA
			$message = "** El cliente está ADENTRO de la malla **";
		}

		if ($pointLocation->pointInPolygon($customerLocation, $polygonPoints) == "outside")
		{
			// ESTÁ FUERA DE LA MALLA
			$message = "** El cliente está AFUERA de la malla **";
		}

		if ($pointLocation->pointInPolygon($customerLocation, $polygonPoints) == "vertex")
		{
			// ESTÁ EXACTAMENTE EN UN VÉRTICE
			$message = "** El cliente está en un VÉRTICE de la malla **";
		}

		echo $message;
	}


	// -------------------------------------------------------------------------------------------------
	// 2. OBTENGO LA DISTANCIA ENTRE LOS DOS PUNTOS: TIENDA Y CLIENTE

	// Para ello recurro a la API de Google Maps. Es necesario obtener una clave API para poder visualizar el mapa.
	// La distancia también puede determinarse a través de la fórmula de Haversine y la función incluida en la clase pointLocation,
	// pero esta no ofrece un cálculo de tiempo, como sí lo hace la API de Google.
	$apiKey = "AIzaSyB1n587x4IGX5JoxqAwiQbVI65mN_yIfcI";

	//Origins: Representa la ubucacion de la tienda
	//Destinations: Representa la ubicación DESTINO del cliente
	$urlMatrix = "https://maps.googleapis.com/maps/api/distancematrix/json?key=".$apiKey."&units=metric&origins=".$storeLocation["lat"].",".$storeLocation["lon"]."&destinations=".$customerCordinates["lat"].",".$customerCordinates["lon"]."&mode=driving";
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

	$storeAddress = $response_a['origin_addresses'][0];
	$customerAddress = $response_a['destination_addresses'][0];

	echo '<h3>Cálculo de distancia y tiempo usando el sistema métrico internacional (metro, segundo)</h3>';
	echo 'Distancia entre la tienda y el cliente (metros): '. number_format(trim($distance), 0);
	echo '<br>';
	echo 'Duración del viaje hacia el cliente (segundos): '. number_format(trim($time), 0);
	echo '<br>';
	echo 'Dirección de la tienda: '.$storeAddress;
	echo '<br>';
	echo 'Dirección del cliente: '.$customerAddress;
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
		$distance = $pointLocation->haversineDistance($location,  $storeLocation);

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

	<script>
		function initMap()
		{
			//Las coordenadas del cliente
			const customerLatLon = { lat: <?php echo $customerCordinates["lat"]; ?>, lng: <?php echo $customerCordinates["lon"]; ?>};

			// Creo el mapa y lo centro en la ubicación del cliente
			var map = new google.maps.Map(document.getElementById("mapa"), {
				zoom: 11,
				center: customerLatLon,
				mapTypeId: "roadmap"
			});

			// El marcador de la ubicación del cliente
			new google.maps.Marker({
				position: customerLatLon,
				map,
				label: "B",
				title: "Cliente"
			});

			// El marcador de la ubicación de nuestra tienda
			const storeLocation = { lat: <?php echo $storeLocation["lat"]; ?>, lng: <?php echo $storeLocation["lon"]; ?>};
			new google.maps.Marker({
				position: storeLocation,
				map,
				label: "A",
				title: "Tienda"
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

			Mallapoligono.setMap(map);
		}
	</script>

	<script async defer
		src="https://maps.googleapis.com/maps/api/js?callback=initMap">
	</script>