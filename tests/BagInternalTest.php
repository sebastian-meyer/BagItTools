<?php

namespace whikloj\BagItTools\Test;

use whikloj\BagItTools\Bag;

/**
 * Tests private or protected functions.
 * @package whikloj\BagItTools\Test
 */
class BagInternalTest extends BagItTestFramework
{

  /**
   * Test makeRelative
   * @group BagInternal
   * @covers \whikloj\BagItTools\Bag::makeRelative
   */
    public function testMakeRelativePlain()
    {
        $methodCall = $this->getReflectionMethod('\whikloj\BagItTools\Bag', 'makeRelative');

        $bag = Bag::create($this->tmpdir);
        $baseDir = $bag->getBagRoot();

        $valid_paths = [
        'data/image/someimage.jpg' => 'data/image/someimage.jpg',
        'data/picture.txt' => 'data/picture.txt',
        'data/images/subimages/../picture.jpg' => 'data/images/picture.jpg',
        'data/one/../two/../three/.././/eggs.txt' => 'data/eggs.txt',
        'somefile.txt' => 'somefile.txt',
        '/var/lib/somewhere' => 'var/lib/somewhere',
        ];

        $invalid_paths = [
        'data/../../../images/places/image.jpg',
        'data/one/..//./two/./../../three/.././../eggs.txt',
        ];

        foreach ($valid_paths as $path => $expected) {
            $fullpath = $baseDir . DIRECTORY_SEPARATOR . $path;
            $relative = $methodCall->invokeArgs($bag, [$fullpath]);
            $this->assertEquals($expected, $relative);
        }

        foreach ($invalid_paths as $path) {
            $fullpath = $baseDir . DIRECTORY_SEPARATOR . $path;
            $relative = $methodCall->invokeArgs($bag, [$fullpath]);
            $this->assertEquals('', $relative);
        }
    }

  /**
   * Test pathInBagData
   * @group BagInternal
   * @covers \whikloj\BagItTools\Bag::pathInBagData
   */
    public function testPathInBagData()
    {
        $methodCall = $this->getReflectionMethod('\whikloj\BagItTools\Bag', 'pathInBagData');

        $bag = Bag::create($this->tmpdir);

        $valid_paths = [
        'data/image/someimage.jpg',
        'data/picture.txt',
        'data/images/subimages/../picture.jpg',
        'data/one/../two/../three/.././/eggs.txt',
        ];
        $invalid_paths = [
        'data/../../../images/places/image.jpg',
        'somefile.txt',
        'Whatever',
        'data/one/../two/../three/.././../eggs.txt',
        ];

        foreach ($valid_paths as $path) {
            $relative = $methodCall->invokeArgs($bag, [$path]);
            $this->assertTrue($relative);
        }

        foreach ($invalid_paths as $path) {
            $relative = $methodCall->invokeArgs($bag, [$path]);
            $this->assertFalse($relative);
        }
    }

    /**
     * Test the BagInfo text wrapping function.
     * @group BagInternal
     * @covers \whikloj\BagItTools\Bag::wrapBagInfoText
     * @covers \whikloj\BagItTools\Bag::wrapAtLength
     */
    public function testWrapBagInfo()
    {
        $test_matrix = [
            "Source-Organization: Organization transferring the content." => [
                "Source-Organization: Organization transferring the content.",
            ],
            "Contact-Name: Person at the source organization who is responsible for the content transfer." => [
                "Contact-Name: Person at the source organization who is responsible for the",
                "  content transfer.",
            ],
            "Bag-Size: The size or approximate size of the bag being transferred, followed by an abbreviation such" .
            " as MB (megabytes), GB (gigabytes), or TB (terabytes): for example, 42600 MB, 42.6 GB, or .043 TB." .
            " Compared to Payload-Oxum (described next), Bag-Size is intended for human consumption. This metadata" .
            " element SHOULD NOT be repeated." => [
                "Bag-Size: The size or approximate size of the bag being transferred, followed",
                "  by an abbreviation such as MB (megabytes), GB (gigabytes), or TB (terabytes):",
                "  for example, 42600 MB, 42.6 GB, or .043 TB. Compared to Payload-Oxum",
                "  (described next), Bag-Size is intended for human consumption. This metadata",
                "  element SHOULD NOT be repeated.",
            ],
        ];

        $bag = Bag::create($this->tmpdir);
        $methodCall = $this->getReflectionMethod('\whikloj\BagItTools\Bag', 'wrapBagInfoText');

        foreach ($test_matrix as $string => $expected) {
            $output = $methodCall->invokeArgs($bag, [$string]);
            $this->assertEquals($expected, $output);
        }
    }

