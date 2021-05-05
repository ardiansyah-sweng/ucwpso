<?php
set_time_limit(1000000);

class FireflyCLOptimizer
{
    protected $dataset;
    protected $parameters;
    protected $dataset_name;

    function __construct($dataset, $parameters, $dataset_name)
    {
        $this->dataset = $dataset;
        $this->parameters = $parameters;
        $this->dataset_name = $dataset_name;
    }

    function prepareDataset()
    {
        $raw_dataset = file($this->dataset[$this->dataset_name]['file_name']);
        foreach ($raw_dataset as $val) {
            $data[] = explode(",", $val);
        }
        foreach ($data as $key => $val) {
            foreach (array_keys($val) as $subkey) {
                if ($subkey == $this->dataset[$this->dataset_name]['column_indexes'][$subkey]) {
                    $data[$key][$this->dataset[$this->dataset_name]['columns'][$subkey]] = $data[$key][$subkey];
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

    function attractiveness($r)
    {
        return $this->parameters['beta'] * exp(-$this->parameters['gamma'] * pow($r, 2));
    }

    function movingFireflyXiToXj($Xi, $Xj, $r, $alpha)
    {
        foreach ($Xi as $key => $position) {
            $positions[$key] = $position + $this->attractiveness($r) * ($Xj[$key] - $position) + $alpha * $this->randomEpsilon();
        }
        return $positions;
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
        foreach ($Xj as $key => $position) {
            // $gamma = $this->gamma(1, $position);
            $gamma = $this->parameters['gamma'];
            $beta = $this->beta($r, $gamma);
            $V = $r * $m * $this->logisticRegression(-pow($beta, ($generation / 600)));
            $positions[$key] = $position + $V * exp(-$gamma * pow($r, 2)) * ($Xk[$key] - $position) + $alpha * $this->randomEpsilon();
        }
        return $positions;
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
        if ($sum_females == 0){
            $sum_females = 0.0000001;
        }
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
        foreach ($Xi as $key => $position) {
            $ret[$key] = $position + $alpha * $this->randomEpsilon();
        }
        return $ret;
    }

    function distance($Xj, $Xi)
    {
        foreach ($Xj as $key => $position) {
            $ret[] = sqrt(pow($position - $Xi[$key], 2));
        }
        return array_sum($ret);
    }

    function frictionFactorsRandomWeight()
    {
        $ff_team_composition = mt_rand($this->parameters['friction_factors']['ff_team_composition'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;
        $ff_process = mt_rand($this->parameters['friction_factors']['ff_process'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;
        $ff_environmental_factors = mt_rand($this->parameters['friction_factors']['ff_environmental_factors'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;
        $ff_team_dynamics = mt_rand($this->parameters['friction_factors']['ff_environmental_factors'] * 100, $this->parameters['friction_factors']['max']  * 100) / 100;

        return [
            'ff_team_composition' => $ff_team_composition,
            'ff_process' => $ff_process,
            'ff_environmental_factors' => $ff_environmental_factors,
            'ff_team_dynamics' => $ff_team_dynamics,
        ];
    }

    function dynamicForceFactorsRandomWeight()
    {
        $dff_expected_team_change = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_team_change'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_introduction_new_tools = mt_rand($this->parameters['dynamic_force_factors']['dff_introduction_new_tools'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_vendor_defect = mt_rand($this->parameters['dynamic_force_factors']['dff_vendor_defect'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_team_member_responsibility = mt_rand($this->parameters['dynamic_force_factors']['dff_team_member_responsibility'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_personal_issue = mt_rand($this->parameters['dynamic_force_factors']['dff_personal_issue'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_delay = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_delay'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_ambiguity = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_ambiguity'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_change = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_change'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;
        $dff_expected_relocation = mt_rand($this->parameters['dynamic_force_factors']['dff_expected_relocation'] * 100, $this->parameters['dynamic_force_factors']['max']  * 100) / 100;

        return [
            'dff_expected_team_change' => $dff_expected_team_change,
            'dff_introduction_new_tools' => $dff_introduction_new_tools,
            'dff_vendor_defect' => $dff_vendor_defect,
            'dff_team_member_responsibility' => $dff_team_member_responsibility,
            'dff_personal_issue' => $dff_personal_issue,
            'dff_expected_delay' => $dff_expected_delay,
            'dff_expected_ambiguity' => $dff_expected_ambiguity,
            'dff_expected_change' => $dff_expected_change,
            'dff_expected_relocation' => $dff_expected_relocation
        ];
    }

    function deceleration($friction_factor, $dynamic_force_factor)
    {
        return $friction_factor * $dynamic_force_factor;
    }

    function velocity($Vi, $deceleration)
    {
        return pow($Vi, $deceleration);
    }

    function averageWeights($weights, $factor)
    {
        if ($factor == 'friction_factors_weights') {
            foreach ($weights as $weight) {
                //print_r($weight);
                $process['ff_team_composition'][] = ($weight[$factor]['ff_team_composition']);
                $process['ff_process'][] = ($weight[$factor]['ff_process']);
                $process['ff_environmental_factors'][] = ($weight[$factor]['ff_environmental_factors']);
                $process['ff_team_dynamics'][] = ($weight[$factor]['ff_team_dynamics']);
            }
        }
        if ($factor == 'dynamic_force_factor_weights') {
            foreach ($weights as $weight) {
                $process['dff_expected_team_change'][] = ($weight[$factor]['dff_expected_team_change']);
                $process['dff_introduction_new_tools'][] = ($weight[$factor]['dff_introduction_new_tools']);
                $process['dff_vendor_defect'][] = ($weight[$factor]['dff_vendor_defect']);
                $process['dff_team_member_responsibility'][] = ($weight[$factor]['dff_team_member_responsibility']);
                $process['dff_personal_issue'][] = ($weight[$factor]['dff_personal_issue']);
                $process['dff_expected_delay'][] = ($weight[$factor]['dff_expected_delay']);
                $process['dff_expected_ambiguity'][] = ($weight[$factor]['dff_expected_ambiguity']);
                $process['dff_expected_change'][] = ($weight[$factor]['dff_expected_change']);
                $process['dff_expected_relocation'][] = ($weight[$factor]['dff_expected_relocation']);
            }
        }
        foreach ($process as $key => $val) {
            $ret[$key] = array_sum($val) / count($weights);
        }
        return $ret;
    }

    function estimatedTimeInDays($effort, $velocity)
    {
        if (is_infinite($velocity) || $velocity == 0) {
            $velocity = 0.00000001;
        }
        return $effort / $velocity;
    }

    function absoluteError($estimated, $actual)
    {
        return abs($estimated - floatval($actual));
    }

    function agile($target_projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $alpha[$generation + 1] = $this->randomzeroToOne();
            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->parameters['firefly_size'] - 1; $i++) {

                    $friction_factor_weights = $this->frictionFactorsRandomWeight();
                    $dynamic_force_factor_weights = $this->dynamicForceFactorsRandomWeight();
                    $friction_factor = array_product($friction_factor_weights);
                    $dynamic_force_factor = array_product($dynamic_force_factor_weights);
                    $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                    $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                    $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                    $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

                    $fireflies[$generation + 1][$i] = [
                        'friction_factors_weights' => $friction_factor_weights,
                        'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                }
                ## Initialize female fireflies in Archive A
                $archive_A[$generation + 1] = $fireflies[$generation + 1];
                ## Initialize selection probabilites
                $selection_probabilities[$generation + 1] = $this->selectionProbabilities($archive_A[$generation + 1]);
            } ## End if generation = 0

            if ($generation > 0) {
                $alpha[$generation + 1] = pow((1 / 9000), (1 / $generation)) * $alpha[$generation];

                foreach ($fireflies[$generation] as $key => $firefly) {

                    if ($this->parameters['firefly_size'] != $key + 1 && $fireflies[$generation][$key + 1]['ae'] < $firefly['ae']) {
                        $rff = $this->distance($fireflies[$generation][$key + 1]['friction_factors_weights'], $firefly['friction_factors_weights']);
                        $dff = $this->distance($fireflies[$generation][$key + 1]['dynamic_force_factor_weights'], $firefly['dynamic_force_factor_weights']);

                        $new_ff = $this->movingFireflyXiToXj($firefly['friction_factors_weights'], $fireflies[$generation][$key + 1]['friction_factors_weights'], $rff, $this->parameters['alpha']);
                        $new_dff = $this->movingFireflyXiToXj($firefly['dynamic_force_factor_weights'], $fireflies[$generation][$key + 1]['dynamic_force_factor_weights'], $dff, $this->parameters['alpha']);

                        $friction_factor = array_product($new_ff);
                        $dynamic_force_factor = array_product($new_dff);
                        $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                        $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                        $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                        $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);
                    } ## End of normal FA

                    if ($this->parameters['firefly_size'] != $key + 1 && $fireflies[$generation][$key + 1]['ae'] > $firefly['ae']) {
                        $selected_female = $this->rouletteWheel($archive_A[$generation], $selection_probabilities[$generation]);

                        $rff = $this->distance($selected_female['friction_factors_weights'], $fireflies[$generation][$key + 1]['friction_factors_weights']);

                        $dff = $this->distance($selected_female['dynamic_force_factor_weights'], $fireflies[$generation][$key + 1]['dynamic_force_factor_weights']);

                        $new_ff = $this->movingFireflyXjToXk($fireflies[$generation][$key + 1]['friction_factors_weights'], $selected_female['friction_factors_weights'], $rff, $alpha[$generation + 1], $generation);

                        $new_dff = $this->movingFireflyXjToXk($fireflies[$generation][$key + 1]['dynamic_force_factor_weights'], $selected_female['dynamic_force_factor_weights'], $dff, $alpha[$generation + 1], $generation);

                        $friction_factor = array_product($new_ff);
                        $dynamic_force_factor = array_product($new_dff);
                        $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                        $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                        $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                        $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);
                    }
                    $fireflies[$generation + 1][$key] = [
                        'friction_factors_weights' => $new_ff,
                        'dynamic_force_factor_weights' => $new_dff,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                } ## End of fireflies

                $global_min_ae = min(array_column($fireflies[$generation + 1], 'ae'));
                //echo $global_min_ae . '<br>';
                $index_global_min = array_search($global_min_ae, array_column($fireflies[$generation + 1], 'ae'));
                //echo $index_global_min . '<br>';
                $global_min = $fireflies[$generation + 1][$index_global_min];
                
                ## Initialize female fireflies in Archive A
                $archive_A[$generation + 1] = $fireflies[$generation + 1];
                ## Initialize selection probabilites
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

    function products($weights)
    {
        $product = 1;
        foreach ($weights as $weight) {
            $product *= $weight;
        }
        return $product;
    }

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->agile($project);
                }
                $ff = $this->averageWeights($results, 'friction_factors_weights');
                $dff = $this->averageWeights($results, 'dynamic_force_factor_weights');

                $friction_factor = $this->products($ff);
                $dynamic_force_factor = $this->products($dff);
                $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                $velocity = $this->velocity($project['Vi'], $deceleration);
                $estimated_time = $this->estimatedTimeInDays($project['effort'], $velocity);
                $absolute_error = $this->absoluteError($estimated_time, $project['actual_time']);

                $ret[] = [
                    'friction_factors_weights' => $ff,
                    'dynamic_force_factor_weights' => $dff,
                    'actual_time' => floatval($project['actual_time']),
                    'estimated_time' => $estimated_time,
                    'ae' => $absolute_error
                ];
                $results = [];
            }
        }
        return $ret;
    }
} ## End of FA-CL Optimizer

$ziauddin_column_indexes = [0, 1, 2, 3, 4, 5, 6];
$ziauddin_columns = ['effort', 'Vi', 'D', 'V', 'sprint_size', 'work_days', 'actual_time'];

$dataset_name = 'ziauddin';
$dataset = [
    'ziauddin' => [
        'file_name' => 'ziauddin.txt',
        'column_indexes' => $ziauddin_column_indexes,
        'columns' => $ziauddin_columns
    ]
];
$firefly_size = 20;
$maximum_generation = 40;
$trials = 1000;
$alpha = 0.5;
$beta_min = 0.2;
$beta = 1;
$gamma = 1;
$fitness = 0.1;
$friction_factors = [
    'ff_team_composition' => 0.91,
    'ff_process' => 0.89,
    'ff_environmental_factors' => 0.96,
    'ff_team_dynamics' => 0.85,
    'max' => 1
];
$dynamic_force_factors = [
    'dff_expected_team_change' => 0.91,
    'dff_introduction_new_tools' => 0.96,
    'dff_vendor_defect' => 0.90,
    'dff_team_member_responsibility' => 0.98,
    'dff_personal_issue' => 0.98,
    'dff_expected_delay' => 0.96,
    'dff_expected_ambiguity' => 0.95,
    'dff_expected_change' => 0.97,
    'dff_expected_relocation' => 0.98,
    'max' => 1
];
$parameters = [
    'firefly_size' => $firefly_size,
    'maximum_generation' => $maximum_generation,
    'trials' => $trials,
    'fitness' => $fitness,
    'alpha' => $alpha,
    'beta_min' => $beta_min,
    'beta' => $beta,
    'gamma' => $gamma,
    'min_epsilon' => -5,
    'max_epsilon' => 5,
    'friction_factors' => $friction_factors,
    'dynamic_force_factors' => $dynamic_force_factors
];

$optimize = new FireflyCLOptimizer($dataset, $parameters, $dataset_name);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 21;

echo '<p>';
foreach ($optimized as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<p>';
    // $data = array($result['actual_time'], $result['estimated_time'], $result['ae']);
    // $fp = fopen('hasil_era_estimated.txt', 'a');
    // fputcsv($fp, $data);
    // fclose($fp);
}
