gUtils
=======

A collection of standalone utilities for PHP developemnt.  These were developed on PHP 5.3.x but may work on earlier versions as well.  Utilities include:

* *Console:* helper for writing command-line scripts, accepting arguments, printing status to the terminal, and minimal support for calling the same scripts from a web browser.
* *PasswordHelper:* utility for creating and verifying bcrypt password hashes and for generating random/temporary passwords
* *RecursiveFileExtensionFilteredIterator:* loops through a directory structure starting at a user-defined path and returns all of the files that match a user defined list of extensions
* *WeightedRandomSelector:* a container that holds a number of items and relative weights that allows you to get random items from the list with the chances of getting an item not random, but based on the weight of the item.