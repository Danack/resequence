Reseq
=====

Reseq is a command line tool that resequences photo files so that they can be loaded automatically into software for making timelapse videos (e.g. Quicktime, ffmpeg etc).

On Mac and Linux it does NOT make copies of photos, nor does it move the original files. Instead it makes symlinks (http://en.wikipedia.org/wiki/Symbolic_link) that point to the original files, to avoid using up diskspace. This means that the original files must be left in place for the 

As Windows does not properly support symbolic links, the files are instead copied.

How to install
==============

1) Extract the files from this archive to somewhere on your computer.

2) Double-click on the file 'install'. This should copy the file 'reseq' and 'resequence.phar' to the directory /usr/bin and make 'reseq' be executable. 

Alternative you can copy the files 'reseq' and 'resequence.phar' to any directory on your computer, and then ensure that that directory is findable in the path variable.


How to use
==========

1) Open terminal.

2) Navigate to the directory that contains the photos you wish to resequence.

3) Type 'reseq' and hit enter.

Reseq will create a directory called 'reseq' that will create symlinks to the original photos.

You must either remove the reseq directory or empty it to run the reseq tool again, as otherwise the resequenced files from the previous run will be in the way.


How to compile
==============

This is an optional step. You can compile the 'resequence.phar' file yourself by running the command create in the root directory of the extracted archive. This will build the 'resequence.phar' file and copy it to the /usr/bin directory.
