<?php

use Hydrant\Collection,
    Hydrant\Connection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    protected $records;
    protected $cursor;

    public function setup()
    {
        $m = new MongoClient;
        $m->dropDB('test');
        Connection::setMongoClient($m);
        Connection::setDefaultDatabaseName('test');

        for ($c = 0; $c < 5; $c++) {
            $data = ["test$c" => "$c", "_id" => new MongoId, "_class" => 'Hydrant\Document'];
            $this->records[] = $data;
            $m->test->default->insert($data);
        }
        $this->cursor = $m->test->default->find();

    }

    public function tearDown()
    {
        Connection::getMongoClient()->dropDB('test');
    }

    public function testPersistObjects()
    {
        $collection = new Collection($this->cursor, true);

        // collect object hashes from first iterate / hydrate
        $hashes = [];
        foreach ($collection as $item) {
            $hashes[] = spl_object_hash($item);
        }

        //iterate the same collection again and we should get the same exact objects back
        $secondaryHashes = [];
        foreach ($collection as $item) {
            $secondaryHashes[] = spl_object_hash($item);
        }

        $this->assertEquals(count($hashes), count($secondaryHashes), 'got different number of items during second iteration of collection');
        for($c = 0; $c < count($hashes); $c++) {
            $this->assertEquals($hashes[$c], $secondaryHashes[$c], 'got different object hashes the second time around');
        }


        // now test that NOT persisting objects results in new objects each time iterated
        $collection = new Collection($this->cursor);
        // collect object hashes from first iterate / hydrate
        $hashes = [];
        foreach ($collection as $item) {
            $hashes[] = spl_object_hash($item);
        }

        //iterate the same collection again and we should get the NEW objects back
        $secondaryHashes = [];
        foreach ($collection as $item) {
            $secondaryHashes[] = spl_object_hash($item);
        }

        $this->assertEquals(count($hashes), count($secondaryHashes), 'got different number of items during second iteration of collection');
        for($c = 0; $c < count($hashes); $c++) {
            $this->assertNotEquals($hashes[$c], $secondaryHashes[$c], 'got same object hashes the second time around');
        }

    }

    public function testHydrate()
    {
        $collection = new Collection($this->cursor);
        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }

        for($c = 0; $c < count($items); $c++) {
            $item = $items[$c];

            // first, we should have the right kind of object
            $this->assertEquals('Hydrant\Document', get_class($item));

            // second, we should see the same values as the phactory-created records
            $sourceItem = $this->records[$c];
            foreach ($item->getStorage() as $key => $val) {
                $this->assertEquals($sourceItem[$key], $item->$key);
            }
        }
    }

    public function testIteration()
    {
        $collection = new Collection($this->cursor);

        // collect items from collection
        $items = [];
        foreach ($collection as $item) {
            $items[] = $item->getStorage();
        }

        // collect array data from raw mongo cursor
        $this->cursor->reset();
        $rawItems = [];
        foreach ($this->cursor as $item) {
            unset($item['_class']);
            $rawItems[] = $item;
        }

        $this->assertEquals(count($rawItems), count($items), 'cursor and collection returned a different number of items');
        for ($c = 0; $c < count($rawItems); $c++) {
            $this->assertEquals($rawItems[$c], $items[$c], 'found an item that different in content between cursor and collection');
        }
    }
}