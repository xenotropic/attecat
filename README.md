attecat
=======

Student attendance tracking software in php, sqlite, jquery-ui. Tracks attendance based on a numerical score, as well as daily notes for each student and an overall notepad for each student.

REQUIREMENTS
============

Apache web server with PHP and SQLite enabled. Tested with apache2 on Ubuntu linux. 


INSTALLATION
============

Put the source code into a directory served by apache. 

USE
===

After installation, set a password on the "admin" tab. To add students, go to the "Add Group" tab and enter a list of students separated by commas or newlines. 

Note that in order to stay logged in, you must have the computer open and connected to the internet. (This may vary depending on the web server's configuration).

To keep attendance, use the "Show Group" tab, and select the group for which you want to keep attendance. Choose the date in the "New Attendance For" text entry field. The "Attendance" column is for a daily attendance score. It can be any textual value, although future versions will do averaging of daily scores. An entry of "A" (without the quotes) is treated as absent and highlighted in yellow. 


TO-DO LIST
==========

There is currently no way to edit or delete daily records. Create such a method.

There is currently no way to edit or delete students, or to add an individual student. Create such methods.

Ensure sessions persist for a longer period of time.


