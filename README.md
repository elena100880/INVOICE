### Additional part of STUDY PROJECT - adding invoices

You can launch the project (after uploading and executing composer install) with `docker-compose up` command.
Then open **http://localhost/index.php/"route_path"**.

**Used:** SQLite, Select2 with AJAX.

**Pages:**

   + **http://localhost/index.php/invoices** - invoices: list and filter by Supplier, Recipient, Position.
   + **http://localhost/index.php/invoice/add** (not finished yet!!) - adding a new invoice, link is also available from page `http://localhost/index.php/invoices`.   

  
As there is a plain functional without pages for  Adding/Editing such entities as Position/Supplier/Recipient - **/var/data.db** file with filled example-tables of positions/suppliers/recipients are added to the repository.

