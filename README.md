# docodile

Generate HTML API documentation from a Postman collection

Code is terrible at the moment

Usage
-----

Don't forget to run a ```composer install``` first. You may have to ```mkdir cache```

```./docodile generate /path/to/postman/collection.json```

output will be in a directory called output. If you have already generated, it is recommended to ```rm -rf output``` before you regenerate
