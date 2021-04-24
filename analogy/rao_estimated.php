<?php
set_time_limit(1000000);

class Raoptimizer
{
    protected $dataset;
    protected $parameters;
    protected $productivity_factor;
    protected $dataset_name;
    protected $normalized_columns;

    function __construct($dataset, $parameters, $productivity_factor, $dataset_name, $normalized_columns)
    {
        $this->dataset = $dataset;
        $this->parameters = $parameters;
        $this->productivity_factor = $productivity_factor;
        $this->dataset_name = $dataset_name;
        $this->normalized_columns = $normalized_columns;
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

    function normalization()
    {
        $datasets = $this->prepareDataset();
        $min_t14 = floatval(min(array_column($datasets, $this->normalized_columns['t14'])));
        $max_t14 = floatval(max(array_column($datasets, $this->normalized_columns['t14'])));
        $min_duration = floatval(min(array_column($datasets, $this->normalized_columns['duration'])));
        $max_duration = floatval(max(array_column($datasets, $this->normalized_columns['duration'])));
        $min_size = floatval(min(array_column($datasets, $this->normalized_columns['size'])));
        $max_size = floatval(max(array_column($datasets, $this->normalized_columns['size'])));
        $min_actual_effort = floatval(min(array_column($datasets, $this->normalized_columns['effort'])));
        $max_actual_effort = floatval(max(array_column($datasets, $this->normalized_columns['effort'])));

        foreach ($datasets as $dataset) {
            $t14 = ($dataset['t14'] - $min_t14) / ($max_t14 - $min_t14);
            $duration = ($dataset['duration'] - $min_duration) / ($max_duration - $min_duration);
            $size = ($dataset['size'] - $min_size) / ($max_size - $min_size);
            if ($size == 0){
                $size = 0.00001;
            }
            $actual_effort = (floatval($dataset['effort']) - $min_actual_effort) / ($max_actual_effort - $min_actual_effort);
            $ret[] = ['t14' => $t14, 'duration' => $duration, 'size' => $size, 'effort' => $actual_effort];
        }
        return $ret;
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
        $t14 = ($dataset[$ret[0]['id']]['t14'] + $dataset[$ret[1]['id']]['t14']) / 2;
        $duration = ($dataset[$ret[0]['id']]['duration'] + $dataset[$ret[1]['id']]['duration']) / 2;
        $size = ($dataset[$ret[0]['id']]['size'] + $dataset[$ret[1]['id']]['size']) / 2;
        return array('t14' => $t14, 'duration' => $duration, 'size' => $size);  ## 2 most similar project a.k.a analogus
    }

    function effortEstimation($analog, $target_project)
    {
        return ($target_project['effort'] / $target_project['size']) * $analog['size'];
    }

    function absoluteError($estimated, $actual)
    {
        return abs($estimated - $actual);
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
                    //$candidates = $this->candidating($particles[$generation], $individu);

                    ## Rao-1 
                    $t14 = $individu['weights']['t14'] + $r1 * ($Xbest[$generation]['weights']['t14'] - $Xworst[$generation]['weights']['t14']);
                    $duration = $individu['weights']['duration'] + $r1 * ($Xbest[$generation]['weights']['duration'] - $Xworst[$generation]['weights']['duration']);
                    $size = $individu['weights']['size'] + $r1 * ($Xbest[$generation]['weights']['size'] - $Xworst[$generation]['weights']['size']);

                    ## Rao-1 Chaotic
                    // $xSimple = $particles[$generation][$i]['xSimple'] + $chaotic[$generation] * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']);
                    // $xAverage = $particles[$generation][$i]['xAverage'] + $chaotic[$generation] * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']);
                    // $xComplex = $particles[$generation][$i]['xComplex'] + $chaotic[$generation] * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']);

                    ## Rao-2
                    // $xSimple = $individu['xSimple'] + $r1 * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']) + ($r2 * (abs($candidates['xSimple1']) - abs($candidates['xSimple2'])));
                    // $xAverage = $individu['xAverage'] + $r1 * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']) + ($r2 * (abs($candidates['xAverage1']) - abs($candidates['xAverage2'])));
                    // $xComplex = $individu['xComplex'] + $r1 * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']) + ($r2 * (abs($candidates['xComplex1']) - abs($candidates['xComplex2'])));

                    ## Rao-3 
                    // $xSimple = $individu['xSimple'] + $r1 * ($Xbest[$generation]['xSimple'] - abs($Xworst[$generation]['xSimple'])) + ($r2 * (abs($candidates['xSimple1']) - $candidates['xSimple2']));
                    // $xAverage = $individu['xAverage'] + $r1 * ($Xbest[$generation]['xAverage'] - abs($Xworst[$generation]['xAverage'])) + ($r2 * (abs($candidates['xAverage1']) - $candidates['xAverage2']));
                    // $xComplex = $individu['xComplex'] + $r1 * ($Xbest[$generation]['xComplex'] - abs($Xworst[$generation]['xComplex'])) + ($r2 * (abs($candidates['xComplex1']) - $candidates['xComplex2']));

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
        $normalized_dataset = $this->normalization();
        foreach ($normalized_dataset as $key => $target_project) {
            if ($key >= 0) {
                for ($i = 0; $i <= $this->parameters['trials'] - 1; $i++) {
                    $results[] = $this->analogy($normalized_dataset, $target_project);
                }

                $t14 = array_sum(array_column($results, 'analog_t14')) / $this->parameters['trials'];
                $duration = array_sum(array_column($results, 'analog_duration')) / $this->parameters['trials'];
                $size = array_sum(array_column($results, 'analog_size')) / $this->parameters['trials'];
                $positions =

                    $positions = ['t14' => $t14, 'duration' => $duration, 'size' => $size];
                $analog = $this->manhattanDistance($target_project, $normalized_dataset, $positions);
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
$trials = 10;
$fitness = 0.5;
$parameters = ['particle_size' => $particle_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 'fitness' => $fitness];
$productivity_factor = 20;
$normalized_columns = ['t14' => 't14', 'duration' => 'duration', 'size' => 'size', 'effort' => 'effort'];

$optimize = new Raoptimizer($dataset, $parameters, $productivity_factor, $dataset_name, $normalized_columns);
$optimized = $optimize->processingDataset();

echo 'MAE: ' . array_sum(array_column($optimized, 'ae')) / 62;

echo '<p>';
foreach ($optimized as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<br>';
    // $data = array($result['xSimple'], $result['xAverage'], $result['xComplex'], $result['estimatedEffort'], floatval($result['actualEffort']), $result['ae']);
    // $fp = fopen('hasil_rao_estimated.txt', 'a');
    // fputcsv($fp, $data);
    // fclose($fp);
}
