<?php

namespace TheFox\Dht\Kademlia;

use Exception;
use RuntimeException;
use TheFox\Storage\YamlStorage;

/**
 * @codeCoverageIgnore
 */
class Table extends YamlStorage
{
    #private $buckets = array();
    #private $bucketsByMask = array();
    private $localNode = null;

    #private $childBucketUpper = null;
    #private $childBucketLower = null;
    private $rootBucket = null;

    public function __construct($filePath = null)
    {
        parent::__construct($filePath);

        #$this->data['bucketsId'] = 0;
        $this->data['timeCreated'] = time();
    }

    public function __sleep()
    {
        return [
            'data', 'dataChanged',
            #'buckets',
            'localNode',
            'rootBucket',
        ];
    }

    public function save()
    {
        #print __CLASS__.'->'.__FUNCTION__.''."\n";

        /*$this->data['buckets'] = array();
        foreach($this->buckets as $bucketId => $bucket){
            $this->data['buckets'][$bucketId] = array(
                'path' => $bucket->getFilePath(),
            );
            
            $bucket->save();
        }*/

        /*$this->data['childBucketUpper'] = null;
        if($this->childBucketUpper){
            $this->data['childBucketUpper'] = $this->childBucketUpper->getFilePath();
            $this->childBucketUpper->save();
        }
        
        $this->data['childBucketLower'] = null;
        if($this->childBucketLower){
            $this->data['childBucketLower'] = $this->childBucketLower->getFilePath();
            $this->childBucketLower->save();
        }*/

        $this->data['rootBucket'] = null;
        if ($this->rootBucket) {
            $this->data['rootBucket'] = $this->rootBucket->getFilePath();
            $this->rootBucket->save();
        }

        $rv = parent::save();
        #unset($this->data['buckets']);
        #unset($this->data['childBucketUpper']);
        #unset($this->data['childBucketLower']);
        unset($this->data['rootBucket']);

        return $rv;
    }

    public function load()
    {
        #print __CLASS__.'->'.__FUNCTION__.''."\n";

        if (parent::load()) {

            if ($this->data) {
                /*if(array_key_exists('buckets', $this->data) && $this->data['buckets']){
                    foreach($this->data['buckets'] as $bucketId => $bucketAr){
                        if(file_exists($bucketAr['path'])){
                            $bucket = new Bucket($bucketAr['path']);
                            $bucket->setDatadirBasePath($this->getDatadirBasePath());
                            $bucket->setLocalNode($this->getLocalNode());
                            
                            if($bucket->load()){
                                $this->buckets[$bucketId] = $bucket;
                                $this->bucketsByMask[$bucket->getMask()] = $bucket;
                            }
                        }
                    }
                }*/
                /*if(array_key_exists('childBucketUpper', $this->data) && $this->data['childBucketUpper']){
                    $this->childBucketUpper = new Bucket($this->data['childBucketUpper']);
                    $this->childBucketUpper->setDatadirBasePath($this->getDatadirBasePath());
                    $this->childBucketUpper->setLocalNode($this->getLocalNode());
                    $this->childBucketUpper->load();
                }
                if(array_key_exists('childBucketLower', $this->data) && $this->data['childBucketLower']){
                    $this->childBucketLower = new Bucket($this->data['childBucketLower']);
                    $this->childBucketLower->setDatadirBasePath($this->getDatadirBasePath());
                    $this->childBucketLower->setLocalNode($this->getLocalNode());
                    $this->childBucketLower->load();
                }*/
                if (array_key_exists('rootBucket', $this->data) && $this->data['rootBucket']) {
                    $this->rootBucket = new Bucket($this->data['rootBucket']);
                    $this->rootBucket->setDatadirBasePath($this->getDatadirBasePath());
                    $this->rootBucket->setLocalNode($this->getLocalNode());
                    $this->rootBucket->load();
                }
            }

            #unset($this->data['buckets']);
            #unset($this->data['childBucketUpper']);
            #unset($this->data['childBucketLower']);
            unset($this->data['rootBucket']);

            #$this->data['bucketsId'] = (int)$this->data['bucketsId'];

            return true;
        }

        return false;
    }

