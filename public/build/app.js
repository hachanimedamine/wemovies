let selectedGenres = []; // Store selected genres globally
let currentSearchQuery = ''; // Store the current search query

$(document).ready(function () {
    const autocompleteResults = $('.autocomplete-results');
    let typingTimer;
    const typingInterval = 300; // Delay for typing to limit requests

    getMoviesByGenre(1); // Load the first page with default genre filters (none selected)

    // Update selected genres when a checkbox is changed
    $(document).on('change', '.genreCheckbox', function () {
        selectedGenres = $('.genreCheckbox:checked').map(function () {
            return parseInt($(this).val());
        }).get();
        getMoviesByGenre(1, currentSearchQuery); // Reset to page 1 when a new genre is selected
    });

    // Function to get movies by genre with pagination
    function getMoviesByGenre(page = 1, query = '') {
        $.ajax({
            url: "/filterByGenre",
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ selectedGenres, page, query }),
            success: function (response) {
                updateMovieList(response.movies); // Update movie list
                updatePagination(response.currentPage, response.totalPages, query); // Update pagination with query
            },
            error: function () {
                console.error("An error occurred while filtering movies by genre.");
            }
        });
    }

    // Update movie list in HTML directly
    function updateMovieList(movies) {
        const movieList = $('.movies-list');
        movieList.empty(); // Clear the current list
        if (movies.length === 0) {
            movieList.html('<p>No movies found.</p>');
            return;
        }
        movies.forEach(movie => {
            const movieHtml = `
                <div class="movie-card d-flex">
                    <img src="https://image.tmdb.org/t/p/w300/${movie.poster_path}" alt="${movie.title}" class="movie-img">
                    <div class="movie-info">
                        <h5>${movie.title}</h5>
                        <p class="overview">${movie.overview}</p>
                        <p class="star-rating">
                            ${movie.vote_average.toFixed(1)}
                            ${[...Array(5)].map((_, i) => `<i class="fa fa-star${i < movie.vote_average / 2 ? '' : '-o'}"></i>`).join('')}
                            <small>(${movie.vote_count} votes)</small>
                        </p>
                        <a href="#" 
                           class="btn btn-primary btn-details detailsFilm"
                           movie-name="${movie.title}" 
                           movie-rate="${movie.vote_average.toFixed(1)}"
                           movie-video-key="${movie.video_key}"
                           movie-desc="${movie.overview}"
                           movie-count="${movie.vote_count}"
                           movie-id="${movie.id}">
                           Lire le détails
                        </a>
                    </div>
                </div>
            `;
            movieList.append(movieHtml);
        });
    }

    function updatePagination(currentPage, totalPages, query = '') {
        const pagination = $('.pagination');
        pagination.empty();

        if (currentPage > 1) {
            pagination.append(`<a href="#" class="btn btn-secondary pagination-link" data-page="${currentPage - 1}" data-query="${query}">Previous</a>`);
        }
        pagination.append(`<span class="mx-3">Page ${currentPage} of ${totalPages}</span>`);
        if (currentPage < totalPages) {
            pagination.append(`<a href="#" class="btn btn-secondary pagination-link" data-page="${currentPage + 1}" data-query="${query}">Next</a>`);
        }
    }

    $(document).on('click', '.pagination-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        const query = $(this).data('query') || '';

        if (query) {
            searchMovies(query, page); // Perform search with pagination if query is present
        } else {
            getMoviesByGenre(page); // Use genre filter otherwise
        }
    });

    function showMovieDetails() {
        $(document).on('click', '.detailsFilm', function (e) {
            e.preventDefault();

            // Retrieve movie attributes from data attributes
            const name = $(this).attr('movie-name');
            const rate = $(this).attr('movie-rate');
            const videoKey = $(this).attr('movie-video-key');
            const videoUrl = `https://www.youtube.com/embed/${videoKey}`;
            console.log(videoKey, "videoKey", videoUrl, "videoUrl");

            const desc = $(this).attr('movie-desc');
            const count = $(this).attr('movie-count');
            const id = parseInt($(this).attr('movie-id'));

            // Populate modal with movie details
            $('#iframeVideo').attr('src', videoUrl).attr('title', name);
            $('#videoModalLabel').text(name);
            $('#movieDescription').text(desc);
            $('#movieRating').text(rate);
            $('#userCount').text(`pour ${count} utilisateurs`);
            $('#submitRating').data('movie-id', $(this).attr('movie-id'));
            $('#detailsModal').modal('show');
            
        });

        // Clear video URL when modal is closed
        $('#detailsModal').on('hidden.bs.modal', function () {
            $('#iframeVideo').attr('src', '');
        });
    }

    // Initialize movie details display
    showMovieDetails();

    // Show autocomplete suggestions as user types
    $('.movieSearchInput').on('input', function () {
        clearTimeout(typingTimer);
        const query = $(this).val().trim();

        if (query.length > 0) {
            typingTimer = setTimeout(() => {
                $.ajax({
                    url: `/autocomplete?query=${encodeURIComponent(query)}`,
                    type: 'GET',
                    success: function (suggestions) {
                        autocompleteResults.empty();
                        if (Array.isArray(suggestions) && suggestions.length) {
                            suggestions.forEach(title => {
                                autocompleteResults.append(`<div class="autocomplete-item">${title}</div>`);
                            });
                            autocompleteResults.show();
                        } else {
                            autocompleteResults.hide();
                        }
                    },
                    error: function () {
                        console.error("Error during autocomplete.");
                    }
                });
            }, typingInterval);
        } else {
            autocompleteResults.hide();
            getMoviesByGenre(1); // If search query is empty, reset to genre-based filtering
        }
    });

    // Click on an autocomplete item to display only that movie
    $(document).on('click', '.autocomplete-item', function () {
        const selectedTitle = $(this).text();
        $('.movieSearchInput').val(selectedTitle);
        autocompleteResults.hide();
        currentSearchQuery = selectedTitle; // Update the search query globally
        searchMovies(currentSearchQuery);
    });

    // Perform search when Enter key is pressed
    $('.movieSearchInput').on('keypress', function (e) {
        if (e.which === 13) { // Enter key code
            e.preventDefault();
            const query = $(this).val().trim();
            currentSearchQuery = query; // Update the global search query
            searchMovies(query);
            autocompleteResults.hide();
        }
    });

    // Search button click event
    $('.movieSearchInputButton').on('click', function (e) {
        e.preventDefault();
        const query = $('.movieSearchInput').val().trim();
        currentSearchQuery = query; // Update the global search query
        searchMovies(query);
        autocompleteResults.hide();
    });

    // Function to perform search and update movie list
    function searchMovies(query, page = 1) {
        const selectedGenres = $('.genreCheckbox:checked').map(function () {
            return parseInt($(this).val());
        }).get();

        $.ajax({
            url: '/search-movie',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ query: query, selectedGenres: selectedGenres, page: page }),
            success: function (response) {
                if (response.results && response.results.length > 0) {
                    updateMovieList(response.results);
                    updatePagination(page, response.totalPages, query); // Update pagination for the search
                } else {
                    $('.movies-list').html('<p>No movies found.</p>');
                }
            },
            error: function () {
                $('.movies-list').html('<p>Error fetching movies.</p>');
            }
        });
    }

    let selectedRating = 0; // Store the user's rating
    let movieId = null; // Store the movie ID for the API call

    $('.rate input').on('change', function () {
        selectedRating = parseFloat($(this).val());
        console.log("Selected rating:", selectedRating);
    });

    // Submit the rating when the "Submit Rating" button is clicked
    $('#submitRating').on('click', function () {
        // Récupérer l'ID du film à partir de l'attribut data-movie-id du bouton
        const movieId = $(this).data('movie-id');
        
        if (!movieId) {
            alert("Movie ID is not set.");
            return;
        }
    
        if (selectedRating === 0) {
            alert("Please select a rating.");
            return;
        }
    
        const apiUrl = `/rate-movie/${movieId}`; // Utiliser l'ID du film dans l'URL
    
        $.ajax({
            url: apiUrl,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ rating: selectedRating * 2 }), // TMDB rating is sur une échelle de 10
            success: function (response) {
                if (response.success) {
                    alert("Rating submitted successfully!");
                    $('#detailsModal').modal('hide'); // Ferme la modale
                }
            },
            error: function (xhr, status, error) {
                console.error("Error submitting rating:", error);
                alert("There was an error submitting your rating.");
            }
        });
    });
    
    // Clear video URL and reset rating when modal is closed
    $('#detailsModal').on('hidden.bs.modal', function () {
        $('#iframeVideo').attr('src', '');
        selectedRating = 0; // Reset the rating
        $('.rate input').prop('checked', false); // Reset the stars
    });
});
