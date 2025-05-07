<?php
/**
 * Utilitaires pour les tests PHPUnit
 */

namespace Tests;

/**
 * Classe utilitaire pour les tests
 */
class TestUtils
{
    /**
     * Appelle une méthode privée ou protégée sur un objet
     * 
     * @param object $object Objet sur lequel appeler la méthode
     * @param string $methodName Nom de la méthode à appeler
     * @param array $parameters Paramètres à passer à la méthode
     * @return mixed Résultat de l'appel de méthode
     */
    public static function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Définit une propriété privée ou protégée sur un objet
     * 
     * @param object $object Objet sur lequel définir la propriété
     * @param string $propertyName Nom de la propriété à définir
     * @param mixed $value Valeur à assigner
     * @return void
     */
    public static function setProperty($object, $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Obtient la valeur d'une propriété privée ou protégée sur un objet
     * 
     * @param object $object Objet depuis lequel obtenir la propriété
     * @param string $propertyName Nom de la propriété à obtenir
     * @return mixed Valeur de la propriété
     */
    public static function getProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * Crée un mock avec des méthodes retournant des valeurs spécifiées
     * 
     * @param string $className Nom de la classe à mocker
     * @param array $methods Tableau associatif de méthodes et leurs valeurs de retour
     * @param array $constructorArgs Arguments du constructeur ou null si aucun
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createMockWithMethods($className, array $methods = [], array $constructorArgs = null)
    {
        $mockBuilder = \PHPUnit\Framework\TestCase::getMockBuilder($className);
        
        if ($constructorArgs !== null) {
            $mockBuilder->setConstructorArgs($constructorArgs);
        } else {
            $mockBuilder->disableOriginalConstructor();
        }
        
        $mock = $mockBuilder->getMock();
        
        foreach ($methods as $methodName => $returnValue) {
            $mock->method($methodName)->willReturn($returnValue);
        }
        
        return $mock;
    }
} 