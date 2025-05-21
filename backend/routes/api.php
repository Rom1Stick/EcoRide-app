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
$router->get('/api/rides/my', 'RideController@getMyRides')->middleware('auth');
$router->get('/api/rides/{id}', 'RideController@show');
$router->post('/api/rides', 'RideController@store')->middleware('auth');
$router->put('/api/rides/{id}', 'RideController@update')->middleware('auth');
$router->delete('/api/rides/{id}', 'RideController@destroy')->middleware('auth');

// Routes de recherche (double route pour compatibilité)
$router->get('/api/rides/search', 'SearchController@search');
$router->get('/api/trips/search', 'SearchController@search');

// Routes des utilisateurs
$router->get('/api/users/me', 'UserController@me');
$router->post('/api/users/me/role-requests', 'UserController@requestRole')->middleware('auth');
$router->post('/api/users/add-role', 'UserController@addRole')->middleware('auth');
$router->put('/api/users/me', 'UserController@update')->middleware('auth');

// Routes des réservations
$router->get('/api/bookings', 'BookingController@index')->middleware('auth');
$router->post('/api/bookings/create', 'BookingController@create')->middleware('auth');
$router->post('/api/rides/{id}/book', 'BookingController@store')->middleware('auth');
$router->post('/api/rides/{id}/confirm', 'BookingController@confirm')->middleware('auth');
$router->delete('/api/bookings/{id}', 'BookingController@destroy')->middleware('auth');

// Routes des véhicules
$router->get('/api/vehicles', 'VehicleController@getUserVehicle')->middleware('auth');
$router->post('/api/vehicles', 'VehicleController@store')->middleware('auth');
$router->put('/api/vehicles/{id}', 'VehicleController@update')->middleware('auth');
$router->delete('/api/vehicles/{id}', 'VehicleController@destroy')->middleware('auth');

// Route des types d'énergie
$router->get('/api/energy-types', 'VehicleController@getEnergyTypes');

// Routes du système de crédits
$router->get('/api/credits/balance', 'CreditsController@balance')->middleware('auth');
$router->get('/api/credits/transactions', 'CreditsController@transactions')->middleware('auth');
$router->post('/api/credits/transfer', 'CreditsController@transfer')->middleware('auth');
$router->get('/api/credits/pricing', 'CreditsController@pricing');
$router->get('/api/admin/credits/alerts', 'CreditsController@alerts')->middleware('auth');

// Routes de recherche de lieux (autocomplétion)
$router->get('/api/locations/search', 'LocationController@search');
$router->get('/api/locations/popular', 'LocationController@getPopular');

// Routes d'administration (gestion des rôles et utilisateurs)
$router->get('/api/admin/users', 'AdminController@listUsers')->middleware('auth');
$router->post('/api/admin/users/{userId}/roles', 'AdminController@addUserRole')->middleware('auth');
$router->delete('/api/admin/users/{userId}/roles/{roleId}', 'AdminController@removeUserRole')->middleware('auth');
$router->get('/api/admin/roles', 'AdminController@listRoles')->middleware('auth');
$router->get('/api/admin/permissions', 'AdminController@listPermissions')->middleware('auth');

// Gestion des demandes de rôle
$router->get('/api/admin/role-requests', 'AdminController@listRoleRequests')->middleware('auth');
$router->post('/api/admin/role-requests/{requestId}/approve', 'AdminController@approveRoleRequest')->middleware('auth');
$router->post('/api/admin/role-requests/{requestId}/reject', 'AdminController@rejectRoleRequest')->middleware('auth');

// Routes supplémentaires
$router->get('/api/admin/users/pending', 'AdminController@listPendingUsers')->middleware('auth');
$router->post('/api/admin/users/{userId}/confirm', 'AdminController@confirmUser')->middleware('auth');

// Routes pour la suspension et réactivation de comptes
$router->post('/api/admin/users/{userId}/suspend', 'AdminController@suspendUser')->middleware('auth');
$router->post('/api/admin/users/{userId}/activate', 'AdminController@activateUser')->middleware('auth');

// Routes pour les statistiques
$router->get('/api/admin/stats/rides', 'AdminController@getRideStats')->middleware('auth');
$router->get('/api/admin/stats/credits', 'AdminController@getCreditStats')->middleware('auth'); 