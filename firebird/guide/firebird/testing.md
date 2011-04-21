# Testing the module

There aren't enough tests currently. It is very unstable and highly experimental.

To test the module you'll need to

*  Create an empty database and set it an alias called `kohana` pointing to the database file
*  Create a database user called `kohana`, with password `kohana` (lowercase)

With all set up, you'll need to go to `/kohana/modules/firebird/tests` and run `phpunit` from the command line. Everything should run fine. If there are any errors, please get in thouch with me via [@Cacovsky](http://twitter.com/Cacovsky).
