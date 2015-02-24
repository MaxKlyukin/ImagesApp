<?php

namespace App\Repositories;

use MongoCollection;
use MongoCursor;
use MongoDate;
use MongoDB;
use MongoId;

class ImageRepository
{

    /** @var MongoCollection */
    private $collection;

    function __construct(MongoDB $db)
    {
        $this->collection = $db->selectCollection('images');
    }

    /**
     * @return MongoCursor
     */
    public function findAll()
    {
        return $this->collection->find()->sort(['createdAt' => -1]);
    }

    /**
     * @param $id
     * @return array|null
     */
    public function findById($id)
    {
        if (!MongoId::isValid($id)) {
            return null;
        }

        return $this->collection->findOne(['_id' => new MongoId($id)]);
    }

    public function create($imageName, $author, $tags)
    {
        $id = new MongoId();
        $imageDocument = [
            '_id' => $id,
            'name' => $imageName,
            'author' => $author,
            'tags' => $tags,
            'likes' => 0,
            'createdAt' => new MongoDate()
        ];
        $this->collection->insert($imageDocument, ['fsync' => true]);

        return $imageDocument;
    }

    public function addLike($id)
    {
        $this->collection->update(
            ['_id' => new MongoId($id)],
            ['$inc' => ['likes' => 1],]
        );
    }

    public function removeById($id)
    {
        $this->collection->remove(['_id' => new MongoId($id)]);
    }
}