<?php

namespace Tests\Unit\Repositories\SQL;

use App\Core\Database\SqlConnection;
use App\Core\Exceptions\ValidationException;
use App\Models\Entities\User;
use App\Repositories\SQL\UserRepository;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class UserRepositoryTest extends TestCase
{
    private $mockPdo;
    private $mockSqlConnection;
    private $mockStatement;
    private $userRepository;
    
    protected function setUp(): void
    {
        // Créer des mocks pour PDO, SqlConnection et PDOStatement
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockSqlConnection = $this->createMock(SqlConnection::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        
        // Configurer le mock de SqlConnection pour renvoyer le mock de PDO
        $this->mockSqlConnection->method('getPdo')
            ->willReturn($this->mockPdo);
        
        // Créer une réflexion sur la classe SqlConnection pour injecter notre mock
        $reflectionClass = new \ReflectionClass(SqlConnection::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $this->mockSqlConnection);
        
        // Créer l'instance du repository à tester
        $this->userRepository = new UserRepository();
    }
    
    protected function tearDown(): void
    {
        // Réinitialiser l'instance de SqlConnection
        $reflectionClass = new \ReflectionClass(SqlConnection::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }
    
    public function testFindByIdReturnsUserWhenFound(): void
    {
        // Configurer le comportement du mock PDO pour préparer la requête
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM Utilisateur'))
            ->willReturn($this->mockStatement);
        
        // Configurer le comportement du mock PDOStatement
        $this->mockStatement->expects($this->once())
            ->method('bindParam')
            ->with(':id', 1, PDO::PARAM_INT);
        
        $this->mockStatement->expects($this->once())
            ->method('execute');
        
        // Simuler un utilisateur trouvé
        $userData = [
            'utilisateur_id' => 1,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'mot_passe' => 'hashed_password',
            'telephone' => '0123456789',
            'date_creation' => '2023-01-01 12:00:00'
        ];
        
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);
        
        // Configurer le mock pour getUserRoles
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT role_id FROM Possede'))
            ->willReturn($this->mockStatement);
        
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false); // Pas de rôles
        
        // Exécuter la méthode à tester
        $user = $this->userRepository->findById(1);
        
        // Vérifier le résultat
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('Dupont', $user->getLastName());
        $this->assertEquals('Jean', $user->getFirstName());
        $this->assertEquals('jean.dupont@example.com', $user->getEmail());
    }
    
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        // Configurer le comportement du mock PDO
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
        
        // Simuler aucun utilisateur trouvé
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        
        // Exécuter la méthode à tester
        $user = $this->userRepository->findById(999);
        
        // Vérifier le résultat
        $this->assertNull($user);
    }
    
    public function testCreateThrowsValidationExceptionWhenInvalidData(): void
    {
        // Créer un utilisateur sans email (invalid)
        $user = new User('Dupont', 'Jean', '', 'password');
        
        // Vérifier que la méthode create lance une ValidationException
        $this->expectException(ValidationException::class);
        
        // Exécuter la méthode à tester
        $this->userRepository->create($user);
    }
    
    public function testCreateReturnsIdWhenSuccessful(): void
    {
        // Créer un utilisateur valide
        $user = new User('Dupont', 'Jean', 'jean.dupont@example.com', 'password');
        
        // Configurer le comportement du mock PDO
        $this->mockPdo->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);
        
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
        
        $this->mockStatement->expects($this->exactly(11))
            ->method('bindValue');
        
        $this->mockStatement->expects($this->once())
            ->method('execute');
        
        // Simuler l'insertion réussie avec ID 1
        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('1');
        
        $this->mockPdo->expects($this->once())
            ->method('commit');
        
        // Exécuter la méthode à tester
        $id = $this->userRepository->create($user);
        
        // Vérifier le résultat
        $this->assertEquals(1, $id);
    }
    
    // D'autres tests pour update, delete, etc.
} 