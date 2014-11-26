<?php

use Symfony\Component\Finder\Finder;

use TheFox\PhpChat\Addressbook;
use TheFox\PhpChat\Contact;

class AddressbookTest extends PHPUnit_Framework_TestCase{
	
	public function testSerialize(){
		$book1 = new Addressbook();
		
		$contact = new Contact();
		$contact->setId(1);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$contact->setUserNickname('nick1');
		$contact->setTimeCreated(24);
		$book1->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(2);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$contact->setUserNickname('nick2');
		$contact->setTimeCreated(25);
		$book1->contactAdd($contact);
		
		$book2 = unserialize(serialize($book1));
		
		$this->assertEquals(2, count($book2->getContacts()));
	}
	
	public function testSaveLoad(){
		$runName = uniqid('', true);
		$fileName = 'testfile_addressbook_'.date('Ymd_His').'_'.$runName.'.yml';
		
		$book = new Addressbook('test_data/'.$fileName);
		
		$contact = new Contact();
		$contact->setId(1);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$contact->setUserNickname('nick1');
		$contact->setTimeCreated(24);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(2);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$contact->setUserNickname('nick2');
		$contact->setTimeCreated(25);
		$book->contactAdd($contact);
		
		$book->save();
		
		$finder = new Finder();
		$files = $finder->in('test_data')->depth(0)->name($fileName)->files();
		$this->assertEquals(1, count($files));
		
		
		$book = new Addressbook('test_data/'.$fileName);
		$this->assertTrue($book->load());
		
		$book = new Addressbook('test_data/not_existing.yml');
		$this->assertFalse($book->load());
	}
	
	public function testContactAdd(){
		$book = new Addressbook();
		
		$contact = new Contact();
		$contact->setId(1);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$contact->setUserNickname('nick1');
		$contact->setTimeCreated(24);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(2);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$contact->setUserNickname('nick2');
		$contact->setTimeCreated(25);
		$book->contactAdd($contact);
		
		$this->assertEquals(2, count($book->getContacts()));
	}
	
	public function testContactGetByNodeId(){
		$book = new Addressbook();
		
		$contact = new Contact();
		$contact->setId(1);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$contact->setUserNickname('nick1');
		$contact->setTimeCreated(24);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(2);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$contact->setUserNickname('nick2');
		$contact->setTimeCreated(25);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(3);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1740');
		$contact->setUserNickname('nick3');
		$contact->setTimeCreated(26);
		$book->contactAdd($contact);
		
		$contact = $book->contactGetByNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$this->assertTrue(is_object($contact));
		$this->assertTrue($contact instanceof Contact);
	}
	
	public function testContactsGetByNick(){
		$book = new Addressbook();
		
		$contact = new Contact();
		$contact->setId(1);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$contact->setUserNickname('nick1');
		$contact->setTimeCreated(24);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(2);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$contact->setUserNickname('nick2');
		$contact->setTimeCreated(25);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(3);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1740');
		$contact->setUserNickname('nick3');
		$contact->setTimeCreated(26);
		$book->contactAdd($contact);
		
		$contacts = $book->contactsGetByNick('nick2');
		#\Doctrine\Common\Util\Debug::dump($contacts[0]);
		$this->assertTrue(is_array($contacts));
		$this->assertEquals(1, count($contacts));
		$this->assertTrue(is_object($contacts[0]));
		$this->assertTrue($contacts[0] instanceof Contact);
	}
	
	public function testContactRemove(){
		$book = new Addressbook();
		
		$contact = new Contact();
		$contact->setId(1);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1738');
		$contact->setUserNickname('nick1');
		$contact->setTimeCreated(24);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(2);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1739');
		$contact->setUserNickname('nick2');
		$contact->setTimeCreated(25);
		$book->contactAdd($contact);
		
		$contact = new Contact();
		$contact->setId(3);
		$contact->setNodeId('cafed00d-2131-4159-8e11-0b4dbadb1740');
		$contact->setUserNickname('nick3');
		$contact->setTimeCreated(26);
		$book->contactAdd($contact);
		
		$this->assertEquals(3, count($book->getContacts()));
		
		$this->assertTrue($book->contactRemove(2));
		$contacts = $book->getContacts();
		$this->assertEquals(2, count($contacts));
		$this->assertEquals(1, $contacts[1]->getId());
		$this->assertEquals(3, $contacts[3]->getId());
		
		$this->assertFalse($book->contactRemove(4));
		$contacts = $book->getContacts();
		$this->assertEquals(2, count($contacts));
		$this->assertEquals(1, $contacts[1]->getId());
		$this->assertEquals(3, $contacts[3]->getId());
	}
	
	public function testGetContacts(){
		$this->assertTrue(true);
		
		$book = new Addressbook();
	}
	
}
