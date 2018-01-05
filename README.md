# docodile

Generate HTML API documentation from a Postman collection

Code is terrible at the moment

Usage
-----

Don't forget to run a ```composer install``` first

```./docodile generate /path/to/postman/collection.json /my/output/directory```

if ```/my/output/directory``` already exists the process will exit as continuing will delete that directory completely. --force will force deletion

Theming
-------

Docodile now supports switching of themes from bootswatch (https://bootswatch.com/3/) and highlightjs' bundled styles (https://highlightjs.org/)

For example:
```./docodile generate ../core/webservice/docs/Core.json.postman_collection output --force -blumen -jgruvbox-light```
this will use the lumen theme for the overall css and the gruvbox-light style for all syntax highlighting
