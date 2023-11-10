<?php

    // Fetch the database file
    $db = new PDO('sqlite:database.db');
    
    $jsondata = fetchFilteredServiceJSONData();

    updateDatabase($db, $jsondata);

    $sql = "SELECT * FROM Tjeneste";

    $response = $db->query($sql);

    // Build the website
    echo file_get_contents("header.php");
?>
<!DOCTYPE html>
<html>
    <div class="body-container">
    <div class="description-top">
        <p>Her finner du en oversikt over tjenester tilhørende IT- Digitalt læringsmiljø</p>
    </div>
      <div class="table-container">
        <table class="services-data-table">
            <tr class="top-table-row">
                <td class="top"> ID </td>
                <td class="top"> Navn </td>
                <td class="top"> Service Type </td>
                <td class="top"> Supplier </td>
                <td class="top"> Owner </td>
                <td class="top"> Timer </td>
                <td class="top"> Handling </td>
            </tr>
            <?php
                $result = $response->fetchAll(PDO::FETCH_ASSOC);

                foreach ($result as $row) {
                    echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['servicetype']}</td>
                    <td>{$row['supplier']}</td>
                    <td>{$row['owner']}</td>
                    <td>10</td>
                    <td><button type='button'>Legg til timer</button></td></tr>";
                }
            ?>
        </table>
      </div>
</html>

<?php

    echo file_get_contents("footer.php");

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
        transferCSVToDataBase($db);
    }

    function checkForProperExistingSchema($db) {

        // Indiviudal checks if the tables for the case
        $checkPerson = databaseTableExists($db, 'Person');
        $checkArbeidstid = databaseTableExists($db, 'Arbeidstid');
        $checkTjeneste = databaseTableExists($db, 'Tjeneste');

        if ($checkPerson == FALSE) {
            $sql = "CREATE TABLE Person (
                person_id	INTEGER NOT NULL,
                fornavn	TEXT,
                etternavn	TEXT,
                kjønn	TEXT,
                email	TEXT,
                alder	INTEGER,
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

        echo "<script>console.log('Database schema created successfully...')</script>";
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

        echo "<script>console.log('Transferring JSON to database...')</script>";

        foreach ($jsondata as $entry) {
            
            // Check if the current service already exists
            $sql = "SELECT * FROM Tjeneste WHERE tjeneste_id={$entry['id_num']}";
            $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if($result) {
                // Entry exists already, skipping
                echo "<script>console.log('Found an existing service record in database - skipping...')</script>";
            } else {

            // create new entry
            $sql = "INSERT INTO Tjeneste (tjeneste_id, id, name, servicetype, supplier, owner) 
                    VALUES ({$entry['id_num']}, '{$entry['id']}', '{$entry['name']}', '{$entry['servicetype']}', '{$entry['supplier']}', '{$entry['owner']}')";

            if($db->query($sql)) {
                echo "<script>console.log('New service record created successfully in database!')</script>";
            } else {
                echo "Error: " . $sql . "<br>";
            }
        }
        }
    }

    // Function that takes the CSV data and creates new records for them in the Person table
    function transferCSVToDataBase($db) {
        
        echo "<script>console.log('Transferring CSV data to database...')</script>";
        // Retrieve the file
        $csv = file('people.csv');
        $csv_data = [];

        // Put the data into an array
        foreach($csv as $line) {
            $csv_data[] = str_getcsv($line);
        }

        // Loop through the array and insert each person into the database
        foreach($csv_data as $element) {

            // Check if the current service already exists
            $sql = "SELECT * FROM Person WHERE person_id={$element[0]}";
            $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if($result) {
                // Entry exists already, skipping
                echo "<script>console.log('Found an existing person record in database - skipping...')</script>";
            } else {

            $sql = "INSERT INTO Person (person_id, fornavn, etternavn, kjønn, email, alder)
                    VALUES ({$element[0]}, '{$element[1]}', '{$element[2]}', '{$element[3]}', '{$element[4]}', '{$element[5]}')";

            if($db->query($sql)) {
                echo "<script>console.log('New person record created successfully in database!')</script>";
            } else {
                echo "Error: " . $sql . "<br>";
            }
        }
    }
    }

    function displayDatabaseDataAsTable($db) {

        $sql = "SELECT * FROM Tjeneste";

        if ( !( $response = $db->query($sql))) {
            echo "<script>console.log('Error retrieving data from database into table')</script>";
        } else {
        
        }
    }
?>