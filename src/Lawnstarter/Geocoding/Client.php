<?php

namespace Lawnstarter\Geocoding;

use GuzzleHttp\Client as GuzzleClient;
use Mockery\Exception;

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
        $longitude = null;
        $latitude = null;
        try {
       
            // BUILD REQUEST
            $request = $this->client->get('geocode', ['query' => [
                'address' => $address,
                'key' => $this->api_key,
            ]]);

            // EXTRACT DATA
            $data = json_decode($request->getBody(), true);
            if (isset($data['status']) && $data['status'] == 'OK') {
                $location = $data['results'][0]['geometry']['location'];
                $longitude = $location['lng'];
                $latitude = $location['lat'];
            }

        } 
        catch (\Exception $e) {
            throw new GeocodingException($e->getMessage(), 0, $e);
        }

        // compile result
        $result = new \stdClass;
        $result->latitude = $latitude;
        $result->longitude = $longitude;
        
        return $result;
    }
}
