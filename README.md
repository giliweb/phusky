<img src="https://github.com/giliweb/phusky/raw/master/logo.png" height="120" />

#### This guide is not yet completed

### Table of Contents
**[Installation Instructions](#installation-instructions)**
**[Create a new object and insert into DB](#Create-a-new-object-and-insert-into-DB)**
**[Update operation](#Update-operation)**
**[Delete](#Delete)**
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

Phusky will now create a set of PHP classes inside the folder you indicated in the -path parameter

How to use Phusky for your project:

## Create a new object and insert into DB
All of the following examples are based on the data available in the "example_db.sql"

Ok, let's say we want to insert in our DB a new Car object. Model's name is "Testarossa" and we already know the brand's ID is 3:
```
$car = new Car([
    "name" => "Testarossa",
    "brands_id" => 3
]);
$car->create();
```

But what if the brand is not yet present in the "brands" table? Just this simple:
```
$car = new Car([
    "name" => "Testarossa",
    "brand" => [
        "name" => "Ferrari"
    ]
]);
$car->create();
```

Phusky will provide to create and insert in our DB the brand with name "Ferrari" and associate the new car object to the new created brand.

Same way we can bind some dependencies to the car object:
```
$car = new Car([
    "name" => "Testarossa",
    "brand" => [
        "name" => "Ferrari"
    ],
    "colors" => [
        [
            "id" => 3
        ],
        [
            "name" => "Red"
        ]
    ]
]);
$car->create();
```
As you can see we are going to bind 2 colors to the car object. 
The first is already known and present in the DB, we know his ID is 3. The second is not yet in the DB, so we are going to create it.
The output of $car will be:
```
Car Object
(
    [name] => Testarossa
    [brand] => Brand Object
        (
            [name] => Ferrari
            [id] => 26
        )

    [colors] => Array
        (
            [0] => Color Object
                (
                    [id] => 3
                    [name] => Blue
                )

            [1] => Color Object
                (
                    [id] => 13
                    [name] => Red
                )

        )

    [brands_id] => 26
    [id] => 55
)
```
## Read some data from the DB and return it as Object
With Phusky you can get a single record or an array of records. That data will be automatically converted to the right object type as defined in the PHP classes.
To get a single record:
```
$car = Car::getById(3);
```
To get multiple records:
```
$cars = Car::read();
```
To search something you got to pass a closure to the read method, as explained in the [MeekroDB documentation](http://meekro.com/docs.php), ie:
```
$cars = Car::read(function(){
    $where = new \WhereClause('and');
    $where->add("brands_id=%d", 3);
    return $where;
});
```
The output will be something like this:
```
Array
(
    [0] => Car Object
        (
            [id] => 42
            [name] => Testarossa
            [brands_id] => 3
        )

    [1] => Car Object
        (
            [id] => 43
            [name] => Enzo
            [brands_id] => 3
        )

    [2] => Car Object
        (
            [id] => 44
            [name] => LaFerrari
            [brands_id] => 3
        )
)
```
Supposing we want to know which colors are binded to those cars, we can proceed this way:
```
    $car->colors;
    OR
    $cars->colors;
```
The output will include the colors info for each car object.
To get a clean output it's advisable to use output method:
```
    $car->output();
    OR
    $cars->output();
```

## Update operation
As for insert procedure, Phusky will dinamically create/update any dependencies of the main object we are going to update in the DB.
The main difference is we can delete them too!
If we call:
```
$car = Car::getById(3); // load car instance
$car->colors = []; // empty the colors array
$car->update(); // write on DB
```
Phusky will DELETE ALL of the colors from the $car object
Here is another example:
```
$car = Car::getById(3);
$car->colors; // load the colors for this car
unset($car->colors[0]); // manually delete this specific color
$car->update(); // write on DB
```
In this last case Phusky will delete just the first color.

## Delete
```
$car = Car::getById(3);
$car->delete();
```

# TODO
1. handle delete to eventually delete the dependencies too
2. ...
