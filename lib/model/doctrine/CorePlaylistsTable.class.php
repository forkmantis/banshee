<?php

/**
 * CorePlaylistsTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class CorePlaylistsTable extends Doctrine_Table
{
    /**
     * Returns an instance of this class.
     *
     * @return object CorePlaylistsTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('CorePlaylists');
    }

    /**
     * Find a playlist by name, or create one with that name and return it.
     *
     * @return CorePlaylists
     **/
    public static function findByNameOrCreate($name)
    {
      $list = self::getInstance()->createQuery('p')->
        where('p.Name = ?', array($name))->
        fetchOne();

      if (!($list instanceof CorePlaylists))
      {
        $list = new CorePlaylists();
        $list->Name = $name;
        $list->save();
      }

      return $list;
    }
}
