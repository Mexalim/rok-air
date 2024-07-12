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
        $date = new DateTimeImmutable();

        foreach($rows as $row) {
            $csv[] = array_combine($header, $row);
        }
        
        //build result array
        foreach ($csv as $v) {
          
          $coordinates_all = get_string_between($v['coordinates'], '(', ')');
          $coordinates = explode(' ', $coordinates_all);
          
          $result[] = [
              "Radius"=> 0.0,
              "Point"=> [
                "Lat"=> (float) $coordinates[1],
                "Lng"=> (float) $coordinates[0],
                "Alt"=> (double) 0.0,
                "Tag"=> $v['name'],
                "Tag2"=> "",
                "color"=> "White",
                "altmode"=> 3
              ],
              "Sidc"=> $_POST['color'],
              "CreateTime"=> !empty($v['observation_datetime']) ? date('c',strtotime($v['observation_datetime'])) : $date->format('c')
          ];


        } 
        
        //check if old points file exist
        if($_FILES['points_old']['error'] == 0){

          //marge new and old array
          $text_type = $_FILES['points_old']['type'];
          $text_tmp_name = $_FILES['points_old']['tmp_name'];

          // check the file is a txt
          $text_finfo = finfo_open(FILEINFO_MIME_TYPE);
          $text_mime = finfo_file($text_finfo, $text_tmp_name);
          finfo_close($finfo);
          $text_allowed_mime = array('text/plain','application/json');
          $txt_arr = json_decode(file_get_contents($text_tmp_name));

          if(in_array($text_mime, $text_allowed_mime) && is_uploaded_file($text_tmp_name)) {

            $txt_arr = json_decode(file_get_contents($text_tmp_name));

            foreach ($txt_arr as $txt_v) {
              $result[] = $txt_v;
            }

          }
          
        }

        //send file result
        header('Content-Disposition: attachment; filename="sample.txt"');
        header('Content-Type: text/plaintx'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
        header('Content-Length: ' . strlen($result));
        header('Connection: close');
 
        echo json_encode($result, JSON_UNESCAPED_UNICODE);

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