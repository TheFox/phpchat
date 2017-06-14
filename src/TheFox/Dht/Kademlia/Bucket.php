<?php

namespace TheFox\Dht\Kademlia;

use RuntimeException;
use TheFox\Storage\YamlStorage;

/**
 * @codeCoverageIgnore
 */
class Bucket extends YamlStorage
{
    public static $SIZE_MAX = 20;

    #private $nodesId = 0;
    private $nodes = [];

    private $localNode = null;

    private $childBucketUpper = null;

    private $childBucketLower = null;

    public function __construct($filePath = null)
    {
        parent::__construct($filePath);

        #$this->data['id'] = 0;
        #$this->data['prefix'] = '';
        #$this->data['prefixName'] = '';
        $this->data['distance'] = 0;
        $this->data['distanceName'] = '';
        $this->data['maskByte'] = 0;
        $this->data['maskByteName'] = 0;
        $this->data['maskBit'] = 0;
        $this->data['maskBitName'] = 0;
        $this->data['isFull'] = false;
        $this->data['isUpper'] = false;
        $this->data['isLower'] = false;
        $this->data['sizeMax'] = static::$SIZE_MAX;
        $this->data['timeCreated'] = time();
    }

    public function __sleep()
    {
        return ['nodes'];
    }

    public function save()
    {
        #$this->data['prefixName'] = intToBin($this->getPrefix());
        $this->data['distanceName'] = intToBin($this->getDistance());
        $this->data['maskBitName'] = intToBin($this->getMaskByte());
        $this->data['maskBitName'] = intToBin($this->getMaskBit());

        $this->data['nodes'] = [];
        foreach ($this->nodes as $nodeId => $node) {
            $this->data['nodes'][$nodeId] = [
                'path' => $node->getFilePath(),
            ];
            $node->save();
        }

        $this->data['childBucketUpper'] = null;
        if ($this->childBucketUpper) {
            $this->data['childBucketUpper'] = $this->childBucketUpper->getFilePath();
            $this->childBucketUpper->save();
        }

        $this->data['childBucketLower'] = null;
        if ($this->childBucketLower) {
            $this->data['childBucketLower'] = $this->childBucketLower->getFilePath();
            $this->childBucketLower->save();
        }

        $rv = parent::save();
        unset($this->data['nodes']);
        unset($this->data['childBucketUpper']);
        unset($this->data['childBucketLower']);

        return $rv;
    }

    public function load()
    {
        if (parent::load()) {

            if ($this->data) {
                if (array_key_exists('nodes', $this->data) && $this->data['nodes']) {
                    foreach ($this->data['nodes'] as $nodeId => $nodeAr) {
                        if (file_exists($nodeAr['path'])) {
                            #$this->nodesId = $nodeId;

                            $node = new Node($nodeAr['path']);
                            $node->setDatadirBasePath($this->getDatadirBasePath());
                            $node->setBucket($this);
                            if ($node->load()) {
                                #$this->nodes[$this->nodesId] = $node;
                                $this->nodes[$nodeId] = $node;
                            }
                        }
                    }
                }
                if (array_key_exists('childBucketUpper', $this->data) && $this->data['childBucketUpper']) {
                    $this->childBucketUpper = new Bucket($this->data['childBucketUpper']);
                    $this->childBucketUpper->setDatadirBasePath($this->getDatadirBasePath());
                    $this->childBucketUpper->setLocalNode($this->getLocalNode());
                    $this->childBucketUpper->load();
                }
                if (array_key_exists('childBucketLower', $this->data) && $this->data['childBucketLower']) {
                    $this->childBucketLower = new Bucket($this->data['childBucketLower']);
                    $this->childBucketLower->setDatadirBasePath($this->getDatadirBasePath());
                    $this->childBucketLower->setLocalNode($this->getLocalNode());
                    $this->childBucketLower->load();
                }
            }

            unset($this->data['nodes']);
            unset($this->data['childBucketUpper']);
            unset($this->data['childBucketLower']);

            return true;
        }

        return false;
    }

