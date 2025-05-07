<?php

namespace Tests\Unit\DataAccess\Sql\Repository;

use App\Core\Database;
use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\Sql\Entity\User;
use App\DataAccess\Sql\Repository\UserRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private $mockPdo;
    private $mockDb;
    private $userRepository;
    private $mockPdoStatement;

    protected function setUp(): void
    {
        $this->mockPdoStatement = $this->createMock(PDOStatement::class);
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockDb = $this->createMock(Database::class);
        $this->mockDb->method('getMysqlConnection')->willReturn($this->mockPdo);
        
        $this->userRepository = new UserRepository($this->mockDb);
    }

    public function testFindById()
    {
        // Configuration du mock
        $this->mockPdoStatement->method('execute')->willReturn(true);
        $this->mockPdoStatement->method('fetch')->willReturn([
            'id' => 1,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => 'hashed_password',
            'phone' => '0612345678',
            'role' => 'ROLE_USER',
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ]);
        
        $this->mockPdo->method('prepare')->willReturn($this->mockPdoStatement);
        
        // Test
        $user = $this->userRepository->findById(1);
        
        // Assertions
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('John', $user->getFirstName());
    }

    public function testCreate()
    {
        // Configuration du mock
        $this->mockPdoStatement->method('execute')->willReturn(true);
        $this->mockPdo->method('prepare')->willReturn($this->mockPdoStatement);
        $this->mockPdo->method('lastInsertId')->willReturn('5');
        
        // Création d'un utilisateur
        $user = new User();
        $user->setEmail('new@example.com')
            ->setFirstName('Jane')
            ->setLastName('Smith')
            ->setPassword('hashed_new_password')
            ->setPhone('0698765432')
            ->setRole('ROLE_USER');
        
        // Test
        $id = $this->userRepository->create($user);
        
        // Assertions
        $this->assertEquals(5, $id);
    }

    public function testUpdate()
    {
        // Configuration du mock
        $this->mockPdoStatement->method('execute')->willReturn(true);
        $this->mockPdoStatement->method('rowCount')->willReturn(1);
        $this->mockPdo->method('prepare')->willReturn($this->mockPdoStatement);
        
        // Création d'un utilisateur pour mise à jour
        $user = new User();
        $user->setId(1)
            ->setEmail('updated@example.com')
            ->setFirstName('Updated')
            ->setLastName('User')
            ->setPassword('hashed_updated_password')
            ->setPhone('0611223344')
            ->setRole('ROLE_USER');
        
        // Test
        $result = $this->userRepository->update($user);
        
        // Assertions
        $this->assertTrue($result);
    }

    public function testDelete()
    {
        // Configuration du mock
        $this->mockPdoStatement->method('execute')->willReturn(true);
        $this->mockPdoStatement->method('rowCount')->willReturn(1);
        $this->mockPdo->method('prepare')->willReturn($this->mockPdoStatement);
        
        // Test
        $result = $this->userRepository->delete(1);
        
        // Assertions
        $this->assertTrue($result);
    }

    public function testFindByEmail()
    {
        // Configuration du mock
        $this->mockPdoStatement->method('execute')->willReturn(true);
        $this->mockPdoStatement->method('fetch')->willReturn([
            'id' => 2,
            'email' => 'specific@example.com',
            'first_name' => 'Specific',
            'last_name' => 'User',
            'password' => 'hashed_specific_password',
            'phone' => '0678901234',
            'role' => 'ROLE_USER',
            'created_at' => '2023-01-02 00:00:00',
            'updated_at' => '2023-01-02 00:00:00'
        ]);
        
        $this->mockPdo->method('prepare')->willReturn($this->mockPdoStatement);
        
        // Test
        $user = $this->userRepository->findByEmail('specific@example.com');
        
        // Assertions
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(2, $user->getId());
        $this->assertEquals('specific@example.com', $user->getEmail());
    }
    
    public function testDatabaseException()
    {
        // Configuration du mock pour simuler une erreur
        $this->mockPdo->method('prepare')->willThrowException(new \PDOException('Database error'));
        
        // Test avec assertion d'exception
        $this->expectException(DataAccessException::class);
        $this->userRepository->findById(999);
    }
} 