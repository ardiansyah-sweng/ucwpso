<?php
set_time_limit(1000000);

class FireflyCLOptimizer
{
    protected $dataset_name;
    protected $parameters;
    protected $scales;

    function __construct($dataset_name, $parameters, $scales)
    {
        $this->dataset_name = $dataset_name;
        $this->parameters = $parameters;
        $this->scales = $scales;
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset_name);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        $column_indexes = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25];
        $columns = ['prec', 'flex', 'resl', 'team', 'pmat', 'rely', 'data', 'cplx', 'ruse', 'docu', 'time', 'stor', 'pvol', 'acap', 'pcap', 'pcon', 'apex', 'plex', 'ltex', 'tool', 'site', 'sced', 'kloc', 'effort', 'defects', 'months'];
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


    function randomEpsilon()
    {
        return mt_rand($this->parameters['min_epsilon'] * 100, $this->parameters['max_epsilon'] * 100) / 100;
    }

    function scaleEffortExponent($B, $scale_factors)
    {
        return $B + 0.01 * array_sum($scale_factors);
    }

    function estimating($A, $size, $E, $effort_multipliers)
    {
        return $A * pow($size, $E) * array_product($effort_multipliers);
    }

    function attractiveness($r)
    {
        return $this->parameters['beta'] * exp(-$this->parameters['gamma'] * pow($r, 2));
    }

    function movingFireflyXiToXj($Xi, $Xj, $r, $alpha)
    {
        return $Xi['A'] + $this->attractiveness($r) * ($Xj['A'] - $Xi['A']) + $alpha * $this->randomEpsilon();
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
        $gamma = $this->gamma($this->parameters['upper_bound'], $this->parameters['lower_bound']);
        $beta = $this->beta($r, $gamma);
        $V = $r * $m * $this->logisticRegression(-pow($beta, ($generation / 600)));

        return $Xj['A'] + $V * exp(-$gamma * pow($r, 2)) * ($Xk['A'] - $Xj['A']) + $alpha * $this->randomEpsilon();
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
        $k = 1;
        $x0 = 0;
        $L = 1;
        return $L / (1 + exp(- ($k * ($x - $x0))));
    }

    function movingFireflyXiRandomly($Xi, $alpha)
    {
        return $Xi['A'] + $alpha * $this->randomEpsilon();
    }

    function distance($Xj, $Xi)
    {
        return sqrt(pow($Xj['A'] - $Xi['A'], 2));
    }

    function cocomo($projects)
    {
        $SF['prec'] = $projects['prec'];
        $SF['flex'] = $projects['flex'];
        $SF['resl'] = $projects['resl'];
        $SF['team'] = $projects['team'];
        $SF['pmat'] = $projects['pmat'];
        $EM['rely'] = $projects['rely'];
        $EM['data'] = $projects['data'];
        $EM['cplx'] = $projects['cplx'];
        $EM['ruse'] = $projects['ruse'];
        $EM['docu'] = $projects['docu'];
        $EM['time'] = $projects['time'];
        $EM['stor'] = $projects['stor'];
        $EM['pvol'] = $projects['pvol'];
        $EM['acap'] = $projects['acap'];
        $EM['pcap'] = $projects['pcap'];
        $EM['pcon'] = $projects['pcon'];
        $EM['apex'] = $projects['apex'];
        $EM['plex'] = $projects['plex'];
        $EM['ltex'] = $projects['ltex'];
        $EM['tool'] = $projects['tool'];
        $EM['site'] = $projects['site'];
        $EM['sced'] = $projects['sced'];

        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $alpha[$generation + 1] = $this->randomzeroToOne();
            $B = $this->randomzeroToOne();

            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->parameters['firefly_size'] - 1; $i++) {
                    $A = mt_rand($this->parameters['lower_bound'] * 100, $this->parameters['upper_bound'] * 100) / 100;
                    $E = $this->scaleEffortExponent($B, $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $fireflies[$generation + 1][$i] = [
                        'A' => $A,
                        'B' => $B,
                        'E' => $E,
                        'EM' => array_sum($EM),
                        'SF' => array_sum($SF),
                        'size' => $projects['kloc'],
                        'effort' => $projects['effort'],
                        'estimatedEffort' => $estimated_effort,
                        'ae' => abs($estimated_effort - $projects['effort'])
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
                        $A = $this->movingFireflyXiRandomly($firefly, $alpha[$generation]);
                    } else {
                        if ($fireflies[$generation][$key + 1]['ae'] < $firefly['ae']) {
                            $r = $this->distance($fireflies[$generation][$key + 1], $firefly);
                            $A = $this->movingFireflyXiToXj($firefly, $fireflies[$generation][$key + 1], $r, $this->parameters['alpha']);
                        } else {
                            $selected_female = $this->rouletteWheel($archive_A[$generation], $selection_probabilities[$generation]);
                            $r = $this->distance($selected_female, $fireflies[$generation][$key + 1]);
                            if ($selected_female['ae'] < $fireflies[$generation][$key + 1]['ae']) {
                                $A = $this->movingFireflyXjToXk($fireflies[$generation][$key + 1], $selected_female, $r, $alpha[$generation + 1], $generation);
                            } else {
                                $A = $this->movingFireflyXiRandomly($firefly, $alpha[$generation]);
                            }
                        }
                    }

                    $E = $this->scaleEffortExponent($B, $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $fireflies[$generation + 1][$key] = [
                        'A' => $A,
                        'B' => $B,
                        'E' => $E,
                        'EM' => array_sum($EM),
                        'SF' => array_sum($SF),
                        'size' => $projects['kloc'],
                        'effort' => $projects['effort'],
                        'estimatedEffort' => $estimated_effort,
                        'ae' => abs($estimated_effort - $projects['effort'])
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
                $projects['prec'] = $this->scales['prec'][$project['prec']];
                $projects['flex'] = $this->scales['flex'][$project['flex']];
                $projects['resl'] = $this->scales['resl'][$project['resl']];
                $projects['team'] = $this->scales['team'][$project['team']];
                $projects['pmat'] = $this->scales['pmat'][$project['pmat']];
                $projects['rely'] = $this->scales['rely'][$project['rely']];
                $projects['data'] = $this->scales['data'][$project['data']];
                $projects['cplx'] = $this->scales['cplx'][$project['cplx']];
                $projects['ruse'] = $this->scales['ruse'][$project['ruse']];
                $projects['docu'] = $this->scales['docu'][$project['docu']];
                $projects['time'] = $this->scales['time'][$project['time']];
                $projects['stor'] = $this->scales['stor'][$project['stor']];
                $projects['pvol'] = $this->scales['pvol'][$project['pvol']];
                $projects['acap'] = $this->scales['acap'][$project['acap']];
                $projects['pcap'] = $this->scales['pcap'][$project['pcap']];
                $projects['pcon'] = $this->scales['pcon'][$project['pcon']];
                $projects['apex'] = $this->scales['apex'][$project['apex']];
                $projects['plex'] = $this->scales['plex'][$project['plex']];
                $projects['ltex'] = $this->scales['ltex'][$project['ltex']];
                $projects['tool'] = $this->scales['tool'][$project['tool']];
                $projects['site'] = $this->scales['site'][$project['site']];
                $projects['sced'] = $this->scales['sced'][$project['sced']];
                $projects['kloc'] = $project['kloc'];
                $projects['effort'] = $project['effort'];
                $projects['defects'] = $project['defects'];
                $projects['months'] = $project['months'];
        
                $SF['prec'] = $projects['prec'];
                $SF['flex'] = $projects['flex'];
                $SF['resl'] = $projects['resl'];
                $SF['team'] = $projects['team'];
                $SF['pmat'] = $projects['pmat'];
                $EM['rely'] = $projects['rely'];
                $EM['data'] = $projects['data'];
                $EM['cplx'] = $projects['cplx'];
                $EM['ruse'] = $projects['ruse'];
                $EM['docu'] = $projects['docu'];
                $EM['time'] = $projects['time'];
                $EM['stor'] = $projects['stor'];
                $EM['pvol'] = $projects['pvol'];
                $EM['acap'] = $projects['acap'];
                $EM['pcap'] = $projects['pcap'];
                $EM['pcon'] = $projects['pcon'];
                $EM['apex'] = $projects['apex'];
                $EM['plex'] = $projects['plex'];
                $EM['ltex'] = $projects['ltex'];
                $EM['tool'] = $projects['tool'];
                $EM['site'] = $projects['site'];
                $EM['sced'] = $projects['sced'];
        
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->cocomo($projects);
                }
                $A = array_sum(array_column($results, 'A')) / $this->parameters['trials'];
                $B = array_sum(array_column($results, 'B')) / $this->parameters['trials'];
                $results = [];
                $E = $this->scaleEffortExponent($B, $SF);
                $effort = $project['effort'];
                $estimatedEffort = $this->estimating($A, $project['kloc'], $E, $EM);
                $ae = abs($estimatedEffort - $effort);
                $ret[] = array('A' => $A, 'B' => $B, 'E' => $E, 'effort' => $effort, 'estimatedEffort' => $estimatedEffort, 'ae' => $ae);           
            }
        }
        return $ret;
    }
} ## End of FA-CL Optimizer

