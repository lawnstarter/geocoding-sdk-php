<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\json_encode;
use Lawnstarter\Geocoding\Client;

class ClientTest extends Orchestra\Testbench\TestCase
{

    protected function getGeocodeResponse($status, $lat, $lng)
    {
        return json_encode([
            'status' => $status,
            'results' => [
                [
                    'geometry' => [
                        'location' => [
                            'lat' => $lat,
                            'lng' => $lng,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_geocode_returns_latitude_and_longitude()
    {
        // mocks
        $responseMock = Mockery::mock(Response::class);
        $responseMock
            ->shouldReceive('getBody')
            ->once()
            ->andReturn($this->getGeocodeResponse('OK', 1, 2));
        $guzzleClientMock = Mockery::mock(GuzzleClient::class);
        $guzzleClientMock
            ->shouldReceive('get')
            ->withArgs(function ($endpoint, $details) {
                return $endpoint === 'geocode'
                    && $details['query']['address'] === '1234 Rainbow Road'
                    && $details['query']['key'] === 'test_google_api_key';
            })
            ->once()
            ->andReturn($responseMock);

        // execute
        $client = new Client('test_google_api_key', $guzzleClientMock);
        $result = $client->geocode('1234 Rainbow Road');

        // assert
        $this->assertNotNull($result);
        $this->assertEquals(1, $result->lat);
        $this->assertEquals(2, $result->lng);
    }

    public function test_geocode_returns_null_if_geocoding_fails()
    {
        // mocks
        $responseMock = Mockery::mock(Response::class);
        $responseMock
            ->shouldReceive('getBody')
            ->once()
            ->andReturn($this->getGeocodeResponse('NO_RESULTS', 1, 2));
        $guzzleClientMock = Mockery::mock(GuzzleClient::class);
        $guzzleClientMock
            ->shouldReceive('get')
            ->withArgs(function ($endpoint, $details) {
                return $endpoint === 'geocode'
                    && $details['query']['address'] === '1234 Rainbow Road'
                    && $details['query']['key'] === 'test_google_api_key';
            })
            ->once()
            ->andReturn($responseMock);

        // execute
        $client = new Client('test_google_api_key', $guzzleClientMock);
        $result = $client->geocode('1234 Rainbow Road');

        // assert
        $this->assertNull($result);
    }

    /**
     *  @expectedException \Lawnstarter\Geocoding\GeocodingException
     */
    public function test_geocode_throws_exception_when_unexpected_exception_occurs()
    {
        // mocks
        $guzzleClientMock = Mockery::mock(GuzzleClient::class);
        $guzzleClientMock
            ->shouldReceive('get')
            ->withArgs(function ($endpoint, $details) {
                return $endpoint === 'geocode'
                    && $details['query']['address'] === '1234 Rainbow Road'
                    && $details['query']['key'] === 'test_google_api_key';
            })
            ->once()
            ->andThrow(new \Exception('test exception'));

        // execute
        $client = new Client('test_google_api_key', $guzzleClientMock);
        $result = $client->geocode('1234 Rainbow Road');
    }
}