    /*public function setId($id){
        $this->data['id'] = (int)$id;
    }
    
    public function getId(){
        return (int)$this->data['id'];
    }*/

    public function setDistance($distance)
    {
        $this->data['distance'] = $distance;
    }

    public function getDistance()
    {
        return $this->data['distance'];
    }

    public function setMaskByte($maskByte)
    {
        $this->data['maskByte'] = $maskByte;
    }

    public function getMaskByte()
    {
        return $this->data['maskByte'];
    }

    public function setMaskBit($maskBit)
    {
        $this->data['maskBit'] = $maskBit;
    }

    public function getMaskBit()
    {
        return $this->data['maskBit'];
    }

    public function setIsFull($isFull)
    {
        $this->data['isFull'] = $isFull;
    }

    public function getIsFull()
    {
        return $this->data['isFull'];
    }

    public function isFull()
    {
        if ($this->getNodesNum() >= static::$SIZE_MAX) {
            // If full, stay full.
            $this->setIsFull(true);
        }

        return $this->getIsFull();
    }

    public function setIsUpper($isUpper)
    {
        $this->data['isUpper'] = $isUpper;
    }

    public function getIsUpper()
    {
        return $this->data['isUpper'];
    }

    public function setIsLower($isLower)
    {
        $this->data['isLower'] = $isLower;
    }

    public function getIsLower()
    {
        return $this->data['isLower'];
    }

    public function getNodes($levelMax = 0, $level = 0)
    {
        #return $this->nodes;
        $nodes = $this->nodes;
        if ($levelMax <= 0 || $level <= $levelMax) {
            if ($this->childBucketUpper) {
                $nodes = array_merge($nodes, $this->childBucketUpper->getNodes($levelMax, $level + 1));
            }
            if ($this->childBucketLower) {
                $nodes = array_merge($nodes, $this->childBucketLower->getNodes($levelMax, $level + 1));
            }
        }
        return $nodes;
    }

    public function getNodesNum()
    {
        return count($this->getNodes());
    }

    public function setLocalNode(Node $localNode)
    {
        $this->localNode = $localNode;
    }

    public function getLocalNode()
    {
        return $this->localNode;
    }

    public function nodeFind(Node $node)
    {
        return $this->nodeFindByIdHexStr($node->getIdHexStr());
    }

    public function nodeFindByIdHexStr($id)
    {
        $id = strtolower($id);
        /*foreach($this->nodes as $nodeId => $node){
            if($node->getIdHexStr() == $id){
                return $node;
            }
        }*/
        if (isset($this->nodes[$id])) {
            return $this->nodes[$id];
        }
        if ($this->childBucketUpper) {
            return $this->childBucketUpper->nodeFindByIdHexStr($id);
        }
        if ($this->childBucketLower) {
            return $this->childBucketLower->nodeFindByIdHexStr($id);
        }
        return null;
    }

    public function nodeFindByUri($uri)
    {
        foreach ($this->nodes as $nodeId => $node) {
            if ((string)$node->getUri() == $uri) {
                return $node;
            }
        }
        if ($this->childBucketUpper) {
            return $this->childBucketUpper->nodeFindByUri($uri);
        }
        if ($this->childBucketLower) {
            return $this->childBucketLower->nodeFindByUri($uri);
        }
        return null;
    }

