<?php

use DateInterval;
use DatePeriod;
use DateTime;

class TemperatureEMA {

    const FILE_NAME = 'weather_statistics.csv';
    const FILE_PATH = 'C:\\Users\\vzheludov\\Desktop\\';
    const FILE_OPEN_MODE = 'r';
    const FILE_CELL_DATE = 0;
    const FILE_CELL_TEMPERATURE = 1;

    const AUTO_DETECT_LINE_ENDINGS = 'auto_detect_line_endings';

    const FORMAT_DATE = 'd.m.Y';
    const FORMAT_TIME = 'H:i';
    const FORMAT_YEAR = 'Y';
    const FORMAT_MONTH = 'm';
    const FORMAT_DAY_OF_WEEK = 'w';

    const DATE_INTERVAL_DURATION = 'P1D';

    const REPORT_TYPE_DAY = 'day';
    const REPORT_TYPE_WEEK = 'week';
    const REPORT_TYPE_MONTH = 'month';

    const HTML_TABLE_OPEN = '<table>';
    const HTML_TABLE_CLOSE = '</table>';
    const HTML_TR_OPEN = '<tr>';
    const HTML_TR_CLOSE = '</tr>';
    const HTML_TD_OPEN = '<td style="border: 1px solid maroon;">';
    const HTML_TD_CLOSE = '</td>';
    const HTML_BR_DOUBLE = '<br><br>';

    const STRING_EMPTY = '';
    const STRING_ORIGINAL_VALUE = 'Исходное t*';
    const STRING_MOVING_VALUE = 'Скользящее t*';
    const STRING_MONTH = 'Месяц ';
    const STRING_WEEK = 'Неделя ';
    const STRING_DAY = 'День ';
    const STRING_TIME = 'Время ';

    const MESSAGE_WRONG_INTERVAL = 'Не удалось определить тип периода';
    const MESSAGE_EMPTY_VALUE = 'Было обнаружено пустое значение, исправьте данные о температуре в строке ';

    const ROW_MONTH = 0;
    const ROW_WEEK = 1;
    const ROW_DAY = 2;
    const ROW_TIME = 3;
    const ROW_ORIGINAL_VALUE = 4;
    const ROW_MOVING_VALUE = 5;

    public function getEMATable($timeInterval = self::REPORT_TYPE_DAY)
    {
        $rowsFromCSV = self::getCSVRows();
        $annualTemperatureMatrix = self::rowsToDateMatrix($rowsFromCSV);

        switch ($timeInterval)
        {
            case self::REPORT_TYPE_DAY:
                return self::getHtmlTableByDays($annualTemperatureMatrix);
            case self::REPORT_TYPE_WEEK:
                return self::getHtmlTableByWeeks($annualTemperatureMatrix);
            case self::REPORT_TYPE_MONTH:
                return self::getHtmlTableByMonths($annualTemperatureMatrix);
            default:
                return self::MESSAGE_WRONG_INTERVAL;
        }
    }

    public static function getEMA(array $numbers, int $n): array
    {
        $m   = count($numbers);
        $α   = 2 / ($n + 1);
        $EMA = [];

        $EMA[] = $numbers[0];

        for ($i = 1; $i < $m; $i++) {
            $EMA[] = ($α * $numbers[$i]) + ((1 - $α) * $EMA[$i - 1]);
        }

        return $EMA;
    }

    public static function getHtmlTableByDays($dateMatrix)
    {
        foreach ($dateMatrix as $years)
        {
            foreach ($years as $months)
            {
                foreach ($months as $weeks)
                {
                    foreach ($weeks as $day => $times)
                    {
                        $emaTemperatureValues = self::getEMA(array_values($times), count($times));

                        $rows = array();

                        $cellHeaders = self::addValueToTableCells($day, self::STRING_EMPTY);
                        $cellOriginalValues = self::addValueToTableCells(self::STRING_ORIGINAL_VALUE, self::STRING_EMPTY);
                        $cellMiddleValues = self::addValueToTableCells(self::STRING_MOVING_VALUE, self::STRING_EMPTY);

                        foreach ($times as $time => $temperature)
                        {
                            $todayEMA = round(array_shift($emaTemperatureValues), 1);

                            $cellHeaders = self::addValueToTableCells($time, $cellHeaders);
                            $cellOriginalValues = self::addValueToTableCells($temperature, $cellOriginalValues);
                            $cellMiddleValues = self::addValueToTableCells($todayEMA, $cellMiddleValues);
                        }

                        $rows[self::ROW_DAY] =  self::placeCellsToRow($cellHeaders);
                        $rows[self::ROW_ORIGINAL_VALUE] = self::placeCellsToRow($cellOriginalValues);
                        $rows[self::ROW_MOVING_VALUE] = self::placeCellsToRow($cellMiddleValues);

                        echo self::getHTMLTable($rows);
                    }
                }
            }
        }
    }

