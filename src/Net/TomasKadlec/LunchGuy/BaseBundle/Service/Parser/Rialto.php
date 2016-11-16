<?php
/**
 * Created by PhpStorm.
 * User: Lukas Koci <lukas.koci@ackee.cz>
 * Date: 10.11.2016
 * Time: 23:15
 */

namespace Net\TomasKadlec\LunchGuy\BaseBundle\Service\Parser;

/**
 * Class Rialto
 *
 * Parser for http://www.rialtopizza.cz/
 *
 * @package Net\TomasKadlec\LunchGuy\BaseBundle\Service\Parser
 */
class Rialto extends AbstractParser {

    const KEY_LEMONADES  = 'Domácí limonády a ostatní nápoje';

    protected $filter = [
        "^&nbsp;$",
        "^[\xa0\xc2[:space:]]*$",
        "Pizzeria[\xa0\xc2[:space:]]*www.rialtopizza.cz",
        "POLEDNÍ[\xa0\xc2[:space:]]*MENU[\xa0\xc2[:space:]]*",
        "Objednávejte[\xa0\xc2[:space:]]*",
    ];

    protected static $selector = 'div.boxx:nth-child(5) tr'; // todo fix child number === day in week number

    public function isSupported($format) {
        return ($format == 'rialto');
    }

    public function supports() {
        return ['rialto'];
    }

    public function parse($format, $data, $charset = 'UTF-8') {
        if ($data) {
            return parent::parse($format, $data, $charset);
        } else
            return ['Menu není k dispozici'];
    }

    /**
     * Takes decision on filtering data resulting from the crawler
     *
     * @param $row
     * @return bool
     */
    protected function filter($row)
    {
        if (empty($row))
            return true;
        foreach ($this->filter as $skip) {
            if (isset($row[0]) && (!isset($row[1]) || !isset($row[2])) && preg_match("/{$skip}/", $row[0]))
                return true;
        }
        return false;
    }

    /**
     * Transforms data from the crawler to an internal array
     *
     * @param $data
     * @return array
     */
    protected function process($data) {
        $result = [];
        $key = NULL;
        $price = NULL;

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (count($row) == 3 && !empty($row[1])) {
                if (preg_match('/AKCE !!!.*/', $row[1])) {
                    $key = static::KEY_LEMONADES;
                } else if (preg_match('/Polévka:/', $row[1])) {
                    $key = static::KEY_SOUPS;
                } else {
                    $key = static::KEY_MAIN;
                }
                $price = $row[2];
                continue;
            }
            if ($key !== NULL && count($row) == 2 && !empty($row[1])) {
                $meal = preg_replace('/\(.*?\)/' ,'', $row[1]);
                $meal = preg_replace('/(\.*)|([0-9]*,-)|([0-9]*,*[0-9]*)/' ,'', $meal);
                $result[$key][] = [
                    trim($meal),
                    (!empty($price) ? 0 + $price : '-')
                ];
            }
        }

        return $result;
    }

}