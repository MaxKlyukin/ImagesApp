<?php

namespace App\Controllers;

use App\Repositories\ImageRepository;
use App\Services\ImageHelper;
use MongoId;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImagesController
{

    /** @var ImageRepository */
    private $repository;

    private $imagesWebDir;

    function __construct(ImageRepository $repository, $imagesWebDir)
    {
        $this->repository = $repository;
        $this->imagesWebDir = $imagesWebDir;
    }

    public function listAction()
    {
        $images = $this->repository->findAll();

        $imagesResponse = [];
        foreach ($images as $image) {
            $imagesResponse[] = $this->prepareImageResponse($image);
        }

        return new JsonResponse($imagesResponse, Response::HTTP_OK);
    }

    public function retrieveAction($id)
    {
        $image = $this->repository->findById($id);
        if (!$image) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->prepareImageResponse($image), Response::HTTP_OK);
    }

    public function createAction(Application $app, Request $request)
    {
        /** @var ImageHelper $imageHelper */
        $imageHelper = $app['images.helper'];

        $uploadedFile = $request->files->get('image');

        $author = $this->prepareAuthor($request->request->get('author'));
        $tags = $this->prepareTags($request->request->get('tags'));

        if (
            !$imageHelper->isValid($uploadedFile)
            || !$author
        ) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $imageFile = $imageHelper->save($uploadedFile);

        $image = $this->repository->create($imageFile->getFilename(), $author, $tags);

        /** @var MongoId $id */
        $id = $image['_id'];
        $idString = $id->__toString();

        return new JsonResponse(
            null, Response::HTTP_CREATED,
            ['Location' => "/images/{$idString}"]
        );
    }

    public function likeAction($id)
    {
        $image = $this->repository->findById($id);
        if (!$image) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
        $this->repository->addLike($id);

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function removeAction(Application $app, $id)
    {
        /** @var ImageHelper $imageHelper */
        $imageHelper = $app['images.helper'];

        $image = $this->repository->findById($id);
        if (!$image) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $imageHelper->remove($image['name']);
        $this->repository->removeById($id);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function prepareImageResponse($row)
    {
        /** @var MongoId $id */
        $id = $row['_id'];

        return [
            '_id' => $id->__toString(),
            'author' => $row['author'],
            'image' => $this->imagesWebDir . $row['name'],
            'tags' => $row['tags'],
            'likes' => $row['likes'],
        ];
    }

    private function prepareAuthor($author)
    {
        if (!is_string($author)) {
            return $author;
        }
        $author = trim($author);
        if (empty($author)) {
            return null;
        }

        return $author;
    }

    private function prepareTags($tags)
    {
        if (!is_array($tags)) {
            return [];
        }
        $tags = array_values($tags);
        foreach ($tags as $key => $tag) {
            if (!is_string($tag)) {
                unset($tags[$key]);
            }
            $tags[$key] = trim($tag);
        }

        return $tags;
    }
}