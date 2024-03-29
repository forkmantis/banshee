<?php

/**
 * CoreTracks
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    banshee
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7691 2011-02-04 15:43:29Z jwage $
 */
class CoreTracks extends BaseCoreTracks
{
  /**
   * Get the last played timestamp, converted to GMT
   **/
  public function getGmLastPlayedStamp()
  {
    return $this->getLastPlayedStamp() + (-1 * ($this->getTimeZoneOffset()));
  }

  public function getTimeZoneOffset()
  {
    return date('Z');
  }

  public function getDaylightSavingsOffset($timestamp)
  {
    return date('I', $timestamp) * 60 * 60;
  }

  /**
   * Takes a Recent Track record (as SimpleXMLObject) and determines if the
   * playcount and last play date needs to be incremented.
   *
   * @return bool
   **/
  public function updateFromLastFmPlay($xml, $allowableOffset)
  {
    $lastFmDate = strtotime($xml->date);    
    if ($lastFmDate === false) $lastFmDate = date('U');

    if ($this->isAMatchOnTime($lastFmDate, $allowableOffset))
    {
      return false;
    }

    $this->PlayCount++;
    $this->setLastPlayedStamp($lastFmDate
      + $this->getTimeZoneOffset()
    );

    $this->save();

    return true;
  }

  /**
   * Checks if the date reported by LastFM matches the songs date within an
   *  acceptable range
   *
   * @return bool
   **/
  private function isAMatchOnTime($lastFmDate, $allowableOffset)
  {
    $isMatch = ($lastFmDate >= $this->getGmLastPlayedStamp() - $allowableOffset)
      && ($lastFmDate <= $this->getGmLastPlayedStamp() + $allowableOffset);
    return $isMatch;
  }
}
