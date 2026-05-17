<?php

namespace Drupal\open_air_monitor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\open_air_monitor\Service\MonitorReaderService;

class MonitorTestController extends ControllerBase {

  protected $readerService;

  public function __construct(
    MonitorReaderService $reader_service
  ) {

    $this->readerService =
      $reader_service;

  }

  public static function create(
    ContainerInterface $container
  ) {

    return new static(
      $container->get(
        'open_air_monitor.reader'
      )
    );

  }

  public function test($city = 'lagos') {

    $data =
      $this->readerService
      ->getCityData($city);

    $attention_html =
      '<h3>⚠️ Areas Requiring Attention</h3><ul>';

    foreach ($data['sensors'] as $sensor) {

      if ($sensor['pm25'] >= 20) {

        $attention_html .= '
          <li>
            ' . $sensor['emoji'] . '
            <strong>' . $sensor['name'] . '</strong>
            — PM2.5: ' . $sensor['pm25'] . '
            (' . $sensor['status'] . ')
          </li>';

      }

    }

    $attention_html .= '</ul>';

    $sensor_html = '

      <h3>📊 Full Sensor Readings</h3>

      <table style="
        border-collapse: collapse;
        width: 100%;
      " border="1" cellpadding="8">

      <tr>

        <th style="background:#f2f2f2;">
          Location
        </th>

        <th style="background:#f2f2f2;">
          PM2.5
        </th>

        <th style="background:#f2f2f2;">
          Status
        </th>

        <th style="background:#f2f2f2;">
          Behaviour Advice
        </th>

      </tr>';

    foreach ($data['sensors'] as $sensor) {

      $sensor_html .= '

        <tr>

          <td>' . $sensor['name'] . '</td>

          <td>' . $sensor['pm25'] . '</td>

          <td>
            ' . $sensor['emoji'] . '
            ' . $sensor['status'] . '
          </td>

          <td>' . $sensor['advice'] . '</td>

        </tr>';

    }

    $sensor_html .= '</table>';

    return [

      '#markup' => '

        <h2>
          ' . $data['emoji'] . '
          ' . ucfirst($city) . ' Air Alert
        </h2>

        <h3>Overall City Status</h3>

        <p>
          <strong>Status:</strong>
          ' . $data['emoji'] . '
          ' . $data['status'] . '
        </p>

        <p>
          <strong>Average PM2.5:</strong>
          ' . $data['average_pm25'] . '
        </p>

        <p>
          <strong>Total Sensors:</strong>
          ' . $data['sensor_count'] . '
        </p>

        <p>
          <strong>Worst Location:</strong>
          ' . $data['worst_location'] . '
        </p>

        <p>
          <strong>Worst PM2.5:</strong>
          ' . $data['worst_value'] . '
        </p>

        <p>
          <strong>Behaviour Advice:</strong>
          ' . $data['advice'] . '
        </p>

        ' . $attention_html . '

        ' . $sensor_html . '

      ',

    ];

  }

}
