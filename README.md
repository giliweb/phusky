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
	composer phusky_setup {classes folder} {db host} {db name} {db_user} {db_password}
```

    