$scales = array(
    "prec" => array("vl" => 6.2, "l" => 4.96, "n" => 3.72, "h" => 2.48, "vh" => 1.24, "eh" => 0),
    "flex" => array("vl" => 5.07, "l" => 4.05, "n" => 3.04, "h" => 2.03, "vh" => 1.01, "eh" => 0),
    "resl" => array("vl" => 7.07, "l" => 5.65, "n" => 4.24, "h" => 2.83, "vh" => 1.41, "eh" => 0),
    "team" => array("vl" => 5.48, "l" => 4.38, "n" => 3.29, "h" => 2.19, "vh" => 1.10, "eh" => 0),
    "pmat" => array("vl" => 7.80, "l" => 6.24, "n" => 4.68, "h" => 3.12, "vh" => 1.56, "eh" => 0),
    "rely" => array("vl" => 0.82, "l" => 0.92, "n" => 1.00, "h" => 1.10, "vh" => 1.26, "eh" => ''),
    "data" => array("vl" => '', "l" => 0.90, "n" => 1.00, "h" => 1.14, "vh" => 1.28, "eh" => ''),
    "cplx" => array("vl" => 0.73, "l" => 0.87, "n" => 1.00, "h" => 1.17, "vh" => 1.34, "eh" => 1.74),
    "ruse" => array("vl" => '', "l" => 0.95, "n" => 1.00, "h" => 1.07, "vh" => 1.15, "eh" => 1.24),
    "docu" => array("vl" => 0.81, "l" => 0.91, "n" => 1.00, "h" => 1.11, "vh" => 1.23, "eh" => ''),
    "time" => array("vl" => '', "l" => '', "n" => 1.00, "h" => 1.11, "vh" => 1.29, "eh" => 1.63),
    "stor" => array("vl" => '', "l" => '', "n" => 1.00, "h" => 1.05, "vh" => 1.17, "eh" => 1.46),
    "pvol" => array("vl" => '', "l" => 0.87, "n" => 1.00, "h" => 1.15, "vh" => 1.30, "eh" => ''),
    "acap" => array("vl" => 1.42, "l" => 1.19, "n" => 1.00, "h" => 0.85, "vh" => 0.71, "eh" => ''),
    "pcap" => array("vl" => 1.34, "l" => 1.15, "n" => 1.00, "h" => 0.88, "vh" => 0.76, "eh" => ''),
    "pcon" => array("vl" => 1.29, "l" => 1.12, "n" => 1.00, "h" => 0.90, "vh" => 0.81, "eh" => ''),
    "apex" => array("vl" => 1.22, "l" => 1.10, "n" => 1.00, "h" => 0.88, "vh" => 0.81, "eh" => ''),
    "plex" => array("vl" => 1.19, "l" => 1.09, "n" => 1.00, "h" => 0.91, "vh" => 0.85, "eh" => ''),
    "ltex" => array("vl" => 1.20, "l" => 1.09, "n" => 1.00, "h" => 0.91, "vh" => 0.84, "eh" => ''),
    "tool" => array("vl" => 1.17, "l" => 1.09, "n" => 1.00, "h" => 0.90, "vh" => 0.78, "eh" => ''),
    "site" => array("vl" => 1.22, "l" => 1.09, "n" => 1.00, "h" => 0.93, "vh" => 0.86, "eh" => 0.80),
    "sced" => array("vl" => 1.43, "l" => 1.14, "n" => 1.00, "h" => 1.00, "vh" => 1.00, "eh" => '')
);

$file_name = 'cocomo.txt';
$firefly_size = 20;
$maximum_generation = 40;
$trials = 1000;
$alpha = 0.5;
$beta_min = 0.2;
$beta = 1;
$gamma = 1;
$lower_bound = 0.01;
$upper_bound = 5;
$fitness = 10;

$parameters = [
    'firefly_size' => $firefly_size,
    'maximum_generation' => $maximum_generation,
    'trials' => $trials,
    'lower_bound' => $lower_bound,
    'upper_bound' => $upper_bound,
    'fitness' => $fitness,
    'alpha' => $alpha,
    'beta_min' => $beta_min,
    'beta' => $beta,
    'gamma' => $gamma,
    'min_epsilon' => -5, 
    'max_epsilon' => 5
];
$productivity_factor = 20;

$optimize = new FireflyCLOptimizer($file_name, $parameters, $scales);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 93;

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
