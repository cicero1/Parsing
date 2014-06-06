##E-catalog Parser  


The program has been written for a test task. ((See test_task)[../blob/master/test_task_php.zip] for the task specification.)  
The intention of the present project is to meet asking requirements and display my skills while keeping it simple.  

________________________________________________________________________________

#####ABOUT
This is a webscraping script which fetches data from the e-catalog in response to user's query, analyzes and parses it. The results are stored in the database and the CVS file.

#####ARCHITECTURE
This project is designed in a modular, flexible and extensible way based on the object-oriented approach. Principles of OOD are adhered to as far as it's reasonable for such a task.  

   ****It's not about how an OO design should be made the best way: There are no full-blown abstract layers worked up. Therefore, extension of the program may demand modification of its components. I also have implemented some specific solutions which are more effective than routine ones. Albeit, it makes further re-use of code difficult.  

#####ALGORITHM
When analyzing user's query, the script tries to guess what goods are being looked for in terms of categories of goods present at the e-catalog. In order to do this the program makes use of Rozetka's embedded search facility. The algorithm is the following:
   - Count number of items containing query string for each category.
   - Calculate a ratio of those to common number of items of each category.
   - Assume the one with the max ratio to be the sought category.
   - Parse all items from it.

#####REQUIREMENTS
PHP >= 5.3 (CLI), MySQL >= 5, Apache.  
PDO_MYSQL, CURL extensions.  

#####HOW TO USE
1. Customize settings in the 'config.php'. Set your actual database connection values. Set the other options if needed.
2. Execute the 'e_catalog_parser.sql' file.
3. Run 'parser.php' via the CLI. Pass query string as a parameter. It has to be a common noun or noun phrase (in Russian), a kind of goods you are seeking for. Try to make it unambiguous and as specific as possible.  
E.g. instead of  
    	`камера`, `память`, `сумка`,  
it should be  
    	`веб-камера`, `оперативная память`, `сумка для ноутбука`.  
The length of string has to be less than 30 characters and 5 words.
4. If you get 'no such items available' - try to alter your query.

_____________________________________________________________________________


Enhancements I am working on:
 - a more intelligent category determination algorithm;
 - performance optimization;
 - enabling exception handling;
  ...  


If you have some suggestions or you need more information please contact me by email.  
  
  
  Best Regards,  
  Vitali Makovijchuk  
  <vitalijob@ukr.net>