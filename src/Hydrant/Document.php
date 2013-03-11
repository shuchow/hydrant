<?php
namespace Hydrant;

class Document
{
    protected $isEmebdded = false;
    protected $isManaged = false;
    protected $data;
    protected $persistenceType;

    protected static $collectionName = 'default';

    public function __construct($data = [])
    {
        $this->data = $data;
        $this->setPersistenceType($data['_class']);
        unset($this->data['_class']);
    }

    public function __get($property)
    {
        return $this->data[$property];
    }

    public function __set($property, $value)
    {
        $this->data[$property] = $value;
    }

    public function setManaged($managed)
    {
        $this->isManaged = $managed;
    }

    public function setPersistenceType($persistenceType = null)
    {
        $this->persistenceType = $persistenceType ?: get_class($this);
    }

    public function getPersistenceType()
    {
        return $this->persistenceType;
    }

    public function getStorage()
    {
        return $this->data;
    }

    public function save()
    {
        $mongoCollection = Connection::getCollection(static::$collectionName);
        if ($this->isManaged) {
            $data = $this->data;
            $this->fixPersistance($data);
            $mongoCollection->update(
                ['_id' => $this->_id],
                $data
            );
        } else {
            if (!$this->_id) {
                $this->_id = new \MongoId();
            }
            $data = $this->data;
            $this->fixPersistance($data);
            $mongoCollection->insert($data);
        }
    }

    private function fixPersistance(&$data)
    {
        foreach ($data as $key => &$val) {
            if (is_array($val)) {
                if (array_values($val) !== $val) {
                    $val['_class'] = 'array';
                }
            } else if ($val instanceof \Hydrant\Document) {
                $storage = $val->getStorage();
                $storage['_class'] = $val->getPersistenceType();
                $val = $storage;
            }
            foreach ($val as $key => &$val) {
                if (is_array($val)) {
                    $this->fixPersistance($val);
                }
            }
        }
    }

    public function delete()
    {
        Connection::getCollection(static::$collectionName)->remove(['_id' => $this->_id]);
    }

    public static function hydrate($data = [], $isEmbedded = false)
    {
        if (!$data){
            return null;
        }

        $data["_class"] = $data["_class"] ?: get_called_class();
        foreach ($data as $key => &$val) {
            if (is_array($val)) {
                if (array_values($val) !== $val) {
                    $val = self::hydrate($val, true);
                }
            }
        }

        if ($data['_class'] === 'array') {
            unset($data['_class']);
            return $data;
        } else {
            return (new $data['_class']($data));
        }
    }

    public static function setCollectionName($collectionName)
    {
        self::$collectionName = $collectionName;
    }

    public static function getCollection()
    {
        return Connection::getCollection(static::$collectionName);
    }

    public static function find($id)
    {
        if (ctype_xdigit($id)) {
            if (strlen($id) == 24) {
                $id = new \MongoId($id);
            }
        }
        return self::hydrate(self::getCollection()->findOne(['_id' => $id]));
    }

    public static function findMany(array $ids)
    {
        foreach ($ids as &$id) {
            if (ctype_xdigit($id)) {
                if (strlen($id) == 24) {
                    $id = new \MongoId($id);
                }
            }
        }
        return new Collection(self::getCollection()->find(['_id' => ['$in' => $ids]]));
    }

    public static function findAll()
    {
        return new Collection(self::getCollection()->find()->sort(['_id' => 1]));
    }

    public static function search($fieldQueryPair) {
        //Make this a wildcard search if it isn't already.

        foreach($fieldQueryPair as $key => $value) {
            if (substr($value, 0, 1) != '/' && substr($value, -1) != '/') {
                $fieldQueryPair[$key] = new \MongoRegex('/.*' . $value . '.*/i');
            }
        }

        return new Collection(self::getCollection()->find($fieldQueryPair));
    }
}