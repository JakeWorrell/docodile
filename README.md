# docodile

Generate HTML API documentation from a Postman collection

Code is terrible at the moment

Usage
-----

Don't forget to run a ```composer install``` first. You may have to ```mkdir cache```

```./docodile generate /path/to/postman/collection.json /my/output/directory```

if ```/my/output/directory``` already exists the process will exit as continuing will delete that directory completely. --force will force deletion
