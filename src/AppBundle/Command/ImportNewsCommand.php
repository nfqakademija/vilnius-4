<?php

namespace AppBundle\Command;

use AppBundle\Entity\Planet;
use AppBundle\Entity\Article;
use AppBundle\Entity\PlanetArticle;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use AppBundle\Repository\PlanetRepository;
use AppBundle\Repository\ArticleRepository;
use AppBundle\Repository\PlanetArticleRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ImportNewsCommand
 * @package Fuksai\src\AppBundle\Command
 */
class ImportNewsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:import:news')
            ->setDescription('Import astronomy news.')
            ->setHelp('This command finds and imports astronomy news in the website.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $astronomyNews = $this->getArticles();

        $planetsNames = $this->getPlanetsNames();

        $this->createNewArticles($astronomyNews, $planetsNames);

        $output->writeln('All astronomy news were inserted!');
    }

    /**
     * @return array
     */
    private function getPlanetsNames()
    {
        $planets = $this->getContainer()
            ->get('doctrine')
            ->getRepository('AppBundle:Planet')
            ->createQueryBuilder('planet')
            ->select('planet.name')
            ->getQuery()
            ->execute();

        $planetsNames = [];

        foreach ($planets as $planet) {
            $planetsNames[] = $planet['name'];
        }

        return $planetsNames;
    }

    /**
     * @return array
     */
    private function getArticles()
    {
        $links = $this->getArticlesLinks();
        $images = $this->getArticlesImages();

        $articles = [];

        $i = 0;

        foreach ($links as $link) {
            $articles[] = $this->getArticle($link->getUri(), $images[$i++]);
        }

        return $articles;
    }

    /**
     * @return \Symfony\Component\DomCrawler\Link[]
     */
    private function getArticlesLinks()
    {
        // string url converted to html
        $html = file_get_contents('https://astronomynow.com/category/news/');

        $crawler = new Crawler($html, 'https');

        // array of the links to the astronomy articles
        $links = $crawler->filter('article > div > header > h3 > a')->links();

        return $links;
    }

    /**
     * @return \Symfony\Component\DomCrawler\Link[]
     */
    private function getArticlesImages()
    {
        // string url converted to html
        $html = file_get_contents('https://astronomynow.com/category/news/');

        $crawler = new Crawler($html, 'https');

        // array of the astronomy articles images
        $images = $crawler->filter('div.mh-loop-thumb > a > img')->each(function (Crawler $node) {
            return $node->attr('src');
        });

        return $images;
    }

    /**
     * @param string $link
     * @param string $image
     * @return array
     */
    private function getArticle(string $link, string $image)
    {
        // string url converted to html
        $html = file_get_contents($link);

        $article = [];

        $crawler = new Crawler($html, 'https');

        $article['url'] = $link;
        $article['urlToImage'] = $image;
        $article['title'] = $crawler->filter('header > h1')->text();
        $article['author'] = $crawler->filter('header > p > span > a.fn')->text();
        $article['publishDate'] = $crawler->filter('header > p > span > a')->text();
        $article['description'] = $this->getDescriptionWithoutImageCaptions($crawler);

        return $article;
    }

    /**
     * @param Crawler $crawler
     * @return string
     */
    private function getDescriptionWithoutImageCaptions(Crawler $crawler)
    {
        $articleDescription = $crawler->filter('div.entry-content')->text();

        $imageCaptions = $crawler->filter('figcaption')->each(function (Crawler $node) {
            return $node->text();
        });
        // remove image captions from the article description
        foreach ($imageCaptions as $imageCaption) {
            $articleDescription = str_replace($imageCaption, "", $articleDescription);
        }
        return $articleDescription;
    }

    /**
     * @param $astronomyNews
     * @param $planetsNames
     */
    private function createNewArticles(array $astronomyNews, array $planetsNames)
    {
        // go through all got astronomical news, check if article exists in DB and create one if it does not exist
        foreach ($astronomyNews as $astronomyArticle) {
            if (!$this->checkArticleExistence($astronomyArticle)) {
                $newArticle = $this->createArticle($astronomyArticle, $planetsNames);
                $this->insertNewArticleToDB($newArticle);
            }
        }
    }

    /**
     * @param array $article
     * @param array $planetsNames
     * @return Article
     */
    private function createArticle(array $article, array $planetsNames)
    {
        $newArticle = new Article();

        $newArticle->setAuthor($article['author']);
        $newArticle->setTitle($article['title']);
        $newArticle->setDescription($article['description']);
        $newArticle->setUrl($article['url']);
        $newArticle->setUrlToImage($article['urlToImage']);
        $newArticle->setPublishStringDate($article['publishDate']);

        // go through all planet names and check if found planet name in title or description
        // then set found planet name to new article
        foreach ($planetsNames as $planetName) {
            if (preg_match('/\b'.$planetName.'\b/i', $article[''.
                'title']) || preg_match('/\b'. $planetName .
                    '\b/i', $article['description'])) {
                $newArticle->setPlanet($planetName);
            }
        }
        return $newArticle;
    }

    /**
     * @param Article $newArticle
     */
    private function insertNewArticleToDB(Article $newArticle)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($newArticle);
        $em->flush();
    }

    /**
     * @param array $newArticle
     * @return bool
     */
    private function checkArticleExistence(array $newArticle)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // article got by article title
        $oldArticle = $em->getRepository('AppBundle:Article')
            ->findOneBy(
                array(
                    'title' => $newArticle['title'],
                )
            );

        if (!empty($oldArticle)) {
            return true;
        }

        return false;
    }
}
