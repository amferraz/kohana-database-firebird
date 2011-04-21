# Using the module

To enable the module, you must ensure that your `bootstrap.php` is properly configured. You'll need to the following line to the enabling module section.
    
         'firebird'      => MODPATH.'firebird',       //Firebird Driver

[!!]  Be sure to add `'firebird'` module  prior to the `'database'` module.

You'll also need to copy the sample config file located in `firebird/config/database.php` to your application `config` directory and adjust it to fit your needs.
    