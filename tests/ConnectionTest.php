<?php

use Hydrant\Connection;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testGetMongoClient()
    {
        $m = new MongoClient;
        Connection::setMongoClient($m);
        $this->assertInstanceOf("MongoClient", Connection::getMongoClient());
    }

    public function testGetDatabase()
    {
        $m = new MongoClient;
        Connection::setMongoClient($m);

        $database = Connection::getDatabase('test');
        $this->assertInstanceOf("MongoDB", $database);
        $this->assertEquals("test", "$database");

        Connection::setDefaultDatabaseName('test1');
        $database = Connection::getDatabase();
        $this->assertInstanceOf("MongoDB", $database);
        $this->assertEquals("test1", "$database");
    }

    public function testGetCollection()
    {
        $m = new MongoClient;
        Connection::setMongoClient($m);

        $collection = Connection::getCollection('test');
        $this->assertInstanceOf("MongoCollection", $collection);
        $this->assertEquals('test', $collection->getName());
    }
}