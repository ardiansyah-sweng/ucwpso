<?php
set_time_limit(1000000);

class Raoptimizer
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

    function estimatedTimeInDays($effort, $velocity)
    {
        return $effort / $velocity;
    }

    function absoluteError($estimated, $actual)
    {
        return abs($estimated - floatval($actual));
    }

    function candidating($particles, $individu)
    {
        $count = 0;
        foreach ($this->parameters['friction_factors'] as $key => $friction_factor) {
            $count = $count + 1;
            if ($count < count($this->parameters['friction_factors'])) {
                for ($i = 1; $i <= 2; $i++) {
                    $ae_candidate = $particles[array_rand($particles)];

                    if ($individu['ae'] > $ae_candidate['ae']) {
                        $candidate = $ae_candidate['friction_factors_weights'][$key];
                    }
                    if ($individu['ae'] < $ae_candidate['ae'] || $individu['ae'] == $ae_candidate['ae']) {
                        $candidate = $individu['friction_factors_weights'][$key];
                    }
                    $candidate_ff_weights[$key][] = $candidate;
                    $ae_candidate = $particles[array_rand($particles)];
                }
            }
        }
        $count = 0;
        foreach ($this->parameters['dynamic_force_factors'] as $key => $dynamic_force_factor) {
            $count = $count + 1;
            if ($count < count($this->parameters['dynamic_force_factors'])) {
                for ($i = 1; $i <= 2; $i++) {
                    if ($individu['ae'] > $ae_candidate['ae']) {
                        $candidate = $ae_candidate['dynamic_force_factor_weights'][$key];
                    }
                    if ($individu['ae'] < $ae_candidate['ae'] || $individu['ae'] == $ae_candidate['ae']) {
                        $candidate = $individu['dynamic_force_factor_weights'][$key];
                    }
                    $candidate_dff_weights[$key][] = $candidate;
                    $ae_candidate = $particles[array_rand($particles)];
                }
            }
        }
        $ret['friction_factors'][] = $candidate_ff_weights;
        $ret['dynamic_force_factor'][] = $candidate_dff_weights;
        return $ret;
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

    function products($weights)
    {
        $product = 1;
        foreach ($weights as $weight) {
            $product *= $weight;
        }
        return $product;
    }

    function deceleration($friction_factor, $dynamic_force_factor)
    {
        return $friction_factor * $dynamic_force_factor;
    }

    function velocity($Vi, $deceleration)
    {
        return pow($Vi, $deceleration);
    }

    function updateWeights($weights, $r1, $r2, $candidate_weights, $Xbest, $Xworst, $rao, $factor)
    {
        foreach ($weights as $key => $weight) {
            $ret[$key] = $weight + $r1 * ($Xbest[$key] - abs($Xworst[$key])) + ($r2 * (abs($candidate_weights[0][$key][0]) - $candidate_weights[0][$key][1]));
            if ($ret[$key] < $this->parameters[$factor][$key]) {
                $ret[$key] = $this->parameters[$factor][$key];
            }
            if ($ret[$key] > $this->parameters[$factor]['max']) {
                $ret[$key] = $this->parameters[$factor]['max'];
            }
        }
        return $ret;
    }

    function averageWeights($weights, $factor)
    {
        if ($factor == 'friction_factors_weights') {
            foreach ($weights as $weight) {
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

    function qualityEvalution($particles)
    {
        array_multisort(array_column($particles, 'ae'), SORT_ASC, $particles);
        return $particles;
    }

    function splitting_particles($sorted_particles, $portion)
    {
        foreach ($sorted_particles as $key => $particle) {
            if ($key < count($sorted_particles) * $portion) {
                $ret['hq'][] = $particle;
            }
            if ($key >= count($sorted_particles) * $portion) {
                $ret['lq'][] = $particle;
            }
        }
        return $ret;
    }

    function crossover($bestHQs, $Xbests, $r, $target_projects)
    {

        foreach ($bestHQs['friction_factors_weights'] as $key => $ffw) {
            $ff_bestHQ[$key] = ($r * $ffw) + (1 - $r) * $Xbests['friction_factors_weights'][$key];
        }
        foreach ($bestHQs['dynamic_force_factor_weights'] as $key => $dff) {
            $dff_bestHQ[$key] = ($r * $dff) + (1 - $r) * $Xbests['dynamic_force_factor_weights'][$key];
        }

        foreach ($Xbests['friction_factors_weights'] as $key => $ffw) {
            $ff_Xbests[$key] = ($r * $ffw) + (1 - $r) * $bestHQs['friction_factors_weights'][$key];
        }
        foreach ($Xbests['dynamic_force_factor_weights'] as $key => $ffw) {
            $dff_Xbests[$key] = ($r * $ffw) + (1 - $r) * $bestHQs['dynamic_force_factor_weights'][$key];
        }

        $friction_factor = $this->products($ff_bestHQ);
        $dynamic_force_factor = $this->products($dff_bestHQ);
        $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
        $velocity = $this->velocity($target_projects['Vi'], $deceleration);
        $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
        $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

        $ret['bestHQ'] = [
            'friction_factors_weights' => $ff_bestHQ,
            'dynamic_force_factor_weights' => $dff_bestHQ,
            'actual_time' => $target_projects['actual_time'],
            'estimated_time' => $estimated_time,
            'ae' => $absolute_error
        ];

        $friction_factor = $this->products($ff_Xbests);
        $dynamic_force_factor = $this->products($dff_Xbests);
        $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
        $velocity = $this->velocity($target_projects['Vi'], $deceleration);
        $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
        $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

        $ret['Xbest'] = [
            'friction_factors_weights' => $ff_Xbests,
            'dynamic_force_factor_weights' => $dff_Xbests,
            'actual_time' => $target_projects['actual_time'],
            'estimated_time' => $estimated_time,
            'ae' => $absolute_error
        ];
        return $ret;
    }

    function mutation($Xbests, $r1, $mutation_radius, $target_projects)
    {
        foreach ($Xbests['friction_factors_weights'] as $key => $ffw) {
            $friction_factor_weights[$key] = $ffw + (2 * $r1 - 1) * ($mutation_radius * abs($this->parameters['friction_factors']['max'] - $this->parameters['friction_factors'][$key]));
        }
        foreach ($Xbests['dynamic_force_factor_weights'] as $key => $dff) {
            $dynamic_force_factor_weights[$key] = $dff + (2 * $r1 - 1) * ($mutation_radius * abs($this->parameters['dynamic_force_factors']['max'] - $this->parameters['dynamic_force_factors'][$key]));
        }

        $friction_factor = $this->products($friction_factor_weights);
        $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
        $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
        $velocity = $this->velocity($target_projects['Vi'], $deceleration);
        $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
        $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

        return [
            'friction_factors_weights' => $friction_factor_weights,
            'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
            'actual_time' => $target_projects['actual_time'],
            'estimated_time' => $estimated_time,
            'ae' => $absolute_error
        ];
    }

    function randomWalk($LQ, $HQ_particle, $r, $target_projects)
    {
        foreach ($LQ as $particle) {
            foreach ($particle['friction_factors_weights'] as $key => $ff) {
                $friction_factor_weights[$key] = $ff + $r * ($HQ_particle['friction_factors_weights'][$key] - $ff);
            }

            foreach ($particle['dynamic_force_factor_weights'] as $key => $dff) {
                $dynamic_force_factor_weights[$key] = $dff + $r * ($HQ_particle['dynamic_force_factor_weights'][$key] - $dff);
            }
            $friction_factor = $this->products($friction_factor_weights);
            $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
            $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
            $velocity = $this->velocity($target_projects['Vi'], $deceleration);
            $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
            $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

            return [
                'friction_factors_weights' => $friction_factor_weights,
                'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                'actual_time' => $target_projects['actual_time'],
                'estimated_time' => $estimated_time,
                'ae' => $absolute_error
            ];
        }
    }

    function delta($f1, $f2, $f3)
    {
        $ret['delta_f1'] = abs($f1 - $f2) / $f1;
        $ret['delta_f2'] = abs($f2 - $f3) / $f2;
        return $ret;
    }

    function updatingHQPortions($f1, $f2, $f3, $portions)
    {
        $delta_f1 = $this->delta($f1, $f2, $f3)['delta_f1'];
        $delta_f2 = $this->delta($f1, $f2, $f3)['delta_f2'];

        $result = $portions * (1 - (($delta_f1 + $delta_f2) / 2));

        if ($delta_f1 > 0 && $delta_f2 > 0 && $result > 0.9) {
            return 0.9;
        }
        if ($delta_f1 > 0 && $delta_f2 > 0 && $result < 0.1) {
            return 0.1;
        }
        if ($delta_f1 === 0 && $delta_f2 === 0) {
            return $portions * 0.97;
        }
        return $result;
    }

    function updatingMutationRadius($f1, $f2, $f3, $mutation_radius)
    {
        $delta_f1 = $this->delta($f1, $f2, $f3)['delta_f1'];
        $delta_f2 = $this->delta($f1, $f2, $f3)['delta_f2'];

        if ($delta_f1 > 0 && $delta_f2 > 0) {
            return $mutation_radius * 0.97;
        }
        if ($delta_f1 == 0 && $delta_f2 == 0) {
            return $mutation_radius * 1.03;
        }
        return $this->a;
    }

    function updatingMutationRate($f1, $f2, $f3, $mutation_rate)
    {
        $delta_f1 = $this->delta($f1, $f2, $f3)['delta_f1'];
        $delta_f2 = $this->delta($f1, $f2, $f3)['delta_f2'];

        if ($delta_f1 > 0 && $delta_f2 > 0) {
            return $mutation_rate * 0.97;
        }
        if ($delta_f1 == 0 && $delta_f2 == 0) {
            return $mutation_rate * 1.03;
        }
        return $this->b;
    }


    function agile($target_projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();
            $r1_mutation = $this->randomZeroToOne();
            $r2_mutation = $this->randomZeroToOne();

            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->parameters['particle_size'] - 1; $i++) {
                    //$chaotic[$generation + 1] = $this->singer(0.8);

                    $friction_factor_weights = $this->frictionFactorsRandomWeight();
                    $dynamic_force_factor_weights = $this->dynamicForceFactorsRandomWeight();
                    $friction_factor = $this->products($friction_factor_weights);
                    $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
                    $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                    $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                    $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                    $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

                    $particles[$generation + 1][$i] = [
                        'friction_factors_weights' => $friction_factor_weights,
                        'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                }
                $mutation_rate[$generation + 1] = $this->parameters['b'];
            } ## End if generation = 0

            if ($generation > 0) {
                //$chaotic[$generation + 1] = $this->singer($chaotic[$generation]);
                $sorted_particles = $this->qualityEvalution($particles[$generation]);
                $Xbest[$generation] = $this->minimalAE($sorted_particles);
                $Xworst[$generation] = $this->maximalAE($sorted_particles);
                array_shift($sorted_particles); //remove top element of array

                if ($generation <= 3 && $generation > 0) {
                    $portions[$generation] = $this->parameters['s'];
                    $mutation_radius[$generation] = $this->parameters['a'];
                    $mutation_rate[$generation] = $this->parameters['b'];
                    $splitted_particles = $this->splitting_particles($sorted_particles, $portions[$generation]);
                }

                ## Entering Rao algorithm for HQ population
                foreach ($splitted_particles['hq'] as $i => $individu) {
                    //$candidates = $this->candidating($particles[$generation], $individu);

                    ## Rao-3
                    $candidates = $this->candidating($splitted_particles['hq'], $individu);
                    $friction_factor_weights = $this->updateWeights(
                        $individu['friction_factors_weights'],
                        $r1,
                        $r2,
                        $candidates['friction_factors'],
                        $Xbest[$generation]['friction_factors_weights'],
                        $Xworst[$generation]['friction_factors_weights'],
                        'rao-3',
                        'friction_factors'
                    );
                    $dynamic_force_factor_weights = $this->updateWeights(
                        $individu['dynamic_force_factor_weights'],
                        $r1,
                        $r2,
                        $candidates['dynamic_force_factor'],
                        $Xbest[$generation]['dynamic_force_factor_weights'],
                        $Xworst[$generation]['dynamic_force_factor_weights'],
                        'rao-3',
                        'dynamic_force_factors'
                    );

                    $friction_factor = $this->products($friction_factor_weights);
                    $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
                    $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                    $velocity = $this->velocity($target_projects['Vi'], $deceleration);
                    $estimated_time = $this->estimatedTimeInDays($target_projects['effort'], $velocity);
                    $absolute_error = $this->absoluteError($estimated_time, $target_projects['actual_time']);

                    $hq[$generation][$i] = [
                        'friction_factors_weights' => $friction_factor_weights,
                        'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                        'actual_time' => $target_projects['actual_time'],
                        'estimated_time' => $estimated_time,
                        'ae' => $absolute_error
                    ];
                }
                $bestHQ[$generation] = $this->minimalAE($hq[$generation]);
                $index_hq = array_rand($splitted_particles['hq']);
                $HQ_particle = $splitted_particles['hq'][$index_hq];

                if ($generation > 3) {
                    $f1 = $Xbest[$generation - 2]['ae'];
                    $f2 = $Xbest[$generation - 1]['ae'];
                    $f3 = $Xbest[$generation]['ae'];

                    $portions[$generation] = $this->updatingHQPortions($f1, $f2, $f3, $this->parameters['s']);
                    $mutation_radius[$generation] = $this->updatingMutationRadius($f1, $f2, $f3, $this->parameters['a']);
                    $mutation_rate[$generation] = $this->updatingMutationRate($f1, $f2, $f3, $this->parameters['b']);
                }

                $r = $this->randomZeroToOne();
                ## Crossover
                if ($r > 0.5) { // TODO add check funcion if r = 0.5
                    $offspring = $this->crossover($bestHQ[$generation], $Xbest[$generation], $r, $target_projects);
                    $Xbest[$generation + 1] = $offspring['Xbest'];
                    $bestHQ[$generation + 1] = $offspring['bestHQ'];
                }
                ## Mutation
                if ($r < 0.5 && $r2_mutation < $this->parameters['b']) {
                    $Xbest[$generation + 1] = $this->mutation($Xbest[$generation], $r1_mutation, $mutation_radius[$generation], $target_projects);
                }
                $r_random_walk = $this->randomZeroToOne();
                $LQ = $this->randomWalk($splitted_particles['lq'], $HQ_particle, $r_random_walk, $target_projects);

                $particles[$generation + 1] = array_merge($hq[$generation], $LQ);

                ## Fitness evaluations
                if ($Xbest[$generation]['ae'] < $this->parameters['fitness']) {
                    return $Xbest[$generation];
                } else {
                    $results[] = $Xbest[$generation];
                }
            } ## End of if generation > 0
        } ## End of Generation
        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    }

    function processingDataset()
    {
        $data_set = $this->prepareDataset();
        foreach ($data_set as $key => $target_project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->agile($target_project);
                }
                $friction_factor_weights = $this->averageWeights($results, 'friction_factors_weights');
                $dynamic_force_factor_weights = $this->averageWeights($results, 'dynamic_force_factor_weights');
                $friction_factor = $this->products($friction_factor_weights);
                $dynamic_force_factor = $this->products($dynamic_force_factor_weights);
                $deceleration = $this->deceleration($friction_factor, $dynamic_force_factor);
                $velocity = $this->velocity($target_project['Vi'], $deceleration);
                $estimated_time = $this->estimatedTimeInDays($target_project['effort'], $velocity);
                $absolute_error = $this->absoluteError($estimated_time, $target_project['actual_time']);

                $ret[] = [
                    'friction_factors_weights' => $friction_factor_weights,
                    'dynamic_force_factor_weights' => $dynamic_force_factor_weights,
                    'actual_time' => floatval($target_project['actual_time']),
                    'estimated_time' => $estimated_time,
                    'ae' => $absolute_error
                ];
                $results = [];
            }
        }
        return $ret;
    }
} ## End of Raoptimizer

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
$particle_size = 60;
$maximum_generation = 40;
$trials = 10;
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
    'particle_size' => $particle_size,
    'maximum_generation' => $maximum_generation,
    'trials' => $trials,
    'fitness' => $fitness,
    'friction_factors' => $friction_factors,
    'dynamic_force_factors' => $dynamic_force_factors,
    's' => 0.5,
    'a' => 0.5,
    'b' => 0.9
];

$optimize = new Raoptimizer($dataset, $parameters, $dataset_name);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 21;

echo '<p>';
foreach ($optimized as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<br>';
    $data = array($result['actual_time'], $result['estimated_time'], $result['ae']);
    $fp = fopen('hasil_era_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
