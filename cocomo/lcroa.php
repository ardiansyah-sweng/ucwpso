<?php
set_time_limit(1000000);

class Raoptimizer
{
    protected $dataset_name;
    protected $scales;
    protected $particle_size;
    protected $maximum_generation;

    function __construct($dataset_name, $scales, $particle_size, $maximum_generation)
    {
        $this->dataset_name = $dataset_name;
        $this->scales = $scales;
        $this->particle_size = $particle_size;
        $this->maximum_generation = $maximum_generation;
    }

    function logistic($chaos_value)
    {
        return (4 * $chaos_value) * (1 - $chaos_value);
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
            ## Generate population
            if ($generation === 0) {
                for ($i = 0; $i <= $this->particle_size - 1; $i++) {
                    $A = mt_rand(0.01 * 100, 5 * 100) / 100;
                    $initial_chaotic = $this->logistic(0.8);
                    $B[$generation + 1] = $initial_chaotic;

                    $E = $this->scaleEffortExponent($initial_chaotic, $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $particles[$generation + 1][$i]['A'] = $A;
                    $particles[$generation + 1][$i]['B'] = $B[$generation + 1];
                    $particles[$generation + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$i]['ae'] = abs($estimated_effort - $projects['effort']);
                }
            } ## End if generation = 0

            if ($generation > 0) {
                $B[$generation + 1] = $this->logistic($B[$generation]);

                for ($i = 0; $i <= $this->particle_size - 1; $i++) {
                    $A = $particles[$generation][$i]['A'] + $B[$generation] * ($best_particles[$generation]['A'] - $worst_particles[$generation]['A']);

                    $E = $this->scaleEffortExponent($B[$generation], $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);

                    $particles[$generation + 1][$i]['A'] = $A;
                    $particles[$generation + 1][$i]['B'] = $B[$generation + 1];
                    $particles[$generation + 1][$i]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$i]['ae'] = abs($estimated_effort - $projects['effort']);
                }
            } ## End if generation > 0
            $best_particles[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
            $worst_particles[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            $ret[] = $best_particles[$generation + 1];
            $ret[] = $worst_particles[$generation + 1];
        } ## End of Generation
        $best = min(array_column($ret, 'ae'));
        $best_index = array_search($best, array_column($ret, 'ae'));
        $solutions['best'] = $ret[$best_index]['ae'];
        $solutions['worst'] = $ret[$best_index + 1]['ae'];
        return $solutions;
    }

    function processingDataset()
    {
        foreach ($this->prepareDataset() as $key => $project) {
            if ($key >= 0) {
                for ($i = 1; $i <= 999; $i++) {
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
                    $ret[$key][] = $this->cocomo($projects);
                }
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

$file_name = 'cocomo.txt';
$particle_size = 30;
$maximum_generation = 40;
$trials = 1000;

$optimize = new Raoptimizer($file_name, $scales, $particle_size, $maximum_generation);
foreach ($optimize->processingDataset() as $key => $result) {
    $best = array_sum(array_column($result, 'best')) / $trials;
    $worst = array_sum(array_column($result, 'worst')) / $trials;
    echo $key . ' ' . $best . ' -- ' . $worst . '<br>';
    $data = array($key, $best, $worst);
    $fp = fopen('hasil_lcrao_estimated.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}