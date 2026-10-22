<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GenreApiController extends Controller
{
    /** GET /api/v1/generos  -  generos con el numero de libros publicados. */
    public function index(): AnonymousResourceCollection
    {
        return GenreResource::collection(
            Genre::withCount('publishedBooks')->orderBy('name')->get()
        );
    }
}
