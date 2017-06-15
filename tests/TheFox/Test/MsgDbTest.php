<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Finder\Finder;
use TheFox\PhpChat\MessageDatabase;
use TheFox\PhpChat\Message;
use TheFox\Dht\Kademlia\Node;

class MsgDbTest extends PHPUnit_Framework_TestCase
{
    const SSL_KEY_PUB1 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2+wZQQSxQXaxUmL/bg7O
gA7fOuw4Kk6/UtEntvM4O1Ll75l0ptgalwkO8DFhwRmWxDd0BYd/RxsbWrii3/1R
6+HSQdjyeeY3gQFdL7r65RRvXkYTtNSsFDeqcVQC+c6lFqRozQDNnAtxmy1Fhc0z
IUeC0iWNXIJciDYLTJV6VB0WNNl+5mCV2KaH2H3opw2A0c/+FTPWbvgf28WAd4FQ
koWiNjnDEDl5Ti39HeJN7q9LjpiafRTSrwE/kNcFNEtcdcxArxITuR92H+VjgXqs
dre0pqN7q1cJCZ/XP8Z0ZWA8rpLym+3S+FJaTJXhHBAv05hOu2zfzKUqaxmatAWz
NgxY7wvarGol/kqBYqyfVO/c1AOdr2Uw9rO0vJ9nPADih+OMYltaX521i6gvngdc
P7JJIZyNcZgN1l6HbO0KxugD2nJfkgGmU/ihIEpHjmrMXYMSzJy1KVOmLFpd8tiu
WXQCmarTOlzkcH7jmVqDRAjMUvDoAve4LYl0jua1W2wtCm1DisgIK6MCt38W8Zn3
o1pxgj1LiQmhAx4D9nL4MH14Zi++mK0iu8tJeXJdcql1l+bOJfkRjkNh3QjmLX3b
zoDXmjCC/vFQgspeMCSnIeml5Ymlk1tgxgiRNAPRpttbyr0jzlnUGEYZ/fGzNsY7
O5mYMzSLyuOXR5xhBhG7fjsCAwEAAQ==
-----END PUBLIC KEY-----
';
    const SSL_KEY_PUB2 = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAwrX73etzvLFRell4nfIT
3YFOuqAwCU6w1N1uipV+e96fx00ZsKQHvugyhwSP85a5TZ4qfQQie3kyRrwwL91s
dXECxskfOXtO94k9CENZGihkacnLUp8eAPJ3dJNHcM9AZm+gFVhVU7XmcQxXex6p
k3nWpCyrrK4ZUeg+D858Tadgd4w+uOgKozUyARrWU5AVVY27X/u97a3DkKbNZhuC
h3gSkBD/d8rjwe6d9siHb6aqiw6DBYXL3AlsDoN/lGvnV1U3wY9zRQ5BuebBzt7Q
ndAqKC9ZdCEK/JFBXkjJsiNOlKmc0AJpf41SyOqvkLvpBxwPvbTp4VLoGwMty+nT
8ke/REypXS9scFDlE6k71xAMi9OBthiVP5lszptPEn3cfhG0YRLuzkLOpcV5Gm0r
egQS+y0TFEu25vUbGcWKKjrWxG+TkWgyKkiylJoXdPRXxDrHA8KEY651x8vs/gsy
rdhoXy/EI+fxI9ytf7JLnc2OP2eh8qdUhcLMMs9mUOy9hojaxBYMAXRqJK53Lsgd
kWcl2BJ8IxSMYUeTbb8UmS2Qr8wWzEVqd/SQ4olC3gcPReEohMpJ+X0mp7CmjQUS
4GoZlLrIR/xaI76pSJLH2FBfHWiLS/Cbpgw8IEcKJFJVIqi7qjHw7MaoXMIqxZVT
2JGsj8q54He5gnVI01MEWr0CAwEAAQ==
-----END PUBLIC KEY-----
';

    public function testSerialize()
    {
        $db1 = new MessageDatabase();

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2940');
        $db1->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2941');
        $db1->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2942');
        $db1->msgAdd($msg);

        $db2 = unserialize(serialize($db1));

        $this->assertEquals(3, count($db2->getMsgs()));

        #\Doctrine\Common\Util\Debug::dump($db2);

        #\Doctrine\Common\Util\Debug::dump($msg);
    }

    public function testSaveLoad()
    {
        $runName = uniqid('', true);
        $dbFileName = 'testfile_msgdb_' . date('Ymd_His') . '_' . $runName . '.yml';

        $db = new MessageDatabase('test_data/' . $dbFileName);

        $fileName = 'testfile_msg1_' . date('Ymd_His') . '_' . $runName . '.yml';
        $msg = new Message('test_data/' . $fileName);
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2940');
        $msg->setDataChanged(true);
        $db->msgAdd($msg);

        $fileName = 'testfile_msg2_' . date('Ymd_His') . '_' . $runName . '.yml';
        $msg = new Message('test_data/' . $fileName);
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2941');
        $msg->setDataChanged(true);
        $db->msgAdd($msg);

        $fileName = 'testfile_msg3_' . date('Ymd_His') . '_' . $runName . '.yml';
        $msg = new Message('test_data/' . $fileName);
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2942');
        $msg->setDataChanged(true);
        $db->msgAdd($msg);

        $db->save();

        $finder = new Finder();
        $files = $finder->in('test_data')->depth(0)->name($dbFileName)->files();
        $this->assertEquals(1, count($files));

        $book = new MessageDatabase('test_data/' . $dbFileName);
        $this->assertTrue($book->load());

        $book = new MessageDatabase('test_data/not_existing.yml');
        $this->assertFalse($book->load());
    }

