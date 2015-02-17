<?php


use Luracast\Restler\RestException;
use Respect\Data\Collections\Filtered;
use Routelandia\Entities;
use Routelandia\Entities\OrderedStation;

class TrafficStats{

    //To test this, use
    /*
curl -X POST http://localhost:8080/api/trafficstats -H "Content-Type: application/json" -d '
{
    "startpt": {
       "lng": -122.78281856328249,
       "lat": 45.44620177127501
       },
    "endpt": {
       "lng": -122.74895907829,
       "lat": 45.424207914266
    },
    "time": {
       "midpoint": "17:30",
       "weekday": "Thursday"
    }
}
'
     */
    /**
     * Takes in a JSON object and returns traffic calculations
     *
     * The JSON object sent to describe the request should be in the following format:
     *
     * <code><pre>
     * {<br />
     *  &nbsp;&nbsp; "startpt": {<br />
     *  &nbsp;&nbsp;  &nbsp;&nbsp;           "lng": -122.00, <br />
     *  &nbsp;&nbsp;  &nbsp;&nbsp;           "lat": 45.00 <br />
     *  &nbsp;&nbsp;  &nbsp;&nbsp;         }, <br />
     *  &nbsp;&nbsp; "endpt":   { <br />
     *  &nbsp;&nbsp;  &nbsp;&nbsp;            "lng": -122.01, <br />
     *  &nbsp;&nbsp;  &nbsp;&nbsp;            "lat": 45.00 <br />
     *  &nbsp;&nbsp;            }, <br />
     *  &nbsp;&nbsp; "time":    { <br />
     *  &nbsp;&nbsp;  &nbsp;&nbsp;            "midpoint": "17:30", <br />
     *  &nbsp;&nbsp;   &nbsp;&nbsp;           "weekday": "Thursday" <br />
     *  &nbsp;&nbsp;            } <br />
     * }
     * </pre></code>
     *
     * The lat and lng should be sent as numbers. Midpoint could be sent either as either "17:30" or "5:30 PM".
     * The weekday parameter should be a text string with the name of the day of the week to run statistics on.
     *
     * @param array $request_data  JSON payload from client
     * @param $startpt
     * @param $endpt
     * @param $time
     * @return array Spits back what it was given
     * @throws RestException
     * @url POST
     */
    // If we want to pull aprt the json payload with restler
    // http://stackoverflow.com/questions/14707629/capturing-all-inputs-in-a-json-request-using-restler
    public function doPOST ($startpt, $endpt, $time,$request_data=null)
    {
        if (empty($request_data)) {
            throw new RestException(412, "JSON object is empty");
        }
         // To grab data from $request_data, syntax is
         // $request_data['startPoint'];

        $startPoint[0] = $startpt['lat'];
        $startPoint[1] = $startpt['lng'];
        $endPoint[0] = $endpt['lat'];
        $endPoint[1] = $endpt['lng'];
        try {
            $validStations = $this->getNearbyStations($startPoint,$endPoint);
        }catch (Exception $e){
            throw new RestException(400,$e->getMessage());
        }


        date_default_timezone_set('America/Los_Angeles');
        $STUPID_DEMO_RESULT = Array();
        $STUPID_DEMO_TIME = "15:45";
        $STUPID_DEMO_HIGHWAYS = OrderedStation::FetchForHighway(12);
        for($i=0; $i<12; $i++) {
          $STUPID_DEMO_OBJ = new stdClass;
          $STUPID_DEMO_TIME = strtotime("+15 minutes", strtotime($STUPID_DEMO_TIME));
          $STUPID_DEMO_OBJ->time_of_day = date('h:i', $STUPID_DEMO_TIME);
          $STUPID_DEMO_OBJ->duration = rand(2,30);
          $STUPID_DEMO_OBJ->stations_used = $STUPID_DEMO_HIGHWAYS;
          array_push($STUPID_DEMO_RESULT, $STUPID_DEMO_OBJ);
        }
        return $STUPID_DEMO_RESULT;
//        return $request_data;
    }

    /**
     * Takes in a float coordinate and returns the station object closest to that point.
     *
     * Takes in a float coordinate and returns the station object closest to that point.
     *
     * @param $startPoint
     * @param $endPoint
     * @return array OrderedStation
     * @throws Exception
     * @internal param array $point 2 element array with two floats
     */
    function getNearbyStations($startPoint,$endPoint){

        $startStations = OrderedStation::getStationsFromCoord($startPoint);
        $endStations = OrderedStation::getStationsFromCoord($endPoint);
        //this type validation should probably be in a different function
        try {
            $finalStations = Stations::ReduceStationPairings($startStations, $endStations);
        }catch (Exception $e){
            throw new Exception("Given coordinates refer to stations on different highways. ".$e->getMessage());
        }
        return $finalStations;

    }

    /**
     * Converts string coordinates into floats
     *
     * Converts string coordinates into floats
     * This takes string from $request_data['point'] and converts it
     * into real floats.
     * NOTE: 90% sure this wont be needed but it should
     * be kept incase we change how the project is structured again.
     *
     * @param String $coord The String containing coords
     * @return array float The two coords separated into an array
     */
    function parseCoordFromString($coord){
        $coord = trim($coord,"[]");
        $pieces = explode(",",$coord);
        $p1 = (double)$pieces[0];
        $p2 = (double)$pieces[1];

        return array($p1,$p2);

    }

}

