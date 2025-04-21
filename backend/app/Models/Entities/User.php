<?php

namespace App\Models\Entities;

/**
 * Classe représentant un utilisateur dans le système EcoRide
 */
class User
{
    private ?int $id = null;
    private string $lastName;
    private string $firstName;
    private string $email;
    private string $password;
    private ?string $phone = null;
    private ?int $addressId = null;
    private ?string $birthDate = null;
    private ?string $photoPath = null;
    private ?string $nickname = null;
    private string $creationDate;
    private ?string $lastConnection = null;
    private array $roles = [];
    
    /**
     * Constructeur avec les champs obligatoires seulement
     *
     * @param string $lastName Nom de famille
     * @param string $firstName Prénom
     * @param string $email Adresse email
     * @param string $password Mot de passe (déjà hashé)
     */
    public function __construct(string $lastName, string $firstName, string $email, string $password)
    {
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->password = $password;
        $this->creationDate = date('Y-m-d H:i:s');
    }
    
    /**
     * Valide les données de l'utilisateur avant persistance
     *
     * @return array Tableau d'erreurs de validation (vide si aucune erreur)
     */
    public function validate(): array
    {
        $errors = [];
        
        if (empty($this->lastName)) {
            $errors['lastName'] = 'Le nom est obligatoire';
        }
        
        if (empty($this->firstName)) {
            $errors['firstName'] = 'Le prénom est obligatoire';
        }
        
        if (empty($this->email)) {
            $errors['email'] = 'L\'email est obligatoire';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        }
        
        if (empty($this->password)) {
            $errors['password'] = 'Le mot de passe est obligatoire';
        }
        
        if ($this->phone !== null && !preg_match('/^[0-9+\s()-]{8,20}$/', $this->phone)) {
            $errors['phone'] = 'Le format du téléphone n\'est pas valide';
        }
        
        if ($this->birthDate !== null) {
            $date = \DateTime::createFromFormat('Y-m-d', $this->birthDate);
            if (!$date || $date->format('Y-m-d') !== $this->birthDate) {
                $errors['birthDate'] = 'La date de naissance n\'est pas valide';
            }
        }
        
        return $errors;
    }
    
    // Getters & Setters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function getLastName(): string
    {
        return $this->lastName;
    }
    
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }
    
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
    
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }
    
    public function getPassword(): string
    {
        return $this->password;
    }
    
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }
    
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
    
    public function getAddressId(): ?int
    {
        return $this->addressId;
    }
    
    public function setAddressId(?int $addressId): self
    {
        $this->addressId = $addressId;
        return $this;
    }
    
    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }
    
    public function setBirthDate(?string $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }
    
    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }
    
    public function setPhotoPath(?string $photoPath): self
    {
        $this->photoPath = $photoPath;
        return $this;
    }
    
    public function getNickname(): ?string
    {
        return $this->nickname;
    }
    
    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }
    
    public function getCreationDate(): string
    {
        return $this->creationDate;
    }
    
    public function setCreationDate(string $creationDate): self
    {
        $this->creationDate = $creationDate;
        return $this;
    }
    
    public function getLastConnection(): ?string
    {
        return $this->lastConnection;
    }
    
    public function setLastConnection(?string $lastConnection): self
    {
        $this->lastConnection = $lastConnection;
        return $this;
    }
    
    public function getRoles(): array
    {
        return $this->roles;
    }
    
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }
    
    public function addRole(int $roleId): self
    {
        if (!in_array($roleId, $this->roles)) {
            $this->roles[] = $roleId;
        }
        return $this;
    }
    
    public function removeRole(int $roleId): self
    {
        $this->roles = array_filter($this->roles, function($id) use ($roleId) {
            return $id !== $roleId;
        });
        return $this;
    }
    
    public function hasRole(int $roleId): bool
    {
        return in_array($roleId, $this->roles);
    }
    
    /**
     * Crée une instance d'utilisateur à partir d'un tableau de données
     *
     * @param array $data Données de l'utilisateur
     * @return User
     */
    public static function fromArray(array $data): self
    {
        $user = new self(
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['mot_passe'] ?? ''
        );
        
        if (isset($data['utilisateur_id'])) {
            $user->setId((int)$data['utilisateur_id']);
        }
        
        if (isset($data['telephone'])) {
            $user->setPhone($data['telephone']);
        }
        
        if (isset($data['adresse_id'])) {
            $user->setAddressId((int)$data['adresse_id']);
        }
        
        if (isset($data['date_naissance'])) {
            $user->setBirthDate($data['date_naissance']);
        }
        
        if (isset($data['photo_path'])) {
            $user->setPhotoPath($data['photo_path']);
        }
        
        if (isset($data['pseudo'])) {
            $user->setNickname($data['pseudo']);
        }
        
        if (isset($data['date_creation'])) {
            $user->setCreationDate($data['date_creation']);
        }
        
        if (isset($data['derniere_connexion'])) {
            $user->setLastConnection($data['derniere_connexion']);
        }
        
        return $user;
    }
    
    /**
     * Convertit l'utilisateur en tableau pour la persistance
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'nom' => $this->lastName,
            'prenom' => $this->firstName,
            'email' => $this->email,
            'mot_passe' => $this->password,
            'date_creation' => $this->creationDate
        ];
        
        if ($this->id !== null) {
            $data['utilisateur_id'] = $this->id;
        }
        
        if ($this->phone !== null) {
            $data['telephone'] = $this->phone;
        }
        
        if ($this->addressId !== null) {
            $data['adresse_id'] = $this->addressId;
        }
        
        if ($this->birthDate !== null) {
            $data['date_naissance'] = $this->birthDate;
        }
        
        if ($this->photoPath !== null) {
            $data['photo_path'] = $this->photoPath;
        }
        
        if ($this->nickname !== null) {
            $data['pseudo'] = $this->nickname;
        }
        
        if ($this->lastConnection !== null) {
            $data['derniere_connexion'] = $this->lastConnection;
        }
        
        return $data;
    }
} 