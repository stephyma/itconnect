IT Connect links you to information technology (IT) resources at the UW and helps you find services, often providing information on how to use them.

Copyright 2012 UW Information Technology, University of Washington

Developer quickstart:

This quickstart assumes you have a Linux Apache MySQL PHP (LAMP) environment.

1. Clone this site:

    git://github.com/abztrakt/itconnect.git

2. Clone the parent and child themes:

    cd itconnect/wp-content/themes
    git clone git://github.com/uweb/UW-Wordpress-Theme.git
    git clone git://github.com/abztrakt/ITConnect-UW-WP-Child.git

3. Create a MySQL database and user:

    mysql> create database itconnect;
    mysql> grant all on itconnect.* to <user>@localhost identified by 'some password';

4. Copy the sample config to wp-config.php:

    cd ../..
    cp wp-config-sample.php wp-config.php

5. Edit wp-config.php with your database settings and Auth keys.

6. ... more to come soon?
