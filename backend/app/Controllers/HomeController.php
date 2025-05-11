<?php

namespace App\Controllers;

class HomeController extends Controller
{
    /**
     * Point d'entrée de l'API
     * @return array
     */
    public function index(): array
    {
        return $this->success([ 'message' => 'Bienvenue dans l\'API EcoRide' ]);
    }

    /**
     * Vérifie l'état de santé de l'application
     * @return array
     */
    public function health(): array
    {
        return $this->success([ 'status' => 'ok' ]);
    }
} 