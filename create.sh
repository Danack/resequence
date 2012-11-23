#!/bin/bash

php -d phar.readonly=0 src/create.php

cp target/resequence.phar /usr/bin/resequence.phar