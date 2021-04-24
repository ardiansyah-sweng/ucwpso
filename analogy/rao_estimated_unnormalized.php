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

    function singer($value)
    {
        return 1.07 * ((7.86 * $value) - (23.31 * POW($value, 2)) + (28.75 * POW($value, 3)) - (13.302875 * POW($value, 4)));
    }

    function sinu($chaos_value)
    {
        return (2.3 * POW($chaos_value, 2)) * sin(pi() * $chaos_value);
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

    function manhattanDistance($target, $dataset, $weights)
    {
        foreach ($dataset as $key => $data) {
            if ($data !== $target) {
                $t14 = $weights['t14'] * abs($data['t14'] - $target['t14']);
                $duration = $weights['duration'] * abs($data['duration'] - $target['duration']);
                $size = $weights['size'] * abs($data['size'] - $target['size']);
                $ret[] = ['distance' => ($t14 + $duration + $size), 'id' => $key];
            }
        }
        array_multisort($ret, SORT_ASC);
        $t14 = ($dataset[$ret[0]['id']]['t14'] + $dataset[$ret[1]['id']]['t14'] + $dataset[$ret[2]['id']]['t14']) / 3;
        $duration = ($dataset[$ret[0]['id']]['duration'] + $dataset[$ret[1]['id']]['duration'] + $dataset[$ret[2]['id']]['duration']) / 3;
        $size = ($dataset[$ret[0]['id']]['size'] + $dataset[$ret[1]['id']]['size'] + $dataset[$ret[2]['id']]['size']) / 3;
        return array('t14' => $t14, 'duration' => $duration, 'size' => $size);  ## 2 most similar project a.k.a analogus
    }

    function effortEstimation($analog, $target_project)
    {
        return (floatval($target_project['effort']) / floatval($target_project['size'])) * $analog['size'];
    }

    function absoluteError($estimated, $actual)
    {
        return abs($estimated - floatval($actual));
    }

    function candidating($particles, $individu)
    {
        $ae_candidate_t141 = $particles[$key_t141 = array_rand($particles)]['ae'];
        $ae_candidate_t142 = $particles[$key_t142 = array_rand($particles)]['ae'];
        $ae_candidate_duration1 = $particles[$key_duration1 = array_rand($particles)]['ae'];
        $ae_candidate_duration2 = $particles[$key_duration2 = array_rand($particles)]['ae'];
        $ae_candidate_size1 = $particles[$key_size1 = array_rand($particles)]['ae'];
        $ae_candidate_size2 = $particles[$key_size2 = array_rand($particles)]['ae'];

        if ($individu['ae'] > $ae_candidate_t141) {
            $t141 = $particles[$key_t141]['weights']['t14'];
        }
        if ($individu['ae'] < $ae_candidate_t141 || $individu['ae'] == $ae_candidate_t141) {
            $t141 = $individu['weights']['t14'];
        }
        if ($individu['ae'] > $ae_candidate_t142) {
            $t142 = $particles[$key_t142]['weights']['t14'];
        }
        if ($individu['ae'] < $ae_candidate_t142 || $individu['ae'] == $ae_candidate_t142) {
            $t142 = $individu['weights']['t14'];
        }

        if ($individu['ae'] > $ae_candidate_duration1) {
            $duration1 = $particles[$key_duration1]['weights']['duration'];
        }
        if ($individu['ae'] < $ae_candidate_duration1 || $individu['ae'] == $ae_candidate_duration1) {
            $duration1 = $individu['weights']['duration'];
        }
        if ($individu['ae'] > $ae_candidate_duration2) {
            $duration2 = $particles[$key_duration2]['weights']['duration'];
        }
        if ($individu['ae'] < $ae_candidate_duration2 || $individu['ae'] == $ae_candidate_duration2) {
            $duration2 = $individu['weights']['duration'];
        }

        if ($individu['ae'] > $ae_candidate_size1) {
            $size1 = $particles[$key_size1]['weights']['size'];
        }
        if ($individu['ae'] < $ae_candidate_size1 || $individu['ae'] == $ae_candidate_size1) {
            $size1 = $individu['weights']['size'];
        }
        if ($individu['ae'] > $ae_candidate_size2) {
            $size2 = $particles[$key_size2]['weights']['size'];
        }
        if ($individu['ae'] < $ae_candidate_size2 || $individu['ae'] == $ae_candidate_size2) {
            $size2 = $individu['weights']['size'];
        }

        return [
            't141' => $t141, 't142' => $t142,
            'duration1' => $duration1, 'duration2' => $duration2,
            'size1' => $size1, 'size2' => $size2
        ];
    }

    function analogy($dataset, $target_projects)
    {

        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();

            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->parameters['particle_size'] - 1; $i++) {
                    $chaotic[$generation + 1] = $this->singer(0.8);
                    $weights = array(
                        't14' => $this->randomZeroToOne(),
                        'duration' => $this->randomZeroToOne(),
                        'size' => $this->randomZeroToOne()
                    );
                    $analog = $this->manhattanDistance($target_projects, $dataset, $weights);
                    $estimated_effort = $this->effortEstimation($analog, $target_projects);
                    $absolute_error = $this->absoluteError($estimated_effort, $target_projects['effort']);
                    $particles[$generation + 1][$i] = [
                        'actual_t14' => $target_projects['t14'],
                        'analog_t14' => $analog['t14'],
                        'actual_duration' => $target_projects['duration'],
                        'analog_duration' => $analog['duration'],
                        'actual_size' => $target_projects['size'],
                        'analog_size' => $analog['size'],
                        'actual_effort' => $target_projects['effort'],
                        'estimated_effort' => $estimated_effort,
                        'ae' => $absolute_error,
                        'weights' => $weights
                    ];
                }

                $Xbest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
                $Xworst[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            } ## End if generation = 0

            if ($generation > 0) {
                $chaotic[$generation + 1] = $this->singer($chaotic[$generation]);

                ## Entering Rao algorithm for HQ population
                foreach ($particles[$generation] as $i => $individu) {
                    $candidates = $this->candidating($particles[$generation], $individu);

                    ## Rao-1 
                    // $t14 = $individu['weights']['t14'] + $r1 * ($Xbest[$generation]['weights']['t14'] - $Xworst[$generation]['weights']['t14']);
                    // $duration = $individu['weights']['duration'] + $r1 * ($Xbest[$generation]['weights']['duration'] - $Xworst[$generation]['weights']['duration']);
                    // $size = $individu['weights']['size'] + $r1 * ($Xbest[$generation]['weights']['size'] - $Xworst[$generation]['weights']['size']);

                    ## Rao-1 Chaotic
                    // $xSimple = $particles[$generation][$i]['xSimple'] + $chaotic[$generation] * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']);
                    // $xAverage = $particles[$generation][$i]['xAverage'] + $chaotic[$generation] * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']);
                    // $xComplex = $particles[$generation][$i]['xComplex'] + $chaotic[$generation] * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']);

                    ## Rao-2
                    // $t14 = $individu['weights']['t14']  + $r1 * ($Xbest[$generation]['weights']['t14']  - $Xworst[$generation]['weights']['t14']) + ($r2 * (abs($candidates['t141']) - abs($candidates['t142'])));
                    // $duration = $individu['weights']['duration'] + $r1 * ($Xbest[$generation]['weights']['duration'] - $Xworst[$generation]['weights']['duration']) + ($r2 * (abs($candidates['duration1']) - abs($candidates['duration2'])));
                    // $size = $individu['weights']['size'] + $r1 * ($Xbest[$generation]['weights']['size'] - $Xworst[$generation]['weights']['size']) + ($r2 * (abs($candidates['size1']) - abs($candidates['size2'])));

                    ## Rao-3 
                    $t14 = $individu['weights']['t14']  + $r1 * ($Xbest[$generation]['weights']['t14'] - abs($Xworst[$generation]['weights']['t14'] )) + ($r2 * (abs($candidates['t141']) - $candidates['t142']));
                    $duration = $individu['weights']['duration']  + $r1 * ($Xbest[$generation]['weights']['duration']  - abs($Xworst[$generation]['weights']['duration'] )) + ($r2 * (abs($candidates['duration1']) - $candidates['duration2']));
                    $size = $individu['weights']['size'] + $r1 * ($Xbest[$generation]['weights']['size'] - abs($Xworst[$generation]['weights']['size'])) + ($r2 * (abs($candidates['size1']) - $candidates['size2']));

                    ## Rao-3 chaotic
                    // $xSimple = $individu['xSimple'] + $chaotic[$generation] * ($Xbest[$generation]['xSimple'] - abs($Xworst[$generation]['xSimple'])) + ($chaotic[$generation] * (abs($xSimple1) - $xSimple2));
                    // $xAverage = $individu['xAverage'] + $chaotic[$generation] * ($Xbest[$generation]['xAverage'] - abs($Xworst[$generation]['xAverage'])) + ($chaotic[$generation] * (abs($xAverage1) - $xAverage2));
                    // $xComplex = $individu['xComplex'] + $chaotic[$generation] * ($Xbest[$generation]['xComplex'] - abs($Xworst[$generation]['xComplex'])) + ($chaotic[$generation] * (abs($xComplex1) - $xComplex2));

                    if ($t14 < -1) {
                        $t14 = -1;
                    }
                    if ($t14 > 1) {
                        $t14 = 1;
                    }
                    if ($duration < -1) {
                        $duration = -1;
                    }
                    if ($duration > 1) {
                        $duration = 1;
                    }
                    if ($size < -1) {
                        $size = -1;
                    }
                    if ($size > 1) {
                        $size = 1;
                    }

                    $positions = ['t14' => $t14, 'duration' => $duration, 'size' => $size];

                    $analog = $this->manhattanDistance($target_projects, $dataset, $positions);
                    $estimated_effort = $this->effortEstimation($analog, $target_projects);
                    $absolute_error = $this->absoluteError($estimated_effort, $target_projects['effort']);
                    $particles[$generation + 1][$i] = [
                        'actual_t14' => $target_projects['t14'],
                        'analog_t14' => $analog['t14'],
                        'actual_duration' => $target_projects['duration'],
                        'analog_duration' => $analog['duration'],
                        'actual_size' => $target_projects['size'],
                        'analog_size' => $analog['size'],
                        'actual_effort' => $target_projects['effort'],
                        'estimated_effort' => $estimated_effort,
                        'ae' => $absolute_error,
                        'weights' => $weights
                    ];
                }

                ## Fitness evaluations
                if ($Xbest[$generation]['ae'] < $this->parameters['fitness']) {
                    return $Xbest[$generation];
                } else {
                    $results[] = $Xbest[$generation];
                }

                $Xbest[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
                $Xworst[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
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
                    $results[] = $this->analogy($data_set, $target_project);
                }

                $t14 = array_sum(array_column($results, 'analog_t14')) / $this->parameters['trials'];
                $duration = array_sum(array_column($results, 'analog_duration')) / $this->parameters['trials'];
                $size = array_sum(array_column($results, 'analog_size')) / $this->parameters['trials'];
                $positions =

                    $positions = ['t14' => $t14, 'duration' => $duration, 'size' => $size];
                $analog = $this->manhattanDistance($target_project, $data_set, $positions);
                $estimated_effort = $this->effortEstimation($analog, $target_project);
                $absolute_error = $this->absoluteError($estimated_effort, $target_project['effort']);

                $ret[] = [
                    'actual_t14' => $target_project['t14'],
                    'analog_t14' => $analog['t14'],
                    'actual_duration' => $target_project['duration'],
                    'analog_duration' => $analog['duration'],
                    'actual_size' => $target_project['size'],
                    'analog_size' => $analog['size'],
                    'actual_effort' => $target_project['effort'],
                    'estimated_effort' => $estimated_effort,
                    'ae' => $absolute_error,
                    'weights' => $positions
                ];

                $results = [];
            }
        }
        return $ret;
    }
} ## End of Raoptimizer

$maxwell_column_indexes = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25];
$maxwell_columns = ['time', 'app', 'har', 'dba', 'ifc', 'source', 'telonuse', 'nlan', 't01', 't02', 't03', 't04', 't05', 't06', 't07', 't08', 't09', 't10', 't11', 't12', 't13', 't14', 't15', 'duration', 'size', 'effort'];

$dataset_name = 'maxwell';
$dataset = [
    'maxwell' => [
        'file_name' => 'maxwell.txt',
        'column_indexes' => $maxwell_column_indexes,
        'columns' => $maxwell_columns
    ]
];
$particle_size = 20;
$maximum_generation = 40;
$trials = 1000;
$fitness = 10;
$parameters = ['particle_size' => $particle_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 'fitness' => $fitness];

$optimize = new Raoptimizer($dataset, $parameters, $dataset_name);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 62;

echo '<p>';
foreach ($optimized as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<br>';
    $data = array($result['analog_t14'], $result['analog_duration'], $result['analog_size'], $result['estimated_effort'], floatval($result['actual_effort']), $result['ae']);
    $fp = fopen('hasil_rao_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
