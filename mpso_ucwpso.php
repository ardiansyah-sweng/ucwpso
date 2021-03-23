<?php

class MPUCWPSO
{
    private $SWARM_SIZE = 30;
    private $PRODUCTIVITY_FACTOR = 20;
    private $MAX_ITERATION = 500;
    private $FITNESS_VALUE_BASELINE = array(
        'azzeh1' => 1219.8,
        'azzeh2' => 201.1,
        'azzeh3' => 1564.8,
        'silhavy' => 240.19,
        'karner' => 1820,
        'nassif' => 1712,
        'ardiansyah' => 404.85
    );
    private $AVOIDED_RANDOM_VALUE = array(0.00, 0.25, 0.50, 0.75, 1.00);
    private $INERTIA_MAX = 0.9;
    private $INERTIA_MIN = 0.4;
    private $C1 = 2.8;
    private $C2 = 1.3;

    /**
     * Membangkitkan nilai acak dari 0..1
     */
    function randomZeroToOne()
    {
        return (float) rand() / (float) getrandmax();
    }

    /**
     * Generate random Simple Use Case Complexity weight parameter
     * Min = 5
     * Max = 7.49
     */
    function randomSimpleUCWeight()
    {
        $MIN = 5;
        $MAX = 7.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Average Use Case Complexity weight parameter
     * Min = 7.5
     * Max = 12.49 
     */
    function randomAverageUCWeight()
    {
        $MIN = 7.5;
        $MAX = 12.49;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Generate random Complex Use Case Complexity weight parameter
     * Min = 12.5
     * Max = 15
     */
    function randomComplexUCWeight()
    {
        $MIN = 12.5;
        $MAX = 15;
        return mt_rand($MIN * 100, $MAX * 100) / 100;
    }

    /**
     * Menghitung estimasi effort
     * parameter: arr[UComplexity]
     * return: arr[Y']
     */
    function effortEstimation($UAW, $UUCW, $tcf, $ecf)
    {
        $UUCP = $UAW + $UUCW;
        $UCP  = $UUCP * $tcf * $ecf;
        $ret['size'] = $UCP;
        $ret['estimation'] = $UCP * $this->PRODUCTIVITY_FACTOR;
        return $ret;
        //return $UCP * $this->PRODUCTIVITY_FACTOR;
    }

    /**
     * Hitung absolute error
     */
    function absoluteError($arrEstimatedEffort, $actualEffort)
    {
        foreach ($arrEstimatedEffort as $key => $val) {
            $absoluteError[] = abs($val - $actualEffort);
        }
        return $absoluteError;
    }

    /**
     * Fungsi pengecekan $r0 != AVOIDED_RANDOM_VALUE
     * Jika sama ulangi random hingga tidak sama
     */
    function randomNumber($r0_baru)
    {
        $i = 0;
        while ($i < count($this->AVOIDED_RANDOM_VALUE)) {
            if ($this->AVOIDED_RANDOM_VALUE[$i] == $r0_baru) {
                $r0_baru = number_format($this->randomZeroToOne(), 2);
                $i = 0;
            }
            if ($this->AVOIDED_RANDOM_VALUE[$i] != $r0_baru) {
                $i++;
            }
        }
        return $r0_baru;
    }

    /**
     * Fungsi AE Minimal
     * Parameter: arrPartikel
     * Return: arrPartikel[indexAEMinimal]
     */
    function minimalAE($arrPartikel)
    {
        foreach ($arrPartikel as $val) {
            $ae[] = $val['ae'];
        }
        return $arrPartikel[array_search(min($ae), $ae)];
    }

    function Main($dataset)
    {
        ##Generate Population
        for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
            $posisi[$i]['simple'] = $this->randomSimpleUCWeight();
            $posisi[$i]['average'] = $this->randomAverageUCWeight();
            $posisi[$i]['complex'] = $this->randomComplexUCWeight();

            $UComplexity[$i]['simple'] = $posisi[$i]['simple'] * $dataset['simpleUC'];
            $UComplexity[$i]['average'] = $posisi[$i]['average'] * $dataset['averageUC'];
            $UComplexity[$i]['complex'] = $posisi[$i]['complex'] * $dataset['complexUC'];

            $UUCW = $UComplexity[$i]['simple'] + $UComplexity[$i]['average'] + $UComplexity[$i]['complex'];

            $partikelAwal[$i]['estimatedEffort'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['estimation'];
            $partikelAwal[$i]['ae'] = abs($partikelAwal[$i]['estimatedEffort'] - $dataset['actualEffort']);
            $partikelAwal[$i]['xSimple'] = $posisi[$i]['simple'];
            $partikelAwal[$i]['xAverage'] = $posisi[$i]['average'];
            $partikelAwal[$i]['xComplex'] = $posisi[$i]['complex'];
        }
        //print_r($partikelAwal);
        //echo '<p>';
        $GBest = $this->minimalAE($partikelAwal);
        //echo '<p>';
        //print_r($GBest);
        ##End Generate Population

        //Inertia weight
        //inisialisasi logistic map chaotic inertia weight
        $r0 = $this->randomNumber(number_format($this->randomZeroToOne(), 2));

        ##Masuk Iterasi
        $iterasi = 0;
        while ($iterasi <= $this->MAX_ITERATION - 1) {
            //echo '<p><strong>ITERASI-' . $iterasi.'</strong>';
            //echo '<p>';

            //Inertia weight
            $r[$iterasi] = 4 * $r0 * (1 - $r0);
            $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $this->MAX_ITERATION);

            if ($iterasi == 0) {
                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
                    $velocity[$iterasi][$i]['vSimple'] = $w * $this->randomZeroToOne() + $this->C1 * $this->randomZeroToOne() * ($partikelAwal[$i]['xSimple'] - $partikelAwal[$i]['xSimple']) + $this->C2 * $this->randomZeroToOne() * ($GBest['xSimple'] - $partikelAwal[$i]['xSimple']);
                    $posisi[$iterasi][$i]['xSimple'] = $partikelAwal[$i]['xSimple'] + $velocity[$iterasi][$i]['vSimple'];
                    $UComplexity[$iterasi][$i]['ucSimple'] = $posisi[$iterasi][$i]['xSimple'] * $dataset['simpleUC'];

                    $velocity[$iterasi][$i]['vAverage'] = $w * $this->randomZeroToOne() + $this->C1 * $this->randomZeroToOne() * ($partikelAwal[$i]['xAverage'] - $partikelAwal[$i]['xAverage']) + $this->C2 * $this->randomZeroToOne() * ($GBest['xAverage'] - $partikelAwal[$i]['xAverage']);
                    $posisi[$iterasi][$i]['xAverage'] = $partikelAwal[$i]['xAverage'] + $velocity[$iterasi][$i]['vAverage'];
                    $UComplexity[$iterasi][$i]['ucAverage'] = $posisi[$iterasi][$i]['xAverage'] * $dataset['averageUC'];

                    $velocity[$iterasi][$i]['vComplex'] = $w * $this->randomZeroToOne() + $this->C1 * $this->randomZeroToOne() * ($partikelAwal[$i]['xComplex'] - $partikelAwal[$i]['xComplex']) + $this->C2 * $this->randomZeroToOne() * ($GBest['xComplex'] - $partikelAwal[$i]['xComplex']);
                    $posisi[$iterasi][$i]['xComplex'] = $partikelAwal[$i]['xComplex'] + $velocity[$iterasi][$i]['vComplex'];
                    $UComplexity[$iterasi][$i]['ucComplex'] = $posisi[$iterasi][$i]['xComplex'] * $dataset['complexUC'];

                    $UUCW = $UComplexity[$iterasi][$i]['ucSimple'] + $UComplexity[$iterasi][$i]['ucAverage'] + $UComplexity[$iterasi][$i]['ucComplex'];

                    $partikel[$iterasi][$i]['estimatedEffort'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['estimation'];
                    $partikel[$iterasi][$i]['ae'] = abs($partikel[$iterasi][$i]['estimatedEffort'] - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $posisi[$iterasi][$i]['xSimple'];
                    $partikel[$iterasi][$i]['xAverage'] = $posisi[$iterasi][$i]['xAverage'];
                    $partikel[$iterasi][$i]['xComplex'] = $posisi[$iterasi][$i]['xComplex'];
                }

                //echo '<br>';
                //Komparasi AE initial vs AE iterasi-0
                for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
                    //echo 'Partikel-' . $i . '<br>';
                    //echo 'AE: '.$partikelAwal[$i]['ae'] . '==' . $partikel[$iterasi][$i]['ae'] . '==>';
                    if ($partikelAwal[$i]['ae'] < $partikel[$iterasi][$i]['ae']) {
                        $partikel[$iterasi][$i]['ae'] = $partikelAwal[$i]['ae'];
                        $partikel[$iterasi][$i]['pbestSimple'] = $partikelAwal[$i]['xSimple'];
                        $partikel[$iterasi][$i]['pbestAverage'] = $partikelAwal[$i]['xAverage'];
                        $partikel[$iterasi][$i]['pbestComplex'] = $partikelAwal[$i]['xComplex'];
                        //echo $partikelAwal[$i]['ae'];
                    }
                    //echo $partikel[$iterasi][$i]['ae'];
                    $partikel[$iterasi][$i]['pbestSimple'] = $partikel[$iterasi][$i]['xSimple'];
                    $partikel[$iterasi][$i]['pbestAverage'] = $partikel[$iterasi][$i]['xAverage'];
                    $partikel[$iterasi][$i]['pbestComplex'] = $partikel[$iterasi][$i]['xComplex'];
                    //echo '<br>';
                }
                $GBest = $this->minimalAE($partikel[$iterasi]);
                //print_r($partikel);
                //echo '<br>';
                //echo 'GBest: ';
                //print_r($GBest);
            }
            //echo '<p>';

            if ($iterasi != 0) {
                //Inertia weight
                $r[$iterasi] = 4 * $r[$iterasi-1] * (1 - $r[$iterasi-1]);
                $w = $r[$iterasi] * $this->INERTIA_MIN + ((($this->INERTIA_MAX - $this->INERTIA_MIN) * $iterasi) / $this->MAX_ITERATION);

                //Update Velocity dan X_Posisi
                for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
                    $velocity[$iterasi][$i]['vSimple'] = ($w * $velocity[$iterasi - 1][$i]['vSimple']) + ($this->C1 * $this->randomZeroToOne()) * ($partikel[$iterasi - 1][$i]['xSimple'] - $partikel[$iterasi - 1][$i]['xSimple']) + ($this->C2 * $this->randomZeroToOne()) * ($GBest['xSimple'] - $partikel[$iterasi - 1][$i]['xSimple']);
                    $posisi[$iterasi][$i]['xSimple'] = $partikel[$iterasi - 1][$i]['xSimple'] + $velocity[$iterasi][$i]['vSimple'];
                    $UComplexity[$iterasi][$i]['ucSimple'] = $posisi[$iterasi][$i]['xSimple'] * $dataset['simpleUC'];

                    $velocity[$iterasi][$i]['vAverage'] = ($w * $velocity[$iterasi - 1][$i]['vAverage']) + ($this->C1 * $this->randomZeroToOne()) * ($partikel[$iterasi - 1][$i]['xAverage'] - $partikel[$iterasi - 1][$i]['xAverage']) + ($this->C2 * $this->randomZeroToOne()) * ($GBest['xAverage'] - $partikel[$iterasi - 1][$i]['xAverage']);
                    $posisi[$iterasi][$i]['xAverage'] = $partikel[$iterasi - 1][$i]['xAverage'] + $velocity[$iterasi][$i]['vAverage'];
                    $UComplexity[$iterasi][$i]['ucAverage'] = $posisi[$iterasi][$i]['xAverage'] * $dataset['averageUC'];

                    $velocity[$iterasi][$i]['vComplex'] = ($w * $velocity[$iterasi - 1][$i]['vComplex']) + ($this->C1 * $this->randomZeroToOne()) * ($partikel[$iterasi - 1][$i]['xComplex'] - $partikel[$iterasi - 1][$i]['xComplex']) + ($this->C2 * $this->randomZeroToOne()) * ($GBest['xComplex'] - $partikel[$iterasi - 1][$i]['xComplex']);
                    $posisi[$iterasi][$i]['xComplex'] = $partikel[$iterasi - 1][$i]['xComplex'] + $velocity[$iterasi][$i]['vComplex'];
                    $UComplexity[$iterasi][$i]['ucComplex'] = $posisi[$iterasi][$i]['xComplex'] * $dataset['complexUC'];

                    $UUCW = $UComplexity[$iterasi][$i]['ucSimple'] + $UComplexity[$iterasi][$i]['ucAverage'] + $UComplexity[$iterasi][$i]['ucComplex'];

                    $partikel[$iterasi][$i]['estimatedEffort'] = $this->effortEstimation($dataset['uaw'], $UUCW, $dataset['tcf'], $dataset['ecf'], $dataset['actualEffort'])['estimation'];
                    $partikel[$iterasi][$i]['ae'] = abs($partikel[$iterasi][$i]['estimatedEffort'] - $dataset['actualEffort']);
                    $partikel[$iterasi][$i]['xSimple'] = $posisi[$iterasi][$i]['xSimple'];
                    $partikel[$iterasi][$i]['xAverage'] = $posisi[$iterasi][$i]['xAverage'];
                    $partikel[$iterasi][$i]['xComplex'] = $posisi[$iterasi][$i]['xComplex'];
                }

                //echo '<br>';
                //Komparasi AE initial vs AE iterasi-0
                for ($i = 0; $i <= $this->SWARM_SIZE - 1; $i++) {
                    //echo 'Partikel-' . $i . '<br>';
                    //echo 'AE: '.$partikel[$iterasi-1][$i]['ae'] . '==' . $partikel[$iterasi][$i]['ae'] . '==>';
                    if ($partikel[$iterasi - 1][$i]['ae'] < $partikel[$iterasi][$i]['ae']) {
                        $partikel[$iterasi][$i]['ae'] = $partikel[$iterasi - 1][$i]['ae'];
                        $partikel[$iterasi][$i]['pbestSimple'] = $partikel[$iterasi - 1][$i]['xSimple'];
                        $partikel[$iterasi][$i]['pbestAverage'] = $partikel[$iterasi - 1][$i]['xAverage'];
                        $partikel[$iterasi][$i]['pbestComplex'] = $partikel[$iterasi - 1][$i]['xComplex'];
                        //echo $partikel[$iterasi-1][$i]['ae'];
                    }
                    //echo $partikel[$iterasi][$i]['ae'];
                    $partikel[$iterasi][$i]['pbestSimple'] = $partikel[$iterasi][$i]['xSimple'];
                    $partikel[$iterasi][$i]['pbestAverage'] = $partikel[$iterasi][$i]['xAverage'];
                    $partikel[$iterasi][$i]['pbestComplex'] = $partikel[$iterasi][$i]['xComplex'];
                    //echo '<br>';
                }
                $GBest = $this->minimalAE($partikel[$iterasi]);
                //echo '<br>';
                //echo 'GBest: ';
                //print_r($GBest);
            }
            //echo '<p>';

            //Stopping criteria is met
            if ($GBest['ae'] < $this->FITNESS_VALUE_BASELINE['silhavy']) {
                //print_r($GBest);
                //echo '<br>';
                return $GBest['ae'];
                //echo '<br>Iterasi ke-'.$iterasi.'<p>';
                break;
            } else {
                $arrGBest['ae'] = $GBest['ae'];
            }
            $iterasi++;
        }
        ##End Iterasi
        return min($arrGBest);
    }
}

