<?php
include '../chaotic_interface.php';
set_time_limit(1000000);

class Raoptimizer
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

    function crossover($bestHQs, $Xbests, $r, $SF, $EM, $size, $effort)
    {
        $A_bestHQ = ($r * $bestHQs['A']) + (1 - $r) * $Xbests['A'];
        $A_Xbest = ($r * $Xbests['A']) + (1 - $r) * $bestHQs['A'];
        $B_bestHQ = ($r * $bestHQs['B']) + (1 - $r) * $Xbests['B'];
        $B_Xbest = ($r * $Xbests['B']) + (1 - $r) * $bestHQs['B'];

        $E_bestHQ = $this->scaleEffortExponent($B_bestHQ, $SF);
        $E_Xbest = $this->scaleEffortExponent($B_Xbest, $SF);
        $estimated_bestHQ = $this->estimating($A_bestHQ, $size, $E_bestHQ, $EM);
        $estimated_Xbest = $this->estimating($A_Xbest, $size, $E_Xbest, $EM);

        $AE_bestHQ = abs($estimated_bestHQ - $effort);
        $AE_Xbest = abs($estimated_Xbest - $effort);

        return array(
            "bestHQ" => array('A' => $A_bestHQ, 'B' => $B_bestHQ, 'estimatedEffort' => $estimated_bestHQ, 'ae' => $AE_bestHQ),
            "Xbest" => array('A' => $A_Xbest, 'B' => $B_Xbest, 'E' => $E_Xbest, 'EM' => $EM, 'SF' => $SF, 'size' => $size, 'effort' => $effort, 'estimatedEffort' => $estimated_Xbest, 'ae' => $AE_Xbest)
        );
    }

    function mutation($Xbests, $r1, $mutation_radius, $SF, $EM, $size, $effort)
    {
        $A = $Xbests['A'] + (2 * $r1 - 1) * ($mutation_radius * abs($this->parameters['upper_bound'] - $this->parameters['lower_bound']));
        $E = $this->scaleEffortExponent($Xbests['B'], $SF);
        $estimated_effort = $this->estimating($A, $size, $E, $EM);
        $ae = abs($estimated_effort - $effort);

        return ['A' => $A, 'B' => $Xbests['B'], 'estimatedEffort' => $estimated_effort, 'ae' => $ae];
    }

    function randomWalk($LQ, $HQ_particle, $r, $SF, $EM, $size, $effort)
    {
        foreach ($LQ as $key => $particle) {
            $ret[$key]['A'] = $particle['A'] + $r * ($HQ_particle['A'] - $particle['A']);
            $ret[$key]['B'] = $particle['B'];
            $ret[$key]['E'] = $this->scaleEffortExponent($particle['B'], $SF);
            $ret[$key]['estimatedEffort'] = $this->estimating($ret[$key]['A'], $size, $ret[$key]['E'], $EM);
            $ret[$key]['ae'] = abs($ret[$key]['estimatedEffort'] - $effort);
        }
        return $ret;
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
        return $this->parameters['a'];
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
        return $this->parameters['b'];
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
            // $r1 = $this->randomZeroToOne(); ## Non chaotic
            // $r2 = $this->randomZeroToOne();## Non chaotic
            $r1_mutation = $this->randomZeroToOne();
            $r2_mutation = $this->randomZeroToOne();
            //$B = $this->randomZeroToOne(); ## Non chaotic

            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic($this->parameters['chaotic_type'], $generation);

            ## Generate population
            if ($generation === 0) {
                $r1[$generation + 1] = $chaos->chaotic($this->parameters['initial_chaos_value']);
                $r2[$generation + 1] = $chaos->chaotic($this->parameters['initial_chaos_value']);
                
                for ($i = 0; $i <= $this->parameters['particle_size']; $i++) {
                    $A = mt_rand($this->parameters['lower_bound'] * 100, $this->parameters['upper_bound'] * 100) / 100;

                    $B[$generation + 1] = $chaos->chaotic($this->parameters['initial_chaos_value']); ## chaotic
                    // $E = $this->scaleEffortExponent($B, $SF); ## Non chaotic
                    $E = $this->scaleEffortExponent($B[$generation + 1], $SF); ## chaotic

                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $particles[$generation + 1][$i]['A'] = $A;
                    // $particles[$generation + 1][$i]['B'] = $B; ## Non chaotic
                    $particles[$generation + 1][$i]['B'] = $B[$generation + 1]; ## chaotic
                    $particles[$generation + 1][$i]['E'] = $E;
                    $particles[$generation + 1][$i]['EM'] = array_sum($EM);
                    $particles[$generation + 1][$i]['SF'] = array_sum($SF);
                    $particles[$generation + 1][$i]['size'] = $projects['kloc'];
                    $particles[$generation + 1][$i]['effort'] = $projects['effort'];
                    $particles[$generation + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$i]['ae'] = abs($estimated_effort - $projects['effort']);
                }
                $mutation_rate[$generation + 1] = $this->parameters['b'];
            } ## End if generation = 0

            if ($generation > 0) {
                $B[$generation + 1] = $chaos->chaotic($B[$generation]); ## Chaotic
                $r1[$generation + 1] = $chaos->chaotic($r1[$generation]); ## Chaotic
                $r2[$generation + 1] = $chaos->chaotic($r2[$generation]); ## Chaotic

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
                    $index_candidate1 = array_rand($splitted_particles['hq']);
                    $index_candidate2 = array_rand($splitted_particles['hq']);
                    $ae_candidate1 = $splitted_particles['hq'][$index_candidate1]['ae'];
                    $ae_candidate2 = $splitted_particles['hq'][$index_candidate2]['ae'];

                    if ($individu['ae'] > $ae_candidate1) {
                        $A1 = $splitted_particles['hq'][$index_candidate1]['A'];
                    }
                    if ($individu['ae'] < $ae_candidate1 || $individu['ae'] == $ae_candidate1) {
                        $A1 = $individu['A'];
                    }
                    if ($individu['ae'] > $ae_candidate2) {
                        $A2 = $splitted_particles['hq'][$index_candidate2]['A'];
                    }
                    if ($individu['ae'] < $ae_candidate2 || $individu['ae'] == $ae_candidate2) {
                        $A2 = $individu['A'];
                    }

                    ## Rao-1
                    // $A = $individu['A'] + $r1 * ($Xbest[$generation]['A'] - $Xworst[$generation]['A']);

                    ## Rao-1 Chaotic
                    // $A = $individu['A'] + $r1[$generation] * ($Xbest[$generation]['A'] - $Xworst[$generation]['A']);

                    ## Rao-2
                    // $A = $individu['A'] + $r1 * ($Xbest[$generation]['A'] - $Xworst[$generation]['A']) + ($r2 * (abs($A1) - abs($A2)));

                    ## Rao-2 Chaotic
                    // $A = $individu['A'] + $r1[$generation] * ($Xbest[$generation]['A'] - $Xworst[$generation]['A']) + ($r2[$generation] * (abs($A1) - abs($A2)));

                    ## Rao-3 
                    // $A = $individu['A'] + $r1 * ($Xbest[$generation]['A'] - abs($Xworst[$generation]['A'])) + ($r2 * (abs($A1) - $A2));

                    ## Rao-3 chaotic
                    $A = $individu['A'] + $r1[$generation] * ($Xbest[$generation]['A'] - abs($Xworst[$generation]['A'])) + ($r2[$generation] * (abs($A1) - $A2));
                    // $E = $this->scaleEffortExponent($B, $SF);

                    $E = $this->scaleEffortExponent($B[$generation], $SF); ## Chaotic
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $hq[$generation][$i]['A'] = $A;
                    $hq[$generation][$i]['B'] = $B[$generation]; ## Chaotic
                    //$hq[$generation][$i]['B'] = $B;
                    $hq[$generation][$i]['E'] = $E;
                    $hq[$generation][$i]['EM'] = array_sum($EM);
                    $hq[$generation][$i]['SF'] = array_sum($SF);
                    $hq[$generation][$i]['size'] = $projects['kloc'];
                    $hq[$generation][$i]['effort'] = $projects['effort'];
                    $hq[$generation][$i]['estimatedEffort'] = $estimated_effort;
                    $hq[$generation][$i]['ae'] = abs($estimated_effort - $projects['effort']);
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
                if ($r  > 0.5) { // TODO add check funcion if r = 0.5
                    $offspring = $this->crossover($bestHQ[$generation], $Xbest[$generation], $r, $SF, $EM, $projects['kloc'], $projects['effort']);
                    $Xbest[$generation + 1] = $offspring['Xbest'];
                    $bestHQ[$generation + 1] = $offspring['bestHQ'];
                }
                if ($r < 0.5 && $r2_mutation < $mutation_rate[$generation]) {
                    $Xbest[$generation + 1] = $this->mutation($Xbest[$generation], $r1_mutation, $mutation_radius[$generation], $SF, $EM, $projects['kloc'], $projects['effort']);
                }

                $r_random_walk = $this->randomZeroToOne();
                $LQ = $this->randomWalk($splitted_particles['lq'], $HQ_particle, $r_random_walk, $SF, $EM, $projects['kloc'], $projects['effort']);

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
        's' => array(0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9),
        'chaotic' => array('bernoulli', 'chebyshev', 'circle', 'gauss', 'logistic', 'sine', 'singer', 'sinu'),
    )
);

foreach ($combinations as $key => $combination) {
    $file_name = 'cocomo.txt';

    $parameters = [
        'particle_size' => $combination['particle_size'],
        'chaotic_type' => $combination['chaotic'],
        'initial_chaos_value' => 0.8,
        'maximum_generation' => 40,
        'trials' => 10,
        's' => $combination['s'],
        'a' => 0.5,
        'b' => 0.9,
        'lower_bound' => 0.01,
        'upper_bound' => 5,
        'fitness' => 10
    ];
    $optimize = new Raoptimizer($file_name, $parameters, $scales);
    $optimized = $optimize->processingDataset();

    $mae = array_sum(array_column($optimized, 'ae')) / 93;
    echo 'MAE: ' . $mae;
    echo '&nbsp; &nbsp; ';
    print_r($combination);
    echo '<br>';

    $data = array($mae, $combination['particle_size'], $combination['s'], $combination['chaotic']);
    $fp = fopen('hasil_cera_perliminary.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