    private function nodeAdd(Node $node, $sortNodes = true)
    {
        $nodeId = $node->getIdHexStr();

        $filePath = null;
        if ($this->getDatadirBasePath()) {
            #$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'.yml';
            #$filePath = $this->getDatadirBasePath().'/node_'.$node->getIdHexStr().'_'.mt_rand(1000, 9999).'.yml';
            #$filePath = $this->getDatadirBasePath().'/node_'.substr($nodeId, -5).'_'.time().'.yml';
            #$filePath = $this->getDatadirBasePath().'/node_'.substr($nodeId, -5).'.yml';
            $filePath = $this->getDatadirBasePath() . '/node_' . $nodeId . '.yml';
        }
        if (!$node->getFilePath()) {
            $node->setFilePath($filePath);
        }
        $node->setDatadirBasePath($this->getDatadirBasePath());
        $node->setBucket($this);
        $node->setDataChanged(true);

        #$this->nodesId++;
        #$this->nodes[$this->nodesId] = $node;
        $this->nodes[$nodeId] = $node;
        $this->isFull();
        $this->setDataChanged(true);
        /*if($sortNodes){
            $this->nodesSort();
        }*/
    }

    private function maskDecr()
    {
        $maskByte = $this->getMaskByte();
        $maskBit = $this->getMaskBit();

        $newMaskByte = $maskByte;
        $newMaskBit = $maskBit - 1;
        $newMaskBitValue = 1 << $newMaskBit;

        if ($newMaskBitValue <= 0) {
            $newMaskByte++;
            $newMaskBit = 7;
            $newMaskBitValue = 1 << $newMaskBit;
        }

        return [$newMaskByte, $newMaskBit, $newMaskBitValue];
    }

    private function setChildBucketUpper($distance)
    {
        if (!$this->childBucketUpper) {
            list($newMaskByte, $newMaskBit, $newMaskBitValue) = $this->maskDecr();

            if ($newMaskByte < Node::ID_LEN_BYTE && $newMaskBitValue > 0) {
                $filePath = null;
                if ($this->getDatadirBasePath()) {
                    $filePath = $this->getDatadirBasePath() . '/bucket';
                    $filePath .= '_m' . sprintf('%02d', $newMaskByte) . '.' . $newMaskBit;
                    $filePath .= '_d' . sprintf('%03d', $distance);
                    $filePath .= '_u';
                    $filePath .= '.yml';

                    if (file_exists($filePath)) {
                        // This should never happen.
                        throw new RuntimeException('path for bucket alread exists: ' . $filePath);
                    }
                }

                $bucket = new Bucket($filePath);
                $bucket->setDistance($distance);
                $bucket->setMaskByte($newMaskByte);
                $bucket->setMaskBit($newMaskBit);
                $bucket->setIsUpper(true);
                $bucket->setDatadirBasePath($this->getDatadirBasePath());
                $bucket->setLocalNode($this->getLocalNode());
                $bucket->setDataChanged(true);
                $this->childBucketUpper = $bucket;

                $this->setDataChanged(true);
            }
            /*else{
                $msgOut = 'mask is at the end: /'.$maskByte.'/ /'.intToBin($maskBitValue).'/ ('.$maskBitValue.')';
                throw new RuntimeException('setChildBucketUpper: '.$msgOut);
                # NOT_IMPLEMENTED
            }*/
        }
        /*else{
            #$msgOut = intToBin($this->childBucketUpper->getMaskBit()).' ('.$this->childBucketUpper->getMaskBit().')';
            #fwrite(STDOUT, 'upper is set: '.$msgOut."\n");
        }*/
    }

