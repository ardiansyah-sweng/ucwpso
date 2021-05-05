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
        return $this->parameters['beta'] * exp(-$this->parameters['gamma'] * pow($r, 2));
    }

    function movingFireflyXiToXj($Xi, $Xj, $r, $alpha)
    {
        $xSimple = $Xi['positions']['xSimple'] + $this->attractiveness($r) * ($Xj['positions']['xSimple'] - $Xi['positions']['xSimple']) + $alpha * $this->randomEpsilon();
        $xSimple = $this->trimmingPositions($xSimple, 'xSimple');

        $xAverage = $Xi['positions']['xAverage'] + $this->attractiveness($r) * ($Xj['positions']['xAverage'] - $Xi['positions']['xAverage']) + $alpha * $this->randomEpsilon();
        $xAverage = $this->trimmingPositions($xAverage, 'xAverage');

        $xComplex = $Xi['positions']['xComplex'] + $this->attractiveness($r) * ($Xj['positions']['xComplex'] - $Xi['positions']['xComplex']) + $alpha * $this->randomEpsilon();
        $xComplex = $this->trimmingPositions($xComplex, 'xComplex');

        return ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
    }

    function gamma($upper_bound, $lower_bound)
    {
        return 1 / pow(($upper_bound - $lower_bound), 2);
    }

    function beta($r, $gamma)
    {
        return $this->parameters['beta_min'] + ($this->parameters['beta'] - $this->parameters['beta_min']) * exp(-$gamma * pow($r, 2));
    }

    function movingFireflyXjToXk($Xj, $Xk, $r, $alpha, $generation)
    {
        $m = 1 / 800;
        $gamma_xSimple = $this->gamma($this->range_positions['max_xSimple'], $this->range_positions['min_xSimple']);
        $beta_xSimple = $this->beta($r, $gamma_xSimple);
        $V_xSimple = $r * $m * $this->logisticRegression(-pow($beta_xSimple, ($generation / 600)));

        $gamma_xAverage = $this->gamma($this->range_positions['max_xAverage'], $this->range_positions['min_xAverage']);
        $beta_xAverage = $this->beta($r, $gamma_xAverage);
        $V_xAverage = $r * $m * $this->logisticRegression(-pow($beta_xAverage, ($generation / 600))); ## TODO I dont sure with ^ as replacement of pow :)

        $gamma_xComplex = $this->gamma($this->range_positions['max_xComplex'], $this->range_positions['min_xComplex']);
        $beta_xComplex = $this->beta($r, $gamma_xComplex);
        $V_xComplex = $r * $m * $this->logisticRegression(-pow($beta_xComplex, ($generation / 600)));

        return [
            'xSimple' => $Xj['positions']['xSimple'] + $V_xSimple * exp(-$gamma_xSimple * pow($r, 2)) * ($Xk['positions']['xSimple'] - $Xj['positions']['xSimple']) + $alpha * $this->randomEpsilon(),
            'xAverage' => $Xj['positions']['xAverage'] + $V_xAverage * exp(-$gamma_xAverage * pow($r, 2)) * ($Xk['positions']['xAverage'] - $Xj['positions']['xAverage']) + $alpha * $this->randomEpsilon(),
            'xComplex' => $Xj['positions']['xComplex'] + $V_xComplex * exp(-$gamma_xComplex * pow($r, 2)) * ($Xk['positions']['xComplex'] - $Xj['positions']['xComplex']) + $alpha * $this->randomEpsilon()
        ];
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
        return $position;
    }

    function selectionProbabilities($archive_A)
    {
        ## Scaling mechanism
        foreach ($archive_A as $key => $ae) {
            $females[] = [
                'index' => $key,
                'ae' => 1 / $ae['ae']
            ];
        }

        ## Selection probability
        $sum_females = array_sum(array_column($females, 'ae'));
        foreach ($females as $female) {
            $selection_probabilities[] = [
                'index' => $female['index'],
                'scale' => $female['ae'],
                'probability' => $female['ae'] / $sum_females
            ];
        }
        return $selection_probabilities;
    }

    function rouletteWheel($archive_A, $selection_probabilities)
    {
        ## Scaling mechanism
        foreach ($archive_A as $key => $ae) {
            $females[] = [
                'index' => $key,
                'ae' => 1 / $ae['ae']
            ];
        }

        foreach ($selection_probabilities as $key => $probability) {
            if ($key == 0) {
                $cummulatives[$key] = $probability['probability'];
            }
            if ($key > 0) {
                $cummulatives[$key] = $probability['probability'] + $cummulatives[$key - 1];
            }
        }

        foreach ($cummulatives as $key => $value) {
            $r = $this->randomzeroToOne();
            $tally = $this->tally($r, $cummulatives);
            $counters[] = $tally;
        }

        foreach (array_unique($counters) as $index) {
            $ret[] = $archive_A[$index];
        }
        $ae = min(array_column($ret, 'ae'));
        $index = array_search($ae, array_column($archive_A, 'ae'));
        return $archive_A[$index];
    }

    function tally($random, $cummulatives)
    {
        foreach ($cummulatives as $key => $cummulative) {
            $minimum = $cummulatives[0];

            if ($random > $cummulatives[$key] && $random <= $cummulatives[$key + 1]) {
                return array_search($cummulatives[$key + 1], $cummulatives);
            }
            if ($random < $minimum) {
                return array_search($minimum, $cummulatives);
            }
        }
    }

    function logisticRegression($x)
    {
        //echo ' xxx ' . $x;
        $k = 1;
        $x0 = 0;
        $L = 1;
        return $L / (1 + exp(- ($k * ($x - $x0))));
    }

    function movingFireflyXiRandomly($Xi, $alpha)
    {
        $xSimple = $Xi['positions']['xSimple'] + $alpha * $this->randomEpsilon();
        $xAverage = $Xi['positions']['xAverage'] + $alpha * $this->randomEpsilon();
        $xComplex = $Xi['positions']['xComplex'] + $alpha * $this->randomEpsilon();
        $xSimple = $this->trimmingPositions($xSimple, 'xSimple');
        $xAverage = $this->trimmingPositions($xAverage, 'xAverage');
        $xComplex = $this->trimmingPositions($xComplex, 'xComplex');
        return ['xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex];
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
            $alpha[$generation + 1] = $this->randomzeroToOne();
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
                ## Initialize female fireflies in Archive A
                $archive_A[$generation + 1] = $fireflies[$generation + 1];
                ## Initialize selection probabilites
                $selection_probabilities[$generation + 1] = $this->selectionProbabilities($archive_A[$generation + 1]);
            } ## End if generation = 0

            if ($generation > 0) {
                $alpha[$generation + 1] = pow((1 / 9000), (1 / $generation)) * $alpha[$generation];

                ## TODO refactor conditional statements
                foreach ($fireflies[$generation] as $key => $firefly) {
                    if ($this->parameters['firefly_size'] == $key + 1) {
                        $new_positions = $this->movingFireflyXiRandomly($firefly, $alpha[$generation]);
                    } else {
                        if ($fireflies[$generation][$key + 1]['ae'] < $firefly['ae']) {
                            $r = $this->distance($fireflies[$generation][$key + 1], $firefly);
                            $new_positions = $this->movingFireflyXiToXj($firefly, $fireflies[$generation][$key + 1], $r, $this->parameters['alpha']);
                        } else {
                            $selected_female = $this->rouletteWheel($archive_A[$generation], $selection_probabilities[$generation]);
                            $r = $this->distance($selected_female, $fireflies[$generation][$key + 1]);
                            if ($selected_female['ae'] < $fireflies[$generation][$key + 1]['ae']) {
                                $new_positions = $this->movingFireflyXjToXk($fireflies[$generation][$key + 1], $selected_female, $r, $alpha[$generation + 1], $generation);
                            } else {
                                $new_positions = $this->movingFireflyXiRandomly($firefly, $alpha[$generation]);
                            }
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

                $archive_A[$generation + 1] = $fireflies[$generation + 1];
                $selection_probabilities[$generation + 1] = $this->selectionProbabilities($archive_A[$generation + 1]);

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
                foreach ($results as $result) {
                    $xSimples[] = $result['positions']['xSimple'];
                    $xAverages[] = $result['positions']['xAverage'];
                    $xComplexes[] = $result['positions']['xComplex'];
                }

                $xSimple = array_sum($xSimples) / $this->parameters['trials'];
                $xSimple = $this->trimmingPositions($xSimple, 'xSimple');
                $xAverage = array_sum($xAverages) / $this->parameters['trials'];
                $xAverage = $this->trimmingPositions($xAverage, 'xAverage');
                $xComplex = array_sum($xComplexes) / $this->parameters['trials'];
                $xComplex = $this->trimmingPositions($xComplex, 'xComplex');

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
$alpha = 0.5;
$beta_min = 0.2;
$beta = 1;
$gamma = 1;
$fitness = 100;

$parameters = ['firefly_size' => $firefly_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 'alpha' => $alpha, 'beta_min' => $beta_min, 'beta' => $beta, 'gamma' => $gamma, 'fitness' => $fitness];
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
