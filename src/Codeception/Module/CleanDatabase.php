<?php

namespace Codeception\Module;

/**
 * @author    Ezra Pool <ezra@digitalnatives.nl>
 * @copyright Digital Natives (c) 2017
 */

use Codeception\Module;

/**
 * Class Transaction
 *
 * @package Codeception\Extensions
 */
class CleanDatabase extends Module
{
    /**
     * @var array
     */
    protected $config = [
        'prefix' => 'wp_'
    ];

    /**
     * Listen to the initialize hook of codeception
     */
    public function _initialize()
    {
        $this->dropEntireDatabase();
    }

    /**
     * Listen to the afterSuite hook of codeception
     */
    public function _afterSuite()
    {
        $this->dropEntireDatabase();
    }

    /**
     * Drop all the tables that begin with the prefix from the config.
     */
    private function dropEntireDatabase()
    {
        /** @var Db $db */
        $db = $this->getModule('Db');

        try {

            $db->driver->executeQuery('start transaction', []);

            $tables = $db->driver->executeQuery('show tables like ?', [$this->config['prefix'] . '%'])
                                 ->fetchAll(\PDO::FETCH_NUM);

            $tables = array_map(function($table){
                return $table[0];
            }, $tables);

            codecept_debug("Dropping tables:\n  - " . implode("\n  - ", $tables));

            foreach ($tables as $table) {
                $db->driver->executeQuery(sprintf('drop table if EXISTS %s', $table), []);
            }

            $db->driver->executeQuery('commit', []);

        } catch (\Exception $e) {
            codecept_debug('Oops, something went wrong, could not delete your tables, rolling back!');
            $db->driver->executeQuery('rollback', []);
        }
    }
}