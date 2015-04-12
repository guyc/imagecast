<?php

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_Photos_UserQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_AlbumQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_PhotoQuery');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_App_Extension_Category');

class GoogleFeedEntry  
{
    function GoogleFeedEntry($Service, $Entry)
    {
        $this->service = $Service;
        $this->entry = $Entry;
    }

    function Id()
    {
        return $this->entry->getGphotoId()->getText();
    }
}

class GoogleFeedIterator implements Iterator {

    function GoogleFeedIterator($Service, $Feed)
    {
        $this->service = $Service;
        $this->feed = $Feed;
    }

    public function Feed()
    {
        return null; // MUST BE OVERRIDDEN
    }

    public function current()
    {
        return null;  // MUST BE OVERRIDDEN
    }

    public function key ()
    {
        return $this->feed->key();
    }

    public function next ()
    {
        $this->feed->next();
    }

    public function rewind ()
    {
        $this->feed->rewind();
    }

    public function valid ()
    {
        return $this->feed->valid();
    }
}

class GooglePhoto extends GoogleFeedEntry
{
    function Title()
    {
        return (string)$this->entry->getTitle();
    }

    function Summary()
    {
        return (string)$this->entry->getSummary();
    }

    function Content()
    {
        foreach ($this->entry->getMediaGroup()->getContent() as $content) {
            return (string)$content->url;
        }
    }

    function ContentType()
    {
        foreach ($this->entry->getMediaGroup()->getContent() as $content) {
            Dump($content);
            return (string)$content->type;
        }
    }

    function AlbumId()
    {
        return $this->entry->getGphotoAlbumId()->getText();
    }

    function Tags()
    {
        $query = $this->service->newPhotoQuery();
        $query->setAlbumId($this->AlbumId());
        $query->setPhotoId($this->Id());
        $query->setKind("tag");
        $feed = $this->service->getUserFeed(null, $query);
        $tags = array();
        foreach ($feed as $tagEntry) {
            $tags[] = $tagEntry->title->text;
        }
        return $tags;
    }
}

class GooglePhotos extends GoogleFeedIterator {
    public function current()
    {
        return new GooglePhoto($this->service, $this->feed->current());
    }
}

class GooglePhotoAlbum extends GoogleFeedEntry {

    function Title()
    {
        return (string)$this->entry->getTitle();
    }

    function Summary()
    {
        return (string)$this->entry->getSummary();
    }

    function UploadPhoto($Args)
    {
        Dump($Args);
        $filename = $Args['filename'];
        $title = $Args['title'];
        $summary = $Args['summary'];
        $contentType = $Args['contentType'];
        $tags = $Args['tags'];
        
        $fd = $this->service->newMediaFileSource($filename);
        $fd->setContentType($contentType);
        $photoEntry = $this->service->newPhotoEntry();
        $photoEntry->setMediaSource($fd);
        $photoEntry->setTitle($this->service->newTitle($title));
        $photoEntry->setSummary($this->service->newSummary($summary));

        $insertedEntry = $this->service->insertPhotoEntry($photoEntry, $this->entry);

        foreach ($tags as $tag) {
            $tagEntry = $this->service->newTagEntry();
            $tagEntry->setTitle($this->service->newTitle($tag));
            $createdTag = $this->service->insertTagEntry($tagEntry, $insertedEntry);
        }

        return new GooglePhoto($this->service, $insertedEntry);
    }

    function Photos()
    {
        $query = $this->service->newAlbumQuery();
        $query->setAlbumId($this->Id());
        $feed = $this->service->getAlbumFeed($query);
        return new GooglePhotos($this->service, $feed);
    }
}


class GooglePhotoAlbums extends GoogleFeedIterator {
    public function current()
    {
        return new GooglePhotoAlbum($this->service, $this->feed->current());
    }
}

class GooglePhotoService {

    function GooglePhotoService($Username, $Password)
    {
        $this->client = self::Client($Username, $Password);
        $this->service = new Zend_Gdata_Photos($this->client);
    }

    static function Client($Username, $Password)
    {
        $serviceName = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
        try {
            $client = Zend_Gdata_ClientLogin::getHttpClient($Username, $Password, $serviceName);
        } catch (Zend_Gdata_App_CaptchaRequiredException $cre) {
            echo 'URL of CAPTCHA image: ' . $cre->getCaptchaUrl() . "\n";
            echo 'Token ID: ' . $cre->getCaptchaToken() . "\n";
        } catch (Zend_Gdata_App_AuthException $ae) {
            echo 'Problem authenticating: ' . $ae->exception() . "\n";
        }
        return $client;
    }

    function Albums()
    {
        $user = null;
        $userFeed = $this->service->getUserFeed($user);
        return new GooglePhotoAlbums($this->service, $userFeed);
    }

    function Album($AlbumName)
    {
        $query = $this->service->newAlbumQuery(); //new Zend_Gdata_Photos_AlbumQuery();
        $user = null; //'default';
        $query->setUser($user);
        $query->setAlbumName($AlbumName);
        //$query->setImgMax("d");
        //Dump($query);

        try {
            $albumEntry = $this->service->getAlbumEntry($query);
        } catch (Zend_Gdata_App_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        return new GooglePhotoAlbum($this->service, $albumEntry);
    }
}

?>