/**
 * Dataset 71 data point
 * Attribute (7): simple, average, complex, uaw, tcf, ecf, actual effort
 */
$dataset = array(
    array('simpleUC' => 6, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 9, 'tcf' => 0.81, 'ecf' => 0.84, 'actualEffort' => 7970),
    array('simpleUC' => 4, 'averageUC' => 20, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.99, 'ecf' => 0.99, 'actualEffort' => 7962),
    array('simpleUC' => 1, 'averageUC' => 5, 'complexUC' => 20, 'uaw' => 9, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 7935),
    array('simpleUC' => 5, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 7805),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 16, 'uaw' => 8, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 7758),
    array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 14, 'uaw' => 8, 'tcf' => 0.99, 'ecf' => 0.99, 'actualEffort' => 7643),
    array('simpleUC' => 3, 'averageUC' => 18, 'complexUC' => 15, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7532),
    array('simpleUC' => 0, 'averageUC' => 16, 'complexUC' => 12, 'uaw' => 8, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 7451),
    array('simpleUC' => 2, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 7449),
    array('simpleUC' => 4, 'averageUC' => 14, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 1.025, 'ecf' => 0.98, 'actualEffort' => 7427),
    array('simpleUC' => 5, 'averageUC' => 16, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7406),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 8, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 7365),
    array('simpleUC' => 9, 'averageUC' => 8, 'complexUC' => 19, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 7350),
    array('simpleUC' => 5, 'averageUC' => 8, 'complexUC' => 20, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 7303),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 7252),
    array('simpleUC' => 1, 'averageUC' => 8, 'complexUC' => 16, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7245),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 16, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 7166),
    array('simpleUC' => 5, 'averageUC' => 11, 'complexUC' => 17, 'uaw' => 7, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 7119),
    array('simpleUC' => 3, 'averageUC' => 9, 'complexUC' => 14, 'uaw' => 8, 'tcf' => 0.92, 'ecf' => 0.78, 'actualEffort' => 7111),
    array('simpleUC' => 2, 'averageUC' => 14, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 7044),
    array('simpleUC' => 5, 'averageUC' => 14, 'complexUC' => 15, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 7040),
    array('simpleUC' => 3, 'averageUC' => 23, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 7028),
    array('simpleUC' => 1, 'averageUC' => 16, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6942),
    array('simpleUC' => 1, 'averageUC' => 15, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6814),
    array('simpleUC' => 2, 'averageUC' => 19, 'complexUC' => 12, 'uaw' => 9, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6809),
    array('simpleUC' => 2, 'averageUC' => 20, 'complexUC' => 11, 'uaw' => 8, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 6802),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 11, 'uaw' => 12, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6787),
    array('simpleUC' => 1, 'averageUC' => 9, 'complexUC' => 14, 'uaw' => 7, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 6764),
    array('simpleUC' => 4, 'averageUC' => 15, 'complexUC' => 14, 'uaw' => 7, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 6761),
    array('simpleUC' => 0, 'averageUC' => 15, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 6725),
    array('simpleUC' => 1, 'averageUC' => 16, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.02, 'ecf' => 1.085, 'actualEffort' => 6690),
    array('simpleUC' => 0, 'averageUC' => 18, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 6600),
    array('simpleUC' => 0, 'averageUC' => 17, 'complexUC' => 8, 'uaw' => 7, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6474),
    array('simpleUC' => 0, 'averageUC' => 13, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.95, 'ecf' => 0.92, 'actualEffort' => 6433),
    array('simpleUC' => 1, 'averageUC' => 13, 'complexUC' => 10, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.79, 'actualEffort' => 6416),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 10, 'uaw' => 8, 'tcf' => 0.94, 'ecf' => 1.02, 'actualEffort' => 6412),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 6, 'tcf' => 0.9, 'ecf' => 0.94, 'actualEffort' => 6400),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 12, 'uaw' => 7, 'tcf' => 0.71, 'ecf' => 0.73, 'actualEffort' => 6360),
    array('simpleUC' => 0, 'averageUC' => 13, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.9, 'ecf' => 0.91, 'actualEffort' => 6337),
    array('simpleUC' => 1, 'averageUC' => 20, 'complexUC' => 27, 'uaw' => 18, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 6240),
    array('simpleUC' => 1, 'averageUC' => 11, 'complexUC' => 11, 'uaw' => 7, 'tcf' => 0.78, 'ecf' => 0.51, 'actualEffort' => 6232),
    array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6173),
    array('simpleUC' => 0, 'averageUC' => 12, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 6160),
    array('simpleUC' => 2, 'averageUC' => 15, 'complexUC' => 12, 'uaw' => 6, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 6117),
    array('simpleUC' => 2, 'averageUC' => 13, 'complexUC' => 9, 'uaw' => 7, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 6062),
    array('simpleUC' => 1, 'averageUC' => 27, 'complexUC' => 15, 'uaw' => 19, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 6051),
    array('simpleUC' => 3, 'averageUC' => 26, 'complexUC' => 15, 'uaw' => 18, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 6048),
    array('simpleUC' => 2, 'averageUC' => 19, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 6035),
    array('simpleUC' => 1, 'averageUC' => 19, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 6024),
    array('simpleUC' => 20, 'averageUC' => 25, 'complexUC' => 9, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 6023),
    array('simpleUC' => 5, 'averageUC' => 25, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.118, 'ecf' => 0.995, 'actualEffort' => 5993),
    array('simpleUC' => 4, 'averageUC' => 16, 'complexUC' => 21, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 5985),
    array('simpleUC' => 5, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.75, 'ecf' => 0.81, 'actualEffort' => 5971),
    array('simpleUC' => 5, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.81, 'ecf' => 0.84, 'actualEffort' => 5962),
    array('simpleUC' => 6, 'averageUC' => 16, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5944),
    array('simpleUC' => 5, 'averageUC' => 25, 'complexUC' => 20, 'uaw' => 17, 'tcf' => 0.85, 'ecf' => 0.88, 'actualEffort' => 5940),
    array('simpleUC' => 0, 'averageUC' => 14, 'complexUC' => 8, 'uaw' => 6, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5927),
    array('simpleUC' => 3, 'averageUC' => 18, 'complexUC' => 19, 'uaw' => 17, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5885),
    array('simpleUC' => 5, 'averageUC' => 16, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.08, 'ecf' => 0.77, 'actualEffort' => 5882),
    array('simpleUC' => 1, 'averageUC' => 14, 'complexUC' => 12, 'uaw' => 6, 'tcf' => 0.72, 'ecf' => 0.67, 'actualEffort' => 5880),
    array('simpleUC' => 3, 'averageUC' => 26, 'complexUC' => 14, 'uaw' => 18, 'tcf' => 0.82, 'ecf' => 0.79, 'actualEffort' => 5880),
    array('simpleUC' => 1, 'averageUC' => 10, 'complexUC' => 15, 'uaw' => 6, 'tcf' => 0.96, 'ecf' => 0.96, 'actualEffort' => 5876),
    array('simpleUC' => 0, 'averageUC' => 3, 'complexUC' => 20, 'uaw' => 6, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5873),
    array('simpleUC' => 3, 'averageUC' => 17, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 1.095, 'ecf' => 0.95, 'actualEffort' => 5865),
    array('simpleUC' => 2, 'averageUC' => 17, 'complexUC' => 20, 'uaw' => 18, 'tcf' => 0.965, 'ecf' => 0.755, 'actualEffort' => 5863),
    array('simpleUC' => 3, 'averageUC' => 21, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.98, 'ecf' => 0.97, 'actualEffort' => 5856),
    array('simpleUC' => 2, 'averageUC' => 18, 'complexUC' => 18, 'uaw' => 18, 'tcf' => 1.05, 'ecf' => 0.95, 'actualEffort' => 5800),
    array('simpleUC' => 1, 'averageUC' => 23, 'complexUC' => 22, 'uaw' => 17, 'tcf' => 1.03, 'ecf' => 0.8, 'actualEffort' => 5791),
    array('simpleUC' => 5, 'averageUC' => 30, 'complexUC' => 10, 'uaw' => 19, 'tcf' => 0.95, 'ecf' => 0.92, 'actualEffort' => 5782),
    array('simpleUC' => 5, 'averageUC' => 15, 'complexUC' => 5, 'uaw' => 6, 'tcf' => 1, 'ecf' => 0.92, 'actualEffort' => 5778),
    array('simpleUC' => 5, 'averageUC' => 18, 'complexUC' => 17, 'uaw' => 18, 'tcf' => 0.85, 'ecf' => 0.89, 'actualEffort' => 5775)
);

$start = microtime(true);
$mpucwPSO = new MPUCWPSO();
$numDataset = count($dataset);

echo 'Jumlah data:' . $numDataset . '<br>';
for ($i = 0; $i <= 30 - 1; $i++) {
    echo '<b>Percobaan ke-' . $i . '</b><br>';
    foreach ($dataset as $key => $val) {
        print_r($mpucwPSO->Main($dataset[$key]));
        echo '<br>';
    }
    echo '<p>';
}
