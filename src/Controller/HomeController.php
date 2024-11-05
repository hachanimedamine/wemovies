<?php

namespace App\Controller;

use App\Service\MovieHtmlGenerator;
use App\Service\TmdbApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home/{page}', name: 'home', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function index(TmdbApiService $tmdbApiService, int $page = 1): Response
    {
        try {
            $bestMovie = $tmdbApiService->getTopRatedMovie();
            $allGenres = $tmdbApiService->getGenres();
            $allMovies = $tmdbApiService->getPopularMovies($page);
          
            $bestMovieVideoKey = $tmdbApiService->getFirstYouTubeVideoKey($bestMovie['id'] ?? 0);
          
            foreach ($allMovies as &$movie) {
                $movie['video_key'] = $tmdbApiService->getFirstYouTubeVideoKey($movie['id']);
              
            }
    
            return $this->render('home/index.html.twig', [
                'movies' => $allMovies,
                'genres' => $allGenres,
                'bestMovie' => $bestMovie,
                'bestMovieVideoKey' => $bestMovieVideoKey,
                'currentPage' => $page,
            ]);
    
        } catch (\Exception $e) {
            return new Response('Erreur lors de la récupération des données : ' . $e->getMessage(), 500);
        }
    }
    

    #[Route('/filterByGenre', name: 'filterByGenre', methods: ['POST'])]
    public function filterByGenre(Request $request, TmdbApiService $tmdbApiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $genres = $data['selectedGenres'] ?? [];
        $page = $data['page'] ?? 1;
    
        try {
            // Fetch movies based on selected genres
            $movies = $tmdbApiService->filterMoviesByGenres($genres, $page);
            foreach ($movies['results'] as &$movie) {
                $movie['video_key'] = $tmdbApiService->getFirstYouTubeVideoKey($movie['id']);
            }
        return new JsonResponse([
            'movies' => $movies['results'],
            'currentPage' => $movies['page'],

            'totalPages' => $movies['total_pages']
        ], 200);
    
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des films : ' . $e->getMessage()], 500);
        }
    }
    
   
  

    #[Route('/autocomplete', name: 'autocomplete', methods: ['GET'])]
public function autocomplete(Request $request, TmdbApiService $tmdbApiService): JsonResponse
{
    $query = $request->query->get('query', '');

    if (empty($query)) {
        return new JsonResponse([]);
    }

    try {
        $results = $tmdbApiService->searchMoviesByTitle($query);
        // Map to extract only titles
        $suggestions = array_map(fn($movie) => $movie['title'], $results);

        return new JsonResponse($suggestions);

    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Erreur lors de l\'auto-complétion : ' . $e->getMessage()], 500);
    }
}

    #[Route('/search-movie', name: 'search_movie', methods: ['POST'])]
    public function searchMovie(Request $request, TmdbApiService $tmdbApiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $query = $data['query'] ?? '';
        $genres = $data['selectedGenres'] ?? [];
        $page = $data['page'] ?? 1;

        try {
            // Effectuer la recherche de films en fonction des genres et du titre avec pagination
            $results = $tmdbApiService->searchMovies($genres, $query, $page);

            // Récupérer la clé vidéo pour chaque film dans les résultats
            foreach ($results['results'] as &$movie) {
                $movie['video_key'] = $tmdbApiService->getFirstYouTubeVideoKey($movie['id']);
            }

            return new JsonResponse([
                'results' => $results['results'],
                'currentPage' => $results['page'],
                'totalPages' => $results['total_pages']
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la recherche du film : ' . $e->getMessage()], 500);
        }
    }
    #[Route('/rate-movie/{movieId}', name: 'rate_movie', methods: ['POST'])]
    public function rateMovie(int $movieId, Request $request, TmdbApiService $tmdbApiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $rating = $data['rating'] ?? null;

        if (!$rating ) {
            return new JsonResponse(['error' => 'Invalid data provided.'], 400);
        }

        try {
            $response = $tmdbApiService->rateMovie($movieId, $rating);
            return new JsonResponse($response);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

}