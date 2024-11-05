<?php

namespace App\Tests\Service;

use App\Service\TmdbApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TmdbApiServiceTest extends TestCase
{
    private $clientMock;
    private $paramsMock;
    private $serializerMock;
    private $tmdbService;

    protected function setUp(): void
    {
        // Créer des mocks pour le client HTTP, les paramètres et le sérialiseur
        $this->clientMock = $this->createMock(Client::class);
        $this->paramsMock = $this->createMock(ParameterBagInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        // Initialiser les paramètres
        $this->paramsMock->method('get')
            ->with('TMDB_BEARER_TOKEN')
            ->willReturn('votre_token');

        // Créer une instance de TmdbApiService avec les mocks
        $this->tmdbService = new TmdbApiService($this->paramsMock, $this->serializerMock);
    }

    public function testAutocompleteSearch()
    {
        $mockResponseData = ['results' => [['title' => 'Example Movie', 'id' => 1]]];
        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'search/movie')
            ->willReturn(new Response(200, [], json_encode($mockResponseData)));

        $this->tmdbService = new TmdbApiService($this->paramsMock, $this->serializerMock);
        $results = $this->tmdbService->autocompleteSearch('Example');

        $this->assertIsArray($results);
        $this->assertEquals('Example Movie', $results[0]['title']);
    }

    public function testRateMovie()
    {
        $movieId = 1;
        $rating = 8.5;
        $mockResponseData = ['status_code' => 1, 'status_message' => 'Success'];

        $this->clientMock->expects($this->once())
            ->method('request')
            ->with('POST', "movie/{$movieId}/rating")
            ->willReturn(new Response(200, [], json_encode($mockResponseData)));

        $response = $this->tmdbService->rateMovie($movieId, $rating);

        $this->assertIsArray($response);
        $this->assertEquals('Success', $response['status_message']);
    }
}
