<?php

/*
	Descripción: El algoritmo del punto en un polígono permite comprobar mediante
	programación si un punto está dentro de un polígono o fuera de ello.
*/

class pointLocation
{

	function pointInPolygon($point, $polygon, $pointOnVertex = true)
	{
		$this->pointOnVertex = $pointOnVertex;

		// Transformar la cadena de coordenadas en matrices con valores "x" e "y"
		$point = $this->pointStringToCoordinates($point);
		$vertices = array();
		foreach ($polygon as $vertex)
		{
			$vertices[] = $this->pointStringToCoordinates($vertex);
		}

		// Checar si el punto se encuentra exactamente en un vértice
		if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
			return "vertex";
		}

		// Checar si el punto está adentro del poligono o en el borde
		$intersections = 0;
		$vertices_count = count($vertices);

		for ($i=1; $i < $vertices_count; $i++)
		{
			$vertex1 = $vertices[$i-1];
			$vertex2 = $vertices[$i];
			if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x']))
			{
				// Checar si el punto está en un segmento horizontal
				return "boundary";
			}
			if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y'])
			{
				$xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
				if ($xinters == $point['x'])
				{
					// Checar si el punto está en un segmento (otro que horizontal)
					return "boundary";
				}
				if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters)
				{
					$intersections++;
				}
			}
		}
		// Si el número de intersecciones es impar, el punto está dentro del poligono.
		if ($intersections % 2 != 0)
		{
			return "inside";
		}
		else
		{
			return "outside";
		}
	}

	function pointOnVertex($point, $vertices)
	{
		foreach($vertices as $vertex) {
			if ($point == $vertex)
			{
				return true;
			}
		}

	}

	function pointStringToCoordinates($pointString)
	{
		$coordinates = explode(",", $pointString);
		return array("x" => trim($coordinates[0]), "y" => trim($coordinates[1]));
	}

	// Función para calcular la distancia utilizando la fórmula Haversine.
	// La fórmula de Haversine es una fórmula trigonométrica utilizada para calcular la distancia entre dos puntos en la superficie de una esfera.
	// Esta fórmula se utiliza comúnmente para calcular la distancia entre dos ubicaciones geográficas, especificadas por sus latitudes y longitudes, en la superficie terrestre.
	// El resultado de la fórmula es la distancia más corta entre los dos puntos a lo largo de la superficie de la esfera, y se expresa típicamente en unidades de distancia, como kilómetros o millas.

	// La fórmula de Haversine toma en cuenta la curvatura de la Tierra y es especialmente útil para calcular distancias en distancias relativamente cortas,
	// como las distancias entre ciudades o ubicaciones geográficas cercanas.

	function haversineDistance($location1, $location2)
	{
		$earthRadius = 6371; // Radio de la tierra en kilómetros

		$lat1Rad = deg2rad($location1["lat"]);
		$lon1Rad = deg2rad($location1["lon"]);

		$lat2Rad = deg2rad($location2["lat"]);
		$lon2Rad = deg2rad($location2["lon"]);

		$latDiff = $lat2Rad - $lat1Rad;
		$lonDiff = $lon2Rad - $lon1Rad;

		$a = sin($latDiff / 2) * sin($latDiff / 2) + cos($lat1Rad) * cos($lat2Rad) * sin($lonDiff / 2) * sin($lonDiff / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		$distance = $earthRadius * $c;

		return $distance;
	}
}