<?php

    main();

    function main() {

        // Fetch the database file
        $db = new PDO('sqlite:database.db');

        updateDatabase($db);

        $statement = $db->query("SELECT * FROM Person");

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);

        $jsondata = fetchFilteredServiceJSONData();
    }

    function fetchFilteredServiceJSONData() {

        // Send GET request for the JSON-file
        $response = file_get_contents("http://gw-uib.intark.uh-it.no/tk-topdesk/svc.json");
        $response_json = json_decode($response, TRUE);

        $filtered_json = array();
        
        // Filter out the values on specified criteria for case
        foreach ( $response_json as $entry ) {

            if($entry['meta']['archived'] == false) {

                if($entry['operatorgroup_firstline'] == 'IT- Digitalt læringsmiljø' || $entry['operatorgroup_secondline'] == 'IT- Digitalt læringsmiljø') {
                    $filtered_json[] = $entry;
                }
            }
        }

        return $filtered_json;
    }

    function updateDatabase($db) {

        checkForProperExistingSchema($db);
    }

    function checkForProperExistingSchema($db) {

        $checkPerson = databaseTableExists($db, 'Person');
        $checkArbeidstid = databaseTableExists($db, 'Arbeidstid');
        $checkTjeneste = databaseTableExists($db, 'Tjeneste');

        if ($checkPerson == FALSE) {
            $sql = "CREATE TABLE Person (
                person_id	INTEGER NOT NULL,
                navn	TEXT,
                PRIMARY KEY('person_id')
            )";
            $db->query($sql);
            $checkPerson == TRUE;
        }

        if ($checkTjeneste == FALSE) {
            $sql = "CREATE TABLE Tjeneste (
                tjeneste_id	INTEGER NOT NULL,
                id	TEXT,
                name	TEXT,
                servicetype	TEXT,
                supplier	TEXT,
                owner	TEXT,
                PRIMARY KEY('tjeneste_id')
            )";
            $db->query($sql);
            $checkTjeneste == TRUE;
        }

        if ($checkArbeidstid == FALSE) {
            $sql = "CREATE TABLE Arbeidstid (
                arbeidstid_id	INTEGER NOT NULL,
                ansatt_id	INTEGER NOT NULL,
                tjeneste_id	INTEGER NOT NULL,
                timer	INTEGER,
                PRIMARY KEY('arbeidstid_id'),
                FOREIGN KEY('ansatt_id') REFERENCES Person('person_id'),
                FOREIGN KEY('tjeneste_id') REFERENCES Tjeneste('tjeneste_id')
            )";
            $db->query($sql);
            $checkArbeidstid == TRUE;
        }

        echo "Database schema created successfully";
    }

    // Helper function to quickly check if table exists
    function databaseTableExists($db, $table) {

        try {
            $result = $db->query("SELECT 1 FROM {$table} LIMIT 1");
        } catch (Exception $e) {
            return FALSE;
        }

        if ($result !== FALSE) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