    private function setChildBucketLower($distance)
    {
        if (!$this->childBucketLower) {
            list($newMaskByte, $newMaskBit, $newMaskBitValue) = $this->maskDecr();

            if ($newMaskByte < Node::ID_LEN_BYTE && $newMaskBitValue > 0) {
                $filePath = null;
                if ($this->getDatadirBasePath()) {
                    $filePath = $this->getDatadirBasePath() . '/bucket';
                    $filePath .= '_m' . sprintf('%02d', $newMaskByte) . '.' . $newMaskBit;
                    $filePath .= '_d' . sprintf('%03d', $distance);
                    $filePath .= '_l';
                    $filePath .= '.yml';

                    if (file_exists($filePath)) {
                        // This should never happen.
                        throw new RuntimeException('path for bucket alread exists: ' . $filePath);
                    }
                }

                $bucket = new Bucket($filePath);
                $bucket->setDistance($distance);
                $bucket->setMaskByte($newMaskByte);
                $bucket->setMaskBit($newMaskBit);
                $bucket->setIsLower(true);
                $bucket->setDatadirBasePath($this->getDatadirBasePath());
                $bucket->setLocalNode($this->getLocalNode());
                $bucket->setDataChanged(true);
                $this->childBucketLower = $bucket;

                $this->setDataChanged(true);
            }
            /*else{
                $msgOut = 'mask is at the end: /'.$maskByte.'/ /'.intToBin($maskBitValue).'/ ('.$maskBitValue.')';
                throw new RuntimeException('setChildBucketLower: '.$msgOut');
                # NOT_IMPLEMENTED
            }*/
        }
        /*else{
            #$msgOut = intToBin($this->childBucketLower->getMaskBit()).' ('.$this->childBucketLower->getMaskBit().')';
            #fwrite(STDOUT, 'upper is set: '.$msgOut."\n");
        }*/
    }

    private function nodesReEnclose($sortNodes = true, $level = 1)
    {
        /*$printLevel = $level;
        if($printLevel >= 5){
            $printLevel = 5;
        }
        $printPrefix = str_repeat("\t", $printLevel);
        */

        /*if($level >= 129){
            #fwrite(STDOUT, $printPrefix.'ERROR: level '.$level.' is too deep'."\n");
            throw new RuntimeException('reenclose level too deep: '.$level);
            #return;
        }*/

        $maskByte = $this->getMaskByte();
        $maskBit = $this->getMaskBit();
        $maskBitValue = 1 << $maskBit;
        if ($maskByte < Node::ID_LEN_BYTE && $maskBitValue > 0) {
            foreach ($this->nodes as $nodeId => $node) {
                #fwrite(STDOUT, $printPrefix.'reenclose node: '.$nodeId."\n");

                #$distance = $this->getLocalNode()->distance($node);
                $distance = $node->getDistance();

                $bucket = null;
                if ($distance[$maskByte] & $maskBitValue) {
                    #fwrite(STDOUT, $printPrefix.'reenclose match: upper'."\n");
                    $this->setChildBucketUpper($distance[$maskByte]);
                    $bucket = $this->childBucketUpper;
                } else {
                    #fwrite(STDOUT, $printPrefix.'reenclose match: lower'."\n");
                    $this->setChildBucketLower($distance[$maskByte]);
                    $bucket = $this->childBucketLower;
                }

                if ($bucket !== null) {
                    #$msgOut = 'reenclose child bucket: ';
                    #$msgOut .= '/'.$bucket->getMaskByte().'/ ';
                    #$msgOut .= ''.intToBin($bucket->getMaskBit()).' d='.intToBin($bucket->getDistance()).'';
                    $bucket->nodeEnclose($node, $sortNodes, $level + 1);
                }
                /*else{
                    $msgOut = 'reenclose bucket is full: ';
                    $msgOut .= 'l='.$level.' ';
                    $msgOut .= 'd='.intToBin($distance[$maskByte]).' ';
                    $msgOut .= 'm='.intToBin($maskBitValue).' n='.$node->getIdHexStr().'';
                    #throw new RuntimeException($msgOut);
                    # NOT_IMPLEMENTED
                }*/
            }

            $this->nodes = [];
            $this->setDataChanged(true);
        }
        /*else{
            #throw new RuntimeException('reenclose failed: l='.$level.' m='.intToBin($maskBitValue).'');
            # NOT_IMPLEMENTED: what happens when a bucket is full?
        }*/
    }

