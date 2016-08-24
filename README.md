### Table of Contents
**[Installation Instructions](#installation-instructions)**  


## Installation Instructions
1. Run: 
```
	composer require giliweb/phusky
```
2. Open composer.json and add
```
    "scripts": {
    	"phusky_setup": "giliweb\\phusky\\Install::init"
    }
```
3. Run
```
	composer phusky_setup {classes folder} {db host} {db name} {db_user} {db_password}
```

    

