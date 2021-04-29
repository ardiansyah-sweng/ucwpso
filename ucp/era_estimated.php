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

    function scaleEffortExponent($B, $scale_factors)
    {
        return $B + 0.01 * array_sum($scale_factors);
    }

    function estimating($A, $size, $E, $effort_multipliers)
    {
        return $A * pow($size, $E) * array_sum($effort_multipliers);
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

    function size($xSimple, $simpleUC, $xAverage, $averageUC, $xComplex, $complexUC, $uaw, $tcf, $ecf)
    {
        $ucSimple = $xSimple * $simpleUC;
        $ucAverage = $xAverage * $averageUC;
        $ucComplex = $xComplex * $complexUC;

        $UUCW = $ucSimple + $ucAverage + $ucComplex;
        $UUCP = $uaw + $UUCW;
        return $UUCP * $tcf * $ecf;
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

    function crossover($bestHQs, $Xbests, $r, $projects)
    {
        $xSimple_bestHQ = ($r * $bestHQs['xSimple']) + (1 - $r) * $Xbests['xSimple'];
        $xSimple_Xbest = ($r * $Xbests['xSimple']) + (1 - $r) * $bestHQs['xSimple'];
        $xAverage_bestHQ = ($r * $bestHQs['xAverage']) + (1 - $r) * $Xbests['xAverage'];
        $xAverage_Xbest = ($r * $Xbests['xAverage']) + (1 - $r) * $bestHQs['xAverage'];
        $xComplex_bestHQ = ($r * $bestHQs['xComplex']) + (1 - $r) * $Xbests['xComplex'];
        $xComplex_Xbest = ($r * $Xbests['xComplex']) + (1 - $r) * $bestHQs['xComplex'];

        if ($xSimple_bestHQ < $this->range_positions['min_xSimple']) {
            $xSimple_bestHQ = $this->range_positions['min_xSimple'];
        }
        if ($xSimple_bestHQ > $this->range_positions['max_xSimple']) {
            $xSimple_bestHQ = $this->range_positions['max_xSimple'];
        }
        if ($xAverage_bestHQ < $this->range_positions['min_xAverage']) {
            $xAverage_bestHQ = $this->range_positions['min_xAverage'];
        }
        if ($xAverage_bestHQ > $this->range_positions['max_xAverage']) {
            $xAverage_bestHQ = $this->range_positions['max_xAverage'];
        }
        if ($xComplex_bestHQ < $this->range_positions['min_xComplex']) {
            $xComplex_bestHQ = $this->range_positions['min_xComplex'];
        }
        if ($xComplex_bestHQ > $this->range_positions['max_xComplex']) {
            $xComplex_bestHQ = $this->range_positions['max_xComplex'];
        }

        if ($xSimple_Xbest < $this->range_positions['min_xSimple']) {
            $xSimple_Xbest = $this->range_positions['min_xSimple'];
        }
        if ($xSimple_Xbest > $this->range_positions['max_xSimple']) {
            $xSimple_Xbest = $this->range_positions['max_xSimple'];
        }
        if ($xAverage_Xbest < $this->range_positions['min_xAverage']) {
            $xAverage_Xbest = $this->range_positions['min_xAverage'];
        }
        if ($xAverage_Xbest > $this->range_positions['max_xAverage']) {
            $xAverage_Xbest = $this->range_positions['max_xAverage'];
        }
        if ($xComplex_Xbest < $this->range_positions['min_xComplex']) {
            $xComplex_Xbest = $this->range_positions['min_xComplex'];
        }
        if ($xComplex_Xbest > $this->range_positions['max_xComplex']) {
            $xComplex_Xbest = $this->range_positions['max_xComplex'];
        }

        $UCP_bestHQ = $this->size($xSimple_bestHQ, $projects['simpleUC'], $xAverage_bestHQ, $projects['averageUC'], $xComplex_bestHQ, $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);

        $UCP_Xbest = $this->size($xSimple_Xbest, $projects['simpleUC'], $xAverage_Xbest, $projects['averageUC'], $xComplex_Xbest, $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);

        $estimated_effort_bestHQ = $UCP_bestHQ * $this->productivity_factor;
        $estimated_effort_Xbest = $UCP_Xbest * $this->productivity_factor;

        $AE_bestHQ = abs($estimated_effort_bestHQ - floatval($projects['actualEffort']));
        $AE_Xbest = abs($estimated_effort_Xbest - floatval($projects['actualEffort']));

        return array(
            "bestHQ" => array('estimatedEffort' => $estimated_effort_bestHQ, 'ucp' => $UCP_bestHQ, 'ae' => $AE_bestHQ, 'xSimple' => $xSimple_bestHQ, 'xAverage' => $xAverage_bestHQ, 'xComplex' => $xComplex_bestHQ),
            "Xbest" => array('estimatedEffort' => $estimated_effort_Xbest, 'ucp' => $UCP_Xbest, 'ae' => $AE_Xbest, 'xSimple' => $xSimple_Xbest, 'xAverage' => $xAverage_Xbest, 'xComplex' => $xComplex_Xbest)
        );
    }

    function mutation($Xbests, $r1, $mutation_radius, $projects)
    {
        $xSimple = $Xbests['xSimple'] + (2 * $r1 - 1) * ($mutation_radius * abs($this->range_positions['max_xSimple'] - $this->range_positions['min_xSimple']));
        $xAverage = $Xbests['xAverage'] + (2 * $r1 - 1) * ($mutation_radius * abs($this->range_positions['max_xAverage'] - $this->range_positions['min_xAverage']));
        $xComplex = $Xbests['xComplex'] + (2 * $r1 - 1) * ($mutation_radius * abs($this->range_positions['max_xComplex'] - $this->range_positions['min_xComplex']));

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

        $UCP = $this->size($xSimple, $projects['simpleUC'], $xAverage, $projects['averageUC'], $xComplex, $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);
        $estimated_effort = $UCP * $this->productivity_factor;
        $ae = abs($estimated_effort - floatval($projects['actualEffort']));

        return array('estimatedEffort' => $estimated_effort, 'ucp' => $UCP, 'ae' => $ae, 'xSimple' => $xSimple, 'xAverage' => $xAverage, 'xComplex' => $xComplex);
    }

    function randomWalk($LQ, $HQ_particle, $r, $projects)
    {
        foreach ($LQ as $key => $particle) {
            $ret[$key]['xSimple'] = $particle['xSimple'] + $r * ($HQ_particle['xSimple'] - $particle['xSimple']);
            $ret[$key]['xAverage'] = $particle['xAverage'] + $r * ($HQ_particle['xAverage'] - $particle['xAverage']);
            $ret[$key]['xComplex'] = $particle['xComplex'] + $r * ($HQ_particle['xComplex'] - $particle['xComplex']);

            if ($ret[$key]['xSimple'] < $this->range_positions['min_xSimple']) {
                $ret[$key]['xSimple'] = $this->range_positions['min_xSimple'];
            }
            if ($ret[$key]['xSimple'] > $this->range_positions['max_xSimple']) {
                $ret[$key]['xSimple'] = $this->range_positions['max_xSimple'];
            }
            if ($ret[$key]['xAverage'] < $this->range_positions['min_xAverage']) {
                $ret[$key]['xAverage'] = $this->range_positions['min_xAverage'];
            }
            if ($ret[$key]['xAverage'] > $this->range_positions['max_xAverage']) {
                $ret[$key]['xAverage'] = $this->range_positions['max_xAverage'];
            }
            if ($ret[$key]['xComplex'] < $this->range_positions['min_xComplex']) {
                $ret[$key]['xComplex'] = $this->range_positions['min_xComplex'];
            }
            if ($ret[$key]['xComplex'] > $this->range_positions['max_xComplex']) {
                $ret[$key]['xComplex'] = $this->range_positions['max_xComplex'];
            }

            $UCP = $this->size($ret[$key]['xSimple'], $projects['simpleUC'], $ret[$key]['xAverage'], $projects['averageUC'], $ret[$key]['xComplex'], $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);

            $ret[$key]['estimatedEffort'] = $UCP * $this->productivity_factor;
            $ret[$key]['ae'] = abs($ret[$key]['estimatedEffort'] - floatval($projects['actualEffort']));
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
    }

    function UCP($projects)
    {
        for ($generation = 0; $generation <= $this->parameters['maximum_generation']; $generation++) {
            $r1 = $this->randomZeroToOne();
            $r2 = $this->randomZeroToOne();
            $r1_mutation = $this->randomZeroToOne();
            $r2_mutation = $this->randomZeroToOne();

            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->parameters['particle_size']; $i++) {
                    $chaotic[$generation + 1] = $this->sinu($this->randomzeroToOne());
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
                $mutation_rate[$generation + 1] = $this->parameters['b'];
            } ## End if generation = 0

            if ($generation > 0) {
                $chaotic[$generation + 1] = $this->sinu($chaotic[$generation]);
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
                    $ae_candidate_xSimple1 = $splitted_particles['hq'][$key_xSimple1 = array_rand($splitted_particles['hq'])]['ae'];
                    $ae_candidate_xSimple2 = $splitted_particles['hq'][$key_xSimple2 = array_rand($splitted_particles['hq'])]['ae'];
                    $ae_candidate_xAverage1 = $splitted_particles['hq'][$key_xAverage1 = array_rand($splitted_particles['hq'])]['ae'];
                    $ae_candidate_xAverage2 = $splitted_particles['hq'][$key_xAverage2 = array_rand($splitted_particles['hq'])]['ae'];
                    $ae_candidate_xComplex1 = $splitted_particles['hq'][$key_xComplex1 = array_rand($splitted_particles['hq'])]['ae'];
                    $ae_candidate_xComplex2 = $splitted_particles['hq'][$key_xComplex2 = array_rand($splitted_particles['hq'])]['ae'];

                    if ($individu['ae'] > $ae_candidate_xSimple1) {
                        $xSimple1 = $splitted_particles['hq'][$key_xSimple1]['xSimple'];
                    }
                    if ($individu['ae'] < $ae_candidate_xSimple1 || $individu['ae'] == $ae_candidate_xSimple1) {
                        $xSimple1 = $individu['xSimple'];
                    }
                    if ($individu['ae'] > $ae_candidate_xSimple2) {
                        $xSimple2 = $splitted_particles['hq'][$key_xSimple2]['xSimple'];
                    }
                    if ($individu['ae'] < $ae_candidate_xSimple2 || $individu['ae'] == $ae_candidate_xSimple2) {
                        $xSimple2 = $individu['xSimple'];
                    }

                    if ($individu['ae'] > $ae_candidate_xAverage1) {
                        $xAverage1 = $splitted_particles['hq'][$key_xAverage1]['xAverage'];
                    }
                    if ($individu['ae'] < $ae_candidate_xAverage1 || $individu['ae'] == $ae_candidate_xAverage1) {
                        $xAverage1 = $individu['xAverage'];
                    }
                    if ($individu['ae'] > $ae_candidate_xAverage2) {
                        $xAverage2 = $splitted_particles['hq'][$key_xAverage2]['xAverage'];
                    }
                    if ($individu['ae'] < $ae_candidate_xAverage2 || $individu['ae'] == $ae_candidate_xAverage2) {
                        $xAverage2 = $individu['xAverage'];
                    }

                    if ($individu['ae'] > $ae_candidate_xComplex1) {
                        $xComplex1 = $splitted_particles['hq'][$key_xComplex1]['xComplex'];
                    }
                    if ($individu['ae'] < $ae_candidate_xComplex1 || $individu['ae'] == $ae_candidate_xComplex1) {
                        $xComplex1 = $individu['xComplex'];
                    }
                    if ($individu['ae'] > $ae_candidate_xComplex2) {
                        $xComplex2 = $splitted_particles['hq'][$key_xComplex2]['xComplex'];
                    }
                    if ($individu['ae'] < $ae_candidate_xComplex2 || $individu['ae'] == $ae_candidate_xComplex2) {
                        $xComplex2 = $individu['xComplex'];
                    }

                    ## Rao-1 
                    // $xSimple = $individu['xSimple'] + $r1 * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']);
                    // $xAverage = $individu['xAverage'] + $r2 * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']);
                    // $xComplex = $individu['xComplex'] + $r3 * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']);

                    ## Rao-1 Chaotic
                    // $xSimple = $particles[$generation][$i]['xSimple'] + $chaotic[$generation] * ($Xbest[$generation]['xSimple'] - $Xworst[$generation]['xSimple']);
                    // $xAverage = $particles[$generation][$i]['xAverage'] + $chaotic[$generation] * ($Xbest[$generation]['xAverage'] - $Xworst[$generation]['xAverage']);
                    // $xComplex = $particles[$generation][$i]['xComplex'] + $chaotic[$generation] * ($Xbest[$generation]['xComplex'] - $Xworst[$generation]['xComplex']);

                    ## Rao-3 
                    // $xSimple = $individu['xSimple'] + $r1 * ($Xbest[$generation]['xSimple'] - abs($Xworst[$generation]['xSimple'])) + ($r2 * (abs($xSimple1) - $xSimple2));
                    // $xAverage = $individu['xAverage'] + $r1 * ($Xbest[$generation]['xAverage'] - abs($Xworst[$generation]['xAverage'])) + ($r2 * (abs($xAverage1) - $xAverage2));
                    // $xComplex = $individu['xComplex'] + $r1 * ($Xbest[$generation]['xComplex'] - abs($Xworst[$generation]['xComplex'])) + ($r2 * (abs($xComplex1) - $xComplex2));

                    ## Rao-3 chaotic
                    $xSimple = $individu['xSimple'] + $chaotic[$generation] * ($Xbest[$generation]['xSimple'] - abs($Xworst[$generation]['xSimple'])) + ($chaotic[$generation] * (abs($xSimple1) - $xSimple2));
                    $xAverage = $individu['xAverage'] + $chaotic[$generation] * ($Xbest[$generation]['xAverage'] - abs($Xworst[$generation]['xAverage'])) + ($chaotic[$generation] * (abs($xAverage1) - $xAverage2));
                    $xComplex = $individu['xComplex'] + $chaotic[$generation] * ($Xbest[$generation]['xComplex'] - abs($Xworst[$generation]['xComplex'])) + ($chaotic[$generation] * (abs($xComplex1) - $xComplex2));

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

                    $UCP = $this->size($xSimple, $projects['simpleUC'], $xAverage, $projects['averageUC'], $xComplex, $projects['complexUC'], $projects['uaw'], $projects['tcf'], $projects['ecf']);
                    $esimated_effort = $UCP * $this->productivity_factor;
                    $hq[$generation][$i]['estimatedEffort'] = $esimated_effort;
                    $hq[$generation][$i]['ucp'] = $UCP;
                    $hq[$generation][$i]['ae'] = abs($esimated_effort - floatval($projects['actualEffort']));
                    $hq[$generation][$i]['xSimple'] = $xSimple;
                    $hq[$generation][$i]['xAverage'] = $xAverage;
                    $hq[$generation][$i]['xComplex'] = $xComplex;
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
                    $offspring = $this->crossover($bestHQ[$generation], $Xbest[$generation], $r, $projects);
                    $Xbest[$generation + 1] = $offspring['Xbest'];
                    $bestHQ[$generation + 1] = $offspring['bestHQ'];
                }

                ## Mutation
                if ($r < 0.5 && $r2_mutation < $this->parameters['b']) {
                    $Xbest[$generation + 1] = $this->mutation($Xbest[$generation], $r1_mutation, $mutation_radius[$generation], $projects);
                }

                $r_random_walk = $this->randomZeroToOne();
                $LQ = $this->randomWalk($splitted_particles['lq'], $HQ_particle, $r_random_walk, $projects);

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
$particle_size = 200;
$maximum_generation = 40;
$trials = 1000;
$s = 0.5;
$a = 0.5;
$b = 0.9;
$fitness = 10;

$parameters = ['particle_size' => $particle_size, 'maximum_generation' => $maximum_generation, 'trials' => $trials, 's' => $s, 'a' => $a, 'b' => $b, 'fitness' => $fitness];
$productivity_factor = 20;

$range_positions = ['min_xSimple' => 5, 'max_xSimple' => 7.49, 'min_xAverage' => 7.5, 'max_xAverage' => 12.49, 'min_xComplex' => 12.5, 'max_xComplex' => 15];

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
