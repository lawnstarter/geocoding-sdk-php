<?php

declare(strict_types=1);

namespace Tests\Lawnstarter\Geocoding;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Lawnstarter\Geocoding\Client;
use Lawnstarter\Geocoding\GeocodingException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function getGeocodeResponse(string $status, float $lat, float $lng): string
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

    #[Test]
    public function it_sets_default_timeout_of_10_seconds(): void
    {
        $client = new Client('test_google_api_key');
        $guzzleClient = $client->getGuzzleClient();

        $this->assertEquals(10, $guzzleClient->getConfig('timeout'));
    }

    #[Test]
    public function it_returns_lat_lng_on_successful_geocode(): void
    {
        $expectedJsonString = $this->getGeocodeResponse('OK', 1.0, 2.0);
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $expectedJsonString);
        fseek($resource, 0);

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('getBody')
            ->once()
            ->andReturn(new Stream($resource));

        $guzzleClientMock = Mockery::mock(GuzzleClient::class);
        $guzzleClientMock->shouldReceive('get')
            ->once()
            ->withArgs(function ($endpoint, $details) {
                return $endpoint === 'geocode/json'
                    && $details['query']['address'] === '1234 Rainbow Road'
                    && $details['query']['key'] === 'test_google_api_key';
            })
            ->andReturn($responseMock);

        $client = new Client('test_google_api_key', $guzzleClientMock);
        $result = $client->geocode('1234 Rainbow Road');

        $this->assertNotNull($result);
        $this->assertEquals(1.0, $result->lat);
        $this->assertEquals(2.0, $result->lng);
        $this->assertObjectHasProperty('data', $result);
        $this->assertNotNull($result->data);
        $this->assertEquals($this->getGeocodeResponse('OK', 1.0, 2.0), json_encode($result->data));
    }

    #[Test]
    public function it_returns_null_if_geocoding_fails(): void
    {
        $expectedJsonString = $this->getGeocodeResponse('ZERO_RESULTS', 0.0, 0.0);
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $expectedJsonString);
        fseek($resource, 0);

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('getBody')
            ->once()
            ->andReturn(new Stream($resource));

        $guzzleClientMock = Mockery::mock(GuzzleClient::class);
        $guzzleClientMock->shouldReceive('get')
            ->once()
            ->withArgs(function ($endpoint, $details) {
                return $endpoint === 'geocode/json'
                    && $details['query']['address'] === '1234 Rainbow Road'
                    && $details['query']['key'] === 'test_google_api_key';
            })
            ->andReturn($responseMock);

        $client = new Client('test_google_api_key', $guzzleClientMock);
        $result = $client->geocode('1234 Rainbow Road');

        $this->assertNull($result);
    }

    #[Test]
    public function it_throws_a_geocoding_exception_on_error(): void
    {
        $this->expectException(GeocodingException::class);
        $this->expectExceptionMessage('test exception');

        $guzzleClientMock = Mockery::mock(GuzzleClient::class);
        $guzzleClientMock->shouldReceive('get')
            ->once()
            ->andThrow(new Exception('test exception'));

        $client = new Client('test_google_api_key', $guzzleClientMock);
        $client->geocode('1234 Rainbow Road');
    }
}
