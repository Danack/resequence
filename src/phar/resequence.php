<?php


//Define new line
define('NL', "\r\n");

define(RESEQ_DIRECTORY, "reseq");

//List of the image files found.
$filesToResequence = array();


//Map of the image files found, with the extract number from their filename as the key
$mappedFilesToResequence = array();



class ExistingFileException extends Exception {}
class NoGapFoundException extends Exception {}
class DuplicateFileNumberException extends Exception {

	var $firstFilename;
	var $secondFilename;
	var $integer;

	function 	__construct($firstFilename, $secondFilename, $integer, $message, $code = 0 , $previous = NULL ){
		parent::__construct($message, $code, $previous);

		$this->firstFilename = $firstFilename;
		$this->secondFilename = $secondFilename;
		$this->integer = $integer;
	}
}


/*
 * List of image types that reseq will resequence.
 */
$extensionsToMatch = array(
	"bmp",
	"cr2",
	"gif",
	"jpg",
	"png",
	"tif"
);


/**
 * Finds all the image files in the current directory to be resequenced.
 */
function findFilesToResequence(){
	$dir = '.';
	$listFilenames = scandir($dir);

	foreach($listFilenames as $filename){
		addFileIfImage($filename);
	}
}


function debug($string){
	//echo $string.NL;
}


/**
 * Adds the filename to the list if it has an image extension (see $extensionsToMatch)
 *
 * @param $filename
 */
function addFileIfImage($filename){

	$pathParts = pathinfo($filename);

	if(array_key_exists('extension', $pathParts) == TRUE){
		foreach($GLOBALS['extensionsToMatch'] as $extension){
			if(strcasecmp($extension, $pathParts['extension']) == 0){
				$GLOBALS['filesToResequence'][] = $filename;
			}
		}
	}
}


/**
 * Create the symlinks for all the files to be resequenced.
 */
function	createSymLinks(){

	$startSequenceNumber = getLowestMaxFileNumber();

	$index = 0;

	foreach($GLOBALS['mappedFilesToResequence'] as $int => $fileToResequence){
		if($int >= $startSequenceNumber){
			//echo "Remap int $int, filename $fileToResequence to $index\r\n";
			resequence($fileToResequence, $index);
			$index++;
		}
		else{
			//Do nothing
		}
	}

	foreach($GLOBALS['mappedFilesToResequence'] as $int => $fileToResequence){
		if($int >= $startSequenceNumber){
			//Do nothing
		}
		else{
			//echo "Remap int $int, filename $fileToResequence to $index\r\n";
			resequence($fileToResequence, $index);
			$index++;
		}
	}
}

/**
 * For all images:
 * 	Extracts the number in their filename.
 *	Puts the filename in a map with that number as the key or ignores the file if it has no number in it.
 *
 * @throws DuplicateFileNumberException If two files have the same integer embedded in the e.g. IMG_01 and IMG_1 both have the integer 1 as their number, but have different filenames
 */
function	sortFiles(){

	foreach($GLOBALS['filesToResequence'] as $fileToResequence){
		$numbers = filter_var($fileToResequence, FILTER_SANITIZE_NUMBER_INT);

		if($numbers === FALSE || strlen($numbers) == 0){
			//Didn't extract a number, ignoring this file.
			continue;
		}

		$int = intval($numbers);

		if(array_key_exists($int, $GLOBALS['mappedFilesToResequence']) == TRUE){
			throw new DuplicateFileNumberException(
				$GLOBALS['mappedFilesToResequence'][$int],
				$fileToResequence,
				$int,
				"Duplicate file number detected, $int, aborting"
			);
		}

		$GLOBALS['mappedFilesToResequence'][$int] = $fileToResequence;
	}

	ksort($GLOBALS['mappedFilesToResequence']);
}


function getResequencedFilename($indexNumber, $originalFilename){

	$filename = "reseq/$indexNumber";

	$pathParts = pathinfo($originalFilename);

	if(array_key_exists('extension', $pathParts) == TRUE){
		$filename = ".".$pathParts['extension'];
	}

	return $filename;
}

