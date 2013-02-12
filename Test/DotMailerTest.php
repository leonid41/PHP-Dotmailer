<?php

require_once '../DotMailer.php';

/**
 * Test class for DotMailer.
 * Generated by PHPUnit on 2012-05-23 at 15:38:01.
 */
class DotMailerTest extends PHPUnit_Framework_TestCase {

  /**
   * @var DotMailer
   */
  protected $object;
  protected $username;
  protected $password;
  protected $addressBookId;
  protected $campaignId;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    // Read config variables.
    $this->username = $GLOBALS['username'];
    $this->password = $GLOBALS['password'];
    $this->addressBookId = $GLOBALS['addressBookId'];
    $this->campaignId = $GLOBALS['campaignId'];

    // Set up DotMailer object.
    $this->object = new DotMailer($this->username, $this->password);

    // Start off with an empty address book.
    $this->object->RemoveAllContactsFromAddressBook($this->addressBookId);
  }
  
  public function testNoConstructorParametersCausesException() {
    $this->setExpectedException('UsernameAndPasswordNotFoundException');
    $this->object = new DotMailer('', '');
  }

  public function testListAddressBooks() {
    $addressBooks = $this->object->ListAddressBooks();
    $addressBook = array_pop($addressBooks);
    $this->assertRegExp('/\d*?/', (string) $addressBook->ID);
    $this->assertRegExp('/[a-zA-Z0-9 ]*?/', $addressBook->Name);
  }

  public function testListContactsInAddressBookWithIncorrectParameters() {
    $this->setExpectedException('MissingRequiredParametersException');
    $contacts = $this->object->ListContactsInAddressBook(0, 10, 0);
  }

  public function testListContactsInAddressBook() {
    $contacts = $this->object->ListContactsInAddressBook($this->addressBookId, 10, 0);
    $this->assertEquals(TRUE, is_array($contacts));
  }

  public function testAddContactToAddressBook() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    $result = $this->object->AddContactToAddressBook($contact, $fields, $this->addressBookId);

    if ($result === FALSE) {
      $this->fail('Failed adding contact to address book.');
      return;
    }

    $this->assertEquals($contact['Email'], $result->Email);
    $this->assertEquals($contact['AudienceType'], $result->AudienceType);
    $this->assertEquals($contact['OptInType'], $result->OptInType);
    $this->assertEquals($contact['Notes'], $result->Notes);
  }

  public function testRemoveAllContactsFromAddressBook() {
    $result = $this->object->RemoveAllContactsFromAddressBook($this->addressBookId);
    $contacts = $this->object->ListContactsInAddressBook($this->addressBookId, 10, 0);
    $this->assertEquals(TRUE, is_array($contacts));
    $this->assertEquals(0, count($contacts));
  }

  public function testAddContactsToAddressBookWithProgress() {

    $data = '"Email","AudienceType","OptInType","EmailType","Notes","FIRSTNAME","LASTNAME"
"test@example.com","B2B","Single","Html","This is an API test contact","John","Test"';

    $dataType = 'CSV';
    $progressId = $this->object->AddContactsToAddressBookWithProgress($this->addressBookId, $data, $dataType);

    $result = $this->object->GetContactImportProgress($progressId);

    if ($result === FALSE) {
      $this->fail('Failed finding import progress for address book.');
      return;
    }

    $this->assertEquals('NotFinished', $result);
  }

  public function testAddContactsToAddressBookWithProgressWithUnknownDataType() {
    $data = '';

    $this->setExpectedException('InvalidFileFormatException');

    $dataType = 'UNKNOWN';
    $this->object->AddContactsToAddressBookWithProgress($this->addressBookId, $data, $dataType);
  }

  public function testGetAddressBookContactCountWithInvalidParameter() {
    $this->setExpectedException('MissingRequiredParametersException');
    $this->object->GetAddressBookContactCount('dsaf96dsaf8');
  }

  public function testGetAddressBookContactCountFromNonExistingAddressBook() {
    $count = $this->object->GetAddressBookContactCount(1234567);
    $this->assertFalse($count);
  }

  public function testGetAddressBookContactCount() {
    $count = $this->object->GetAddressBookContactCount($this->addressBookId);
    $this->assertEquals(0, $count);

    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    $result = $this->object->AddContactToAddressBook($contact, $fields, $this->addressBookId);

    if ($result === FALSE) {
      $this->fail('Failed adding contact to address book.');
      return;
    }

    $count = $this->object->GetAddressBookContactCount($this->addressBookId);
    $this->assertEquals(1, $count);
  }

  public function CreateAndDestroyAddressBook() {
    $addressBookName = 'zzzsabnd8ad76stdgsadbnn';
    $addressBook = $this->object->CreateAddressBook($addressBookName);

    $this->assertEquals($addressBookName, $addressBook->Name);
    $this->assertTrue(is_numeric($addressBook->ID));

    $result = $this->object->DeleteAddressBook($addressBook->ID);
  }

  public function testGetContactByEmailWithExistingContact() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    $result = $this->object->AddContactToAddressBook($contact, $fields, $this->addressBookId);
    $foundContact = $this->object->GetContactByEmail($contact['Email']);
    $this->assertTrue(is_numeric($foundContact->ID));
    $this->assertEquals($contact['Email'], $foundContact->Email);
  }

  public function testGetContactByEmailWithNonExistingContact() {
    // Create random email address
    $email = 'test' . rand(0, 1000) . '@test' . rand(0, 1000) . '.co.uk';

    $foundContact = $this->object->GetContactByEmail($email);
    $this->assertFalse($foundContact);
  }

  public function testGetContactById() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    $result = $this->object->AddContactToAddressBook($contact, $fields, $this->addressBookId);

    if ($result === FALSE) {
      $this->fail('Failed adding contact to address book.');
      return;
    }

    $contact['ID'] = $result->ID;

    $foundContact = $this->object->GetContactById($contact['ID']);
    $this->assertTrue(is_numeric($foundContact->ID));
    $this->assertEquals($contact['Email'], $foundContact->Email);
  }

  public function testCreateContact() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    $result = $this->object->CreateContact($contact, $fields);
  }

  public function testUpdateContact() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    $result = $this->object->CreateContact($contact, $fields);

    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'UpdatedName'
    );

    $result = $this->object->UpdateContact($contact, $fields);
    $foundContact = $this->object->GetContactByEmail($contact['Email']);
    $this->assertTrue(is_numeric($foundContact->ID));

    $firstnameId = 0;
    $lastnameId = 0;

    $this->object->flattenContactFields($foundContact);
    $this->assertEquals($fields['FIRSTNAME'], $foundContact->fields['FIRSTNAME']);
    $this->assertEquals($fields['LASTNAME'], $foundContact->fields['LASTNAME']);
  }

  public function testGetServerTime() {
    $result = $this->object->GetServerTime();
    $this->assertTrue(is_string($result));
    $this->assertRegExp('/\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d.\d*?Z/', $result);
  }

  public function testListContactDataLabels() {
    $result = $this->object->ListContactDataLabels();
    $this->assertTrue(is_array($result->ContactDataLabel));
    $this->assertTrue(is_object($result->ContactDataLabel[0]));
  }

  public function testGetCurrentAccountInfo() {
    $result = $this->object->GetCurrentAccountInfo();
    $this->assertTrue(is_integer($result->Id));
    $this->assertTrue(is_object($result->Properties));
    $this->assertTrue(is_array($result->Properties->APIAccountProperty));
  }

  public function testListSentCampaignsWithActivitySinceDate() {
    $result = $this->object->ListSentCampaignsWithActivitySinceDate('2001-10-26T21:32:52');
    $this->assertTrue(count($result) > 0);
  }

  public function testListSentCampaignsWithActivitySinceCalculatedDate() {
    $result = $this->object->ListSentCampaignsWithActivitySinceDate(date('Y-m-d\TH:i:s', strtotime('-3 months')));
    $this->assertTrue(count($result) > 0);
  }

  public function testGetCampaign() {
    $campaigns = $this->object->ListSentCampaignsWithActivitySinceDate('2001-10-26T21:32:52');
    $campaign = array_pop($campaigns);

    $result = $this->object->GetCampaign($campaign->Id);
    $this->assertTrue(isset($result->FromName));
    $this->assertEquals($campaign->Status, $result->Status);
  }

  public function testGetCampaignSummary() {
    $result = $this->object->GetCampaignSummary($this->campaignId);

    $this->assertTrue(is_numeric($result->NumOpens));
    $this->assertTrue(is_numeric($result->NumSent));
    $this->assertTrue(!is_numeric($result->DateSent));
  }

  public function testGetCampaignSummaryWithAnInvalidCampaignId() {
    $result = $this->object->GetCampaignSummary(1234546);
    $this->assertFalse($result);
  }

  public function testListAddressBooksForCampaign() {
    $result = $this->object->ListAddressBooksForCampaign($this->campaignId);
    $this->assertTrue(isset($result[0]->ID));
    $this->assertTrue(is_numeric($result[0]->ID));
  }

  public function testListAddressBooksForCampaignWithAnInvalidCampaignId() {
    $result = $this->object->ListAddressBooksForCampaign(1234546);
    $this->assertFalse($result);
  }

  public function testListAddressBooksForContact() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );
    
    $result = $this->object->ListAddressBooksForContact($contact);
    $this->assertFalse($result);
    
    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );
    
    $this->object->AddContactToAddressBook($contact, $fields, $this->addressBookId);

    $result = $this->object->ListAddressBooksForContact($contact);
    
    $this->assertTrue(gettype($result) == 'object');
    $this->assertTrue($result->ID == $this->addressBookId);
  }

  public function testSendCampaignToContact() {
    $contact = array(
      'Email' => 'testemail@test.co.uk',
      'AudienceType' => 'B2B',
      'OptInType' => 'Single',
      'EmailType' => 'Html',
      'Notes' => 'This is an API test contact'
    );

    $fields = array(
      'FIRSTNAME' => 'John',
      'LASTNAME' => 'Test'
    );

    //$this->object->AddContactToAddressBook($contact, $fields, $this->addressBookId);
    $foundContact = $this->object->GetContactByEmail($contact['Email']);
    
    $result = $this->object->SendCampaignToContact($this->campaignId, $foundContact->ID, date('Y-m-d\TH:i:s', strtotime('+5 minutes')));
    $this->assertTrue($result);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    // Keep everything clean by removing everything from the address book.
    $this->object->RemoveAllContactsFromAddressBook($this->addressBookId);
  }

}