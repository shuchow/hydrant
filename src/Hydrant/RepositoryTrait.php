<?php
namespace Hydrant;

trait RepositoryTrait
{

    protected static $collectionName;

    public static function setCollectionName($collectionName)
    {
        self::$collectionName = $collectionName;
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