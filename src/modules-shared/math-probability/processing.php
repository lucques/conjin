<?
    /**
     * Sample absolute frequencies
     * @param int $seed: Random seed
     * @param int $n How many times to sample
     * @param array $pdf: Probability density function: Keys are the outcomes, values are the probabilities (max. precision: 0.001)
     * @return array: Abs. frequencies of the outcomes: Keys are the outcomes, values are the frequencies
     */
    function prob_sample_absolute_frequencies(int $seed, int $n, array $pdf) {
        // Set a fixed seed
        mt_srand($seed);

        $outcomes = array_keys($pdf);
        $frequencies = array_fill_keys($outcomes, 0);

        for ($i = 0; $i < $n; $i++) {
            $r = mt_rand(0, 1000) / 1000;
            $cumulative = 0;
            foreach ($outcomes as $outcome) {
                $cumulative += $pdf[$outcome];
                if ($r <= $cumulative) {
                    $frequencies[$outcome]++;
                    break;
                }
            }
        }
        return $frequencies;
    }

    /**
     * Convert absolute frequencies to relative frequencies
     * @param array $frequencies: Abs. frequencies of the outcomes: Keys are the outcomes, values are the frequencies
     * @return array: Rel. frequencies of the outcomes: Keys are the outcomes, values are the relative frequencies
     */
    function prob_absolute_to_relative_frequencies(array $frequencies) {
        $total = array_sum($frequencies);
        $relative_frequencies = [];
        foreach ($frequencies as $outcome => $frequency) {
            $relative_frequencies[$outcome] = $frequency / $total;
        }
        return $relative_frequencies;
    }
?>