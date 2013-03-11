<?php
namespace Hydrant;

class Connection
{
    /**
     * @var \MongoClient
     */
    protected static $mongoClient;

    /**
     * @var string
     */
    protected static $defaultDatabaseName;

    /**
     * @param \MongoClient $mongoClient
     */
    public static function setMongoClient(\MongoClient $mongoClient)
    {
        self::$mongoClient = $mongoClient;
    }

    /**
     * @return \MongoClient
     */
    public static function getMongoClient()
    {
        return self::$mongoClient;
    }

    /**
     * @param $defaultDatabaseName
     */
    public static function setDefaultDatabaseName($defaultDatabaseName)
    {
        self::$defaultDatabaseName = $defaultDatabaseName;
    }

    /**
     * @param null $databaseName
     * @return \MongoDB
     */
    public static function getDatabase($databaseName = null)
    {
        $databaseName = $databaseName ?: self::$defaultDatabaseName;
        return self::$mongoClient->$databaseName;
    }

    /**
     * @param $collectionName
     * @param null $databaseName
     * @return \MongoCollection
     */
    public static function getCollection($collectionName, $databaseName = null)
    {
        $databaseName = $databaseName ?: self::$defaultDatabaseName;
        return self::$mongoClient->$databaseName->$collectionName;
    }
}