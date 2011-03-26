<?php

class buildplaylistbyratioTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      // add your own options here
    ));

    $this->namespace        = 'banshee';
    $this->name             = 'build-playlist-by-ratio';
    $this->briefDescription = 'Generate a playlist based on the rating:play ratios';
    $this->detailedDescription = <<<EOF
The [recent-tracks|INFO] Generate a playlist based on the rating:play ratios
Call it with:

  [php symfony test:regen-playlist|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    $list = CorePlaylistsTable::findByNameOrCreate(sfConfig::get('app_playlist_name'));
    $list->clearAllEntries();

    foreach($list->Entries as $entry)
    {
      print $entry->TrackID."\n";
    }

    $timeOffset = 14 * 24 * 60 * 60;
  

    $conn = $databaseManager->getDatabase('doctrine')->getDoctrineConnection();
    $conn->beginTransaction();
    for ($i = 1; $i <= 5; $i++) {
      $tracks = CoreTracksTable::fetchLowestPlayedByRatioAndRating(
        $i
        , $timeOffset
        , sfConfig::get('app_ratio_playlist_count_'.$i, 100)
      );
      foreach ($tracks as $track) {
        $entry = new CorePlaylistEntries();
        $entry->TrackID = $track['TrackID'];
        $entry->PlaylistID = $list->PlaylistID;
        $entry->Generated = 1;
        $entry->save();
        print sprintf('Adding %s by %s with a rating of %s'."\n",
          $track['Title']
          , $track['Artist']['Name']
          , $track['Rating']
        );
      }
    }
    $conn->commit();
  }
}
