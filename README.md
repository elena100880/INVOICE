### Additional part of STUDY PROJECT - adding invoices


**Launch with Docker in Linux**:

Execute commands:
+ `git clone https://github.com/elena100880/INVOICE-additional-part-of-study-project`

in project folder:
+ `compose install`
+ `docker-compose up`

Then open http://localhost/index.php/<route_path> in your browser.


***
**Dockerfile**

Docker-compose.yaml file in the project folder uses an official image php:7.4-apache.

Also, you can use my Dockerfile from rep: https://github.com/elena100880/dockerfile.

It includes php:8.0-apache official image (or you can change it to php:7.4-apache) and the installation of Composer, XDebug for VSC, Nano, some PHP extensions and enabling using mod rewrite (so you can skip index.php in URLs).

Execute the following commands:

+ `docker build . -t php:8.0-apache-xdebug` in the folder with Dockerfile.
+ `docker run -p -d 80:80 -v "$PWD":/var/www -w="/var/www" php:8.0-apache-xdebug composer install` in the project folder.
+ `docker run -d -p 80:80 -v "$PWD":/var/www --name oo php:8.0-apache-xdebug` in the project folder to launch the project.

   
***
**Used:** SQLite, Select2 with AJAX and multiple choice.

***
**DataBase**

As there is a plain functional without pages for  Adding/Editing such entities as Position/Supplier/Recipient - **/var/data.db** file with filled example-tables of positions/suppliers/recipients are added to the repository.

***
**Pages:**

   + **http://localhost/index.php/invoices** - invoices: list and filter by Supplier, Recipient, Position.
   + **http://localhost/index.php/invoice/add**  - adding a new invoice, link is also available from page `http://localhost/index.php/invoices`. 
   + **http://localhost/index.php/invoice/edit/{id_invoice}**  - editing/deleting the existing invoice, link is also available from page `http://localhost/index.php/invoices`.

  


