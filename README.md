Test exercise.

You can launch the project (after uploading and executing composer install) with 'docker-compose up' command.

Then open http://localhost/index.php/<route_path>.

Used: SQLite,


There are two pages:  http://localhost/index.php/invoices and  http://localhost/index.php/invoice/edit/{id_invoice} 
                      (available by link from http://localhost/index.php/invoices).
                      
As there is a plain functional without pages for  Adding/Editing such entities as Position/Supplier/Recipient - /var/data.db(SQLite) file with filled example-tables of positions/suppliers/recipients are added to the repository.



---------------------------------------


Initial task:


"Zadaniem jest rozwiązanie problemu zapisu faktury. Potrzebny jest model dla faktury,
która posiada nabywcę, dostawcę, pozycje oraz wartość. Pozycja na fakturze
ma swoją nazwę oraz wartość. Faktura może mieć wiele pozycji i wartość faktury
jest sumą jej wszystkich pozycji.

Na danym modelu potrzebujemy mieć możliwość przeprowadzenia akcji utworzenia faktury
z pozycjami oraz usunięcia wybranej pozycji po jej numerze pozycyjnym."
