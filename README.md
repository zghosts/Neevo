Neevo - Tiny open-source database abstraction layer for PHP

Info
====

- Available under MIT license (http://www.opensource.org/licenses/mit-license.php)
- Author: Martin Srank - Smasty (http://smasty.net)
- Website (temporary): http://labs.smasty.net/neevo/
- Public API (temporary): http://labs.smasty.net/neevo/doc/

About Neevo
===========

First of all, thank you for downloading Neevo!

Neevo is a very small, fully object-oriented database abstraction layer for PHP.
It's open-source and released under the terms and conditions of the MIT license.

Neevo allows you to easily write SQL queries for different SQL drivers
in unified syntax with the use of Object-oriented PHP and fluent interfaces.
Of course, Neevo automatically escapes all code to avoid SQL Injection attacs, etc.

Neevo currently supports only one SQL driver - MySQL (through PHP extension 'mysql')
but more drivers are being prepared: PostgreSQL and SQLite for now. Neevo also offers
an Interface and Public API for other programmers, so new drivers can be easily added.


Features
========
 - SELECT queries (JOINs not supported)
 - INSERT queries
 - UPDATE queries
 - DELETE queries

 - Multiple drivers support (*)
 - Fetch results: "one method to rule them all" - only one intelligent method
   for fetching data from database.
 - Affected rows
 - Retrieved rows
 - Seek
 - Table prefix support
 - Query execution time
 - Last executed query
 - Executed queries counter
 - Query and connection info
 - Randomize result order
 - Dump queries
 - "Undo" - Removes some piece from already built queries
   (e.g. "2nd WHERE condition" or "column `email`")
 - Multi-level error-reporting system (based on Exceptions):
    - E_NONE:    No errors and warnings are reported.
    - E_CATCH:   All errors and warnings are handled by defined error handler.
    - E_WARNING: Warnings are handled, errros thrown only.
    - E_STRICT:  All errors and warnings are thrown only.
 - Ability to use your own error handler function.
 - One-file-only minified version
    (Thanks to Jakub Vrana - http://php.vrana.cz and his Adminer - http://adminer.org)


Supported drivers
=================
 - MySQL (PHP extension 'mysql')


Todo
====

 - Better site and Public API documentation ;-)

 - PostgreSQL driver
 - SQLite driver
 - Show database and table structure
 - MySQLi driver


Compiler
========

Neevo comes with what I call "compiler" - PHP CLI (command-line interface) script
which simplifies some boring work for me: Minifies source to one file without
comments and whitespace, regenerates PHPDoc and increments revision number.

Usage: $ php compiler [help] [rev+|rev-] [doc [-<config>]] [min|min+] [<filename>]

  help        Displays help
  rev+        Increments REVISION in <filename>
  rev-        Decrements REVISION in <filename>
  doc         Runs PHPDoc generator
  -<config>   PHPDoc config file; if not set, default used
  min         Minifies source code of <filename>
  min+        + shorten var names.
  <filename>  File to compile; if not set, default used

Minification part is made up from functions written by Jakub Vrana (http://php.vrana.cz)
for his Adminer "Compact MySQL management" (http://adminer.org) licensed under
Apache license 2.0 and are used with his permission.