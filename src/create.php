<?php

$buildDir = "/../target/";

$outputFilename = 'resequence.phar';

if (!Phar::canWrite()) {
	die("Phar is in read-only mode, try php -d phar.readonly=0 resequence/create.php\n");
}

$buildPath = __DIR__ . $buildDir.$outputFilename;

//echo "DIR is ".__DIR__."\r\n";

@unlink($buildPath);
$p = new Phar($buildPath, 0, $outputFilename);
$result = $p->buildFromDirectory(__DIR__."/phar/");

//var_dump($result);

$stub = <<<'EOD'
#!/usr/bin/env php
<?php
//Phar::interceptFileFuncs();
include "phar://" . __FILE__ . "/resequence.php";
__HALT_COMPILER();
EOD;
$p->setStub($stub);

chmod($buildPath, 0744);