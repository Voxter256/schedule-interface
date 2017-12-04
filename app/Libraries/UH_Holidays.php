<?php

namespace App\Libraries;

class UH_Holidays
{
    // Source: https://gist.github.com/Greg-Boggs/3d05dbb44e664cab270b
    private $year;
    private $list = [];
    const ONE_DAY = 86400; // Number of seconds in one day

    function __construct($year = null, $timezone = 'America/New_York'){
        try
        {
            if (! date_default_timezone_set($timezone))
            {
                throw new Exception($timezone.' is not a valid timezone.');
            }

            $this->year = (is_null($year))? (int) date("Y") : (int) $year;
            if (! is_int($this->year) || $this->year < 1997)
            {
                throw new Exception($year.' is not a valid year. Valid values are integers greater than 1996.');
            }

            $this->set_list();
        }

        catch(Exception $e)
        {
            echo $e->getMessage();
            exit();
        }
    }

    private function adjust_fixed_holiday($timestamp)
    {
        $weekday = date("w", $timestamp);
        if ($weekday == 0)
        {
            return $timestamp + self::ONE_DAY;
        }
        if ($weekday == 6)
        {
            return $timestamp - self::ONE_DAY;
        }
        return $timestamp;
    }

    private function set_list()
    {
        for($relativeYear=0;$relativeYear<=1;$relativeYear++){  // This year and next year
            $this->list[] =
                mktime(0, 0, 0, 1, 1, $this->year + $relativeYear);
                // New Year's Day: January 1st
            $this->list[] =
                strtotime("last Monday of May 2017" . (string) ($this->year + $relativeYear));
                // Memorial Day: last Monday of May
            $this->list[] =
                mktime(0, 0, 0, 7, 4, $this->year + $relativeYear);
                // Independence day: July 4, if not Saturday/Sunday
            $this->list[] =
                strtotime("first Monday of September " . (string) ($this->year  + $relativeYear));
                // Labor Day: 1st Monday of September
            $this->list[] =
                strtotime("4 Thursdays", mktime(0, 0, 0, 11, 1, $this->year + $relativeYear));
                // Thanksgiving Day: 4th Thursday of November
            $this->list[] =
                mktime(0, 0, 0, 12, 25, $this->year + $relativeYear);
                // Christmas: December 25 every year, if not Saturday/Sunday
        }
    }

    public function get_list()
    {
        return $this->list;
    }

    public function is_holiday($timestamp)
    {
        foreach ($this->list as $holiday)
        {
           if ($timestamp == $holiday["timestamp"]) return true;
        }

        return false;
    }
}
