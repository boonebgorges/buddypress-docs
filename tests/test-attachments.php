<?php

class BP_Docs_Attachments_Tests extends BP_Docs_TestCase {
	function test_filename_is_safe() {
		$this->assertTrue( BP_Docs_Attachments::filename_is_safe( 'foo.jpg' ) );

		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( '../foo.jpg' ) );
		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( '.foo.jpg' ) );
		$this->assertFalse( BP_Docs_Attachments::filename_is_safe( 'foo/bar.jpg' ) );
	}
}
