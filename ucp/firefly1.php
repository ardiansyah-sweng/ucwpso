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

    function size($xSimple, $simpleUC, $xAverage, $averageUC, $xComplex, $complexUC, $uaw, $tcf, $ecf)
    {
        $ucSimple = $xSimple * $simpleUC;
        $ucAverage = $xAverage * $averageUC;
        $ucComplex = $xComplex * $complexUC;

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $uaw + $UUCW;
        return $UUCP * $tcf * $ecf;
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

    function attractiveness($r)
    {
        return $this->parameters['b'] * exp(-$this->parameters['g'] * pow($r, 2));
    }

    function logisticRegression($x)
    {
        $k = 1;
        $x0 = 0;
        $L = 1;
        return $L / (1 + exp(- ($k * ($x - $x0))));
    }

    function firefly($single_particle, $particles, $r, $generation, $females)
    {
        foreach ($particles as $particle) {
            if ($single_particle !== $particle && $particle['ae'] < $single_particle['ae']) {
                $xSimple = $single_particle['xSimple'] + $this->attractiveness($r) * ($particle['xSimple'] - $single_particle['xSimple'] + ($this->randomzeroToOne() * $this->randomEpsilon()));
                if ($xSimple < $this->range_positions['min_xSimple']) {
                    $xSimple = $this->range_positions['min_xSimple'];
                }
                if ($xSimple > $this->range_positions['max_xSimple']) {
                    $xSimple = $this->range_positions['max_xSimple'];
                }

                $xAverage = $single_particle['xAverage'] + $this->attractiveness($r) * ($particle['xAverage'] - $single_particle['xAverage'] + ($this->randomzeroToOne() * $this->randomEpsilon()));
                if ($xAverage < $this->range_positions['min_xAverage']) {
                    $xAverage = $this->range_positions['min_xAverage'];
                }
                if ($xAverage > $this->range_positions['max_xAverage']) {
                    $xAverage = $this->range_positions['max_xAverage'];
                }

                $xComplex = $single_particle['xComplex'] + $this->attractiveness($r) * ($particle['xComplex'] - $single_particle['xComplex'] + ($this->randomzeroToOne() * $this->randomEpsilon()));
                if ($xComplex < $this->range_positions['min_xComplex']) {
                    $xComplex = $this->range_positions['min_xComplex'];
                }
                if ($xComplex > $this->range_positions['max_xComplex']) {
                    $xComplex = $this->range_positions['max_xComplex'];
                }
                $ret[] = [
                    'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex
                ];
            } else {
                if ($females['ae'] < $particle['ae']) {
                    $betha = $this->parameters['b_min'] + ($this->parameters['b'] - $this->parameters['b_min']) * exp(-$this->parameters['g'] * pow($r, 2));
                    $x = pow($generation / 600, $betha); ## TODO recheck the equation to avoid NaN
                    $v = ($r / 800) * $this->logisticRegression($x);
                    $exponent = exp(-$this->parameters['g'] * pow($r, 2));

                    $xSimple = $particle['xSimple'] + $v * $exponent * ($females['xSimple'] - $particle['xSimple'] + ($this->randomzeroToOne() * $this->randomEpsilon()));
                    if ($xSimple < $this->range_positions['min_xSimple']) {
                        $xSimple = $this->range_positions['min_xSimple'];
                    }
                    if ($xSimple > $this->range_positions['max_xSimple']) {
                        $xSimple = $this->range_positions['max_xSimple'];
                    }

                    $xAverage = $particle['xAverage'] + $v * $exponent * ($females['xAverage'] - $particle['xAverage'] + ($this->randomzeroToOne() * $this->randomEpsilon()));
                    if ($xAverage < $this->range_positions['min_xAverage']) {
                        $xAverage = $this->range_positions['min_xAverage'];
                    }
                    if ($xAverage > $this->range_positions['max_xAverage']) {
                        $xAverage = $this->range_positions['max_xAverage'];
                    }

                    $xComplex = $particle['xComplex'] + $v * $exponent * ($females['xComplex'] - $particle['xComplex'] + ($this->randomzeroToOne() * $this->randomEpsilon()));
                    if ($xComplex < $this->range_positions['min_xComplex']) {
                        $xComplex = $this->range_positions['min_xComplex'];
                    }
                    if ($xComplex > $this->range_positions['max_xComplex']) {
                        $xComplex = $this->range_positions['max_xComplex'];
                    }
                    ## TODO check for exceeding limits

                    $ret[] = [
                        'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex
                    ];
                }
            }
        }
        return $ret;
    }

    function movingFireflyXiToXj($Xi, $Xj, $r, $alpha)
    {
        $xSimple = $Xi['xSimple'] + $this->attractiveness($r) * ($Xj['xSimple'] - $Xi['xSimple']) + $alpha * $this->randomEpsilon();
        if ($xSimple < $this->range_positions['min_xSimple']) {
            $xSimple = $this->range_positions['min_xSimple'];
        }
        if ($xSimple > $this->range_positions['max_xSimple']) {
            $xSimple = $this->range_positions['max_xSimple'];
        }
        $xAverage = $Xi['xAverage'] + $this->attractiveness($r) * ($Xj['xAverage'] - $Xi['xAverage']) + $alpha * $this->randomEpsilon();
        if ($xAverage < $this->range_positions['min_xAverage']) {
            $xAverage = $this->range_positions['min_xAverage'];
        }
        if ($xAverage > $this->range_positions['max_xAverage']) {
            $xAverage = $this->range_positions['max_xAverage'];
        }
        $xComplex = $Xi['xComplex'] + $this->attractiveness($r) * ($Xj['xComplex'] - $Xi['xComplex']) + $alpha * $this->randomEpsilon();
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

    function UCP($projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $r = $this->randomzeroToOne();
            ## Generate population
            if ($generation === 0) {
                $alpha[$generation + 1] = $this->randomzeroToOne();
                for ($i = 0; $i <= $this->parameters['particle_size'] - 1; $i++) {
                    $xSimple = $this->randomSimpleUCWeight();
                    $xAverage = $this->randomAverageUCWeight();
                    $xComplex = $this->randomComplexUCWeight();

                    $UCP = $this->size($xSimple, $projects['simpleUC'], $xAverage, $projects['averageUC'], $xComplex, $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;
                    $particles[$generation + 1][$i]['estimatedEffort'] = $esimated_effort;
                    $particles[$generation + 1][$i]['ucp'] = $UCP;
                    $particles[$generation + 1][$i]['ae'] = abs($esimated_effort - floatval($projects['actualEffort']));
                    $particles[$generation + 1][$i]['xSimple'] = $xSimple;
                    $particles[$generation + 1][$i]['xAverage'] = $xAverage;
                    $particles[$generation + 1][$i]['xComplex'] = $xComplex;
                }
                ## Initialize female fireflies in Archive A
                $archive_A[$generation + 1] = $particles[$generation + 1];
                ## Initialize selection probabilites
                $selection_probabilities[$generation + 1] = $this->selectionProbabilities($archive_A[$generation + 1]);

                $global_min_ae = min(array_column($particles[$generation + 1], 'ae'));
                $index_global_min = array_search($global_min_ae, array_column($particles[$generation + 1], 'ae'));
                $global_min[$generation + 1] = $particles[$generation + 1][$index_global_min];
            } ## End if generation = 0

            if ($generation > 0 && $generation < 5) {
                echo '<b> Gen: ' . $generation . '</b><br>';
                $FEs = $this->parameters['particle_size'];
                $maxFEs = max(array_column($particles[$generation], 'ae'));
                //while ($FEs < $maxFEs) {
                foreach ($particles[$generation] as $i => $Xi) {
                    echo 'i: ' . $i . ' ';
                    print_r($Xi);
                    echo '<br>';
                    foreach ($particles[$generation] as $Xj) {
                        //echo '&nbsp;&nbsp;&nbsp;&nbsp; ==>';
                        //print_r($Xj);
                        //echo '<br>';

                        if ($Xj['ae'] < $Xi['ae'] && $Xi !== $Xj) {
                            $positionsXi = [
                                'xSimple' => $Xi['xSimple'],
                                'xAverage' => $Xi['xAverage'],
                                'xComplex' => $Xi['xComplex']
                            ];
                            $positionsXj = [
                                'xSimple' => $Xj['xSimple'],
                                'xAverage' => $Xj['xAverage'],
                                'xComplex' => $Xj['xComplex']
                            ];

                            $new_positions = $this->movingFireflyXiToXj($positionsXi, $positionsXj, $r, $alpha[$generation + 1]);
                            $UCP = $this->size($new_positions['xSimple'], $projects['simpleUC'], $new_positions['xAverage'], $projects['averageUC'], $new_positions['xComplex'], $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);
                            $esimated_effort = $UCP * $this->productivity_factor;
                            $particles[$generation + 1][$i]['estimatedEffort'] = $esimated_effort;
                            $particles[$generation + 1][$i]['ucp'] = $UCP;
                            $particles[$generation + 1][$i]['ae'] = abs($esimated_effort - floatval($projects['actualEffort']));
                            $particles[$generation + 1][$i]['xSimple'] = $xSimple;
                            $particles[$generation + 1][$i]['xAverage'] = $xAverage;
                            $particles[$generation + 1][$i]['xComplex'] = $xComplex;

                            $FEs += $FEs;
                            $maxFEs = max(array_column($particles[$generation + 1], 'ae'));
                            //print_r($particles[$generation + 1]);
                            //echo '<br>';

                            //break;
                            // echo '&nbsp;&nbsp;&nbsp;&nbsp; ==>';
                            // echo 'New particle: ' . $FEs . ' ' . $maxFEs;
                            // print_r($particles[$generation + 1]);
                            // echo '<br>';
                        } else {
                            //print_r($global_min[$generation]);
                            $selected_female = $this->rouletteWheel($archive_A[$generation], $selection_probabilities[$generation]);
                            if ($selected_female['ae'] < $Xj['ae']) {

                                $m = 1 / 800;
                                $gamma_xSimple = $this->gamma($this->range_positions['max_xSimple'], $this->range_positions['min_xSimple']);
                                $beta_xSimple = $this->beta($r, $gamma_xSimple);
                                $V_xSimple = $r * $m * $this->logisticRegression(-$beta_xSimple ^ ($generation / 600));

                                $gamma_xAverage = $this->gamma($this->range_positions['max_xAverage'], $this->range_positions['min_xAverage']);
                                $beta_xAverage = $this->beta($r, $gamma_xAverage);
                                $V_xAverage = $r * $m * $this->logisticRegression(-$beta_xAverage ^ ($generation / 600)); ## TODO I dont sure with ^ as replacement of pow :)

                                $gamma_xComplex = $this->gamma($this->range_positions['max_xComplex'], $this->range_positions['min_xComplex']);
                                $beta_xComplex = $this->beta($r, $gamma_xComplex);
                                $V_xComplex = $r * $m * $this->logisticRegression(-$beta_xComplex ^ ($generation / 600));

                                $v = ['vSimple' => $V_xSimple, 'vAverage' => $V_xAverage, 'vComplex' => $V_xComplex];

                                $gamma = ['gSimple' => $gamma_xSimple, 'gAverage' => $gamma_xAverage, 'gComplex' => $gamma_xComplex];
                                
                                $alpha[$generation + 1] = pow((1 / 9000), (1 / $generation)) * $alpha[$generation];

                                $new_positions = $this->movingFireflyXjToXk($Xj, $selected_female, $r, $v, $alpha[$generation + 1], $gamma);

                                $UCP = $this->size($new_positions['xSimple'], $projects['simpleUC'], $new_positions['xAverage'], $projects['averageUC'], $new_positions['xComplex'], $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);
                                $esimated_effort = $UCP * $this->productivity_factor;
                                $particles[$generation + 1][$i]['estimatedEffort'] = $esimated_effort;
                                $particles[$generation + 1][$i]['ucp'] = $UCP;
                                $particles[$generation + 1][$i]['ae'] = abs($esimated_effort - floatval($projects['actualEffort']));
                                $particles[$generation + 1][$i]['xSimple'] = $xSimple;
                                $particles[$generation + 1][$i]['xAverage'] = $xAverage;
                                $particles[$generation + 1][$i]['xComplex'] = $xComplex;
                                //print_r($particles[$generation + 1]);
                                //echo '<br>';
                                //break;
                                $FEs += $FEs;
                                $maxFEs = max(array_column($particles[$generation + 1], 'ae'));
                                // echo '&nbsp;&nbsp;&nbsp;&nbsp; ==>';
                                // echo 'New particle: ' . $FEs . ' ' . $maxFEs;
                                // print_r($particles[$generation + 1]);
                                // echo '<br>';
                            }
                        }
                    }
                    // echo '<p>';
                }
                //print_r($global_min[$generation]);
                echo '<br>';
                ## Initialize female fireflies in Archive A
                $archive_A[$generation + 1] = $particles[$generation + 1];
                ## Initialize selection probabilites
                $selection_probabilities[$generation + 1] = $this->selectionProbabilities($archive_A[$generation + 1]);

                $global_min_ae = min(array_column($particles[$generation + 1], 'ae'));
                $index_global_min = array_search($global_min_ae, array_column($particles[$generation + 1], 'ae'));
                $global_min[$generation + 1] = $particles[$generation + 1][$index_global_min];
                print_r($global_min[$generation]);

                echo '<p>';
                echo '<p>';
                //dd($particles);
                //}

                ## Fitness evaluations
                // if ($Xbest[$generation]['ae'] < $this->parameters['fitness']) {
                //     return $Xbest[$generation];
                // } else {
                //     $results[] = $Xbest[$generation];
                // }
            } ## End of if generation > 0
        } ## End of Generation
    }

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->UCP($project);
                }
                $xSimple = array_sum(array_column($results, 'xSimple')) / $this->parameters['trials'];
                $xAverage = array_sum(array_column($results, 'xAverage')) / $this->parameters['trials'];
                $xComplex = array_sum(array_column($results, 'xComplex')) / $this->parameters['trials'];
                $results = [];
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

                $UCP = $this->size($xSimple, $project['simpleUC'], $xAverage, $project['averageUC'], $xComplex, $project['complexUC'], $project['uaw'], $project['tcf'], $project['ecf']);

                $estimated_effort = $UCP * $this->productivity_factor;
                $ae = abs($estimated_effort - floatval($project['actualEffort']));
                $ret[] = array('actualEffort' => $project['actualEffort'], 'estimatedEffort' => $estimated_effort, 'ucp' => $UCP, 'ae' => $ae, 'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex);
            }
        }
        return $ret;
    }
} ## End of Raoptimizer


$file_name = 'silhavy_dataset.txt';
$fun = 1;
$particle_size = 20;
$maximum_generation = 40;
$trials = 1;
$s = 0.5;
$a = 0.5;
$b_min = 0.2;
$b = 1;
$g = 1;
$fitness = 100;

$parameters = ['particle_size' => $particle_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 's' => $s, 'a' => $a, 'b_min' => $b_min, 'b' => $b, 'g' => $g, 'fitness' => $fitness];
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
    $data = array($result['xSimple'], $result['xAverage'], $result['xComplex'], $result['estimatedEffort'], floatval($result['actualEffort']), $result['ae']);
    $fp = fopen('hasil_era_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
