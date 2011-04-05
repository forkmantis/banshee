<?php

/**
 * CoreTracksTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class CoreTracksTable extends Doctrine_Table
{

  /**
   * Returns an instance of this class.
   *
   * @return object CoreTracksTable
   */
  public static function getInstance()
  {
    return Doctrine_Core::getTable('CoreTracks');
  }

  /**
   * Fetch the most recently added tracks
   *
   * @return DoctrineResultSet
   **/
  public static function fetchRecent($limit = 10)
  {
    return self::getInstance()->createQuery('ct')->
      orderBy('ct.DateAddedStamp desc')->
      limit($limit)->
      execute();
  }

  /**
   * Fetch (hopefuly) one record by artist, album and song title
   *
   * @return DoctrineResultSet
   **/
  public static function fetchByArtistAlbumTitle($artist, $album, $title)
  {
    return self::getInstance()->createQuery('ct')->
      innerJoin('ct.Album a')->
      innerJoin('ct.Artist art')->
      where('ct.Title = ? COLLATE NOCASE AND art.Name = ? COLLATE NOCASE AND a.Title = ? COLLATE NOCASE',
        array($title, $artist, $album))->
      execute();
  }

  /**
   * undocumented function
   *
   * @return void
   * @author Me
   **/
  public static function fetchByArtistAlbum($artist, $album)
  {
    return self::getInstance()->createQuery('ct')->
      innerJoin('ct.Album a')->
      innerJoin('ct.Artist art')->
      where('art.Name = ? COLLATE NOCASE AND a.Title = ? COLLATE NOCASE',
        array($artist, $album))->
      execute();
  }

  /**
   * Fetch those w/ the lowest play to rating ratio, sorted by how long ago 
   *  it was played
   *
   * @return Array
   **/
  public static function fetchLowestPlayedByRatio($offsetSeconds, $limit = 500)
  {
    return Doctrine_Query::create()->
      select('a.Name, ct.Title, ct.TrackID, (1000 * 
        (ct.PlayCount + ct.SkipCount) / ct.Rating) as Ratio')->
      from('CoreTracks ct')->
      innerJoin('ct.Artist a')->
      where('(ct.UriType IS NULL OR ct.UriType = 1) AND ct.LastPlayedStamp < '.(gmdate('U') - $offsetSeconds))->
      orderBy('(1000 * (ct.PlayCount + ct.SkipCount) / ct.Rating), 
        ct.LastPlayedStamp')->
      limit($limit)->
      execute()->toArray();
  }

  /**
   * Fetch those w/ the lowest play to rating ratio by rating, sorted by how long ago 
   *  it was played
   *
   * @return Array
   **/
  public static function fetchLowestPlayedByRatioAndRating($rating, $offsetSeconds, $limit = 100)
  {
    return Doctrine_Query::create()->
      select('a.Name, ct.Title, ct.TrackID, ct.Rating')->
      from('CoreTracks ct')->
      innerJoin('ct.Artist a')->
      where('(ct.UriType IS NULL OR ct.UriType = 1) AND ct.Rating = ? AND ct.LastPlayedStamp < '.(gmdate('U') - $offsetSeconds)
        , array($rating))->
      orderBy('(ct.PlayCount + (ct.SkipCount * 2)), 
        ct.LastPlayedStamp')->
      limit($limit)->
      execute()->toArray();
  }
}
