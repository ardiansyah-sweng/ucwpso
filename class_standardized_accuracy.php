<?php
include "class_random_guessing.php";

/**
 * Standardized Accuracy Metric Evaluation
 * 
 * @author Ardiansyah
 * @input file_name, column_index of dataset
 * @output array[MARP0_Shepperd, MARP0_Langdon]
 * 
 * Usage example:
 *      $file_name = 'hasil_mpso_sinusoidal.txt';
 *      $value = new StandardizedAccuracy($file_name);
 *      $value->standardizedAccuracy($column_index);
 */
class StandardizedAccuracy
{
    protected $file_name;
    protected $MARPi;

    function __construct($file_name, $MARPi)
    {
        $this->file_name = $file_name;
        $this->MARPi = $MARPi;
    }

    /**
     * Calculate MAR_Pi
     * 
     * @input array[ae] from effort prediction algorithm
     * @output MARPi
     */
    function calculateMARPi()
    {
        return 1674;
    }

    function calculateShepperdMARP0($index_column)
    {
        $sum = 0;
        $predicted_data = RandomGuess::predictP0($this->file_name, $index_column);
        foreach ($predicted_data as $val) {
            $sum += $val[2];
        }
        return $sum / count($predicted_data);
    }

    function calculateLangdonMARP0($index_column)
    {
        if (filesize($this->file_name) == 0) {
            return "The file is DEFINITELY empty";
        }

        //TODO check if txt is not in two column, separated by comma
        //TODO check if file name is not well formatted (txt, csv)

        $predicted_data = RandomGuess::predictP0($this->file_name, $index_column);

        $numberOfData = count($predicted_data);
        $sum = 0;
        foreach ($predicted_data as $key => $val) {
            for ($j = 1; $j <= $key; $j++) {
                if (($key + 1) == $j) {
                    $absoluteError = 0;
                } else {
                    $absoluteError = abs(floatval($val[1]) - floatval($val[0]));
                }
                $sum += $absoluteError;
            }
        }
        return (2 / pow($numberOfData, 2)) * $sum;
    }

    function standardizedAccuracy($index_column)
    {
        $ret['sa_shepperd'] = (1 - ($this->calculateMARPi() / $this->calculateShepperdMARP0($index_column))) * 100;
        $ret['sa_langdon'] = (1 - ($this->calculateMARPi() / $this->calculateLangdonMARP0($index_column))) * 100;
        return $ret;
    }
}
