<?php

namespace Routelandia\Entities;

use Respect\Relational\Mapper;
use Routelandia\DB;

/**
 * Represents a single Station
 *
 * == Notes about Stations in the Portal Database ==
 * Station's have ID's grouped into thousands:
 *   1000's: Inductive loop detectors
 *   2000's: HOV lane detectors in vancouver. (Or maybe elsewhere later)
 *   3000's: HD Radar detectors.
 *   5000's: Onramp detectors.
 *
 * An OrderedStation is much like a
 * station, except it's coming in via
 * the OrderedStations view, which scopes
 * both the columns chosen, which stations
 * are selected (Throws out all but 1000/3000),
 * and orders the station by their position in
 * the linked-list of stations.
 * The orderedStations view is designed to give
 * either a single station, or all stations for a
 * specific highway. (The ordering doesn't make much
 * sense when you select all of them)
 */
class OrderedStation {

  public $stationid;
  public $upstream;
  public $downstream;
  public $highwayid;
  public $opposite_stationid;
  public $milepost;
  public $length;
  public $locationtext;
  protected $linked_list_path;
  public $linked_list_position;

  // We're going to convert these and output them as the geojson_x columns
  // so we'll hide the raw columns by setting them protected.
  protected $segment_raw;
  protected $segment_50k;
  protected $segment_100k;
  protected $segment_250k;
  protected $segment_500k;
  protected $segment_1000k;

  /**
   * @Relational\isNotColumn
   */
  public $geojson_raw;

  /**
   * This is a bad hack to override what the ORM is doing and trigger
   * the JSON to be decoded when the segment_x property is set.
   */
  public function __set($name,$value) {
      print("SETTING ".$name);
      switch($name) {
          case 'segment_raw':
              $this->segment_raw = $value;
              $this->geojson_raw = json_decode($value);
              break;
          case 'height':
              $this->height = $this->_handleHeight($value);
              break;
      }
  }

  /**
   * Decodes the "string" of JSON returned by postgres
   * to an actual object so it can be printed correctly.
   * NOTE: This should be handled automatically by the ORM
   *       which is something we'll continue to work on, but
   *       in the meantime this gets the JSON out to the API
   *       so the client team can continue to move forward.
   */
  public function decodeSegmentsJson() {
    $this->geojson_raw = json_decode($this->segment_raw);
  }



  /**
   * Decode the "array" string returned by postgres into an actual array
   *
   * PHP docs say to do this. [ sigh ] Apparently PHP can't interpret the
   * column AS an array, which it really ought to be doing.
   */
  public function linkedListPathAsArray() {
    $r = str_getcsv(str_replace('\\\\', '\\', trim($this->linked_list_path, "{}")), ",", "");
    return array_map('intval', $r);
  }


/************************************************************
 * STATIC CLASS FUNCTIONS
 ************************************************************/

  /**
   * Retrieve all results from the orderedStations view.
   *
   * Returns everything, formatted in the OrderedStations entity way.
   */
  public static function fetchAll() {
    $ss = DB::instance()->orderedStations()->fetchAll();
    foreach($ss as $elem) {
      $elem->decodeSegmentsJson();
    }
    return $ss;
  }



  /**
   * Return a a single station with the given ID
   *
   */
  public static function fetch($id) {
    $s = DB::instance()->orderedStations(array('stationid='=>$id))->fetch();
    $s->decodeSegmentsJson();
    return $s;
  }


  /**
   * Return all the stations for the highway with the given ID
   *
   * NOTE: This shouldn't be done. There should be a ".stations" on the Highway entity.
   * But for now...
   */
  public static function fetchForHighway($hid) {
    // TODO: This should use stations()->highways[$id] instead of hardcoding 'highwayid'.
    //         Unfortunately that seems to throw an error in Mapper.
    $ss = DB::instance()->orderedStations(array('highwayid='=>$hid))->fetchAll();
    foreach($ss as $elem) {
      $elem->decodeSegmentsJson();
    }
    return $ss;
  }


  /**
   * Accepts an station ID and returns the related onramps.
   *
   * Currently will only return a single onramp, but the possibility is there...
   */
  public static function fetchRelatedOnramps($id) {
    $onRamp = DB::instance()->stations(array('stationid='=>$id))->fetch();
  }


  /**
   * Returns the ID of the onramp detector related to this station.
   *
   * Related onramps are detected by having the "same" ID in the 5000 range.
   * i.e. Station 1037 should have an onramp 5037, if such an onramp exists.
   * Note that onramps aren't useful for speed, because they're just a single loop.
   * @return int -1 if not possible, otherwise the ID that the onramp *should* be.
   */
  public static function calculateRelatedOnrampID($tid) {
    if($tid >= 1000 && $tid < 4000) {
      // First we strip it down to the base ID. (not in the thousands range.)
      while($tid > 1000) {
        $tid = $tid-1000;
      }

      return $tid+5000;
    } else {
      return null;
    }
  }

}