    public function nodeEnclose(Node $node, $sortNodes = true, $level = 1)
    {
        if (!$this->getLocalNode()) {
            throw new RuntimeException('localNode not set.');
        }

        $nodeEncloseReturnValue = $node;

        /*if($level >= 260){
            fwrite(STDOUT, str_repeat("\t", $level).'ERROR: level '.$level.' is too deep'."\n");
            throw new RuntimeException('enclose level too deep: '.$level);
        }*/

        /*$printLevel = $level;
        if($printLevel >= 5){
            $printLevel = 5;
        }
        $printPrefix = str_repeat("\t", $printLevel);
        */

        if ($node->getIdHexStr() != '00000000-0000-4000-8000-000000000000') {
            $distance = $node->getDistance();
            if (!$distance) {
                $distance = $this->getLocalNode()->distance($node);
                $node->setDistance($distance);
            }

            $maskByte = 0;
            $maskBit = 7; // Root MaskBit

            if ($this->getIsUpper() || $this->getIsLower()) {
                $maskByte = $this->getMaskByte();
                $maskBit = $this->getMaskBit();
            } else {
                $this->setMaskByte($maskByte);
                $this->setMaskBit($maskBit);
            }

            $maskBitValue = 1 << $maskBit;

            /*
            if($this->childBucketUpper){
                #fwrite(STDOUT, $printPrefix.'upper: '.intToBin($this->childBucketUpper->getMaskBit())."\n");
            }
            else{
                #fwrite(STDOUT, $printPrefix.'upper: N/A'."\n");
            }
            if($this->childBucketLower){
                #fwrite(STDOUT, $printPrefix.'lower: '.intToBin($this->childBucketLower->getMaskBit())."\n");
            }
            else{
                #fwrite(STDOUT, $printPrefix.'lower: N/A'."\n");
            }
            */

            #timeStop('onode find start');
            $onode = $this->nodeFind($node);
            #timeStop('onode find end');
            if (!$onode) {
                if ($this->getNodesNum() < static::$SIZE_MAX && !$this->getIsFull()) {
                    $this->nodeAdd($node, $sortNodes);
                    $nodeEncloseReturnValue = $node;

                    if ($this->isFull()) {
                        #timeStop('nodesReEnclose start');
                        $this->nodesReEnclose($sortNodes, $level + 1);
                        #timeStop('nodesReEnclose end');
                    }
                } else {
                    $bucket = null;
                    if ($distance[$maskByte] & $maskBitValue) {
                        $this->setChildBucketUpper($distance[$maskByte]);
                        $bucket = $this->childBucketUpper;
                    } else {
                        $this->setChildBucketLower($distance[$maskByte]);
                        $bucket = $this->childBucketLower;
                    }

                    if ($bucket !== null) {
                        #$msgOut = intToBin($bucket->getMaskBit()).' d='.intToBin($bucket->getDistance()).'';
                        $bucket->nodeEnclose($node, $sortNodes, $level + 1);
                    }
                    /*else{
                        #$msgOut = 'enclose: bucket is full: ';
                        #$msgOut .= 'l='.$level.' ';
                        #$msgOut .= 'd='.intToBin($distance[$maskByte]).' ';
                        #$msgOut .= 'm='.intToBin($maskBitValue).' n='.$node->getIdHexStr().'';
                        
                        #throw new RuntimeException($msgOut);
                        # NOT_IMPLEMENTED: what happens when a bucket is not found?
                    }*/
                }
            } else {
                #usleep(1000000);
                #timeStop('onode update start');
                $onode->update($node);
                #timeStop('onode update end');
                $nodeEncloseReturnValue = $onode;
            }
        }

        return $nodeEncloseReturnValue;
    }

    public function nodeRemoveByIndex($index)
    {
        unset($this->nodes[$index]);
    }

    public function nodesSort()
    {
        uasort($this->nodes, function ($node_a, $node_b) {
            $dist_a = $this->getLocalNode()->distance($node_a);
            $dist_b = $this->getLocalNode()->distance($node_b);

            if ($dist_a == $dist_b) {
                return 0;
            }
            return $dist_a < $dist_b ? -1 : 1;
        });

        $this->setDataChanged(true);
    }
}
