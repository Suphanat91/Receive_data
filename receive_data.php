<?php
require_once("API/dblib.php");
require_once("API/bblib.php");
require_once("API/constants.php");
require_once("API/mapping_sensors.php");
session_start();
date_default_timezone_set('Asia/Bangkok');

// Check mode
$mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING);

switch ($mode) {
    case 'SSDB':
        $data = set_sensor_to_database($_POST);
        break;
    default:
        $data = array("result" => GISTDA_WS_ERROR);
        break;
}
export_to_json($data);

function export_to_json($data) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
}

function set_sensor_to_database($post_params) {
    $device_id = filter_var($post_params['device_id'], FILTER_SANITIZE_STRING);

    if (!validate_input($device_id)) {
        return array("result" => GISTDA_WS_ERROR);
    }

    if (count($post_params) < 11) {
        return array("result" => GISTDA_WS_ERROR);
    }

    $conn = mydb_connect();
    if (!$conn) {
        return array("result" => GISTDA_WS_ERROR);
    }

    // Check if device_id already exists
    $check_query = "SELECT device_id FROM sensor_data_001 WHERE device_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $device_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        return array("result" => GISTDA_WS_ERROR);
    }

    $stmt->close();

    $latitude = filter_var($post_params['lat'], FILTER_SANITIZE_STRING);
    $longitude = filter_var($post_params['lon'], FILTER_SANITIZE_STRING);
    $gps_date = filter_var($post_params['gps_d'], FILTER_SANITIZE_STRING);
    $gps_time = filter_var($post_params['gps_t'], FILTER_SANITIZE_STRING);
    $temperature = filter_var($post_params['temp'], FILTER_SANITIZE_STRING);
    $humidity = filter_var($post_params['hu'], FILTER_SANITIZE_STRING);
    $co = filter_var($post_params['co'], FILTER_SANITIZE_STRING);
    $co2 = filter_var($post_params['co2'], FILTER_SANITIZE_STRING);
    $pm25 = filter_var($post_params['pm25'], FILTER_SANITIZE_STRING);
    $pm10 = filter_var($post_params['pm10'], FILTER_SANITIZE_STRING);
    $pm1 = filter_var($post_params['pm1'], FILTER_SANITIZE_STRING);

    $sql = "INSERT INTO sensor_data_001 (
                device_id, latitude, longitude, gps_date, gps_time, temperature, humidity, co, co2, pm25, pm10, pm1
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssss", $device_id, $latitude, $longitude, $gps_date, $gps_time, $temperature, $humidity, $co, $co2, $pm25, $pm10, $pm1);

    $result = $stmt->execute();
    if ($result) {
        $ret = array("status" => GISTDA_WS_OK);
    } else {
        $ret = array("result" => GISTDA_WS_ERROR);
    }

    $stmt->close();
    $conn->close();

    return $ret;
}
?>
