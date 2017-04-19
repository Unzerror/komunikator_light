<?php

/*
 *  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

 *    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
 *    Copyright (C) 2012-2013, ООО «Телефонные системы»

 *    ЭТОТ ФАЙЛ является частью проекта «Komunikator»

 *    Сайт проекта «Komunikator»: http://komunikator.ru/
 *    Служба технической поддержки проекта «Komunikator»: E-mail: support@komunikator.ru

 *    В проекте «Komunikator» используются:
 *      исходные коды проекта «YATE», http://yate.null.ro/pmwiki/
 *      исходные коды проекта «FREESENTRAL», http://www.freesentral.com/
 *      библиотеки проекта «Sencha Ext JS», http://www.sencha.com/products/extjs

 *    Web-приложение «Komunikator» является свободным и открытым программным обеспечением. Тем самым
 *  давая пользователю право на распространение и (или) модификацию данного Web-приложения (а также
 *  и иные права) согласно условиям GNU General Public License, опубликованной
 *  Free Software Foundation, версии 3.

 *    В случае отсутствия файла «License» (идущего вместе с исходными кодами программного обеспечения)
 *  описывающего условия GNU General Public License версии 3, можно посетить официальный сайт
 *  http://www.gnu.org/licenses/ , где опубликованы условия GNU General Public License
 *  различных версий (в том числе и версии 3).

 *  | ENG | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

 *    "Komunikator" is a web interface for IP-PBX "YATE" configuration and management
 *    Copyright (C) 2012-2013, "Telephonnyie sistemy" Ltd.

 *    THIS FILE is an integral part of the project "Komunikator"

 *    "Komunikator" project site: http://komunikator.ru/
 *    "Komunikator" technical support e-mail: support@komunikator.ru

 *    The project "Komunikator" are used:
 *      the source code of "YATE" project, http://yate.null.ro/pmwiki/
 *      the source code of "FREESENTRAL" project, http://www.freesentral.com/
 *      "Sencha Ext JS" project libraries, http://www.sencha.com/products/extjs

 *    "Komunikator" web application is a free/libre and open-source software. Therefore it grants user rights
 *  for distribution and (or) modification (including other rights) of this programming solution according
 *  to GNU General Public License terms and conditions published by Free Software Foundation in version 3.

 *    In case the file "License" that describes GNU General Public License terms and conditions,
 *  version 3, is missing (initially goes with software source code), you can visit the official site
 *  http://www.gnu.org/licenses/ and find terms specified in appropriate GNU General Public License
 *  version (version 3 as well).

 *  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */
/*
 * Для совместимости с системами, где нет этой ф-ции. Преобразуем время, заданное соотвественно формату
 * 	strptime() returns an array with the date parsed, or FALSE on error. 
 *
 * Month and weekday names and other language dependent strings respect the current
 * locale set with setlocale() (LC_TIME). 
 *
 * Функция уже есть в PHP PHP 5 >= 5.1.0RC1. Замечание: Для Windows-платформ эта функция не реализована.
 *
 * Функция обратна функции: strtotime()
 *
 * @author Lionel SAURON 
 * @version 1.0 
 * @public 
 *  
 * @param $sDate(string)    The string to parse (e.g. returned from strftime()). 
 * @param $sFormat(string)  The format used in date  (e.g. the same as used in strftime()). 
 * @return (array)          Returns an array with the <code>$sDate</code> parsed, or <code>false</code> on error. 

  Таблица 1. The following parameters are returned in the array

  parameters Description
  tm_sec 		Seconds after the minute (0-61)
  tm_min 		Minutes after the hour (0-59)
  tm_hour 	Hour since midnight (0-23)
  tm_mday 	Day of the month (1-31)
  tm_mon 		Months since January (0-11)
  tm_year 	Years since 1900
  tm_wday 	Days since Sunday (0-6)
  tm_yday 	Days since January 1 (0-365)
  unparsed 	the date part which was not recognized using the specified format

 */
if (function_exists("strptime") == false) {

    function strptime($sDate, $sFormat) {
        $aResult = array
            (
            'tm_sec' => 0,
            'tm_min' => 0,
            'tm_hour' => 0,
            'tm_mday' => 1,
            'tm_mon' => 0,
            'tm_year' => 0,
            'tm_wday' => 0,
            'tm_yday' => 0,
            'unparsed' => $sDate,
        );

        while ($sFormat != "") {
            // ===== Search a %x element, Check the static string before the %x ===== 
            $nIdxFound = strpos($sFormat, '%');
            if ($nIdxFound === false) {

                // There is no more format. Check the last static string. 
                $aResult['unparsed'] = ($sFormat == $sDate) ? "" : $sDate;
                break;
            }

            $sFormatBefore = substr($sFormat, 0, $nIdxFound);
            $sDateBefore = substr($sDate, 0, $nIdxFound);

            if ($sFormatBefore != $sDateBefore)
                break;

            // ===== Read the value of the %x found ===== 
            $sFormat = substr($sFormat, $nIdxFound);
            $sDate = substr($sDate, $nIdxFound);

            $aResult['unparsed'] = $sDate;

            $sFormatCurrent = substr($sFormat, 0, 2);
            $sFormatAfter = substr($sFormat, 2);

            $nValue = -1;
            $sDateAfter = "";
            switch ($sFormatCurrent) {
                case '%S': // Seconds after the minute (0-59) 

                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if (($nValue < 0) || ($nValue > 59))
                        return false;

                    $aResult['tm_sec'] = $nValue;
                    break;

                // ---------- 
                case '%M': // Minutes after the hour (0-59) 
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if (($nValue < 0) || ($nValue > 59))
                        return false;

                    $aResult['tm_min'] = $nValue;
                    break;

                // ---------- 
                case '%H': // Hour since midnight (0-23) 
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if (($nValue < 0) || ($nValue > 23))
                        return false;

                    $aResult['tm_hour'] = $nValue;
                    break;

                // ---------- 
                case '%d': // Day of the month (1-31) 
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if (($nValue < 1) || ($nValue > 31))
                        return false;

                    $aResult['tm_mday'] = $nValue;
                    break;

                // ---------- 
                case '%m': // Months since January (0-11) 
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if (($nValue < 1) || ($nValue > 12))
                        return false;

                    $aResult['tm_mon'] = ($nValue - 1);
                    break;

                // ---------- 
                case '%Y': // Years since 1900 
                    sscanf($sDate, "%4d%[^\\n]", $nValue, $sDateAfter);

                    if ($nValue < 1900)
                        return false;

                    $aResult['tm_year'] = ($nValue - 1900);
                    break;

                // ---------- 
                default: break 2; // Break Switch and while 
            }

            // ===== Next please ===== 
            $sFormat = $sFormatAfter;
            $sDate = $sDateAfter;

            $aResult['unparsed'] = $sDate;
        } // END while($sFormat != "") 
        // ===== Create the other value of the result array ===== 
        $nParsedDateTimestamp = mktime($aResult['tm_hour'], $aResult['tm_min'], $aResult['tm_sec'], $aResult['tm_mon'] + 1, $aResult['tm_mday'], $aResult['tm_year'] + 1900);

        // Before PHP 5.1 return -1 when error 
        if (($nParsedDateTimestamp === false) || ($nParsedDateTimestamp === -1))
            return false;

        $aResult['tm_wday'] = (int) strftime("%w", $nParsedDateTimestamp); // Days since Sunday (0-6) 
        $aResult['tm_yday'] = (strftime("%j", $nParsedDateTimestamp) - 1); // Days since January 1 (0-365) 

        return $aResult;
    }

// END of function 
} // END if(function_exists("strptime") == false) 
?>
