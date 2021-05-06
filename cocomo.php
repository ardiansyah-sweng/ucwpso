<?php
include 'optimizer.php';
include 'chaotic_interface.php';
include 'optimizers_interface.php';

class Cocomo extends Optimizer
{
    protected $parameters;
    protected $scales = array(
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

    function __construct($parameters)
    {
        $this->parameters = $parameters;
    }

    function prepareDataset()
    {
        $raw_dataset = file('cocomo/' . $this->parameters['file_name']);
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

    function _isChaotic($chaos_type)
    {
        if ($chaos_type != 'xxx') {
            return true;
        }
    }

    function estimation($projects)
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


        for ($generation = 0; $generation <= $this->parameters['max_generation']; ++$generation) {
            $chaoticFactory = new ChaoticFactory();
            $chaos = $chaoticFactory->initializeChaotic($this->parameters['chaotic_type'], $generation);

            if ($generation === 0) {
                ## Generate population

                if ($this->_isChaotic($this->parameters['chaotic_type'])) {
                    $B[$generation + 1] = $chaos->chaotic($this->parameters['initial_chaos']);
                    $r1[$generation + 1] = $chaos->chaotic($this->parameters['initial_chaos']);
                    $r2[$generation + 1] = $chaos->chaotic($this->parameters['initial_chaos']);
                }
                if (!$this->_isChaotic($this->parameters['chaotic_type'])) {
                    $B[$generation + 1] = $this->randomZeroToOne();
                    $r1[$generation + 1] = $this->randomZeroToOne();
                    $r2[$generation + 1] = $this->randomZeroToOne();
                }

                $initial_dimensions = $this->generatePopulation($this->parameters);
                foreach ($initial_dimensions as $key => $dimension) {
                    $A = $dimension[0];
                    $E = $this->scaleEffortExponent($B[$generation + 1], $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);
                    $ae = abs($estimated_effort - $projects['effort']); // TODO provide interface for error functions SSE, RMSE, AE, MRE
                    $particles[$generation + 1][$key]['A'] = $A;
                    $particles[$generation + 1][$key]['B'] = $B[$generation + 1];
                    $particles[$generation + 1][$key]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$key]['ae'] = $ae;
                }
                $best_particles[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
                $worst_particles[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            } ## End if Generation = 0

            if ($generation > 0) {
                if ($this->_isChaotic($this->parameters['chaotic_type'])) {
                    $B[$generation + 1] = $chaos->chaotic($B[$generation]);
                    $r1[$generation + 1] = $chaos->chaotic($r1[$generation]);
                    $r2[$generation + 1] = $chaos->chaotic($r2[$generation]);
                }
                if (!$this->_isChaotic($this->parameters['chaotic_type'])) {
                    $B[$generation + 1] = $this->randomZeroToOne();
                    $r1[$generation + 1] = $this->randomZeroToOne();
                    $r2[$generation + 1] = $this->randomZeroToOne();
                }

                foreach ($particles[$generation] as $key => $particle) {

                    $parameter = [
                        'X' => $particle['A'],
                        'r1' => $r1[$generation + 1],
                        'r2' => $r2[$generation + 1],
                        'best_X' => $best_particles[$generation]['A'],
                        'worst_X' => $worst_particles[$generation]['A'],
                        'particles' => $particles[$generation],
                        'individu' => $particle
                    ];

                    $optimizerFactory = new OptimizerFactory();
                    $optimizer = $optimizerFactory->initializeOptimizer($this->parameters['optimizer_type'], $generation, $parameter);

                    $A = $this->trimming($optimizer->optimizer());
                    $E = $this->scaleEffortExponent($B[$generation], $SF);
                    $estimated_effort = $this->estimating($A, $projects['kloc'], $E, $EM);
                    $ae = abs($estimated_effort - $projects['effort']); // TODO provide interface for error functions SSE, RMSE, AE, MRE
                    $particles[$generation + 1][$key]['A'] = $A;
                    $particles[$generation + 1][$key]['B'] = $B[$generation + 1];
                    $particles[$generation + 1][$key]['estimatedEffort'] = $estimated_effort;
                    $particles[$generation + 1][$key]['ae'] = $ae;
                }
                $best_particles[$generation + 1] = $this->minimalAE($particles[$generation + 1]);
                $worst_particles[$generation + 1] = $this->maximalAE($particles[$generation + 1]);
            } ## End if generation > 0

            ## Fitness evaluation
            $results = [];
            if ($best_particles[$generation + 1]['ae'] < $this->parameters['fitness']) {
                return $best_particles[$generation + 1];
            } else {
                $results[] = $best_particles[$generation + 1];
            }
        } ## End of generation
        $best = min(array_column($results, 'ae'));
        $index = array_search($best, array_column($results, 'ae'));
        return $results[$index];
    }

    function estimating($A, $size, $E, $effort_multipliers)
    {
        return $A * pow($size, $E) * array_product($effort_multipliers);
    }

    function trimming($dimension_value)
    {
        if ($dimension_value < $this->parameters['dimensions'][0]['lower']) {
            return $this->parameters['dimensions'][0]['lower'];
        }
        if ($dimension_value > $this->parameters['dimensions'][0]['upper']) {
            return $this->parameters['dimensions'][0]['upper'];
        }
        return $dimension_value;
    }

    function _isMoreThanOneDimension()
    {
        if (count($this->parameters['dimensions']) > 1) {
            return true;
        }
    }

    function processingDataset()
    {
        if ($this->_isMoreThanOneDimension()) {
            return 'Not for Cocomo! Make sure dimension parameter is only One';
        }
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
                    $results[] = $this->estimation($projects);
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
}

## Instantiation

$problem_dimensions = [
    ['lower' => 0.01, 'upper' => 5]
];

$optimizer_methods = [
    'optimizer' => 'rao1',
    'optimizer' => 'rao2',
    'optimizer' => 'rao3',
    'optimizer' => 'era',
    'optimizer' => 'facl',
    'optimizer' => 'cera'
];

$parameters = [
    'swarm_size' => 80,
    'max_generation' => 40,
    'fitness' => 10,
    'dimensions' => $problem_dimensions,
    'file_name' => 'cocomo.txt',
    'trials' => 30,
    'chaotic_type' => 'singer',
    'optimizer_type' => 'rao2',
    'project_size' => 93,
    'initial_chaos' => (float) rand() / (float) getrandmax()
];

$optimized = new Cocomo($parameters);
$optimized_result = $optimized->processingDataset();
$mae = array_sum(array_column($optimized_result, 'ae')) / $parameters['project_size'];
echo 'MAE: ' . $mae;
echo '<p>';
foreach ($optimized_result as $key => $result) {
    echo $key . ' ';
    print_r($result);
    echo '<br>';
    $data = array($result['ae']);
    $fp = fopen('hasil_cocomo.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
