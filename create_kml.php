<?php

/**
 * Get coordinates
 */
function get_string_between($string, $start, $end){
  $string = ' ' . $string;
  $ini = strpos($string, $start);
  if ($ini == 0) return '';
  $ini += strlen($start);
  $len = strpos($string, $end, $ini) - $ini;
  return substr($string, $ini, $len);
}


/**
 * Form
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
  if($_FILES['csv']['error'] == 0){

      $name = $_FILES['csv']['name'];
    
      $ext = strtolower(end(explode('.', $_FILES['csv']['name'])));
      $type = $_FILES['csv']['type'];
      $tmpName = $_FILES['csv']['tmp_name'];

      // check the file is a csv
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $tmpName);
      finfo_close($finfo);
      $allowed_mime = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');

      if(in_array($mime, $allowed_mime) && is_uploaded_file($tmpName)) {

        // Map Rows and Loop Through Them 
        $rows   = array_map('str_getcsv', file($tmpName));
        $header = array_shift($rows);
        $csv    = array();
        $result = [];
        $kml = '';
        $date = new DateTimeImmutable();

        foreach($rows as $row) {
            $csv[] = array_combine($header, $row);
        }
 
        $kml = '<?xml version="1.0" encoding="UTF-8"?>
              <kml xmlns="http://www.opengis.net/kml/2.2"> <Document>';


        //build result array
        foreach ($csv as $key => $v) {
            $result = '';

            $kml .= '<name>'.$v['name'].'</name>
              <description></description> 
              <Style id="plyid_'.$key.'">
              <LineStyle>
              <color>red</color>
              <width>4</width>
              </LineStyle>
              <PolyStyle>
              <color>red</color>
              </PolyStyle>
              </Style> <Placemark>
              <name>'.$v['name'].'</name>
              <description></description>
              <styleUrl>#plyid_'.$key.'</styleUrl>
              <LineString>
              <extrude>1</extrude>
              <tessellate>1</tessellate>
              <altitudeMode>absolute</altitudeMode><coordinates>';

            //LINESTRING (38.0621005 49.1609452, 38.1011843 49.178323, 38
            $coordinates_all = get_string_between($v['coordinates'], '(', ')'); 

            //check if value looks like this POLYGON ((38.1198655 49.1720676, 38.1198893 49.175241, 38.1225782
            if (strpos($coordinates_all, '(') !== false) {
                $coordinates_all = get_string_between($coordinates_all, '(', ')'); 
            }

            $coordinates = explode(',', $coordinates_all);

            foreach ($coordinates as $coordinate) {
              $coordinate = str_replace(' ',',', $coordinate);
              $result .= $coordinate.',0';
            }

            $kml .= $result.'</coordinates></LineString> </Placemark>';
         
        } 

        $kml .= '</Document> </kml>';

        //send file result
        header('Content-Disposition: attachment; filename="sample.kml"');
        header('Content-Type: text/plaintx'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
        //header('Content-Length: ' . strlen($result));
        header('Connection: close');
 
        //echo json_encode($result, JSON_UNESCAPED_UNICODE);
        echo $kml;
        exit();

      } else {
        http_response_code(404);
        die();
      }
      
  }

} else {
  http_response_code(404);
  die();
}

?>