    public function testMsgAdd()
    {
        $db = new MessageDatabase();
        $db->setDatadirBasePath('test_data');

        $runName = uniqid('', true);
        $fileName = 'testfile_msg_' . date('Ymd_His') . '_' . $runName . '.yml';

        $msg = new Message('test_data/' . $fileName);
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2943');
        $msg->setDataChanged(true);
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $msg->setDataChanged(true);
        $db->msgAdd($msg);

        $this->assertEquals(2, count($db->getMsgs()));
        $this->assertEquals(2, $db->getMsgsCount());
    }

    public function testMsgUpdate()
    {
        $db = new MessageDatabase();

        $msg1 = new Message();
        $msg1->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $msg1->setSentNodes(['76cabb4d-e729-4a50-a792-e223704c2948']);
        $msg1->setDataChanged(true);
        $db->msgAdd($msg1);

        $msg2 = new Message();
        $msg2->setId('76cabb4d-e729-4a50-a792-e223704c2945');
        $this->assertFalse($db->msgUpdate($msg2));

        $msg2 = new Message();
        $msg2->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $this->assertFalse($db->msgUpdate($msg2));

        $msg2->setVersion(2);
        $msg2->setSrcNodeId('76cabb4d-e729-4a50-a792-e223704c2946');
        $msg2->setSrcSslKeyPub(static::SSL_KEY_PUB1);
        $msg2->setDstNodeId('76cabb4d-e729-4a50-a792-e223704c2947');
        $msg2->setDstSslPubKey(static::SSL_KEY_PUB2);
        $msg2->setSubject('subj1');
        $msg2->setBody('body1');
        $msg2->setText('text1');
        $msg2->setPassword('pwd1');
        $msg2->setChecksum('checksum1');
        $msg2->setSentNodes(['76cabb4d-e729-4a50-a792-e223704c2949']);
        $msg2->setRelayCount(1);
        $msg2->setForwardCycles(1);
        $msg2->setEncryptionMode('S');
        $msg2->setStatus('D');
        $msg2->setTimeCreated(24);
        $this->assertTrue($db->msgUpdate($msg2));

        $this->assertEquals([
            '76cabb4d-e729-4a50-a792-e223704c2948',
            '76cabb4d-e729-4a50-a792-e223704c2949',
        ], $msg1->getSentNodes());
    }

    public function testGetMsgWithNoDstNodeId()
    {
        $db = new MessageDatabase();

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2943');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $msg->setDstNodeId('76cabb4d-e729-4a50-a792-e223704c2947');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2945');
        $db->msgAdd($msg);

        $msgs = $db->getMsgWithNoDstNodeId();
        $this->assertTrue(is_array($msgs));
        $this->assertEquals(2, count($msgs));
        #\Doctrine\Common\Util\Debug::dump($msgs);
        $this->assertEquals('76cabb4d-e729-4a50-a792-e223704c2943', $msgs['76cabb4d-e729-4a50-a792-e223704c2943']->getId());
        $this->assertEquals('76cabb4d-e729-4a50-a792-e223704c2945', $msgs['76cabb4d-e729-4a50-a792-e223704c2945']->getId());
    }

    public function testGetUnsentMsgs()
    {
        $db = new MessageDatabase();

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2943');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $msg->setSentNodes(['76cabb4d-e729-4a50-a792-e223704c2947']);
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2945');
        $db->msgAdd($msg);

        $msgs = $db->getUnsentMsgs();
        $this->assertTrue(is_array($msgs));
        $this->assertEquals(2, count($msgs));
    }

    public function testGetMsgsForDst()
    {
        $db = new MessageDatabase();

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2943');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $msg->setDstNodeId('76cabb4d-e729-4a50-a792-e223704c2947');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2945');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2946');
        $msg->setDstNodeId('76cabb4d-e729-4a50-a792-e223704c2947');
        $db->msgAdd($msg);

        $node = new Node();
        $node->setIdHexStr('76cabb4d-e729-4a50-a792-e223704c2947');

        $msgs = $db->getMsgsForDst($node);
        $this->assertTrue(is_array($msgs));
        $this->assertTrue(isset($msgs['76cabb4d-e729-4a50-a792-e223704c2944']));
        $this->assertTrue(isset($msgs['76cabb4d-e729-4a50-a792-e223704c2946']));
    }

    public function testGetMsgById()
    {
        $db = new MessageDatabase();

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2943');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2944');
        $db->msgAdd($msg);

        $msg = new Message();
        $msg->setId('76cabb4d-e729-4a50-a792-e223704c2945');
        $db->msgAdd($msg);

        $msg = $db->getMsgById('76cabb4d-e729-4a50-a792-e223704c2944');
        $this->assertTrue(is_object($msg));

        $msg = $db->getMsgById('76cabb4d-e729-4a50-a792-e223704c2946');
        $this->assertEquals(null, $msg);
    }
}
