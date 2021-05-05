<?php
set_time_limit(1000000);

class Raoptimizer
{
    protected $dataset_name;
    protected $parameters;
    protected $productivity_factor;
    protected $range_positions;

    function __construct($dataset_name, $parameters, $productivity_factor, $range_positions)
    {
        $this->dataset_name = $dataset_name;
        $this->parameters = $parameters;
        $this->productivity_factor = $productivity_factor;
        $this->range_positions = $range_positions;
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset_name);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        $column_indexes = [0, 1, 2, 3, 4, 5, 6];
        $columns = ['simpleUC', 'averageUC', 'complexUC', 'uaw', 'tcf', 'ecf', 'actualEffort'];
        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $column_indexes[$subkey]) {
                    $data[$key][$columns[$subkey]] = $data[$key][$subkey];
                    unset($data[$key][$subkey]);
                }
            }
        }
        return $data;
    }

    function minimalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(min($ae), $ae)];
    }

    function maximalAE($particles)
    {
        foreach ($particles as $val) {
            $ae[] = $val['ae'];
        }
        return $particles[array_search(max($ae), $ae)];
    }

    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Generate random Simple Use Case Complexity weight parameter
     * Min = 5,     xMinSimple = 4.5
     * Max = 7.49   xMaxSimple = 8.239
     */
    function randomSimpleUCWeight()
    {
        return mt_rand($this->range_positions['min_xSimple'] * 100, $this->range_positions['max_xSimple'] * 100) / 100;
    }

    /**
     * Generate random Average Use Case Complexity weight parameter
     * Min = 7.5    xMinAverage = 6.75
     * Max = 12.49  xMaxAverage = 13.739
     */
    function randomAverageUCWeight()
    {
        return mt_rand($this->range_positions['min_xAverage'] * 100, $this->range_positions['max_xAverage'] * 100) / 100;
    }

    /**
     * Generate random Complex Use Case Complexity weight parameter
     * Min = 12.5   xMinComplex = 11.25
     * Max = 15     xMaxComplex = 16.5
     */
    function randomComplexUCWeight()
    {
        return mt_rand($this->range_positions['min_xComplex'] * 100, $this->range_positions['max_xComplex'] * 100) / 100;
    }

    function randomEpsilon()
    {
        return mt_rand($this->range_positions['min_epsilon'] * 100, $this->range_positions['max_epsilon'] * 100) / 100;
    }

    function size($positions, $target_projects)
    {
        //$xSimple, $simpleUC, $xAverage, $averageUC, $xComplex, $complexUC, $uaw, $tcf, $ecf
        $ucSimple = $positions['xSimple'] * $target_projects['simpleUC'];
        $ucAverage = $positions['xAverage'] * $target_projects['averageUC'];
        $ucComplex = $positions['xComplex'] * $target_projects['complexUC'];

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $target_projects['uaw'] + $UUCW;
        return $UUCP * $target_projects['tcf'] * $target_projects['ecf'];
    }

    function attractiveness($r)
    {
        return $this->parameters['b'] * exp(-$this->parameters['g'] * pow($r, 2));
    }

    function movingFireflyXiToXj($Xi, $Xj, $r, $alpha)
    {
        $xSimple = $Xi['positions']['xSimple'] + $this->attractiveness($r) * ($Xj['positions']['xSimple'] - $Xi['positions']['xSimple']) + $alpha * $this->randomEpsilon();
        if ($xSimple < $this->range_positions['min_xSimple']) {
            $xSimple = $this->range_positions['min_xSimple'];
        }
        if ($xSimple > $this->range_positions['max_xSimple']) {
            $xSimple = $this->range_positions['max_xSimple'];
        }

        $xAverage = $Xi['positions']['xAverage'] + $this->attractiveness($r) * ($Xj['positions']['xAverage'] - $Xi['positions']['xAverage']) + $alpha * $this->randomEpsilon();
        $xAverage = $this->trimmingPositions($xAverage, 'xAverage');
        if ($xAverage < $this->range_positions['min_xAverage']) {
            $xAverage = $this->range_positions['min_xAverage'];
        }
        if ($xAverage > $this->range_positions['max_xAverage']) {
            $xAverage = $this->range_positions['max_xAverage'];
        }

        $xComplex = $Xi['positions']['xComplex'] + $this->attractiveness($r) * ($Xj['positions']['xComplex'] - $Xi['positions']['xComplex']) + $alpha * $this->randomEpsilon();
        $xComplex = $this->trimmingPositions($xComplex, 'xComplex');
        if ($xComplex < $this->range_positions['min_xComplex']) {
            $xComplex = $this->range_positions['min_xComplex'];
        }
        if ($xComplex > $this->range_positions['max_xComplex']) {
            $xComplex = $this->range_positions['max_xComplex'];
        }

        return ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
    }

    function movingFireflyXiRandomly($Xi, $alpha)
    {
        $xSimple = $Xi['positions']['xSimple'] + $alpha * $this->randomEpsilon();
        if ($xSimple < $this->range_positions['min_xSimple']) {
            $xSimple = $this->range_positions['min_xSimple'];
        }
        if ($xSimple > $this->range_positions['max_xSimple']) {
            $xSimple = $this->range_positions['max_xSimple'];
        }
        $xAverage = $Xi['positions']['xAverage'] + $alpha * $this->randomEpsilon();
        if ($xAverage < $this->range_positions['min_xAverage']) {
            $xAverage = $this->range_positions['min_xAverage'];
        }
        if ($xAverage > $this->range_positions['max_xAverage']) {
            $xAverage = $this->range_positions['max_xAverage'];
        }
        $xComplex = $Xi['positions']['xComplex'] + $alpha * $this->randomEpsilon();
        if ($xComplex < $this->range_positions['min_xComplex']) {
            $xComplex = $this->range_positions['min_xComplex'];
        }
        if ($xComplex > $this->range_positions['max_xComplex']) {
            $xComplex = $this->range_positions['max_xComplex'];
        }
        return ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
    }

    function gamma($upper_bound, $lower_bound)
    {
        return 1 / pow(($upper_bound - $lower_bound), 2);
    }

    function beta($r, $gamma)
    {
        return $this->parameters['b_min'] + ($this->parameters['b'] - $this->parameters['b_min']) * exp(-$gamma * pow($r, 2));
    }

    function movingFireflyXjToXk($Xj, $Xk, $r, $v, $alpha, $gamma)
    {
        $xSimple = $Xj['xSimple'] + $v['vSimple'] * exp(-$gamma['gSimple'] * pow($r, 2)) * ($Xk['xSimple'] - $Xj['xSimple']) + $alpha * $this->randomEpsilon();
        if ($xSimple < $this->range_positions['min_xSimple']) {
            $xSimple = $this->range_positions['min_xSimple'];
        }
        if ($xSimple > $this->range_positions['max_xSimple']) {
            $xSimple = $this->range_positions['max_xSimple'];
        }

        $xAverage = $Xj['xAverage'] + $v['vAverage'] * exp(-$gamma['gAverage'] * pow($r, 2)) * ($Xk['xAverage'] - $Xj['xAverage']) + $alpha * $this->randomEpsilon();
        if ($xAverage < $this->range_positions['min_xAverage']) {
            $xAverage = $this->range_positions['min_xAverage'];
        }
        if ($xAverage > $this->range_positions['max_xAverage']) {
            $xAverage = $this->range_positions['max_xAverage'];
        }

        $xComplex = $Xj['xComplex'] + $v['vComplex'] * exp(-$gamma['gComplex'] * pow($r, 2)) * ($Xk['xComplex'] - $Xj['xComplex']) + $alpha * $this->randomEpsilon();
        if ($xComplex < $this->range_positions['min_xComplex']) {
            $xComplex = $this->range_positions['min_xComplex'];
        }
        if ($xComplex > $this->range_positions['max_xComplex']) {
            $xComplex = $this->range_positions['max_xComplex'];
        }

        return ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
    }

    function trimmingPositions($position, $label)
    {
        if ($label === 'xSimple' && $position < $this->range_positions['min_xSimple']) {
            return $this->range_positions['min_xSimple'];
        }
        if ($label === 'xSimple' && $position > $this->range_positions['max_xSimple']) {
            return $this->range_positions['max_xSimple'];
        }

        if ($label === 'xAverage' && $position < $this->range_positions['min_xAverage']) {
            return $this->range_positions['min_xAverage'];
        }
        if ($label === 'xAverage' && $position > $this->range_positions['max_xAverage']) {
            return $this->range_positions['max_xAverage'];
        }

        if ($label === 'xComplex' && $position < $this->range_positions['min_xComplex']) {
            return $this->range_positions['min_xComplex'];
        }
        if ($label === 'xComplex' && $position > $this->range_positions['max_xComplex']) {
            return $this->range_positions['max_xComplex'];
        }
    }

    function distance($Xj, $Xi)
    {
        foreach ($Xj['positions'] as $key => $position) {
            $distances[] = sqrt(pow($position - $Xi['positions'][$key], 2));
        }
        return array_sum($distances);
    }

    function UCP($projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $alpha = $this->randomzeroToOne();
            ## Generate population
            if ($generation === 0) {

                for ($i = 0; $i <= $this->parameters['firefly_size'] - 1; $i++) {
                    $positions = [
                        'xSimple' => $this->randomSimpleUCWeight(),
                        'xAverage' => $this->randomAverageUCWeight(),
                        'xComplex' => $this->randomComplexUCWeight()
                    ];

                    $UCP = $this->size($positions, $projects);
                    $estimated_effort = $UCP * $this->productivity_factor;
                    $ae = abs($estimated_effort - floatval($projects['actualEffort']));
                    $fireflies[$generation + 1][$i] = [
                        'estimatedEffort' => $estimated_effort,
                        'ucp' => $UCP,
                        'ae' => $ae,
                        'positions' => $positions
                    ];
                }
                // $global_min_ae = min(array_column($fireflies[$generation + 1], 'ae'));
                // $index_global_min = array_search($global_min_ae, array_column($fireflies[$generation + 1], 'ae'));
                // $global_min[$generation + 1] = $fireflies[$generation + 1][$index_global_min];
            } ## End if generation = 0

            if ($generation > 0) {
                ## TODO refactor conditional statements
                foreach ($fireflies[$generation] as $key => $firefly) {
                    if ($this->parameters['firefly_size'] == $key + 1) {
                        $new_positions = $this->movingFireflyXiRandomly($firefly, $alpha);
                    } else {
                        if ($fireflies[$generation][$key + 1]['ae'] < $firefly['ae']) {
                            $r = $this->distance($fireflies[$generation][$key + 1], $firefly);
                            $new_positions = $this->movingFireflyXiToXj($firefly, $fireflies[$generation][$key + 1], $r, $alpha);
                        } else {
                            $new_positions = $this->movingFireflyXiRandomly($firefly, $alpha);
                        }
                    }
                    $UCP = $this->size($new_positions, $projects);
                    $estimated_effort = $UCP * $this->productivity_factor;
                    $ae = abs($estimated_effort - floatval($projects['actualEffort']));
                    $fireflies[$generation + 1][$key] = [
                        'estimatedEffort' => $estimated_effort,
                        'ucp' => $UCP,
                        'ae' => $ae,
                        'positions' => $new_positions
                    ];
                }
                $global_min_ae = min(array_column($fireflies[$generation + 1], 'ae'));
                $index_global_min = array_search($global_min_ae, array_column($fireflies[$generation + 1], 'ae'));
                $global_min = $fireflies[$generation + 1][$index_global_min];

                ## Fitness evaluations
                if ($global_min['ae'] < $this->parameters['fitness']) {
                    return $global_min;
                } else {
                    $results[] = $global_min;
                }
            } ## End if generation > 0
        } ## End of Generation
        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    }

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->UCP($project);
                }
                //print_r($this->UCP($project));

                foreach ($results as $result) {
                    $xSimples[] = $result['positions']['xSimple'];
                    $xAverages[] = $result['positions']['xAverage'];
                    $xComplexes[] = $result['positions']['xComplex'];
                }

                $xSimple = array_sum($xSimples) / $this->parameters['trials'];
                $xAverage = array_sum($xAverages) / $this->parameters['trials'];
                $xComplex = array_sum($xComplexes) / $this->parameters['trials'];

                if ($xSimple < $this->range_positions['min_xSimple']) {
                    $xSimple = $this->range_positions['min_xSimple'];
                }
                if ($xSimple > $this->range_positions['max_xSimple']) {
                    $xSimple = $this->range_positions['max_xSimple'];
                }
                if ($xAverage < $this->range_positions['min_xAverage']) {
                    $xAverage = $this->range_positions['min_xAverage'];
                }
                if ($xAverage > $this->range_positions['max_xAverage']) {
                    $xAverage = $this->range_positions['max_xAverage'];
                }
                if ($xComplex < $this->range_positions['min_xComplex']) {
                    $xComplex = $this->range_positions['min_xComplex'];
                }
                if ($xComplex > $this->range_positions['max_xComplex']) {
                    $xComplex = $this->range_positions['max_xComplex'];
                }
                $positions = [
                    'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex
                ];
                $UCP = $this->size($positions, $project);
                $estimated_effort = $UCP * $this->productivity_factor;
                $ae = abs($estimated_effort - floatval($project['actualEffort']));
                $ret[] = array('actualEffort' => $project['actualEffort'], 'estimatedEffort' => $estimated_effort, 'ucp' => $UCP, 'ae' => $ae, 'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex);
                $results = [];
                $xSimples = [];
                $xAverages = [];
                $xComplexes = [];
            }
        }
        return $ret;
    }
} ## End of Raoptimizer


$file_name = 'silhavy_dataset.txt';
$firefly_size = 20;
$maximum_generation = 40;
$trials = 1000;
$s = 0.5;
$a = 0.5;
$b_min = 0.2;
$b = 2;
$g = 1;
$fitness = 100;

$parameters = ['firefly_size' => $firefly_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 's' => $s, 'a' => $a, 'b_min' => $b_min, 'b' => $b, 'g' => $g, 'fitness' => $fitness];
$productivity_factor = 20;

$range_positions = [
    'min_xSimple' => 5, 'max_xSimple' => 7.49,
    'min_xAverage' => 7.5, 'max_xAverage' => 12.49,
    'min_xComplex' => 12.5, 'max_xComplex' => 15,
    'min_epsilon' => -5, 'max_epsilon' => 5
];

$optimize = new Raoptimizer($file_name, $parameters, $productivity_factor, $range_positions);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 71;

echo '<p>';
foreach ($optimized as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<br>';
    // $data = array($result['xSimple'], $result['xAverage'], $result['xComplex'], $result['estimatedEffort'], floatval($result['actualEffort']), $result['ae']);
    // $fp = fopen('hasil_era_estimated.txt', 'a');
    // fputcsv($fp, $data);
    // fclose($fp);
}
