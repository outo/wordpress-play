<?php

class WPPainInducerDataset
{

    private $prefixedTableName;

    /**
     * WPPainInducerDataset constructor.
     * @param $tableName - name of table to store pain
     */
    public function __construct($tableName)
    {
        //FIXME: if you want to parametrise tableName then find a way to sanitise it against SQL injection (missing here)
        global $wpdb;
        $this->prefixedTableName = $wpdb->prefix . $tableName;
    }


    /**
     * Initialisation will ensure the table exists.
     */
    public function initialise()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sqlCreateTable = "CREATE TABLE IF NOT EXISTS $this->prefixedTableName (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `type_of_pain` text NOT NULL,
    UNIQUE (`id`)
    ) $charset_collate;";
        dbDelta($sqlCreateTable);
    }


    /**
     * Return resultset of a table with given limit applied.
     * @param $limit
     * @return mixed
     */
    public function select($limit)
    {
        global $wpdb;

        $sqlSelect = $wpdb->prepare(
            "SELECT * FROM $this->prefixedTableName as T ORDER BY T.id DESC LIMIT %d",
            $limit);

        return $wpdb->get_results($sqlSelect, OBJECT);
    }

    public function insert($typeOfPain)
    {
        global $wpdb;

        //FIXME: it looks like the following is already delegating into prepared statement (good)
        return $wpdb->insert(
            $this->prefixedTableName,
            array(
                'type_of_pain' => $typeOfPain
            )
        );
    }
}