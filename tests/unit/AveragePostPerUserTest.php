<?php

declare(strict_types = 1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use DateTime;
use Statistics\Enum\StatsEnum;
use SocialPost\Hydrator\FictionalPostHydrator;
use Statistics\Service\Factory\StatisticsServiceFactory;
use Statistics\Extractor\StatisticsToExtractor;
use Statistics\Dto\ParamsTo;

/**
 * Class AveragePostPerUserTest
 *
 * @package Tests\unit
 */
class AveragePostPerUserTest extends TestCase
{
    private const STAT_LABELS = [
        StatsEnum::TOTAL_POSTS_PER_WEEK         => 
            'Total posts split by week',
        StatsEnum::AVERAGE_POST_NUMBER_PER_USER => 
            'Average number of posts per user in a given month',
        StatsEnum::AVERAGE_POST_LENGTH          => 
            'Average character length/post in a given month',
        StatsEnum::MAX_POST_LENGTH              => 
            'Longest post by character length in a given month',
    ];

    /**
     * @var array
     */
    private $posts;

    /**
     * @var array
     */
    private $hydratedPosts;

    /**
     * @var array
     */
    private $params;

    protected function setUp(): void
    {
        $data = file_get_contents("./tests/data/social-posts-response.json");
        $respArr = json_decode($data, TRUE);
        $this->posts = $respArr['data']['posts'] ?? null;
        $this->hydratedPosts = $this->hydrate($this->posts);

        $month = "August, 2018";
        $date  = DateTime::createFromFormat('F, Y', $month);

        if (false === $date) {
            $date = new DateTime();
        }

        $startDate = (clone $date)->modify('first day of this month');
        $endDate   = (clone $date)->modify('last day of this month');
        $this->params = [
            (new ParamsTo())
                ->setStatName(StatsEnum::AVERAGE_POST_NUMBER_PER_USER)
                ->setStartDate($startDate)
                ->setEndDate($endDate),
        ];
    }

    /**
     * @param array $posts
     */
    private function hydrate(array $posts) 
    {
        $hydrator = new FictionalPostHydrator();
        foreach ($posts as $postData) {
            yield $hydrator->hydrate($postData);
        }
    }

    /**
     * @test
     */
    public function testAveragePostLength(): void
    {
        try {
            $statFactory = new StatisticsServiceFactory();
            $statsService = $statFactory->create();
            $stats = $statsService->calculateStats($this->hydratedPosts,
                    $this->params);

            $extractor = new StatisticsToExtractor();
            $statResponse = $extractor->extract($stats, self::STAT_LABELS);
            
            foreach($statResponse["children"] as $stat):
                if ($stat["name"] === "average-posts-per-user") {
                    $this->assertEquals(1, $stat["value"]);
                    break;
                }
            endforeach;
        } catch (\Throwable $throwable) {
            http_response_code(500);

            $statResponse = ['message' => 'An error occurred'];
        }
    }
}
