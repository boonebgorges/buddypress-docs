<?php

class BP_Docs_Attachments_Tests extends BP_Docs_TestCase {
	function test_filename_is_safe() {
		$this->assertTrue( BP_Docs_Attachments::filename_is_safe( 'foo.jpg' ) );

		// No traversing
		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( '../foo.jpg' ) );

		// No leading dots
		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( '.foo.jpg' ) );

		// No slashes
		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( 'foo/bar.jpg' ) );

		// No forbidden extensions
		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( 'foo.php' ) );

	}
}