    /*private function bucketNew($mask){
        #print __CLASS__.'->'.__FUNCTION__.''."\n";
        #print __CLASS__.'->'.__FUNCTION__.': '.$this->getDatadirBasePath()."\n";
        
        $this->data['bucketsId']++;
        
        $filePath = null;
        if($this->getDatadirBasePath()){
            $filePath = $this->getDatadirBasePath().'/bucket_'.$this->data['bucketsId'].'.yml';
        }
        
        $bucket = new Bucket($filePath);
        $bucket->setId($this->data['bucketsId']);
        $bucket->setMask($mask);
        $bucket->setDatadirBasePath($this->getDatadirBasePath());
        $bucket->setLocalNode($this->getLocalNode());
        
        $this->buckets[$this->data['bucketsId']] = $bucket;
        $this->bucketsByMask[$bucket->getMask()] = $bucket;
        
        $this->setDataChanged(true);
        
        return $bucket;
    }*/

    public function setLocalNode(Node $node)
    {
        $this->localNode = $node;
    }

    public function getLocalNode()
    {
        return $this->localNode;
    }

    public function getNodes($levelMax = 0)
    {
        if ($this->rootBucket) {
            return $this->rootBucket->getNodes($levelMax);
        }
        return [];
    }

    public function getNodesNum()
    {
        return count($this->getNodes());
    }

    public function getNodesClosest($num = 20)
    {
        if (!$this->getLocalNode()) {
            throw new RuntimeException('localNode not set.');
        }

        if ($this->rootBucket) {
            $nodes = $this->rootBucket->getNodes();
            $nodes = array_slice($nodes, 0, $num);
            ksort($nodes, SORT_STRING);

            $rv = array_values($nodes);
            return $rv;
        }

        return [];
    }

    public function nodeFindInBuckets(Node $node)
    {
        if ($this->rootBucket) {
            return $this->rootBucket->nodeFind($node);
        }

        return null;
    }

    public function nodeFindInBucketsByUri($uri)
    {
        if ($this->rootBucket) {
            return $this->rootBucket->nodeFindByUri($uri);
        }

        return null;
    }

    public function nodeFindClosest(Node $node, $num = 8)
    {
        if ($this->rootBucket) {
            $nodes = [];
            foreach ($this->rootBucket->getNodes() as $onodeId => $onode) {
                if (!$onode->isEqual($node)) {
                    $nodes[$onode->distanceHexStr($node)] = $onode;
                    ksort($nodes, SORT_STRING);
                    $nodes = array_slice($nodes, 0, $num);
                }
            }
            $rv = array_values($nodes);
            return $rv;
        }

        return [];
    }

    public function nodeFindByKeyPubFingerprint($fingerprint)
    {
        foreach ($this->rootBucket->getNodes() as $onodeId => $onode) {
            if ($fingerprint == $onode->getSslKeyPubFingerprint()) {
                return $onode;
            }
        }

        return null;
    }

    public function nodeEnclose(Node $node)
    {
        if (!$this->getLocalNode()) {
            throw new RuntimeException('localNode not set.');
        }

        if (!$this->rootBucket) {
            $filePath = null;
            if ($this->getDatadirBasePath()) {
                #$filePath = $this->getDatadirBasePath().'/bucket_root_'.time().'_'.mt_rand(1000, 9999).'.yml';
                $filePath = $this->getDatadirBasePath() . '/bucket_root.yml';
                if (file_exists($filePath)) {
                    // This should never happen.
                    throw new RuntimeException('path for bucket alread exists: ' . $filePath);
                }
            }

            $bucket = new Bucket($filePath);
            $bucket->setDatadirBasePath($this->getDatadirBasePath());
            $bucket->setLocalNode($this->getLocalNode());
            $this->rootBucket = $bucket;

            $this->setDataChanged(true);
        }

        #$returnNode = $this->rootBucket->nodeEnclose($node);
        $returnNode = $this->rootBucket->nodeEnclose($node, false);

        return $returnNode;
    }
}