    public static function getHtmlTableByWeeks($dateMatrix)
    {
        foreach ($dateMatrix as $years)
        {
            foreach ($years as $months)
            {
                foreach ($months as $weekNumber => $days)
                {
                    $weekTemperatures = array_flatten($days);

                    $emaTemperatureValues = self::getEMA($weekTemperatures, count($weekTemperatures));

                    $cellHeaderWeek = self::addValueToTableCells(self::STRING_WEEK . $weekNumber, self::STRING_EMPTY);
                    $cellHeaderDays = self::addValueToTableCells(self::STRING_DAY, self::STRING_EMPTY);
                    $cellHeaderTime = self::addValueToTableCells(self::STRING_TIME, self::STRING_EMPTY);
                    $cellOriginalValues = self::addValueToTableCells(self::STRING_ORIGINAL_VALUE, self::STRING_EMPTY);
                    $cellMiddleValues = self::addValueToTableCells(self::STRING_MOVING_VALUE, self::STRING_EMPTY);

                    foreach ($days as $day => $times)
                    {
                        foreach ($times as $time => $temperature)
                        {
                            $todayEMA = round(array_shift($emaTemperatureValues), 1);

                            $cellHeaderDays = self::addValueToTableCells($day, $cellHeaderDays);
                            $cellHeaderTime = self::addValueToTableCells($time, $cellHeaderTime);
                            $cellOriginalValues = self::addValueToTableCells($temperature, $cellOriginalValues);
                            $cellMiddleValues = self::addValueToTableCells($todayEMA, $cellMiddleValues);
                        }
                    }

                    $rows = array();

                    $rows[self::ROW_WEEK] = self::placeCellsToRow($cellHeaderWeek);
                    $rows[self::ROW_DAY] = self::placeCellsToRow($cellHeaderDays);
                    $rows[self::ROW_TIME] = self::placeCellsToRow($cellHeaderTime);
                    $rows[self::ROW_ORIGINAL_VALUE] = self::placeCellsToRow($cellOriginalValues);
                    $rows[self::ROW_MOVING_VALUE] = self::placeCellsToRow($cellMiddleValues);

                    echo self::getHTMLTable($rows);
                }
            }
        }
    }

