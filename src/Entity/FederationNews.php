<?php

declare(strict_types=1);

namespace FrankProjects\UltimateWarfare\Entity;

/**
 * FederationNews
 */
class FederationNews
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var string
     */
    private $news;

    /**
     * @var Federation
     */
    private $federation;

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param int $timestamp
     */
    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp
     *
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Set news
     *
     * @param string $news
     */
    public function setNews(string $news): void
    {
        $this->news = $news;
    }

    /**
     * Get news
     *
     * @return string
     */
    public function getNews(): string
    {
        return $this->news;
    }

    /**
     * @return Federation
     */
    public function getFederation(): Federation
    {
        return $this->federation;
    }

    /**
     * @param Federation $federation
     */
    public function setFederation(Federation $federation): void
    {
        $this->federation = $federation;
    }

    /**
     * @param Federation $federation
     * @param string $news
     * @return FederationNews
     */
    public static function createForFederation(Federation $federation, string $news): FederationNews
    {
        $federationNews = new FederationNews();
        $federationNews->setFederation($federation);
        $federationNews->setNews($news);
        $federationNews->setTimestamp(time());

        return $federationNews;
    }
}
