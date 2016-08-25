### Table of Contents
**[Installation Instructions](#installation-instructions)**
## Installation Instructions
Run:
```
	composer require giliweb/phusky
```
Open composer.json and add
```
    "scripts": {
    	"phusky_setup": "giliweb\\phusky\\Install::init"
    }
```
Run
```
	composer phusky_setup -- -path=classes_folder -dbhost=db_host -dbname=db_name -dbuser=db_user -dbpassword=db_password
```



