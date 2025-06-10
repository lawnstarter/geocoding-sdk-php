<?php

namespace Lawnstarter\Geocoding;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use stdClass;

class Client
{
    private const BASE_URI = 'https://maps.googleapis.com/maps/api/';
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly string $apiKey,
        private readonly GuzzleClient $guzzleClient = new GuzzleClient([
            'base_uri' => self::BASE_URI,
            'timeout' => self::TIMEOUT_SECONDS,
        ])
    )
    {}

    public function getGuzzleClient(): GuzzleClient
    {
        return $this->guzzleClient;
    }

    /**
     * @throws GeocodingException
     */
    public function geocode(string $address): ?stdClass
    {
        try {
            $response = $this->guzzleClient->get('geocode/json', [
                'query' => [
                    'address' => $address,
                    'key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (($data['status'] ?? null) !== 'OK') {
                return null;
            }

            $location = $data['results'][0]['geometry']['location'];

            return (object)[
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'data' => $data,
            ];
        } catch (Exception $e) {
            throw new GeocodingException($e->getMessage(), previous: $e);
        }
    }
}
