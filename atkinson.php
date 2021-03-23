<?php

// $data = array(
//     array(670, 691),
//     array(912, 902),
//     array(218, 274),
//     array(595, 479),
//     array(267, 308),
//     array(344, 301),
//     array(229, 234),
//     array(190, 171),
//     array(869, 333),
//     array(109, 159),
//     array(289, 238),
//     array(616, 373),
//     array(557, 308),
//     array(416, 558),
//     array(578, 861),
//     array(438, 423)
// );
$karner_data = array(
    array(7970,6518.22),
    array(7962,6567.13),
    array(7935,6570.01),
    array(7805,6524.64),
    array(7758,6524.97),
    array(7643,6486.70),
    array(7532,6546.78),
    array(7451,6492.45),
    array(7449,6492.38),
    array( 7427,6491.76),
    array( 7406,6494.39),
    array( 7365,6475.72),
    array(7350,6470.80),
    array( 7303,6442.85),
    array(7252,6427.63),
    array( 7245,6421.44),
    array( 7166,6370.10),
    array( 7119,6359.01),
    array(  7111,6367.73),
    array(  7044,6328.96),
    array(  7040,6316.53),
    array(  7028,6301.39),
    array(   6942,6263.58),
    array(  6814,6178.35),
    array(  6809,6166.16),
    array(  6802,6176.16),
    array(  6787,6149.87),
    array(  6764,6146.36),
    array(  6761,6149.46),
    array(  6725,6102.41),
    array(   6690,6075.20),
    array(  6600,5997.88),
    array(   6474,5894.58),
    array(   6433,5832.75),
    array(  6416,5842.02),
    array( 6412,5812.75),
    array(  6400,5819.22),
    array(  6360,5790.23),
    array(  6337,5733.33),
    array(  6240,5654.51),
    array( 6232,5632.47),
    array(  6173,5596.09),
    array(  6160,5581.17),
    array(  6117,5514.76),
    array(  6062,427.72),
    array(  6051,5415.12),
    array(  6048,5423.48),
    array(  6035,5383.61),
    array(  6024,5359.30),
    array(   6023,5364.71),
    array(  5993,5323.01),
    array(  5985,5332.92),
    array(  5971,5293.87),
    array(  5962,5324.18),
    array(  5944,5309.91),
    array(  5940,5294.66),
    array(  5927,5249.87),
    array( 5885,5185.66),
    array( 5882,5164.73),
    array( 5880,5182.22),
    array(5880,5156.78),
    array(  5876,5155.64),
    array(  5873,5177.67),
    array(  5865,5150.67),
    array( 5863,5149.62),
    array( 5856,5129.00),
    array( 5800,5005.77),
    array( 5791,5009.67),
    array( 5782,4975.97),
    array( 5778,4979.80),
    array( 5775,4983.05),
);


$N = count($karner_data);
$sum = 0;
foreach ($karner_data as $key => $val){
    for ($j = 1; $j<=$key; $j++){
        if ( ($key+1) == $j){
            $a = 0;
        } else {
            $pred = $val[1];
            $a = abs($pred - $val[0]);
        }
        echo ($key+1).' '.$val[0].' '.$j.' '.$val[1].' '.$a;
        echo '<br>';
        $sum += $a;
    }
}
echo '<p>';
echo 'MAE Actual Col[0] = '. (array_sum($karner_data[0])/$N);
echo '<br>';
echo $sum. '<br>';
echo 'Exact MAR = '.(2/pow($N,2))*$sum;

echo '<p>';
echo 'Shepperd: '. $N*($N-1). ' '.$sum/($N*($N-1));