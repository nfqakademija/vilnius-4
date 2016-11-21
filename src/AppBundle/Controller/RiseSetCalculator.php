<?php
/**
 * Created by PhpStorm.
 * User: shalifar
 * Date: 16.11.20
 * Time: 18.31
 */

namespace AppBundle\Controller;


class RiseSetCalculator
{
    public function __construct()
    {
        //$this->city=$city;
//        $this->latitude=$latitude;
//        $this->longitude=$longitude;
//        $this->timezone=$timezone;
    }

    public function getRiseSet($city)
    {
        //$google_api_key = $this->getContainer()->getParameter('googlemaps_api_key');
        $google_api_key = 'AIzaSyAxIUHAHOUopm10UZaMDBXiiDxqkTnaAzg';

        $data = $this->getData('https://maps.googleapis.com/maps/api/geocode/json?address='.$city);
        $lat = $data['results'][0]['geometry']['location']['lat'];
        $lng = $data['results'][0]['geometry']['location']['lng'];

        $data = $this->getData('https://maps.googleapis.com/maps/api/timezone/json?location='.
            $lat . ','.
            $lng. '&timestamp='.
            time() . '&key='.
            $google_api_key);
        $timezone = $data['rawOffset'] / 3600;

        $tz_sign = $this->getSign($timezone);
        $timezone = abs($timezone);

        $lat_sign = $this->getSign($lat);
        $lat = abs($lat);

        $lng_sign = $this->getSign($lng);
        $lng = abs($lng);

        $latitude = $this->parseCoordinates($lat);
        $longitude = $this->parseCoordinates($lng);

        $today = date('Y-m-d');
        $date_args = explode("-", $today);

        $data = $this->getPlainData('http://aa.usno.navy.mil/cgi-bin/aa_mrst2.pl?form=2&ID=AA'.
            '&year='.$date_args[0].
            '&month='.$date_args[1].
            '&day='.$date_args[2].
            '&reps=1'.
            '&body=1'.
            '&place=mercury'.
            '&lon_sign='.$lng_sign.
            '&lon_deg='.$longitude['deg'].
            '&lon_min='.$longitude['min'].
            '&lon_sec=1'.
            '&lat_sign='.$lat_sign.
            '&lat_deg='.$latitude['deg'].
            '&lat_min='.$latitude['min'].
            '&lat_sec=1'.
            '&height=1'.
            '&tz='.$timezone.
            '&tz_sign='.$tz_sign);

        $schedule = $this->parseResponse($data);



        return $data;
    }

    private function parseResponse($response)
    {
        $result['rise'] = substr($response, 1421, 5);
        $result['set'] = substr($response, 1455, 5);

        return $result;
    }

    private function parseCoordinates($coordinate)
    {
        $result['min'] = rtrim(round(($coordinate - floor($coordinate))/5*3, 2)*100, ".0");
        $result['deg'] = rtrim(floor($coordinate), ".0");

        return $result;
    }

    private function getSign($value)
    {
        if($value < 0 ){return -1;}
        return 1;

    }

    private function getData($url)
    {
        $json = file_get_contents($url);
        $data = json_decode($json, true);

        return $data;
    }

    private function getPlainData($url)
    {
        $data = file_get_contents($url);

        return $data;
    }
}
