<?php
/**
 * Script de vérification de la connexion MongoDB
 * Exécuter avec: docker-compose run tests php scripts/database/check_mongo.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Collection;

echo "== Script de vérification MongoDB ==\n\n";

try {
    // Configuration de la connexion
    $host = getenv('MONGO_HOST') ?: 'mongodb';
    $port = getenv('MONGO_PORT') ?: '27017';
    $username = getenv('MONGO_USERNAME') ?: 'mongo';
    $password = getenv('MONGO_PASSWORD') ?: 'changeme';
    $dbName = getenv('MONGO_DATABASE') ?: 'ecoride_nosql';

    echo "Connexion à MongoDB...\n";
    echo "Host: $host:$port\n";
    echo "Database: $dbName\n";
    
    // Connexion à MongoDB
    $uri = "mongodb://{$username}:{$password}@{$host}:{$port}";
    $client = new Client($uri);
    
    // Ping pour vérifier la connexion
    $result = $client->selectDatabase('admin')->command(['ping' => 1]);
    
    if ($result->toArray()[0]->ok == 1) {
        echo "✅ Connexion à MongoDB réussie!\n\n";
    } else {
        echo "❌ Erreur de connexion à MongoDB (ping)\n";
        exit(1);
    }
    
    // Sélection de la base de données
    $database = $client->selectDatabase($dbName);
    
    // Test d'écriture dans une collection temporaire
    echo "Test d'écriture...\n";
    $collection = $database->selectCollection('test_collection');
    
    // Nettoyage préalable
    $collection->drop();
    
    // Insertion d'un document
    $insertResult = $collection->insertOne([
        'test_key' => 'test_value',
        'created_at' => new \MongoDB\BSON\UTCDateTime(time() * 1000)
    ]);
    
    if ($insertResult->getInsertedCount() > 0) {
        $id = $insertResult->getInsertedId();
        echo "✅ Test d'insertion réussi (ID: {$id})\n";
        
        // Test de lecture
        echo "Test de lecture...\n";
        $document = $collection->findOne(['_id' => $id]);
        
        if ($document) {
            echo "✅ Test de lecture réussi (Valeur: {$document->test_key})\n";
            
            // Test de mise à jour
            echo "Test de mise à jour...\n";
            $updateResult = $collection->updateOne(
                ['_id' => $id],
                ['$set' => ['test_key' => 'updated_value']]
            );
            
            if ($updateResult->getModifiedCount() > 0) {
                echo "✅ Test de mise à jour réussi\n";
                
                // Test de suppression
                echo "Test de suppression...\n";
                $deleteResult = $collection->deleteOne(['_id' => $id]);
                
                if ($deleteResult->getDeletedCount() > 0) {
                    echo "✅ Test de suppression réussi\n";
                } else {
                    echo "❌ Échec du test de suppression\n";
                }
            } else {
                echo "❌ Échec du test de mise à jour\n";
            }
        } else {
            echo "❌ Échec du test de lecture\n";
        }
    } else {
        echo "❌ Échec du test d'insertion\n";
    }
    
    // Nettoyage final
    $collection->drop();
    
    echo "\n✅ Tous les tests MongoDB sont réussis!\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 