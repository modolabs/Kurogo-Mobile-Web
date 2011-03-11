<?php
		$file = $_GET['file'];
		$file = $jpeg_temp_dir . '/' . $file;
		
		$file = basename( $file );
		$file = addcslashes( $file, '/\\' );
		$f = fopen( "$file", 'r' );
		$jpeg = fread( $f, filesize( "$file" ) );
		fclose( $f );
		
		Header( "Content-type: image/jpeg" );
		Header( "Content-disposition: inline; filename=photo.jpg" );
		echo $jpeg;
?>