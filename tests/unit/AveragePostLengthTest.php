<?php

declare(strict_types = 1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use DateTime;
use Statistics\Builder\ParamsBuilder;
use Statistics\Enum\StatsEnum;
use SocialPost\Hydrator\FictionalPostHydrator;
use Statistics\Service\Factory\StatisticsServiceFactory;
use Statistics\Extractor\StatisticsToExtractor;
use Statistics\Dto\ParamsTo;

/**
 * Class AveragePostLengthTest
 *
 * @package Tests\unit
 */
class AveragePostPerUserTest extends TestCase
{
    private const STAT_LABELS = [
        StatsEnum::TOTAL_POSTS_PER_WEEK         => 'Total posts split by week',
        StatsEnum::AVERAGE_POST_NUMBER_PER_USER => 'Average number of posts per user in a given month',
        StatsEnum::AVERAGE_POST_LENGTH          => 'Average character length/post in a given month',
        StatsEnum::MAX_POST_LENGTH              => 'Longest post by character length in a given month',
    ];

    /**
     * @var Array
     */
    private $posts;

    /**
     * @var Array
     */
    private $hydratedPosts;

    /**
     * @var Array
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
                ->setStatName(StatsEnum::AVERAGE_POST_LENGTH)
                ->setStartDate($startDate)
                ->setEndDate($endDate),
        ];
    }

    private function hydrate($posts) 
    {
        $hydrator = new FictionalPostHydrator();
        foreach ($posts as $postData) {
            yield $hydrator->hydrate($postData);
        }
    }

    /**
     * @test
     */
    public function testAveragePostPerUser(): void
    {
        try {
            $statFactory = new StatisticsServiceFactory();
            $statsService = $statFactory->create();
            $stats = $statsService->calculateStats($this->hydratedPosts, $this->params);

            $extractor = new StatisticsToExtractor();
            $statResponse = $extractor->extract($stats, self::STAT_LABELS);
            
            foreach($statResponse["children"] as $stat):
                if ($stat["name"] === "average-character-length") {
                    $this->assertEquals(495.25, $stat["value"]);
                    break;
                }
            endforeach;
        } catch (\Throwable $throwable) {
            http_response_code(500);

            $statResponse = ['message' => 'An error occurred'];
        }
    }
}
