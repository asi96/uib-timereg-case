<?php

    // Fetch the database file
    $db = new PDO('sqlite:database.db');

    $statement = $db->query("SELECT * FROM Person");

    $data = $statement->fetchAll(PDO::FETCH_ASSOC);

    $jsondata = fetchFilteredServiceJSONData();

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

?>