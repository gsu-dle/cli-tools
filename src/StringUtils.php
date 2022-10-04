<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

class StringUtils
{
    /**
     * @param int $startTime
     * 
     * @return string
     */
    public static function getElapsedTime(int|float $startTime): string
    {
        return static::formatElapsedTime(round(microtime(true) - $startTime, 3));
    }


    /**
     * @param float $elapsedTime
     * 
     * @return string
     */
    public static function formatElapsedTime(float $elapsedTime): string
    {
        $elapsedTime = abs($elapsedTime);
        $elapsed = intval($elapsedTime);
        $hours = intval(gmdate("H", $elapsed));
        $minutes = intval(gmdate("i", $elapsed));
        $seconds = intval(gmdate("s", $elapsed));
        $fraction = explode(".", number_format(round($elapsedTime - $elapsed, 3), 3))[1];

        $elapsed = "{$seconds}.{$fraction} sec";
        if ($hours > 0 || $minutes > 0) {
            $elapsed = "{$minutes} min, " . $elapsed;
            if ($hours > 0) {
                $elapsed = "{$hours} hr, " . $elapsed;
            }
        }

        return $elapsed;
    }
}
