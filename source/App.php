<?php

    main();

    function main() {

        // Fetch the database file
        $db = new PDO('sqlite:database.db');

        /*
        $statement = $db->query("SELECT * FROM Person");

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);
        */

        $jsondata = fetchFilteredServiceJSONData();

        updateDatabase($db, $jsondata);
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

    function updateDatabase($db, $jsondata) {

        checkForProperExistingSchema($db);
        transferJSONToDatabase($db, $jsondata);

    }

    function checkForProperExistingSchema($db) {

        // Indiviudal checks if the tables for the case
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

        echo "Database schema created successfully... <br>";
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

    // Function that transfers the filtered JSON data to the database
    function transferJSONToDatabase($db, $jsondata) {

        echo "Transferring JSON to database...<br>";

        foreach ($jsondata as $entry) {
            
            // Check if the current service already exists
            $sql = "SELECT * FROM Tjeneste WHERE tjeneste_id={$entry['id_num']}";
            $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if($result) {
                // Entry exists already, skipping
                echo "Found an existing record - skipping... <br>";
            } else {

            // create new entry
            $sql = "INSERT INTO Tjeneste (tjeneste_id, id, name, servicetype, supplier, owner) 
                    VALUES ({$entry['id_num']}, '{$entry['id']}', '{$entry['name']}', '{$entry['servicetype']}', '{$entry['supplier']}', '{$entry['owner']}')";

            if($db->query($sql)) {
                echo "New record created successfully! <br>";
            } else {
                echo "Error: " . $sql . "<br>";
            }
        }
        }
    }
?>