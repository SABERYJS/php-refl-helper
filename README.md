# php-refl-helper
this library can help create object of specified class,or call   specified method of specified clas
## why to use this library
if you are a PHP developer, you will know that,PHP has a feature called *Reflection*,with that,you can do cool things,as  follows:
- create object dynamic.
- call method of speciafied class.
this library is inside with you.
## how to use
first,we assume that there  are two class,code is here:
```php
class Apple{
  public function  shape(){
    return 'red';
  }
}
class Person{
  public function eat(Apple $apple){
    echo $apple->shape();
  }
}
```
as you see,there are two class called *Apple* and *Person*,
if you want to call eat method of *Person* ,you can do like this:
```php
ReflHelper::callMethod(Person::class,'eat');
```
ReflHelper will do everything for you,even,you do not need to create Apple object,not only but also,this library also also support a function :
```php
ReflHelper::getInstance(Person::class);
```
upper code will create instance of specified class,parse dependency

## contact 
*email*:saberyjs@gmail.com
*QQ*:1174332406
