<?php

namespace App\Controllers\Refactored;

use App\Controllers\Controller;
use App\Core\Http\Response;
use App\Domain\Services\UserService;
use App\Domain\Exceptions\UnauthorizedException;
use App\Domain\ValueObjects\Email;

/**
 * Contrôleur V2 pour la gestion des utilisateurs
 * Utilise l'architecture orientée objet avec injection de dépendances
 */
class UserControllerV2 extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Profil de l'utilisateur connecté
     */
    public function me(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $profile = $this->userService->getUserProfile($userId);
            
            return $this->jsonSuccess($profile);
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération du profil utilisateur', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Mise à jour du profil utilisateur
     */
    public function updateProfile(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $data = $this->getJsonData();
            
            // Validation des données
            $errors = $this->validateProfileData($data);
            if (!empty($errors)) {
                return $this->jsonError('Données invalides', 400, ['errors' => $errors]);
            }
            
            $updatedProfile = $this->userService->updateUserProfile($userId, $data);
            
            return $this->jsonSuccess($updatedProfile, 'Profil mis à jour avec succès');
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la mise à jour du profil', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Ajouter un rôle à l'utilisateur
     */
    public function addRole(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $data = $this->getJsonData();
            
            if (empty($data['role'])) {
                return $this->jsonError('Le rôle est requis', 400);
            }
            
            $result = $this->userService->addRole($userId, $data['role']);
            
            return $this->jsonSuccess($result, 'Rôle ajouté avec succès');
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de l\'ajout du rôle', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Demande d'ajout de rôle (ex: devenir conducteur)
     */
    public function requestRole(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $data = $this->getJsonData();
            
            if (empty($data['role'])) {
                return $this->jsonError('Le rôle demandé est requis', 400);
            }
            
            if (empty($data['reason'])) {
                return $this->jsonError('La raison de la demande est requise', 400);
            }
            
            // Pour l'instant, on simule une demande en attente
            $request = [
                'userId' => $userId,
                'role' => $data['role'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'requestedAt' => date('Y-m-d H:i:s')
            ];
            
            return $this->jsonSuccess($request, 'Demande de rôle soumise avec succès');
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la demande de rôle', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Statistiques de l'utilisateur
     */
    public function stats(): Response
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $stats = $this->userService->getUserStats($userId);
            
            return $this->jsonSuccess($stats);
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération des statistiques', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Profil d'un utilisateur spécifique (public)
     */
    public function show(int $userId): Response
    {
        try {
            $profile = $this->userService->getUserProfile($userId);
            
            // Masquer les informations sensibles pour un profil public
            $publicProfile = [
                'id' => $profile['id'],
                'name' => $profile['name'],
                'username' => $profile['username'],
                'photoPath' => $profile['photoPath'],
                'rating' => $profile['rating'],
                'totalRides' => $profile['totalRides'],
                'joinedAt' => $profile['joinedAt']
            ];
            
            return $this->jsonSuccess($publicProfile);
            
        } catch (UnauthorizedException $e) {
            return $this->jsonError('Utilisateur non trouvé', 404);
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la récupération du profil public', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Recherche d'utilisateurs (pour admins ou fonctionnalités spécifiques)
     */
    public function search(): Response
    {
        try {
            $query = $_GET['q'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 20), 50); // Max 50 résultats
            
            if (strlen($query) < 2) {
                return $this->jsonError('La recherche doit contenir au moins 2 caractères', 400);
            }
            
            // Pour l'instant, simulation de la recherche - à implémenter dans UserService
            $users = [];
            
            // Masquer les informations sensibles
            $publicUsers = array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'photoPath' => $user['photoPath'],
                    'rating' => $user['rating']
                ];
            }, $users);
            
            return $this->jsonSuccess([
                'users' => $publicUsers,
                'count' => count($publicUsers),
                'query' => $query
            ]);
            
        } catch (\Exception $e) {
            $this->logError('Erreur lors de la recherche d\'utilisateurs', $e);
            return $this->jsonError('Erreur interne du serveur', 500);
        }
    }

    /**
     * Extrait le token d'authentification de la requête
     */
    private function extractTokenFromRequest(): ?string
    {
        // Vérifier d'abord le cookie
        $token = $_COOKIE['auth_token'] ?? null;
        
        // Puis l'en-tête Authorization
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        return $token;
    }

    /**
     * Récupère l'ID de l'utilisateur authentifié
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = (int)($_SERVER['AUTH_USER_ID'] ?? 0);
        if (!$userId) {
            throw new UnauthorizedException('Utilisateur non authentifié');
        }
        return $userId;
    }

    /**
     * Valide les données de profil
     */
    private function validateProfileData(array $data): array
    {
        $errors = [];
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format d\'email invalide';
        }
        
        if (isset($data['username']) && (strlen($data['username']) < 3 || strlen($data['username']) > 50)) {
            $errors[] = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères';
        }
        
        if (isset($data['photo_path']) && !empty($data['photo_path']) && !filter_var($data['photo_path'], FILTER_VALIDATE_URL)) {
            // Vérifier si c'est un chemin relatif valide
            if (!preg_match('/^\/[a-zA-Z0-9\/_.-]+\.(jpg|jpeg|png|gif|svg)$/i', $data['photo_path'])) {
                $errors[] = 'Le chemin de la photo doit être une URL valide ou un chemin relatif vers une image';
            }
        }
        
        return $errors;
    }

    /**
     * Log une erreur avec contexte
     */
    private function logError(string $message, \Exception $e): void
    {
        error_log(sprintf(
            '[%s] %s: %s in %s:%d',
            date('Y-m-d H:i:s'),
            $message,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    /**
     * Retourne une réponse JSON de succès
     */
    private function jsonSuccess($data = null, string $message = 'Opération réussie'): Response
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return (new Response())
            ->header('Content-Type', 'application/json')
            ->status(200)
            ->content(json_encode($response));
    }

    /**
     * Retourne une réponse JSON d'erreur
     */
    private function jsonError(string $message, int $code = 400, array $extra = []): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code
        ];
        
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }
        
        return (new Response())
            ->header('Content-Type', 'application/json')
            ->status($code)
            ->content(json_encode($response));
    }

    /**
     * Récupère les données JSON de la requête
     */
    protected function getJsonData(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?: [];
    }
} 