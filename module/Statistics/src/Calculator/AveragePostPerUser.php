<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

/**
 * Class AveragePostPerUser - Average number of posts per user per month
 *
 * @package Statistics\Calculator
 *
 * @author OPMat
 */
class AveragePostPerUser extends AbstractCalculator
{

    protected const UNITS = 'posts';

    /**
     * @var array
     */
    private $userPostsTotal = [];
    
    /**
     * @param SocialPostTo $postTo
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $authorId = $postTo->getAuthorId();

        $this->userPostsTotal[$authorId] = ($this->userPostsTotal[$authorId] ?? 0) + 1;
    }

    /**
     * @return StatisticsTo
     */
    protected function doCalculate(): StatisticsTo
    {
        $nUsers = count($this->userPostsTotal);
        $returnValue = $nUsers > 0
            ? array_sum($this->userPostsTotal) / $nUsers
            : 0;
        
        return (new StatisticsTo())->setValue(round($returnValue,2));
    }
}
