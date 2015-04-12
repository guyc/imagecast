<?php

require_once('Util.php');
require_once('GooglePhotoService.php');
{
    $tempDir = '/tmp';
    $username = 'youremailaddresshere';
    $password = 'yourpasswordhere';
    $sourceAlbumName = 'nameofsourcefolder';
    $destAlbumName = 'nameofdestinationfolder';

    $photoService = new GooglePhotoService($username, $password);

    $sourceAlbum = $photoService->Album($sourceAlbumName);
    Dump(array($sourceAlbum->Title(),$sourceAlbum->Id()));
    
    $destAlbum = $photoService->Album($destAlbumName);
    Dump($destAlbum->Title());

    $existingPhotos = array();
    foreach ($destAlbum->Photos() as $destPhoto) {
        foreach ($destPhoto->Tags() as $tag) {
            Dump($tag);
            $existingPhotos[] = $tag;
        }
    }

    foreach ($sourceAlbum->Photos() as $sourcePhoto) {
        $sourceId = $sourcePhoto->Id();
        Dump($sourceId);
        if (!in_array($sourceId, $existingPhotos)) {
	  Dump(array($sourcePhoto->Title(),$sourcePhoto->Summary(),$sourcePhoto->Content()));
            $sourceFileName =  tempnam($tempDir, $sourcePhoto->Title());
            file_put_contents($sourceFileName, fopen($sourcePhoto->Content(), 'r'));
            $destFileName =  tempnam($tempDir, $sourcePhoto->Title());
            Dump(array($sourceFileName,$destFileName));
            shell_exec("./process.sh ".
		       escapeshellarg($sourceFileName).
		       " ".
		       escapeshellarg($destFileName));
            $destAlbum->UploadPhoto(array(
                                          'filename'=>$destFileName,
                                          'title'=>$sourcePhoto->Title(),
                                          'summary'=>$sourcePhoto->Summary(),
                                          'contentType'=>$sourcePhoto->ContentType(),
                                          'tags'=>array($sourcePhoto->Id())
                                          )
                                    );
	    unlink($sourceFileName);
	    unlink($destFileName);
        }
    }
}