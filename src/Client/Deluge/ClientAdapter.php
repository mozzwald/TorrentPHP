<?php

namespace TorrentPHP\Client\Deluge;

use TorrentPHP\ClientAdapter as BaseClientAdapter,
    TorrentPHP\Torrent;

/**
 * Class ClientAdapter
 *
 * @package TorrentPHP\Client\Deluge
 */
class ClientAdapter extends BaseClientAdapter
{
    /**
     * @see ClientTransport::getTorrents()
     */
    public function getTorrents(array $ids = array())
    {
        $data = json_decode($this->transport->getTorrents($ids), true);

        $torrents = array();

        if (!empty($ids))
        {
            $changed = array('result' => array(), 'error' => $data['error']);
            $i = 0;
            foreach ($ids as $thisID)
            {
                 $changed['result'][$i] = $data['result'][$thisID];
                 $i++;
            }
            $data = $changed;
        }

        foreach ($data['result'] as $array)
        {
            $torrent = $this->torrentFactory->build($array['hash'], $array['name'], $array['total_wanted']);

            $torrent->setDownloadSpeed($array['download_payload_rate']);
            $torrent->setUploadSpeed($array['upload_payload_rate']);

            /** Deluge doesn't have a per-torrent error string **/
            $torrent->setErrorString((is_null($data['error']) ? "" : print_r($data['error'], true)));

            $torrent->setStatus($array['state']);

            foreach ($array['files'] as $fileData)
            {
                $file = $this->fileFactory->build($fileData['path'], $fileData['size']);

                $torrent->addFile($file);
            }

            $torrent->setBytesDownloaded($array['total_done']);
            $torrent->setBytesUploaded($array['total_uploaded']);

            $torrents[] = $torrent;
        }

        return $torrents;
    }

    /**
     * @see ClientTransport::addTorrent()
     */
    public function addTorrent($path)
    {
        $data = json_decode($this->transport->addTorrent($path));

        $torrentHash = array($data->result);

        $torrents = $this->getTorrents($torrentHash);

        return $torrents[0];
    }

    /**
     * @see ClientTransport::startTorrent()
     */
    public function startTorrent(Torrent $torrent = null, $torrentId = null)
    {
        $this->transport->startTorrent($torrent, $torrentId);

        $torrentHash = (!is_null($torrent)) ? $torrent->getHashString() : $torrentId;

        $torrents = $this->getTorrents(array($torrentHash));

        return $torrents[0];
    }

    /**
     * @see ClientTransport::pauseTorrent()
     */
    public function pauseTorrent(Torrent $torrent = null, $torrentId = null)
    {
        $this->transport->pauseTorrent($torrent, $torrentId);

        $torrentHash = (!is_null($torrent)) ? $torrent->getHashString() : $torrentId;

        $torrents = $this->getTorrents(array($torrentHash));

        return $torrents[0];
    }

    /**
     * @see ClientTransport::deleteTorrent()
     */
    public function deleteTorrent(Torrent $torrent = null, $torrentId = null)
    {
        $data = json_decode($this->transport->deleteTorrent($torrent, $torrentId));

        return $data->result;
    }
}