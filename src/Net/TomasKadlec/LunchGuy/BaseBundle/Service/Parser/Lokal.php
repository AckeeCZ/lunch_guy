<?php
namespace Net\TomasKadlec\LunchGuy\BaseBundle\Service\Parser;

/**
 * Class Lokal
 *
 * Parser for http://www.utopolu.cz/
 *
 * @package Net\TomasKadlec\LunchGuy\BaseBundle\Service\Parser
 */
class Lokal extends AbstractParser
{

    const KEY_STARTERS  = 'Předkrmy';

    protected $filter = [
        "^&nbsp;$",
        "^[\xa0\xc2[:space:]]*$",
    ];

    protected static $selector = 'table.menu-list.first tr';

    public function isSupported($format)
    {
        return ($format == 'lokal');
    }

    public function supports()
    {
        return [ 'lokal' ];
    }

    public function parse($format, $data, $charset = 'UTF-8')
    {
        if ($data) {
            $data = preg_replace('/<span class="allergens(.*?)>(.*?)<\/span>/s', '', $data);
            return parent::parse($format, $data, $charset);
        } else {
            return ['Menu není k dispozici'];
        }
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
    protected function process($data)
    {
        $result = [];
        $key = null;
        foreach ($data as $row) {
            if (!is_array($row) || $this->filter($row))
                continue;
            if (count($row) == 1) {
                if (preg_match('/Předkrmy/', $row[0]))
                    $key = static::KEY_STARTERS;
                else if (preg_match('/Polévky/', $row[0]))
                    $key = static::KEY_SOUPS;
                else if (preg_match('/Hotová.jídla/', $row[0]))
                    $key = static::KEY_MAIN;
                else if (preg_match('/Bezmasá jídla/', $row[0]))
                    $key = static::KEY_MAIN;
                else if (preg_match('/Doporučujeme/', $row[0]))
                    $key = static::KEY_MAIN;
                else if (preg_match('/Menu/', $row[0]))
                    $key = static::KEY_MENU;
                else if (preg_match('/Saláty/', $row[0]))
                    $key = static::KEY_SALADS;
                else if (preg_match('/Dezert/', $row[0]))
                    $key = static::KEY_DESERTS;
                else
                    $key = null;
                continue;
            }
            if ($key !== null && !empty($row[0])) {
                $result[$key][] = [
                    trim($row[0]),
                    (!empty($row[2]) ? 0 + $row[2] : '-')
                ];
            }
        }

        return $result;
    }

}
