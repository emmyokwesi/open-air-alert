<?php

namespace Drupal\open_air_monitor\Service;

use GuzzleHttp\ClientInterface;

class MonitorReaderService {

  protected $httpClient;

  protected $cities = [

    'lagos' => [
      'xmin' => 2.8,
      'ymin' => 6.2,
      'xmax' => 4.2,
      'ymax' => 7.1,
    ],

    'benin' => [
      'xmin' => 5.5,
      'ymin' => 6.1,
      'xmax' => 5.9,
      'ymax' => 6.6,
    ],

  ];

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  public function getCityData($city_key) {

    if (!isset($this->cities[$city_key])) {
      return FALSE;
    }

    $city = $this->cities[$city_key];

    $url = 'https://map-data.airgradient.com/map/api/v1/measurements/current/cluster'
      . '?xmin=' . $city['xmin']
      . '&ymin=' . $city['ymin']
      . '&xmax=' . $city['xmax']
      . '&ymax=' . $city['ymax']
      . '&zoom=9'
      . '&measure=pm25'
      . '&excludeOutliers=true';

    $response = $this->httpClient->request(
      'GET',
      $url
    );

    $data = json_decode(
      $response->getBody(),
      TRUE
    );

    if (!isset($data['data'])) {
      return FALSE;
    }

    $total = 0;
    $count = 0;
    $worst = 0;
    $worst_location = '';

    $sensors = [];

    foreach ($data['data'] as $sensor) {

      $value = $sensor['value'];

      $status =
        $this->calculateStatus($value);

      $total += $value;
      $count++;

      if ($value > $worst) {

        $worst = $value;

        $worst_location =
          $sensor['locationName'];

      }

      $sensors[] = [
        'name' => $sensor['locationName'],
        'pm25' => $value,
        'status' => $status,
        'emoji' =>
          $this->getStatusEmoji($status),
        'advice' =>
          $this->getBehaviourAdvice($status),
      ];

    }

    usort($sensors, function ($a, $b) {
      return $b['pm25'] <=> $a['pm25'];
    });

    $average = $count
      ? round($total / $count, 1)
      : 0;

    $status =
      $this->calculateStatus($average);

    return [
      'average_pm25' => $average,
      'status' => $status,
      'emoji' =>
        $this->getStatusEmoji($status),
      'advice' =>
        $this->getBehaviourAdvice($status),
      'sensor_count' => $count,
      'worst_location' => $worst_location,
      'worst_value' => $worst,
      'sensors' => $sensors,
    ];

  }

  protected function calculateStatus($pm25) {

    if ($pm25 <= 12) {
      return 'Good';
    }

    if ($pm25 <= 35) {
      return 'Moderate';
    }

    if ($pm25 <= 55) {
      return 'Unhealthy';
    }

    return 'Unsafe';

  }

  protected function getStatusEmoji($status) {

    $emojis = [
      'Good' => 'ðŸŸ¢',
      'Moderate' => 'ðŸŸ¡',
      'Unhealthy' => 'ðŸŸ ',
      'Unsafe' => 'ðŸ”´',
    ];

    return $emojis[$status] ?? 'âšª';

  }

  protected function getBehaviourAdvice($status) {

    $advice = [

      'Good' =>
        'Air quality is healthy. Outdoor activities are safe.',

      'Moderate' =>
        'Air quality is moderate. Sensitive individuals should reduce prolonged outdoor exposure.',

      'Unhealthy' =>
        'Air quality is unhealthy. Stay indoors when possible and reduce strenuous outdoor activity.',

      'Unsafe' =>
        'Air quality is unsafe. Stay indoors, wear masks outdoors, and avoid prolonged exposure.',

    ];

    return $advice[$status] ?? '';

  }

}