    /**
     * Test internal version comparison.
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::compareVersion
     */
    public function testVersionCompare()
    {
        $bag = Bag::create($this->tmpdir);
        $method = $this->getReflectionMethod('\whikloj\BagItTools\Bag', 'compareVersion');

        // Current version is 1.0
        $this->assertEquals(-1, $method->invokeArgs($bag, ['0.97']));
        $this->assertEquals(0, $method->invokeArgs($bag, ['1.0']));
        $this->assertEquals(1, $method->invokeArgs($bag, ['1.1']));
    }


    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::resetErrorsAndWarnings
     */
    public function testResetErrorsAndWarnings()
    {
        $this->tmpdir = $this->copyTestBag(self::TEST_RESOURCES . DIRECTORY_SEPARATOR . 'Test097Bag');
        $bag = Bag::load($this->tmpdir);
        $this->assertEquals('0.97', $bag->getVersionString());
        touch($bag->getDataDirectory() . DIRECTORY_SEPARATOR . 'oops.txt');
        $this->assertFalse($bag->validate());
        $this->assertCount(1, $bag->getErrors());
        $this->assertCount(2, $bag->getWarnings());

        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'resetErrorsAndWarnings'
        );
        $methodCall->invokeArgs($bag, []);

        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::addBagError
     */
    public function testAddBagError()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'addBagError'
        );
        $methodCall->invokeArgs($bag, ['some_file', 'some_error']);
        $this->assertCount(1, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::addBagWarning
     */
    public function testAddBagWarning()
    {
        $bag = Bag::create($this->tmpdir);
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(0, $bag->getWarnings());
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'addBagWarning'
        );
        $methodCall->invokeArgs($bag, ['some_file', 'some_warning']);
        $this->assertCount(0, $bag->getErrors());
        $this->assertCount(1, $bag->getWarnings());
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::arrayKeyExistsNoCase
     */
    public function testArrayKeyExistsNoCase()
    {
        $test_array = [
            ['name' => 'BOB'],
        ];
        $bag = Bag::create($this->tmpdir);
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'arrayKeyExistsNoCase'
        );
        $this->assertTrue($methodCall->invokeArgs(
            $bag,
            ['BOB', 'name', $test_array]
        ));
        $this->assertTrue($methodCall->invokeArgs(
            $bag,
            ['bob', 'name', $test_array]
        ));
        $this->assertTrue($methodCall->invokeArgs(
            $bag,
            ['boB', 'name', $test_array]
        ));
        $this->assertFalse($methodCall->invokeArgs(
            $bag,
            ['BOO', 'name', $test_array]
        ));
    }

    /**
     * Test that getDirectory finds a single directory.
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::getDirectory
       */
    public function testGetDirectorySuccess()
    {
        $tmp = $this->getTempName();
        $bag = Bag::create($tmp);
        $expected_dir = $this->tmpdir . DIRECTORY_SEPARATOR . "expectedDir";
        mkdir($expected_dir, 0777, true);
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'getDirectory'
        );
        $this->assertEquals($expected_dir, $methodCall->invokeArgs(
            $bag,
            [$this->tmpdir]
        ));
        $this->deleteDirAndContents($tmp);
    }

    /**
     * Test that getDirectory fails with multiple subdirectories.
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::getDirectory
       * @expectedException \whikloj\BagItTools\BagItException
     */
    public function testGetDirectoryFails()
    {
        $tmp = $this->getTempName();
        $bag = Bag::create($tmp);
        $expected_dir1 = $this->tmpdir . DIRECTORY_SEPARATOR . "expectedDir";
        $expected_dir2 = $this->tmpdir . DIRECTORY_SEPARATOR . "anotherExpectedDir";
        mkdir($expected_dir1, 0777, true);
        mkdir($expected_dir2, 0777, true);
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'getDirectory'
        );
        $this->assertEquals($expected_dir1, $methodCall->invokeArgs(
            $bag,
            [$this->tmpdir]
        ));
        $this->deleteDirAndContents($tmp);
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::clearPayloadManifests
     */
    public function testClearPayloadManifests()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->addAlgorithm("sha1");
        $bag->update();
        $this->assertFileExists($bag->makeAbsolute('manifest-sha1.txt'));
        $this->assertFileExists($bag->makeAbsolute('manifest-sha512.txt'));
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'clearPayloadManifests'
        );
        $methodCall->invoke($bag);
        $this->assertFileNotExists($bag->makeAbsolute('manifest-sha1.txt'));
        $this->assertFileNotExists($bag->makeAbsolute('manifest-sha512.txt'));
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\TagManifest::isTagManifest
     */
    public function testIsTagManifest()
    {
        $bag = Bag::create($this->tmpdir);
        $bag->setExtended(true);
        $bag->update();
        $tagmanifests = $bag->getTagManifests();
        $this->assertArrayHasKey('sha512', $tagmanifests);
        $manifest = $tagmanifests['sha512'];
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\TagManifest',
            'isTagManifest'
        );
        $fullValidName = $bag->makeAbsolute("tagmanifest-sha256.txt");
        $this->assertTrue($methodCall->invokeArgs(
            $manifest,
            [$fullValidName]
        ));
        $fullInvalidName = $bag->makeAbsolute("fetch.txt");
        $this->assertFalse($methodCall->invokeArgs(
            $manifest,
            [$fullInvalidName]
        ));
    }

    /**
     * @group Internal
     *
     * @covers \whikloj\BagItTools\Bag::updateBagInfoIndex
     */
    public function testUpdateBagInfoIndex()
    {
        $expected = [
            'contact-name' => [
                'Jared Whiklo',
            ],
            'source-organization' => [
                'The room.'
            ],
        ];
        $bag = Bag::create($this->tmpdir);
        $property = new \ReflectionProperty($bag, 'bagInfoTagIndex');
        $property->setAccessible(true);

        $bag->addBagInfoTag('Contact-Name', 'Jared Whiklo');
        $bag->addBagInfoTag('SOURCE-ORGANIZATION', 'The room.');

        $result = $property->getValue($bag);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group Internal
     * @covers \whikloj\BagItTools\Bag::convertToHumanReadable
     */
    public function testConvertBytes()
    {
        $bag = Bag::create($this->tmpdir);
        $methodCall = $this->getReflectionMethod(
            '\whikloj\BagItTools\Bag',
            'convertToHumanReadable'
        );
        $this->assertEquals('1.01 KB', $methodCall->invokeArgs(
            $bag,
            [1036]
        ));
        $this->assertEquals('14.31 MB', $methodCall->invokeArgs(
            $bag,
            [15004300]
        ));
        $this->assertEquals('100.00 B', $methodCall->invokeArgs(
            $bag,
            [100]
        ));
        $this->assertEquals('0 B', $methodCall->invokeArgs(
            $bag,
            [0]
        ));
        $this->assertEquals('13.97 GB', $methodCall->invokeArgs(
            $bag,
            [15004300760]
        ));
    }
}
