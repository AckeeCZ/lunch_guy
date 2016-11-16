<?php
/**
 * Created by PhpStorm.
 * User: Lukas Koci <lukas.koci@ackee.cz>
 * Date: 7.11.2016
 * Time: 23:06
 */

namespace Net\TomasKadlec\LunchGuy\BaseBundle\Service\Parser;

/**
 * Class Gate
 *
 * Parser for http://www.gate-restaurant.cz/
 *
 * @package Net\TomasKadlec\LunchGuy\BaseBundle\Service\Parser
 */
class Gate extends AbstractParser {

    protected $filter = [
        "^&nbsp;$",
        "^[\xa0\xc2[:space:]]*$",
    ];

    protected static $selector = 'table.content tr';

    public function isSupported($format) {
        return ($format == 'gate');
    }

    public function supports() {
        return ['gate'];
    }

    public function parse($format, $data, $charset = 'UTF-8') {
        $data = json_decode($data);
        if (!empty($data->menu)) {
            return parent::parse($format, $data->menu, $charset);
        } else
            return ['Menu není k dispozici'];
    }

    /**
     * Takes decision on filtering data resulting from the crawler
     *
     * @param $row
     * @return bool
     */
    protected function filter($row) {
        if (empty($row)) {
            return TRUE;
        }

        foreach ($this->filter as $skip) {
            if (isset($row[0]) && (!isset($row[1]) || !isset($row[2])) && preg_match("/{$skip}/", $row[0])) {
                return TRUE;
            }

        }

        return FALSE;
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
        
        foreach ($data as $row) {
            if (!is_array($row) || $this->filter($row)) {
                continue;
            }
            if (count($row) == 1) {
                if (preg_match('/Polévka/', $row[0])) {
                    $key = static::KEY_SOUPS;
                } else if (preg_match('/Hlavní jídlo/', $row[0])) {
                    $key = static::KEY_MAIN;
                } else {
                    $key = NULL;
                }
                continue;
            }
            if ($key !== NULL && !empty($row[1])) {
                $result[$key][] = [
                    trim($row[1]),
                    (!empty($row[2]) ? 0 + $row[2] : '-')
                ];
            }
        }

        return $result;
    }

}
