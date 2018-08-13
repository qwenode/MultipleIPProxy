<?php

class Helper
{
    public static function isAllowed($ip)
    {
        $isAllow = FALSE;
        foreach (Configure::$allowed as $item) {
            if ($item == '*') {
                $isAllow = TRUE;
                break;
            }
            if ($item == $ip) {
                $isAllow = TRUE;
                break;
            }
        }

        return $isAllow;
    }

}