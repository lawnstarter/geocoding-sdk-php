<?php

namespace Lawnstarter\Geocoding;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    protected $apiKey;
    protected $guzzleClient;

    public function __construct($apiKey, GuzzleClient $guzzleClient = null)
    {
        $this->apiKey = $apiKey;

        if (is_null($guzzleClient)) {
            $guzzleClient = new GuzzleClient([
                'base_uri' => 'https://maps.googleapis.com/maps/api/'
            ]);
        }

        $this->guzzleClient = $guzzleClient;
    }

    public function geocode($address)
    {
        $lat = null;
        $lng = null;
        try {

            // BUILD REQUEST
            $request = $this->guzzleClient->get('geocode/json', ['query' => [
                'address' => $address,
                'key' => $this->apiKey,
            ]]);

            // EXTRACT DATA
            $data = json_decode($request->getBody(), true);
            if (isset($data['status']) && $data['status'] == 'OK') {
                $location = $data['results'][0]['geometry']['location'];
                $lat = $location['lat'];
                $lng = $location['lng'];
            } else {
                return null;
            }
        } catch (\Exception $e) {
            throw new GeocodingException($e->getMessage(), 0, $e);
        }

        // compile result
        $result = new \stdClass;
        $result->lat = $lat;
        $result->lng = $lng;

        return $result;
    }
}
