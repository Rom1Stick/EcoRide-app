<?php

/**
 * Définition des routes de l'API EcoRide
 */

// Instance du routeur
$router = $app->getRouter();

// Routes publiques
$router->get('/api', 'HomeController@index');
$router->get('/api/health', 'HomeController@health');

// Routes d'authentification
$router->post('/api/auth/register', 'AuthController@register');
$router->post('/api/auth/login', 'AuthController@login');
$router->post('/api/auth/refresh', 'AuthController@refresh')->middleware('auth');
$router->post('/api/auth/logout', 'AuthController@logout')->middleware('auth');

// Endpoint de confirmation de compte via jeton
$router->get('/api/auth/confirm', 'AuthController@confirm');

// Routes des trajets
$router->get('/api/rides', 'RideController@index');
$router->get('/api/rides/{id}', 'RideController@show');
$router->post('/api/rides', 'RideController@store')->middleware('auth');
$router->put('/api/rides/{id}', 'RideController@update')->middleware('auth');
$router->delete('/api/rides/{id}', 'RideController@destroy')->middleware('auth');

// Routes de recherche
$router->get('/api/rides/search', 'SearchController@search');

// Routes des utilisateurs
$router->get('/api/users/me', 'UserController@me')->middleware('auth');
$router->put('/api/users/me', 'UserController@update')->middleware('auth');

// Routes des réservations
$router->get('/api/bookings', 'BookingController@index')->middleware('auth');
$router->post('/api/rides/{id}/book', 'BookingController@store')->middleware('auth');
$router->delete('/api/bookings/{id}', 'BookingController@destroy')->middleware('auth'); 