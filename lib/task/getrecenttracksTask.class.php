<?php

class getrecenttracksTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      // add your own options here
      new sfCommandOption('from_date', null, sfCommandOption::PARAMETER_OPTIONAL, 'Date you want to retreive music for (in any valid format)', null),
      new sfCommandOption('time_offset', null, sfCommandOption::PARAMETER_OPTIONAL, 'How far back from the End Date (in seconds) to grab data', (24 * 60 * 60) - 1),
      new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number of records to retreive', 200),
      new sfCommandOption('send_email', null, sfCommandOption::PARAMETER_OPTIONAL, 'Send emails of tracks that could not be matched', false),
    ));

    $this->namespace        = 'lastfm';
    $this->name             = 'get-recent-tracks';
    $this->briefDescription = 'retreive recently listened tracks';
    $this->detailedDescription = <<<EOF
The [recent-tracks|INFO] task retreive recently listened tracks.
Call it with:

  [php symfony test|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

  $from = date('U', strtotime(
    (($options['from_date'] == null) 
      ? date('Y-m-d').' 00:00:00' 
      : $options['from_date'])
    ));
  $to = $from + $options['time_offset'];

    $lastfm = new wpLastFm();
    $result = $lastfm->get('user.getRecentTracks'
      , array(
        'limit' => $options['limit']
        , 'from' => $from
        , 'to' => $to
      )
    );

    $noMatchEmail = '';

    foreach ($result->recenttracks->track as $track) {
      $songs = CoreTracksTable::fetchByArtistAlbumTitle(
        $track->artist
        , $track->album
        , $track->name
      );

      $play = PlayTable::getOrCreate($track->date);
      $play->artist = $track->artist;
      $play->name = $track->name;
      $play->album = $track->album;
      $play->gmt_date = $track->date;
      
      if ($songs->Count() == 1)
      {
        $song = $songs->getFirst();
        $prevCount = $song->PlayCount;
        $prevDate = $song->LastPlayedStamp;

        if ($play->track_id) 
        {
          echo 'PREVIOUSLY UPDATED for '.$song->Title.' by '.$song->Album->ArtistName."\n";
          echo "\n";
        }
        elseif ($song->updateFromLastFmPlay($track, sfConfig::get('app_allowable_offset_seconds', 300)))
        {
          $play->track_id = $song->TrackID;
          $play->status = 'Updated';
          echo 'UPDATING '.$song->Title.' by '.$song->Album->ArtistName."\n";
          echo '  playcount set to '.$song->PlayCount.' from '.$prevCount."\n";
          echo '  lastPlayStamp set to '.date('Y-m-d H:i:s T', $song->LastPlayedStamp).' from '.date('Y-m-d H:i:s', $prevDate)."\n";
          echo "\n";
        }
        else
        {
          $play->status = 'Banshee';
          echo 'NO UPDATE MADE for '.$song->Title.' by '.$song->Album->ArtistName."\n";
          echo "\n";
        }
      }
      elseif ($songs->count() == 0)
      {
        $play->status = 'No Match';

        $noMatchMsg = '"'.$track->name.'" by "'.$track->artist.'" on "'.$track->album."\"\n";
        print 'NO MATCH FOUND FOR '.$noMatchMsg;
        $noMatchEmail = $noMatchEmail.$noMatchMsg."\n";
        
        $possibleSongs = CoreTracksTable::fetchByArtistAlbum($track->artist, $track->album);
        foreach ($possibleSongs as $possibility)
        {
          print '  '.$possibility->Title."\n";
        }

        print "\n";
      }
      $play->save();
    }

    if ($options['send_email'] && $noMatchEmail)
    {
      include_once sfConfig::get('sf_root_dir').'/lib/vendor/symfony/lib/vendor/swiftmailer/swift_required.php';
      $transport = Swift_MailTransport::newInstance();
      $mailer = Swift_Mailer::newInstance($transport);
      $message = Swift_Message::newInstance();

      $message->setFrom(sfConfig::get('app_email_from'));
      $message->setTo(sfConfig::get('app_email_to'));
      $message->setSubject('Mismatched songs for '.Date('Y-m-d'));
      $message->setBody($noMatchEmail);
      $mailer->send($message);
    }
  }
}
