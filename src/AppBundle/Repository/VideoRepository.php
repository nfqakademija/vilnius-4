<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Video;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * VideosRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VideoRepository extends EntityRepository
{
    /**
     * Saves videos to database
     *
     * @param Video $video
     */
    public function save(Video $video)
    {
        $em = $this->getEntityManager();
        $em->persist($video);
        $em->flush();
    }

    /**
     * Returns channels channel names that are being used to search videos in youtube
     *
     * @return array $channels
     */
    public function findAllChannels()
    {
        $channels = $this->createQueryBuilder('video')
            ->select('video.channelName')
            ->distinct()
            ->getQuery()
            ->execute();

        return $channels;
    }

    /**
     * Saves many videos to database
     *
     * @param array $videos
     */
    public function saveMany(array $videos)
    {
        $em = $this->getEntityManager();

        foreach ($videos as $video) {
            $em->persist($video);
        }

        $em->flush();
    }

    /**
     * Return all videos from database and passes values to paginate
     *
     * @param int $currentPage
     * @return Paginator
     */
    public function getAllVideos($currentPage = 1)
    {
        $videos = $this->createQueryBuilder('video')
            ->orderBy('video.id', 'DESC')
            ->getQuery();

        $paginator = $this->paginate($videos, $currentPage);

        return $paginator;
    }

    /**
     * Return all channel videos from database and passes values to paginate
     *
     * @param $currentPage
     * @param $channelName
     * @return Paginator
     */
    public function getAllChannelVideos($currentPage, $channelName)
    {
        $videos = $this->createQueryBuilder('video')
            ->where('video.channelName = :channelName')
            ->setParameter('channelName', $channelName)
            ->getQuery();

        $paginator = $this->paginate($videos, $currentPage);

        return $paginator;
    }

    /**
     * Return all videos by name from database and passes values to paginate
     *
     * @param $currentPage
     * @param $planetName
     * @return Paginator
     */
    public function getAllVideosByName($currentPage, $planetName)
    {
        $videos = $this->createQueryBuilder('video')
            ->where('video.keyName = :planetName')
            ->setParameter('planetName', $planetName)
            ->getQuery();

        $paginator = $this->paginate($videos, $currentPage);

        return $paginator;
    }

    /**
     * Creates pagination class and sets how much videos should be in one page
     *
     * @param $dql
     * @param int $page
     * @param int $limit
     * @return Paginator
     */
    public function paginate($dql, $page = 1, $limit = 6): Paginator
    {
        $paginator = new Paginator($dql);

        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }
}
