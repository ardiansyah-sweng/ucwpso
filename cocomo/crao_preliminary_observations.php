<?php
set_time_limit(1000000);
include '../chaotic_interface.php';

class Raoptimizer
{
    protected $dataset_name;
    protected $scales;
    protected $particle_size;
    protected $maximum_generation;
    protected $lower_bound;
    protected $upper_bound;
    protected $trials;
    protected $fitness;
    protected $chaotic_type;

    function __construct($dataset_name, $scales, $particle_size, $maximum_generation, $lower_bound, $upper_bound, $trials, $fitness, $chaotic_type)
    {
        $this->dataset_name = $dataset_name;
        $this->scales = $scales;
        $this->particle_size = $particle_size;
        $this->maximum_generation = $maximum_generation;
        $this->lower_bound = $lower_bound;
        $this->upper_bound = $upper_bound;
        $this->trials = $trials;
        $this->fitness = $fitness;
        $this->chaotic_type = $chaotic_type;
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

    function scaleEffortExponent($B, $scale_factors)
    {
        return $B + 0.01 * array_sum($scale_factors);
    }

    function estimating($A, $size, $E, $effort_multipliers)
    {
        return $A * pow($size, $E) * array_product($effort_multipliers);
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

    function candidating($particles, $individu)
    {
        for ($i = 1; $i <= 2; $i++) {
            $ae_candidate = $particles[array_rand($particles)];

            if ($individu['ae'] > $ae_candidate['ae']) {
                $candidate = $ae_candidate['A'];
            }
            if ($individu['ae'] < $ae_candidate['ae'] || $individu['ae'] == $ae_candidate['ae']) {
                $candidate = $individu['A'];
            }
            $ret[$i] = $candidate;
        }
        return $ret;
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

        for ($generation = 0; $generation <= $this->maximum_generation - 1; $generation++) {
            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic($this->chaotic_type, $generation);

            ## Generate population
            if ($generation === 0) {
                $r1[$generation + 1] = $chaos->chaotic($this->randomZeroToOne());
                $r2[$generation + 1] = $chaos->chaotic($this->randomZeroToOne());
                $B[$generation + 1] = $chaos->chaotic($this->randomZeroToOne());
                for ($i = 0; $i <= $this->particle_size - 1; $i++) {

                    $A = mt_rand($this->lower_bound * 100, $this->upper_bound * 100) / 100;
                    $E = $this->scaleEffortExponent($B[$generation + 1], $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $particles[$generation + 1][$i]['A'] = $A;
                    $particles[$generation + 1][$i]['B'] = $B[$generation + 1];
                    $particles[$generation + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$i]['ae'] = abs($estimated_effort - $projects['effort']);
                }
            } ## End if generation = 0

            if ($generation > 0) {
                $r1[$generation + 1] = $chaos->chaotic($r1[$generation]);
                $r2[$generation + 1] = $chaos->chaotic($r2[$generation]);
                $B[$generation + 1] = $chaos->chaotic($B[$generation]);
                foreach ($particles[$generation] as $i => $particle) {

                    ## Rao-1
                    // $A = $particles[$generation][$i]['A'] + $r1[$generation] * ($best_particles[$generation]['A'] - $worst_particles[$generation]['A']);

                    $candidates = $this->candidating($particles[$generation], $particle);
                    ## Rao-2
                    // $A = $particles[$generation][$i]['A'] + $r1[$generation] * ($best_particles[$generation]['A'] - $worst_particles[$generation]['A']) + ($r2[$generation] * (abs($candidates[1]) - abs($candidates[2])));

                    ## Rao-3
                    $A = $particles[$generation][$i]['A'] + $r1[$generation] * ($best_particles[$generation]['A'] - abs($worst_particles[$generation]['A'])) + ($r2[$generation] * (abs($candidates[1]) - $candidates[2]));

                    $E = $this->scaleEffortExponent($B[$generation], $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $particles[$generation + 1][$i]['A'] = $A;
                    $particles[$generation + 1][$i]['B'] = $B[$generation];
                    $particles[$generation][$i]['E'] = $E;
                    $particles[$generation][$i]['EM'] = array_sum($EM);
                    $particles[$generation][$i]['SF'] = array_sum($SF);
                    $particles[$generation][$i]['size'] = $projects['kloc'];
                    $particles[$generation][$i]['effort'] = $projects['effort'];
                    $particles[$generation + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$i]['ae'] = abs($estimated_effort - $projects['effort']);
                }
            } ## End if generation > 0
            $best_particles[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
            $worst_particles[$generation + 1] = $this->maximalAE($particles[$generation + 1]);

            ## Fitness evaluation
            if ($best_particles[$generation + 1]['ae'] < $this->fitness) {
                return $best_particles[$generation + 1];
            } else {
                $results[] = $best_particles[$generation + 1];
            }
            // $ret[] = $best_particles[$generation + 1];
            // $ret[] = $worst_particles[$generation + 1];
        } ## End of Generation
        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];


        // $best = min(array_column($results, 'ae'));
        // $best_index = array_search($best, array_column($ret, 'ae'));
        // $solutions['best'] = $ret[$best_index]['ae'];
        // $solutions['worst'] = $ret[$best_index + 1]['ae'];
        // return $solutions;
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

                for ($i = 0; $i <= $this->trials - 1; $i++) {
                    $results[] = $this->cocomo($projects);
                }
                $A = array_sum(array_column($results, 'A')) / $this->trials;
                $B = array_sum(array_column($results, 'B')) / $this->trials;
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
} ## End of Raoptimizer

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
function get_combinations($arrays)
{
    $result = array(array());
    foreach ($arrays as $property => $property_values) {
        $tmp = array();
        foreach ($result as $result_item) {
            foreach ($property_values as $property_value) {
                $tmp[] = array_merge($result_item, array($property => $property_value));
            }
        }
        $result = $tmp;
    }
    return $result;
}

$combinations = get_combinations(
    array(
        'particle_size' => array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100),
        'chaotic' => array('bernoulli', 'chebyshev', 'circle', 'gauss', 'logistic', 'sine', 'singer', 'sinu'),
    )
);

foreach ($combinations as $key => $combination) {
    $file_name = 'cocomo.txt';
    $particle_size = $combination['particle_size'];
    $maximum_generation = 40;
    $trials = 10;
    $lower_bound = 0.01;
    $upper_bound = 5;
    $fitness = 10;
    $number_of_project_cocomo = 93;
    $chaotic_type = $combination['chaotic'];

    $optimize = new Raoptimizer($file_name, $scales, $particle_size, $maximum_generation, $lower_bound, $upper_bound, $fitness, $trials, $chaotic_type);

    $optimized = $optimize->processingDataset();
    $mae = array_sum(array_column($optimized, 'ae')) / $number_of_project_cocomo;
    echo 'MAE: ' . $mae;
    echo '&nbsp; &nbsp; ';
    print_r($combination);
    echo '<br>';
    $data = array($mae, $combination['particle_size'], $combination['chaotic']);
    $fp = fopen('hasil_crao_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