    public static function getHtmlTableByMonths($dateMatrix)
    {
        foreach ($dateMatrix as $years)
        {
            foreach ($years as $monthNumber => $weeks)
            {
                $monthTemperatures = array_flatten($weeks);

                $emaTemperatureValues = self::getEMA($monthTemperatures, count($monthTemperatures));

                $cellHeaderMonth = self::addValueToTableCells(self::STRING_MONTH . $monthNumber, self::STRING_EMPTY);
                $cellHeaderWeek = self::addValueToTableCells(self::STRING_WEEK, self::STRING_EMPTY);
                $cellHeaderDays = self::addValueToTableCells(self::STRING_DAY, self::STRING_EMPTY);
                $cellHeaderTime = self::addValueToTableCells(self::STRING_TIME, self::STRING_EMPTY);
                $cellOriginalValues = self::addValueToTableCells(self::STRING_ORIGINAL_VALUE, self::STRING_EMPTY);
                $cellMiddleValues = self::addValueToTableCells(self::STRING_MOVING_VALUE, self::STRING_EMPTY);

                foreach ($weeks as $weekNumber => $days)
                {
                    foreach ($days as $day => $times)
                    {
                        foreach ($times as $time => $temperature)
                        {
                            $todayEMA = round(array_shift($emaTemperatureValues), 1);

                            $cellHeaderWeek = self::addValueToTableCells($weekNumber, $cellHeaderWeek);
                            $cellHeaderDays = self::addValueToTableCells($day, $cellHeaderDays);
                            $cellHeaderTime = self::addValueToTableCells($time, $cellHeaderTime);
                            $cellOriginalValues = self::addValueToTableCells($temperature, $cellOriginalValues);
                            $cellMiddleValues = self::addValueToTableCells($todayEMA, $cellMiddleValues);
                        }
                    }
                }

                $rows = array();

                $rows[self::ROW_MONTH] = self::placeCellsToRow($cellHeaderMonth);
                $rows[self::ROW_WEEK] = self::placeCellsToRow($cellHeaderWeek);
                $rows[self::ROW_DAY] = self::placeCellsToRow($cellHeaderDays);
                $rows[self::ROW_TIME] = self::placeCellsToRow($cellHeaderTime);
                $rows[self::ROW_ORIGINAL_VALUE] = self::placeCellsToRow($cellOriginalValues);
                $rows[self::ROW_MOVING_VALUE] = self::placeCellsToRow($cellMiddleValues);

                echo self::getHTMLTable($rows);
            }
        }
    }

    public static function rowsToDateMatrix($rowsFromCSV)
    {
        $annualTemperatureMatrix = array();

        $allDates = array_keys($rowsFromCSV);

        $firstDayDate = new DateTime(array_pop($allDates));
        $lastDayDate = new DateTime(array_shift($allDates));

        $interval = new DateInterval(self::DATE_INTERVAL_DURATION);
        $dateRange = new DatePeriod($firstDayDate, $interval, $lastDayDate);

        $weekNumber = 1;

        foreach ($dateRange as $date)
        {
            $year = $date->format(self::FORMAT_YEAR);
            $month = $date->format(self::FORMAT_MONTH);
            $day = $date->format(self::FORMAT_DATE);

            $annualTemperatureMatrix[$year][$month][$weekNumber][$day] = $rowsFromCSV[$day];

            if ($date->format(self::FORMAT_DAY_OF_WEEK) == 6)
            {
                $weekNumber++;
            }
        }

        return $annualTemperatureMatrix;
    }

    public static function getCSVRows()
    {
        $rowsFromCSV = array();

        $handle = fopen(self::FILE_PATH . self::FILE_NAME, self::FILE_OPEN_MODE);

        ini_set(self::AUTO_DETECT_LINE_ENDINGS,TRUE);

        while ( ($csvRow = fgetcsv($handle, null, ';') ) !== FALSE )
        {
            // Не придумал как иначе исключить получение заголовков из файла
            try
            {
                $day = new DateTime( $csvRow[self::FILE_CELL_DATE]);
            }
            catch (\Exception $error)
            {
                continue;
            }

            $dayDate = $day->format(self::FORMAT_DATE);
            $dayTime = $day->format(self::FORMAT_TIME);

            // В исходном архиве были данные с пропущенными температурами за конкретное время
            if($csvRow[self::FILE_CELL_TEMPERATURE] == self::STRING_EMPTY)
            {
                die(self::MESSAGE_EMPTY_VALUE . $dayDate . $dayTime);
            }
            else
            {
                $rowsFromCSV[$dayDate][$dayTime] = $csvRow[self::FILE_CELL_TEMPERATURE];
            }
        }

        ini_set(self::AUTO_DETECT_LINE_ENDINGS,FALSE);

        fclose($handle);

        return $rowsFromCSV;
    }

    public static function addValueToTableCells($value, string $cells) : string
    {
        return $cells . self::HTML_TD_OPEN. $value . self::HTML_TD_CLOSE;
    }

    public static function placeCellsToRow(string $cells) : string
    {
        return self::HTML_TR_OPEN . $cells . self::HTML_TR_CLOSE;
    }

    public static function getHTMLTable(array $rows) : string
    {
        return self::HTML_TABLE_OPEN . implode('', $rows) . self::HTML_TABLE_CLOSE . self::HTML_BR_DOUBLE;
    }
}
