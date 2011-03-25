<?php

/**
 * CorePlaylists
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    banshee
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7691 2011-02-04 15:43:29Z jwage $
 */
class CorePlaylists extends BaseCorePlaylists
{

  /**
   * Clear all entries for this list
   *
   * @return integer
   **/
  public function clearAllEntries()
  {
    return CorePlaylistEntriesTable::getInstance()->createQuery('pe')->
      where('pe.PlaylistID = ?', array($this->PlaylistID))->
      delete()->
      execute();
  }
}
