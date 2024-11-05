<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TmdbApiService
{
    private Client $client;
    private SerializerInterface $serializer;

    public function __construct(ParameterBagInterface $params, SerializerInterface $serializer)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.themoviedb.org/3/',
            'headers' => [
                'Authorization' => 'Bearer ' . $params->get('TMDB_BEARER_TOKEN'),
                'Accept' => 'application/json',
            ]
        ]);
        $this->serializer = $serializer;
    }

    public function autocompleteSearch(string $query): array
    {
        $response = $this->client->request('GET', 'search/movie', [
            'query' => [
                'query' => $query,
                'include_adult' => 'false',
                'language' => 'en-US',

            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['results'] ?? [];
    }

    /**
     * Sérialise les données en JSON.
     *
     * @param mixed $data
     * @return string
     */
    public function serializeData($data): string
    {
        return $this->serializer->serialize($data, 'json');
    }

    public function getMovieVideos(int $movieId, string $language = 'en-US'): array
    {
        return $this->fetchData("movie/{$movieId}/videos", ['language' => $language])['results'] ?? [];
    }


    public function getFirstYouTubeVideoKey(int $movieId): ?string
    {
        $videos = $this->fetchData("movie/{$movieId}/videos", ['language' => 'en-US'])['results'] ?? [];
        foreach ($videos as $video) {
            if ($video['site'] === 'YouTube') {
                return $video['key'];
            }
        }
        return null;
    }

    private function fetchData(string $endpoint, array $queryParams = []): array
    {
        $response = $this->client->request('GET', $endpoint, ['query' => $queryParams]);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getTopRatedMovie(): array
    {
        return $this->fetchData('movie/top_rated', ['language' => 'fr-FR'])['results'][0] ?? [];
    }

    public function getGenres(): array
    {
        return $this->fetchData('genre/movie/list', ['language' => 'fr'])['genres'] ?? [];
    }

    public function getPopularMovies(int $page = 1): array
    {
        return $this->fetchData('discover/movie', [
            'page' => $page,
        ])['results'] ?? [];
    }

    public function searchMovies(?array $genres = null, ?string $nameField = null, int $page = 1): array
    {
        $queryParams = [
            'include_adult' => 'false',
            'include_video' => 'false',
            'language' => 'en-US',
            'sort_by' => 'popularity.desc',
            'page' => $page // Ajoutez la pagination
        ];

        // Appliquer les filtres de genre si fournis
        if (!empty($genres)) {
            $queryParams['with_genres'] = implode(',', $genres);
        }

        // Appliquer le filtre de nom/titre si fourni
        if (!empty($nameField)) {
            $endpoint = 'search/movie';
            $queryParams['query'] = $nameField;
        } else {
            $endpoint = 'discover/movie';
        }

        // Appel à l'API pour récupérer les données
        $response = $this->fetchData($endpoint, $queryParams);

        return [
            'results' => $response['results'] ?? [],
            'page' => $response['page'] ?? 1,
            'total_pages' => $response['total_pages'] ?? 1
        ];
    }
    public function searchMoviesByTitle(string $title): array
    {
        try {
            $response = $this->client->request('GET', 'search/movie', [
                'query' => [
                    'query' => $title,
                    'language' => 'fr-FR',
                    'include_adult' => 'false'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['results'] ?? [];
        } catch (\Exception $e) {
            // Log ou message d'erreur
            throw new \RuntimeException('Erreur lors de la recherche du film : ' . $e->getMessage());
        }
    }

    public function filterMoviesByGenres(array $genres, int $page = 1): array
    {
        return $this->fetchData('discover/movie', [
            'with_genres' => implode(',', $genres),
            'page' => $page,
        ]);
    }
    public function rateMovie(int $movieId, float $rating): array
    {
        try {
            $response = $this->client->request('POST', "movie/{$movieId}/rating", [
                'json' => [
                    'value' => $rating // Send the rating value
                ],
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'évaluation du film : ' . $e->getMessage());
        }
    }


}