/**
 * Generate a symlink for the file $filename in the subdirectory 'reseq'
 *
 * @param $filename
 * @param $indexNumber
 */
function	resequence($filename, $indexNumber){
	debug("Reseq filename $filename to $indexNumber\r\n");

	$reseqFilename = getResequencedFilename($indexNumber, $filename);

	symlink("../".$filename, $reseqFilename);
}


/**
 * Examines a sequence of numbers that have wrapped around a digit limit cutoff and finds the start of
 * the sequence based on the largest gap
 *
 * e.g. for the sequence:
 *
 * 1, 2, 3, 15, 16, 18, 19
 *
 * The start of the sequence is 15 as the gap between 3 -> 15 is the largest gap.
 *
 *
 * @return bool|int
 * @throws NoGapFoundException Failed to find any gap in the numbers - they don't need resequencing.
 */
function getLowestMaxFileNumber(){

	$previousInt = NULL;
	$largestGap = 0;
	$largestGapEndInt = FALSE;

	foreach($GLOBALS['mappedFilesToResequence'] as $int => $fileToResequence){

		if($previousInt === NULL){
			//Nothing to compare
		}
		else if(($int - 1) == $previousInt){ //no gap
			//$previousInt = $int;
			//echo "Cont $int\r\n";
		}
		else{
			$currentGap = $int - $previousInt;
			//echo "Gap ends at $int\r\n";

			if($currentGap > $largestGap){
				$largestGap = $currentGap;
				$largestGapEndInt = $int;
				//echo "New biggest gap ends at $largestGapEndInt\r\n";
			}
			else{
				//echo "But it's smaller than previous gap\r\n";
			}
		}

		$previousInt = $int;
	}

	if($largestGapEndInt === FALSE){
		//echo "Failed to find any gap, images don't need resequencing?\r\n";
		throw new NoGapFoundException("Failed to find any gap, images don't need resequencing?");
	}

	//echo "Images start at $largestGapEndInt\r\n";
	return $largestGapEndInt;
}

/**
 * Create the directory that will hold the resequenced symlink files.
 * @throws Exception
 */
function	createReseqDirectory(){

	$reseqDirectoryExists = file_exists(RESEQ_DIRECTORY);

	debug("reseqDirectoryExists is [$reseqDirectoryExists]");

	if($reseqDirectoryExists) {
		if(is_dir(RESEQ_DIRECTORY) == FALSE){
			throw new Exception("File exists with name ".RESEQ_DIRECTORY.". Cannot create subdirectory for resequenced files");
		}
	}
	else{
		@mkdir(RESEQ_DIRECTORY);

		if(file_exists(RESEQ_DIRECTORY) == FALSE) {
			throw new Exception("Failed to create subdirectory ".RESEQ_DIRECTORY);
		}
	}
}


/**
 * Checks to make sure that there are no files in the way where we are going to create the symlinks.
 * @throws ExistingFileException
 */
function	checkReseqDirectoryForClashingFiles(){

	foreach($GLOBALS['mappedFilesToResequence'] as $int => $fileToResequence){

		$reseqFilename = getResequencedFilename($int, $fileToResequence);

		if(file_exists($reseqFilename) == TRUE){
			throw new ExistingFileException("File $reseqFilename already exists in the directory ".RESEQ_DIRECTORY." so cannot create symlink. Please empty the ".RESEQ_DIRECTORY." directory before running the reseq tool.");
		}
	}
}



//Begin actual code.
try{

	findFilesToResequence();

	sortFiles();

	createReseqDirectory();

	checkReseqDirectoryForClashingFiles();

	createSymLinks();

	echo "Resequence complete.".NL;
}
catch(ExistingFileException $efe){
	echo $efe->getMessage();
}
catch(DuplicateFileNumberException $dfne){
	echo "Detected duplicate number in files aborting".NL;

	echo "For both of the files [".$dfne->firstFilename."] and [".$dfne->secondFilename."] extracted ".$dfne->integer." as their number, so can't tell what order they should be in.".NL;
}
catch(NoGapFoundException $ngfe){
	echo $ngfe->getMessage();
}
catch(Exception $e){
	echo $e->getMessage();
}




?>