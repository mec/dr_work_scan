<?php

	/*
		Script to scan Client Work for old jobs

		Usage:

		php archive_scan.php [path to scan] [email address]

		Both arguments are required.
	*/

	// get path from arguments
	$path_to_scan = $argv[1];
	// confrim the path exsists
	if (!file_exists( $path_to_scan )) {
		exit;
	}
	if (!is_dir( $path_to_scan )) {
		exit;
	}
	// get the second argument
	$email_address = $argv[2];

	echo "Running...\n";

	//scan the top level for one level
	$top_level = scandir( $path_to_scan );

	// regex pattern for capital letters only
	$pattern = "/^[A-Z]+$/";

	// sort the array to only show our alphabet folders
	$top_level = preg_grep ( $pattern , $top_level );

	// var to hold the client folders
	$clientPaths = array();

	// var to hold all the jobs
	$jobPaths = array();

	// first loop looking for client folders

	foreach ( $top_level as $top_folder ) {
		// assemble the path
		$path = $path_to_scan . $top_folder;
		echo "Scanning path : " . $path . "\n";

		// scan the directory at the path
		$clients = scandir( $path );
		echo "Finding clients...\n";
		foreach ( $clients as $client ) {
			// we need a path to check
			$thisPath = $path . "/" . $client;

			if ( performScan( $thisPath ) ) { 
				$clientPaths[] = $thisPath;
				echo "Found client : " . $client . "\n";
			}
		}
	}
	
	// we have all the client paths so scan for jobs
	echo "Finding jobs...\n";
	foreach ( $clientPaths as $clientPath ) {
		// scan the client path for jobs
		$jobs = scandir( $clientPath );
		foreach ($jobs as $job) {
			// we need a path to check
			$thisPath = $clientPath . "/" . $job;
			if ( performScan( $thisPath ) ) { 
				$jobPaths[] = $thisPath;
				echo "Found job : " . $job . "\n";
			}
		}

	}	

	// we now have all the jobs so scan them
	echo "Checking jobs...\n";
	// get the current date and time.
	$now = time();
	foreach ($jobPaths as $jobPath) {
		// counters
		$totalJobSize = 0;
		$totalFiles = 0;
		$matchingFiles = 0;

		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jobPath), RecursiveIteratorIterator::SELF_FIRST);
		foreach($objects as $name => $object){
			$totalJobSize = $totalJobSize + filesize( $object );
			$totalFiles ++;

			// get the file atime (last access)
			$atime = fileatime( $object );
			if ( $now - $atime > 31556926 ) {
				$matchingFiles++;			}
		}

		// calculate the percentage of matching files
		$matchingFilesPercentage = $matchingFiles / $totalFiles * 100;
		if ( $matchingFilesPercentage >= 90 AND $totalJobSize > 2000000000 ) {
			// output
			echo "Checking : " . basename($jobPath) . "\n";
			echo "Path : " . $jobPath . "\n";
			echo "Number of files in job : " . $totalFiles . "\n";
			echo "Size of job : " . human_filesize( $totalJobSize ) . "\n";
			echo "Percentage of matching files : " . $matchingFilesPercentage . "%\n\n";
		}
	}
	
	function performScan( $path ) {
		$performScan = TRUE;

		if ( !is_dir( $path ) ) $performScan = FALSE;

		if ( basename ( $path ) == "TheVolumeSettingsFolder") $performScan = FALSE;
		if ( basename ( $path ) == ".") $performScan = FALSE;
		if ( basename ( $path ) == "..") $performScan = FALSE;
		if ( basename ( $path ) == ".DS_Store") $performScan = FALSE;
		if ( basename ( $path ) == "TheFindByContentFolder") $performScan = FALSE;
		if ( basename ( $path ) == "Network Trash Folder") $performScan = FALSE;
		if ( basename ( $path ) == "Icon") $performScan = FALSE;
		if ( basename ( $path ) == "Temporary Items") $performScan = FALSE;
		
		return $performScan;
	}

	function human_filesize($bytes, $decimals = 2) {
	    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	    $factor = floor((strlen($bytes) - 1) / 3);
	    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	}
?>